
<p>
    Changes in the integrity of your core files were detected, you may want to check
    each file to determine if they were infected with malicious code. The WordPress
    core directories <code>/&lt;root&gt;</code>, <code>/wp-admin</code> and <code>
    /wp-includes</code> are the only ones being scanned; the content, uploads, and
    custom directories are not part of the official archives so you have to check
    them manually.
</p>

<table border="1" cellspacing="1" cellpadding="5">
    <thead>
        <tr>
            <th colspan="5">
                Core integrity (%%SUCURI.CoreFiles.ListCount%% files)
            </th>
        </tr>

        <tr>
            <th>&nbsp;</th>
            <th width="80">Status</th>
            <th width="100">File Size</th>
            <th width="170">Modified At</th>
            <th>File Path</th>
        </tr>
    </thead>

    <tbody>
        %%%SUCURI.CoreFiles.List%%%
    </tbody>

    <tfoot>
        <tr>
            <td colspan="5">
                <p>
                    <strong>Note.</strong> This is not a malware scanner but an integrity checker
                    which is a completely different thing, if you want to check if your site is
                    generating malicious code then use the <a href="%%SUCURI.URL.Scanner%%">malware
                    scan</a> tool. If you see the text <em>"must be fixed manually"</em> in any of
                    these files that means that they do not have write permissions so you can not
                    fix them using this tool. Access the <a href="%%SUCURI.URL.Home%%">admin area
                    </a> of your website to fix these files.
                </p>
            </td>
        </tr>
    </tfoot>
</table>
