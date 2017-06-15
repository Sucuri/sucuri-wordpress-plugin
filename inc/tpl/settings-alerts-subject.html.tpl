
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.AlertsSubject@@</h3>

    <div class="inside">
        <p>@@SUCURI.AlertsSubjectInfo@@</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <ul class="sucuriscan-subject-formats">
                %%%SUCURI.Alerts.Subject%%%

                <li>
                    <label>
                        <input type="radio" name="sucuriscan_email_subject" value="custom" %%SUCURI.Alerts.CustomChecked%% />
                        <span>@@SUCURI.CustomFormat@@</span>
                        <input type="text" name="sucuriscan_custom_email_subject" value="%%SUCURI.Alerts.CustomValue%%" />
                    </label>
                </li>
            </ul>

            <div class="sucuriscan-recipient-form">
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </div>
        </form>
    </div>
</div>
