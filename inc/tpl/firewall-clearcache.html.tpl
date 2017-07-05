
<script type="text/javascript">
/* global jQuery */
/* jshint camelcase: false */
jQuery(function ($) {
    $('#firewall-clear-cache-form > button').on('click', function (event) {
        event.preventDefault();

        $('#firewall-clear-cache-form .button').attr('disabled', true);
        $('#firewall-clear-cache-form .button').html('@@SUCURI.Loading@@');
        $('#firewall-clear-cache-response').html('');

        $.post('%%SUCURI.AjaxURL.Firewall%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'firewall_clear_cache',
        }, function (data) {
            $('#firewall-clear-cache-response').html(data);
            $('#firewall-clear-cache-form').addClass('sucuriscan-hidden');
        });
    });
});
</script>

<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.FirewallCacheTitle@@</h3>

    <div class="inside">
        <p>@@SUCURI.FirewallCacheInfo@@</p>

        <div class="sucuriscan-inline-alert-info">
            <p>@@SUCURI.FirewallCacheNote@@</p>
        </div>

        <p>@@SUCURI.FirewallCacheWiki@@</p>

        <div id="firewall-clear-cache-response"></div>

        <form action="%%SUCURI.URL.Firewall%%#clearcache" method="post" id="firewall-clear-cache-form">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_clear_cache" value="1" />
            <button class="button button-primary">@@SUCURI.FirewallCacheButton@@</button>
        </form>
    </div>
</div>
