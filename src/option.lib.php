<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Plugin options handler.
 *
 * Options are pieces of data that WordPress uses to store various preferences
 * and configuration settings. Listed below are the options, along with some of
 * the default values from the current WordPress install. By using the
 * appropriate function, options can be added, changed, removed, and retrieved,
 * from the wp_options table.
 *
 * The Options API is a simple and standardized way of storing data in the
 * database. The API makes it easy to create, access, update, and delete
 * options. All the data is stored in the wp_options table under a given custom
 * name. This page contains the technical documentation needed to use the
 * Options API. A list of default options can be found in the Option Reference.
 *
 * Note that the _site_ functions are essentially the same as their
 * counterparts. The only differences occur for WP Multisite, when the options
 * apply network-wide and the data is stored in the wp_sitemeta table under the
 * given custom name.
 *
 * @see https://codex.wordpress.org/Option_Reference
 * @see https://codex.wordpress.org/Options_API
 */
class SucuriScanOption extends SucuriScanRequest
{
    /**
     * Default values for all the plugin's options.
     *
     * @return array Default values for all the plugin's options.
     */
    private static function getDefaultOptionValues()
    {
        $defaults = array(
            'sucuriscan_account' => '',
            'sucuriscan_addr_header' => 'HTTP_X_SUCURI_CLIENTIP',
            'sucuriscan_api_handler' => 'curl',
            'sucuriscan_api_key' => false,
            'sucuriscan_api_protocol' => 'https',
            'sucuriscan_api_service' => 'enabled',
            'sucuriscan_audit_report' => 'disabled',
            'sucuriscan_cloudproxy_apikey' => '',
            'sucuriscan_collect_wrong_passwords' => 'disabled',
            'sucuriscan_comment_monitor' => 'disabled',
            'sucuriscan_datastore_path' => dirname(self::optionsFilePath()),
            'sucuriscan_dismiss_setup' => 'disabled',
            'sucuriscan_dns_lookups' => 'enabled',
            'sucuriscan_email_subject' => 'Sucuri Alert, :domain, :event',
            'sucuriscan_emails_per_hour' => 5,
            'sucuriscan_emails_sent' => 0,
            'sucuriscan_errorlogs_limit' => 30,
            'sucuriscan_fs_scanner' => 'enabled',
            'sucuriscan_ignore_scanning' => 'disabled',
            'sucuriscan_ignored_events' => '',
            'sucuriscan_language' => 'en_US',
            'sucuriscan_last_email_at' => time(),
            'sucuriscan_lastlogin_redirection' => 'enabled',
            'sucuriscan_logs4report' => 500,
            'sucuriscan_maximum_failed_logins' => 30,
            'sucuriscan_notify_available_updates' => 'disabled',
            'sucuriscan_notify_bruteforce_attack' => 'disabled',
            'sucuriscan_notify_failed_login' => 'enabled',
            'sucuriscan_notify_plugin_activated' => 'disabled',
            'sucuriscan_notify_plugin_change' => 'disabled',
            'sucuriscan_notify_plugin_deactivated' => 'disabled',
            'sucuriscan_notify_plugin_deleted' => 'disabled',
            'sucuriscan_notify_plugin_installed' => 'disabled',
            'sucuriscan_notify_plugin_updated' => 'disabled',
            'sucuriscan_notify_post_publication' => 'enabled',
            'sucuriscan_notify_scan_checksums' => 'disabled',
            'sucuriscan_notify_settings_updated' => 'disabled',
            'sucuriscan_notify_success_login' => 'enabled',
            'sucuriscan_notify_theme_activated' => 'disabled',
            'sucuriscan_notify_theme_deleted' => 'disabled',
            'sucuriscan_notify_theme_editor' => 'enabled',
            'sucuriscan_notify_theme_installed' => 'disabled',
            'sucuriscan_notify_theme_updated' => 'disabled',
            'sucuriscan_notify_to' => '',
            'sucuriscan_notify_user_registration' => 'disabled',
            'sucuriscan_notify_website_updated' => 'disabled',
            'sucuriscan_notify_widget_added' => 'disabled',
            'sucuriscan_notify_widget_deleted' => 'disabled',
            'sucuriscan_parse_errorlogs' => 'enabled',
            'sucuriscan_plugin_version' => '0.0',
            'sucuriscan_prettify_mails' => 'disabled',
            'sucuriscan_request_timeout' => 5,
            'sucuriscan_revproxy' => 'disabled',
            'sucuriscan_runtime' => 0,
            'sucuriscan_scan_checksums' => 'enabled',
            'sucuriscan_scan_errorlogs' => 'disabled',
            'sucuriscan_scan_frequency' => 'twicedaily',
            'sucuriscan_scan_interface' => 'spl',
            'sucuriscan_selfhosting_fpath' => '',
            'sucuriscan_selfhosting_monitor' => 'disabled',
            'sucuriscan_site_version' => '0.0',
            'sucuriscan_sitecheck_counter' => 0,
            'sucuriscan_sitecheck_timeout' => 30,
            'sucuriscan_use_wpmail' => 'enabled',
        );

        return $defaults;
    }

    /**
     * Name of all valid plugin's options.
     *
     * @return array Name of all valid plugin's options.
     */
    private static function getDefaultOptionNames()
    {
        $options = self::getDefaultOptionValues();
        $names = array_keys($options);

        return $names;
    }

    /**
     * Retrieve the default values for some specific options.
     *
     * @param  string|array $settings Either an array that will be complemented or a string with the name of the option.
     * @return string|array           The default values for the specified options.
     */
    private static function getDefaultOptions($settings = '')
    {
        $default_options = self::getDefaultOptionValues();

        // Use framework built-in function.
        if (function_exists('get_option')) {
            $admin_email = get_option('admin_email');
            $default_options['sucuriscan_account'] = $admin_email;
            $default_options['sucuriscan_notify_to'] = $admin_email;
        }

        if (is_array($settings)) {
            foreach ($default_options as $option_name => $option_value) {
                if (!isset($settings[ $option_name ])) {
                    $settings[ $option_name ] = $option_value;
                }
            }

            return $settings;
        }

        if (is_string($settings)
            && !empty($settings)
            && array_key_exists($settings, $default_options)
        ) {
            return $default_options[ $settings ];
        }

        return false;
    }

    /**
     * Check if the settings will be stored in the database.
     *
     * Since version 1.7.18 the plugin started using plain text files to store
     * its settings as a security measure to reduce the scope of the attacks
     * against the database and to simplify the management of the settings for
     * multisite installations. Some users complained about this and suggested
     * to create an option to allow them to keep using the database instead of
     * plain text files.
     *
     * We will not add an explicit option in the settings page, but users can go
     * around this defining a constant in the configuration file named
     * "SUCURI_SETTINGS_IN" with value "database" to force the plugin to store
     * its settings in the database instead of the plain text files.
     *
     * @return boolean True if the settings will be stored in the database.
     */
    public static function settingsInDatabase()
    {
        return (bool) (
            defined('SUCURI_SETTINGS_IN')
            && SUCURI_SETTINGS_IN === 'database'
        );
    }

    /**
     * Check if the settings will be stored in a plain text file.
     *
     * @return boolean True if the settings will be stored in a file.
     */
    public static function settingsInTextFile()
    {
        return (bool) (self::settingsInDatabase() === false);
    }

    /**
     * Returns path of the options storage.
     *
     * Returns the absolute path of the file that will store the options
     * associated to the plugin. This must be a PHP file without public access,
     * for which reason it will contain a header with an exit point to prevent
     * malicious people to read the its content. The rest of the file will
     * content a JSON encoded array.
     *
     * @return string File path of the options storage.
     */
    public static function optionsFilePath()
    {
        $content_dir = defined('WP_CONTENT_DIR')
            ? rtrim(WP_CONTENT_DIR, '/')
            : ABSPATH . '/wp-content';
        $folder = $content_dir . '/uploads/sucuri';

        if (defined('SUCURI_DATA_STORAGE')
            && file_exists(SUCURI_DATA_STORAGE)
            && is_dir(SUCURI_DATA_STORAGE)
        ) {
            $folder = SUCURI_DATA_STORAGE;
        }

        return self::fixPath($folder . '/sucuri-settings.php');
    }

    /**
     * Returns an array with all the plugin options.
     *
     * NOTE: There is a maximum number of lines for this file, one is for the
     * exit point and the other one is for a single line JSON encoded string.
     * We will discard any other content that exceeds this limit.
     *
     * @return array Array with all the plugin options.
     */
    public static function getAllOptions()
    {
        $options = array();
        $fpath = self::optionsFilePath();

        /* Use this over SucuriScanCache to prevent nested function calls */
        $content = SucuriScanFileInfo::fileContent($fpath);

        if ($content !== false) {
            // Refer to self::optionsFilePath to know why the number two.
            $lines = explode("\n", $content, 2);

            if (count($lines) >= 2) {
                $jsonData = json_decode($lines[1], true);

                if (is_array($jsonData) && !empty($jsonData)) {
                    $options = $jsonData;
                }
            }
        }

        return $options;
    }

    /**
     * Write new options into the external options file.
     *
     * @param  array   $options Array with plugins options.
     * @return boolean          True if the new options were saved, false otherwise.
     */
    public static function writeNewOptions($options = array())
    {
        $fpath = self::optionsFilePath();
        $content = "<?php exit(0); ?>\n";
        $content .= @json_encode($options) . "\n";

        return (bool) @file_put_contents($fpath, $content);
    }

    /**
     * Returns data from the settings file or the database.
     *
     * To facilitate the development, you can prefix the name of the key in the
     * request (when accessing it) with a single colon, this function will
     * automatically replace that character with the unique identifier of the
     * plugin.
     *
     * NOTE: The SucuriScanCache library is a better interface to read the
     * content of a configuration file following the same standard in the other
     * files associated to the plugin. However, this library makes use of this
     * function to retrieve the directory where the files are stored, if we use
     * this library for this specific task we will end up provoking a maximum
     * nesting function call warning.
     *
     * @param  string $option Name of the setting that will be retrieved.
     * @return string         Option value, or default value if empty.
     */
    public static function getOption($option = '')
    {
        $options = self::getAllOptions();
        $option = self::varPrefix($option);

        if (array_key_exists($option, $options)) {
            return $options[$option];
        }

        /**
         * Fallback to the default values.
         *
         * If the option is not set in the external options file then we must
         * search in the database for older data, this to provide backward
         * compatibility with older installations of the plugin. If the option
         * is found in the database we must insert it in the external file and
         * delete it from the database before the value is returned to the user.
         *
         * If the option is not in the external file nor in the database, and
         * the name starts with the same prefix used by the plugin then we must
         * return the default value defined by the author.
         *
         * Note that if the plain text file is not writable the function should
         * not delete the option from the database to keep backward compatibility
         * with previous installations of the plugin.
         */
        if (function_exists('get_option')) {
            $value = get_option($option);

            if ($value !== false) {
                if (strpos($option, 'sucuriscan_') === 0) {
                    $written = self::updateOption($option, $value);

                    if ($written === true) {
                        delete_option($option);
                    }
                }

                return $value;
            }
        }

        if (strpos($option, 'sucuriscan_') === 0) {
            return self::getDefaultOptions($option);
        }

        return false;
    }

    /**
     * Update the value of an database' option.
     *
     * Use the function to update a named option/value pair to the options database
     * table. The option name value is escaped with a special database method before
     * the insert SQL statement but not the option value, this value should always
     * be properly sanitized.
     *
     * @see https://codex.wordpress.org/Function_Reference/update_option
     *
     * @param  string  $option Name of the option to update, must not exceed 64 characters.
     * @param  string  $value  New value, either an integer, string, array, or object.
     * @return boolean         True if option value has changed, false otherwise.
     */
    public static function updateOption($option = '', $value = '')
    {
        if (strpos($option, ':') === 0 || strpos($option, 'sucuriscan') === 0) {
            $options = self::getAllOptions();
            $option = self::varPrefix($option);
            $options[$option] = $value;

            // Skip if user wants to use the database.
            if (self::settingsInTextFile() && self::writeNewOptions($options)) {
                return true;
            }
        }

        if (function_exists('update_option')) {
            return update_option($option, $value);
        }

        return false;
    }

    /**
     * Remove an option from the database.
     *
     * A safe way of removing a named option/value pair from the options database table.
     *
     * @see https://codex.wordpress.org/Function_Reference/delete_option
     *
     * @param  string  $option Name of the option to be deleted.
     * @return boolean         True, if option is successfully deleted. False on failure, or option does not exist.
     */
    public static function deleteOption($option = '')
    {
        if (strpos($option, ':') === 0 || strpos($option, 'sucuriscan') === 0) {
            $options = self::getAllOptions();
            $option = self::varPrefix($option);

            // Create/Modify option's value.
            if (array_key_exists($option, $options)) {
                unset($options[$option]);

                return self::writeNewOptions($options);
            }
        }

        if (function_exists('delete_option')) {
            return delete_option($option);
        }

        return false;
    }

    /**
     * Check whether a setting is enabled or not.
     *
     * @param  string  $option Name of the option to be deleted.
     * @return boolean         True if the option is enabled, false otherwise.
     */
    public static function isEnabled($option = '')
    {
        return (bool) (self::getOption($option) === 'enabled');
    }

    /**
     * Check whether a setting is disabled or not.
     *
     * @param  string  $option Name of the option to be deleted.
     * @return boolean         True if the option is disabled, false otherwise.
     */
    public static function isDisabled($option = '')
    {
        return (bool) (self::getOption($option) === 'disabled');
    }

    /**
     * Delete all the plugin options from the database.
     *
     * @return void
     */
    public static function deletePluginOptions()
    {
        global $wpdb;

        $options = $wpdb->get_results(
            "SELECT * FROM {$wpdb->options}
            WHERE option_name LIKE 'sucuriscan%'
            ORDER BY option_id ASC"
        );

        foreach ($options as $option) {
            delete_option($option->option_name);
        }

        // Merge with the default options to ensure full cleanup.
        $default = self::getDefaultOptionNames();

        foreach ($default as $option) {
            if (is_string($option)) {
                self::deleteOption($option);
            }
        }
    }

    /**
     * Retrieve all the options stored by Wordpress in the database. The options
     * containing the word "transient" are excluded from the results, this function
     * is compatible with multisite instances.
     *
     * @return array All the options stored by Wordpress in the database, except the transient options.
     */
    private static function getSiteOptions()
    {
        global $wpdb;

        $settings = array();
        $results = $wpdb->get_results(
            "SELECT * FROM {$wpdb->options}
            WHERE option_name NOT LIKE '%_transient_%'
            ORDER BY option_id ASC"
        );

        foreach ($results as $row) {
            $settings[ $row->option_name ] = $row->option_value;
        }

        $external = self::getAllOptions();

        foreach ($external as $option => $value) {
            $settings[$option] = $value;
        }

        return $settings;
    }

    /**
     * Check what Wordpress options were changed comparing the values in the database
     * with the values sent through a simple request using a GET or POST method.
     *
     * @param  array $request The content of the global variable GET or POST considering SERVER[REQUEST_METHOD].
     * @return array          A list of all the options that were changes through this request.
     */
    public static function whatOptionsWereChanged($request = array())
    {
        $options_changed = array(
            'original' => array(),
            'changed' => array()
        );

        $site_options = self::getSiteOptions();

        foreach ($request as $req_name => $req_value) {
            if (array_key_exists($req_name, $site_options)
                && $site_options[ $req_name ] != $req_value
            ) {
                $options_changed['original'][ $req_name ] = $site_options[ $req_name ];
                $options_changed['changed'][ $req_name ] = $req_value;
            }
        }

        return $options_changed;
    }

    /**
     * Check the nonce comming from any of the settings pages.
     *
     * @return boolean TRUE if the nonce is valid, FALSE otherwise.
     */
    public static function checkOptionsNonce()
    {
        // Create the option_page value if permalink submission.
        if (!isset($_POST['option_page'])
            && isset($_POST['permalink_structure'])
        ) {
            $_POST['option_page'] = 'permalink';
        }

        // Check if the option_page has an allowed value.
        if ($option_page = SucuriScanRequest::post('option_page')) {
            $nonce = '_wpnonce';
            $action = '';

            switch ($option_page) {
                case 'general':    /* no_break */
                case 'writing':    /* no_break */
                case 'reading':    /* no_break */
                case 'discussion': /* no_break */
                case 'media':      /* no_break */
                case 'options':    /* no_break */
                    $action = $option_page . '-options';
                    break;
                case 'permalink':
                    $action = 'update-permalink';
                    break;
            }

            // Check the nonce validity.
            if (!empty($action)
                && isset($_REQUEST[ $nonce ])
                && wp_verify_nonce($_REQUEST[ $nonce ], $action)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a list of the post types ignored to receive email notifications when the
     * "new site content" hook is triggered.
     *
     * @return array List of ignored posts-types to send notifications.
     */
    public static function getIgnoredEvents()
    {
        $post_types = self::getOption(':ignored_events');
        $post_types_arr = false;

        if (is_string($post_types)) {
            $post_types_arr = @json_decode($post_types, true);
        }

        if (!is_array($post_types_arr)) {
            $post_types_arr = array();
        }

        return $post_types_arr;
    }

    /**
     * Add a new post type to the list of ignored events to send notifications.
     *
     * @param  string  $event_name Unique post-type name.
     * @return boolean             Whether the event was ignored or not.
     */
    public static function addIgnoredEvent($event_name = '')
    {
        if (function_exists('get_post_types')) {
            $post_types = get_post_types();

            // Check if the event is a registered post-type.
            if (array_key_exists($event_name, $post_types)) {
                $ignored_events = self::getIgnoredEvents();

                // Check if the event is not ignored already.
                if (!array_key_exists($event_name, $ignored_events)) {
                    $ignored_events[ $event_name ] = time();
                    $saved = self::updateOption(':ignored_events', json_encode($ignored_events));

                    return $saved;
                }
            }
        }

        return false;
    }

    /**
     * Remove a post type from the list of ignored events to send notifications.
     *
     * @param  string  $event Unique post-type name.
     * @return boolean        Whether the event was removed from the list or not.
     */
    public static function removeIgnoredEvent($event = '')
    {
        $ignored = self::getIgnoredEvents();

        if (array_key_exists($event, $ignored)) {
            unset($ignored[$event]);

            return self::updateOption(
                ':ignored_events',
                @json_encode($ignored)
            );
        }

        return false;
    }

    /**
     * Check whether an event is being ignored to send notifications or not.
     *
     * @param  string  $event Unique post-type name.
     * @return boolean        Whether an event is being ignored or not.
     */
    public static function isIgnoredEvent($event = '')
    {
        $event = strtolower($event);
        $ignored = self::getIgnoredEvents();

        return array_key_exists($event, $ignored);
    }

    /**
     * Retrieve a list of basic security keys and check whether their values were
     * randomized correctly.
     *
     * @return array Array with three keys: good, missing, bad.
     */
    public static function getSecurityKeys()
    {
        $response = array(
            'good' => array(),
            'missing' => array(),
            'bad' => array(),
        );
        $key_names = array(
            'AUTH_KEY',
            'AUTH_SALT',
            'LOGGED_IN_KEY',
            'LOGGED_IN_SALT',
            'NONCE_KEY',
            'NONCE_SALT',
            'SECURE_AUTH_KEY',
            'SECURE_AUTH_SALT',
        );

        foreach ($key_names as $key_name) {
            if (defined($key_name)) {
                $key_value = constant($key_name);

                if (stripos($key_value, 'unique phrase') !== false) {
                    $response['bad'][ $key_name ] = $key_value;
                } else {
                    $response['good'][ $key_name ] = $key_value;
                }
            } else {
                $response['missing'][ $key_name ] = false;
            }
        }

        return $response;
    }

    /**
     * Change the reverse proxy setting.
     *
     * When enabled this option forces the plugin to override the value of the
     * global IP address variable from the HTTP header selected by the user from the
     * settings. Note that this may also be automatically enabled when the firewall
     * page is activated as it assumes that the proxy is creating a custom HTTP
     * header for the real IP.
     *
     * @param  string $header Valid HTTP header name.
     * @return void
     */
    public static function setRevProxy($action = 'disable')
    {
        if ($action !== 'enable' && $action !== 'disable') {
            return self::deleteOption(':revproxy');
        }

        $action_d = $action . 'd';
        $message = 'Reverse proxy support was <code>' . $action_d . '</code>';

        self::updateOption(':revproxy', $action_d);
        SucuriScanEvent::reportInfoEvent($message);
        SucuriScanEvent::notifyEvent('plugin_change', $message);
        SucuriScanInterface::info($message);
    }

    /**
     * Change the HTTP header to retrieve the real IP address.
     *
     * @param  string $header Valid HTTP header name.
     * @return void
     */
    public static function setAddrHeader($header = 'REMOTE_ADDR')
    {
        $header = strtoupper($header);
        $allowed = SucuriScan::allowedHttpHeaders(true);

        if (array_key_exists($header, $allowed)) {
            $message = 'HTTP header was set to <code>' . $header . '</code>';

            self::updateOption(':addr_header', $header);
            SucuriScanEvent::reportInfoEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info($message);
        } else {
            SucuriScanInterface::error('HTTP header is not in the allowed list');
        }
    }
}
