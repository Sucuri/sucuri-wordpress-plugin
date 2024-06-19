<?php

/**
 * Code related to the settings-alerts.php interface.
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

define('SUCURISCAN_CACHEOPTIONS_DATASTORE_FILE', 'sucuri-cacheoptions.php');

/**
 * Get the filepath where the information of the cache options is stored.
 *
 * @return string Absolute filepath where the ache options is stored.
 */
function sucuriscan_cacheoptions_datastore_filepath()
{
	return SucuriScan::dataStorePath('sucuri-cacheoptions.php');
}

/**
 * Check whether the cache options datastore file exists or not, if not then
 * we try to create the file and check again the success of the operation.
 *
 * @return string|bool Path to the storage file if exists, false otherwise.
 */
function sucuriscan_cacheoptions_datastore_exists()
{
	$fpath = sucuriscan_cacheoptions_datastore_filepath();

	if (!file_exists($fpath)) {
		@file_put_contents($fpath, "<?php exit(0); ?>\n", LOCK_EX);
	}

	return file_exists($fpath) ? $fpath : false;
}

/**
 * Check whether cache options datastore file is writable or not, if not
 * we try to set the right permissions and check again the success of the operation.
 *
 * @return string|bool Path to the storage file if writable, false otherwise.
 */
function sucuriscan_cacheoptions_datastore_is_writable()
{
	$datastore_filepath = sucuriscan_cacheoptions_datastore_filepath();

	if ($datastore_filepath) {
		if (!is_writable($datastore_filepath)) {
			@chmod($datastore_filepath, 0644);
		}

		if (is_writable($datastore_filepath)) {
			return $datastore_filepath;
		}
	}

	return false;
}

/**
 * Check whether the cache options datastore file is readable or not, if not
 * we try to set the right permissions and check again the success of the operation.
 *
 * @return string|bool Path to the storage file if readable, false otherwise.
 */
function sucuriscan_cacheoptions_datastore_is_readable()
{
	$datastore_filepath = sucuriscan_cacheoptions_datastore_exists();

	if ($datastore_filepath && is_readable($datastore_filepath)) {
		return $datastore_filepath;
	}

	return false;
}

/**
 * Returns the HTML to configure cache options.
 * TODO: Update this comment.
 * By default the plugin sends the email notifications about the security events
 * to the first email address used during the installation of the website. This
 * is usually the email of the website owner. The plugin allows to add more
 * emails to the list so the alerts are sent to other people.
 *
 * @param  bool $nonce True if the CSRF protection worked, false otherwise.
 * @return string      HTML for the email alert recipients.
 */
function sucuriscan_settings_cache_options($nonce)
{
	$params = array(
		'CacheOptions.Options' => '',
	);


	$cacheOptions = array(
		'cache_option_front_page' => array(
			'name' => 'Front Page',
			'max-age' => SucuriScanOption::getOption(':cache_option_front_page_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_front_page_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_front_page_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_front_page_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_front_page_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_front_page_old_age_multiplier'),
		),
		'cache_option_posts' => array(
			'name' => 'Posts',
			'max-age' => SucuriScanOption::getOption(':cache_option_posts_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_posts_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_posts_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_posts_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_posts_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_posts_old_age_multiplier'),
		),
		'cache_option_pages' => array(
			'name' => 'Pages',
			'max-age' => SucuriScanOption::getOption(':cache_option_pages_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_pages_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_pages_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_pages_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_pages_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_pages_old_age_multiplier'),
		),
		'cache_option_main_index' => array(
			'name' => 'Main Index',
			'max-age' => SucuriScanOption::getOption(':cache_option_main_index_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_main_index_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_main_index_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_main_index_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_main_index_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_main_index_old_age_multiplier'),
		),
		'cache_option_categories' => array(
			'name' => 'Categories',
			'max-age' => SucuriScanOption::getOption(':cache_option_categories_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_categories_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_categories_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_categories_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_categories_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_categories_old_age_multiplier'),
		),
		'cache_option_tags' => array(
			'name' => 'Tags',
			'max-age' => SucuriScanOption::getOption(':cache_option_tags_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_tags_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_tags_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_tags_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_tags_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_tags_old_age_multiplier'),
		),
		'cache_option_authors' => array(
			'name' => 'Authors',
			'max-age' => SucuriScanOption::getOption(':cache_option_authors_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_authors_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_authors_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_authors_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_authors_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_authors_old_age_multiplier'),
		),
		'cache_option_dated_archives' => array(
			'name' => 'Dated Archives',
			'max-age' => SucuriScanOption::getOption(':cache_option_dated_archives_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_dated_archives_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_dated_archives_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_dated_archives_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_dated_archives_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_dated_archives_old_age_multiplier'),
		),
		'cache_option_feeds' => array(
			'name' => 'Feeds',
			'max-age' => SucuriScanOption::getOption(':cache_option_feeds_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_feeds_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_feeds_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_feeds_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_feeds_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_feeds_old_age_multiplier'),
		),
		'cache_option_attachment_pages' => array(
			'name' => 'Attachment Pages',
			'max-age' => SucuriScanOption::getOption(':cache_option_attachment_pages_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_attachment_pages_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_attachment_pages_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_attachment_pages_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_attachment_pages_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_attachment_pages_old_age_multiplier'),
		),
		'cache_option_search_results' => array(
			'name' => 'Search Results',
			'max-age' => SucuriScanOption::getOption(':cache_option_search_results_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_search_results_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_search_results_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_search_results_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_search_results_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_search_results_old_age_multiplier'),
		),
		'cache_option_404_not_found' => array(
			'name' => '404 Not Found',
			'max-age' => SucuriScanOption::getOption(':cache_option_404_not_found_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_404_not_found_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_404_not_found_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_404_not_found_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_404_not_found_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_404_not_found_old_age_multiplier'),
		),
		'cache_option_permanent_redirects' => array(
			'name' => 'Permanent Redirects',
			'max-age' => SucuriScanOption::getOption(':cache_option_permanent_redirects_max_age'),
			's-maxage' => SucuriScanOption::getOption(':cache_option_permanent_redirects_s_maxage'),
			'stale-if-error' => SucuriScanOption::getOption(':cache_option_permanent_redirects_stale_if_error'),
			'stale-while-revalidate' => SucuriScanOption::getOption(':cache_option_permanent_redirects_stale_while_revalidate'),
			'pagination-factor' => SucuriScanOption::getOption(':cache_option_permanent_redirects_pagination_factor'),
			'old-age-multiplier' => SucuriScanOption::getOption(':cache_option_permanent_redirects_old_age_multiplier'),
		),
	);



	foreach ($cacheOptions as $option) {
		$params['CacheOptions.Options'] .= SucuriScanTemplate::getSnippet(
			'settings-headers-cache-option',
			array(
				'name' => $option['name'],
				'maxAge' => $option['max-age'],
				'sMaxAge' => $option['s-maxage'],
				'staleIferror' => $option['stale-if-error'],
				'staleWhileRevalidate' => $option['stale-while-revalidate'],
				'paginationFactor' => $option['pagination-factor'],
				'oldAgeMultiplier' => $option['old-age-multiplier'],
			)
		);
	}

	$params['CacheOptions.NoItemsVisibility'] = 'hidden';

	return SucuriScanTemplate::getSection('settings-headers-cache', $params);
}