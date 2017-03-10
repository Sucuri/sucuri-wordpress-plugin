<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Read and parse the content of the trust-ip settings template.
 *
 * @return string Parsed HTML code for the trust-ip settings panel.
 */
function sucuriscan_settings_trust_ip()
{
    $params = array();
    $params['TrustedIPs.List'] = '';
    $params['TrustedIPs.NoItems.Visibility'] = 'visible';

    $cache = new SucuriScanCache('trustip');

    if (SucuriScanInterface::checkNonce()) {
        // Trust and IP address to ignore notifications for a subnet.
        if ($trust_ip = SucuriScanRequest::post(':trust_ip')) {
            if (SucuriScan::isValidIP($trust_ip) || SucuriScan::isValidCIDR($trust_ip)) {
                $ip_info = SucuriScan::getIPInfo($trust_ip);
                $ip_info['added_at'] = SucuriScan::localTime();
                $cache_key = md5($ip_info['remote_addr']);

                if ($cache->exists($cache_key)) {
                    SucuriScanInterface::error('The IP address specified was already trusted.');
                } elseif ($cache->add($cache_key, $ip_info)) {
                    $message = 'Changes from <code>' . $trust_ip . '</code> will be ignored';

                    SucuriScanEvent::reportWarningEvent($message);
                    SucuriScanInterface::info($message);
                } else {
                    SucuriScanInterface::error('The new entry was not saved in the datastore file.');
                }
            }
        }

        // Trust and IP address to ignore notifications for a subnet.
        if ($del_trust_ip = SucuriScanRequest::post(':del_trust_ip', '_array')) {
            foreach ($del_trust_ip as $cache_key) {
                $cache->delete($cache_key);
            }

            SucuriScanInterface::info('The IP addresses selected were deleted successfully.');
        }
    }

    $trusted_ips = $cache->getAll();

    if ($trusted_ips) {
        $counter = 0;

        foreach ($trusted_ips as $cache_key => $ip_info) {
            $css_class = ($counter % 2 === 0) ? '' : 'alternate';

            if ($ip_info->cidr_range == 32) {
                $ip_info->cidr_format = 'n/a';
            }

            $params['TrustedIPs.List'] .= SucuriScanTemplate::getSnippet(
                'settings-trustip',
                array(
                    'TrustIP.CssClass' => $css_class,
                    'TrustIP.CacheKey' => $cache_key,
                    'TrustIP.RemoteAddr' => SucuriScan::escape($ip_info->remote_addr),
                    'TrustIP.CIDRFormat' => SucuriScan::escape($ip_info->cidr_format),
                    'TrustIP.AddedAt' => SucuriScan::datetime($ip_info->added_at),
                )
            );

            $counter++;
        }

        if ($counter > 0) {
            $params['TrustedIPs.NoItems.Visibility'] = 'hidden';
        }
    }

    return SucuriScanTemplate::getSection('settings-trustip', $params);
}
