
<div class="postbox">
    <h3>Ignore Scanning for Files</h3>

    <div class="inside">
        <p>
            By default the file system scanner ignore the directories listed here. You can
            use this panel to insert individual files or symbolic links in the list using
            their absolute path. By aware that the form only accepts valid file paths,
            wildcards are not allowed to prevent the misuse of this tool.
        </p>

        <form action="%%SUCURI.URL.Settings%%#ignorescanning" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_ignorescanning_action" value="ignore" />
            <input type="text" name="sucuriscan_ignorescanning_file" placeholder="e.g. /private/cert.crt" />
            <button type="submit" class="button button-primary">Proceed</button>
        </form>
    </div>
</div>
