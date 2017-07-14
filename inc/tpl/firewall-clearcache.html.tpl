
<script type="text/javascript">
/* global jQuery */
/* jshint camelcase: false */
jQuery(function ($) {
    $('#firewall-clear-cache-button').on('click', function (event) {
        event.preventDefault();

        var button = $(this);
        button.attr('disabled', true);
        button.html('@@SUCURI.Loading@@');
        $('#firewall-clear-cache-response').html('');

        $.post('%%SUCURI.AjaxURL.Firewall%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'firewall_clear_cache',
        }, function (data) {
            button.addClass('sucuriscan-hidden');
            $('#firewall-clear-cache-response').html(data);
        });
    });

    $('#firewall-clear-cache-auto').on('change', 'input:checkbox', function () {
        var checked = $(this).is(':checked');

        $('#firewall-clear-cache-auto span').html(
        '@@SUCURI.FirewallAutoClearCache@@ (@@SUCURI.Loading@@)');

        $.post('%%SUCURI.AjaxURL.Firewall%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'firewall_auto_clear_cache',
            auto_clear_cache: (checked?'enable':'disable'),
        }, function () {
            $('#firewall-clear-cache-auto span')
            .html('@@SUCURI.FirewallAutoClearCache@@');
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

        <div id="firewall-clear-cache-auto">
            <label>
                <input type="checkbox" name="sucuriscan_auto_clear_cache" value="true" %%SUCURI.FirewallAutoClearCache%% />
                <span>@@SUCURI.FirewallAutoClearCache@@</span>
            </label>
        </div>

        <div id="firewall-clear-cache-response"></div>
        <button id="firewall-clear-cache-button" class="button button-primary">@@SUCURI.FirewallCacheButton@@</button>
    </div>
</div>
