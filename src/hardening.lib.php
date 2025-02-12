<?php

/**
 * Code related to the hardening.lib.php interface.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
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
 * Project hardening library.
 *
 * In computing, hardening is usually the process of securing a system by
 * reducing its surface of vulnerability. A system has a larger vulnerability
 * surface the more functions it fulfills; in principle a single-method system
 * is more secure than a multipurpose one. Reducing available vectors of attack
 * typically includes the removal of unnecessary software, unnecessary usernames
 * or logins and the disabling or removal of unnecessary services.
 *
 * There are various methods of hardening Unix and Linux systems. This may
 * involve, among other measures, applying a patch to the kernel such as Exec
 * Shield or PaX; closing open network ports; and setting up intrusion-detection
 * systems, firewalls and intrusion-prevention systems. There are also hardening
 * scripts and tools like Bastille Linux, JASS for Solaris systems and
 * Apache/PHP Hardener that can, for example, deactivate unneeded features in
 * configuration files or perform various other protective measures.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanHardening extends SucuriScan
{
    /*
     * This method is used to extract the folder and relative path (from one of the allowed folders)
     * given a full path and a list of allowed folders.
     *
     * @param string $fullPath Full path to the file.
     * @param array $allowed_folders List of allowed folders.
     *
     * @return array|false  Array with the root directory and relative path
     *                      or null if the file is not in an allowed folder.
     */
    public static function getFolderAndFilePath($path = '', $allowed_folders = array())
    {
        $best_match = false;

        foreach ($allowed_folders as $base_directory) {
            if (strpos($path, $base_directory . DIRECTORY_SEPARATOR) === 0) {
                if ($best_match === false || substr_count(
                    $base_directory,
                    DIRECTORY_SEPARATOR
                ) > substr_count($best_match['base_directory'], DIRECTORY_SEPARATOR)) {
                    $relative_path = str_replace($base_directory . DIRECTORY_SEPARATOR, '', $path);

                    $best_match = array(
                        'base_directory' => $base_directory,
                        'relative_path' => $relative_path
                    );
                }
            }
        }

        return $best_match;
    }

    /**
     * Returns a list of access control rules for the Apache web server that can be
     * used to deny and allow certain files to be accessed by certain network nodes.
     * Currently supports Apache 2.2 and 2.4 and denies access to all PHP files with
     * any mixed extension case.
     *
     * @return array List of access control rules.
     */
    private static function getRules()
    {
        return array(
            '<FilesMatch "\.(?i:php)$">',
            '  <IfModule !mod_authz_core.c>',
            '    Order allow,deny',
            '    Deny from all',
            '  </IfModule>',
            '  <IfModule mod_authz_core.c>',
            '    Require all denied',
            '  </IfModule>',
            '</FilesMatch>',
        );
    }

    /**
     * Adds some rules to an existing access control file (or creates it if does not
     * exists) to deny access to all files with certain extension in any mixed case.
     * The permissions to modify the file are checked before anything else, this
     * method is self-contained.
     *
     * @param string $directory Valid directory path where to place the access rules.
     * @return bool              True if the rules are successfully added, false otherwise.
     */
    public static function hardenDirectory($directory = '')
    {
        if (!is_dir($directory) || !is_writable($directory)) {
            return self::throwException(__('Directory is not usable', 'sucuri-scanner'));
        }

        if (SucuriScan::isNginxServer() || SucuriScan::isIISServer()) {
            return self::throwException(__('Access control file is not supported', 'sucuri-scanner'));
        }

        $fhandle = false;
        $target = self::htaccess($directory);

        if (file_exists($target)) {
            self::fixPreviousHardening($directory);
            $fhandle = @fopen($target, 'a');
        } else {
            $fhandle = @fopen($target, 'w');
        }

        if (!$fhandle) {
            return false;
        }

        $deny_rules = self::getRules();
        $rules_text = implode("\n", $deny_rules);
        $written = @fwrite($fhandle, "\n" . $rules_text . "\n");
        @fclose($fhandle);

        return (bool)($written !== false);
    }

    /**
     * Deletes some rules from an existing access control file to allow access to
     * all files with certain extension in any mixed case. The file is truncated if
     * after the operation its size is equals to zero.
     *
     * @param string $directory Valid directory path where to access rules are.
     * @return bool              True if the rules are successfully deleted, false otherwise.
     */
    public static function unhardenDirectory($directory = '')
    {
        if (!self::isHardened($directory)) {
            return self::throwException(__('Directory is not hardened', 'sucuri-scanner'));
        }

        $fpath = self::htaccess($directory);
        $content = SucuriScanFileInfo::fileContent($fpath);
        $deny_rules = self::getRules();
        $rules_text = implode("\n", $deny_rules);
        $content = str_replace($rules_text, '', $content);
        $written = @file_put_contents($fpath, $content);
        $trimmed = trim($content);

        if (!filesize($fpath) || empty($trimmed)) {
            @unlink($fpath);
        }

        return (bool)($written !== false);
    }

    /**
     * Remove the hardening applied in previous versions.
     *
     * @param string $directory Valid directory path.
     * @return bool              True if the access control file was fixed.
     */
    private static function fixPreviousHardening($directory = '')
    {
        $fpath = self::htaccess($directory);
        $content = SucuriScanFileInfo::fileContent($fpath);
        $rules = "<Files *.php>\ndeny from all\n</Files>";

        /* no previous hardening rules exist */
        if (strpos($content, $rules) === false) {
            return true;
        }

        $content = str_replace($rules, '', $content);
        $written = @file_put_contents($fpath, $content);

        return (bool)($written !== false);
    }

    /**
     * Check whether a directory is hardened or not.
     *
     * @param string $directory Valid directory path.
     * @return bool              True if the directory is hardened, false otherwise.
     */
    public static function isHardened($directory = '')
    {
        if (!is_dir($directory)) {
            return false;
        }

        $fpath = self::htaccess($directory);
        $content = SucuriScanFileInfo::fileContent($fpath);
        $deny_rules = self::getRules();
        $rules_text = implode("\n", $deny_rules);

        return (bool)(strpos($content, $rules_text) !== false);
    }

    /**
     * Returns the path to the Apache access control file.
     *
     * @param string $folder Folder where the htaccess file is supposed to be.
     * @return string         Path to the htaccess file in the specified folder.
     */
	public static function htaccess($folder = '')
	{
		if (!function_exists('get_home_path')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$home = get_home_path();

		if ($home !== '/' && strpos($folder, $home) === 0) {
			$folder = substr($folder, strlen($home));
		}

		$wordpress_path = rtrim($home, DIRECTORY_SEPARATOR);
		$folder = trim($folder, DIRECTORY_SEPARATOR);

		$path = $wordpress_path . '/' . $folder . '/.htaccess';

		return preg_replace('/\/+/', '/', $path);
	}

    /**
     * Generates Apache access control rules for a file.
     *
     * Assuming that the directory hosting the specified file is hardened, this
     * method will generate the necessary rules to allowlist such file so anyone
     * can send a direct request to it. The method will generate both the rules
     * for Apache 2.4.
     *
     * Please note that since v1.9.5 we allow relatives paths to be used in the
     * REQUEST_URI condition. This is useful when the file is located in a subfolder
     * of the folder being hardened.
     *
     * @param string $filepath File path to be ignored by the hardening.
     * @param string $folder Folder hosting the specified file.
     *
     * @return string       Access control rules to allowlist the file.
     */
    private static function allowlistRule($filepath = '', $folder = '')
    {
        $filepath = str_replace(array('<', '>', '..'), '', $filepath);
        $relative_folder = str_replace(ABSPATH, '/', $folder);
        $relative_folder = '/' . ltrim($relative_folder, '/');

        $path = sprintf(
            "<Files %s>\n"
            . "  <If \"%%{REQUEST_URI} =~ m#^%s/%s$#\">\n"
            . "    <IfModule !mod_authz_core.c>\n"
            . "      Allow from all\n"
            . "    </IfModule>\n"
            . "    <IfModule mod_authz_core.c>\n"
            . "      Require all granted\n"
            . "    </IfModule>\n"
            . "  </If>\n"
            . "</Files>\n",
            basename($filepath),
            rtrim($relative_folder, '/'),
            ltrim($filepath, '/')
        );

        return $path;
    }

    /**
     * Generates Apache access control rules for a file (legacy).
     *
     * Assuming that the directory hosting the specified file is hardened, this
     * method will generate the necessary rules to allowlist such file so anyone
     * can send a direct request to it. The method will generate both the rules
     * for Apache 2.4 and a compatibility conditional for older versions.
     *
     * @param string $filepath File to be ignored by the hardening.
     * @return string       Access control rules to allowlist the file.
     */
    private static function allowlistRuleLegacy($filepath = '', $folder = '')
    {
        $filepath = str_replace(array('<', '>', '..'), '', $filepath);

        return sprintf(
            "<Files %s>\n"
            . "  <IfModule !mod_authz_core.c>\n"
            . "    Allow from all\n"
            . "  </IfModule>\n"
            . "  <IfModule mod_authz_core.c>\n"
            . "    Require all granted\n"
            . "  </IfModule>\n"
            . "</Files>",
            basename($filepath)
        );
    }

    /**
     * Adds file in the specified folder to the allowlist.
     *
     * If the website owner has applied the hardening to the folder where the
     * specified file is located, all the requests sent directly to the file
     * will be blocked by the web server using its access control module. An
     * admin can ignore this hardening in one or more files if direct access to
     * it is required, as is the case with some 3rd-party plugins and themes.
     *
     * @param string $filepath File to be ignored by the hardening.
     * @param string $folder Folder hosting the specified file.
     * @return bool           True if the file has been added to the allowlist, false otherwise.
     */
    public static function allow($filepath = '', $folder = '')
    {
        if (SucuriScan::isNginxServer() || SucuriScan::isIISServer()) {
            throw new Exception(__('Access control file is not supported', 'sucuri-scanner'));
        }

        $htaccess = self::htaccess($folder);

        if (!file_exists($htaccess)) {
            throw new Exception(__('Access control file does not exists', 'sucuri-scanner'));
        }

        if (!is_writable($htaccess)) {
            throw new Exception(__('Access control file is not writable', 'sucuri-scanner'));
        }

        $rules = self::allowlistRule($filepath, $folder);
        $content = SucuriScanFileInfo::fileContent($htaccess);

        if (strpos($content, $rules) !== false) {
            throw new Exception(__('File is already in the allowlist', 'sucuri-scanner'));
        }

        return (bool)@file_put_contents(
            $htaccess,
            "\n" . self::allowlistRule($filepath, $folder),
            FILE_APPEND
        );
    }

    /**
     * Blocks a file in the specified folder.
     *
     * If the website owner has applied the hardening to the folder where the
     * specified file is located, all the requests sent directly to the file
     * will be blocked by the web server using its access control module. If an
     * admin has added a file to the allowlist in this folder because a 3rd-party plugin or
     * theme required it, they can decide to remove this file from the allowlist using this
     * method which is executed by one of the tools in the settings page.
     *
     * @param string $filepath File to stop ignoring from the hardening.
     * @param string $folder Folder hosting the specified file.
     * @param bool $is_legacy Whether to use the legacy allowlist rule.
     *
     * @return bool          True if the file has been removed from the allowlist, false otherwise.
     */
    public static function removeFromAllowlist($filepath = '', $folder = '', $is_legacy = false)
    {
        $rules = self::allowlistRule($filepath, $folder);
        $htaccess = self::htaccess($folder);
        $content = SucuriScanFileInfo::fileContent($htaccess);

        if (!$content || !is_writable($htaccess)) {
            return self::throwException(__('Cannot remove file from the allowlist; no permissions.', 'sucuri-scanner'));
        }

        if ($is_legacy) {
            $rules = self::allowlistRuleLegacy($filepath, $folder);
        }

        $content = str_replace($rules, '', $content);
        $content = rtrim($content) . "\n";

        return (bool)@file_put_contents($htaccess, $content);
    }

    /*
     * This method is used to get the list of files in the allowlist in a folder.
     * This returns the list of files with both the old and new patterns.
     *
     * @param string $content Content of the .htaccess file.
     *
     * @return array List of files in the allowlist.
     */
    private static function getFiles($content = '')
    {
        preg_match_all('/<Files (\S+)>/', $content, $matches);

        return isset($matches[1]) ? $matches[1] : array();
    }

    /*
     * This method is used to get the list of all files with new pattern in the .htaccess file
     * introduced in version 1.9.5. This pattern is based on the relative URL from the root directory
     * that the user selected.
     *
     * @param string $content Content of the .htaccess file.
     *
     * @return array List of files with new pattern in the .htaccess file.
     */
    private static function getFilesWithNewPattern($content = '', $folder = '')
    {
        preg_match_all('/m#\^(\S+\/(\S+))\$\#/', $content, $new_matches, PREG_SET_ORDER, 0);

        $files_with_new_pattern = array();

        if (empty($new_matches)) {
            return array();
        }

        foreach ($new_matches as $match) {
            $uri = $match[0];

            if (empty($uri)) {
                continue;
            }

            $cleaned_uri = str_replace(array('m#^', '$#'), '', $uri);

            $relative_folder_uri = str_replace(ABSPATH, '', $folder);
            $relative_path = str_replace($relative_folder_uri, '', $cleaned_uri);
            $relative_path = ltrim($relative_path, '/');

            $files_with_new_pattern[] = array(
                'file' => basename($cleaned_uri),
                'relative_path' => $relative_path,
            );
        }

        return $files_with_new_pattern;
    }

    /* This method is used to build the allowlist from the list of files and files with new pattern.
     *
     * @param array $files List of files in the allowlist.
     * @param array $filesWithNewPattern List of files with new pattern in the .htaccess file.
     *
     * @return array List of files in the allowlist.
     */
    private static function buildAllowlist($files = array(), $files_with_new_pattern = array())
    {
        if (empty($files)) {
            return array();
        }

        $allowlist = array();
        $processed_files = array();

        foreach ($files as $file) {
            $wildcard_pattern = true;
            $relative_path = '';

            // If this file is found in $files_with_new_pattern, it should not be marked as a wildcard pattern
            foreach ($files_with_new_pattern as $file_new_pattern) {
                if ($file_new_pattern['file'] === $file) {
                    if (isset($processed_files[$file])) {
                        continue;
                    }

                    $wildcard_pattern = false;
                    $relative_path = $file_new_pattern['relative_path'];
                    break;
                }
            }

            $processed_files[$file] = true;

            $allowlist[] = array(
                'file' => $file,
                'relative_path' => $relative_path ?: $file,
                'wildcard_pattern' => $wildcard_pattern,
            );
        }

        return $allowlist;
    }

    /**
     * Returns a list of files in the allowlist in folder.
     *
     * @param string $folder Directory to scan for files in the allowlist.
     * @return array         List of files in the allowlist. Each file is an array with the keys:
     *                       - file: The name of the file.
     *                       - relative_path: The relative path of the file.
     *                       - wildcard_pattern: Whether the file is a wildcard pattern.
     */
    public static function getAllowlist($folder = '')
    {
        $htaccess = self::htaccess($folder);
        $content = SucuriScanFileInfo::fileContent($htaccess);

        $files = self::getFiles($content);
        $files_with_new_pattern = self::getFilesWithNewPattern($content, $folder);

        $allowlist = self::buildAllowlist($files, $files_with_new_pattern);

        return $allowlist;
    }
}
