<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Display the page with a temporary message explaining the action that will be
 * performed once the hidden form is submitted to retrieve the scanning results
 * from the public SiteCheck API.
 *
 * @return void
 */
function sucuriscan_scanner_page()
{
    SucuriScanInterface::checkPageVisibility();

    $params = array();
    $cache = new SucuriScanCache('sitecheck');
    $scan_results = $cache->get('scan_results', SUCURISCAN_SITECHECK_LIFETIME, 'array');
    $report_results = (bool) ($scan_results && !empty($scan_results));
    $nonce = SucuriScanInterface::checkNonce();

    // Retrieve SiteCheck scan results if user submits the form.
    if ($nonce && SucuriScanRequest::post(':malware_scan')) {
        $report_results = true;
    }

    /**
     * Retrieve SiteCheck results from custom domain.
     *
     * To facilitate the debugging of the code we will allow the existence of a
     * GET parameter that will force the plugin to scan a specific website
     * instead of the website where the plugin is running. Since this will be a
     * semi-hidden feature we can bypass some actions like the recycling of the
     * data returned by a previous scan.
     *
     * Usage: Add "&s=TLD" where TLD is a WordPress or non-WordPress website.
     */
    if ($nonce && SucuriScanRequest::get('s')) {
        $info = $cache->getDatastoreInfo();
        $report_results = true;
        $scan_results = false;

        @unlink($info['fpath']);
    }

    if ($report_results === true) {
        $template_name = 'malwarescan-results';
        $params = sucuriscan_sitecheck_info($scan_results);
        $params['PageTitle'] = 'Malware Scan';
        $params['PageStyleClass'] = 'scanner-results';
    } else {
        $template_name = 'malwarescan';
        $params['PageTitle'] = 'Malware Scan';
        $params['PageStyleClass'] = 'scanner-loading';
    }

    echo SucuriScanTemplate::getTemplate($template_name, $params);
}

/**
 * Handle an Ajax request for this specific page.
 *
 * @return mixed.
 */
function sucuriscan_scanner_ajax()
{
    SucuriScanInterface::checkPageVisibility();

    if (SucuriScanInterface::checkNonce()) {
        sucuriscan_scanner_modfiles_ajax();
    }

    wp_die();
}

/**
 * Display the result of site scan made through SiteCheck.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @return array               Array with psuedo-variables to build the template.
 */
function sucuriscan_sitecheck_info($scan_results = array())
{
    $tld = SucuriScan::getDomain();

    if ($custom = SucuriScanRequest::get('s')) {
        $tld = SucuriScan::escape($custom);
    }

    $params = array(
        'ScannedDomainName' => $tld,
        'ScannerResults.CssClass' => '',
        'ScannerResults.Content' => '',
        'WebsiteDetails.CssClass' => '',
        'WebsiteDetails.Content' => '',
        'BlacklistStatus.CssClass' => '',
        'BlacklistStatus.Content' => '',
        'WebsiteLinks.CssClass' => '',
        'WebsiteLinks.Content' => '',
        'ModifiedFiles.CssClass' => '',
        'ModifiedFiles.Content' => '',
        'SignupButtonVisibility' => 'hidden',
    );

    // If the results are not cached, then request a new scan and store in cache.
    if ($scan_results === false) {
        $scan_results = SucuriScanAPI::getSitecheckResults($tld);

        // Check for error messages in the request's response.
        if (is_string($scan_results)) {
            if (@preg_match('/^ERROR:(.*)/', $scan_results, $error_m)) {
                SucuriScanInterface::error(
                    'The site <code>' . SucuriScan::escape($tld) . '</code>'
                    . ' was not scanned: ' . SucuriScan::escape($error_m[1])
                );
            } else {
                SucuriScanInterface::error('SiteCheck error: ' . $scan_results);
            }
        } else {
            $cache = new SucuriScanCache('sitecheck');
            $results_were_cached = $cache->add('scan_results', $scan_results);

            if (!$results_were_cached) {
                SucuriScanInterface::error('Could not cache the malware scan results.');
            }
        }
    }

    if (is_array($scan_results) && !empty($scan_results)) {
        // Increase the malware scan counter.
        $sitecheck_counter = (int) SucuriScanOption::getOption(':sitecheck_counter');
        SucuriScanOption::updateOption(':sitecheck_counter', $sitecheck_counter + 1);
        add_thickbox();

        $params = sucuriscan_sitecheck_scanner_results($scan_results, $params);
        $params = sucuriscan_sitecheck_website_details($scan_results, $params);
        $params = sucuriscan_sitecheck_website_links($scan_results, $params);
        $params = sucuriscan_sitecheck_blacklist_status($scan_results, $params);

        $params['ModifiedFiles.Content'] = sucuriscan_modified_files();

        if (isset($scan_results['MALWARE']['WARN'])
            || isset($scan_results['BLACKLIST']['WARN'])
        ) {
            $params['SignupButtonVisibility'] = 'visible';
        }
    }

    return $params;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * remote scanner results tab.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_scanner_results($scan_results = false, $params = array())
{
    $secvars = array(
        'CacheLifeTime' => SUCURISCAN_SITECHECK_LIFETIME,
        'WebsiteStatus' => 'Site status unknown',
        'NoMalwareRowVisibility' => 'visible',
        'FixButtonVisibility' => 'hidden',
        'MalwarePayloadList' => '',
    );

    if (isset($scan_results['MALWARE']['WARN'])) {
        $params['ScannerResults.CssClass'] = 'sucuriscan-red-tab';
        $secvars['WebsiteStatus'] = 'Site compromised (malware was identified)';
        $secvars['NoMalwareRowVisibility'] = 'hidden';
        $secvars['FixButtonVisibility'] = 'visible';

        foreach ($scan_results['MALWARE']['WARN'] as $key => $malres) {
            $malres = SucuriScanAPI::getSitecheckMalware($malres);

            if ($malres !== false) {
                $secvars['MalwarePayloadList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-resmalware',
                    array(
                        'MalwareKey' => $key,
                        'MalwareDocs' => $malres['malware_docs'],
                        'MalwareType' => $malres['malware_type'],
                        'MalwarePayload' => $malres['malware_payload'],
                        'AlertMessage' => $malres['alert_message'],
                        'InfectedUrl' => $malres['infected_url'],
                    )
                );
            }
        }
    } else {
        $secvars['WebsiteStatus'] = 'Site clean (no malware was identified)';
    }

    $params['ScannerResults.Content'] = SucuriScanTemplate::getSection('malwarescan-resmalware', $secvars);

    return $params;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_website_details($scan_results = false, $params = array())
{
    $secvars = array(
        'UpdateWebsiteButtonVisibility' => 'hidden',
        'VersionNumberOfTheUpdate' => '0.0',
        'AdminUrlForUpdates' => SucuriScan::adminURL('update-core.php'),
        'GenericInformationList' => '',
        'NoAppDetailsVisibility' => 'visible',
        'ApplicationDetailsList' => '',
        'SystemNoticeList' => '',
        'OutdatedSoftwareList' => '',
        'HasRecommendationsVisibility' => 'hidden',
        'SecurityRecomendationList' => '',
    );

    // Check whether this WordPress installation needs an update.
    if (function_exists('get_core_updates')) {
        $site_updates = get_core_updates();

        if (!is_array($site_updates)
            || empty($site_updates)
            || $site_updates[0]->response == 'latest'
        ) {
            $secvars['VersionNumberOfTheUpdate'] = $site_updates[0]->version;
        }
    }

    if (isset($scan_results['OUTDATEDSCAN'])
        || isset($scan_results['RECOMMENDATIONS'])
    ) {
        $params['WebsiteDetails.CssClass'] = 'sucuriscan-red-tab';
    }

    $secvars = sucuriscan_sitecheck_general_information($scan_results, $secvars);
    $secvars = sucuriscan_sitecheck_application_details($scan_results, $secvars);
    $secvars = sucuriscan_sitecheck_system_notices($scan_results, $secvars);
    $secvars = sucuriscan_sitecheck_outdated_software($scan_results, $secvars);
    $secvars = sucuriscan_sitecheck_recommendations($scan_results, $secvars);

    $params['WebsiteDetails.Content'] = SucuriScanTemplate::getSection('malwarescan-reswebdetails', $secvars);

    return $params;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab and specifically in the general information panel.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_general_information($scan_results = false, $secvars = array())
{
    $possible_keys = array(
        'SITE' => 'Website',
        'DOMAIN' => 'Domain Scanned',
        'IP' => 'Site IP Address',
        'HOSTING' => 'Hosting Company',
        'CMS' => 'CMS Found',
        'WP_VERSION' => 'WordPress Version',
        'PHP_VERSION' => 'PHP Version',
    );

    if (isset($scan_results['SCAN'])) {
        $scan_results['SCAN']['WP_VERSION'] = array(SucuriScan::siteVersion());
        $scan_results['SCAN']['PHP_VERSION'] = array(phpversion());

        foreach ($possible_keys as $result_key => $result_title) {
            if (isset($scan_results['SCAN'][$result_key])) {
                if (is_array($scan_results['SCAN'][$result_key])) {
                    $result_value = implode(', ', $scan_results['SCAN'][$result_key]);
                } else {
                    $result_value = json_encode($scan_results['SCAN'][$result_key]);
                }

                $secvars['GenericInformationList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-appdetail',
                    array(
                        'InformationTitle' => $result_title,
                        'InformationValue' => $result_value,
                    )
                );
            }
        }
    }

    return $secvars;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab and specifically in the application details panel.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_application_details($scan_results = false, $secvars = array())
{
    if (isset($scan_results['WEBAPP'])) {
        foreach ($scan_results['WEBAPP'] as $key => $webapp_details) {
            if ($key !== 'INFO' && $key !== 'VERSION' && $key !== 'NOTICE') {
                /* skip details for unsupported data schema */
                continue;
            }

            /* skip if no data is available */
            if (!is_array($webapp_details)) {
                continue;
            }

            foreach ($webapp_details as $i => $details) {
                $secvars['NoAppDetailsVisibility'] = 'hidden';

                if (is_array($details)) {
                    $details = isset($details[0]) ? $details[0] : '';
                }

                $details_parts = explode(':', $details, 2);
                $result_title = isset($details_parts[0]) ? trim($details_parts[0]) : '';
                $result_value = isset($details_parts[1]) ? trim($details_parts[1]) : '';

                $secvars['ApplicationDetailsList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-appdetail',
                    array(
                        'InformationTitle' => $result_title,
                        'InformationValue' => $result_value,
                    )
                );
            }
        }
    }

    return $secvars;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab and specifically in the system notices panel.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_system_notices($scan_results = false, $secvars = array())
{
    if (isset($scan_results['SYSTEM']['NOTICE'])) {
        foreach ($scan_results['SYSTEM']['NOTICE'] as $notice) {
            $secvars['NoAppDetailsVisibility'] = 'hidden';

            if (is_array($notice)) {
                $notice = implode(', ', $notice);
            }

            $secvars['SystemNoticeList'] .= SucuriScanTemplate::getSnippet(
                'malwarescan-sysnotice',
                array(
                    'SystemNotice' => $notice,
                )
            );
        }
    }

    return $secvars;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab and specifically in the outdated software panel.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_outdated_software($scan_results = false, $secvars = array())
{
    if (isset($scan_results['OUTDATEDSCAN'])) {
        foreach ($scan_results['OUTDATEDSCAN'] as $outdated) {
            if (count($outdated) >= 3) {
                $secvars['HasRecommendationsVisibility'] = 'visible';
                $secvars['OutdatedSoftwareList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-outdated',
                    array(
                        'OutdatedSoftwareTitle' => $outdated[0],
                        'OutdatedSoftwareUrl' => $outdated[1],
                        'OutdatedSoftwareValue' => $outdated[2],
                    )
                );
            }
        }
    }

    return $secvars;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab and specifically in the security recommendations panel.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_recommendations($scan_results = false, $secvars = array())
{
    if (isset($scan_results['RECOMMENDATIONS'])) {
        foreach ($scan_results['RECOMMENDATIONS'] as $recommendation) {
            if (count($recommendation) >= 3) {
                $secvars['HasRecommendationsVisibility'] = 'visible';
                $secvars['SecurityRecomendationList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-recommendation',
                    array(
                        'RecommendationTitle' => $recommendation[0],
                        'RecommendationValue' => $recommendation[1],
                        'RecommendationUrl' => $recommendation[2],
                        'RecommendationUrlTitle' => $recommendation[2],
                    )
                );
            }
        }
    }

    return $secvars;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website links tab.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra information.
 */
function sucuriscan_sitecheck_website_links($scan_results = false, $params = array())
{
    $possible_url_keys = array(
        'IFRAME' => 'List of iframes found',
        'JSEXTERNAL' => 'List of external scripts included',
        'JSLOCAL' => 'List of scripts included',
        'URL' => 'List of links found',
    );
    $secvars = array(
        'WebsiteLinksAllList' => '',
        'NoLinksVisibility' => 'hidden',
    );

    if (isset($scan_results['LINKS'])) {
        foreach ($possible_url_keys as $result_key => $result_title) {
            if (isset($scan_results['LINKS'][$result_key])) {
                $result_value = 0;
                $result_items = '';

                foreach ($scan_results['LINKS'][$result_key] as $url_path) {
                    $result_value += 1;
                    $result_items .= SucuriScanTemplate::getSnippet(
                        'malwarescan-weblinkitems',
                        array(
                            'WebsiteLinksItemTitle' => $url_path,
                        )
                    );
                }

                $secvars['WebsiteLinksAllList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-weblinktitle',
                    array(
                        'WebsiteLinksSectionTitle' => $result_title,
                        'WebsiteLinksSectionTotal' => $result_value,
                        'WebsiteLinksSectionItems' => $result_items /* Do not escape. */,
                    )
                );
            }
        }
    } else {
        $secvars['NoLinksVisibility'] = 'visible';
    }

    $params['WebsiteLinks.Content'] = SucuriScanTemplate::getSection('malwarescan-resweblinks', $secvars);

    return $params;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * blacklist status tab.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_blacklist_status($scan_results = false, $params = array())
{
    $blacklist_types = array(
        'INFO' => 'CLEAN',
        'WARN' => 'WARNING',
    );
    $secvars = array(
        'BlacklistStatusTitle' => 'Site blacklist-free',
        'BlacklistStatusList' => '',
    );

    if (isset($scan_results['BLACKLIST']['WARN'])) {
        $params['BlacklistStatusTitle'] = 'Site blacklisted';
        $params['BlacklistStatus.CssClass'] = 'sucuriscan-red-tab';
    }

    foreach ($blacklist_types as $type => $group_title) {
        if (isset($scan_results['BLACKLIST'][$type])) {
            foreach ($scan_results['BLACKLIST'][$type] as $blres) {
                $css_blacklist = ($type == 'INFO') ? 'success' : 'danger';

                $secvars['BlacklistStatusList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-resblacklist',
                    array(
                        'BlacklistStatusCssClass' => $css_blacklist,
                        'BlacklistStatusGroupTitle' => $group_title,
                        'BlacklistStatusReporterName' => $blres[0],
                        'BlacklistStatusReporterUrl' => $blres[1],
                    )
                );
            }
        }
    }

    $params['BlacklistStatus.Content'] = SucuriScanTemplate::getSection('malwarescan-resblacklist', $secvars);

    return $params;
}
