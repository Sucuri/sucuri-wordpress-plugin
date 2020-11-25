
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Password Guessing Brute Force Attacks}}</h3>

    <div class="inside">
        <p>{{<a href="https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing" target="_blank" rel="noopener">Password guessing brute force attacks</a> are very common against web sites and web servers. They are one of the most common vectors used to compromise web sites. The process is very simple and the attackers basically try multiple combinations of usernames and passwords until they find one that works. Once they get in, they can compromise the web site with malware, spam , phishing or anything else they want.}}</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>{{Consider Brute-Force Attack After:}}</label>
                <select name="sucuriscan_maximum_failed_logins" data-cy="sucuriscan_max_failed_logins_select">
                    %%%SUCURI.Alerts.BruteForce%%%
                </select>
                <button type="submit" class="button button-primary" data-cy="sucuriscan_max_failed_logins_submit">{{Submit}}</button>
            </fieldset>
        </form>
    </div>
</div>
