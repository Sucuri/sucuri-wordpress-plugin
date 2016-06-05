
<div class="sucuriscan-panelstuff">
    <div class="postbox sucuriscan-border sucuriscan-table-description">
        <h3>Heartbeat</h3>

        <div class="inside">
            <p>
                The purpose of the <a href="https://core.trac.wordpress.org/ticket/23216"
                target="_blank">Heartbeat API</a> is to simulate bidirectional connection
                between the browser and the server. Initially it was used for autosave, post
                locking and log-in expiration warning while a user is writing or editing. The
                idea was to have an API that sends XHR <em>(XML HTTP Request)</em> requests to
                the server every fifteen seconds and triggers events <em>(or callbacks)</em> on
                receiving data.
            </p>
        </div>
    </div>
</div>

<table class="wp-list-table widefat sucuriscan-table sucuriscan-settings sucuriscan-settings-heartbeat">
    <thead>
        <tr>
            <th>Option</th>
            <th>Value</th>
            <th>&nbsp;</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>Heartbeat status</td>
            <td>%%SUCURI.HeartbeatStatus%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#heartbeat" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <select name="sucuriscan_heartbeat_status">
                        %%%SUCURI.HeartbeatStatusOptions%%%
                    </select>
                    <button type="submit" class="button-primary">Change</button>
                </form>
            </td>
        </tr>

        <tr class="alternate">
            <td>Pulse interval</td>
            <td>%%SUCURI.HeartbeatPulse%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#heartbeat" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <select name="sucuriscan_heartbeat_pulse">
                        %%%SUCURI.HeartbeatPulseOptions%%%
                    </select>
                    <button type="submit" class="button-primary">Change</button>
                </form>
            </td>
        </tr>

        <tr>
            <td>Interval speed</td>
            <td>%%SUCURI.HeartbeatInterval%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#heartbeat" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <select name="sucuriscan_heartbeat_interval">
                        %%%SUCURI.HeartbeatIntervalOptions%%%
                    </select>
                    <button type="submit" class="button-primary">Change</button>
                </form>
            </td>
        </tr>

        <tr class="alternate">
            <td>Auto-start</td>
            <td>%%SUCURI.HeartbeatAutostart%%</td>
            <td class="td-with-button">
                <form action="%%SUCURI.URL.Settings%%#heartbeat" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_heartbeat_autostart" value="%%SUCURI.HeartbeatAutostartSwitchValue%%" />
                    <button type="submit" class="button-primary %%SUCURI.HeartbeatAutostartSwitchCssClass%%">%%SUCURI.HeartbeatAutostartSwitchText%%</button>
                </form>
            </td>
        </tr>
    </tbody>
</table>
