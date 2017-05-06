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
 * Here are implemented the methods needed to open, scan, read, create files
 * and folders using the built-in PHP class SplFileInfo. The SplFileInfo class
 * offers a high-level object oriented interface to information for an individual
 * file.
 */
class SucuriScanCommand extends SucuriScan
{
    /**
     * Check if the system was configured to allow the execution of certain shell
     * commands. In this specific case, we want to know if the generic exec method
     * is enabled or not to allow the execution of system programs.
     *
     * @return boolean True if the exec method is enabled, false otherwise.
     */
    private static function canExecuteCommands()
    {
        $disabled = self::iniGet('disable_functions', true);
        $methods = explode(',', $disabled);

        return !in_array('exec', $methods);
    }

    public static function exists($cmd)
    {
        if (!self::canExecuteCommands()) {
            self::throwException('Cannot execute external commands');
            return false;
        }

        $command = 'command -v ' . $cmd . ' &> /dev/null';
        $command = escapeshellcmd($command);

        @exec($command, $out, $err);

        if ($err !== 0) {
            self::throwException('Command ' . $cmd . ' does not exists');
            return false;
        }

        return true;
    }

    /**
     * Compare files line by line.
     *
     * @param  string $a File path of the original file.
     * @param  string $b File path of the modified file.
     * @return array     Line-by-line changes (if any).
     */
    public static function diff($a, $b)
    {
        if (!self::exists('diff')) {
            return;
        }

        $command = sprintf(
            'diff -u -- %s %s 2> /dev/null',
            escapeshellarg($a),
            escapeshellarg($b)
        );

        @exec($command, $out, $err);

        return $out;
    }

    public static function diffHTML($filepath, $version)
    {
        $tempfile = tempnam(sys_get_temp_dir(), SUCURISCAN . '-integrity-');
        $handle = @fopen($tempfile, 'w'); /* delete after the comparison */

        if (!$handle) {
            self::throwException('Temporary file cannot be created.');
            return ''; /* empty diff output */
        }

        $a = $tempfile; /* original file to compare */
        $b = ABSPATH . '/' . $filepath; /* modified */
        $content = SucuriScanAPI::getOriginalCoreFile($filepath, $version);
        @fwrite($handle, $content); /* create a copy of the original file */
        $output = self::diff($a, $b);
        @fclose($tempfile);
        @unlink($tempfile);

        if (!is_array($output) || empty($output)) {
            return ''; /* no differences found */
        }

        $response = '<ul class="' . SUCURISCAN . '-diff-content">';

        foreach ($output as $key => $line) {
            $number = $key + 1; /* line number */
            $cssclass = SUCURISCAN . '-diff-line';
            $cssclass .= "\x20" . SUCURISCAN . '-diff-line' . $number;

            if ($number === 1) {
                $line = str_replace($a, $b . ' (ORIGINAL)', $line);
                $cssclass .= "\x20" . SUCURISCAN . '-diff-header';
                $line = substr($line, 0, 4 + strlen($b) + 11);
            } elseif ($number === 2) {
                $line = str_replace($b, $b . ' (MODIFIED)', $line);
                $cssclass .= "\x20" . SUCURISCAN . '-diff-header';
                $line = substr($line, 0, 4 + strlen($b) + 11);
            } elseif ($number === 3) {
                $cssclass .= "\x20" . SUCURISCAN . '-diff-header';
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

        $response .= '</ul>';

        return $response;
    }
}
