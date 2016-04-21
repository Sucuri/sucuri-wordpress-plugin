
<div class="postbox">
    <h3>Malware Scanner</h3>

    <div class="inside">
        <p>
            The malware scanner is a free tool powered by <a href="https://sitecheck.sucuri.net/"
            target="_blank">Sucuri SiteCheck</a>, it will check your website for known malware,
            blacklisting status, website errors, and out-of-date software. Although we do our
            best to provide the best results, 100% accuracy is not realistic, and not guaranteed.
        </p>

        <p>
            The remote website scanner tries to identify if the provided site is infected
            with any type of malware including SPAM or if it has been blacklisted or
            defaced. Sounds simple, but being able to identify these issues remotely
            <em>(without server access)</em> is a very complicated task, and that is why we
            do not guarantee 100% accuracy. If you see a positive result in the scan
            results, it just means that when we scanned we could not see anything malicious.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                More information at <a href="https://blog.sucuri.net/2012/10/ask-sucuri-how-does-sitecheck-work.html"
                target="_blank">Ask Sucuri: How does SiteCheck works?</a>
            </p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.SiteCheck.StatusNum%%">
            <span>Malware Scanner is %%SUCURI.SiteCheck.Status%%</span>
            <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_sitecheck_scanner" value="%%SUCURI.SiteCheck.SwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.SiteCheck.SwitchCssClass%%">%%SUCURI.SiteCheck.SwitchText%%</button>
            </form>
        </div>
    </div>
</div>
