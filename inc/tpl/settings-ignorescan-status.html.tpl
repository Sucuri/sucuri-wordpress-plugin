
<div class="postbox">
    <h3>Ignore Scanning</h3>

    <div class="inside">
        <p>
            If your project has too many directories and/or files it may cause the file
            system scanners to fail, you may want to increase the maximum execution time of
            the PHP scripts and the memory limit to allow the functions executed during the
            file system scans to finish successfully. If you do not want or do not have
            sufficient privileges to increase these values then you may want to skip some
            directories, this will force the plugin to ignore the files inside these
            folders.
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>Ignore Scanning is %%SUCURI.IgnoreScan.Status%%</span>
            <form action="%%SUCURI.URL.Settings%%#ignorescanning" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_ignore_scanning" value="%%SUCURI.IgnoreScan.SwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.IgnoreScan.SwitchCssClass%%">%%SUCURI.IgnoreScan.SwitchText%%</button>
            </form>
        </div>
    </div>
</div>
