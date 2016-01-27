
<div class="postbox">
    <h3>Date &amp; Time</h3>

    <div class="inside">
        <p>
            The plugin uses built-in WordPress functions to retrieve the current date and
            time, as well to translate timestamps to human readable text. Below is shown the
            data returned by the main three functions used by this plugin to get the date
            for the logs and email alerts, if you notice an inconsistency with any of these
            values please change the timezone settings.
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>Current Date &amp; Time is</span>
            <strong>%%SUCURI.Datetime.HumanReadable%%</strong>
            <em>(%%SUCURI.Datetime.Timezone%% - %%SUCURI.Datetime.Timestamp%%)</em>
            <a href="%%SUCURI.Datetime.AdminURL%%" target="_blank" class="button-primary">Change</a>
        </div>
    </div>
</div>
