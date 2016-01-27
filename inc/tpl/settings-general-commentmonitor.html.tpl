
<div class="postbox">
    <h3>User Comment Monitor</h3>

    <div class="inside">
        <p>
            User comments are the main source of spam in WordPress websites, this option
            enables the monitoring of data sent via the comment forms loaded in every page
            and post. Remember that the plugin sends this information to the Sucuri servers
            so if you do not agree with this you must keep this option disabled. Among the
            data included in the report for each comment there are identifiers of the post
            and user account <em>(usually null for guest comments)</em>, the IP address of
            the author, the email address of the author, the user-agent of the web browser
            used by the author to create the comment, the current date, the status which
            usually falls under the category of not approved, and the message itself.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                We also use this information in an anonymous way to generate <a target="_blank"
                href="https://sucuri.net/security-reports/brute-force/">statistics</a> of usage
                that help us improve our service.
            </p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>User Comment Monitor is %%SUCURI.CommentMonitorStatus%%</span>

            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_comment_monitor" value="%%SUCURI.CommentMonitorSwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.CommentMonitorSwitchCssClass%%">
                    %%SUCURI.CommentMonitorSwitchText%%
                </button>
            </form>
        </div>
    </div>
</div>
