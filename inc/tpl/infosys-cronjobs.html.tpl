
<div class="sucuriscan-panelstuff">
    <div class="postbox sucuriscan-border sucuriscan-table-description">
        <h3>Scheduled Tasks (%%SUCURI.Cronjobs.Total%% tasks)</h3>

        <div class="inside">
            <p>
                <strong>Scheduled Tasks</strong> are rules registered in your database by a
                plugin, theme, or the base system itself; they are used to automatically execute
                actions defined in the code every certain amount of time. A good use of these
                rules is to generate backup files of your site, execute a security scanner, or
                remove unused elements like drafts.
            </p>

            <div class="sucuriscan-inline-alert-warning">
                <p>
                    Note that there are some scheduled tasks <em>(registered by the base
                    system)</em> that can not be removed permanently using this tool, tasks such as
                    the <strong>addon update</strong> and <strong>version checker</strong> are
                    required by the site to work correctly.
                </p>
            </div>

            <form action="%%SUCURI.URL.Infosys%%#wordpress-cronjobs" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

                <table class="wp-list-table widefat sucuriscan-table sucuriscan-wpcron-list">
                    <thead>
                        <tr>
                            <th class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                                <input id="cb-select-all-1" type="checkbox">
                            </th>
                            <th>Task</th>
                            <th>Schedule</th>
                            <th>Next due</th>
                            <th>Arguments</th>
                        </tr>
                    </thead>

                    <tbody>
                        %%%SUCURI.Cronjobs.List%%%
                    </tbody>
                </table>

                <div class="sucuriscan-recipient-form">
                    <label>
                        <select name="sucuriscan_cronjob_action">
                            <option value="">Choose action</option>
                            <option value="runnow">Execute now</option>
                            <option value="hourly">Re-schedule to hourly</option>
                            <option value="twicedaily">Re-schedule to twicedaily</option>
                            <option value="daily">Re-schedule to daily</option>
                            <option value="remove">Remove</option>
                        </select>
                    </label>
                    <button type="submit" class="button button-primary">Send action</button>
                </div>
            </form>
        </div>
    </div>
</div>
