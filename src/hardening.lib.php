<?php

/**
 * Project hardening library.
 *
 * In computing, hardening is usually the process of securing a system by
 * reducing its surface of vulnerability. A system has a larger vulnerability
 * surface the more functions it fulfills; in principle a single-function system
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
    private static function get_rules()
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
     * function is self-contained.
     *
     * @param  string  $directory Valid directory path where to place the access rules.
     * @return boolean            True if the rules are successfully added, false otherwise.
     */
    public static function harden_directory($directory = '')
    {
        if (file_exists($directory)
            && is_writable($directory)
            && is_dir($directory)
        ) {
            $fhandle = false;
            $target = self::htaccess($directory);
            $deny_rules = self::get_rules();

            if (file_exists($target)) {
                self::fix_previous_hardening($directory);
                $fhandle = @fopen($target, 'a');
            } else {
                $fhandle = @fopen($target, 'w');
            }

            if ($fhandle) {
                $rules_str = "\n" . implode("\n", $deny_rules) . "\n";
                $written = @fwrite($fhandle, $rules_str);
                @fclose($fhandle);

                return (bool) ($written !== false);
            }
        }

        return false;
    }

    /**
     * Deletes some rules from an existing access control file to allow access to
     * all files with certain extension in any mixed case. The file is truncated if
     * after the operation its size is equals to zero.
     *
     * @param  string  $directory Valid directory path where to access rules are.
     * @return boolean            True if the rules are successfully deleted, false otherwise.
     */
    public static function unharden_directory($directory = '')
    {
        if (self::is_hardened($directory)) {
            $deny_rules = self::get_rules();
            $fpath = self::htaccess($directory);
            $content = @file_get_contents($fpath);

            if ($content) {
                $rules_str = implode("\n", $deny_rules);
                $content = str_replace($rules_str, '', $content);
                $written = @file_put_contents($fpath, $content);
                $trimmed = trim($content);

                if (!filesize($fpath) || empty($trimmed)) {
                    @unlink($fpath);
                }

                return (bool) ($written !== false);
            }
        }

        return false;
    }

    /**
     * Remove the hardening applied in previous versions.
     *
     * @param  string  $directory Valid directory path.
     * @return boolean            True if the access control file was fixed.
     */
    private static function fix_previous_hardening($directory = '')
    {
        $fpath = self::htaccess($directory);
        $content = @file_get_contents($fpath);
        $rules = "<Files *.php>\ndeny from all\n</Files>";

        if ($content) {
            if (strpos($content, $rules) !== false) {
                $content = str_replace($rules, '', $content);
                $written = @file_put_contents($fpath, $content);

                return (bool) ($written !== false);
            }
        }

        return true;
    }

    /**
     * Check whether a directory is hardened or not.
     *
     * @param  string  $directory Valid directory path.
     * @return boolean            True if the directory is hardened, false otherwise.
     */
    public static function is_hardened($directory = '')
    {
        if (file_exists($directory) && is_dir($directory)) {
            $fpath = self::htaccess($directory);

            if (file_exists($fpath) && is_readable($fpath)) {
                $rules = self::get_rules();
                $rules_str = implode("\n", $rules);
                $content = @file_get_contents($fpath);

                if (strpos($content, $rules_str) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function htaccess($folder = '')
    {
        $folder = str_replace(ABSPATH, '', $folder);
        $bpath = rtrim(ABSPATH, DIRECTORY_SEPARATOR);
        $folder_path = $bpath . '/' . $folder;
        $htaccess = $folder_path . '/.htaccess';

        return $htaccess;
    }

    private static function whitelist_rule($file = '')
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

    public static function whitelist($file = '', $folder = '')
    {
        $htaccess = self::htaccess($folder);

        if (file_exists($htaccess)) {
            if (is_writable($htaccess)) {
                $rules = "\n" . self::whitelist_rule($file);
                @file_put_contents($htaccess, $rules, FILE_APPEND);
            } else {
                throw new Exception('Access control file is not writable');
            }
        } else {
            throw new Exception('Access control file does not exists');
        }
    }

    public static function dewhitelist($file = '', $folder = '')
    {
        $htaccess = self::htaccess($folder);

        if (file_exists($htaccess)
            && is_readable($htaccess)
            && is_writable($htaccess)
        ) {
            $content = file_get_contents($htaccess);
            $rules = self::whitelist_rule($file);
            $content = str_replace($rules, '', $content);
            $content = rtrim($content) . "\n";

            @file_put_contents($htaccess, $content);
        }
    }

    public static function get_whitelisted($folder = '')
    {
        $htaccess = self::htaccess($folder);

        if (file_exists($htaccess) && is_readable($htaccess)) {
            $content = file_get_contents($htaccess);

            if (@preg_match_all('/<Files (\S+)>/', $content, $matches)) {
                return $matches[1];
            }
        }

        return false;
    }
}
