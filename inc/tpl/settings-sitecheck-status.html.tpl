
<div class="postbox">
    <h3>Malware Scanner</h3>

    <div class="inside">
        <p>
            The malware scanner is a free tool powered by <a href="https://sitecheck.sucuri.net/"
            target="_blank">Sucuri SiteCheck</a>, it will check your website for
            known malware, blacklisting status, website errors, and out-of-date
            software. Although we do our best to provide the best results, 100%
            accuracy is not realistic, and not guaranteed. The remote website
            scanner tries to identify if the provided site is infected with any
            type of malware including SPAM or if it has been blacklisted or defaced.
            Sounds simple, but being able to identify these issues remotely <em>
            (without server access)</em> is a very complicated task, and that is
            why we do not guarantee 100% accuracy. If you see a positive result
            in the scan results, it just means that when we scanned we could not
            see anything malicious.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                More information at <a href="https://blog.sucuri.net/2012/10/ask-sucuri-how-does-sitecheck-work.html"
                target="_blank">Ask Sucuri: How does SiteCheck works?</a>
            </p>
        </div>

        <div class="sucuriscan-%%SUCURI.SiteCheck.IfEnabled%%">
            <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
                <span>Malware Scanner is Enabled; used %%SUCURI.SiteCheck.Counter%% times</span>

                <form action="%%SUCURI.URL.Scanner%%" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_malware_scan" value="1" />
                    <button type="submit" class="button-primary">Scan Now</button>
                </form>
            </div>

            <p>
                You can disable this scanner adding this constant in your configuration
                file: <code>define('SUCURISCAN_NO_SITECHECK', true);</code>
            </p>
        </div>

        <div class="sucuriscan-%%SUCURI.SiteCheck.IfDisabled%%">
            <div class="sucuriscan-hstatus sucuriscan-hstatus-0">
                <span>Malware Scanner is Disabled</span>
            </div>

            <p>
                Enable this scanner by removing the constant <em>"SUCURISCAN_NO_SITECHECK"
                </em> from the configuration file.
            </p>
        </div>
    </div>
</div>
