<?php

/**
 * Code related to the settings-webinfo.php interface.
 *
 * @package Sucuri Security
 * @subpackage settings-webinfo.php
 * @copyright Since 2010 Sucuri Inc.
 */

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Gather information from the server, database engine, and PHP interpreter.
 *
 * @return string HTML for the system info page.
 */
function sucuriscan_settings_webinfo_details()
{
    global $wpdb;

    $params = array(
        'ServerInfo.Variables' => '',
    );

    $info_vars = array(
        'Plugin_version' => SUCURISCAN_VERSION,
        'Last_filesystem_scan' => SucuriScanFSScanner::getFilesystemRuntime(true),
        'Datetime_and_Timezone' => '',
        'Operating_system' => sprintf('%s (%d Bit)', PHP_OS, PHP_INT_SIZE * 8),
        'Server' => __('Unknown', SUCURISCAN_TEXTDOMAIN),
        'Developer_mode' => __('NotActive', SUCURISCAN_TEXTDOMAIN),
        'Memory_usage' => __('Unknown', SUCURISCAN_TEXTDOMAIN),
        'MySQL_version' => '0.0',
        'SQL_mode' => __('NotSet', SUCURISCAN_TEXTDOMAIN),
        'PHP_version' => PHP_VERSION,
    );

    $info_vars['Datetime_and_Timezone'] = sprintf(
        '%s (GMT %s)',
        SucuriScan::currentDateTime(),
        SucuriScanOption::getOption('gmt_offset')
    );

    if (defined('WP_DEBUG') && WP_DEBUG) {
        $info_vars['Developer_mode'] = __('Active', SUCURISCAN_TEXTDOMAIN);
    }

    if (function_exists('memory_get_usage')) {
        $info_vars['Memory_usage'] = round(memory_get_usage() / 1024 / 1024, 2).' MB';
    }

    if (isset($_SERVER['SERVER_SOFTWARE'])) {
        $info_vars['Server'] = $_SERVER['SERVER_SOFTWARE'];
    }

    if ($wpdb) {
        $info_vars['MySQL_version'] = $wpdb->get_var('SELECT VERSION() AS version');

        $mysql_info = $wpdb->get_results('SHOW VARIABLES LIKE "sql_mode"');
        if (is_array($mysql_info) && !empty($mysql_info[0]->Value)) {
            $info_vars['SQL_mode'] = $mysql_info[0]->Value;
        }
    }

    $field_names = array(
        'safe_mode',
        'expose_php',
        'allow_url_fopen',
        'memory_limit',
        'upload_max_filesize',
        'post_max_size',
        'max_execution_time',
        'max_input_time',
    );

    foreach ($field_names as $php_flag) {
        $php_flag_value = SucuriScan::iniGet($php_flag);
        $php_flag_name = 'PHP_' . $php_flag;
        $info_vars[$php_flag_name] = $php_flag_value ? $php_flag_value : 'N/A';
    }

    foreach ($info_vars as $var_name => $var_value) {
        $var_name = str_replace('_', "\x20", $var_name);

        $params['ServerInfo.Variables'] .=
        SucuriScanTemplate::getSnippet('settings-webinfo-details', array(
            'ServerInfo.Title' => $var_name,
            'ServerInfo.Value' => $var_value,
        ));
    }

    return SucuriScanTemplate::getSection('settings-webinfo-details', $params);
}

/**
 * Retrieve all the constants and variables with their respective values defined
 * in the WordPress configuration file, only the database password constant is
 * omitted for security reasons.
 *
 * @return string The HTML code displaying the constants and variables found in the wp-config file.
 */
function sucuriscan_settings_webinfo_wpconfig()
{
    $params = array(
        'WordpressConfig.Rules' => '',
        'WordpressConfig.Total' => 0,
    );

    $ignore_wp_rules = array('DB_PASSWORD');
    $wp_config_path = SucuriScan::getWPConfigPath();

    if ($wp_config_path) {
        $wp_config_rules = array();
        $wp_config_content = SucuriScanFileInfo::fileLines($wp_config_path);

        // Parse the main configuration file and look for constants and global variables.
        foreach ((array) $wp_config_content as $line) {
            if (@preg_match('/^\s?(#|\/\/)/', $line)) {
                continue; /* Ignore commented lines. */
            } elseif (@preg_match('/define\(/', $line)) {
                // Detect PHP constants even if the line if indented.
                $line = preg_replace('/.*define\((.+)\);.*/', '$1', $line);
                $line_parts = explode(',', $line, 2);
            } elseif (@preg_match('/^\$[a-zA-Z_]+/', $line)) {
                // Detect global variables like the database table prefix.
                $line = @preg_replace('/;\s\/\/.*/', ';', $line);
                $line_parts = explode('=', $line, 2);
            } else {
                continue; /* Ignore other lines. */
            }

            // Clean and append the rule to the wp_config_rules variable.
            if (isset($line_parts) && count($line_parts) === 2) {
                $key_name = '';
                $key_value = '';

                // TODO: A foreach loop is not really necessary, find a better way.
                foreach ($line_parts as $i => $line_part) {
                    $line_part = trim($line_part);
                    $line_part = ltrim($line_part, '$');
                    $line_part = rtrim($line_part, ';');

                    // Remove single/double quotes at the beginning and end of the string.
                    $line_part = ltrim($line_part, "'");
                    $line_part = rtrim($line_part, "'");
                    $line_part = ltrim($line_part, '"');
                    $line_part = rtrim($line_part, '"');

                    // Assign the clean strings to specific variables.
                    if ($i == 0) {
                        $key_name = $line_part;
                    }

                    if ($i == 1) {
                        if (defined($key_name)) {
                            $key_value = constant($key_name);

                            if (is_bool($key_value)) {
                                $key_value = ($key_value === true) ? 'True' : 'False';
                            }
                        } else {
                            $key_value = $line_part;
                        }
                    }
                }

                // Remove the value of sensitive variables like the database password.
                if (in_array($key_name, $ignore_wp_rules)) {
                    $key_value = 'hidden';
                }

                // Append the value to the configuration rules.
                $wp_config_rules[$key_name] = $key_value;
            }
        }

        // Pass the WordPress configuration rules to the template and show them.
        foreach ($wp_config_rules as $var_name => $var_value) {
            if (empty($var_value)) {
                $var_value = '--';
            }

            $params['WordpressConfig.Total'] += 1;
            $params['WordpressConfig.Rules'] .=
            SucuriScanTemplate::getSnippet('settings-webinfo-wpconfig', array(
                'WordpressConfig.VariableName' => $var_name,
                'WordpressConfig.VariableValue' => $var_value,
            ));
        }
    }

    return SucuriScanTemplate::getSection('settings-webinfo-wpconfig', $params);
}

/**
 * Find the main htaccess file for the site and check whether the rules of the
 * main htaccess file of the site are the default rules generated by WordPress.
 *
 * @return string The HTML code displaying the information about the HTAccess rules.
 */
function sucuriscan_settings_webinfo_htaccess()
{
    $htaccess = SucuriScan::getHtaccessPath();
    $params = array(
        'HTAccess.Content' => '',
        'HTAccess.TextareaVisible' => 'hidden',
        'HTAccess.StandardVisible' => 'hidden',
        'HTAccess.NotFoundVisible' => 'hidden',
        'HTAccess.FoundVisible' => 'hidden',
        'HTAccess.Fpath' => __('Unknown', SUCURISCAN_TEXTDOMAIN),
    );

    if ($htaccess) {
        $rules = SucuriScanFileInfo::fileContent($htaccess);

        $params['HTAccess.TextareaVisible'] = 'visible';
        $params['HTAccess.Content'] = $rules;
        $params['HTAccess.Fpath'] = $htaccess;
        $params['HTAccess.FoundVisible'] = 'visible';

        if (sucuriscan_htaccess_is_standard($rules)) {
            $params['HTAccess.StandardVisible'] = 'visible';
        }
    } else {
        $params['HTAccess.NotFoundVisible'] = 'visible';
    }

    return SucuriScanTemplate::getSection('settings-webinfo-htaccess', $params);
}

/**
 * Check if the standard rules for a normal WordPress installation (not network
 * based) are inside the main htaccess file. This only applies to websites that
 * have permalinks enabled, or 3rd-party plugins that require custom rules
 * (generally based on mod_deflate) to compress and/or generate static files for
 * cache.
 *
 * @param string $rules Content of the main htaccess file.
 * @return bool True if the htaccess has the standard rules, false otherwise.
 */
function sucuriscan_htaccess_is_standard($rules = '')
{
    if (!$rules) {
        if ($htaccess = SucuriScan::getHtaccessPath()) {
            $rules = SucuriScanFileInfo::fileContent($htaccess);
        }
    }

    if (class_exists('WP_Rewrite') && is_string($rules) && !empty($rules)) {
        $rewrite = new WP_Rewrite();
        $standard = $rewrite->mod_rewrite_rules();

        if (!empty($standard)) {
            return (bool) (strpos($rules, $standard) !== false);
        }
    }

    return false;
}
