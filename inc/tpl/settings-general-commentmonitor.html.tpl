
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.CommentMonitor@@</h3>

    <div class="inside">
        <p>@@SUCURI.CommentMonitorInfo@@</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>@@SUCURI.CommentMonitor@@ &mdash; %%SUCURI.CommentMonitorStatus%%</span>

            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_comment_monitor" value="%%SUCURI.CommentMonitorSwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.CommentMonitorSwitchText%%</button>
            </form>
        </div>
    </div>
</div>
