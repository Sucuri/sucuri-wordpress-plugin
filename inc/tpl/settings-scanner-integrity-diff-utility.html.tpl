
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.DiffUtility@@</h3>

    <div class="inside">
        <p>@@SUCURI.DiffUtilityDescription@@</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.DiffUtility.StatusNum%%">
            <span>@@SUCURI.DiffUtility@@ &mdash; %%SUCURI.DiffUtility.Status%%</span>

            <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_diff_utility" value="%%SUCURI.DiffUtility.SwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.DiffUtility.SwitchText%%</button>
            </form>
        </div>
    </div>
</div>
