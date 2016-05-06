
<div class="postbox">
    <h3>API Communication Protocol</h3>

    <div class="inside">
        <p>
            HTTPS is a protocol for secure communication over a computer network which is
            widely used on the Internet. HTTPS consists of communication over Hypertext
            Transfer Protocol (HTTP) within a connection encrypted by Transport Layer
            Security or its predecessor, Secure Sockets Layer. The main motivation for HTTPS
            is authentication of the visited website and protection of the privacy and
            integrity of the exchanged data.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                HTTPS provides authentication of the website and associated web server with
                which one is communicating, which protects against <a  target="_blank"
                href="https://en.wikipedia.org/wiki/Man-in-the-middle_attack">man-in-the-middle
                attacks</a>. Additionally, it provides bidirectional encryption of communications
                between a client and server, which protects against eavesdropping and tampering
                with and/or forging the contents of the communication. In practice, this provides
                a reasonable guarantee that one is communicating with precisely the website that
                one intended to communicate with (as opposed to an impostor), as well as ensuring
                that the contents of communications between the user and site cannot be read or
                forged by any third party.
            </p>
        </div>

        <p>
            More info at <a href="https://en.wikipedia.org/wiki/HTTPS" target="_blank">WikiPedia HTTPS</a>
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.ApiProtocol.StatusNum%%">
            <span>API Communication via HTTPS is %%SUCURI.ApiProtocol.Status%%</span>
            <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_api_protocol" value="%%SUCURI.ApiProtocol.SwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.ApiProtocol.SwitchCssClass%%">%%SUCURI.ApiProtocol.SwitchText%%</button>
            </form>
        </div>

        <p>List of URLs that will be affected by this setting:</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-monospace">
            %%%SUCURI.ApiProtocol.AffectedUrls%%%
        </div>
    </div>
</div>
