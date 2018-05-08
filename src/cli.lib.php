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
}

WP_CLI::add_command('sucuri', 'SucuriScanCLI');
