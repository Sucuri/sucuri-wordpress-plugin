<?php

/**
 * Code related to the interface.lib.php interface.
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
 * Plugin initializer.
 *
 * Define all the required variables, script, styles, and basic functions needed
 * when the site is loaded, not even the administrator panel but also the front
 * page, some bug-fixes will/are applied here for sites behind a proxy, and
 * sites with old versions of the premium plugin (deprecated on July, 2014).
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanInterface
{
    /**
     * Initialization code for the plugin.
     *
     * @return void
     */
    public static function initialize()
    {
        SucuriScanEvent::installScheduledTask();

        if (SucuriScan::supportReverseProxy() || SucuriScan::isBehindFirewall()) {
            $_SERVER['SUCURIREAL_REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
            $_SERVER['REMOTE_ADDR'] = SucuriScan::getRemoteAddr();
        }
    }

    /**
     * Define which javascript and css files will be loaded in the header of the
     * plugin pages, only when the administrator panel is accessed.
     *
     * @return void
     */
    public static function enqueueScripts()
    {
        wp_register_style(
            'sucuriscan',
            SUCURISCAN_URL . '/inc/css/styles.css',
            array(/* empty */),
            SucuriScan::fileVersion('inc/css/styles.css')
        );
        wp_enqueue_style('sucuriscan');

        wp_register_script(
            'sucuriscan',
            SUCURISCAN_URL . '/inc/js/scripts.js',
            array(/* empty */),
            SucuriScan::fileVersion('inc/js/scripts.js')
        );
        wp_enqueue_script('sucuriscan');

        if (SucuriScanRequest::get('page', 'sucuriscan_firewall') !== false) {
            wp_register_style(
                'sucuriscan2',
                SUCURISCAN_URL . '/inc/css/flags.min.css',
                array(/* empty */),
                SucuriScan::fileVersion('inc/css/flags.min.css')
            );
            wp_enqueue_style('sucuriscan2');
        }
    }

    /**
     * Remove the old Sucuri plugins.
     *
     * Considering that in the new version (after 1.6.0) all the functionality
     * of the others will be merged here, this will remove duplicated code,
     * duplicated bugs and/or duplicated maintenance reports allowing us to
     * focus in one unique project.
     *
     * @return void
     */
    public static function handleOldPlugins()
    {
        // @codeCoverageIgnoreStart
        if (!class_exists('SucuriScanFileInfo')) {
            return;
        }
        // @codeCoverageIgnoreEnd

        $finfo = new SucuriScanFileInfo();
        $finfo->ignore_files = false;
        $finfo->ignore_directories = false;
        $finfo->skip_directories = false;
        $finfo->run_recursively = true;

        $plugins = array(
            'c3VjdXJpLXdwLXBsdWdpbi9zdWN1cmkucGhw',
            'c3VjdXJpLWNsb3VkcHJveHktd2FmL2Nsb3VkcHJveHkucGhw',
            'ZGVzc2t5LXNlY3VyaXR5L2Rlc3NreS1zZWN1cml0eS5waHA=',
        );

        foreach ($plugins as $plugin) {
            $plugin = base64_decode($plugin);
            $plugin_directory = dirname(WP_PLUGIN_DIR . '/' . $plugin);

            if (file_exists($plugin_directory)) {
                if (is_plugin_active($plugin)) {
                    // @codeCoverageIgnoreStart
                    deactivate_plugins($plugin);
                    // @codeCoverageIgnoreEnd
                }

                $finfo->removeDirectoryTree($plugin_directory);
            }
        }
    }

    /**
     * Create a folder in the WordPress upload directory where the plugin will
     * store all the temporal or dynamic information.
     *
     * @return void
     */
    public static function createStorageFolder()
    {
        $directory = SucuriScan::dataStorePath();

        if (!file_exists($directory)) {
            @mkdir($directory, 0755, true);
        }

        if (file_exists($directory)) {
            // Create last-logins datastore file.
            sucuriscan_lastlogins_datastore_exists();

            // Create a htaccess file to deny access from all.
            if (!SucuriScanHardening::isHardened($directory)) {
                SucuriScanHardening::hardenDirectory($directory);
            }

            // Create an index.html to avoid directory listing.
            if (!file_exists($directory . '/index.html')) {
                @file_put_contents(
                    $directory . '/index.html',
                    '<!-- Prevent the directory listing. -->'
                );
            }
        }
    }

    /**
     * Display alerts and execute pre-checks before every page.
     *
     * This method verifies if the visibility of the requested page is allowed
     * for the current user in session which usually needs to be granted admin
     * privileges to access the plugin's tools. It also checks if the required
     * SPL library is available and if the settings file is writable.
     *
     * @return void
     */
    public static function startupChecks()
    {
        self::checkPageVisibility();

        self::noticeAfterUpdate();

        if (!SucuriScanFileInfo::isSplAvailable()) {
            /* display a warning when system dependencies are not met */
            self::error(__('The plugin requires PHP 5 >= 5.3.0 - OR - PHP 7', 'sucuri-scanner'));
        }

        $filename = SucuriScanOption::optionsFilePath();

        if (!is_writable($filename)) {
            self::error(
                sprintf(
                    __('Storage is not writable: <code>%s</code>', 'sucuri-scanner'),
                    $filename /* absolute path of the settings file */
                )
            );
        }
    }

    /**
     * Do something if the plugin was updated.
     *
     * Check if an option exists with the version number of the plugin, if the
     * number is different than the number defined in the constant that comes
     * with this code then we can consider this as an update, in which case we
     * will execute certain actions and/or display some messages.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public static function noticeAfterUpdate()
    {
        /* get version of the plugin that was previously installed */
        $version = SucuriScanOption::getOption(':plugin_version');

        /* use simple comparison to force type cast. */
        if ($version == SUCURISCAN_VERSION) {
            return;
        }

        /* update the version number in the plugin settings. */
        SucuriScanOption::updateOption(':plugin_version', SUCURISCAN_VERSION);

        /**
         * Invite website owner to subscribe to our security newsletter.
         *
         * For every fresh installation of the plugin we will send a one-time
         * email to the website owner with an invitation to subscribe to our
         * security related newsletter where they can learn about better security
         * practices and get alerts from public vulnerabilities disclosures.
         *
         * @date Featured added at - May 01, 2017
         */
        self::info(__('Do you want to get vulnerability disclosures? Subscribe to our newsletter <a href="http://sucuri.hs-sites.com/subscribe-to-security" target="_blank" rel="noopener">here</a>', 'sucuri-scanner'));
    }

    /**
     * Check whether a user has the permissions to see a page from the plugin.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public static function checkPageVisibility()
    {
        if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
            SucuriScan::throwException(__('Access denied; cannot manage options', 'sucuri-scanner'));
            wp_die(sprintf(__('Access denied by %s', 'sucuri-scanner'), SUCURISCAN_PLUGIN_NAME));
        }
    }

    /**
     * Verify the nonce of the previous page after a form submission. If the
     * validation fails the execution of the script will be stopped and a dead page
     * will be printed to the client using the official WordPress method.
     *
     * @codeCoverageIgnore
     *
     * @return bool True if the nonce is valid, false otherwise.
     */
    public static function checkNonce()
    {
        if (!empty($_POST)) {
            $nonce_name = 'sucuriscan_page_nonce';
            $nonce_value = SucuriScanRequest::post($nonce_name, '_nonce');

            if (!$nonce_value || !wp_verify_nonce($nonce_value, $nonce_name)) {
                SucuriScan::throwException(__('Nonce is invalid', 'sucuri-scanner'));
                self::error(__('WordPress CSRF verification failed. The submitted form is missing an important unique code that prevents the execution of automated malicious scanners. Go back and try again. If you did not submit a form, this error message could be an indication of an incompatibility between this plugin and another add-on; one of them is inserting data into the global POST variable when the HTTP request is coming via GET. Disable them one by one (while reloading this page) to find the culprit.', 'sucuri-scanner'));
                return false;
            }
        }

        return true;
    }

    /**
     * Prints a HTML alert in the WordPress admin interface.
     *
     * @codeCoverageIgnore
     *
     * @param  string $type    The type of alert, it can be either Updated or Error.
     * @param  string $message The message that will be printed in the alert.
     * @return void
     */
    private static function adminNotice($type = 'updated', $message = '')
    {
        $display_notice = true;

        /**
         * Do not render notice during user authentication.
         *
         * There are some special cases when the error or warning messages
         * should not be rendered to the end user because it may break the
         * default functionality of the request handler. For instance, rendering
         * an HTML alert like this when the user authentication process is
         * executed may cause a "headers already sent" error.
         */
        if (!empty($_POST)
            && SucuriScanRequest::post('log')
            && SucuriScanRequest::post('pwd')
            && SucuriScanRequest::post('wp-submit')
        ) {
            $display_notice = false;
        }

        /* display the HTML notice to the current user */
        if ($display_notice === true && !empty($message)) {
            $message = SUCURISCAN_ADMIN_NOTICE_PREFIX . "\x20" . $message;

            SucuriScan::throwException($message, $type);

            echo SucuriScanTemplate::getSection(
                'notification-admin',
                array(
                    'AlertType' => $type,
                    'AlertUnique' => rand(100, 999),
                    'AlertMessage' => $message,
                )
            );
        }
    }

    /**
     * Prints a HTML alert of type ERROR in the WordPress admin interface.
     *
     * @param  string $msg The message that will be printed in the alert.
     * @return void
     */
    public static function error($msg = '')
    {
        self::adminNotice('error', $msg);
        return false; /* assume failure */
    }

    /**
     * Prints a HTML alert of type INFO in the WordPress admin interface.
     *
     * @param  string $msg The message that will be printed in the alert.
     * @return void
     */
    public static function info($msg = '')
    {
        self::adminNotice('updated', $msg);
        return true; /* assume success */
    }
}
