<?php

/**
 * Code related to the cli.lib.php interface.
 *
 * @package Sucuri Security
 * @subpackage cli.lib.php
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
 * Manage Sucuri API registration.
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
