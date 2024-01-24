
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Post-Type Alerts}}</h3>

    <div class="inside">
        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.PostTypes.ErrorVisibility%%">
            <p>{{It seems that you disabled the email alerts for <b>new site content</b>, this panel is intended to provide a way to ignore specific events in your site and with that the alerts reported to your email. Since you have deactivated the <b>new site content</b> alerts, this panel will be disabled too.}}</p>
        </div>

        <p>{{This is a list of registered <a href="https://wordpress.org/documentation/article/what-is-post-type/" target="_blank" rel="noopener">Post Types</a>. You will receive an email alert when a custom page or post associated to any of these types is created or updated. If you don’t want to receive one or more of these alerts, feel free to uncheck the boxes in the table below. If you are receiving alerts for post types that are not listed in this table, it may be because there is an add-on that that is generating a custom post-type on runtime, you will have to find out by yourself what is the unique ID of that post-type and type it in the form below. The plugin will do its best to ignore these alerts as long as the unique ID is valid.}}</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_ignorerule_action" value="add">

            <fieldset class="sucuriscan-clearfix">
                <label>{{Stop Alerts For This Post-Type:}}</label>
                <input type="text" name="sucuriscan_ignorerule" placeholder="{{e.g. unique_post_type_id}}" data-cy="sucuriscan_alerts_post_type_input" />
                <button type="submit" class="button button-primary" data-cy="sucuriscan_alerts_post_type_submit">{{Submit}}</button>
            </fieldset>
        </form>

        <hr>

        <button class="button button-primary sucuriscan-show-section" section="sucuriscan-ignorerules" on="{{Show Post-Types Table}}" off="{{Hide Post-Types Table}}" data-cy="sucuriscan_alerts_post_type_toggle_post_type_list">{{Show Post-Types Table}}</button>

        <div class="sucuriscan-hidden" id="sucuriscan-ignorerules">
            <hr>

            <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_ignorerule_action" value="batch" />

                <table class="wp-list-table widefat sucuriscan-table sucuriscan-settings-ignorerules" data-cy="sucuriscan_alerts_post_type_table">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1">{{Select All}}</label>
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th class="manage-column">{{Post Type}}</th>
                            <th class="manage-column">{{Post Type ID}}</th>
                            <th class="manage-column">{{Ignored At (optional)}}</th>
                        </tr>
                    </thead>

                    <tbody>
                        %%%SUCURI.PostTypes.List%%%
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" data-cy="sucuriscan_alerts_post_type_save_submit">{{Submit}}</button>
            </form>
        </div>
    </div>
</div>
