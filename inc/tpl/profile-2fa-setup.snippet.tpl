<div class="sucuriscan-profile-2fa-setup">
    <p>Two-Factor Authentication is not activated for your account. Scan the QR code below or enter the key manually in
        your authenticator app, then enter the 6-digit code to enable it.</p>

    <div class="sucuriscan-2fa-setup-row">
        <div id="sucuriscan-topt-qr" class="sucuriscan-topt-qr"></div>

        <div class="sucuriscan-2fa-setup-form" role="form" aria-labelledby="sucuriscan-2fa-setup-label">
            <p id="sucuriscan-2fa-setup-label">
                <strong>Secret:</strong>
                <code>%%SUCURI.totp_key%%</code>
            </p>

            <label for="sucuriscan-totp-code"><strong>Verification code</strong></label><br />
            <input type="text" id="sucuriscan-totp-code" name="sucuriscan_totp_code" maxlength="6" inputmode="numeric"
                pattern="[0-9]{6}" placeholder="123456" autocomplete="one-time-code"
                class="regular-text sucuriscan-2fa-code-input" aria-describedby="sucuriscan-2fa-enable-msg" />

            <input type="hidden" name="sucuri_2fa_secret" value="%%SUCURI.totp_key%%" />

            <div class="sucuriscan-2fa-action-row">
                <button type="button" class="button button-primary" id="sucuri-2fa-enable-btn">Verify & Enable</button>
                <span id="sucuri-2fa-enable-msg" class="sucuriscan-2fa-enable-msg" role="status"
                    aria-live="polite"></span>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(function ($) {
        const $root = $('.sucuriscan-profile-2fa-setup');

        if (!$root.length) return;

        const ajaxUrl = '%%SUCURI.ajax_url%%';
        const ajaxNonce = '%%SUCURI.ajax_nonce%%';
        const userId = '%%SUCURI.user_id%%';
        const uri = '%%SUCURI.topt_url%%';

        if (uri && typeof qrcode === 'function') {
            const $qr = $('#sucuriscan-topt-qr');
            if ($qr.length) {
                try {
                    const qr = qrcode(0, 'M');

                    qr.addData(uri);
                    qr.make();

                    $qr.html(qr.createImgTag(6, 4));
                } catch (e) { /* noop */ }
            }
        }

        const $btn = $('#sucuri-2fa-enable-btn');
        const $msg = $('#sucuri-2fa-enable-msg');

        const setMessage = (text, type) => {
            if (!$msg.length) return;

            $msg.text(text || '').removeClass('sucuriscan-text-error sucuriscan-text-success');

            if (type === 'error') $msg.addClass('sucuriscan-text-error');
            if (type === 'success') $msg.addClass('sucuriscan-text-success');
        };

        $btn.on('click', function () {
            const code = ($('#sucuriscan-totp-code').val() || '').toString().replace(/\s+/g, '');
            const secret = ($root.find('input[name="sucuri_2fa_secret"]').val() || '').toString();

            if (!/^\d{6}$/.test(code)) { setMessage('Enter six digits', 'error'); return; }

            setMessage('Verifying…');
            $btn.prop('disabled', true).attr('aria-disabled', 'true');

            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: { action: 'sucuri_profile_2fa_enable', nonce: ajaxNonce, user_id: userId, code: code, secret: secret }
            }).done(function (resp) {
                if (resp && resp.success && resp.data && resp.data.html) {
                    const container = $btn.closest('td').get(0) || $root.get(0);

                    if (container) { container.innerHTML = resp.data.html; }

                    setMessage('', '');
                } else {
                    const err = (resp && resp.data && (resp.data.message || resp.data.error)) || 'Verification failed';

                    setMessage(err, 'error');

                    $btn.prop('disabled', false).removeAttr('aria-disabled');
                }
            }).fail(function () {
                setMessage('Verification failed', 'error');
                $btn.prop('disabled', false).removeAttr('aria-disabled');
            });
        });
    });
</script>