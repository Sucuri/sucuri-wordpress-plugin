
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Scanner Settings</h3>

    <div class="inside">
        <p>
            The plugin scans your entire website looking for changes which are
            later reported via the API in the audit logs page. This scanner runs
            twice-daily by default but you can change the frequency to meet your
            own requirements. Notice that scanning your project files too frequently
            will affect the performance of your website. Be sure to have enough
            server resources before changing this option. The memory limit and
            maximum execution time are two of the PHP options that your server
            will set to stop your website from consuming too much resources.
        </p>

        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.NoSPL.Visibility%%">
            <p>
                The scanner uses the <a href="http://php.net/manual/en/class.splfileobject.php"
                target="_blank">PHP SPL library</a> and the <a target="_blank"
                href="http://php.net/manual/en/class.filesystemiterator.php">
                Filesystem Iterator</a> class to scan the directory tree where
                your website is located in the server. This library is only
                available on PHP 5 >= 5.3.0 &mdash; OR &mdash; PHP 7; if you have
                an older version of PHP the plugin will not work as expected.
                Please ask your hosting provider to advice you on this matter.
            </p>
        </div>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <fieldset class="sucuriscan-clearfix">
                <label>Scanning Frequency</label>
                <select name="sucuriscan_scan_frequency">
                    %%%SUCURI.ScanningFrequencyOptions%%%
                </select>
                <button type="submit" class="button button-primary">Change</button>
            </fieldset>
        </form>
    </div>
</div>
