<?php

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
     * @param  boolean $format Whether the timestamp must be formatted as date/time or not.
     * @return string          The timestamp of the runtime, or an string with the date/time.
     */
    public static function get_filesystem_runtime($format = false)
    {
        $runtime = SucuriScanOption::get_option(':runtime');

        if ($runtime > 0) {
            if ($format) {
                return SucuriScan::datetime($runtime);
            }

            return $runtime;
        }

        if ($format) {
            return 'Unknown';
        }

        return false;
    }

    /**
     * Check whether the administrator enabled the feature to ignore some
     * directories during the file system scans. This function is overwritten by a
     * GET parameter in the settings page named no_scan which must be equal to the
     * number one.
     *
     * @return boolean Whether the feature to ignore files is enabled or not.
     */
    public static function will_ignore_scanning()
    {
        return SucuriScanOption::is_enabled(':ignore_scanning');
    }

    /**
     * Add a new directory path to the list of ignored paths.
     *
     * @param  string  $directory_path The (full) absolute path of a directory.
     * @return boolean                 TRUE if the directory path was added to the list, FALSE otherwise.
     */
    public static function ignore_directory($directory_path = '')
    {
        $cache = new SucuriScanCache('ignorescanning');

        // Use the checksum of the directory path as the cache key.
        $cache_key = md5($directory_path);
        $resource_type = SucuriScanFileInfo::get_resource_type($directory_path);
        $cache_value = array(
            'directory_path' => $directory_path,
            'ignored_at' => self::local_time(),
            'resource_type' => $resource_type,
        );
        $cached = $cache->add($cache_key, $cache_value);

        return $cached;
    }

    /**
     * Remove a directory path from the list of ignored paths.
     *
     * @param  string  $directory_path The (full) absolute path of a directory.
     * @return boolean                 TRUE if the directory path was removed to the list, FALSE otherwise.
     */
    public static function unignore_directory($directory_path = '')
    {
        $cache = new SucuriScanCache('ignorescanning');

        // Use the checksum of the directory path as the cache key.
        $cache_key = md5($directory_path);
        $removed = $cache->delete($cache_key);

        return $removed;
    }

    /**
     * Retrieve a list of directories ignored.
     *
     * Retrieve a list of directory paths that will be ignored during the file
     * system scans, any sub-directory and files inside these folders will be
     * skipped automatically and will not be used to detect malware or modifications
     * in the site.
     *
     * The structure of the array returned by the function will always be composed
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
    public static function get_ignored_directories()
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
    public static function get_ignored_directories_live()
    {
        $response = array(
            'is_ignored' => array(),
            'is_not_ignored' => array(),
        );

        // Get the ignored directories from the cache.
        $ignored_directories = self::get_ignored_directories();

        if ($ignored_directories) {
            $response['is_ignored'] = $ignored_directories['raw'];
        }

        // Scan the project and file all directories.
        $file_info = new SucuriScanFileInfo();
        $file_info->ignore_files = true;
        $file_info->ignore_directories = true;
        $file_info->scan_interface = SucuriScanOption::get_option(':scan_interface');
        $directory_list = $file_info->get_diretories_only(ABSPATH);

        if ($directory_list) {
            $response['is_not_ignored'] = $directory_list;
        }

        return $response;
    }

    /**
     * Read and parse the lines inside a PHP error log file.
     *
     * @param  array $error_logs The content of an error log file, or an array with the lines.
     * @return array             List of valid error logs with their attributes separated.
     */
    public static function parse_error_logs($error_logs = array())
    {
        $logs_arr = array();
        $pattern = '/^'
            . '(\[(\S+) ([0-9:]{5,8})( \S+)?\] )?' // Detect date, time, and timezone.
            . '(PHP )?([a-zA-Z ]+):\s'             // Detect PHP error severity.
            . '(.+) in (.+)'                       // Detect error message, and file path.
            . '(:| on line )([0-9]+)'              // Detect line number.
            . '$/';

        if (is_string($error_logs)) {
            $error_logs = explode("\n", $error_logs);
        }

        foreach ((array) $error_logs as $line) {
            if (!is_string($line) || empty($line)) {
                continue;
            }

            if (preg_match($pattern, $line, $match)) {
                $data_set = array(
                    'date' => '',
                    'time' => '',
                    'timestamp' => 0,
                    'date_time' => '',
                    'time_zone' => '',
                    'error_type' => '',
                    'error_code' => 'unknown',
                    'error_message' => '',
                    'file_path' => '',
                    'line_number' => 0,
                );

                // Basic attributes from the scrapping.
                $data_set['date'] = $match[2];
                $data_set['time'] = $match[3];
                $data_set['time_zone'] = trim($match[4]);
                $data_set['error_type'] = trim($match[6]);
                $data_set['error_message'] = trim($match[7]);
                $data_set['file_path'] = trim($match[8]);
                $data_set['line_number'] = (int) $match[10];

                // Additional data from the attributes.
                if ($data_set['date']) {
                    $data_set['date_time'] = $data_set['date']
                        . "\x20" . $data_set['time']
                        . "\x20" . $data_set['time_zone'];
                    $data_set['timestamp'] = strtotime($data_set['date_time']);
                }

                if ($data_set['error_type']) {
                    $valid_types = array( 'warning', 'notice', 'error' );

                    foreach ($valid_types as $valid_type) {
                        if (stripos($data_set['error_type'], $valid_type) !== false) {
                            $data_set['error_code'] = $valid_type;
                            break;
                        }
                    }
                }

                $logs_arr[] = (object) $data_set;
            }
        }

        return $logs_arr;
    }
}