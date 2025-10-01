<div class="sucuriscan-login-2fa-header"><strong>{{Sucuri Security}}</strong></div>

<div class="sucuriscan-login-2fa-qr-section">
    <div id="sucuriscan-totp-qr" class="sucuriscan-login-2fa-qr-box" style="text-align: center;"></div>
    <p class="sucuriscan-2fa-secret">{{Secret (manual entry):}} <code
            class="sucuriscan-2fa-secret-code">%%SUCURI.SecretManual%%</code></p>
</div>

<form name="sucuri-2fa-setup" id="loginform" action="%%SUCURI.ActionURL%%" method="post"
    class="sucuriscan-login-2fa-form">
    %%%SUCURI.NonceField%%%
    <p style="display: block;">
        <label for="sucuriscan-totp-code">{{Enter code}}<br />
            <input type="text" name="sucuriscan_totp_code" id="sucuriscan-totp-code" style="display: block;"
                class="input sucuriscan-login-2fa-code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                autocomplete="one-time-code" placeholder="123456" />
        </label>
    </p>
    <p class="submit" style="display: block;">
        <input type="submit" name="wp-submit" id="sucuriscan-totp-submit" style="display: block;"
            class="button button-primary button-large sucuriscan-login-2fa-submit" value="{{Activate and continue}}" />
    </p>
</form>

<script src="%%SUCURI.PluginURL%%/inc/js/qr.js"></script>
<script>
    (function () {
        try {
            var uri = '%%SUCURI.OtpauthURI%%';
            var element = document.getElementById('sucuriscan-totp-qr');

            if (element && window.qrcode) {
                var qr = qrcode(0, 'M');

                qr.addData(uri);
                qr.make();

                element.innerHTML = qr.createImgTag(6, 4);
            }
        } catch (e) { }
    })();
</script>