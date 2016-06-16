
<div class="sucuriscan-panelstuff">
    <div class="postbox sucuriscan-border sucuriscan-table-description">
        <h3>Modified Files</h3>

        <div class="inside">
            <p>
                The scanner searches the WordPress content directory for files that were
                modified during the number of days specified by the user in the request. If your
                site was recently attacked, you can see which files were modified to assist with
                any investigation. Other WordPress core directories are scanned automatically
                with the core integrity checker, if you want to scan other directories that are
                not part of the official WordPress packages you have to ask for assistance to
                your hosting provider.
            </p>

            <div class="sucuriscan-inline-alert-info">
                <p>
                    Note that in most Unix file systems, a file is considered modified when its
                    inode data is changed; that is, when the permissions, owner, group, or other
                    metadata from the inode is updated. Considering this it may be possible to have
                    false-positives in the result.
                </p>
            </div>

            <div class="sucuriscan-inline-alert-warning">
                <p>
                    Depending on the number of files stored in your website this operation may fail
                    due to limitations set by your hosting provider to keep the memory assignation
                    of the PHP scripts in certain numbers. If you have issues executing this tool
                    ask your hosting provider to assist you in the configuration of your website to
                    allow the execution of this script.
                </p>
            </div>

            <form action="%%SUCURI.URL.Scanner%%" method="post" id="sucuriscan-modfiles-form">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <span class="sucuriscan-input-group">
                    <label>Search Files Modified</label>
                    <select id="sucuriscan_back_days">
                        %%%SUCURI.ModifiedFiles.SelectOptions%%%
                    </select>
                </span>
                <button id="sucuriscan-modfiles-button" class="button-primary">Proceed</button>
            </form>
        </div>

        <script type="text/javascript">
        jQuery(function($){
            $('#sucuriscan-modfiles-button').on('click', function(ev){
                ev.preventDefault();
                $('.sucuriscan-modifiedfiles tbody').html(
                    '<tr><td colspan="3"><span>Loading <em>(may take '
                    + 'several seconds)</em>...</span></td></tr>'
                );
                $.post('%%SUCURI.AjaxURL.Scanner%%', {
                    action: 'sucuriscan_scanner_ajax',
                    sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                    form_action: 'get_modfiles',
                }, function(data){
                    $('.sucuriscan-modifiedfiles tbody').html(data);
                });
            });
            $('#sucuriscan-modfiles-button').click();
        });
        </script>
    </div>
</div>

<table class="wp-list-table widefat sucuriscan-table sucuriscan-table-double-title sucuriscan-modifiedfiles">
    <thead>
        <tr>
            <th colspan="3" class="sucuriscan-clearfix thead-with-button">
                <span>Modified files <em>(inside the content directory)</em></span>
                <span class="thead-topright-action">&nbsp;</span>
            </th>
        </tr>

        <tr>
            <th width="200">Modification</th>
            <th width="100">File Size</th>
            <th>File Path</th>
        </tr>
    </thead>

    <tbody>
    </tbody>
</table>
