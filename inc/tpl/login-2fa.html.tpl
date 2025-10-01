<form name="sucuri-2fa-verify" id="loginform" action="%%SUCURI.ActionURL%%" method="post">
    %%%SUCURI.NonceField%%%
    <p style="display:block;">
        <label for="sucuriscan-totp-code">{{Authentication code}}<br />
            <input type="text" name="sucuriscan_totp_code" id="sucuriscan-totp-code" class="input" maxlength="6"
                pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" placeholder="123456"
                style="display:block;" />
        </label>
    </p>
    <p class="submit" style="display:block;">
        <input type="submit" name="wp-submit" id="sucuriscan-totp-submit" class="button button-primary button-large"
            value="{{Verify}}" style="display:block;" />
    </p>
</form>