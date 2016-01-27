
<div class="postbox">
    <h3>Password Guessing Brute Force Attacks</h3>

    <div class="inside">
        <p>
            Password guessing brute force attacks are very common against web sites and web
            servers. They are one of the most common vectors used to compromise web sites.
            The process is very simple and the attackers basically try multiple combinations
            of usernames and passwords until they find one that works. Once they get in,
            they can compromise the web site with malware, spam , phishing or anything else
            they want.
        </p>

        <div class="sucuriscan-inline-alert-warning">
            <p>This option overrides the <em>"Alerts Per Hour"</em> setting.</p>
        </div>

        <form action="%%SUCURI.URL.Settings%%#notifications" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <span class="sucuriscan-input-group">
                <label>Consider Brute-Force Attack After:</label>
                <select name="sucuriscan_maximum_failed_logins">
                    %%%SUCURI.AlertSettings.BruteForce%%%
                </select>
            </span>
            <button type="submit" class="button-primary">Save</button>
        </form>

        <p>
            More info at <a href="https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing"
            target="_blank">Sucuri KB - Password Guessing Brute Force Attacks</a>.
        </p>
    </div>
</div>
