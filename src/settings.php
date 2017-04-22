<?php

/**
 * Code related to the settings.php interface.
 *
 * @package Sucuri Security
 * @subpackage settings.php
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
 * Abstract class for the settings page.
 */
class SucuriScanSettings extends SucuriScanOption
{
}
