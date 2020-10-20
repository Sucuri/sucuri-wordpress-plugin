
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Ignore Files And Folders During The Scans}}</h3>

    <div class="inside">
        <p>{{Use this tool to select the files and/or folders that are too heavy for the scanner to process. These are usually folders with images, media files like videos and audios, backups and &mdash; in general &mdash; anything that is not code-related. Ignoring these files or folders will reduce the memory consumption of the PHP script.}}</p>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <fieldset class="sucuriscan-clearfix">
                <label>{{Ignore a file or directory:}}</label>
                <input type="text" name="sucuriscan_ignorefolder" placeholder="{{e.g. /private/directory/}}" data-cy="sucuriscan_ignore_files_folders_input" />
                <button type="submit" class="button button-primary" data-cy="sucuriscan_ignore_files_folders_ignore_submit">{{Submit}}</button>
            </fieldset>
        </form>

        <hr>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-ignorescanning" data-cy="sucuriscan_ignore_files_folders_table">
                <thead>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">{{Select All}}</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="manage-column">{{File Path}}</th>
                    <th class="manage-column">{{Status}}</th>
                </thead>

                <tbody>
                    %%%SUCURI.IgnoreScan.List%%%
                </tbody>
            </table>

            <button type="submit" class="button button-primary" data-cy="sucuriscan_ignore_files_folders_unignore_submit">{{Unignore Selected Directories}}</button>
        </form>
    </div>
</div>
