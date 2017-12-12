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
 * @copyright  2010-2017 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanHardening extends SucuriScan
{
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
     * @param  string $directory Valid directory path where to place the access rules.
     * @return bool              True if the rules are successfully added, false otherwise.
     */
    public static function hardenDirectory($directory = '')
    {
        if (!is_dir($directory) || !is_writable($directory)) {
            return self::throwException('Directory is not usable');
        }

        $fhandle = false;
        $target = self::htaccess($directory);

        if (file_exists($target)) {
            self::fixPreviousHardening($directory);
            $fhandle = @fopen($target, 'a');
        } else {
            $fhandle = @fopen($target, 'w');
        }

        $deny_rules = self::getRules();
        $rules_text = implode("\n", $deny_rules);
        $written = @fwrite($fhandle, "\n" . $rules_text . "\n");
        @fclose($fhandle);

        return (bool) ($written !== false);
    }

    /**
     * Deletes some rules from an existing access control file to allow access to
     * all files with certain extension in any mixed case. The file is truncated if
     * after the operation its size is equals to zero.
     *
     * @param  string $directory Valid directory path where to access rules are.
     * @return bool              True if the rules are successfully deleted, false otherwise.
     */
    public static function unhardenDirectory($directory = '')
    {
        if (!self::isHardened($directory)) {
            return self::throwException('Directory is not hardened');
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

        return (bool) ($written !== false);
    }

    /**
     * Remove the hardening applied in previous versions.
     *
     * @param  string $directory Valid directory path.
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

        return (bool) ($written !== false);
    }

    /**
     * Check whether a directory is hardened or not.
     *
     * @param  string $directory Valid directory path.
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

        return (bool) (strpos($content, $rules_text) !== false);
    }

    /**
     * Returns the path to the Apache access control file.
     *
     * @param  string $folder Folder where the htaccess file is supposed to be.
     * @return string         Path to the htaccess file in the specified folder.
     */
    private static function htaccess($folder = '')
    {
        $folder = str_replace(ABSPATH, '', $folder);
        $bpath = rtrim(ABSPATH, DIRECTORY_SEPARATOR);

        return $bpath . '/' . $folder . '/.htaccess';
    }

    /**
     * Generates Apache access control rules for a file.
     *
     * Assumming that the directory hosting the specified file is hardened, this
     * method will generate the necessary rules to whitelist such file so anyone
     * can send a direct request to it. The method will generate both the rules
     * for Apache 2.4 and a compatibility conditional for older versions.
     *
     * @param  string $file File to be ignored by the hardening.
     * @return string       Access control rules to whitelist the file.
     */
    private static function whitelistRule($file = '')
    {
        $file = str_replace('/', '', $file);
        $file = str_replace('<', '', $file);
        $file = str_replace('>', '', $file);

        return sprintf(
            "<Files %s>\n"
            . "  <IfModule !mod_authz_core.c>\n"
            . "    Allow from all\n"
            . "  </IfModule>\n"
            . "  <IfModule mod_authz_core.c>\n"
            . "    Require all granted\n"
            . "  </IfModule>\n"
            . "</Files>\n",
            $file
        );
    }

    /**
     * Whitelists a file in the specified folder.
     *
     * If the website owner has applied the hardening to the folder where the
     * specified file is located, all the requests sent directly to the file
     * will be blocked by the web server using its access control module. An
     * admin can ignore this hardening in one or more files if direct access to
     * it is required, as is the case with some 3rd-party plugins and themes.
     *
     * @param  string $file   File to be ignored by the hardening.
     * @param  string $folder Folder hosting the specified file.
     * @return bool           True if the file has been whitelisted, false otherwise.
     */
    public static function whitelist($file = '', $folder = '')
    {
        $htaccess = self::htaccess($folder);

        if (!file_exists($htaccess)) {
            throw new Exception('Access control file does not exists');
        }

        if (!is_writable($htaccess)) {
            throw new Exception('Access control file is not writable');
        }

        return (bool) @file_put_contents(
            $htaccess,
            "\n" . self::whitelistRule($file),
            FILE_APPEND
        );
    }

    /**
     * Dewhitelists a file in the specified folder.
     *
     * If the website owner has applied the hardening to the folder where the
     * specified file is located, all the requests sent directly to the file
     * will be blocked by the web server using its access control module. If an
     * admin has whitelisted a file in this folder because a 3rd-party plugin or
     * theme required it, they can decide to revert the whitelisting using this
     * method which is executed by one of the tools in the settings page.
     *
     * @param  string $file   File to stop ignoring from the hardening.
     * @param  string $folder Folder hosting the specified file.
     * @return bool           True if the file has been dewhitelisted, false otherwise.
     */
    public static function dewhitelist($file = '', $folder = '')
    {
        $htaccess = self::htaccess($folder);
        $content = SucuriScanFileInfo::fileContent($htaccess);

        if (!$content || !is_writable($htaccess)) {
            return self::throwException('Cannot dewhitelist file; no permissions.');
        }

        $rules = self::whitelistRule($file);
        $content = str_replace($rules, '', $content);
        $content = rtrim($content) . "\n";

        return (bool) @file_put_contents($htaccess, $content);
    }

    /**
     * Returns a list of whitelisted files in folder.
     *
     * @param  string $folder Directory to scan for whitelisted files.
     * @return array          List of whitelisted files, false on failure.
     */
    public static function getWhitelisted($folder = '')
    {
        $htaccess = self::htaccess($folder);
        $content = SucuriScanFileInfo::fileContent($htaccess);
        @preg_match_all('/<Files (\S+)>/', $content, $matches);

        return $matches[1];
    }
}
