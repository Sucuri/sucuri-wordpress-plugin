
<script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */

    jQuery(document).ready(function($) {
        $('.sucuriscan-header-cache-control-edit-btn').each(function() {
            $(this).click(function(e) {
                e.preventDefault();
                var rowClass = $(this).closest('tr').data('page');
                var row = $(this).closest('tr');
                var valueSpans = $('.sucuriscan-row-' + rowClass + ' .sucuriscan-headers-cache-value');
                var inputFields = $('.sucuriscan-row-' + rowClass + ' .sucuriscan-headers-cache-input');

                if ($(this).text() === 'Edit') {
                    row.addClass('sucuriscan-headers-cache-is-editing');
                    valueSpans.addClass('sucuriscan-hidden');
                    inputFields.removeClass('sucuriscan-hidden');
                    $(this).text('Update');

                    return;
                }

                valueSpans.each(function(index, span) {
                    $(span).text(inputFields.eq(index).val());
                });

                row.removeClass('sucuriscan-headers-cache-is-editing');
                valueSpans.removeClass('sucuriscan-hidden');
                inputFields.addClass('sucuriscan-hidden');
                inputFields.addClass('p-0');

                $(this).text('Edit');

                var newValues = {};

                inputFields.each(function() {
                    newValues[this.name] = this.value;
                });

                $.post('%%SUCURI.URL.Settings%%#headers', {
                    sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                    sucuriscan_update_cache_options: 1,
                    sucuriscan_cache_options_mode: 'custom',
                    sucuriscan_page_type: rowClass,
                    ...newValues,
                });
            });
        });
    });

    jQuery(document).ready(function($) {
        // Define a mapper function
        var optionValues = {
            'static': {
                'front_page': { 'max-age': 604800 },
                'posts': { 'max-age': 604800 },
                'pages': { 'max-age': 604800 },
                'main_index': { 'max-age': 604800 },
                'categories': { 'max-age': 604800 },
                'tags': { 'max-age': 604800 },
                'authors': { 'max-age': 604800 },
                'archives': { 'max-age': 604800 },
                'feeds': { 'max-age': 604800 },
                'attachment_pages': { 'max-age': 604800 },
                'search_results': { 'max-age': 604800 },
                '404_not_found': { 'max-age': 604800 },
                'redirects': { 'max-age': 604800 },
            },
            'occasional': {
                'front_page': { 'max-age': 21600 },
                'posts': { 'max-age': 43200 },
                'pages': { 'max-age': 43200 },
                'main_index': { 'max-age': 21600 },
                'categories': { 'max-age': 43200 },
                'tags': { 'max-age': 43200 },
                'authors': { 'max-age': 43200 },
                'archives': { 'max-age': 86400 },
                'feeds': { 'max-age': 43200 },
                'attachment_pages': { 'max-age': 86400 },
                'search_results': { 'max-age': 604800 },
                '404_not_found': { 'max-age': 86400 },
                'redirects': { 'max-age': 86400 },
            },
            'frequent': {
                'front_page': { 'max-age': 1800 },
                'posts': { 'max-age': 3600 },
                'pages': { 'max-age': 3600 },
                'main_index': { 'max-age': 1800 },
                'categories': { 'max-age': 7200 },
                'tags': { 'max-age': 7200 },
                'authors': { 'max-age': 7200 },
                'archives': { 'max-age': 7200 },
                'feeds': { 'max-age': 1800 },
                'attachment_pages': { 'max-age': 7200 },
                'search_results': { 'max-age': 7200 },
                '404_not_found': { 'max-age': 7200 },
                'redirects': { 'max-age': 7200 },
            },
            'busy': {
                'front_page': { 'max-age': 300 },
                'posts': { 'max-age': 600 },
                'pages': { 'max-age': 600 },
                'main_index': { 'max-age': 300 },
                'categories': { 'max-age': 600 },
                'tags': { 'max-age': 600 },
                'authors': { 'max-age': 600 },
                'archives': { 'max-age': 600 },
                'feeds': { 'max-age': 300 },
                'attachment_pages': { 'max-age': 600 },
                'search_results': { 'max-age': 600 },
                '404_not_found': { 'max-age': 600 },
                'redirects': { 'max-age': 600 },
            },
            'default': {
                'front_page': { 'max-age': 604800 },
                'posts': { 'max-age': 604800 },
                'pages': { 'max-age': 604800 },
                'main_index': { 'max-age': 604800 },
                'categories': { 'max-age': 604800 },
                'tags': { 'max-age': 604800 },
                'authors': { 'max-age': 604800 },
                'archives': { 'max-age': 604800 },
                'feeds': { 'max-age': 604800 },
                'attachment_pages': { 'max-age': 604800 },
                'search_results': { 'max-age': 604800 },
                '404_not_found': { 'max-age': 604800 },
                'redirects': { 'max-age': 604800 },
            },
        };

        $('[data-cy="sucuriscan_headers_cache_control_dropdown"]').change(function() {
            var selectedOption = $(this).val();
            if (selectedOption === 'disabled') return;

            $('tr[data-page]').each(function() {
                var pageType = $(this).data('page');
                var values = optionValues[selectedOption][pageType] || optionValues['default'][pageType];
                var currentRow = $(this); // Store the reference to the current row

                $.each(values, function(fieldName, fieldValue) {
                    var fieldClass = '.sucuriscan-headers-' + fieldName;
                    currentRow.find(fieldClass + ' .sucuriscan-headers-cache-input').val(fieldValue);
                    currentRow.find(fieldClass + ' .sucuriscan-headers-cache-value').text(fieldValue);
                });
            });
        });
    });
</script>

<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Cache Control Header Options}}</h3>

    <form action="%%SUCURI.URL.Settings%%#headers" method="post">
        <div class="inside">
            <p>{{Here you can see all the cache options available.}}</p>

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-table-fixed-layout sucuriscan-table-double-title sucuriscan-last-logins">
                <thead>
                    <tr>
                        <th class="manage-column">{{Option}}</th>
                        <th class="manage-column">{{max-age}}</th>
                        <th class="manage-column">{{s-maxage}}</th>
                        <th class="manage-column">{{stale-if-error}}</th>
                        <th class="manage-column">{{stale-while-revalidate}}</th>
                        <th class="manage-column">{{Pagination factor}}</th>
                        <th class="manage-column">{{Old age multiplier}}</th>

                        <th class="manage-column">&nbsp;</th>
                    </tr>
                </thead>

                <tbody data-cy="sucuriscan_last_logins_table">

                %%%SUCURI.CacheOptions.Options%%%

                <tr class="sucuriscan-%%SUCURI.CacheOptions.NoItemsVisibility%%">
                    <td colspan="5">
                        <em>{{No options available}}</em>
                    </td>
                </tr>

                </tbody>
            </table>
        </div>

        <div class="sucuriscan-double-box sucuriscan-hstatus sucuriscan-double-box-update sucuriscan-hstatus-%%SUCURI.CacheOptions.CacheControl%%" data-cy="sucuriscan_headers_cache_control">
            <p>
                <strong>{{Cache Control Header}}</strong> &mdash; %%SUCURI.CacheOptions.Status%%<br />
                {{WordPress by default does not come with cache control headers, used by WAFs and CDNs that are useful to both improve performance and reduce bandwidth and other resources demand on the hosting server. }}
            </p>

            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_update_cache_options" value="1" />

            <div>
                <label><strong>{{Mode:}}</strong></label>

                <select name="sucuriscan_cache_options_mode" data-cy="sucuriscan_headers_cache_control_dropdown">
                    %%%SUCURI.CacheOptions.Modes%%%
                </select>

                <input type="submit" value="{{Submit}}" class="button button-primary" data-cy="sucuriscan_headers_cache_control_submit_btn" />
            </div>
        </div>
    </form>
</div>
