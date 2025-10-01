<script type="text/javascript">
    jQuery(function ($) {
        const AJAX = '%%SUCURI.AjaxURL.Dashboard%%';
        const NONCE = '%%SUCURI.PageNonce%%';

        const msg = $('#sucuriscan-totp-msg');

        function showQR() {
            const qrWrapper = $('#sucuriscan-topt-qr');
            const qr = qrcode(0, 'M');

            qr.addData("%%SUCURI.topt_url%%");
            qr.make();

            qrWrapper.html(qr.createImgTag(6, 4));
        }

        function call(action, extra = {}) {
            return $.post(AJAX, $.extend({
                action: 'sucuriscan_ajax',
                form_action: action,
                sucuriscan_page_nonce: NONCE
            }, extra));
        }

        $(document).on('click', '#sucuriscan-totp-submit', function () {
            const codeValue = $('#sucuriscan-totp-code').val().replace(/\s+/g, '');
            const enforceAll = jQuery('input[name="sucuriscan_2fa_enforce_all"]').is(':checked') ? '1' : '0';

            msg.text('Verifying…').css('color', '');

            call('totp_verify', { topt_code: codeValue, topt_key: "%%SUCURI.totp_key%%", enforce_all: enforceAll }).done(function (resp) {
                if (resp && resp.data === 'activated' && !resp.error) {
                    msg.text('Activated').css('color', 'green');

                    $('#sucuriscan-topt-qr').hide();
                    $('#sucuriscan-totp-form').hide();
                    $('#two-factor-info-deactivated').hide();
                    $('#two-factor-info-activated').show();

                    window.location.reload();
                } else {
                    var err = (resp && resp.error) ? resp.error : 'Verification failed';
                    error(err);
                }
            }).fail(function () { error('Verify request failed'); });
        });

        function error(txt) { msg.text(txt).css('color', 'red'); }

        showQR();
    });

    jQuery('#two-factor-info-activated').hide();
</script>

<div id="two-factor-info">
    <div id="two-factor-info-deactivated">
        <p>Two factor authentication is not activated. Please scan the QR below, or input the key manually in your app,
            then click on verify to activate it.</p>
        <p>Please be mindful that activating this option will block other users from login until they activate
            two-factor.</p>
        <div id="sucuriscan-topt-qr" class="sucuriscan-topt-qr"></div>
        <p class="sucuriscan-2fa-secret"><code class="sucuriscan-2fa-secret-code">%%SUCURI.SecretManual%%</code></p>
    </div>
    <div id="two-factor-info-activated" style="display:none;">
        Two-Factor Authentication is enabled for your account.
    </div>
</div>

<div id="sucuriscan-totp-form">
    <form id="sucuriscan-totp-form" class="sucuriscan-2fa-form">
        <input name="sucuriscan_totp_code" type="text" id="sucuriscan-totp-code" maxlength="6" pattern="[0-9]{6}"
            placeholder="123 456" autocomplete="one-time-code" class="sucuriscan-2fa-code-input" />

        <p class="sucuriscan-2fa-enforce">
            <label>
                <input type="checkbox" name="sucuriscan_2fa_enforce_all" value="1" />
                {{Enforce two-factor for all users}}
            </label>
        </p>

        <button type="button" class="button button-primary" id="sucuriscan-totp-submit">{{Setup}}</button>
    </form>

    <span id="sucuriscan-totp-msg" class="sucuriscan-2fa-msg"></span>
</div>