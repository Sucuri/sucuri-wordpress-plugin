<form name="sucuri-2fa-verify" id="loginform" action="%%SUCURI.ActionURL%%" method="post">
    %%%SUCURI.NonceField%%%
    <p style="display:block;">
        <label for="sucuriscan-totp-code">{{Authentication code}}<br />
            <input type="text" name="sucuriscan_totp_code" id="sucuriscan-totp-code" class="input" maxlength="9"
                inputmode="text" autocomplete="one-time-code" placeholder="123456"
                style="display:block;" />
        </label>
    </p>
    <p style="display:block;">
        {{Enter the 6-digit code from your authenticator app or an 8-character backup code.}}
    </p>
    <p class="submit" style="display:block;">
        <input type="submit" name="wp-submit" id="sucuriscan-totp-submit" class="button button-primary button-large"
            value="{{Verify}}" style="display:block;" />
    </p>
</form>
