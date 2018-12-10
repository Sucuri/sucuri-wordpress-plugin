<?php

/**
 * Code related to the cli.lib.php interface.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanCLI
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
 * Manage Sucuri API registration.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanCLI extends WP_CLI_Command
{
    /**
     * Register and connect to the Sucuri API.
     *
     * ## OPTIONS
     *
     * [<api_key>]
     * : Sucuri API key to register with.
     *
     * ## EXAMPLES
     *
     *     # New registration
     *     wp sucuri register
     *     API key: 99e656abef7a123d1cffe73f91ba63702
     *     Success: The API key for your site was successfully generated and saved.
     *
     *     # Existing key registration
     *     wp sucuri register 99e656abef7a123d1cffe73f91ba63702
     *     Success: The API key for your site was successfully saved.
     *
     *     # Registration recovery
     *     wp sucuri register
     *     Warning: We already have an API key created for this site. It has been sent to the email admin@example.com for recovery.
     *
     * @param  array $args Arguments from the command line interface.
     * @return void
     */
    public function register($args)
    {
        list($api_key) = $args;

        ob_start();
        $registered = $api_key ? SucuriScanAPI::setPluginKey($api_key, true) : SucuriScanAPI::registerSite();
        $output = ob_get_clean();

        preg_match_all('/<p><b>SUCURI:<\/b>(.+)<\/p>/', $output, $matches);

        $message = isset($matches[1][0]) ? trim(strip_tags($matches[1][0])) : 'An unknown error occurred during registration.';

        if (! $registered) {
            WP_CLI::error($message);
        }

        if ($registered && $api_key) {
            WP_CLI::success('The API key for your site was successfully saved.');
            return;
        }

        $api_key = SucuriScanAPI::getPluginKey();

        WP_CLI::line("API key: $api_key");

        WP_CLI::success($message);
    }

    /**
     * Manage which files are included in Sucuri integrity checks.
     *
     * ## OPTIONS
     *
     * <action>
     * : The action to be taken (ignore or unignore).
     *
     * <file_path>
     * : Relative path to a file.
     *
     * [--reason=<reason>]
     * : Why the file should be ignored from integrity checks.
     * ---
     * default: added
     * options:
     *   - added
     *   - modified
     *   - removed
     * ---
     *
     * ## EXAMPLES
     *
     *     # Ignore a file
     *     wp sucuri integrity ignore wp-admin/install.php --reason=removed
     *     Success: 'wp-admin/install.php' file successfully ignored.
     *
     *     # Unignore a file
     *     wp sucuri integrity unignore foo.php
     *     Success: 'foo.php' file successfully unignored.
     *
     * @param  array $args Arguments from the command line interface.
     * @param  array $assoc_args Associative arguments from the command line interface.
     * @return void
     */
    public function integrity($args, $assoc_args)
    {
        list($action, $file_path) = $args;

        $allowed_actions = array('ignore', 'unignore');

        if (! in_array($action, $allowed_actions, true)) {
            WP_CLI::error("Requested action '{$action}' is not supported.");
        }

        $allowed_reasons = array('added', 'modified', 'removed');

        $file_status = WP_CLI\Utils\get_flag_value( $assoc_args, 'reason', $default = 'added' );

        if (! in_array($file_status, $allowed_reasons, true)) {
            WP_CLI::error("Specified reason '{$file_status}' is not supported.");
        }

        $cache = new SucuriScanCache('integrity');

        $cache_key = md5($file_path);

        if ('ignore' === $action) {
            $cache->add(
                $cache_key,
                array(
                    'file_path' => $file_path,
                    'file_status' => $file_status,
                    'ignored_at' => time(),
                )
            );
            WP_CLI::success("'{$file_path}' file successfully ignored.");
        }

        if ('unignore' === $action) {
            $cache->delete($cache_key);
            WP_CLI::success("'{$file_path}' file successfully unignored.");
        }
    }
}

WP_CLI::add_command('sucuri', 'SucuriScanCLI');
