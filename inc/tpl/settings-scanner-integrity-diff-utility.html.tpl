
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">WordPress Integrity Diff Utility</h3>

    <div class="inside">
        <p>If your server allows the execution of system commands, you can configure the plugin to use the <a href="https://en.wikipedia.org/wiki/Diff_utility" target="_blank" rel="noopener">Unix Diff Utility</a> to compare the actual content of the file installed in the website and the original file provided by WordPress. This will show the differences between both files and then you can act upon the information provided.</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.DiffUtility.StatusNum%%">
            <span>WordPress Integrity Diff Utility &mdash; %%SUCURI.DiffUtility.Status%%</span>

            <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_diff_utility" value="%%SUCURI.DiffUtility.SwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.DiffUtility.SwitchText%%</button>
            </form>
        </div>
    </div>
</div>
