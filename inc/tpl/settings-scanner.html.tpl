
<div id="poststuff">
    <div class="postbox sucuriscan-border sucuriscan-table-description">
        <h3>Scanner Settings</h3>

        <div class="inside">

            <p>
                There are multiple scanners implemented in the code of the plugin, all of them
                are enabled by default and you can deactivate them separately without affect the
                others. You may want to disable a scanner because your site has too many
                directories and/or files to scan, or because the maximum quantity of memory
                allowed for your project is not enough to execute one these functions. You can
                enable and disable any of the scanners anything you want.
            </p>

            <div class="sucuriscan-inline-alert-info">
                <p>
                    The <em>"Scanning Algorithm"</em> is the method that will be used to read the
                    diretories and files contained in the project when any of the file system
                    scanners are executed. The best option is SPL <em>(Standard PHP Library)</em>
                    but it is not available in all versions of the PHP interpreter. We recommend to
                    upgrade the version of PHP installed in the server, but if you can not do this
                    then choose a different algorithm.
                </p>
            </div>

        </div>
    </div>
</div>

<table class="wp-list-table widefat sucuriscan-table sucuriscan-settings sucuriscan-settings-scanner">
    <thead>
        <tr>
            <th>Option</th>
            <th>Value</th>
            <th>&nbsp;</th>
        </tr>
    </thead>

    <tbody>

        <tr>
            <td>Last background scan</td>
            <td><span class="sucuriscan-monospace">%%SUCURI.ScanningRuntimeHuman%%</span></td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Home%%" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <button type="submit" name="sucuriscan_force_scan" class="button-primary">Force scan</button>
                </form>
            </td>
        </tr>

        <tr class="alternate">
            <td>Scanning algorithm</td>
            <td>%%SUCURI.ScanningInterface%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <select name="sucuriscan_scan_interface">
                        %%%SUCURI.ScanningInterfaceOptions%%%
                    </select>
                    <button type="submit" class="button-primary">Change</button>
                </form>
            </td>
        </tr>

        <tr>
            <td>Scanning frequency</td>
            <td>%%SUCURI.ScanningFrequency%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <select name="sucuriscan_scan_frequency">
                        %%%SUCURI.ScanningFrequencyOptions%%%
                    </select>
                    <button type="submit" class="button-primary">Change</button>
                </form>
            </td>
        </tr>

        <tr class="alternate">
            <td>Main <abbr title="File System Scanner">FS Scanner</abbr></td>
            <td>%%SUCURI.FsScannerStatus%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_fs_scanner" value="%%SUCURI.FsScannerSwitchValue%%" />
                    <button type="submit" class="button-primary %%SUCURI.FsScannerSwitchCssClass%%">%%SUCURI.FsScannerSwitchText%%</button>
                </form>
            </td>
        </tr>

        <tr class="alternate">
            <td>FS Scanner, Core integrity checks</td>
            <td>%%SUCURI.ScanChecksumsStatus%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_scan_checksums" value="%%SUCURI.ScanChecksumsSwitchValue%%" />
                    <button type="submit" class="button-primary %%SUCURI.ScanChecksumsSwitchCssClass%%">%%SUCURI.ScanChecksumsSwitchText%%</button>
                </form>
            </td>
        </tr>

        <tr>
            <td>FS Scanner, Ignore scanning</td>
            <td>%%SUCURI.IgnoreScanningStatus%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_ignore_scanning" value="%%SUCURI.IgnoreScanningSwitchValue%%" />
                    <button type="submit" class="button-primary %%SUCURI.IgnoreScanningSwitchCssClass%%">%%SUCURI.IgnoreScanningSwitchText%%</button>
                </form>
            </td>
        </tr>

        <tr class="alternate">
            <td>FS Scanner, Error log files</td>
            <td>%%SUCURI.ScanErrorlogsStatus%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_scan_errorlogs" value="%%SUCURI.ScanErrorlogsSwitchValue%%" />
                    <button type="submit" class="button-primary %%SUCURI.ScanErrorlogsSwitchCssClass%%">%%SUCURI.ScanErrorlogsSwitchText%%</button>
                </form>
            </td>
        </tr>

        <tr>
            <td>SiteCheck scanner</td>
            <td>%%SUCURI.SiteCheckScannerStatus%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_sitecheck_scanner" value="%%SUCURI.SiteCheckScannerSwitchValue%%" />
                    <button type="submit" class="button-primary %%SUCURI.SiteCheckScannerSwitchCssClass%%">%%SUCURI.SiteCheckScannerSwitchText%%</button>
                </form>
            </td>
        </tr>

        <tr class="alternate">
            <td>SiteCheck counter</td>
            <td>%%SUCURI.SiteCheckCounter%% scans so far</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Scanner%%" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_malware_scan" value="1" />
                    <button type="submit" class="button-primary">Force scan</button>
                </form>
            </td>
        </tr>

        <tr>
            <td>Analyze error logs</td>
            <td>%%SUCURI.ParseErrorLogsStatus%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_parse_errorlogs" value="%%SUCURI.ParseErrorLogsSwitchValue%%" />
                    <button type="submit" class="button-primary %%SUCURI.ParseErrorLogsSwitchCssClass%%">%%SUCURI.ParseErrorLogsSwitchText%%</button>
                </form>
            </td>
        </tr>

        <tr class="alternate">
            <td>Error logs limit</td>
            <td>Analyze last %%SUCURI.ErrorLogsLimit%% logs</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="text" name="sucuriscan_errorlogs_limit" placeholder="Number of lines to analyze" class="input-text" />
                    <button type="submit" class="button-primary">Change</button>
                </form>
            </td>
        </tr>

        <tr>
            <td>Reset core integrity logs</td>
            <td><span class="sucuriscan-monospace">%%SUCURI.IntegrityLogLife%% of data</span></td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_reset_logfile" value="integrity" />
                    <button type="submit" class="button-primary">Reset logs</button>
                </form>
            </td>
        </tr>

        <tr class="alternate">
            <td>Reset last login logs</td>
            <td><span class="sucuriscan-monospace">%%SUCURI.LastLoginLogLife%% of data</span></td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_reset_logfile" value="lastlogins" />
                    <button type="submit" class="button-primary">Reset logs</button>
                </form>
            </td>
        </tr>

        <tr>
            <td>Reset failed login logs</td>
            <td><span class="sucuriscan-monospace">%%SUCURI.FailedLoginLogLife%% of data</span></td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_reset_logfile" value="failedlogins" />
                    <button type="submit" class="button-primary">Reset logs</button>
                </form>
            </td>
        </tr>

        <tr class="alternate">
            <td>Reset sitecheck logs</td>
            <td><span class="sucuriscan-monospace">%%SUCURI.SiteCheckLogLife%% of data</span></td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_reset_logfile" value="sitecheck" />
                    <button type="submit" class="button-primary">Reset logs</button>
                </form>
            </td>
        </tr>

    </tbody>
</table>
