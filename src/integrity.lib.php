<?php

/**
 * Code related to the integrity.lib.php interface.
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
 * Checks the integrity of the WordPress installation.
 *
 * This tool finds changes in the standard WordPress installation. Files located
 * in the root directory, wp-admin and wp-includes will be compared against the
 * files distributed with the current WordPress version; all files with
 * inconsistencies will be listed here.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2017 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanIntegrity
{
    /**
     * Compare the md5sum of the core files in the current site with the hashes hosted
     * remotely in Sucuri servers. These hashes are updated every time a new version
     * of WordPress is released. If the "Send Email" parameter is set the method will
     * send a notification to the administrator with a list of files that were added,
     * modified and/or deleted so far.
     *
     * @return string HTML code with a list of files that were affected.
     */
    public static function pageIntegrity()
    {
        $params = array();

        self::pageIntegritySubmission();

        return SucuriScanTemplate::getSection('integrity', $params);
    }

    /**
     * Returns a JSON-encoded object with the WordPress integrity status.
     *
     * The plugin gets the checksum of all the files installed in the server that
     * are also part of a normal WordPress package. Then, it communicates with
     * a WordPress API service to retrieve the official checksums of the files
     * distributed with the package with the same version installed in the site.
     *
     * For any file found in the site that is not part of a normal installation
     * the plugin will report it as ADDED, for any file that is missing from the
     * installation but part of the official WordPress package, the plugin will
     * report it as DELETED, and for every file found in the site that is also
     * part of a normal installation, it will report it as MODIFIED if there are
     * differences in their checksums.
     *
     * @codeCoverageIgnore - Notice that there is a test case that covers this
     * code, but since the WP-Send-JSON method uses die() to stop any further
     * output it means that XDebug cannot cover the next line, leaving a report
     * with a missing line in the coverage. Since the test case takes care of
     * the functionality of this code we will assume that it is fully covered.
     *
     * @return void
     */
    public static function ajaxIntegrity()
    {
        if (SucuriScanRequest::post('form_action') !== 'check_wordpress_integrity') {
            return;
        }

        wp_send_json(self::getIntegrityStatus(), 200);
    }

    /**
     * Mark as fixed, restore or delete flagged integrity files.
     *
     * Process the HTTP requests sent by the form submissions originated in the
     * integrity page, all forms must have a nonce field that will be checked
     * against the one generated in the template render function.
     *
     * @return void
     */
    private static function pageIntegritySubmission()
    {
        /* restore, remove, mark as fixed the core files */
        $action = SucuriScanRequest::post(':integrity_action');

        if ($action === false || !SucuriScanInterface::checkNonce()) {
            return; /* skip if the action or nonce is invalid */
        }

        /* skip if the user didn't confirm the operation */
        if (SucuriScanRequest::post(':process_form') != 1) {
            return SucuriScanInterface::error('You need to confirm that you understand the risk of this operation.');
        }

        /* skip if the requested action is not currently supported */
        if ($action !== 'fixed' && $action !== 'delete' && $action !== 'restore') {
            return SucuriScanInterface::error('Requested action is not supported.');
        }

        /* process the HTTP request */
        $cache = new SucuriScanCache('integrity');
        $core_files = SucuriScanRequest::post(':integrity', '_array');
        $files_selected = count($core_files);
        $files_affected = array();
        $files_processed = 0;
        $action_titles = array(
            'restore' => 'Core file restored',
            'delete' => 'Non-core file deleted',
            'fixed' => 'Core file marked as fixed',
        );

        /* skip if no files were selected */
        if (!$core_files) {
            return SucuriScanInterface::error('Nothing was selected from the list.');
        }

        /* process files until the maximum execution time is reached */
        $startTime = microtime(true);
        $displayTimeoutAlert = false;
        $maxtime = (int) SucuriScan::iniGet('max_execution_time');
        $timeout = ($maxtime > 1) ? ($maxtime - 6) : 30;

        foreach ((array) $core_files as $file_meta) {
            if (strpos($file_meta, '@') === false) {
                continue;
            }

            /* avoid gateway timeout; max execution time */
            $elapsedTime = (microtime(true) - $startTime);
            if ($elapsedTime >= $timeout) {
                $displayTimeoutAlert = true;
                break;
            }

            @list($status_type, $file_path) = explode('@', $file_meta, 2);

            if (!$file_path || !$status_type) {
                continue;
            }

            $full_path = ABSPATH . '/' . $file_path;

            if ($action === 'fixed' && ($status_type === 'added' || $status_type === 'removed' || $status_type === 'modified')) {
                $cache_key = md5($file_path);
                $cache_value = array(
                    'file_path' => $file_path,
                    'file_status' => $status_type,
                    'ignored_at' => time(),
                );

                if ($cache->add($cache_key, $cache_value)) {
                    $files_affected[] = $full_path;
                    $files_processed++;
                }
                continue;
            }

            if ($action === 'restore' && ($status_type === 'removed' || $status_type === 'modified')) {
                $content = SucuriScanAPI::getOriginalCoreFile($file_path);

                if ($content) {
                    $basedir = dirname($full_path);

                    if (!file_exists($basedir)) {
                        @mkdir($basedir, 0755, true);
                    }

                    if (@file_put_contents($full_path, $content)) {
                        $files_affected[] = $full_path;
                        $files_processed++;
                    }
                }
                continue;
            }

            if ($action === 'delete' && $status_type === 'added') {
                if (@unlink($full_path)) {
                    $files_affected[] = $full_path;
                    $files_processed++;
                }
                continue;
            }
        }

        /* report files affected as a single event */
        if (!empty($files_affected)) {
            $message = $action_titles[$action] . ':';
            $message .= count($files_affected) > 1 ? "\x20(multiple entries):\x20" : '';
            $message .= @implode(',', $files_affected);

            switch ($action) {
                case 'restore':
                    SucuriScanEvent::reportInfoEvent($message);
                    break;

                case 'delete':
                    SucuriScanEvent::reportNoticeEvent($message);
                    break;

                case 'fixed':
                    SucuriScanEvent::reportWarningEvent($message);
                    break;
            }
        }

        if ($displayTimeoutAlert) {
            SucuriScanInterface::error('Server is not fast enough to process this action; maximum execution time reached');
        }

        if ($files_processed != $files_selected) {
            return SucuriScanInterface::error(
                sprintf(
                    'Only <b>%d</b> out of <b>%d</b> files were processed.',
                    $files_processed,
                    $files_selected
                )
            );
        }

        return SucuriScanInterface::info(
            sprintf(
                '<b>%d</b> out of <b>%d</b> files were successfully processed.',
                $files_processed,
                $files_selected
            )
        );
    }

    /**
     * Checks if the WordPress integrity is correct or not.
     *
     * For any file found in the site that is not part of a normal installation
     * the plugin will report it as ADDED, for any file that is missing from the
     * installation but part of the official WordPress package, the plugin will
     * report it as DELETED, and for every file found in the site that is also
     * part of a normal installation, it will report it as MODIFIED if there are
     * differences in their checksums.
     *
     * The website owner will receive an email alert with this information.
     *
     * @param  bool $send_email Send an email alert to the admins.
     * @return string|bool      HTML with information about the integrity.
     */
    public static function getIntegrityStatus($send_email = false)
    {
        $params = array();
        $affected_files = 0;

        $params['Version'] = SucuriScan::siteVersion();
        $params['Integrity.List'] = '';
        $params['Integrity.ListCount'] = 0;
        $params['Integrity.RemoteChecksumsURL'] = '';
        $params['Integrity.BadVisibility'] = 'hidden';
        $params['Integrity.GoodVisibility'] = 'hidden';
        $params['Integrity.FailureVisibility'] = 'visible';
        $params['Integrity.FalsePositivesVisibility'] = 'hidden';
        $params['Integrity.DiffUtility'] = '';

        // Check if there are added, removed, or modified files.
        $latest_hashes = self::checkIntegrityIntegrity();
        $params['Integrity.RemoteChecksumsURL'] = SucuriScanAPI::checksumAPI();

        if ($latest_hashes) {
            $cache = new SucuriScanCache('integrity');
            $ignored_files = $cache->getAll();
            $counter = 0;

            $params['Integrity.BadVisibility'] = 'hidden';
            $params['Integrity.GoodVisibility'] = 'visible';
            $params['Integrity.FailureVisibility'] = 'hidden';

            foreach ($latest_hashes as $list_type => $file_list) {
                if ($list_type == 'stable' || empty($file_list)) {
                    continue;
                }

                foreach ($file_list as $file_info) {
                    $file_path = $file_info['filepath'];
                    $full_filepath = sprintf('%s/%s', rtrim(ABSPATH, '/'), $file_path);

                    if ($ignored_files /* skip files marked as fixed */
                        && array_key_exists(md5($file_path), $ignored_files)
                    ) {
                        $params['Integrity.FalsePositivesVisibility'] = 'visible';
                        continue;
                    }

                    // Add extra information to the file list.
                    $file_size = @filesize($full_filepath);
                    $file_size_human = ''; /* empty */

                    /* error message if the file cannot be fixed */
                    $error = '';
                    $visibility = 'hidden';

                    if ($file_info['is_fixable'] !== true) {
                        $visibility = 'visible';

                        if ($list_type === 'added') {
                            $error = 'The plugin has no permission to delete this file because it was created by a different system user who has more privileges than your account. Please use FTP to delete it.';
                        } elseif ($list_type === 'modified') {
                            $error = 'The plugin has no permission to restore this file because it was modified by a different system user who has more privileges than your account. Please use FTP to restore it.';
                        } elseif ($list_type === 'removed') {
                            $error = 'The plugin has no permission to restore this file because its directory is owned by a different system user who has more privileges than your account. Please use FTP to restore it.';
                        }
                    }

                    // Pretty-print the file size in human-readable form.
                    if ($file_size !== false) {
                        $file_size_human = SucuriScan::humanFileSize($file_size);
                    }

                    $modified_at = $file_info['modified_at'] ? SucuriScan::datetime($file_info['modified_at']) : '';

                    // Generate the HTML code from the snippet template for this file.
                    $params['Integrity.List'] .= SucuriScanTemplate::getSnippet(
                        'integrity-incorrect',
                        array(
                            'Integrity.StatusType' => $list_type,
                            'Integrity.FilePath' => $file_path,
                            'Integrity.FileSize' => $file_size,
                            'Integrity.FileSizeHuman' => $file_size_human,
                            'Integrity.FileSizeNumber' => number_format($file_size),
                            'Integrity.ModifiedAt' => $modified_at,
                            'Integrity.ErrorVisibility' => $visibility,
                            'Integrity.ErrorMessage' => $error,
                        )
                    );
                    $affected_files++;
                    $counter++;
                }
            }

            if ($counter > 0) {
                $params['Integrity.ListCount'] = $counter;
                $params['Integrity.BadVisibility'] = 'visible';
                $params['Integrity.GoodVisibility'] = 'hidden';
            }
        }

        if ($send_email === true) {
            if ($affected_files > 0) {
                return SucuriScanEvent::notifyEvent(
                    'scan_checksums', /* send alert with a list of affected files */
                    SucuriScanTemplate::getSection('integrity-notification', $params)
                );
            }

            return false;
        }

        ob_start();
        $details = SucuriScanSiteCheck::details();
        $errors = ob_get_clean(); /* capture possible errors */
        $params['SiteCheck.Details'] = empty($errors) ? $details : '<br>'.$errors;

        $params['Integrity.DiffUtility'] = SucuriScanIntegrity::diffUtility();

        $template = ($affected_files === 0) ? 'correct' : 'incorrect';
        return SucuriScanTemplate::getSection('integrity-' . $template, $params);
    }

    /**
     * Setups the page to allow the execution of the diff utility.
     *
     * This method will write the modal window and the JavaScript code that will
     * allow the admin to send an Ajax request to inspect the difference between
     * a file that is currently installed in the website and the original code
     * distributed with the official WordPress package.
     *
     * @return string HTML and JavaScript code for the diff utility.
     */
    public static function diffUtility()
    {
        if (!SucuriScanOption::isEnabled(':diff_utility')) {
            return ''; /* empty page */
        }

        $params = array();

        $params['DiffUtility.Modal'] = SucuriScanTemplate::getModal(
            'none',
            array(
                'Title' => 'WordPress Integrity Diff Utility',
                'Content' => '' /* empty */,
                'Identifier' => 'diff-utility',
                'Visibility' => 'hidden',
            )
        );

        return SucuriScanTemplate::getSection('integrity-diff-utility', $params);
    }

    /**
     * Returns the output of the diff utility.
     *
     * Some errors will be reported if the admin requests to see the differences
     * in a file that is not part of the official WordPress distribution. Also,
     * if the file does not exists it will be useless to see the differences
     * because obviously the content of the file will all be missing. The plugin
     * will thrown an exception in this case too.
     *
     * @codeCoverageIgnore - Notice that there is a test case that covers this
     * code, but since the WP-Send-JSON method uses die() to stop any further
     * output it means that XDebug cannot cover the next line, leaving a report
     * with a missing line in the coverage. Since the test case takes care of
     * the functionality of this code we will assume that it is fully covered.
     *
     * @return void
     */
    public static function ajaxIntegrityDiffUtility()
    {
        if (SucuriScanRequest::post('form_action') !== 'integrity_diff_utility') {
            return;
        }

        ob_start();
        $filename = SucuriScanRequest::post('filepath');
        echo SucuriScanCommand::diffHTML($filename);
        $response = ob_get_clean();

        wp_send_json($response, 200);
    }

    /**
     * Retrieve a list of md5sum and last modification time of all the files in the
     * folder specified. This is a recursive function.
     *
     * @param  string $dir       The base path where the scanning will start.
     * @param  bool   $recursive Either TRUE or FALSE if the scan should be performed recursively.
     * @return array             List of arrays containing the md5sum and last modification time of the files found.
     */
    private static function integrityTree($dir = './', $recursive = false)
    {
        $file_info = new SucuriScanFileInfo();
        $file_info->ignore_files = false;
        $file_info->ignore_directories = false;
        $file_info->run_recursively = $recursive;

        $tree = $file_info->getDirectoryTreeMd5($dir, true);

        return !$tree ? array() : $tree;
    }

    /**
     * Check whether the core WordPress files where modified, removed or if any file
     * was added to the core folders. This method returns an associative array with
     * these keys:
     *
     * <ul>
     *   <li>modified: Files with a different checksum according to the official WordPress archives,</li>
     *   <li>stable: Files with the same checksums than the official files,</li>
     *   <li>removed: Official files which are not present in the local project,</li>
     *   <li>added: Files present in the local project but not in the official WordPress packages.</li>
     * </ul>
     *
     * @return array|bool Associative array with these keys: modified, stable, removed, added.
     */
    private static function checkIntegrityIntegrity()
    {
        $base_content_dir = '';
        $latest_hashes = SucuriScanAPI::getOfficialChecksums();

        if (defined('WP_CONTENT_DIR')) {
            $base_content_dir = basename(rtrim(WP_CONTENT_DIR, '/'));
        }

        // @codeCoverageIgnoreStart
        if (!$latest_hashes) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        $output = array(
            'added' => array(),
            'removed' => array(),
            'modified' => array(),
            'stable' => array(),
        );

        // Get current filesystem tree.
        $wp_top_hashes = self::integrityTree(ABSPATH, false);
        $wp_admin_hashes = self::integrityTree(ABSPATH . 'wp-admin', true);
        $wp_includes_hashes = self::integrityTree(ABSPATH . 'wp-includes', true);
        $wp_core_hashes = array_merge($wp_top_hashes, $wp_admin_hashes, $wp_includes_hashes);
        $checksumAlgorithm = SucuriScanAPI::checksumAlgorithm();

        // Compare remote and local checksums and search removed files.
        foreach ($latest_hashes as $file_path => $remote) {
            if (self::ignoreIntegrityFilepath($file_path)) {
                continue;
            }

            $full_filepath = sprintf('%s/%s', ABSPATH, $file_path);

            // @codeCoverageIgnoreStart
            if (!file_exists($full_filepath)
                && defined('WP_CONTENT_DIR')
                && strpos($file_path, 'wp-content') !== false
            ) {
                /* patch for custom content directory path */
                $file_path = str_replace('wp-content', $base_content_dir, $file_path);
                $dir_content_dir = dirname(rtrim(WP_CONTENT_DIR, '/'));
                $full_filepath = sprintf('%s/%s', $dir_content_dir, $file_path);
            }
            // @codeCoverageIgnoreEnd

            // Check whether the official file exists or not.
            if (file_exists($full_filepath)) {
                /* skip folders; cannot calculate a hash easily */
                if (is_dir($full_filepath)) {
                    $output['stable'][] = array(
                        'filepath' => $file_path,
                        'is_fixable' => false,
                        'modified_at' => 0,
                    );
                    continue;
                }

                $local = SucuriScanAPI::checksum($checksumAlgorithm, $full_filepath);

                if ($local !== false && $local === $remote) {
                    $output['stable'][] = array(
                        'filepath' => $file_path,
                        'is_fixable' => false,
                        'modified_at' => 0,
                    );
                } else {
                    $modified_at = @filemtime($full_filepath);
                    $is_fixable = (bool) is_writable($full_filepath);
                    $output['modified'][] = array(
                        'filepath' => $file_path,
                        'is_fixable' => $is_fixable,
                        'modified_at' => $modified_at,
                    );
                }
            } else {
                $is_fixable = is_writable(dirname($full_filepath));
                $output['removed'][] = array(
                    'filepath' => $file_path,
                    'is_fixable' => $is_fixable,
                    'modified_at' => 0,
                );
            }
        }

        // Search added files (files not common in a normal wordpress installation).
        foreach ($wp_core_hashes as $file_path => $extra_info) {
            $file_path = str_replace(DIRECTORY_SEPARATOR, '/', $file_path);
            $file_path = @preg_replace('/^\.\/(.*)/', '$1', $file_path);

            if (self::ignoreIntegrityFilepath($file_path)) {
                continue;
            }

            if (!array_key_exists($file_path, $latest_hashes)) {
                $full_filepath = ABSPATH . '/' . $file_path;
                $modified_at = @filemtime($full_filepath);
                $is_fixable = (bool) is_writable($full_filepath);
                $output['added'][] = array(
                    'filepath' => $file_path,
                    'is_fixable' => $is_fixable,
                    'modified_at' => $modified_at,
                );
            }
        }

        return $output;
    }

    /**
     * Ignore irrelevant files and directories from the integrity checking.
     *
     * @param  string $path File path that will be compared.
     * @return bool         True if the file should be ignored, false otherwise.
     */
    private static function ignoreIntegrityFilepath($path = '')
    {
        $irrelevant = array(
            'php.ini',
            '.htaccess',
            '.htpasswd',
            '.ftpquota',
            'wp-includes/.htaccess',
            'wp-admin/setup-config.php',
            'wp-tests-config.php',
            'wp-config.php',
            'sitemap.xml',
            'sitemap.xml.gz',
            'readme.html',
            'error_log',
            'wp-pass.php',
            'wp-rss.php',
            'wp-feed.php',
            'wp-register.php',
            'wp-atom.php',
            'wp-commentsrss2.php',
            'wp-rss2.php',
            'wp-rdf.php',
            '404.php',
            '503.php',
            '500.php',
            '500.shtml',
            '400.shtml',
            '401.shtml',
            '402.shtml',
            '403.shtml',
            '404.shtml',
            '405.shtml',
            '406.shtml',
            '407.shtml',
            '408.shtml',
            '409.shtml',
            'healthcheck.html',
        );

        /**
         * Ignore i18n files.
         *
         * Sites with i18n have differences compared with the official English
         * version of the project, basically they have files with new variables
         * specifying the language that will be used in the admin panel, site
         * options, and emails.
         */
        if (@$GLOBALS['wp_local_package'] != 'en_US') {
            $irrelevant[] = 'wp-includes/version.php';
            $irrelevant[] = 'wp-config-sample.php';
        }

        if (in_array($path, $irrelevant)) {
            return true;
        }

        /* use regular expressions */
        $ignore = false;
        $irrelevant = array(
            '^sucuri-[0-9a-z\-]+\.php$',
            '^\S+-sucuri-db-dump-gzip-[0-9]{10}-[0-9a-z]{32}\.gz$',
            '^\.sucuri-sss-resume-[0-9a-z]{32}\.php$',
            '^([^\/]*)\.(pdf|css|txt|jpg|gif|png|jpeg)$',
            '^wp-content\/(themes|plugins)\/.+',
            '^google[0-9a-z]{16}\.html$',
            '^pinterest-[0-9a-z]{5}\.html$',
            '\.ico$',
        );

        foreach ($irrelevant as $pattern) {
            if (@preg_match('/'.$pattern.'/', $path)) {
                $ignore = true;
                break;
            }
        }

        return $ignore;
    }
}
