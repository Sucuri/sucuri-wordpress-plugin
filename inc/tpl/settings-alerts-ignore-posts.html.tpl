
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.PostTypeAlerts@@</h3>

    <div class="inside">
        <p class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.IgnoreRules.MessageVisibility%%">
            @@SUCURI.PostTypeAlertsDisabled@@
        </p>

        <p>@@SUCURI.PostTypeAlertsInfo@@</p>

        <p>@@SUCURI.PostTypeAlertsInvisible@@</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_ignorerule_action" value="add">

            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.PostTypeAlertsStop@@:</label>
                <input type="text" name="sucuriscan_ignorerule" placeholder="e.g. unique_post_type_id" />
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>

        <hr>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-settings-ignorerules">
            <thead>
                <tr>
                    <th>@@SUCURI.IgnoredAt@@</th>
                    <th>@@SUCURI.Ignored@@</th>
                    <th>@@SUCURI.PostType@@</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.IgnoreRules.PostTypes%%%
            </tbody>
        </table>
    </div>
</div>
