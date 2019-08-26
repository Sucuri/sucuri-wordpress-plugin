<?php

/**
 * Code related to the wprecommendations.lib.php checks.
 *
 * PHP version 5
 *
 * @category   Library
 *
 * @author     Northon Torga <northon.torga@sucuri.net>
 * @copyright  2010-2019 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 *
 * @see        https://wordpress.org/plugins/sucuri-scanner
 */
if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Make sure the WordPress install follows security best practices.
 *
 * @category   Library
 *
 * @author     Northon Torga <northon.torga@sucuri.net>
 * @copyright  2010-2019 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 *
 * @see        https://wordpress.org/plugins/sucuri-scanner
 * @see        https://sucuri.net/guides/wordpress-security/
 */
class SucuriWordPressRecommendations
{
    /**
     * Generates the HTML section for the WordPress recommendations section.
     *
     * @return string HTML code to render the recommendations section
     */
    public static function pageWordPressRecommendations()
    {
        $params = array();
        $recommendations = array();
        $params['WordPress.Recommendations.Content'] = '';

        /*
         * SECURITY CHECKS
         *
         * Each check must register a second array inside $recommendations,
         * containing the title and description of the recommendation.
         */

        /*
         * Check if WordPress is using an SSL certificate.
         * @see https://blog.sucuri.net/2019/03/how-to-add-ssl-move-wordpress-from-http-to-https.html
         */
        if (!is_ssl()) {
            // phpcs:disable Generic.Files.LineLength
            $recommendations['noSSL'] = array(
                __('Implement an SSL Certificate', 'sucuri-scanner') => __('SSL certificates help protect the integrity of the data in transit between the host (web server or firewall) and the client (web browser).', 'sucuri-scanner'),
            );
            // phpcs:enable
        }

        /*
         * Check if PHP version being used needs to be upgraded.
         * @see https://www.php.net/supported-versions.php
         */
        if (version_compare(phpversion(), '7.2', '<')) {
            // phpcs:disable Generic.Files.LineLength
            $recommendations['PHPVersionCheck'] = array(
                __('Upgrade PHP to a supported version', 'sucuri-scanner') => __('The PHP version you are using no longer receives security support and could be exposed to unpatched security vulnerabilities.', 'sucuri-scanner'),
            );
            // phpcs:enable
        }

        /*
         * Check if WordPress Salt & Security Keys are set and were updated on the last 6 months.
         * @see https://wordpress.org/support/article/editing-wp-config-php/#security-keys
         * @see https://sucuri.net/guides/wordpress-security/#harrec
         */
        if (!defined('AUTH_KEY') || !defined('AUTH_SALT')) {
            // phpcs:disable Generic.Files.LineLength
            $recommendations['wpSaltExistenceChecker'] = array(
                __('Missing WordPress Salt & Security Keys', 'sucuri-scanner') => __('Consider using WordPress Salt & Security Keys to add an extra layer of protection to the session cookies and credentials.', 'sucuri-scanner'),
            );
        // phpcs:enable
        } elseif (file_exists(ABSPATH.'/wp-config.php')) {
            if (filemtime(ABSPATH.'/wp-config.php') < strtotime('-12 months')) {
                // phpcs:disable Generic.Files.LineLength
                $recommendations['wpSaltAgeDiscriminator'] = array(
                    __('WordPress Salt & Security Keys should be updated', 'sucuri-scanner') => __('Updating WordPress Salt & Security Keys after a compromise and on a regular basis, at least once a year, reduces the risks of session hijacking.', 'sucuri-scanner'),
                );
                // phpcs:enable
            }
        }

        /*
         * Check if there is a standard administrator/admin account.
         * @see https://sucuri.net/guides/wordpress-security/#uac
         */
        $usersWithAdminLogin = get_users(array(
            'role' => 'administrator',
            'fields' => array( 'user_login' ),
            'search' => 'admin'
        ));
        $usersWithAdminstratorLogin = get_users(array(
            'role' => 'administrator',
            'fields' => array( 'user_login' ),
            'search' => 'administrator'
        ));
        if (!empty($usersWithAdminLogin) || !empty($usersWithAdminstratorLogin)) {
            // phpcs:disable Generic.Files.LineLength
            $recommendations['adminBadUsername'] = array(
                __('Admin/Administrator username still exists', 'sucuri-scanner') => __('Using a unique username and removing the default admin/administrator account make it more difficult for attackers to brute force your WordPress.', 'sucuri-scanner'),
            );
            // phpcs:enable
        }

        /*
         * Check if user is using the super-admin for day-to-day operations.
         * @see https://sucuri.net/guides/wordpress-security/#uac
         */
        $wpUsersCount = count_users();
        if ($wpUsersCount['total_users'] === 1) {
            // phpcs:disable Generic.Files.LineLength
            $recommendations['lonelySuperAdmin'] = array(
                __('Use super admin account only when needed', 'sucuri-scanner') => __('Create an Editor account instead of always using the super-admin to reduce the damage in case of session hijacking.', 'sucuri-scanner'),
            );
            // phpcs:enable
        }

        /*
         * Check if user a 2FA plugin is being used.
         * @see https://sucuri.net/guides/wordpress-security/#pwd
         *
         * First the script will get the plugins name, then run a regex match to
         * identify possible 2FA plugins being used.
         *
         * NOTE: $wpPluginsInstalledName, $wpPluginsActivatedName, $wpPluginsDeactivatedName
         * are created by this feature.
         */
        $wpPluginsInstalled = get_plugins();
        foreach ($wpPluginsInstalled as $pluginPath => $pluginDetails) {
            $wpPluginsInstalledName[] = $pluginDetails['Name'];
            if (is_plugin_active($pluginPath)) {
                $wpPluginsActivatedName[] = $pluginDetails['Name'];
            } else {
                $wpPluginsDeactivatedName[] = $pluginDetails['Name'];
            }
        }
        // phpcs:disable Generic.Files.LineLength
        $wp2faPluginsInstalled = preg_grep('/(2FA|2\sFactor|Two\sFactor|Two-Factor|2-Factor|Secure\sLogin|SecSign|Authentication|Wordfence\sSecurity|iThemes\sSecurity\sPro|Limit\sLogin|LDAP)/i', $wpPluginsActivatedName);
        if (empty($wp2faPluginsInstalled)) {
            $recommendations['loginUnprotected'] = array(
                __('Unable to detect a popular 2FA plugin', 'sucuri-scanner') => __('Do you have another 2FA solution in place? If not, it\'s recommended that you add a 2FA plugin to protect your website.', 'sucuri-scanner'),
            );
        }
        // phpcs:enable

        /*
         * Check for unwanted extensions.
         * @see https://sucuri.net/guides/wordpress-security/#apt
         *
         * Triggered if there are more than 2 themes or 5 plugins disabled and the
         * WordPress install is not a multisite.
         *
         * NOTE: This check uses $wpPluginsDeactivatedName which is created by
         * the 2FA plugin check.
         */
        if ((count(wp_get_themes()) > 2 || count($wpPluginsDeactivatedName) > 1) && !is_multisite()) {
            // phpcs:disable Generic.Files.LineLength
            $recommendations['forgottenExtensionFestival'] = array(
                __('Remove unwanted/unused extensions', 'sucuri-scanner') => __('Keeping unwanted themes and plugins increases the chance of a compromise, even if they are disabled.', 'sucuri-scanner'),
            );
            // phpcs:enable
        }

        /*
         * Check for too much plugins.
         * @see https://sucuri.net/guides/wordpress-security/#apt
         */
        if (count($wpPluginsInstalled) > 50 && !is_multisite()) {
            // phpcs:disable Generic.Files.LineLength
            $recommendations['tooMuchPlugins'] = array(
                __('Decrease the number of plugins', 'sucuri-scanner') => __('The greater the number of plugins installed, the greater the risk of infection and performance issues.', 'sucuri-scanner'),
            );
            // phpcs:enable
        }

        /*
         * Check for backup plugins.
         * @see https://sucuri.net/guides/wordpress-security/#bup
         *
         * NOTE: This check uses $wpPluginsInstalledName which is created by
         * the 2FA plugin check.
         */
        // phpcs:disable Generic.Files.LineLength
        $wpBackupsPluginsInstalled = preg_grep('/(Backup|Back-up|Migration|VaultPress|Duplicator|Snapshot|ManageWP|iControlWP|Staging|Updraft|Backwp|Recovery)/i', $wpPluginsActivatedName);
        if (empty($wpBackupsPluginsInstalled)) {
            $recommendations['lackOfBackupsPlugins'] = array(
                __('Unable to detect a popular backup plugin', 'sucuri-scanner') => __('Do you have another backup solution in place? If not, it\'s recommended that you add a backup plugin to recover your website when needed.', 'sucuri-scanner'),
            );
        }
        // phpcs:enable

        /*
         * Check if File Editing is still possible via wp-admin.
         * @see https://sucuri.net/guides/wordpress-security/#appconf
         */
        if (!defined('DISALLOW_FILE_EDIT')) {
            // phpcs:disable Generic.Files.LineLength
            $recommendations['fileEditStillEnabled'] = array(
                __('Disable file editing', 'sucuri-scanner') => __('Using "DISALLOW_FILE_EDIT" helps prevent an attacker from changing your files through WordPress backend.', 'sucuri-scanner'),
            );
            // phpcs:enable
        }

        /*
         * Check if WordPress Debug Mode is set.
         * @see https://wordpress.org/support/article/debugging-in-wordpress/
         */
        if (defined('WP_DEBUG')) {
            if (WP_DEBUG) {
                // phpcs:disable Generic.Files.LineLength
                $recommendations['wpDebugOnline'] = array(
                    __('Disable WordPress debug mode', 'sucuri-scanner') => __('When "WP_DEBUG" is set to true, it will cause all PHP errors, notices and warnings to be displayed which can expose sensitive information.', 'sucuri-scanner'),
                );
                // phpcs:enable
            }
        }

        /*
         * Check if Hardening was applied as long as this is not a NGINX/IIS server.
         * @see https://sucuri.net/guides/wordpress-security/#harrec
         */
        if (
            !SucuriScan::isNginxServer() &&
            !SucuriScan::isIISServer() &&
            (!SucuriScanHardening::isHardened(WP_CONTENT_DIR) ||
            !SucuriScanHardening::isHardened(ABSPATH.'/wp-includes'))
            ) {
            // phpcs:disable Generic.Files.LineLength
            $recommendations['notHardened'] = array(
                __('Prevent PHP direct execution on sensitive directories', 'sucuri-scanner') => __('Directories such as "wp-content" and "wp-includes" are generally not intended to be accessed by any user, consider hardening them via Sucuri Security -> Settings -> Hardening.', 'sucuri-scanner'),
            );
            // phpcs:enable
        }

        /*
         * DELIVERY RESULTS
         *
         * Delivery an "all is good" message, unless recommendations array has values,
         * in which case the plugin must display the items that need fixing.
         */
        $params['WordPress.Recommendations.Color'] = 'green';
        // phpcs:disable Generic.Files.LineLength
        $params['WordPress.Recommendations.Content'] = __('Your WordPress install is following <a href="https://sucuri.net/guides/wordpress-security" target="_blank" rel="noopener">the security best practices</a>.', 'sucuri-scanner');
        // phpcs:enable

        if (count($recommendations) !== 0) {
            /* Set title to blue as not there is still recommendations to be followed. */
            $params['WordPress.Recommendations.Color'] = 'blue';
            $params['WordPress.Recommendations.Content'] = null;

            /* Delivery the recommendations using the getSnippet function. */
            $recommendation = array_keys($recommendations);
            foreach ($recommendation as $checkid) {
                foreach ($recommendations[$checkid] as $title => $description) {
                    $params['WordPress.Recommendations.Content'] .= SucuriScanTemplate::getSnippet(
                        'wordpress-recommendations',
                        array(
                            'WordPress.Recommendations.Title' => $title,
                            'WordPress.Recommendations.Value' => $description,
                        )
                    );
                }
            }
        }

        return SucuriScanTemplate::getSection('wordpress-recommendations', $params);
    }
}
