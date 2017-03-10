
<div class="postbox">
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

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <div class="sucuriscan-input-group">
                <label>Scanning Algorithm</label>
                <select name="sucuriscan_scan_interface">
                    %%%SUCURI.ScanningInterfaceOptions%%%
                </select>
                <button type="submit" class="button-primary">Change</button>
            </div>

            <div class="sucuriscan-input-group">
                <label>Scanning Frequency</label>
                <select name="sucuriscan_scan_frequency">
                    %%%SUCURI.ScanningFrequencyOptions%%%
                </select>
                <button type="submit" class="button-primary">Change</button>
            </div>
        </form>
    </div>
</div>
