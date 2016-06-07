
<div class="sucuriscan-panelstuff">
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

    </tbody>
</table>

<div class="sucuriscan-panelstuff sucuriscan-general-scanner">
    %%%SUCURI.Settings.CoreFilesStatus%%%

    %%%SUCURI.Settings.CoreFilesLanguage%%%

    %%%SUCURI.Settings.CoreFilesCache%%%

    %%%SUCURI.Settings.SiteCheckStatus%%%

    %%%SUCURI.Settings.SiteCheckCache%%%

    %%%SUCURI.Settings.SiteCheckTimeout%%%
</div>
