<?php
/**
 * Cache options library
 *
 * @package Sucuri
 */

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
	if (!headers_sent()) {
		/* Report invalid access if possible. */
		header('HTTP/1.1 403 Forbidden');
	}
	exit(1);
}

/**
 * Cache options library
 *
 * @package Sucuri
 */

class SucuriScanCacheHeaders extends SucuriScan
{
	public function __construct()
	{
		//$this->setCacheHeaders();
	}

	public function setCacheHeaders()
	{
		// Headers are already sent; Nothing to do here.
//		if (headers_sent()) {
//			return false;
//		}

		$maxAge = SucuriScanOption::getOption(':cache_option_front_page_max_age');
		$cacheHeader = 'Cache-Control: max-age=' . $maxAge;


		header($cacheHeader);
	}
}
