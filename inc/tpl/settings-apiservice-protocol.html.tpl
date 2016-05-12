
<div class="postbox">
    <h3>API Communication Protocol</h3>

    <div class="inside">
        <p>
            HTTPS is a protocol for secure communication over a computer network which is
            widely used on the Internet. HTTPS consists of communication over Hypertext
            Transfer Protocol (HTTP) within a connection encrypted by Transport Layer
            Security or its predecessor, Secure Sockets Layer. The main motivation for HTTPS
            is authentication of the visited website and protection of the privacy and
            integrity of the exchanged data.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                HTTPS provides authentication of the website and associated web server with
                which one is communicating, which protects against <a  target="_blank"
                href="https://en.wikipedia.org/wiki/Man-in-the-middle_attack">man-in-the-middle
                attacks</a>. Additionally, it provides bidirectional encryption of communications
                between a client and server, which protects against eavesdropping and tampering
                with and/or forging the contents of the communication. In practice, this provides
                a reasonable guarantee that one is communicating with precisely the website that
                one intended to communicate with (as opposed to an impostor), as well as ensuring
                that the contents of communications between the user and site cannot be read or
                forged by any third party.
            </p>
        </div>

        <p>
            More info at <a href="https://en.wikipedia.org/wiki/HTTPS" target="_blank">WikiPedia HTTPS</a>
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.ApiProtocol.StatusNum%%">
            <span>API Communication via HTTPS is %%SUCURI.ApiProtocol.Status%%</span>
            <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_api_protocol" value="%%SUCURI.ApiProtocol.SwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.ApiProtocol.SwitchCssClass%%">%%SUCURI.ApiProtocol.SwitchText%%</button>
            </form>
        </div>

        <script type="text/javascript">
        jQuery(function ($) {
            $('body').on('click', '#sucuriscan-debug-api-calls button', function (ev) {
                ev.preventDefault();
                var apiUnique;
                var testedUrls = 0;
                var button = $(this);
                var apiUrls = $('#sucuriscan-debug-api-calls tbody :checkbox:checked');
                var totalApiUrls = apiUrls.length;

                button.attr('disabled', true);
                button.html('Test API Calls &mdash; Loading...');
                $('#sucuriscan-debug-api-calls tbody td > div').html('');

                apiUrls.each(function (key, el) {
                    apiUnique = $(el).val();
                    $('#sucuriscan-api-' + apiUnique).html('Loading...');

                    $.post('%%SUCURI.AjaxURL.Settings%%', {
                        action: 'sucuriscan_settings_ajax',
                        sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                        form_action: 'debug_api_call',
                        api_unique: apiUnique
                    }, function (data) {
                        testedUrls++;
                        $('#sucuriscan-api-' + data.unique).html(data.output);

                        if (testedUrls === totalApiUrls) {
                            button.attr('disabled', false);
                            button.html('Test API Calls');
                        }
                    });
                });
            });
        });
        </script>

        <form id="sucuriscan-debug-api-calls" action="%%SUCURI.URL.Settings%%#apiservice" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table">
                <thead>
                    <tr>
                        <th class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </th>
                        <th class="manage-column" colspan="2">API URL <em>(URLs affected by this setting)</em></th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.ApiProtocol.AffectedUrls%%%
                </tbody>
            </table>

            <div class="sucuriscan-recipient-form">
                <button type="submit" name="sucuriscan_debug_api_calls"
                value="1" class="button-primary">Test API Calls</button>
            </div>
        </form>
    </div>
</div>
