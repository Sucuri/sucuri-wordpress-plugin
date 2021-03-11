
<script type="text/javascript">
/* global jQuery */
/* jshint forin: false */
/* jshint camelcase: false */
jQuery(document).ready(function ($) {
    var sucuriscanLoadIPAccess = function () {
        // $('.sucuriscan-ipaccess-table tbody').html('<tr>' +
        // '<td colspan="2">Loading...</td></tr>');

        $.post('%%SUCURI.AjaxURL.Firewall%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'firewall_ipaccess',
        }, function (data) {
            $('.sucuriscan-ipaccess-table tbody').html('');

            for (var i in data.blocklist) {
                $('.sucuriscan-ipaccess-table tbody').append('<tr>' +
                '<td><span class="sucuriscan-monospace">' + data.blocklist[i] + '</span></td>' +
                '<td><button class="button button-primary sucuriscan-deblocklist" ' +
                'data-cy="' + data.blocklist[i] + '" ' +
                'ip="' + data.blocklist[i] + '">{{Delete}}</button></td>' +
                '</tr>');
            }
        });
    };

    var sucuriscanPrintStatus = function (button, data) {
        button.attr('disabled', false);
        button.html('{{Submit}}');

        if (data.ok) {
            sucuriscanLoadIPAccess();

            $('#sucuriscan-ipaccess-response').html('<div '+
            'class="sucuriscan-inline-alert-success"><p>' +
            data.msg + '</p></div>');
        } else {
            $('#sucuriscan-ipaccess-response').html('<div '+
            'class="sucuriscan-inline-alert-error"><p>' +
            data.msg + '</p></div>');
        }
    };

    $('.sucuriscan-container').on('click', '.sucuriscan-ipaccess-button', function (event) {
        event.preventDefault();

        var button = $(this);
        var ip = $('.sucuriscan-ipaccess-form input[name=sucuriscan_ip]').val();

        button.attr('disabled', true);
        button.html('{{Loading...}}');
        $('#sucuriscan-ipaccess-response').html('');

        $.post('%%SUCURI.AjaxURL.Firewall%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'firewall_blocklist',
            ip: ip,
        }, function (data) {
            sucuriscanPrintStatus(button, data);
        });
    });

    $('.sucuriscan-container').on('click', '.sucuriscan-deblocklist', function (event) {
        event.preventDefault();

        var button = $(this);

        button.attr('disabled', true);
        button.html('{{Loading...}}');
        $('#sucuriscan-ipaccess-response').html('');

        $.post('%%SUCURI.AjaxURL.Firewall%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'firewall_deblocklist',
            ip: button.attr('ip'),
        }, function (data) {
            sucuriscanPrintStatus(button, data);
        });
    });

    sucuriscanLoadIPAccess();
});
</script>

<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{IP Address Access}}</h3>

    <div class="inside">
        <p>{{This tool allows you to add one or more IP addresses to the blocklist and stop them from accessing your website.}}</p>
        <p>{{To delete an IP from the blocklist you can use the form below or you can log into the Firewall dashboard.}}</p>

        <div id="sucuriscan-ipaccess-response"></div>

        <form action="%%SUCURI.URL.Firewall%%#ipaccess" method="post" class="sucuriscan-ipaccess-form">
            <input type="hidden" name="sucuriscan_blocklist_ip" value="true" />
            <fieldset class="sucuriscan-clearfix">
                <label>{{Add IP to the Blocklist:}}</label>
                <input type="text" name="sucuriscan_ip" data-cy="sucuriscan_ip_access_input" placeholder="{{e.g. 192.168.1.54}}" />
                <button class="button button-primary sucuriscan-ipaccess-button" data-cy="sucuriscan_ip_access_submit">{{Submit}}</button>
            </fieldset>
        </form>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-ipaccess-table">
            <thead>
                <tr>
                    <th>{{IP Address}}</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody>
                <tr><td colspan="2">{{Loading...}}</td></tr>
            </tbody>
        </table>
    </div>
</div>
