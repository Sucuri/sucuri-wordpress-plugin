<?php

/**
 * Code related to the fsscanner.lib.php interface.
 *
 * @package Sucuri Security
 * @subpackage fsscanner.lib.php
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
 * File System Scanner
 *
 * The File System Scanner component performs full and incremental scans over a
 * file system folder, maintaining a snapshot of the filesystem and comparing it
 * with the current content to establish what content has been updated. Updated
 * content is then submitted to the remote server and it is stored for future
 * analysis.
 */
class SucuriScanFSScanner extends SucuriScan
{
    /**
     * Retrieve the last time when the filesystem scan was ran.
     *
     * @param bool $format Whether the timestamp must be formatted as date/time or not.
     * @return string The timestamp of the runtime, or an string with the date/time.
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
     * @param string $path The (full) absolute path of a directory.
     * @return bool TRUE if the directory path was added to the list, FALSE otherwise.
     */
    public static function ignoreDirectory($path = '')
    {
        $cache = new SucuriScanCache('ignorescanning');
        $resource_type = SucuriScanFileInfo::getResourceType($path);
        $cache_value = array(
            'directory_path' => $path,
            'ignored_at' => self::localTime(),
            'resource_type' => $resource_type,
        );

        return $cache->add(md5($path), $cache_value);
    }

    /**
     * Remove a directory path from the list of ignored paths.
     *
     * @param string $path The (full) absolute path of a directory.
     * @return bool TRUE if the directory path was removed to the list, FALSE otherwise.
     */
    public static function unignoreDirectory($path = '')
    {
        $cache = new SucuriScanCache('ignorescanning');

        return $cache->delete(md5($path));
    }

    /**
     * Retrieve a list of directories ignored.
     *
     * Retrieve a list of directory paths that will be ignored during the file
     * system scans, any sub-directory and files inside these folders will be
     * skipped automatically and will not be used to detect malware or modifications
     * in the site.
     *
     * The structure of the array returned by the method will always be composed
     * by four (4) indexes which will facilitate the execution of common conditions
     * in the implementation code.
     *
     * <ul>
     * <li>raw: Will contains the raw data retrieved from the built-in cache system.</li>
     * <li>checksums: Will contains the md5 of all the directory paths.</li>
     * <li>directories: Will contains a list of directory paths.</li>
     * <li>ignored_at_list: Will contains a list of timestamps for when the directories were ignored.</li>
     * </ul>
     *
     * @return array List of ignored directory paths.
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
        $ignored_directories = $cache->getAll($cache_lifetime, 'array');

        if ($ignored_directories) {
            $response['raw'] = $ignored_directories;

            foreach ($ignored_directories as $checksum => $data) {
                if (array_key_exists('directory_path', $data)
                    && array_key_exists('ignored_at', $data)
                ) {
                    $response['checksums'][] = $checksum;
                    $response['directories'][] = $data['directory_path'];
                    $response['ignored_at_list'][] = $data['ignored_at'];
                }
            }
        }

        return $response;
    }

    /**
     * Run file system scan and retrieve ignored folders.
     *
     * Run a file system scan and retrieve an array with two indexes, the first
     * containing a list of ignored directory paths and their respective timestamps
     * of when they were added by an administrator user, and the second containing a
     * list of directories that are not being ignored.
     *
     * @return array List of ignored and not ignored directories.
     */
    public static function getIgnoredDirectoriesLive()
    {
        $response = array(
            'is_ignored' => array(),
            'is_not_ignored' => array(),
        );

        // Get the ignored directories from the cache.
        $ignored_directories = self::getIgnoredDirectories();

        if ($ignored_directories) {
            $response['is_ignored'] = $ignored_directories['raw'];
        }

        // Scan the project and file all directories.
        $file_info = new SucuriScanFileInfo();
        $file_info->ignore_files = true;
        $file_info->ignore_directories = true;
        $directory_list = $file_info->getDirectoriesOnly(ABSPATH);

        if ($directory_list) {
            $response['is_not_ignored'] = $directory_list;
        }

        return $response;
    }
}
