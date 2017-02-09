<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Class to process files and folders.
 *
 * Here are implemented the functions needed to open, scan, read, create files
 * and folders using the built-in PHP class SplFileInfo. The SplFileInfo class
 * offers a high-level object oriented interface to information for an individual
 * file.
 */
class SucuriScanFileInfo extends SucuriScan
{

    /**
     * Define the interface that will be used to execute the file system scans, the
     * available options are SPL, OpenDir, and Glob (all in lowercase). This can be
     * configured from the settings page.
     *
     * @var string
     */
    public $scan_interface = 'spl';

    /**
     * Whether the list of files that can be ignored from the filesystem scan will
     * be used to return the directory tree, this should be disabled when scanning a
     * directory without the need to filter the items in the list.
     *
     * @var boolean
     */
    public $ignore_files = true;

    /**
     * Whether the list of folders that can be ignored from the filesystem scan will
     * be used to return the directory tree, this should be disabled when scanning a
     * path without the need to filter the items in the list.
     *
     * @var boolean
     */
    public $ignore_directories = true;

    /**
     * A list of ignored directory paths, these folders will be skipped during the
     * execution of the file system scans, and any sub-directory or files inside
     * these paths will be ignored too.
     *
     * @see SucuriScanFSScanner.getIgnoredDirectories()
     * @var array
     */
    private $ignored_directories = array();

    /**
     * Whether the filesystem scanner should run recursively or not.
     *
     * @var boolean
     */
    public $run_recursively = true;

    /**
     * Whether the directory paths must be skipped or not.
     *
     * This is useful to retrieve the full list of resources inside a parent
     * directory, one case where this option can be set as True is when a folder is
     * required to be deleted recursively, considering that by default the folders
     * are ignored and that a folder may be empty some times there could be issues
     * because the deletion will not reach these resources.
     *
     * @var boolean
     */
    public $skip_directories = true;

    /**
     * Class constructor.
     */
    public function __construct()
    {
    }

    /**
     * Retrieve a long text string with signatures of all the files contained
     * in the main and subdirectories of the folder specified, also the filesize
     * and md5sum of that file. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param  string  $directory Parent directory where the filesystem scan will start.
     * @param  boolean $as_array  Whether the result of the operation will be returned as an array or string.
     * @return array              List of files in the main and subdirectories of the folder specified.
     */
    public function getDirectoryTreeMd5($directory = '', $as_array = false)
    {
        $project_signatures = '';
        $abspath = self::fixPath(ABSPATH);
        $files = $this->getDirectoryTree($directory);

        if ($as_array) {
            $project_signatures = array();
        }

        if ($files) {
            sort($files);

            foreach ($files as $filepath) {
                $file_checksum = @md5_file($filepath);
                $filesize = @filesize($filepath);

                if ($as_array) {
                    $basename = str_replace($abspath . '/', '', $filepath);
                    $project_signatures[ $basename ] = array(
                        'filepath' => $filepath,
                        'checksum' => $file_checksum,
                        'filesize' => $filesize,
                        'created_at' => @filectime($filepath),
                        'modified_at' => @filemtime($filepath),
                    );
                } else {
                    $filepath = str_replace($abspath, $abspath . '/', $filepath);
                    $project_signatures .= sprintf(
                        "%s%s%s%s\n",
                        $file_checksum,
                        $filesize,
                        chr(32),
                        $filepath
                    );
                }
            }
        }

        return $project_signatures;
    }

    /**
     * Retrieve a list with all the files contained in the main and subdirectories
     * of the folder specified. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param  string $directory Parent directory where the filesystem scan will start.
     * @return array             List of files in the main and subdirectories of the folder specified.
     */
    public function getDirectoryTree($directory = '')
    {
        if (file_exists($directory) && is_dir($directory)) {
            $tree = array();

            // Check whether the ignore scanning feature is enabled or not.
            if (SucuriScanFSScanner::willIgnoreScanning()) {
                $this->ignored_directories = SucuriScanFSScanner::getIgnoredDirectories();
            }

            switch ($this->scan_interface) {
                case 'spl':
                    if ($this->isSplAvailable()) {
                        $tree = $this->getDirectoryTreeWithSpl($directory);
                    } else {
                        $this->scan_interface = 'opendir';
                        SucuriScanOption::updateOption(':scan_interface', $this->scan_interface);
                        $tree = $this->getDirectoryTree($directory);
                    }
                    break;

                case 'glob':
                    $tree = $this->getDirectoryTreeWithGlob($directory);
                    break;

                case 'opendir':
                    $tree = $this->getDirectoryTreeWithOpendir($directory);
                    break;

                default:
                    $this->scan_interface = 'spl';
                    $tree = $this->getDirectoryTree($directory);
                    break;
            }

            if (is_array($tree) && !empty($tree)) {
                sort($tree); /* Sort in alphabetic order */

                return array_map(array('SucuriScan', 'fixPath'), $tree);
            }
        }

        return false;
    }

    /**
     * Find a file under the directory tree specified.
     *
     * @param  string $filename  Name of the folder or file being scanned at the moment.
     * @param  string $directory Directory where the scanner is located at the moment.
     * @return array             List of file paths where the file was found.
     */
    public function findFile($filename = '', $directory = null)
    {
        $file_paths = array();

        if (is_null($directory)
            && defined('ABSPATH')
        ) {
            $directory = ABSPATH;
        }

        if (is_dir($directory)) {
            $dir_tree = $this->getDirectoryTree($directory);

            foreach ($dir_tree as $filepath) {
                /**
                 * Checking the whole file path will result in a list of false
                 * positive data as the parent directories might contain part of
                 * the word that is being searched.
                 *
                 * @var string
                 */
                $basename = basename($filepath);

                if (stripos($basename, $filename) !== false) {
                    $file_paths[] = $filepath;
                }
            }
        }

        return $file_paths;
    }

    /**
     * Check whether the built-in class SplFileObject is available in the system
     * or not, it is required to have PHP >= 5.1.0. The SplFileObject class offers
     * an object oriented interface for a file.
     *
     * @link https://www.php.net/manual/en/class.splfileobject.php
     *
     * @return boolean Whether the PHP class "SplFileObject" is available or not.
     */
    public static function isSplAvailable()
    {
        return (bool) class_exists('SplFileObject');
    }

    /**
     * Retrieve a list with all the files contained in the main and subdirectories
     * of the folder specified. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @link https://www.php.net/manual/en/class.recursivedirectoryiterator.php
     * @see  RecursiveDirectoryIterator extends FilesystemIterator
     * @see  FilesystemIterator         extends DirectoryIterator
     * @see  DirectoryIterator          extends SplFileInfo
     * @see  SplFileInfo
     *
     * @param  string $directory Parent directory where the filesystem scan will start.
     * @return array             List of files in the main and subdirectories of the folder specified.
     */
    private function getDirectoryTreeWithSpl($directory = '')
    {
        $files = array();
        $filepath = @realpath($directory);
        $objects = array();

        // Exception for directory name must not be empty.
        if ($filepath === false) {
            return $files;
        }

        if (!class_exists('FilesystemIterator')) {
            $this->scan_interface = 'opendir';
            SucuriScanOption::updateOption(':scan_interface', $this->scan_interface);
            $alternative_tree = $this->getDirectoryTree($directory);

            return $alternative_tree;
        }

        try {
            if ($this->run_recursively) {
                $flags = FilesystemIterator::KEY_AS_PATHNAME
                    | FilesystemIterator::CURRENT_AS_FILEINFO
                    | FilesystemIterator::SKIP_DOTS
                    | FilesystemIterator::UNIX_PATHS;
                $objects = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($filepath, $flags),
                    RecursiveIteratorIterator::SELF_FIRST,
                    RecursiveIteratorIterator::CATCH_GET_CHILD
                );
            } else {
                $objects = new DirectoryIterator($filepath);
            }
        } catch (RuntimeException $exception) {
            SucuriScanEvent::reportException($exception);
        }

        foreach ($objects as $filepath => $fileinfo) {
            $filename = $fileinfo->getFilename();

            if ($this->ignoreFolderPath(null, $filename)
                || (
                    $this->skip_directories === true
                    && $fileinfo->isDir()
                )
            ) {
                continue;
            }

            if ($this->run_recursively) {
                $directory = dirname($filepath);
            } else {
                $directory = $fileinfo->getPath();
                $filepath = $directory . '/' . $filename;
            }

            if ($this->ignoreFolderPath($directory, $filename)
                || $this->ignoreFilePath($filename)
            ) {
                continue;
            }

            $files[] = $filepath;
        }

        return $files;
    }

    /**
     * Retrieve a list with all the files contained in the main and subdirectories
     * of the folder specified. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param  string $directory Parent directory where the filesystem scan will start.
     * @return array             List of files in the main and subdirectories of the folder specified.
     */
    private function getDirectoryTreeWithGlob($directory = '')
    {
        $files = array();
        $directory_pattern = sprintf('%s/*', rtrim($directory, '/'));
        $files_found = @glob($directory_pattern);

        if (is_array($files_found)) {
            foreach ($files_found as $filepath) {
                $filepath = @realpath($filepath);
                $directory = dirname($filepath);
                $filepath_parts = explode('/', $filepath);
                $filename = array_pop($filepath_parts);

                if (is_dir($filepath)) {
                    if ($this->ignoreFolderPath($directory, $filename)) {
                        continue;
                    }

                    if ($this->run_recursively) {
                        $sub_files = $this->getDirectoryTreeWithGlob($filepath);

                        if ($sub_files) {
                            $files = array_merge($files, $sub_files);
                        }
                    }
                } elseif ($this->ignoreFilePath($filename)) {
                    continue;
                } else {
                    $files[] = $filepath;
                }
            }
        }

        return $files;
    }

    /**
     * Retrieve a list with all the files contained in the main and subdirectories
     * of the folder specified. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param  string $directory Parent directory where the filesystem scan will start.
     * @return array             List of files in the main and subdirectories of the folder specified.
     */
    private function getDirectoryTreeWithOpendir($directory = '')
    {
        $files = array();
        $dh = @opendir($directory);

        if (!$dh) {
            return false;
        }

        while (($filename = readdir($dh)) !== false) {
            $filepath = @realpath($directory . '/' . $filename);

            if ($filepath === false) {
                continue;
            } elseif (is_dir($filepath)) {
                if ($this->ignoreFolderPath($directory, $filename)) {
                    continue;
                }

                if ($this->run_recursively) {
                    $sub_files = $this->getDirectoryTreeWithOpendir($filepath);

                    if ($sub_files) {
                        $files = array_merge($files, $sub_files);
                    }
                }
            } else {
                if ($this->ignoreFilePath($filename)) {
                    continue;
                }
                $files[] = $filepath;
            }
        }

        closedir($dh);
        return $files;
    }

    /**
     * Skip some specific directories and file paths from the filesystem scan.
     *
     * @param  string  $directory Directory where the scanner is located at the moment.
     * @param  string  $filename  Name of the folder or file being scanned at the moment.
     * @return boolean            Either TRUE or FALSE representing that the scan should ignore this folder or not.
     */
    private function ignoreFolderPath($directory = '', $filename = '')
    {
        // Ignoring current and parent folders.
        if ($filename == '.' || $filename == '..') {
            return true;
        }

        if ($this->ignore_directories) {
            // Ignore directories based on a common regular expression.
            $filepath = @realpath($directory . '/' . $filename);
            $pattern = '/\/wp-content\/(uploads|cache|backup|w3tc)/';

            if (preg_match($pattern, $filepath)) {
                return true;
            }

            // Ignore directories specified by the administrator.
            if (!empty($this->ignored_directories)) {
                foreach ($this->ignored_directories['directories'] as $ignored_dir) {
                    if (strpos($directory, $ignored_dir) !== false
                        || strpos($filepath, $ignored_dir) !== false
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Skip some specific files from the filesystem scan.
     *
     * @param  string  $filename Name of the folder or file being scanned at the moment.
     * @return boolean           Either TRUE or FALSE representing that the scan should ignore this filename or not.
     */
    private function ignoreFilePath($filename = '')
    {
        if (!$this->ignore_files) {
            return false;
        }

        // Ignoring backup files from our clean ups.
        if (strpos($filename, '_sucuribackup.') !== false) {
            return true;
        }

        // Ignore files specified by the administrator.
        if (!empty($this->ignored_directories)) {
            foreach ($this->ignored_directories['directories'] as $ignored_dir) {
                if (strpos($ignored_dir, $filename) !== false) {
                    return true;
                }
            }
        }

        // Any file maching one of these rules WILL NOT be ignored.
        if (( strpos($filename, '.php') !== false) ||
            ( strpos($filename, '.htm') !== false) ||
            ( strpos($filename, '.js') !== false) ||
            ( strcmp($filename, '.htaccess') == 0     ) ||
            ( strcmp($filename, 'php.ini') == 0     )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve a list of unique directory paths.
     *
     * @param  array $dir_tree A list of files under a directory.
     * @return array           A list of unique directory paths.
     */
    public function getDiretoriesOnly($dir_tree = array())
    {
        $dirs = array();

        if (is_string($dir_tree)) {
            $dir_tree = $this->getDirectoryTree($dir_tree);
        }

        if (is_array($dir_tree) && !empty($dir_tree)) {
            foreach ($dir_tree as $filepath) {
                $dir_path = dirname($filepath);

                if (!in_array($dir_path, $dirs)) {
                    if (is_array($this->ignored_directories)
                        && array_key_exists('directories', $this->ignored_directories)
                        && is_array($this->ignored_directories['directories'])
                        && in_array($dir_path, $this->ignored_directories['directories'])
                    ) {
                        continue;
                    }

                    $dirs[] = $dir_path;
                }
            }
        }

        return $dirs;
    }

    /**
     * Returns a list of lines matching the specified pattern in all the files found
     * in the specified directory, each entry in the list contains the relative path
     * of the file and the number of the line where the pattern was found, as well
     * as the string around the pattern in that line.
     *
     * @param  string $directory Directory where the scanner is located at the moment.
     * @param  string $pattern   Text that will be searched inside each file.
     * @return array             Associative list with the file path and line number of the match.
     */
    public function grepPattern($directory = '', $pattern = '')
    {
        $dir_tree = $this->getDirectoryTree($directory);
        $pattern = '/.*' . str_replace('/', '\/', $pattern) . '.*/';
        $results = array();

        if (class_exists('SplFileObject')
            && class_exists('RegexIterator')
            && SucuriScan::isValidPattern($pattern)
        ) {
            foreach ($dir_tree as $file_path) {
                try {
                    $fobject = new SplFileObject($file_path);
                    $fstream = new RegexIterator($fobject, $pattern, RegexIterator::MATCH);

                    foreach ($fstream as $key => $ltext) {
                        $lnumber = ( $key + 1 );
                        $ltext = str_replace("\n", '', $ltext);
                        $fpath = str_replace($directory, '', $file_path);
                        $loutput = sprintf('%s:%d:%s', $fpath, $lnumber, $ltext);
                        $results[] = array(
                            'file_path' => $file_path,
                            'relative_path' => $fpath,
                            'line_number' => $lnumber,
                            'line_text' => $ltext,
                            'output' => $loutput,
                        );
                    }
                } catch (RuntimeException $exception) {
                    SucuriScanEvent::reportException($exception);
                }
            }
        }

        return $results;
    }

    /**
     * Remove a directory recursively.
     *
     * @param  string  $directory Path of the existing directory that will be removed.
     * @return boolean            TRUE if all the files and folder inside the directory were removed.
     */
    public function removeDirectoryTree($directory = '')
    {
        $dir_tree = $this->getDirectoryTree($directory);

        if ($dir_tree) {
            $dirs_only = array();

            // Include the parent directory as the first entry.
            $dirs_only[] = $directory;

            /**
             * Delete all the files and symbolic links recursively and append the
             * directories in a list to delete them later when we are sure that all files
             * were successfully deleted, this is because PHP does not allows to delete non-
             * empty folders.
             */
            foreach ($dir_tree as $filepath) {
                if (is_dir($filepath)) {
                    $dirs_only[] = $filepath;
                } else {
                    @unlink($filepath);
                }
            }

            if (!function_exists('sucuriscanStrlenDiff')) {
                /**
                 * Evaluates the difference between the length of two strings.
                 *
                 * @param  string  $a First string of characters that will be measured.
                 * @param  string  $b Second string of characters that will be measured.
                 * @return integer    The difference in length between the two strings.
                 */
                function sucuriscanStrlenDiff($a = '', $b = '')
                {
                    return strlen($b) - strlen($a);
                }
            }

            // Sort the directories by deep level in ascendant order.
            $dirs_only = array_unique($dirs_only);
            usort($dirs_only, 'sucuriscanStrlenDiff');

            // Delete all the directories starting from the deepest level.
            foreach ($dirs_only as $dir_path) {
                @rmdir($dir_path);
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the content of a file.
     *
     * If the file does not exists or is not readable the function will return
     * false. Make sure that you double check this with a condition using triple
     * equals in order to avoid ambiguous results when the file exists, is
     * readable, but is empty.
     *
     * @param  string $fpath Relative or absolute path of the file.
     * @return string        Content of the file, false if not accessible.
     */
    public static function fileContent($fpath = '')
    {
        if (file_exists($fpath) && is_readable($fpath)) {
            return file_get_contents($fpath);
        }

        return false;
    }

    /**
     * Return the lines of a file as an array, it will automatically remove the new
     * line characters from the end of each line, and skip empty lines from the
     * list.
     *
     * @param  string $filepath Path to the file.
     * @return array            An array where each element is a line in the file.
     */
    public static function fileLines($filepath = '')
    {
        return @file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Function to emulate the UNIX tail function by displaying the last X number of
     * lines in a file. Useful for large files, such as logs, when you want to
     * process lines in PHP or write lines to a database.
     *
     * @param  string  $file_path Path to the file.
     * @param  integer $lines     Number of lines to retrieve from the end of the file.
     * @param  boolean $adaptive  Whether the buffer will adapt to a specific number of bytes or not.
     * @return string             Text contained at the end of the file.
     */
    public static function tailFile($file_path = '', $lines = 1, $adaptive = true)
    {
        $file = @fopen($file_path, 'rb');
        $limit = $lines;

        if ($file) {
            fseek($file, -1, SEEK_END);

            if ($adaptive && $lines < 2) {
                $buffer = 64;
            } elseif ($adaptive && $lines < 10) {
                $buffer = 512;
            } else {
                $buffer = 4096;
            }

            if (fread($file, 1) != "\n") {
                $lines -= 1;
            }

            $output = '';
            $chunk = '';

            while (ftell($file) > 0 && $lines >= 0) {
                $seek = min(ftell($file), $buffer);
                fseek($file, -$seek, SEEK_CUR);
                $chunk = fread($file, $seek);
                $output = $chunk . $output;
                fseek($file, -mb_strlen($chunk, '8bit'), SEEK_CUR);
                $lines -= substr_count($chunk, "\n");
            }

            fclose($file);

            $lines_arr = explode("\n", $output);
            $lines_count = count($lines_arr);
            $result = array_slice($lines_arr, ($lines_count - $limit));

            return $result;
        }

        return false;
    }

    /**
     * Gets inode change time of file.
     *
     * @param  string  $file_path Path to the file.
     * @return integer            Time the file was last changed.
     */
    public static function creationTime($file_path = '')
    {
        if (file_exists($file_path)) {
            clearstatcache($file_path);
            return filectime($file_path);
        }

        return 0;
    }

    /**
     * Gets file modification time.
     *
     * @param  string  $file_path Path to the file.
     * @return integer            Time the file was last modified.
     */
    public static function modificationTime($file_path = '')
    {
        if (file_exists($file_path)) {
            clearstatcache($file_path);
            return filemtime($file_path);
        }

        return 0;
    }

    /**
     * Tells whether the filename is a directory, symbolic link, or file.
     *
     * @param  string $path Path to the file.
     * @return string       Type of resource: dir, link, file.
     */
    public static function getResourceType($path = '')
    {
        if (is_dir($path)) {
            return 'dir';
        } elseif (is_link($path)) {
            return 'link';
        } elseif (is_file($path)) {
            return 'file';
        } else {
            return 'unknown';
        }
    }
}
