
<div class="sucuriscan-panel sucuriscan-integrity sucuriscan-integrity-incorrect">
    <div class="sucuriscan-clearfix">
        <div class="sucuriscan-pull-left sucuriscan-integrity-left">
            <h2 class="sucuriscan-title">@@SUCURI.IntegrityTitle@@</h2>

            <p>@@SUCURI.IntegrityDescription@@</p>
        </div>

        <div class="sucuriscan-pull-right sucuriscan-integrity-right">
            <h2 class="sucuriscan-subtitle">@@SUCURI.IntegrityBadTitle@@</h2>

            <p>@@SUCURI.IntegrityBadDescription@@</p>

            <p><a href="%%SUCURI.URL.Settings%%#scanner">@@SUCURI.ReviewFalsePositives@@</a></p>
        </div>
    </div>

    %%%SUCURI.SiteCheck.Details%%%

    %%%SUCURI.Integrity.DiffUtility%%%

    <form action="%%SUCURI.URL.Dashboard%%" method="post" class="sucuriscan-%%SUCURI.Integrity.BadVisibility%%">
        <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-integrity-table">
            <thead>
                <tr>
                    <th colspan="5">@@SUCURI.IntegrityTitle@@ (%%SUCURI.Integrity.ListCount%%)</th>
                </tr>

                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th width="20" class="manage-column">&nbsp;</th>
                    <th width="100" class="manage-column">@@SUCURI.FileSize@@</th>
                    <th width="200" class="manage-column">@@SUCURI.ModifiedAt@@</th>
                    <th class="manage-column">@@SUCURI.FilePath@@</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.Integrity.List%%%
            </tbody>
        </table>

        <div class="sucuriscan-inline-alert-info">
            <p>@@SUCURI.MarkFixedDescription@@</p>
        </div>

        <p>
            <label>
                <input type="hidden" name="sucuriscan_process_form" value="0" />
                <input type="checkbox" name="sucuriscan_process_form" value="1" />
                <span>@@SUCURI.UnderstandTheRisk@@</span>
            </label>
        </p>

        <fieldset class="sucuriscan-clearfix">
            <label>@@SUCURI.Action@@:</label>
            <select name="sucuriscan_integrity_action">
                <option value="fixed">@@SUCURI.MarkFixed@@</option>
                <option value="restore">@@SUCURI.RestoreFile@@</option>
                <option value="delete">@@SUCURI.DeleteFile@@</option>
            </select>
            <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
        </fieldset>
    </form>
</div>
