<?php

/**
 * Code related to the settings-hardening.php interface.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Renders the content of the plugin's hardening page.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanHardeningPage extends SucuriScan
{
    /**
     * Generate the HTML code necessary to show a form with the options to harden
     * a specific part of the WordPress installation, if the Status variable is
     * set as a positive integer the button is shown as "unharden".
     *
     * @param  array $args Array with template variables to replace.
     * @return string      HTML code with the replaced template variables.
     */
    private static function drawSection($args = array())
    {
        $params = array();

        $params['Hardening.Title'] = '';
        $params['Hardening.Status'] = '';
        $params['Hardening.FieldName'] = '';
        $params['Hardening.FieldText'] = '';
        $params['Hardening.FieldAttrs'] = '';
        $params['Hardening.Description'] = '';

        foreach ($args as $keyname => $value) {
            $params[$keyname] = $value;
        }

        if (!empty($params['Hardening.FieldName'])) {
            $params['Hardening.FieldName'] = sprintf(
                '%s_hardening_%s',
                SUCURISCAN,
                $params['Hardening.FieldName']
            );
        }

        return SucuriScanTemplate::getSnippet('settings-hardening-options', $params);
    }

    /**
     * Checks if the request has a valid nonce to prevent a CSRF.
     *
     * @param  string $function Name of the action that was executed.
     * @return bool             True if the request has a valid CSRF protection.
     */
    private static function processRequest($function)
    {
        return (bool)(SucuriScanInterface::checkNonce() /* CSRF protection */
            && SucuriScanRequest::post(':hardening_' . $function));
    }

    /**
     * Checks if the Firewall service is enabled and configured.
     *
     * WAF is a protection layer for your web site, blocking all sort of attacks
     * (brute force attempts, DDoS, SQL injections, etc) and helping it remain
     * malware and blacklist free. This test checks if your site is using Sucuri
     * Firewall to protect your site.
     *
     * @return string HTML code with the replaced template variables.
     */
    public static function firewall()
    {
        $params = array();

        if (self::processRequest(__FUNCTION__)) {
            SucuriScanInterface::error(
                __('The firewall is a premium service that you need purchase at - <a href="https://sucuri.net/website-firewall/signup" target="_blank">Sucuri Firewall</a>', 'sucuri-scanner')
            );
        }

        $params['Hardening.FieldName'] = __FUNCTION__;
        $params['Hardening.Title'] = __('Website Firewall Protection', 'sucuri-scanner');
        $params['Hardening.Description'] = __('A WAF is a protection layer for your web site, blocking all sort of attacks (brute force attempts, DDoS, SQL injections, etc) and helping it remain malware and blacklist free. This test checks if your site is using Sucuri Firewall to protect your site.', 'sucuri-scanner');

        if (!SucuriScan::isBehindFirewall()) {
            $params['Hardening.Status'] = 0;
            $params['Hardening.FieldText'] = __('Apply Hardening', 'sucuri-scanner');
        } else {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldAttrs'] = 'disabled';
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        }

        return self::drawSection($params);
    }

    /**
     * Checks if the WordPress version is the latest available.
     *
     * Why keep your site updated? WordPress is an open-source project which
     * means that with every update the details of the changes made to the
     * source code are made public, if there were security fixes then someone
     * with malicious intent can use this information to attack any site that
     * has not been upgraded.
     *
     * @return HTML with the information about this hardening option.
     */
    public static function wpversion()
    {
        $params = array();
        $updates = get_core_updates();
        $site_version = SucuriScan::siteVersion();

        $params['URL.Settings'] = admin_url('update-core.php');
        $params['Hardening.Status'] = 0;
        $params['Hardening.FieldText'] = __('Check Updates Now', 'sucuri-scanner');
        $params['Hardening.Title'] = __('Verify WordPress Version', 'sucuri-scanner');
        $params['Hardening.Description'] = __('Why keep your site updated? WordPress is an open-source project which means that with every update the details of the changes made to the source code are made public, if there were security fixes then someone with malicious intent can use this information to attack any site that has not been upgraded.', 'sucuri-scanner');

        if (isset($updates[0]) && $updates[0] instanceof stdClass) {
            if ($updates[0]->response == 'latest' || $updates[0]->response == 'development') {
                $params['Hardening.FieldText'] = __('WordPress Update Available', 'sucuri-scanner');
                $params['Hardening.FieldAttrs'] = 'disabled';
                $params['Hardening.Status'] = 1;
            }
        }

        return self::drawSection($params);
    }

    /**
     * Checks if the server is using a modern PHP version.
     *
     * Each release branch of PHP is fully supported for two years from its
     * initial stable release. During this period, bugs and security issues that
     * have been reported are fixed and are released in regular point releases.
     * After this two year period of active support, each branch is then
     * supported for an additional year for critical security issues only.
     * Releases during this period are made on an as-needed basis: there may be
     * multiple point releases, or none, depending on the number of reports.
     * Once the three years of support are completed, the branch reaches its end
     * of life and is no longer supported.
     *
     * @see http://php.net/supported-versions.php
     *
     * @return HTML with the information about this hardening option.
     */
    public static function phpversion()
    {
        $params = array();

        if (self::processRequest(__FUNCTION__)) {
            SucuriScanInterface::error(
                __('Ask your hosting provider to install an updated version of PHP - <a href="http://php.net/supported-versions.php" target="_blank" rel="noopener">List of PHP Supported Versions</a>', 'sucuri-scanner')
            );
        }

        $params['Hardening.FieldName'] = __FUNCTION__;
        $params['Hardening.Title'] = __('Verify PHP Version', 'sucuri-scanner');
        $params['Hardening.Description'] = sprintf(__('PHP %s is installed.', 'sucuri-scanner'), PHP_VERSION);

        if (intval(version_compare(PHP_VERSION, '7.1.0') >= 0)) {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldAttrs'] = 'disabled';
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        } else {
            $params['Hardening.Status'] = 0;
            $params['Hardening.FieldText'] = __('Apply Hardening', 'sucuri-scanner');
        }

        return self::drawSection($params);
    }

    /**
     * Notify the state of the hardening for the removal of the Generator tag in
     * HTML code printed by WordPress to show the current version number of the
     * installation.
     *
     * @return HTML with the information about this hardening option.
     */
    public static function wpgenerator()
    {
        $params = array();

        $params['Hardening.Title'] = __('Remove WordPress Version', 'sucuri-scanner');
        $params['Hardening.Status'] = 1;
        $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        $params['Hardening.FieldAttrs'] = 'disabled';
        $params['Hardening.Description'] = __('It checks if your WordPress version is being leaked to the public via a HTML meta-tag. Many web vulnerability scanners use this to determine which version of the code is running in your website. They use this to find disclosed vulnerabilities associated to this version number. A vulnerability scanner can still guess which version of WordPress is installed by comparing the checksum of some static files.', 'sucuri-scanner');

        return self::drawSection($params);
    }

    /**
     * Offers information to apply a hardening to an Nginx installation.
     *
     * @return HTML with the information about this hardening option.
     */
    public static function nginxphp()
    {
        if (!SucuriScan::isNginxServer()) {
            return ''; /* empty page */
        }

        $params = array();

        if (self::processRequest(__FUNCTION__)) {
            SucuriScanInterface::error(
                __('Read the official WordPress guidelines to learn how to restrict access to PHP files in sensitive directories - <a href="https://codex.wordpress.org/Nginx#Global_restrictions_file" target="_blank" rel="noopener">Nginx Global Restrictions For WordPress</a>', 'sucuri-scanner')
            );
        }

        $params['Hardening.Title'] = __('Block of Certain PHP Files', 'sucuri-scanner');
        $params['Hardening.Status'] = 2;
        $params['Hardening.FieldName'] = __FUNCTION__;
        $params['Hardening.FieldText'] = __('Check Hardening', 'sucuri-scanner');
        $params['Hardening.Description'] = __('Block the execution of PHP files in sensitive directories. Be careful while applying this hardening option as there are many plugins and theme which rely on the ability to execute PHP files in the content directory to generate images or save temporary data. Use the "Whitelist PHP Files" tool to add exceptions to individual files.', 'sucuri-scanner');

        return self::drawSection($params);
    }

    /**
     * Check whether the WordPress upload folder is protected or not.
     *
     * A htaccess file is placed in the upload folder denying the access to any php
     * file that could be uploaded through a vulnerability in a Plugin, Theme or
     * WordPress itself.
     *
     * @return HTML with the information about this hardening option.
     */
    public static function wpuploads()
    {
        if (SucuriScan::isNginxServer() || SucuriScan::isIISServer()) {
            return ''; /* empty page */
        }

        $params = array();
        $folder = WP_CONTENT_DIR . '/uploads';

        if (self::processRequest(__FUNCTION__)) {
            $result = SucuriScanHardening::hardenDirectory($folder);

            if ($result === true) {
                SucuriScanEvent::reportNoticeEvent(__('Hardening applied to the uploads directory', 'sucuri-scanner'));
                SucuriScanInterface::info(__('Hardening applied to the uploads directory', 'sucuri-scanner'));
            } else {
                SucuriScanInterface::error(__('Error hardening directory, check the permissions.', 'sucuri-scanner'));
            }
        }

        if (self::processRequest(__FUNCTION__ . '_revert')) {
            $result = SucuriScanHardening::unhardenDirectory($folder);

            if ($result === true) {
                SucuriScanEvent::reportErrorEvent(__('Hardening reverted in the uploads directory', 'sucuri-scanner'));
                SucuriScanInterface::info(__('Hardening reverted in the uploads directory', 'sucuri-scanner'));
            } else {
                SucuriScanInterface::error(__('Access file is not writable, check the permissions.', 'sucuri-scanner'));
            }
        }

        $params['Hardening.Title'] = __('Block PHP Files in Uploads Directory', 'sucuri-scanner');
        $params['Hardening.Description'] = __('Block the execution of PHP files in sensitive directories. Be careful while applying this hardening option as there are many plugins and theme which rely on the ability to execute PHP files in the content directory to generate images or save temporary data. Use the "Whitelist PHP Files" tool to add exceptions to individual files.', 'sucuri-scanner');

        if (SucuriScan::isBehindFirewall()) {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldAttrs'] = 'disabled';
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        } elseif (SucuriScanHardening::isHardened($folder)) {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldName'] = __FUNCTION__ . '_revert';
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        } else {
            $params['Hardening.Status'] = 0;
            $params['Hardening.FieldName'] = __FUNCTION__;
            $params['Hardening.FieldText'] = __('Apply Hardening', 'sucuri-scanner');
        }

        return self::drawSection($params);
    }

    /**
     * Check whether the WordPress content folder is protected or not.
     *
     * A htaccess file is placed in the content folder denying the access to any php
     * file that could be uploaded through a vulnerability in a Plugin, Theme or
     * WordPress itself.
     *
     * @return HTML with the information about this hardening option.
     */
    public static function wpcontent()
    {
        if (SucuriScan::isNginxServer() || SucuriScan::isIISServer()) {
            return ''; /* empty page */
        }

        $params = array();

        if (self::processRequest(__FUNCTION__)) {
            $result = SucuriScanHardening::hardenDirectory(WP_CONTENT_DIR);

            if ($result === true) {
                SucuriScanEvent::reportNoticeEvent(__('Hardening applied to the content directory', 'sucuri-scanner'));
                SucuriScanInterface::info(__('Hardening applied to the content directory', 'sucuri-scanner'));
            } else {
                SucuriScanInterface::error(__('Error hardening directory, check the permissions.', 'sucuri-scanner'));
            }
        }

        if (self::processRequest(__FUNCTION__ . '_revert')) {
            $result = SucuriScanHardening::unhardenDirectory(WP_CONTENT_DIR);

            if ($result === true) {
                SucuriScanEvent::reportErrorEvent(__('Hardening reverted in the content directory', 'sucuri-scanner'));
                SucuriScanInterface::info(__('Hardening reverted in the content directory', 'sucuri-scanner'));
            } else {
                SucuriScanInterface::error(__('Access file is not writable, check the permissions.', 'sucuri-scanner'));
            }
        }

        $params['Hardening.Title'] = __('Block PHP Files in WP-CONTENT Directory', 'sucuri-scanner');
        $params['Hardening.Description'] = __('Block the execution of PHP files in sensitive directories. Be careful while applying this hardening option as there are many plugins and theme which rely on the ability to execute PHP files in the content directory to generate images or save temporary data. Use the "Whitelist PHP Files" tool to add exceptions to individual files.', 'sucuri-scanner');

        if (SucuriScan::isBehindFirewall()) {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldAttrs'] = 'disabled';
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        } elseif (SucuriScanHardening::isHardened(WP_CONTENT_DIR)) {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldName'] = __FUNCTION__ . '_revert';
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        } else {
            $params['Hardening.Status'] = 0;
            $params['Hardening.FieldName'] = __FUNCTION__;
            $params['Hardening.FieldText'] = __('Apply Hardening', 'sucuri-scanner');
        }

        return self::drawSection($params);
    }

    /**
     * Check whether the WordPress includes folder is protected or not.
     *
     * A htaccess file is placed in the includes folder denying the access to any php
     * file that could be uploaded through a vulnerability in a Plugin, Theme or
     * WordPress itself, there are some exceptions for some specific files that must
     * be available publicly.
     *
     * @return HTML with the information about this hardening option.
     */
    public static function wpincludes()
    {
        if (SucuriScan::isNginxServer() || SucuriScan::isIISServer()) {
            return ''; /* empty page */
        }

        $params = array();
        $folder = ABSPATH . '/wp-includes';

        if (self::processRequest(__FUNCTION__)) {
            $result = SucuriScanHardening::hardenDirectory($folder);

            if ($result === true) {
                try {
                    SucuriScanHardening::whitelist('wp-tinymce.php', 'wp-includes');
                    SucuriScanHardening::whitelist('ms-files.php', 'wp-includes');
                    SucuriScanEvent::reportNoticeEvent(__('Hardening applied to the library directory', 'sucuri-scanner'));
                    SucuriScanInterface::info(__('Hardening applied to the library directory', 'sucuri-scanner'));
                } catch (Exception $e) {
                    SucuriScanInterface::error($e->getMessage());
                }
            } else {
                SucuriScanInterface::error(__('Error hardening directory, check the permissions.', 'sucuri-scanner'));
            }
        }

        if (self::processRequest(__FUNCTION__ . '_revert')) {
            $result = SucuriScanHardening::unhardenDirectory($folder);

            if ($result === true) {
                SucuriScanHardening::dewhitelist('wp-tinymce.php', 'wp-includes');
                SucuriScanHardening::dewhitelist('ms-files.php', 'wp-includes');
                SucuriScanEvent::reportErrorEvent(__('Hardening reverted in the library directory', 'sucuri-scanner'));
                SucuriScanInterface::info(__('Hardening reverted in the library directory', 'sucuri-scanner'));
            } else {
                SucuriScanInterface::error(__('Access file is not writable, check the permissions.', 'sucuri-scanner'));
            }
        }

        $params['Hardening.Title'] = __('Block PHP Files in WP-INCLUDES Directory', 'sucuri-scanner');
        $params['Hardening.Description'] = __('Block the execution of PHP files in sensitive directories. Be careful while applying this hardening option as there are many plugins and theme which rely on the ability to execute PHP files in the content directory to generate images or save temporary data. Use the "Whitelist PHP Files" tool to add exceptions to individual files.', 'sucuri-scanner');

        if (SucuriScan::isBehindFirewall()) {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldAttrs'] = 'disabled';
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        } elseif (SucuriScanHardening::isHardened($folder)) {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldName'] = __FUNCTION__ . '_revert';
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        } else {
            $params['Hardening.Status'] = 0;
            $params['Hardening.FieldName'] = __FUNCTION__;
            $params['Hardening.FieldText'] = __('Apply Hardening', 'sucuri-scanner');
        }

        return self::drawSection($params);
    }

    /**
     * Check whether the "readme.html" file is still available in the root of the
     * site or not, which can lead to an attacker to know which version number of
     * Wordpress is being used and search for possible vulnerabilities.
     *
     * @return HTML with the information about this hardening option.
     */
    public static function readme()
    {
        $params = array();

        if (self::processRequest(__FUNCTION__)) {
            if (@unlink(ABSPATH . '/readme.html') === false) {
                SucuriScanInterface::error(sprintf(__('Cannot delete <code>%s/readme.html</code>', 'sucuri-scanner'), ABSPATH));
            } else {
                SucuriScanEvent::reportNoticeEvent(__('Hardening applied to the <code>readme.html</code> file', 'sucuri-scanner'));
                SucuriScanInterface::info(__('Hardening applied to the <code>readme.html</code> file', 'sucuri-scanner'));
            }
        }

        $params['Hardening.Title'] = __('Information Leakage', 'sucuri-scanner');
        $params['Hardening.Description'] = __('Checks if the WordPress README file still exists in the website. The information in this file can be used by malicious users to pin-point which disclosed vulnerabilities are associated to the website. Be aware that WordPress recreates this file automatically with every update.', 'sucuri-scanner');

        if (file_exists(ABSPATH . '/readme.html')) {
            $params['Hardening.Status'] = 0;
            $params['Hardening.FieldName'] = __FUNCTION__;
            $params['Hardening.FieldText'] = __('Apply Hardening', 'sucuri-scanner');
        } else {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
            $params['Hardening.FieldAttrs'] = 'disabled';
        }

        return self::drawSection($params);
    }

    /**
     * Check whether the main admin user still has the default name "admin" or
     * not, which can lead to an attacker to perform a brute force attack.
     *
     * @return HTML with the information about this hardening option.
     */
    public static function adminuser()
    {
        $params = array();

        $user_query = new WP_User_Query(
            array(
                'search' => 'admin',
                'fields' => array('ID', 'user_login'),
                'search_columns' => array('user_login'),
            )
        );
        $results = $user_query->get_results();

        $params['URL.Settings'] = admin_url('users.php?role=administrator');
        $params['Hardening.Title'] = __('Default Admin Account', 'sucuri-scanner');
        $params['Hardening.Description'] = __('Check if the primary user account still uses the name "admin". This allows malicious users to easily identify which account has the highest privileges to target an attack.', 'sucuri-scanner');

        if (count($results) === 0) {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldAttrs'] = 'disabled';
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        } else {
            $params['Hardening.Status'] = 0;
            $params['Hardening.FieldName'] = __FUNCTION__;
            $params['Hardening.FieldText'] = __('Apply Hardening', 'sucuri-scanner');
        }

        return self::drawSection($params);
    }

    /**
     * Enable or disable the user of the built-in Wordpress file editor.
     *
     * @return HTML with the information about this hardening option.
     */
    public static function fileeditor()
    {
        $params = array();
        $fileEditorWasDisabled = (bool)(defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT);

        if (self::processRequest(__FUNCTION__)) {
            $config = SucuriScan::getConfigPath();

            if (!$config) {
                SucuriScanInterface::error(__('WordPress configuration file was not found.', 'sucuri-scanner'));
            } elseif (!is_writable($config)) {
                SucuriScanInterface::error(__('WordPress configuration file is not writable.', 'sucuri-scanner'));
            } else {
                $content = SucuriScanFileInfo::fileContent($config);
                $lines = explode("\n", $content);
                $newlines = array();

                foreach ($lines as $line) {
                    if (strpos($line, 'DB_COLLATE') === false) {
                        $newlines[] = $line;
                        continue;
                    }

                    $newlines[] = $line; /* add current line */
                    $newlines[] = ''; /* add line separator */
                    $newlines[] = "define('DISALLOW_FILE_EDIT', true);";
                }

                $fileEditorWasDisabled = true;
                $content = implode("\n", $newlines);
                @file_put_contents($config, $content, LOCK_EX);
                SucuriScanEvent::reportNoticeEvent(__('Hardening applied to the plugin and theme editor', 'sucuri-scanner'));
                SucuriScanInterface::info(__('Hardening applied to the plugin and theme editor', 'sucuri-scanner'));
            }
        }

        if (self::processRequest(__FUNCTION__ . '_revert')) {
            $config = SucuriScan::getConfigPath();

            if (!$config) {
                SucuriScanInterface::error(__('WordPress configuration file was not found.', 'sucuri-scanner'));
            } elseif (!is_writable($config)) {
                SucuriScanInterface::error(__('WordPress configuration file is not writable.', 'sucuri-scanner'));
            } else {
                $content = SucuriScanFileInfo::fileContent($config);
                $lines = explode("\n", $content);
                $hardeningWasReverted = false;
                $newlines = array();

                foreach ($lines as $line) {
                    if (strpos($line, 'DISALLOW_FILE_EDIT') !== false) {
                        $hardeningWasReverted = true;
                        continue; /* remove the constant from the file */
                    }

                    $newlines[] = $line; /* add current line */
                }

                if (!$hardeningWasReverted) {
                    SucuriScanInterface::error(
                        __('File Editor was not disabled using this tool. You must scan your project for a constant defined as DISALLOW_FILE_EDIT, then either delete it or set its value to False. Any plugin/theme can disable the file editor, so it is impossible to determine the origin of the constant.', 'sucuri-scanner')
                    );
                } else {
                    $fileEditorWasDisabled = false;
                    $content = implode("\n", $newlines);
                    @file_put_contents($config, $content, LOCK_EX);
                    SucuriScanEvent::reportErrorEvent(__('Hardening reverted in the plugin and theme editor', 'sucuri-scanner'));
                    SucuriScanInterface::info(__('Hardening reverted in the plugin and theme editor', 'sucuri-scanner'));
                }
            }
        }

        $params['Hardening.Title'] = __('Plugin and Theme Editor', 'sucuri-scanner');
        $params['Hardening.Description'] = __('Disables the theme and plugin editors to prevent unwanted modifications to the code. If you are having problems reverting this please open the wp-config.php file and delete the line with the constant DISALLOW_FILE_EDIT.', 'sucuri-scanner');

        if ($fileEditorWasDisabled) {
            $params['Hardening.Status'] = 1;
            $params['Hardening.FieldName'] = __FUNCTION__ . '_revert';
            $params['Hardening.FieldText'] = __('Revert Hardening', 'sucuri-scanner');
        } else {
            $params['Hardening.Status'] = 0;
            $params['Hardening.FieldName'] = __FUNCTION__;
            $params['Hardening.FieldText'] = __('Apply Hardening', 'sucuri-scanner');
        }

        return self::drawSection($params);
    }

    /**
     * Whitelist individual PHP files.
     *
     * Allows an admin to whitelist individual PHP files after the directory has
     * been hardened. Since the hardening rules denies access to all PHP files
     * contained in such directory, 3rd-party plugins and themes that makes use
     * of these direct requests will stop working. The admins will want to allow
     * direct access to certain PHP files.
     *
     * @return HTML with the information about this hardening option.
     */
    public static function whitelistPHPFiles()
    {
        $params = array(
            'HardeningWhitelist.List' => '',
            'HardeningWhitelist.AllowedFolders' => '',
            'HardeningWhitelist.NoItemsVisibility' => 'visible',
        );

        $upload_dir = wp_upload_dir();
        $allowed_folders = array(
            rtrim(ABSPATH, '/') . '/' . WPINC,
            WP_CONTENT_DIR,
            $upload_dir['basedir']
        );

        if (SucuriScanInterface::checkNonce()) {
            // Add a new file to the hardening whitelist.
            $fwhite = SucuriScanRequest::post(':hardening_whitelist');

            if ($fwhite) {
                $folder = SucuriScanRequest::post(':hardening_folder');

                if (in_array($folder, $allowed_folders)) {
                    try {
                        SucuriScanHardening::whitelist($fwhite, $folder);
                        SucuriScanInterface::info(__('The file has been whitelisted from the hardening', 'sucuri-scanner'));
                    } catch (Exception $e) {
                        SucuriScanInterface::error($e->getMessage());
                    }
                } else {
                    SucuriScanInterface::error(__('Specified folder is not hardened by this plugin', 'sucuri-scanner'));
                }
            }

            // Remove a file from the hardening whitelist.
            $rmfwhite = SucuriScanRequest::post(':hardening_rmfwhite', '_array');

            if ($rmfwhite) {
                foreach ($rmfwhite as $fpath) {
                    $fpath = str_replace('/.*/', '|', $fpath);
                    $parts = explode('|', $fpath, 2);
                    SucuriScanHardening::dewhitelist($parts[1], $parts[0]);
                }

                SucuriScanInterface::info(__('Selected files have been removed', 'sucuri-scanner'));
            }
        }

        // Read the access control file and retrieve the whitelisted files.
        foreach ($allowed_folders as $folder) {
            $files = SucuriScanHardening::getWhitelisted($folder);

            $params['HardeningWhitelist.AllowedFolders'] .= sprintf(
                '<option value="%s">%s</option>',
                SucuriScan::escape($folder),
                SucuriScan::escape($folder)
            );

            if (is_array($files) && !empty($files)) {
                $params['HardeningWhitelist.NoItemsVisibility'] = 'hidden';

                foreach ($files as $file) {
                    $fregexp = sprintf('%s/.*/%s', $folder, $file);
                    $html = SucuriScanTemplate::getSnippet(
                        'settings-hardening-whitelist-phpfiles',
                        array(
                            'HardeningWhitelist.Regexp' => $fregexp,
                            'HardeningWhitelist.Folder' => $folder,
                            'HardeningWhitelist.File' => $file,
                        )
                    );
                    $params['HardeningWhitelist.List'] .= $html;
                }
            }
        }

        return SucuriScanTemplate::getSection('settings-hardening-whitelist-phpfiles', $params);
    }
}
