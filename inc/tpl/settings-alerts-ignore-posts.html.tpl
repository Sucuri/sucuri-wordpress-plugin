
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Ignore Post Changes</h3>

    <div class="inside">
        <p class="sucuriscan-%%SUCURI.IgnoreRules.MessageVisibility%%">
            It seems that you disabled the email alerts for <b>new site
            content</b>, this panel is intended to provide a way to ignore
            specific events in your site and with that the alerts reported to
            your email. Since you have deactivated the <b>new site content</b>
            alerts, this panel will be disabled too.
        </p>

        <p class="sucuriscan-%%SUCURI.IgnoreRules.TableVisibility%%">
            This is a list of registered <a href="https://codex.wordpress.org/Post_Types"
            target="_blank">Post Types</a>. You will receive an alert if any of these
            <code>post-types</code> are created and/or updated. You may want to ignore
            some of them as some 3rd-party extensions create temporary data in the posts
            table to track changes in their own tools.
        </p>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-settings-ignorerules sucuriscan-%%SUCURI.IgnoreRules.TableVisibility%%">
            <thead>
                <tr>
                    <th>Ignored At</th>
                    <th>Ignored</th>
                    <th>Post Type</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.IgnoreRules.PostTypes%%%
            </tbody>
        </table>
    </div>
</div>
