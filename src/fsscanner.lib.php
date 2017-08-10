<?php

/**
 * Code related to the fsscanner.lib.php interface.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2017 Sucuri Inc.
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
 * File System Scanner
 *
 * The File System Scanner component performs full and incremental scans over a
 * file system folder, maintaining a snapshot of the filesystem and comparing it
 * with the current content to establish what content has been updated. Updated
 * content is then submitted to the remote server and it is stored for future
 * analysis.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2017 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanFSScanner extends SucuriScan
{
    /**
     * Retrieve the last time when the filesystem scan was ran.
     *
     * @param  bool $format Whether the timestamp must be formatted as date/time or not.
     * @return string       The timestamp of the runtime, or an string with the date/time.
     */
    public static function getFilesystemRuntime($format = false)
    {
        $runtime = SucuriScanOption::getOption(':runtime');

        if ($runtime > 0) {
            if ($format) {
                return SucuriScan::datetime($runtime);
            }

            return $runtime;
        }

        return 'Unknown';
    }

    /**
     * Add a new directory path to the list of ignored paths.
     *
     * @param  string $path The (full) absolute path of a directory.
     * @return bool         TRUE if the directory path was added to the list, FALSE otherwise.
     */
    public static function ignoreDirectory($path = '')
    {
        $cache = new SucuriScanCache('ignorescanning');
        $resource_type = SucuriScanFileInfo::getResourceType($path);
        $cache_value = array(
            'directory_path' => $path,
            'ignored_at' => time(),
            'resource_type' => $resource_type,
        );

        return $cache->add(md5($path), $cache_value);
    }

    /**
     * Remove a directory path from the list of ignored paths.
     *
     * @param  string $path The (full) absolute path of a directory.
     * @return bool         TRUE if the directory path was removed to the list, FALSE otherwise.
     */
    public static function unignoreDirectory($path = '')
    {
        $cache = new SucuriScanCache('ignorescanning');

        return $cache->delete(md5($path));
    }

    /**
     * Returns a list of ignored directories.
     *
     * The method returns an array with the following keys:
     *
     * - raw:             Contains the raw data from the local cache.
     * - checksums:       Contains the md5 of all the directories.
     * - directories:     Contains a list of directories.
     * - ignored_at_list: Contains a list of timestamps.
     *
     * @return array List of ignored directories.
     */
    public static function getIgnoredDirectories()
    {
        $response = array(
            'raw' => array(),
            'checksums' => array(),
            'directories' => array(),
            'ignored_at_list' => array(),
        );

        $cache = new SucuriScanCache('ignorescanning');
        $cache_lifetime = 0; // It is not necessary to expire this cache.
        $entries = $cache->getAll($cache_lifetime, 'array');

        if ($entries) {
            $response['raw'] = $entries;

            foreach ($entries as $checksum => $data) {
                if (isset($data['directory_path']) && isset($data['ignored_at'])) {
                    $response['checksums'][] = $checksum;
                    $response['directories'][] = $data['directory_path'];
                    $response['ignored_at_list'][] = $data['ignored_at'];
                }
            }
        }

        return $response;
    }
}
