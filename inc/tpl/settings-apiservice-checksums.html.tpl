
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{WordPress Checksums API}}</h3>

    <div class="inside">
        <p>{{The WordPress integrity tool uses a remote API service maintained by the WordPress organization to determine which files in the installation were added, removed or modified. The API returns a list of files with their respective checksums, this information guarantees that the installation is not corrupt. You can, however, point the integrity tool to a GitHub repository in case that you are using a custom version of WordPress like the <a href="https://github.com/WordPress/WordPress" target="_blank" rel="noopener">development version of the code</a>.}}</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>{{WordPress Checksums API}} &mdash; <a target="_blank"
            href="%%SUCURI.ChecksumsAPI%%">%%SUCURI.ChecksumsAPI%%</a>
            </span>
        </div>

        <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>{{WordPress Checksums API}}:</label>
                <input type="text" name="sucuriscan_checksum_api" placeholder="{{e.g. URL — or — user/repo}}" size="30" data-cy="sucuriscan_wordpress_checksum_api_input" />
                <button type="submit" class="button button-primary" data-cy="sucuriscan_wordpress_checksum_api_submit">{{Submit}}</button>
            </fieldset>
        </form>
    </div>
</div>
