<div class="sucuriscan-profile-2fa-status">
    <p class="sucuriscan-mb-5" data-cy="sucuriscan-2fa-status-text">
        <span class="dashicons dashicons-yes sucuriscan-2fa-status-icon sucuriscan-2fa-status-icon-success"
            aria-hidden="true"></span>
        Two-Factor Authentication is enabled for this account.
    </p>

    <p class="sucuriscan-2fa-reset-row">
        <button type="button" class="button button-secondary" id="sucuri-2fa-reset-btn"
            aria-controls="sucuri-2fa-reset-msg" data-cy="sucuriscan-2fa-reset-btn">
            Reset two-factor
        </button>
        <span id="sucuri-2fa-reset-msg" class="sucuriscan-2fa-reset-msg" role="status" aria-live="polite"></span>
    </p>
</div>

<script type="text/javascript">
    jQuery(function ($) {
        const $root = $('.sucuriscan-profile-2fa-status');

        if (!$root.length) return;

        const ajaxUrl = '%%SUCURI.ajax_url%%';
        const ajaxNonce = '%%SUCURI.ajax_nonce%%';
        const userId = '%%SUCURI.user_id%%';

        const $btn = $('#sucuri-2fa-reset-btn');
        const $msg = $('#sucuri-2fa-reset-msg');

        const setMessage = (text, type) => {
            if (!$msg.length) return;

            $msg.text(text || '').removeClass('sucuriscan-text-error sucuriscan-text-success');

            if (type === 'error') $msg.addClass('sucuriscan-text-error');
            if (type === 'success') $msg.addClass('sucuriscan-text-success');
        };

        const setBusy = (busy) => {
            $btn.prop('disabled', !!busy).attr('aria-disabled', busy ? 'true' : 'false');
        };

        const hydrateSetup = ($container) => {
            if (!$container || !$container.length) return;

            try {
                const $qr = $container.find('#sucuriscan-topt-qr');

                if ($qr.length && typeof qrcode === 'function') {
                    const uri = $qr.data('otpauth') || '';

                    if (uri) {
                        const qr = qrcode(0, 'M');

                        qr.addData(uri);
                        qr.make();

                        $qr.html(qr.createImgTag(6, 4));
                    } else {
                        $qr.html('<em class="sucuriscan-text-error">QR unavailable: missing secret. Reload profile.</em>');
                    }
                }
            } catch (e) { /* noop */ }

            const $enableBtn = $container.find('#sucuri-2fa-enable-btn');
            const $enableMsg = $container.find('#sucuri-2fa-enable-msg');

            const setEnableMsg = (text, type) => {
                if (!$enableMsg.length) return;

                $enableMsg.text(text || '').removeClass('sucuriscan-text-error sucuriscan-text-success');

                if (type === 'error') $enableMsg.addClass('sucuriscan-text-error');
                if (type === 'success') $enableMsg.addClass('sucuriscan-text-success');
            };

            if ($enableBtn.length) {
                $enableBtn.on('click', () => {
                    const $code = $container.find('#sucuriscan-totp-code');
                    const $secret = $container.find('input[name="sucuri_2fa_secret"]');
                    const code = ($code.val() || '').toString().replace(/\s+/g, '');
                    const secret = ($secret.val() || '').toString();

                    if (!/^\d{6}$/.test(code)) { setEnableMsg('Enter six digits', 'error'); return; }

                    setEnableMsg('Verifying…');
                    $enableBtn.prop('disabled', true).attr('aria-disabled', 'true');

                    $.ajax({
                        url: ajaxUrl,
                        method: 'POST',
                        dataType: 'json',
                        data: { action: 'sucuri_profile_2fa_enable', nonce: ajaxNonce, user_id: userId, code, secret }
                    }).done(resp => {
                        if (resp && resp.success && resp.data && resp.data.html) {
                            const cell = $container.closest('td').get(0) || $container.get(0);

                            if (cell) cell.innerHTML = resp.data.html;
                        } else {
                            const err = (resp && resp.data && (resp.data.message || resp.data.error)) || 'Verification failed';

                            setEnableMsg(err, 'error');

                            $enableBtn.prop('disabled', false).removeAttr('aria-disabled');
                        }
                    }).fail(() => {
                        setEnableMsg('Verification failed', 'error');
                        $enableBtn.prop('disabled', false).removeAttr('aria-disabled');
                    });
                });
            }
        };

        $btn.on('click', () => {
            if (!window.confirm('This will disable two-factor for this user. Continue?')) return;

            setBusy(true);
            setMessage('Resetting…');

            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: { action: 'sucuri_profile_2fa_reset', nonce: ajaxNonce, user_id: userId }
            }).done(resp => {
                if (resp && resp.success && resp.data && resp.data.html) {
                    const $cell = $root.closest('td').length ? $($root.closest('td')[0]) : $root;
                    $cell.html(resp.data.html);

                    hydrateSetup($cell);
                } else {
                    const err = (resp && resp.data && (resp.data.message || resp.data.error)) || 'Reset failed';
                    setMessage(err, 'error');
                    setBusy(false);
                }
            }).fail(() => {
                setMessage('Reset failed', 'error');
                setBusy(false);
            });
        });
    });
</script>