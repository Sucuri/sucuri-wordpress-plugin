<form action="%%SUCURI.URL.2FA%%" method="post">
    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
    <input type="hidden" name="sucuriscan_update_twofactor_bulk" value="1" />

    <div class="inside">
        <table class="wp-list-table widefat sucuriscan-table sucuriscan-table-fixed-layout sucuriscan-last-logins">
            <thead>
                <tr>
                    <th class="manage-column" style="width:32px;"><input type="checkbox" id="sucuri-2fa-select-all" />
                    </th>
                    <th class="manage-column">{{User}}</th>
                    <th class="manage-column">{{Email}}</th>
                    <th class="manage-column">{{Status}}</th>
                </tr>
            </thead>
            <tbody>
                %%%SUCURI.Rows%%%
            </tbody>
        </table>
    </div>

    <div class="sucuriscan-double-box sucuriscan-double-box-update sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.TwoFactor.Status%%"
        data-cy="sucuriscan_twofactor_bulk_control">
        <p>
            <strong>Two-Factor Authentication</strong> — <span>{{%%SUCURI.TwoFactor.StatusText%%}}</span>
            <br />
            <span>{{Select users above to activate, disable, or reset their two-factor authentication settings.}}</span>
        </p>
        <div>
            <select name="sucuriscan_twofactor_bulk_action" data-cy="sucuriscan_twofactor_bulk_dropdown"
                class="sucuriscan-twofactor-bulk-select">
                %%%SUCURI.BulkOptions%%%
            </select>
            <input type="submit" value="{{Apply}}" class="button button-primary"
                data-cy="sucuriscan_twofactor_bulk_submit_btn" />
        </div>
    </div>
</form>

<script>
    (function () {
        var selectAll = document.getElementById('sucuri-2fa-select-all');

        if (!selectAll) return;

        selectAll.addEventListener('change', function () {
            var boxes = document.querySelectorAll('input[name="sucuriscan_twofactor_users[]"]');

            for (var i = 0; i < boxes.length; i++) { boxes[i].checked = selectAll.checked; }
        });
    })();
</script>