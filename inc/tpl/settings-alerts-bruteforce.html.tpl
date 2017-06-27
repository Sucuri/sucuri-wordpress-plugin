
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.PasswordAttack@@</h3>

    <div class="inside">
        <p>@@SUCURI.PasswordAttackInfo@@</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.PasswordAttackAfter@@:</label>
                <select name="sucuriscan_maximum_failed_logins">
                    %%%SUCURI.Alerts.BruteForce%%%
                </select>
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>
    </div>
</div>
