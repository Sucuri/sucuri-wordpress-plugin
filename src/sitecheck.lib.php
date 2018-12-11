<?php

/**
 * Code related to the sitecheck.lib.php interface.
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

/**
 * Controls the execution of the SiteCheck scanner.
 *
 * SiteCheck is a web application scanner that reads the source code of a
 * website to determine if it is serving malicious code, it scans the home page
 * and linked sub-pages, then compares the results with a list of signatures as
 * well as a list of blacklist services to see if other malware scanners have
 * flagged the website before. This operation may take a couple of seconds,
 * around twenty seconds in most cases; be sure to set enough timeout for the
 * operation to finish, otherwise the scanner will return innacurate
 * information.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 * @see        https://sitecheck.sucuri.net/
 */
class SucuriScanSiteCheck extends SucuriScanAPI
{
    /**
     * Returns the URL that will be scanned by SiteCheck.
     *
     * @return string URL to be scanned.
     */
    private static function targetURL()
    {
        /* allow to set a custom URL for the scans */
        $custom = SucuriScanOption::getOption(':sitecheck_target');

        if ($custom) {
            return $custom;
        }

        return SucuriScan::getDomain();
    }

    /**
     * Executes a malware scan against the specified website.
     *
     * @see https://sitecheck.sucuri.net/
     *
     * @param  bool $clear Request the results from a fresh scan or not.
     * @return array|bool  JSON encoded website scan results.
     */
    public static function runMalwareScan($clear = false)
    {
        $params = array();
        $params['json'] = 1;
        $params['fromwp'] = 2;
        $params['scan'] = self::targetURL();

        /* force clear scan */
        if ($clear === true) {
            $params['clear'] = 1;
        }

        $args = array('assoc' => true, 'timeout' => 60);

        return self::apiCall('https://sitecheck.sucuri.net/', 'GET', $params, $args);
    }

    /**
     * Scans a website for malware using SiteCheck.
     *
     * This method will first check if the scan results have been cached by a
     * previous scan. The lifetime of the cache is defined in the global script
     * but usually it should not be higher than fifteen minutes. If the cache
     * exists it will be used to display the information in the dashboard.
     *
     * If the cache does not exists or has already expired, it will send a HTTP
     * request to the SiteCheck API service to execute a fresh scan, this takes
     * around twenty seconds, it will decode and process the response and render
     * the results in the dashboard.
     *
     * If the user sends a GET parameter named "s" with a valid domain name, it
     * will be used instead of the one of the current website. This is useful if
     * you want to test the functionality of the scanner in a different website
     * without access to its domain, which is basically the same thing that you
     * can do in the official SiteCheck website. This parameter also bypasses
     * the cache.
     *
     * @return array|bool SiteCheck scan results.
     */
    public static function scanAndCollectData()
    {
        $cache = new SucuriScanCache('sitecheck');

        if (SucuriScanRequest::post(':sitecheck_refresh') === 'true') {
            /* user requested to reset the sitecheck cache */
            $cache->delete('scan_results');
        }

        $results = $cache->get('scan_results', SUCURISCAN_SITECHECK_LIFETIME, 'array');

        /* return cached malware scan results. */
        if ($results && !empty($results)) {
            return $results;
        }

        /* delete expired cache */
        $cache->delete('scan_results');

        /* send HTTP request to SiteCheck's API service. */
        $results = self::runMalwareScan();

        /* check for error in the request's response. */
        if (is_string($results) || isset($results['SYSTEM']['ERROR'])) {
            if (isset($results['SYSTEM']['ERROR'])) {
                $results = implode("\x20", $results['SYSTEM']['ERROR']);
            }

            return SucuriScanInterface::error('SiteCheck error: ' . $results);
        }

        /* cache the results for some time. */
        $cache->add('scan_results', $results);

        return $results;
    }

    /**
     * Returns the amount of time left before the SiteCheck cache expires.
     *
     * @return string Time left before the SiteCheck cache expires.
     */
    private static function cacheLifetime()
    {
        $current = time();
        $cache = new SucuriScanCache('sitecheck');
        $timeDiff = $current - $cache->updatedAt();
        $timeLeft = SUCURISCAN_SITECHECK_LIFETIME - $timeDiff;

        return self::humanTime($current + $timeLeft);
    }

    /**
     * Generates the HTML section for the SiteCheck details.
     *
     * @return string HTML code to render the details section.
     */
    public static function details()
    {
        $params = array();
        $data = self::scanAndCollectData();
        $data['details'] = array();

        $params['SiteCheck.Metadata'] = '';
        $params['SiteCheck.Lifetime'] = self::cacheLifetime();

        $data['details'][] = 'PHP Version: ' . phpversion();
        $data['details'][] = 'Version: ' . SucuriScan::siteVersion();

        if (isset($data['SCAN']['SITE'])) {
            $params['SiteCheck.Website'] = $data['SCAN']['SITE'][0];
        }

        if (isset($data['SCAN']['IP'])) {
            $params['SiteCheck.ServerAddress'] = $data['SCAN']['IP'][0];
        }

        if (isset($data['SCAN']['HOSTING'])) {
            $data['details'][] = 'Hosting: ' . $data['SCAN']['HOSTING'][0];
        }

        if (isset($data['SCAN']['CMS'])) {
            $data['details'][] = 'CMS: ' . $data['SCAN']['CMS'][0];
        }

        if (isset($data['SYSTEM']['NOTICE'])) {
            $data['details'] = array_merge(
                $data['details'],
                $data['SYSTEM']['NOTICE']
            );
        }

        if (isset($data['SYSTEM']['INFO'])) {
            $data['details'] = array_merge(
                $data['details'],
                $data['SYSTEM']['INFO']
            );
        }

        if (isset($data['WEBAPP']['VERSION'])) {
            $data['details'] = array_merge(
                $data['details'],
                $data['WEBAPP']['VERSION']
            );
        }

        if (isset($data['WEBAPP']['WARN'])) {
            $data['details'] = array_merge(
                $data['details'],
                $data['WEBAPP']['WARN']
            );
        }

        if (isset($data['OUTDATEDSCAN'])) {
            foreach ($data['OUTDATEDSCAN'] as $outdated) {
                if (isset($outdated[0]) && isset($outdated[2])) {
                    $data['details'][] = $outdated[0] . ':' . $outdated[2];
                }
            }
        }

        foreach ($data['details'] as $text) {
            $parts = explode(':', $text, 2);

            if (count($parts) === 2) {
                /* prefer local version number over SiteCheck's */
                if (strpos($parts[0], 'WordPress version') !== false) {
                    continue;
                }

                /* redundant; we already know the CMS is WordPress */
                if (strpos($parts[0], 'CMS') !== false) {
                    continue;
                }

                $params['SiteCheck.Metadata'] .= SucuriScanTemplate::getSnippet(
                    'sitecheck-details',
                    array(
                        'SiteCheck.Title' => trim($parts[0]),
                        'SiteCheck.Value' => trim($parts[1]),
                    )
                );
            }
        }

        return SucuriScanTemplate::getSection('sitecheck-details', $params);
    }

    /**
     * Generates the HTML section for the SiteCheck malware.
     *
     * @return string HTML code to render the malware section.
     */
    public static function malware()
    {
        $params = array();
        $data = self::scanAndCollectData();

        $params['Malware.Content'] = '';
        $params['Malware.Color'] = 'green';
        $params['Malware.Title'] = 'Site is Clean';
        $params['Malware.CleanVisibility'] = 'visible';
        $params['Malware.InfectedVisibility'] = 'hidden';

        if (isset($data['MALWARE']['WARN']) && !empty($data['MALWARE']['WARN'])) {
            $params['Malware.Color'] = 'red';
            $params['Malware.Title'] = 'Site is not Clean';
            $params['Malware.CleanVisibility'] = 'hidden';
            $params['Malware.InfectedVisibility'] = 'visible';

            foreach ($data['MALWARE']['WARN'] as $mal) {
                $info = self::malwareDetails($mal);

                if ($info) {
                    $params['Malware.Content'] .= SucuriScanTemplate::getSnippet(
                        'sitecheck-malware',
                        array(
                            'Malware.InfectedURL' => $info['infected_url'],
                            'Malware.MalwareType' => $info['malware_type'],
                            'Malware.MalwareDocs' => $info['malware_docs'],
                            'Malware.AlertMessage' => $info['alert_message'],
                            'Malware.MalwarePayload' => $info['malware_payload'],
                        )
                    );
                }
            }
        }

        return SucuriScanTemplate::getSection('sitecheck-malware', $params);
    }

    /**
     * Generates the HTML section for the SiteCheck blacklist.
     *
     * @return string HTML code to render the blacklist section.
     */
    public static function blacklist()
    {
        $params = array();
        $data = self::scanAndCollectData();

        if (!isset($data['BLACKLIST']) || !is_array($data['BLACKLIST'])) {
            return ''; /* there is not enough information to render */
        }

        $params['Blacklist.Title'] = 'Not Blacklisted';
        $params['Blacklist.Color'] = 'green';
        $params['Blacklist.Content'] = '';

        foreach ($data['BLACKLIST'] as $type => $proof) {
            foreach ($proof as $info) {
                $url = $info[1];
                $title = @preg_replace(
                    '/Domain (clean|blacklisted) (on|by) (the )?/',
                    '' /* remove unnecessary text from the output */,
                    substr($info[0], 0, strrpos($info[0], ':'))
                );

                $params['Blacklist.Content'] .= SucuriScanTemplate::getSnippet(
                    'sitecheck-blacklist',
                    array(
                        'Blacklist.URL' => $url,
                        'Blacklist.Status' => $type,
                        'Blacklist.Service' => $title,
                    )
                );
            }
        }

        if (isset($data['BLACKLIST']['WARN'])) {
            $params['Blacklist.Title'] = 'Blacklisted';
            $params['Blacklist.Color'] = 'red';
        }

        return SucuriScanTemplate::getSection('sitecheck-blacklist', $params);
    }

    /**
     * Generates the HTML section for the SiteCheck recommendations.
     *
     * @return string HTML code to render the recommendations section.
     */
    public static function recommendations()
    {
        $params = array();
        $data = self::scanAndCollectData();
        $sechead = array(
            'x-content-type-options' => 'X-Content-Type-Options Header',
            'x-frame-options' => 'X-Frame-Options Security Header',
            'x-xss-protection' => 'X-XSS-Protection Security Header',
        );

        $params['Recommendations.Content'] = '';
        $params['Recommendations.Color'] = 'green';

        if (isset($data['RECOMMENDATIONS'])) {
            foreach ($data['RECOMMENDATIONS'] as $recommendation) {
                if (count($recommendation) < 3) {
                    continue;
                }

                if (stripos($recommendation[0], 'x-content-type')) {
                    unset($sechead['x-content-type-options']);
                }

                if (stripos($recommendation[0], 'x-frame-options')) {
                    unset($sechead['x-frame-options']);
                }

                if (stripos($recommendation[0], 'x-xss-protection')) {
                    unset($sechead['x-xss-protection']);
                }

                $params['Recommendations.Color'] = 'blue';
                $params['Recommendations.Content'] .= SucuriScanTemplate::getSnippet(
                    'sitecheck-recommendations',
                    array(
                        'Recommendations.Title' => $recommendation[0],
                        'Recommendations.Value' => $recommendation[1],
                        'Recommendations.URL' => $recommendation[2],
                    )
                );
            }
        }

        foreach ($sechead as $header => $message) {
            $params['Recommendations.Content'] .=
                '<li class="sucuriscan-sitecheck-list-INFO">'
                . $message . '</li>';
        }

        return SucuriScanTemplate::getSection('sitecheck-recommendations', $params);
    }

    /**
     * Returns the title for the iFrames section.
     *
     * @return string Title for the iFrames section.
     */
    public static function iFramesTitle()
    {
        $data = self::scanAndCollectData();

        return sprintf('iFrames: %d', @count($data['LINKS']['IFRAME']));
    }

    /**
     * Returns the title for the links section.
     *
     * @return string Title for the links section.
     */
    public static function linksTitle()
    {
        $data = self::scanAndCollectData();

        return sprintf('Links: %d', @count($data['LINKS']['URL']));
    }

    /**
     * Returns the title for the scripts section.
     *
     * @return string Title for the scripts section.
     */
    public static function scriptsTitle()
    {
        $data = self::scanAndCollectData();
        $total = 0; /* all type of scripts */

        if (isset($data['LINKS']['JSLOCAL'])) {
            $total += count($data['LINKS']['JSLOCAL']);
        }

        if (isset($data['LINKS']['JSEXTERNAL'])) {
            $total += count($data['LINKS']['JSEXTERNAL']);
        }

        return sprintf('Scripts: %d', $total);
    }

    /**
     * Returns the content for the iFrames section.
     *
     * @return string Content for the iFrames section.
     */
    public static function iFramesContent()
    {
        $data = self::scanAndCollectData();
        return isset($data['LINKS']['IFRAME']) ? $data['LINKS']['IFRAME'] : array();
    }

    /**
     * Returns the content for the links section.
     *
     * @return string Content for the links section.
     */
    public static function linksContent()
    {
        $data = self::scanAndCollectData();
        return isset($data['LINKS']['URL']) ? $data['LINKS']['URL'] : array();
    }

    /**
     * Returns the content for the scripts section.
     *
     * @return array Content for the scripts section.
     */
    public static function scriptsContent()
    {
        $links = array();
        $data = self::scanAndCollectData();

        if (isset($data['LINKS']['JSLOCAL'])) {
            $links = array_merge($links, $data['LINKS']['JSLOCAL']);
        }

        if (isset($data['LINKS']['JSEXTERNAL'])) {
            $links = array_merge($links, $data['LINKS']['JSEXTERNAL']);
        }

        return $links;
    }

    /**
     * Extract detailed information from a SiteCheck malware payload.
     *
     * @param  array $malware Array with two entries with basic malware information.
     * @return array          Detailed information of the malware found by SiteCheck.
     */
    public static function malwareDetails($malware = array())
    {
        if (count($malware) < 2) {
            return array(/* empty details */);
        }

        $data = array(
            'alert_message' => '',
            'infected_url' => '',
            'malware_type' => '',
            'malware_docs' => '',
            'malware_payload' => '',
        );

        // Extract the information from the alert message.
        $alert_parts = explode(':', $malware[0], 2);

        if (isset($alert_parts[1])) {
            $data['alert_message'] = $alert_parts[0];
            $data['infected_url'] = trim($alert_parts[1]);
        }

        // Extract the information from the malware message.
        $malware_parts = explode("\n", $malware[1], 2);

        if (isset($malware_parts[1])) {
            $pattern = ".\x20Details:\x20";
            if (strpos($malware_parts[0], $pattern) !== false) {
                $offset = strpos($malware_parts[0], $pattern);
                $data['malware_type'] = substr($malware_parts[0], 0, $offset);
                $data['malware_docs'] = substr($malware_parts[0], $offset + 11);
            }

            $data['malware_payload'] = trim($malware_parts[1]);
        }

        return $data;
    }

    /**
     * Returns a JSON-encoded object with the malware scan results.
     *
     * @codeCoverageIgnore - Notice that there is a test case that covers this
     * code, but since the WP-Send-JSON method uses die() to stop any further
     * output it means that XDebug cannot cover the next line, leaving a report
     * with a missing line in the coverage. Since the test case takes care of
     * the functionality of this code we will assume that it is fully covered.
     *
     * @return void
     */
    public static function ajaxMalwareScan()
    {
        if (SucuriScanRequest::post('form_action') !== 'malware_scan') {
            return;
        }

        ob_start();

        $response = array();

        $response['malware'] = SucuriScanSiteCheck::malware();
        $response['blacklist'] = SucuriScanSiteCheck::blacklist();
        $response['recommendations'] = SucuriScanSiteCheck::recommendations();

        $response['iframes'] = array(
            'title' => SucuriScanSiteCheck::iFramesTitle(),
            'content' => SucuriScanSiteCheck::iFramesContent(),
        );
        $response['links'] = array(
            'title' => SucuriScanSiteCheck::linksTitle(),
            'content' => SucuriScanSiteCheck::linksContent(),
        );
        $response['scripts'] = array(
            'title' => SucuriScanSiteCheck::scriptsTitle(),
            'content' => SucuriScanSiteCheck::scriptsContent(),
        );

        $errors = ob_get_clean(); /* capture possible errors */

        if (!empty($errors)) {
            $response['malware'] = '';
            $response['blacklist'] = '';
            $response['recommendations'] = '';
        }

        wp_send_json($response, 200);
    }

    /**
     * Returns the HTML to configure the API SiteCheck service.
     *
     * @return string HTML for the API SiteCheck service option.
     */
    public static function targetURLOption()
    {
        $params = array();

        if (SucuriScanInterface::checkNonce()) {
            $custom = SucuriScanRequest::post(':sitecheck_target');
            if ($custom !== false) {
                SucuriScanOption::updateOption(':sitecheck_target', $custom);
            }
        }

        $params['SiteCheck.Target'] = self::targetURL();

        return SucuriScanTemplate::getSection('sitecheck-target', $params);
    }
}
