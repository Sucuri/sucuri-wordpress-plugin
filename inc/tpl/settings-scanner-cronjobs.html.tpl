
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Scheduled Tasks}}</h3>

    <script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */
    jQuery(document).ready(function ($) {
        $.post('%%SUCURI.AjaxURL.Dashboard%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'get_cronjobs',
        }, function (data) {
            $('.sucuriscan-wpcron-list tbody').html(data);
        });
    });
    </script>

    <div class="inside">
        <p>{{The plugin scans your entire website looking for changes which are later reported via the API in the audit logs page. By default the scanner runs daily but you can change the frequency to meet your requirements. Notice that scanning your project files too frequently may affect the performance of your website. Be sure to have enough server resources before changing this option. The memory limit and maximum execution time are two of the PHP options that your server will set to stop your website from consuming too much resources.}}</p>

        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.NoSPL.Visibility%%">
            <p>{{The scanner uses the <a href="http://php.net/manual/en/class.splfileobject.php" target="_blank" rel="noopener">PHP SPL library</a> and the <a target="_blank" href="http://php.net/manual/en/class.filesystemiterator.php" rel="noopener">Filesystem Iterator</a> class to scan the directory tree where your website is located in the server. This library is only available on PHP 5 >= 5.3.0 &mdash; OR &mdash; PHP 7; if you have an older version of PHP the plugin will not work as expected. Please ask your hosting provider to advise you on this matter.}}</p>
        </div>

        <p>{{Scheduled tasks are rules registered in your database by a plugin, theme, or the base system itself; they are used to automatically execute actions defined in the code every certain amount of time. A good use of these rules is to generate backup files of your site, execute a security scanner, or remove unused elements like drafts. <b>Note:</b> Scheduled tasks can be re-installed by any plugin/theme automatically.}}</p>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-wpcron-list">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">{{Select All}}</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th>{{Name}}</th>
                        <th>{{Schedule}}</th>
                        <th>{{Next Due}}</th>
                        <th>{{Arguments}}</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td colspan="5">
                            <span>{{Loading...}}</span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <fieldset class="sucuriscan-clearfix">
                <label>{{Action:}}</label>
                <select name="sucuriscan_cronjob_action">
                    %%%SUCURI.Cronjob.Schedules%%%
                </select>
                <button type="submit" class="button button-primary">{{Submit}}</button>
            </fieldset>
        </form>
    </div>
</div>
