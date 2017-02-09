<?php

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
     *
     * @return void
     */
    public static function initialize()
    {
        if (SucuriScan::supportReverseProxy()
            || SucuriScan::isBehindCloudproxy()
        ) {
            $_SERVER['SUCURIREAL_REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
            $_SERVER['REMOTE_ADDR'] = SucuriScan::getRemoteAddr();
        }

        SucuriScanEvent::scheduleTask(false);
    }

    /**
     * Define which javascript and css files will be loaded in the header of the
     * plugin pages, only when the administrator panel is accessed.
     *
     * @return void
     */
    public static function enqueueScripts()
    {
        $asset_version = '';

        if (strlen(SUCURISCAN_PLUGIN_CHECKSUM) >= 7) {
            $asset_version = substr(SUCURISCAN_PLUGIN_CHECKSUM, 0, 7);
        }

        wp_register_style('sucuriscan', SUCURISCAN_URL . '/inc/css/sucuri-scanner.css', array(), $asset_version);
        wp_register_script('sucuriscan', SUCURISCAN_URL . '/inc/js/sucuri-scanner.js', array(), $asset_version);
        wp_enqueue_style('sucuriscan');
        wp_enqueue_script('sucuriscan');

        if (SucuriScanRequest::get('page', 'sucuriscan') !== false) {
            wp_register_script('sucuriscan2', SUCURISCAN_URL . '/inc/js/d3.min.js', array(), $asset_version);
            wp_register_script('sucuriscan3', SUCURISCAN_URL . '/inc/js/c3.min.js', array(), $asset_version);
            wp_enqueue_script('sucuriscan2');
            wp_enqueue_script('sucuriscan3');
        }
    }

    /**
     * Generate the menu and submenus for the plugin in the admin interface.
     *
     * @return void
     */
    public static function addInterfaceMenu()
    {
        global $sucuriscan_pages;

        if (function_exists('add_menu_page')
            && $sucuriscan_pages
            && is_array($sucuriscan_pages)
            && array_key_exists('sucuriscan', $sucuriscan_pages)
        ) {
            // Add main menu link.
            add_menu_page(
                'Sucuri Security',
                'Sucuri Security',
                'manage_options',
                'sucuriscan',
                'sucuriscan_page',
                SUCURISCAN_URL . '/inc/images/menu-icon.png'
            );

            foreach ($sucuriscan_pages as $sub_page_func => $sub_page_title) {
                if ($sub_page_func == 'sucuriscan_scanner'
                    && SucuriScanSiteCheck::hasBeenDisabled()
                ) {
                    continue;
                }

                $page_func = $sub_page_func . '_page';

                add_submenu_page(
                    'sucuriscan',
                    $sub_page_title,
                    $sub_page_title,
                    'manage_options',
                    $sub_page_func,
                    $page_func
                );
            }
        }
    }

    /**
     * Remove the old Sucuri plugins considering that with the new version (after
     * 1.6.0) all the functionality of the others will be merged here, this will
     * remove duplicated functionality, duplicated bugs and/or duplicated
     * maintenance reports allowing us to focus in one unique project.
     *
     * @return void
     */
    public static function handleOldPlugins()
    {
        if (class_exists('SucuriScanFileInfo')) {
            $file_info = new SucuriScanFileInfo();
            $file_info->ignore_files = false;
            $file_info->ignore_directories = false;

            $plugins = array(
                'sucuri-wp-plugin/sucuri.php',
                'sucuri-cloudproxy-waf/cloudproxy.php',
            );

            foreach ($plugins as $plugin) {
                $plugin_directory = dirname(WP_PLUGIN_DIR . '/' . $plugin);

                if (file_exists($plugin_directory)) {
                    if (is_plugin_active($plugin)) {
                        deactivate_plugins($plugin);
                    }

                    $file_info->removeDirectoryTree($plugin_directory);
                }
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
            @file_put_contents(
                $directory . '/index.html',
                '<!-- Prevent the directory listing. -->',
                LOCK_EX
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
     * @return void
     */
    public static function noticeAfterUpdate()
    {
        $version = SucuriScanOption::getOption(':plugin_version');

        // Use simple comparison to force type cast.
        if ($version != SUCURISCAN_VERSION) {
            /**
             * Check if the API communication has been disabled due to issues
             * with the previous version of the code, in this case we will
             * display a message at the top of the admin dashboard suggesting
             * the user to enable it once again expecting to see have a better
             * performance with the new code.
             */
            if (SucuriScanOption::isDisabled(':api_service')) {
                self::info(
                    'API service communication is disabled, if you just updated '
                    . 'the plugin this might be a good opportunity to test this '
                    . 'feature once again with the new code. Enable it again from '
                    . 'the "API Service" panel located in the settings page.'
                );
            }

            // Update the version number in the plugin settings.
            SucuriScanOption::updateOption(':plugin_version', SUCURISCAN_VERSION);
        }
    }

    /**
     * Check whether a user has the permissions to see a page from the plugin.
     *
     * @return void
     */
    public static function checkPageVisibility()
    {
        if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
            wp_die(__('Access denied by <b>Sucuri Scanner</b>.'));
        }
    }

    /**
     * Verify the nonce of the previous page after a form submission. If the
     * validation fails the execution of the script will be stopped and a dead page
     * will be printed to the client using the official WordPress method.
     *
     * @return boolean Either TRUE or FALSE if the nonce is valid or not respectively.
     */
    public static function checkNonce()
    {
        if (!empty($_POST)) {
            $nonce_name = 'sucuriscan_page_nonce';
            $nonce_value = SucuriScanRequest::post($nonce_name, '_nonce');

            if (!$nonce_value || !wp_verify_nonce($nonce_value, $nonce_name)) {
                wp_die(__('WordPress Nonce verification failed, try again going back and checking the form.'));

                return false;
            }
        }

        return true;
    }

    /**
     * Prints a HTML alert in the WordPress admin interface.
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
         * There are some special cases when the error or warning messages should not be
         * rendered to the end user because it may break the default functionality of
         * the request handler. For instance, rendering an HTML alert like this when the
         * user authentication process is executed may cause a "headers already sent"
         * error.
         */
        if (!empty($_POST)
            && SucuriScanRequest::post('log')
            && SucuriScanRequest::post('pwd')
            && SucuriScanRequest::post('wp-submit')
        ) {
            $display_notice = false;
        }

        // Display the HTML notice to the current user.
        if ($display_notice === true && !empty($message)) {
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
     * @param  string $error_msg The message that will be printed in the alert.
     * @return void
     */
    public static function error($error_msg = '')
    {
        self::adminNotice('error', '<b>Sucuri:</b> ' . $error_msg);
    }

    /**
     * Prints a HTML alert of type INFO in the WordPress admin interface.
     *
     * @param  string $info_msg The message that will be printed in the alert.
     * @return void
     */
    public static function info($info_msg = '')
    {
        self::adminNotice('updated', '<b>Sucuri:</b> ' . $info_msg);
    }

    /**
     * Decide if the API key generator needs to be visible.
     *
     * Once the user activates the plugin an information bar will appear at the
     * top of the admin interface advising him to generate an unique API key for
     * his website, this will allow him to activate additional features of the
     * plugin that are only available while the API key is present.
     *
     * If the user doesn't generates the key right after the activation in the
     * plugins page we have to keep the information bar visible in certain pages
     * to remind him. This is, the home page of the admin dashboard, the plugins
     * page, and any of the pages associated to the plugin.
     *
     * @return boolean Display the API key generator button or not.
     */
    private static function displayNoticesHere()
    {
        global $sucuriscan_pages;

        $page = SucuriScanRequest::get('page');
        $script = (string) @$_SERVER['SCRIPT_NAME'];
        $visibility = array(
            '/wp-admin/index.php',
            '/wp-admin/plugins.php',
        );

        if ($page
            && is_array($sucuriscan_pages)
            && array_key_exists($page, $sucuriscan_pages)
        ) {
            return true;
        }

        if (in_array($script, $visibility)) {
            return true;
        }

        /**
         * Retry using a reverse name.
         *
         * People might choose to install WordPress in a sublevel of the
         * document root, this changes the structure of the script name
         * variable. To address this incompatibility we will iterate over all
         * the visible pages and check the reverse version of the string with
         * the reverse version of the script name, if the beginning of the
         * string matches then we will consider the page available.
         */
        $script = strrev($script);

        foreach ($visibility as $visible) {
            $elbis = strrev($visible);

            if (strpos($script, $elbis) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Display a notice message with instructions to continue the setup of the
     * plugin, this includes the generation of the API key and other steps that need
     * to be done to fully activate this plugin.
     *
     * @return void
     */
    public static function setupAlert()
    {
        if (current_user_can('manage_options')
            && self::displayNoticesHere()
            && !SucuriScanAPI::getPluginKey()
            && SucuriScanRequest::post(':plugin_api_key') === false
            && SucuriScanRequest::post(':recover_key') === false
            && !SucuriScanRequest::post(':manual_api_key')
        ) {
            if (SucuriScanRequest::get(':dismiss_setup') !== false) {
                SucuriScanOption::updateOption(':dismiss_setup', 'enabled');
            } elseif (SucuriScanOption::isEnabled(':dismiss_setup')) {
                /* Do not display API key generation form. */
            } else {
                echo SucuriScanTemplate::getSection('setup-notice');
                echo SucuriScanTemplate::getModal('setup-form', array(
                    'Visibility' => 'hidden',
                    'Title' => 'Sucuri API key generation',
                    'CssClass' => 'sucuriscan-setup-instructions',
                ));
            }
        }
    }
}
