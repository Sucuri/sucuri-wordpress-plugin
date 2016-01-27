
<tr class="%%SUCURI.IgnoreRules.CssClass%%">
    <td>%%SUCURI.IgnoreRules.Num%%</td>
    <td>%%SUCURI.IgnoreRules.PostTypeTitle%%</td>
    <td><span class="sucuriscan-label-%%SUCURI.IgnoreRules.IsIgnoredClass%%">%%SUCURI.IgnoreRules.IsIgnored%%</span></td>
    <td><em class="sucuriscan-monospace">%%SUCURI.IgnoreRules.WasIgnoredAt%%</em></td>
    <td class="td-with-button">
        <form action="%%SUCURI.URL.Settings%%#ignorerules" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_ignorerule" value="%%SUCURI.IgnoreRules.PostType%%" />
            <input type="hidden" name="sucuriscan_ignorerule_action" value="%%SUCURI.IgnoreRules.Action%%" />
            <button type="submit" class="%%SUCURI.IgnoreRules.ButtonClass%%">%%SUCURI.IgnoreRules.ButtonText%%</button>
        </form>
    </td>
</tr>
