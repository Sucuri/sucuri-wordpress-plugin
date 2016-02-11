
<div class="postbox">
    <h3>Core Integrity Checks</h3>

    <div class="inside">
        <p>
            This tool allows you to scan the core directories searching for added, modified,
            and deleted files, there is no need to touch any of these core files so any
            inconsistency notified after the scan must be considered as a high severity
            warning as it may be a sign that a malicious person got access to the website
            and was able to add malicious code, modify files to inject malware, and/or delete
            important parts of the project.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                Note that this tool does not checks for malicious code, for that you have to
                use the <a href="%%SUCURI.URL.Scanner%%">Malware Scanner</a> instead.
            </p>
        </div>

        <p>
            This tool detects changes in the project core files using a list of checksums
            that WordPress provides via their official API service, if a file in the website
            has a different checksum then the plugin displays a warning saying that the file
            was modified. If the file is listed in the data provided by WordPress but does
            not exists in the website then the plugin displays a warning saying that the
            file was deleted. If the plugin finds a file in one of the core directories that
            is not listed in the checksums then it displays a warning saying that the file
            was added.
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.Integrity.StatusNum%%">
            <span>Core Integrity Checks are %%SUCURI.Integrity.Status%%</span>
            <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_scan_checksums" value="%%SUCURI.Integrity.SwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.Integrity.SwitchCssClass%%">%%SUCURI.Integrity.SwitchText%%</button>
            </form>
        </div>
    </div>
</div>
