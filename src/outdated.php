<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Check whether the WordPress version is outdated or not.
 *
 * @return string Panel with a warning advising that WordPress is outdated.
 */
function sucuriscan_wordpress_outdated()
{
    $site_version = SucuriScan::siteVersion();
    $updates = get_core_updates();
    $cp = (!is_array($updates) || empty($updates) ? 1 : 0);

    $params = array(
        'WordPress.Version' => $site_version,
        'WordPress.NewVersion' => '0.0.0',
        'WordPress.NewLocale' => 'default',
        'WordPress.UpdateURL' => SucuriScan::adminURL('update-core.php'),
        'WordPress.DownloadURL' => '#',
        'WordPress.UpdateVisibility' => 'hidden',
    );

    if (isset($updates[0])
        && $updates[0] instanceof stdClass
        && property_exists($updates[0], 'version')
        && property_exists($updates[0], 'download')
    ) {
        $params['WordPress.NewVersion'] = $updates[0]->version;
        $params['WordPress.DownloadURL'] = $updates[0]->download;

        if (property_exists($updates[0], 'locale')) {
            $params['WordPress.NewLocale'] = $updates[0]->locale;
        }

        if ($updates[0]->response == 'latest'
            || $updates[0]->response == 'development'
            || $updates[0]->version == $site_version
        ) {
            $cp = 1;
        }
    }

    if ($cp == 0) {
        $params['WordPress.UpdateVisibility'] = 'visible';
    }

    return SucuriScanTemplate::getSection('integrity-wpoutdate', $params);
}
