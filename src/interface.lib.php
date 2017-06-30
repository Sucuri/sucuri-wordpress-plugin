<?php

/**
 * Code related to the interface.lib.php interface.
 *
 * @package Sucuri Security
 * @subpackage interface.lib.php
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
 * Plugin initializer.
 *
 * Define all the required variables, script, styles, and basic functions needed
 * when the site is loaded, not even the administrator panel but also the front
 * page, some bug-fixes will/are applied here for sites behind a proxy, and
 * sites with old versions of the premium plugin (that was deprecated at
 * July/2014).
 */
class SucuriScanInterface
{
    /**
     * Initialization code for the plugin.
     *
     * The initial variables and information needed by the plugin during the
     * execution of other functions will be generated. Things like the real IP
     * address of the client when it has been forwarded or it's behind an external
     * service like a Proxy.
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
     */
    public static function enqueueScripts()
    {
        $asset = substr(md5(microtime(true)), 0, 7);

        wp_register_style(
            'sucuriscan1',
            SUCURISCAN_URL . '/inc/css/styles.css',
            array(/* empty */),
            $asset
        );
        wp_enqueue_style('sucuriscan1');

        wp_register_script(
            'sucuriscan1',
            SUCURISCAN_URL . '/inc/js/scripts.js',
            array(/* empty */),
            $asset
        );
        wp_enqueue_script('sucuriscan1');

        if (SucuriScanRequest::get('page', 'sucuriscan') !== false) {
            wp_register_style(
                'sucuriscan2',
                SUCURISCAN_URL . '/inc/css/c3.min.css',
                array(/* empty */),
                $asset
            );
            wp_enqueue_style('sucuriscan2');

            wp_register_script(
                'sucuriscan2',
                SUCURISCAN_URL . '/inc/js/d3.min.js',
                array(/* empty */),
                $asset
            );
            wp_enqueue_script('sucuriscan2');

            wp_register_script(
                'sucuriscan3',
                SUCURISCAN_URL . '/inc/js/c3.min.js',
                array(/* empty */),
                $asset
            );
            wp_enqueue_script('sucuriscan3');
        }

        if (SucuriScanRequest::get('page', 'sucuriscan_firewall') !== false) {
            wp_register_style(
                'sucuriscan3',
                SUCURISCAN_URL . '/inc/css/flags.min.css',
                array(/* empty */),
                $asset
            );
            wp_enqueue_style('sucuriscan3');
        }
    }

    /**
     * Remove the old Sucuri plugins considering that with the new version (after
     * 1.6.0) all the functionality of the others will be merged here, this will
     * remove duplicated functionality, duplicated bugs and/or duplicated
     * maintenance reports allowing us to focus in one unique project.
     */
    public static function handleOldPlugins()
    {
        if (class_exists('SucuriScanFileInfo')) {
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
    }

    /**
     * Create a folder in the WordPress upload directory where the plugin will
     * store all the temporal or dynamic information.
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
     * Do something if the plugin was updated.
     *
     * Check if an option exists with the version number of the plugin, if the
     * number is different than the number defined in the constant that comes
     * with this code then we can consider this as an update, in which case we
     * will execute certain actions and/or display some messages.
     *
     * @codeCoverageIgnore
     */
    public static function noticeAfterUpdate()
    {
        /* get version of the plugin that was previously installed */
        $version = SucuriScanOption::getOption(':plugin_version');

        /* use simple comparison to force type cast. */
        if (headers_sent() || $version == SUCURISCAN_VERSION) {
            return;
        }

        if (!is_writable(SucuriScanOption::optionsFilePath())) {
            /**
             * Stop if the settings file is not writable.
             *
             * In some cases where the settings file is not writable, or for
             * some reason the option cannot be updated, the alerts below will
             * be rendered all the time, to avoid unnecessary complains from
             * the website owners we will not display the alerts if the option
             * cannot be updated.
             */
            return;
        }

        /* update the version number in the plugin settings. */
        SucuriScanOption::updateOption(':plugin_version', SUCURISCAN_VERSION);

        /**
         * Suggest re-activation of the API communication.
         *
         * Check if the API communication has been disabled due to issues with
         * the previous version of the code, in this case we will display a
         * message at the top of the admin dashboard suggesting the user to
         * enable it once again expecting to see have a better performance with
         * the new code.
         */
        if (SucuriScanOption::isDisabled(':api_service')) {
            self::info(__('EnableAPIServiceAgain', SUCURISCAN_TEXTDOMAIN));
        }

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
        self::info(__('NewsletterInvitation', SUCURISCAN_TEXTDOMAIN));
    }

    /**
     * Check whether a user has the permissions to see a page from the plugin.
     *
     * @codeCoverageIgnore
     */
    public static function checkPageVisibility()
    {
        if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
            SucuriScan::throwException('Access denied; cannot manage options');
            wp_die(__('AccessDenied', SUCURISCAN_TEXTDOMAIN));
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
                SucuriScan::throwException('Nonce is invalid');
                wp_die(__('NonceFailure', SUCURISCAN_TEXTDOMAIN));
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
     * @param string $type The type of alert, it can be either Updated or Error.
     * @param string $message The message that will be printed in the alert.
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

            echo SucuriScanTemplate::getSection('notification-admin', array(
                'AlertType' => $type,
                'AlertUnique' => rand(100, 999),
                'AlertMessage' => $message,
            ));
        }
    }

    /**
     * Prints a HTML alert of type ERROR in the WordPress admin interface.
     *
     * @param string $msg The message that will be printed in the alert.
     */
    public static function error($msg = '')
    {
        self::adminNotice('error', $msg);
        return false; /* assume failure */
    }

    /**
     * Prints a HTML alert of type INFO in the WordPress admin interface.
     *
     * @param string $msg The message that will be printed in the alert.
     */
    public static function info($msg = '')
    {
        self::adminNotice('updated', $msg);
        return true; /* assume success */
    }
}
