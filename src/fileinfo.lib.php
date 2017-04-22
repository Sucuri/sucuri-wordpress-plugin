<?php

/**
 * Code related to the fileinfo.lib.php interface.
 *
 * @package Sucuri Security
 * @subpackage fileinfo.lib.php
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
 * Class to process files and folders.
 *
 * Here are implemented the methods needed to open, scan, read, create files
 * and folders using the built-in PHP class SplFileInfo. The SplFileInfo class
 * offers a high-level object oriented interface to information for an individual
 * file.
 */
class SucuriScanFileInfo extends SucuriScan
{
    /**
     * Whether the list of files that can be ignored from the filesystem scan will
     * be used to return the directory tree, this should be disabled when scanning a
     * directory without the need to filter the items in the list.
     *
     * @var boolean
     */
    public $ignore_files;

    /**
     * Whether the list of folders that can be ignored from the filesystem scan will
     * be used to return the directory tree, this should be disabled when scanning a
     * path without the need to filter the items in the list.
     *
     * @var boolean
     */
    public $ignore_directories;

    /**
     * A list of ignored directory paths, these folders will be skipped during the
     * execution of the file system scans, and any sub-directory or files inside
     * these paths will be ignored too.
     *
     * @see SucuriScanFSScanner.getIgnoredDirectories()
     * @var array
     */
    private $ignored_directories;

    /**
     * Whether the filesystem scanner should run recursively or not.
     *
     * @var boolean
     */
    public $run_recursively;

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
    public $skip_directories;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->ignore_files = true;
        $this->ignore_directories = true;
        $this->ignored_directories = array();
        $this->run_recursively = true;
        $this->skip_directories = true;
    }

    /**
     * Retrieve a long text string with signatures of all the files contained
     * in the main and subdirectories of the folder specified, also the filesize
     * and md5sum of that file. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param string $directory Where to execute the scanner.
     * @param bool $as_array Return the file list as an array.
     * @return array|string List of files in this project.
     */
    public function getDirectoryTreeMd5($directory = '', $as_array = false)
    {
        $signatures = '';
        $abspath = self::fixPath(ABSPATH);
        $files = $this->getDirectoryTree($directory);

        if ($as_array) {
            $signatures = array();
        }

        if (!$files) {
            self::throwException('No files were found');
            return $signatures;
        }

        sort($files); /* sort file list alphabetically */

        foreach ($files as $filepath) {
            /* silence errors when file is not readable */
            $file_checksum = @md5_file($filepath);
            $filesize = @filesize($filepath);

            if ($as_array) {
                $basename = str_replace($abspath . '/', '', $filepath);
                $signatures[$basename] = array(
                    'filepath' => $filepath,
                    'checksum' => $file_checksum,
                    'filesize' => $filesize,
                    'created_at' => @filectime($filepath),
                    'modified_at' => @filemtime($filepath),
                );
            } else {
                $filepath = str_replace($abspath, $abspath . '/', $filepath);
                $signatures .= $file_checksum . $filesize . "\x20" . $filepath . "\n";
            }
        }

        return $signatures;
    }

    /**
     * Retrieve a list with all the files contained in the main and subdirectories
     * of the folder specified. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param string $directory Where to execute the scanner.
     * @return array|bool List of files in the project.
     */
    public function getDirectoryTree($directory = '')
    {
        if (!file_exists($directory) && !is_dir($directory)) {
            return false;
        }

        $tree = array();

        $this->ignored_directories = SucuriScanFSScanner::getIgnoredDirectories();

        $tree = $this->getDirectoryTreeWithSpl($directory);

        if (!is_array($tree) || empty($tree)) {
            return false;
        }

        sort($tree); /* sort directory tree alphabetically */

        return array_map(array('SucuriScan', 'fixPath'), $tree);
    }

    /**
     * Check whether the built-in class SplFileObject is available in the system
     * or not, it is required to have PHP >= 5.1.0. The SplFileObject class offers
     * an object oriented interface for a file.
     *
     * @link https://www.php.net/manual/en/class.splfileobject.php
     *
     * @return bool Whether the PHP class "SplFileObject" is available or not.
     */
    public static function isSplAvailable()
    {
        return (bool) (class_exists('SplFileObject') && class_exists('FilesystemIterator'));
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
     * @param string $directory Where to execute the scanner.
     * @return array|bool List of files in the project.
     */
    private function getDirectoryTreeWithSpl($directory = '')
    {
        $files = array();
        $filepath = @realpath($directory);
        $objects = array();

        // Exception for directory name must not be empty.
        if (!$filepath) {
            self::throwException('Directory path is invalid');
            return false;
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

            if ($this->ignoreFolderPath('', $filename)) {
                /* skip irrelevant files from the scan */
                continue;
            }

            if ($this->skip_directories && $fileinfo->isDir()) {
                /* skip irrelevant directories from the scan */
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
     * Skip some specific directories and file paths from the filesystem scan.
     *
     * @param string $directory Directory where the scanner is located at the moment.
     * @param string $filename Name of the folder or file being scanned at the moment.
     * @return bool Either TRUE or FALSE representing that the scan should ignore this folder or not.
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

            /* silence regexp match if the file is not readable */
            if (@preg_match($pattern, $filepath)) {
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
     * @param string $filename Name of the folder or file being scanned at the moment.
     * @return bool Either TRUE or FALSE representing that the scan should ignore this filename or not.
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
     * @param array $dir_tree A list of files under a directory.
     * @return array A list of unique directory paths.
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
     * Remove a directory recursively.
     *
     * @param string $directory Path of the existing directory that will be removed.
     * @return bool TRUE if all the files and folder inside the directory were removed.
     */
    public function removeDirectoryTree($directory = '')
    {
        /* delete all the regular files and symbolic links */
        if ($dir_tree = $this->getDirectoryTree($directory)) {
            foreach ($dir_tree as $filename) {
                if (is_file($filename) || is_link($filename)) {
                    @unlink($filename);
                }
            }
        }

        if (!function_exists('sucuriscanStrlenDiff')) {
            /**
             * Evaluates the difference between the length of two strings.
             *
             * @param string $a First string of characters that will be measured.
             * @param string $b Second string of characters that will be measured.
             * @return int The difference in length between the two strings.
             */
            function sucuriscanStrlenDiff($a = '', $b = '')
            {
                return strlen($b) - strlen($a);
            }
        }

        /* delete all directories starting from the deepest level */
        if ($dir_tree = $this->getDirectoryTree($directory)) {
            $dir_tree = array_unique($dir_tree);
            usort($dir_tree, 'sucuriscanStrlenDiff');
            foreach ($dir_tree as $dir_path) {
                @rmdir($dir_path);
            }
        }

        @rmdir($directory); /* attempt to delete parent */

        /* check if we deleted all the files and sub-directories */
        return (bool) !($this->getDirectoryTree($directory));
    }

    /**
     * Returns the content of a file.
     *
     * If the file does not exists or is not readable the method will return
     * false. Make sure that you double check this with a condition using triple
     * equals in order to avoid ambiguous results when the file exists, is
     * readable, but is empty.
     *
     * @param string $fpath Relative or absolute path of the file.
     * @return string Content of the file, false if not accessible.
     */
    public static function fileContent($fpath = '')
    {
        if (!file_exists($fpath) || !is_readable($fpath)) {
            return ''; /* empty content for compatibility */
        }

        return @file_get_contents($fpath);
    }

    /**
     * Return the lines of a file as an array, it will automatically remove the new
     * line characters from the end of each line, and skip empty lines from the
     * list.
     *
     * @param string $filepath Path to the file.
     * @return array An array where each element is a line in the file.
     */
    public static function fileLines($filepath = '')
    {
        return @file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Tells whether the filename is a directory, symbolic link, or file.
     *
     * @param string $path Path to the file.
     * @return string Type of resource: dir, link, file.
     */
    public static function getResourceType($path = '')
    {
        if (is_dir($path)) {
            return 'dir';
        }

        if (is_link($path)) {
            return 'link';
        }

        if (is_file($path)) {
            return 'file';
        }

        return 'unknown';
    }
}
