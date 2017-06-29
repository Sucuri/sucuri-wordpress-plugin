<?php

/**
 * Code related to the integrity.lib.php interface.
 *
 * @package Sucuri Security
 * @subpackage integrity.lib.php
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
 * Checks the integrity of the WordPress installation.
 *
 * This tool finds changes in the standard WordPress installation. Files located
 * in the root directory, wp-admin and wp-includes will be compared against the
 * files distributed with the current WordPress version; all files with
 * inconsistencies will be listed here.
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
            return SucuriScanInterface::error(__('ConfirmOperation', SUCURISCAN_TEXTDOMAIN));
        }

        /* skip if the requested action is not currently supported */
        if ($action !== 'fixed' && $action !== 'delete' && $action !== 'restore') {
            return SucuriScanInterface::error(__('NonSupportedAction', SUCURISCAN_TEXTDOMAIN));
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
            return SucuriScanInterface::error(__('NothingSelected', SUCURISCAN_TEXTDOMAIN));
        }

        foreach ((array) $core_files as $file_meta) {
            if (strpos($file_meta, '@')) {
                @list($status_type, $file_path) = explode('@', $file_meta, 2);

                if (!$file_path || !$status_type) {
                    continue;
                }

                $full_path = ABSPATH . '/' . $file_path;

                if ($action === 'fixed' && (
                    $status_type === 'added'
                    || $status_type === 'removed'
                    || $status_type === 'modified'
                )) {
                    $cache_key = md5($file_path);
                    $cache_value = array(
                        'file_path' => $file_path,
                        'file_status' => $status_type,
                        'ignored_at' => time(),
                    );
                    $cached = $cache->add($cache_key, $cache_value);
                    $files_processed += ($cached ? 1 : 0);
                    $files_affected[] = $full_path;
                    continue;
                }

                if ($action === 'restore' && (
                    $status_type === 'removed'
                    || $status_type === 'modified'
                )) {
                    if ($content = SucuriScanAPI::getOriginalCoreFile($file_path)) {
                        $basedir = dirname($full_path);
                        @mkdir($basedir, 0755, true);

                        if (file_exists($basedir)) {
                            $restored = @file_put_contents($full_path, $content);
                            $files_processed += ($restored ? 1 : 0);
                            $files_affected[] = $full_path;
                        }
                    }
                    continue;
                }

                if ($action === 'delete' && $status_type === 'added') {
                    if (@unlink($full_path)) {
                        $files_processed += 1;
                        $files_affected[] = $full_path;
                    }
                    continue;
                }
            }
        }

        /* report files affected as a single event */
        if (!empty($files_affected)) {
            $message_tpl = (count($files_affected) > 1)
                ? '%s: (multiple entries): %s'
                : '%s: %s';
            $message = sprintf(
                $message_tpl,
                $action_titles[$action],
                @implode(',', $files_affected)
            );

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

        if ($files_processed != $files_selected) {
            return SucuriScanInterface::error(sprintf(
                __('SomeItemsProcessed', SUCURISCAN_TEXTDOMAIN),
                $files_processed,
                $files_selected
            ));
        }

        return SucuriScanInterface::info(sprintf(
            __('AllItemsProcessed', SUCURISCAN_TEXTDOMAIN),
            $files_processed,
            $files_selected
        ));
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
     * @param bool $send_email Send an email alert to the admins.
     * @return string|bool HTML with information about the integrity.
     */
    public static function getIntegrityStatus($send_email = false)
    {
        $params = array();
        $affected_files = 0;
        $siteVersion = SucuriScan::siteVersion();

        $params['Version'] = SucuriScan::siteVersion();
        $params['Integrity.List'] = '';
        $params['Integrity.ListCount'] = 0;
        $params['Integrity.RemoteChecksumsURL'] = '';
        $params['Integrity.BadVisibility'] = 'hidden';
        $params['Integrity.GoodVisibility'] = 'hidden';
        $params['Integrity.FailureVisibility'] = 'visible';
        $params['Integrity.FalsePositivesVisibility'] = 'hidden';

        /* Check if we have already ignored irrelevant files */
        self::ignoreIrrelevantFiles();

        if ($siteVersion) {
            // Check if there are added, removed, or modified files.
            $latest_hashes = self::checkIntegrityIntegrity($siteVersion);
            $language = SucuriScanOption::getOption(':language');
            $params['Integrity.RemoteChecksumsURL'] =
                'https://api.wordpress.org/core/checksums/1.0/'
                . '?version=' . $siteVersion . '&locale=' . $language;

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
                                $error = __('ErrorIntegrityAdded', SUCURISCAN_TEXTDOMAIN);
                            } elseif ($list_type === 'modified') {
                                $error = __('ErrorIntegrityModified', SUCURISCAN_TEXTDOMAIN);
                            } elseif ($list_type === 'removed') {
                                $error = __('ErrorIntegrityRemoved', SUCURISCAN_TEXTDOMAIN);
                            }
                        }

                        // Pretty-print the file size in human-readable form.
                        if ($file_size !== false) {
                            $file_size_human = SucuriScan::humanFileSize($file_size);
                        }

                        // Generate the HTML code from the snippet template for this file.
                        $params['Integrity.List'] .=
                        SucuriScanTemplate::getSnippet('integrity-incorrect', array(
                            'Integrity.StatusType' => $list_type,
                            'Integrity.FilePath' => $file_path,
                            'Integrity.FileSize' => $file_size,
                            'Integrity.FileSizeHuman' => $file_size_human,
                            'Integrity.FileSizeNumber' => number_format($file_size),
                            'Integrity.ModifiedAt' => SucuriScan::datetime($file_info['modified_at']),
                            'Integrity.ErrorVisibility' => $visibility,
                            'Integrity.ErrorMessage' => $error,
                        ));
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
        }

        if ($send_email === true) {
            return ($affected_files > 0)
            ? SucuriScanEvent::notifyEvent(
                'scan_checksums', /* send alert with a list of affected files */
                SucuriScanTemplate::getSection('integrity-notification', $params)
            )
            : false;
        }

        ob_start();
        $details = SucuriScanSiteCheck::details();
        $errors = ob_get_clean(); /* capture possible errors */
        $params['SiteCheck.Details'] = empty($errors) ? $details : '<br>'.$errors;

        $params['Integrity.DiffUtility'] = SucuriScanIntegrity::diffUtility();

        return ($affected_files === 0)
        ? SucuriScanTemplate::getSection('integrity-correct', $params)
        : SucuriScanTemplate::getSection('integrity-incorrect', $params);
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

        $params['DiffUtility.Modal'] = SucuriScanTemplate::getModal('none', array(
            'Title' => __('DiffUtility', SUCURISCAN_TEXTDOMAIN),
            'Content' => '' /* empty */,
            'Identifier' => 'diff-utility',
            'Visibility' => 'hidden',
        ));

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
     */
    public static function ajaxIntegrityDiffUtility()
    {
        if (SucuriScanRequest::post('form_action') !== 'integrity_diff_utility') {
            return;
        }

        $version = SucuriScan::siteVersion();
        $filepath = SucuriScanRequest::post('filepath');

        ob_start();
        print(SucuriScanCommand::diffHTML($filepath, $version));
        $response = ob_get_clean();

        wp_send_json($response, 200);
    }

    /**
     * Retrieve a list of md5sum and last modification time of all the files in the
     * folder specified. This is a recursive function.
     *
     * @param string $dir The base path where the scanning will start.
     * @param bool $recursive Either TRUE or FALSE if the scan should be performed recursively.
     * @return array List of arrays containing the md5sum and last modification time of the files found.
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
     * @param string|int $version Valid version number of the WordPress project.
     * @return array|bool Associative array with these keys: modified, stable, removed, added.
     */
    private static function checkIntegrityIntegrity($version = 0)
    {
        $latest_hashes = SucuriScanAPI::getOfficialChecksums($version);
        $base_content_dir = defined('WP_CONTENT_DIR')
            ? basename(rtrim(WP_CONTENT_DIR, '/'))
            : '';

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
                $local = @md5_file($full_filepath);

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
     * @param string $path File path that will be compared.
     * @return bool True if the file should be ignored, false otherwise.
     */
    private static function ignoreIntegrityFilepath($path = '')
    {
        // List of files that will be ignored from the integrity checking.
        $ignore_files = array(
            '^sucuri-[0-9a-z\-]+\.php$',
            '^\S+-sucuri-db-dump-gzip-[0-9]{10}-[0-9a-z]{32}\.gz$',
            '^([^\/]*)\.(pdf|css|txt|jpg|gif|png|jpeg)$',
            '^wp-content\/(themes|plugins)\/.+',
            '^google[0-9a-z]{16}\.html$',
            '^pinterest-[0-9a-z]{5}\.html$',
            '\.ico$',
        );

        // Determine whether a file must be ignored from the integrity checks or not.
        foreach ($ignore_files as $ignore_pattern) {
            if (@preg_match('/'.$ignore_pattern.'/', $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Includes some irrelevant files into the integrity cache.
     */
    private static function ignoreIrrelevantFiles()
    {
        global $wp_local_package;

        if (SucuriScanOption::getOption(':integrity_startup') !== 'done') {
            /* ignore files no matter if they do not exist */
            self::ignoreIrrelevantFile('php.ini', false);
            self::ignoreIrrelevantFile('.htaccess', false);
            self::ignoreIrrelevantFile('.htpasswd', false);
            self::ignoreIrrelevantFile('.ftpquota', false);
            self::ignoreIrrelevantFile('wp-includes/.htaccess', false);
            self::ignoreIrrelevantFile('wp-admin/setup-config.php', false);
            self::ignoreIrrelevantFile('wp-config.php', false);
            self::ignoreIrrelevantFile('sitemap.xml', false);
            self::ignoreIrrelevantFile('sitemap.xml.gz', false);
            self::ignoreIrrelevantFile('readme.html', false);
            self::ignoreIrrelevantFile('error_log', false);

            /* ignore irrelevant files only if they exist */
            self::ignoreIrrelevantFile('wp-pass.php');
            self::ignoreIrrelevantFile('wp-rss.php');
            self::ignoreIrrelevantFile('wp-feed.php');
            self::ignoreIrrelevantFile('wp-register.php');
            self::ignoreIrrelevantFile('wp-atom.php');
            self::ignoreIrrelevantFile('wp-commentsrss2.php');
            self::ignoreIrrelevantFile('wp-rss2.php');
            self::ignoreIrrelevantFile('wp-rdf.php');
            self::ignoreIrrelevantFile('404.php');
            self::ignoreIrrelevantFile('503.php');
            self::ignoreIrrelevantFile('500.php');
            self::ignoreIrrelevantFile('500.shtml');
            self::ignoreIrrelevantFile('400.shtml');
            self::ignoreIrrelevantFile('401.shtml');
            self::ignoreIrrelevantFile('402.shtml');
            self::ignoreIrrelevantFile('403.shtml');
            self::ignoreIrrelevantFile('404.shtml');
            self::ignoreIrrelevantFile('405.shtml');
            self::ignoreIrrelevantFile('406.shtml');
            self::ignoreIrrelevantFile('407.shtml');
            self::ignoreIrrelevantFile('408.shtml');
            self::ignoreIrrelevantFile('409.shtml');
            self::ignoreIrrelevantFile('healthcheck.html');

            /**
             * Ignore i18n files.
             *
             * Sites with i18n have differences compared with the official English version
             * of the project, basically they have files with new variables specifying the
             * language that will be used in the admin panel, site options, and emails.
             */
            if (isset($wp_local_package) && $wp_local_package != 'en_US') {
                self::ignoreIrrelevantFile('wp-includes/version.php');
                self::ignoreIrrelevantFile('wp-config-sample.php');
            }

            SucuriScanOption::updateOption(':integrity_startup', 'done');
        }
    }

    /**
     * Forces the integrity checker to ignore a file.
     *
     * The first time the WordPress integrity checker it will include some files
     * into a storage file that will be used to skip these files during the next
     * scans. This is to reduce the amount of false/positives. The website owner
     * is free to add more files that are considered harmless, or stop ignoring
     * these files from a tool available in the scanner section in the settings
     * page.
     *
     * @param string $path Relative path to the file.
     * @param bool $checkExistence Stop if the file does not exists.
     * @return bool True if the file was ignored, false otherwise.
     */
    private static function ignoreIrrelevantFile($path = '', $checkExistence = true)
    {
        if ($checkExistence && !file_exists(ABSPATH . '/' . $path)) {
            return; /* skip if the file does not exists */
        }

        $cache = new SucuriScanCache('integrity');

        $cache_key = md5($path);
        $cache_value = array(
            'file_path' => $path,
            'file_status' => 'added',
            'ignored_at' => time(),
        );

        return $cache->add($cache_key, $cache_value);
    }
}
