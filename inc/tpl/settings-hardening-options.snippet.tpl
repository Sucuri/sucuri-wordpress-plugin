
<div class="sucuriscan-hardening-option sucuriscan-hstatus sucuriscan-status-%%SUCURI.Hardening.Status%%">
    <span>%%SUCURI.Hardening.Title%%</span>

    <div>
        <p>%%SUCURI.Hardening.Description%%</p>

        <form action="%%SUCURI.URL.Hardening%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <input type="submit" name="%%SUCURI.Hardening.FieldName%%" value="%%SUCURI.Hardening.FieldText%%" %%SUCURI.Hardening.FieldAttrs%% class="button button-primary" />
        </form>
    </div>
</div>
