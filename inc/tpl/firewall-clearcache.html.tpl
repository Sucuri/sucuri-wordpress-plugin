
<div class="sucuriscan-panelstuff">
    <div class="postbox">
        <h3>Clear Cache</h3>

        <div class="inside">
            <p>
                CloudProxy offers multiple options to configure the cache level applied to your
                website. You can either enable the full cache functionality which is the
                recommended setting, or you can set the cache level to minimal which will keep
                the pages static for a couple of minutes, or force the usage of the website
                headers <em>(only for advanced users)</em>, or in extreme cases where you do not
                need the cache you can simply disable it. You can find more information about it
                in the <a href="https://kb.sucuri.net/cloudproxy/Performance/caching-options"
                target="_blank">Sucuri Knowledge Base</a> website.
            </p>

            <div class="sucuriscan-inline-alert-info">
                <p>
                    Note that CloudProxy has <a href="https://kb.sucuri.net/cloudproxy/Performance/cache-exceptions"
                    target="_blank">special caching rules</a> for Images, Cascading Style Sheets,
                    JavaScript, PDF, TXT, media files and a few more extensions that are stored on
                    our <a href="https://en.wikipedia.org/wiki/Edge_device" target="_blank">edge</a>.
                    The only way to flush the cache for these files is by clearing CloudProxy's cache
                    completely <em>(for the whole website)</em>.
                </p>
            </div>

            <div class="sucuriscan-inline-alert-warning">
                <p>
                    Due to our caching of JavaScript and CSS files, often, as is best practice, the
                    use of versioning during development will ensure updates going live as expected.
                    This is done by adding a query string such as <code>?ver=1.2.3</code> and
                    incrementing on each update.
                </p>
            </div>

            <p>
                A web cache (or HTTP cache) is an information technology for the temporary
                storage (caching) of web documents, such as HTML pages and images, to reduce
                bandwidth usage, server load, and perceived lag. A web cache system stores
                copies of documents passing through it; subsequent requests may be satisfied
                from the cache if certain conditions are met. A web cache system can refer
                either to an appliance, or to a computer program.
            </p>

            <p>
                More info at <a href="https://en.wikipedia.org/wiki/Web_cache" target="_blank">
                WikiPedia - Web Cache</a>
            </p>

            <form action="%%SUCURI.URL.Firewall%%#clearcache" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_clear_cache" value="1" />
                <input type="submit" value="Clear Cache" class="button button-primary" />
            </form>
        </div>
    </div>
</div>
