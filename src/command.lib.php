<?php

/**
 * Code related to the command.lib.php interface.
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
class SucuriScanCommand extends SucuriScan
{
    /**
     * Check if the system was configured to allow the execution of certain shell
     * commands. In this specific case, we want to know if the generic exec method
     * is enabled or not to allow the execution of system programs.
     *
     * @return bool True if the exec method is enabled, false otherwise.
     */
    private static function canExecuteCommands()
    {
        $disabled = self::iniGet('disable_functions', true);
        $methods = explode(',', $disabled);

        return !in_array('exec', $methods);
    }

    /**
     * Checks if an external command exists or not.
     *
     * @param  string $cmd Name of the external command.
     * @return bool        True if the command exists, false otherwise.
     */
    public static function exists($cmd)
    {
        $err = 255;
        $out = array();

        if (self::canExecuteCommands()) {
            $command = sprintf('command -v %s 1>/dev/null', escapeshellarg($cmd));
            @exec($command, $out, $err); /* ignore output and capture errors */
        }

        if ($err !== 0) {
            return self::throwException('Command ' . $cmd . ' does not exists');
        }

        return true;
    }

    /**
     * Compares two files line by line.
     *
     * @param  string $a File path of the original file.
     * @param  string $b File path of the modified file.
     * @return array     Line-by-line changes (if any).
     */
    public static function diff($a, $b)
    {
        $out = array();

        if (self::exists('diff')) {
            @exec(
                sprintf(
                    'diff -u -- %s %s 2> /dev/null',
                    escapeshellarg($a),
                    escapeshellarg($b)
                ),
                $out,
                $err
            );
        }

        return $out;
    }

    /**
     * Generates the HTML code with the diff output.
     *
     * The method takes the relative path of a core WordPress file, then tries
     * to download a fresh copy of such file from the official WordPress API. If
     * the download succeeds the method will write the content of this file into
     * a temporary resource. Then it will use the Unix diff utility to find all
     * the differences between the original code and the one present in the site.
     *
     * If there are differences, the method will write the HTML code to report
     * them in the dashboard. Some basic CSS classes will be attached to some of
     * the elements in the code to facilitate the styling of the diff report.
     *
     * @param  string $filepath Relative path to the core WordPress file.
     * @return string|bool      HTML code with the diff report, false on failure.
     */
    public static function diffHTML($filepath)
    {
        $checksums = SucuriScanAPI::getOfficialChecksums();

        if (!$checksums) {
            return SucuriScanInterface::error('Unsupported WordPress version.');
        }

        if (!array_key_exists($filepath, $checksums)) {
            return SucuriScanInterface::error('Not an official WordPress file.');
        }

        $output = ''; /* initialize empty with no differences */
        $a = tempnam(sys_get_temp_dir(), SUCURISCAN . '-integrity-');
        $b = tempnam(sys_get_temp_dir(), SUCURISCAN . '-integrity-');
        $handle = @fopen($a, 'w');

        if ($handle) {
            @fwrite($handle, SucuriScanAPI::getOriginalCoreFile($filepath));
            @copy(ABSPATH . '/' . $filepath, $b);
            $output = self::diff($a, $b);
            @fclose($handle);
        }

        @unlink($a); /* delete original file */
        @unlink($b); /* delete modified file */

        if (!is_array($output) || empty($output)) {
            return SucuriScanInterface::error('There are no differences.');
        }

        $response = "<ul class='" . SUCURISCAN . "-diff-content'>\n";

        foreach ($output as $key => $line) {
            $number = $key + 1; /* line number */
            $cssclass = SUCURISCAN . '-diff-line';
            $cssclass .= "\x20" . SUCURISCAN . '-diff-line' . $number;

            if ($number === 1) {
                $line = sprintf('--- %s (ORIGINAL)', $filepath);
                $cssclass .= "\x20" . SUCURISCAN . '-diff-header';
            } elseif ($number === 2) {
                $line = sprintf('+++ %s (MODIFIED)', $filepath);
                $cssclass .= "\x20" . SUCURISCAN . '-diff-header';
            } elseif ($number === 3) {
                $cssclass .= "\x20" . SUCURISCAN . '-diff-header';
            } elseif ($line === '') {
                /* do not touch empty lines */
            } elseif ($line[0] === '-') {
                $cssclass .= "\x20" . SUCURISCAN . '-diff-minus';
            } elseif ($line[0] === '+') {
                $cssclass .= "\x20" . SUCURISCAN . '-diff-plus';
            }

            $response .= sprintf(
                "<li class='%s'>%s</li>\n",
                $cssclass, /* include external CSS styling */
                SucuriScan::escape($line) /* clean user input */
            );
        }

        $response .= "</ul>\n";

        return $response;
    }
}
