<?php

/**
 * Code related to the cron.lib.php interface.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Northon Torga <northon.torga@sucuri.net>
 * @copyright  2010-2019 Sucuri Inc.
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
 * Class to process Sucuri custom cronjobs.
 *
 * Here are implemented the cronjob methods used by the plugin.
 *
 * Remember: methods must be static and their name must match the cron hook.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Northon Torga <northon.torga@sucuri.net>
 * @copyright  2010-2019 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanCrons extends SucuriScan
{
    /**
     * Update secret keys.
     */
    public static function sucuriscan_autoseckeyupdater()
    {
        $wpconfig_process = SucuriScanEvent::setNewConfigKeys();
        if (!$wpconfig_process) {
            SucuriScanEvent::reportNoticeEvent(__('Automatic update of security keys failed. WordPress configuration file was not found or new keys could not be created.', 'sucuri-scanner'));
        } elseif ($wpconfig_process['updated']) {
            SucuriScanEvent::reportNoticeEvent(__('Automatic update of security keys succeeded.', 'sucuri-scanner'));
        } else {
            SucuriScanEvent::reportNoticeEvent(__('Automatic update of security keys failed. Something went wrong!', 'sucuri-scanner'));
        }
    }
}
