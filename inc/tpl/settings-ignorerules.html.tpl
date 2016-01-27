
<div id="poststuff">
    <div class="postbox sucuriscan-border sucuriscan-border-bad sucuriscan-%%SUCURI.IgnoreRules.MessageVisibility%%">
        <h3>Ignore Alerts</h3>

        <div class="inside">
            <p>
                It seems that you disabled the email notifications for <strong>new site
                content</strong>, this panel is intended to provide a way to ignore specific
                events in your site and with that the alerts reported to your email. Since you
                have deactivated the <strong>new site content</strong> alerts, this panel will
                be disabled too.
            </p>
        </div>
    </div>
</div>

<div id="poststuff">
    <div class="postbox sucuriscan-border sucuriscan-table-description sucuriscan-%%SUCURI.IgnoreRules.TableVisibility%%">
        <h3>Ignore Alerts</h3>

        <div class="inside">
            <p>
                This is a list of registered <a href="https://codex.wordpress.org/Post_Types"
                target="_blank">Post Types</a>, since you have enabled the <strong>email alerts
                for new or modified content</strong>, we will send you an alert if any of these
                <code>post-types</code> are created and/or updated. You may want to ignore some
                of them as some 3rd-party extensions create temporary data in the posts table
                to track changes in their own tools.
            </p>
        </div>
    </div>
</div>

<table class="wp-list-table widefat sucuriscan-table sucuriscan-settings-ignorerules sucuriscan-%%SUCURI.IgnoreRules.TableVisibility%%">
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th>Post Type</th>
            <th width="50">Ignored</th>
            <th>Ignored At</th>
            <th>&nbsp;</th>
        </tr>
    </thead>

    <tbody>
        %%%SUCURI.IgnoreRules.PostTypes%%%
    </tbody>
</table>
