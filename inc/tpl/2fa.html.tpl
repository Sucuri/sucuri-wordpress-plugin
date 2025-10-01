<script type="text/javascript">
    jQuery(function ($) {
        const AJAX = '%%SUCURI.AjaxURL.Dashboard%%';
        const NONCE = '%%SUCURI.PageNonce%%';

        const $qr = $('#sucuriscan-totp-qr');
        const $form = $('#sucuriscan-totp-form');
        const $code = $('#sucuriscan-totp-code');
        const $msg = $('#sucuriscan-totp-msg');

        function call(action, extra = {}) {
            return $.post(AJAX, $.extend({
                action: 'sucuriscan_ajax',
                form_action: action,
                sucuriscan_page_nonce: NONCE
            }, extra));
        }

        function refresh() {
            call('2fa_status').done(resp => {
                if (!resp.success) {
                    return error(resp.data);
                }

                const $wrap = $('#sucuriscan-topt-qr');

                const qr = qrcode(0, 'M');

                qr.addData(resp.data.uri);
                qr.make();

                $wrap.html(qr.createImgTag(6, 4));

                switch (resp.data.status) {
                    case 'needs_setup':
                        $qr.show(); $form.show();
                        break;

                    case 'activated':
                        $qr.hide(); $form.hide();
                        $msg.text('TOTP enabled ✔︎').css('color', 'green');
                        break;
                }
            }).fail(() => error('Status request failed'));
        }

        function provision() {
            call('sucuriscan_totp_provision').done(resp => {
                if (!resp.success) {
                    return error(resp.data);
                }

                const qr = qrcode(0, 'M');

                qr.addData(resp.data.uri);
                qr.make();

                $qr.html(qr.createImgTag(6, 4)).show();

                $form.show();
            }).fail(() => error('Provision request failed'));
        }

        function error(txt) { $msg.text(txt).css('color', 'red'); }
    });
</script>


<div id="sucuriscan-tabs-headers">
    <div class="sucuriscan-panel">
        <h3 class="sucuriscan-title">{{Two-Factor Authentication}}</h3>

        %%%SUCURI.TwoFactor.CurrentUser%%%
    </div>

    <div class="sucuriscan-panel">
        <h3 class="sucuriscan-title">{{Two-Factor Authentication Policy}}</h3>

        %%%SUCURI.TwoFactor.Users%%%
    </div>
</div>