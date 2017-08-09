<?php

/**
 * Code related to the fileinfo.lib.php interface.
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
 * Class to process files and folders.
 *
 * Here are implemented the methods needed to open, scan, read, create files
 * and folders using the built-in PHP class SplFileInfo. The SplFileInfo class
 * offers a high-level object oriented interface to information for an individual
 * file.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2017 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanFileInfo extends SucuriScan
{
    /**
     * Whether the list of files that can be ignored from the filesystem scan will
     * be used to return the directory tree, this should be disabled when scanning a
     * directory without the need to filter the items in the list.
     *
     * @var bool
     */
    public $ignore_files;

    /**
     * Whether the list of folders that can be ignored from the filesystem scan will
     * be used to return the directory tree, this should be disabled when scanning a
     * path without the need to filter the items in the list.
     *
     * @var bool
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
     * @var bool
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
     * @var bool
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
        $this->skip_directories = true;
        $this->run_recursively = true;
    }

    /**
     * Checks if the file scanner is usable.
     *
     * @link https://www.php.net/manual/en/class.splfileobject.php
     *
     * @return bool True if PHP class "SplFileObject" is available.
     */
    public static function isSplAvailable()
    {
        return (bool) (
            class_exists('SplFileObject')
            && class_exists('FilesystemIterator')
            && class_exists('RecursiveIteratorIterator')
            && class_exists('RecursiveDirectoryIterator')
        );
    }

    /**
     * Ignores a file if the extension is not supported.
     *
     * Note: This is an approach that is intentionally naive.
     *
     * @param  string $path Path to the file.
     * @return True         if the file must be ignored.
     */
    private function ignoreFile($path)
    {
        return (bool) (
            $this->ignore_files
            && strpos($path, '.js') === false
            && strpos($path, '.css') === false
            && strpos($path, '.txt') === false
            && strpos($path, '.htm') === false
            && strpos($path, '.php') === false
            && strpos($path, '.ini') === false
            && strpos($path, '.htaccess') === false
        );
    }

    /**
     * Ignores a folder if the extension is not supported.
     *
     * Note: This is an approach that is intentionally naive.
     *
     * @param  string $path Path to the folder.
     * @return True         if the folder must be ignored.
     */
    private function ignoreFolder($path)
    {
        return (bool) ($this->ignore_directories && (
            strpos($path, '/.hg') !== false
            || strpos($path, '/.git') !== false
            || strpos($path, '/.svn') !== false
            || strpos($path, 'wp-content/backup') !== false
            || strpos($path, 'wp-content/cache') !== false
            || strpos($path, 'wp-content/uploads') !== false
            || strpos($path, 'wp-content/w3tc') !== false
        ));
    }

    /**
     * Ignores files specified by the admins.
     *
     * @param  string $path Path to the file or directory.
     * @return bool         True if the path has to be ignored.
     */
    private function isIgnoredPath($path)
    {
        $shouldBeIgnored = false;

        if (is_array($this->ignored_directories)
            && isset($this->ignored_directories['directories'])
            && !empty($this->ignored_directories['directories'])
        ) {
            foreach ($this->ignored_directories['directories'] as $ignored) {
                if (strpos($path, $ignored) !== false) {
                    $shouldBeIgnored = true;
                    break;
                }
            }
        }

        return $shouldBeIgnored;
    }

    /**
     * Reads a directory and retrieves all its files.
     *
     * @see http://www.php.net/manual/en/class.recursivedirectoryiterator.php
     * @see http://php.net/manual/en/class.recursivedirectoryiterator.php
     * @see http://php.net/manual/en/class.filesystemiterator.php
     * @see http://php.net/manual/en/class.directoryiterator.php
     * @see http://php.net/manual/en/class.splfileinfo.php
     *
     * @param  string $directory Where to execute the scanner.
     * @param  string $filterby  Either "file" or "directory".
     * @return array             List of files in the specified directory.
     */
    public function getDirectoryTree($directory = '', $filterby = 'file')
    {
        $files = array();

        if (is_dir($directory) && self::isSplAvailable()) {
            $objects = array();

            $this->ignored_directories = SucuriScanFSScanner::getIgnoredDirectories();

            // @codeCoverageIgnoreStart
            try {
                if ($this->run_recursively) {
                    $flags = FilesystemIterator::KEY_AS_PATHNAME;
                    $flags |= FilesystemIterator::CURRENT_AS_FILEINFO;
                    $flags |= FilesystemIterator::SKIP_DOTS;
                    $flags |= FilesystemIterator::UNIX_PATHS;
                    $objects = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($directory, $flags),
                        RecursiveIteratorIterator::SELF_FIRST,
                        RecursiveIteratorIterator::CATCH_GET_CHILD
                    );
                } else {
                    $objects = new DirectoryIterator($directory);
                }
            } catch (RuntimeException $exception) {
                /* ignore failure */
            }
            // @codeCoverageIgnoreEnd

            foreach ($objects as $fifo) {
                $filepath = $fifo->getRealPath();

                /* check files and directories */
                if ($this->isIgnoredPath($filepath)) {
                    continue;
                }

                /* check only files */
                if ($fifo->isFile()
                    && $filterby === 'file'
                    && !$this->ignoreFile($filepath)
                    && !$this->ignoreFolder($filepath)
                ) {
                    $files[] = $filepath;
                    continue;
                }

                /* check only directories */
                if ($fifo->isDir()
                    && $filterby === 'directory'
                    && !$this->ignoreFolder($filepath)
                ) {
                    $files[] = $filepath;
                    continue;
                }
            }

            sort($files);
        }

        return array_map(array('SucuriScan', 'fixPath'), $files);
    }

    /**
     * Retrieve a long text string with signatures of all the files contained
     * in the main and subdirectories of the folder specified, also the filesize
     * and md5sum of that file. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param  string $directory Where to execute the scanner.
     * @param  bool   $as_array  Return the file list as an array.
     * @return array|string|bool List of files in this project.
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
            return self::throwException('No files were found');
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
     * Retrieves a list of unique directory paths.
     *
     * @param  string $directory Directory path to scan.
     * @return array             A list of unique directory paths.
     */
    public function getDirectoriesOnly($directory = '')
    {
        $tree = $this->getDirectoryTree($directory, 'directory');

        return array_merge(array($directory), $tree);
    }

    /**
     * Deletes a directory recursively.
     *
     * @param  string $directory Path of the existing directory that will be removed.
     * @return bool              TRUE if all the files and folder inside the directory were removed.
     */
    public function removeDirectoryTree($directory = '')
    {
        $directory = realpath($directory);

        if (!is_dir($directory)) {
            return self::throwException('Directory does not exists');
        }

        if ($directory === ABSPATH . 'wp-content') {
            return self::throwException('Cannot delete content directory');
        }

        if ($directory === ABSPATH . 'wp-content/uploads') {
            return self::throwException('Cannot delete uploads directory');
        }

        /* force complete scan */
        $this->ignore_files = false;
        $this->skip_directories = false;
        $this->ignore_directories = false;

        /* delete all the regular files and symbolic links */
        $dir_tree = $this->getDirectoryTree($directory, 'file');
        if (is_array($dir_tree) && !empty($dir_tree)) {
            foreach ($dir_tree as $filename) {
                if (is_file($filename) || is_link($filename)) {
                    @unlink($filename);
                }
            }
        }

        /* delete directories starting from the deepest level */
        $dir_tree = $this->getDirectoryTree($directory, 'directory');
        if (is_array($dir_tree) && !empty($dir_tree)) {
            $dir_tree = array_unique($dir_tree);
            usort($dir_tree, array('SucuriScanFileInfo', 'sortByLength'));
            foreach ($dir_tree as $dir_path) {
                @rmdir($dir_path);
            }
        }

        @rmdir($directory); /* attempt to delete parent */

        /* check if we deleted all the files and sub-directories */
        return (bool) !($this->getDirectoryTree($directory));
    }

    /**
     * Evaluates the difference between the length of two strings.
     *
     * @param  string $a First string of characters that will be measured.
     * @param  string $b Second string of characters that will be measured.
     * @return int       The difference in length between the two strings.
     */
    public static function sortByLength($a, $b)
    {
        return strlen($b) - strlen($a);
    }

    /**
     * Returns the content of a file.
     *
     * If the file does not exists or is not readable the method will return
     * false. Make sure that you double check this with a condition using triple
     * equals in order to avoid ambiguous results when the file exists, is
     * readable, but is empty.
     *
     * @param  string $path Relative or absolute path of the file.
     * @return string       Content of the file, false if not accessible.
     */
    public static function fileContent($path = '')
    {
        return (string) (is_readable($path) ? @file_get_contents($path) : '');
    }

    /**
     * Returns the lines of a file as an array, it will automatically remove the
     * new line characters from the end of each line, and skip empty lines from
     * the list.
     *
     * @param  string $filepath Path to the file.
     * @return array            An array where each element is a line in the file.
     */
    public static function fileLines($filepath = '')
    {
        return @file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
