<?php

/**
 * Code related to the wprecommendations.lib.php checks.
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
 * Make sure the WordPress install follows security best practices.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Northon Torga <northon.torga@sucuri.net>
 * @copyright  2010-2019 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 * @see        https://sitecheck.sucuri.net/
 */
class SucuriWordPressRecomendations
{

    /**
     * Generates the HTML section for the WordPress recommendations section.
     *
     * @return string HTML code to render the recommendations section.
     */
    public static function pageWordPressRecommendations()
    {

        $params = array();
        $recommendations = array();
        $params['WordPress.Recommendations.Content'] = '';

        /**
         * BEGIN security checks.
         * 
         * Each check must register a second array inside $recommendations,
         * containing the title and description of the recommendation.
         */

        // Check if php version needs to be upgraded.
        if (version_compare(phpversion(), '7.1', '<')) {
            $recommendations['PHPVersionCheck'] = array(
                __('Upgrade PHP to a supported version', 'sucuri-scanner') =>
                __('The PHP version you are using no longer receives security support and could be exposed to unpatched security vulnerabilities.', 'sucuri-scanner')
            );
        }

        /**
         * BEGIN delivery of results.
         * 
         * When recommendations array is empty, delivery an "all is good" message,
         * otherwise display each item that needs fixing individually.
         */
        if (count($recommendations) == 0) {

            $params['WordPress.Recommendations.Color'] = 'green';
            $params['WordPress.Recommendations.Content'] = __('Your WordPress install is following <a href="https://sucuri.net/guides/wordpress-security" target="_blank" rel="noopener">the security best practices</a>.', 'sucuri-scanner');
        } else {

            /* set title to blue as not all recommendations have been fullfilled */
            $params['WordPress.Recommendations.Color'] = 'blue';

            /* delivery the recommendations using the getSnippet function */
            $recommendation = array_keys($recommendations);
            foreach ($recommendation as $checkid) {

                foreach ($recommendations[$checkid] as $title => $description) {

                    $params['WordPress.Recommendations.Content'] .= SucuriScanTemplate::getSnippet(
                        'wordpress-recommendations',
                        array(
                            'WordPress.Recommendations.Title' => $title,
                            'WordPress.Recommendations.Value' => $description
                        )
                    );
                }
            }
        }

        return SucuriScanTemplate::getSection('wordpress-recommendations', $params);
    }
}
