
<div class="postbox">
    <h3>Core Integrity</h3>

    <div class="inside">
        <p>
            Every WordPress release comes with a set of files that are part of the standard
            installation process of each version, none of these files should be modified as
            they are overwritten on each upgrade, it is not advised that web developers
            modify the core files and instead extend the base functionality with themes or
            plugins. Only three directories are scanned: admin, includes, and the document
            root where the configuration and startup files are located.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                Use a <a href="https://sucuri.net/website-antivirus/" target="_blank"> server
                side scanner</a> or a <a href="https://sitecheck.sucuri.net/" target="_blank">
                web scanner</a> to find the source of the infection and broken pages respectively.
            </p>
        </div>

        <div id="sucuriscan-corefiles-response">
            <em>Loading...</em>
        </div>

        <script type="text/javascript">
        jQuery(function($){
            $.post('%%SUCURI.AjaxURL.Home%%', {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: 'get_core_files',
            }, function(data){
                $('#sucuriscan-corefiles-response').html(data);
            });
        });
        </script>
    </div>
</div>
