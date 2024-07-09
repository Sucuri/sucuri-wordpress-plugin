<script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */

    jQuery(document).ready(function ($) {
        $('.sucuriscan-headers-cache-input[type="checkbox"]').prop('disabled', true);

        $('input.sucuriscan-headers-cache-input[type="checkbox"]').each(function () {
            if ($(this).val() === '1' || $(this).val().toLowerCase() === 'true') {
                $(this).prop('checked', true);
            }
        });

        $('input.sucuriscan-headers-cache-input[type="checkbox"]').change(function () {
            if ($(this).is(':checked')) {
                $(this).val('1');
            } else {
                $(this).val('0');
            }
        });
    });

    jQuery(document).ready(function ($) {
        $('.sucuriscan-header-cache-control-edit-btn').each(function () {
            $(this).click(function (e) {
                e.preventDefault();

                var isEditing = $(this).text().trim() === 'Edit';

                var row = $(this).closest('tr');
                var contentType = $(this).closest('tr').data('page');

                var spans = $('.sucuriscan-row-' + contentType + ' .sucuriscan-headers-cache-value:not(.sucuriscan-unavailable)');
                var inputs = $('.sucuriscan-row-' + contentType + ' .sucuriscan-headers-cache-input:not(.sucuriscan-unavailable)');

                // Check if the row is already in editing mode
                if (isEditing) {
                    row.addClass('sucuriscan-headers-cache-is-editing');

                    spans.each(function (index, span) {
                        $(span).addClass('sucuriscan-hidden');
                    });

                    inputs.each(function (index, input) {
                        $(input).removeClass('sucuriscan-hidden');
                    });


                    $('[data-cy=sucuriscan_headers_cache_control_dropdown]').val('custom');
                    $('.sucuriscan-headers-cache-input[type="checkbox"]').prop('disabled', false);

                    $(this).text('Update');
                } else {
                    var newValues = {};

                    row.removeClass('sucuriscan-headers-cache-is-editing');

                    inputs.each(function (index, input) {
                        var newValue = $(input).val();
                        $(spans[index]).text(newValue);
                        newValues[this.name] = newValue;
                    });

                    spans.each(function (index, span) {
                        $(span).removeClass('sucuriscan-hidden');
                    });

                    inputs.each(function (index, input) {
                        if ($(input).attr('type') !== 'checkbox') {
                            $(input).addClass('sucuriscan-hidden');
                        }
                    });

                    $(this).text('Edit');

                    inputs.each(function () {
                        newValues[this.name] = this.value;
                    });

                    $.post('%%SUCURI.URL.Settings%%#headers', {
                        sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                        sucuriscan_update_cache_options: 1,
                        sucuriscan_cache_options_mode: 'custom',
                        sucuriscan_page_type: contentType,
                        ...newValues,
                    });

                    // Update the box to enabled
                    var cacheControlStatusDiv = $('.sucuriscan-double-box-update');
                    var cacheControlStatusSpan = cacheControlStatusDiv.find('span');

                    cacheControlStatusDiv.removeClass('sucuriscan-hstatus-0');
                    cacheControlStatusDiv.addClass('sucuriscan-hstatus-1');

                    cacheControlStatusSpan.text('Enabled');
                    $('.sucuriscan-headers-cache-input[type="checkbox"]').prop('disabled', true);
                }
            });
        });
    });


    jQuery(document).ready(function ($) {
        var optionValues = {
            'static': {
                'front_page': {'max-age': 604800},
                'posts': {'max-age': 604800},
                'pages': {'max-age': 604800},
                'main_index': {'max-age': 604800},
                'categories': {'max-age': 604800},
                'tags': {'max-age': 604800},
                'authors': {'max-age': 604800},
                'archives': {'max-age': 604800},
                'feeds': {'max-age': 604800},
                'attachment_pages': {'max-age': 604800},
                'search_results': {'max-age': 604800},
                '404_not_found': {'max-age': 604800},
                'redirects': {'max-age': 604800},
            },
            'occasional': {
                'front_page': {'max-age': 21600},
                'posts': {'max-age': 43200},
                'pages': {'max-age': 43200},
                'main_index': {'max-age': 21600},
                'categories': {'max-age': 43200},
                'tags': {'max-age': 43200},
                'authors': {'max-age': 43200},
                'archives': {'max-age': 86400},
                'feeds': {'max-age': 43200},
                'attachment_pages': {'max-age': 86400},
                'search_results': {'max-age': 604800},
                '404_not_found': {'max-age': 86400},
                'redirects': {'max-age': 86400},
            },
            'frequent': {
                'front_page': {'max-age': 1800},
                'posts': {'max-age': 3600},
                'pages': {'max-age': 3600},
                'main_index': {'max-age': 1800},
                'categories': {'max-age': 7200},
                'tags': {'max-age': 7200},
                'authors': {'max-age': 7200},
                'archives': {'max-age': 7200},
                'feeds': {'max-age': 1800},
                'attachment_pages': {'max-age': 7200},
                'search_results': {'max-age': 7200},
                '404_not_found': {'max-age': 7200},
                'redirects': {'max-age': 7200},
            },
            'busy': {
                'front_page': {'max-age': 300},
                'posts': {'max-age': 600},
                'pages': {'max-age': 600},
                'main_index': {'max-age': 300},
                'categories': {'max-age': 600},
                'tags': {'max-age': 600},
                'authors': {'max-age': 600},
                'archives': {'max-age': 600},
                'feeds': {'max-age': 300},
                'attachment_pages': {'max-age': 600},
                'search_results': {'max-age': 600},
                '404_not_found': {'max-age': 600},
                'redirects': {'max-age': 600},
            },
            'default': {
                'front_page': {'max-age': 604800},
                'posts': {'max-age': 604800},
                'pages': {'max-age': 604800},
                'main_index': {'max-age': 604800},
                'categories': {'max-age': 604800},
                'tags': {'max-age': 604800},
                'authors': {'max-age': 604800},
                'archives': {'max-age': 604800},
                'feeds': {'max-age': 604800},
                'attachment_pages': {'max-age': 604800},
                'search_results': {'max-age': 604800},
                '404_not_found': {'max-age': 604800},
                'redirects': {'max-age': 604800},
            },
        };

        $('[data-cy="sucuriscan_headers_cache_control_dropdown"]').change(function () {
            var selectedOption = $(this).val();

            if (selectedOption === 'disabled') return;

            $('tr[data-page]').each(function () {
                var contentType = $(this).data('page');
                var values = optionValues[selectedOption][contentType] || optionValues['default'][contentType];
                var row = $(this);

                $.each(values, function (fieldName, fieldValue) {
                    var fieldClass = '.sucuriscan-headers-' + fieldName;

                    row.find(fieldClass + ' .sucuriscan-headers-cache-input').val(fieldValue);
                    row.find(fieldClass + ' .sucuriscan-headers-cache-value').text(fieldValue);
                });
            });
        });
    });
</script>

<!-- In order to avoid re-using the same SVG over and over again: -->
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="14" height="14"
     style="display: none;">
    <symbol id="helper-svg" viewBox="0 0 14 14">
        <path fill="#000000" d="m6.998315,0.033333c-3.846307,0 -6.964982,
                    3.118675 -6.964982,6.964982s3.118675,6.965574 6.964982,6.965574s6.965574,
                    -3.119267 6.965574,-6.965574s-3.119267,-6.964982 -6.965574,-6.964982zm1.449957,
                    10.794779c-0.358509,0.141517 -0.643901,0.248833 -0.857945,0.32313c-0.213455,
                    0.074296 -0.461699,0.111444 -0.744143,0.111444c-0.433985,0 -0.771855,
                    -0.106137 -1.012434,-0.317823s-0.360279,-0.479978 -0.360279,-0.806055c0,
                    -0.126776 0.008845,-0.256499 0.026534,-0.388581c0.018281,-0.132082 0.047174,
                    -0.280675 0.086679,-0.447547l0.448727,-1.584988c0.039507,-0.152131 0.073707,
                    -0.296596 0.100831,-0.431036c0.027123,-0.135621 0.040097,-0.260037 0.040097,
                    -0.37325c0,-0.201661 -0.041865,-0.343178 -0.125008,-0.422782c-0.08432,
                    -0.079603 -0.242937,-0.11852 -0.479388,-0.11852c-0.115572,0 -0.234682,
                    0.0171 -0.35674,0.05307c-0.120879,0.037148 -0.225837,0.070758 -0.311926,
                    0.103779l0.118521,-0.488235c0.293647,-0.119699 0.574911,-0.222299 0.843204,
                    -0.307209c0.268291,-0.086089 0.521842,-0.128543 0.760652,-0.128543c0.431036,
                    0 0.7636,0.104959 0.997693,0.312517c0.232913,0.208147 0.350253,0.478797 0.350253,
                    0.811363c0,0.068989 -0.008255,0.190458 -0.024174,0.363815c-0.015921,
                    0.173947 -0.045994,0.332565 -0.089628,0.478209l-0.446368,1.580269c-0.036558,
                    0.126776 -0.068988,0.271831 -0.098472,0.433985c-0.028893,0.162156 -0.043043,
                    0.285983 -0.043043,0.369123c0,0.209916 0.046582,0.353202 0.140926,
                    0.429268c0.093164,0.076064 0.256498,0.114392 0.487643,0.114392c0.109086,
                    0 0.231144,-0.019459 0.369124,-0.057197c0.136799,-0.037737 0.23586,
                    -0.071349 0.298364,-0.100241l-0.119699,0.487643zm-0.079014,-6.414247c-0.208148,
                    0.193407 -0.45875,0.290109 -0.751808,0.290109c-0.292469,0 -0.54484,
                    -0.096702 -0.754756,-0.290109c-0.208737,-0.193406 -0.314285,-0.428678 -0.314285,
                    -0.703457c0,-0.274188 0.106138,-0.51005 0.314285,-0.705225c0.208148,
                    -0.195175 0.462287,-0.293058 0.754756,-0.293058c0.293058,0 0.54425,
                    0.097293 0.751808,0.293058c0.208146,0.195175 0.312516,0.431036 0.312516,
                    0.705225c0,0.275368 -0.10437,0.510051 -0.312516,0.703457z">
        </path>
    </symbol>
</svg>

<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Cache Control Header Options}}</h3>

    <form action="%%SUCURI.URL.Settings%%#headers" method="post">
        <div class="inside">
            <p>{{Please enable site caching on your WAF to use these settings. If you are a Sucuri client and require assistance, please <a href="https://docs.sucuri.net/billing/how-do-i-open-a-general-support-ticket/" target="_blank" rel="noopener">{{create a ticket}}</a> and reach out to the firewall team for support.}}</p>
            <p>{{Here you can see all the cache options available.}}</p>

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-table-fixed-layout sucuriscan-table-double-title sucuriscan-last-logins">
                <thead>
                <tr>
                    <th class="manage-column">{{Option}} </th>
                    <th class="manage-column">
                        {{max-age}}
                        <span class="sucuriscan-tooltip"
                              content="{{The max-age setting tells your browser how long, in seconds, it can keep a copy of a web page before it needs to check for a new version. This is the basic setting needed to make caching work on most devices.}}">
                            <svg><use xlink:href="#helper-svg"></use></svg>
                        </span>
                    </th>
                    <th class="manage-column">
                        {{s-maxage}}
                        <span class="sucuriscan-tooltip"
                              content="{{The s-maxage setting tells shared caches, like those used by multiple visitors or devices (such as CDNs or web accelerators), how long they can keep a copy of a web page. It allows you to control how often these shared caches update their content compared to private caches.}}">
                            <svg><use xlink:href="#helper-svg"></use></svg>
                        </span>
                    </th>
                    <th class="manage-column">
                        {{stale-if-error}}
                        <span class="sucuriscan-tooltip"
                              content="{{The stale-if-error setting allows a cached page to be served even after it has expired if the original web server returns an error. This helps keep your website available by showing an older version of the page instead of an error message. You need to set a private or shared cache duration to use this option, and it can be set for hours or days.}}">
                            <svg><use xlink:href="#helper-svg"></use></svg>
                        </span>
                    </th>
                    <th class="manage-column">
                        {{stale-while-revalidate}}
                        <span class="sucuriscan-tooltip"
                              content="{{The stale-while-revalidate setting lets shared caches serve an old version of a web page while they update the cached copy in the background. This improves loading times because visitors don’t have to wait for the updated content. Like stale-if-error, it requires a private or shared cache duration, and can be used together with stale-if-error. This setting is useful for ensuring quick page loads while keeping content reasonably fresh.}}">
                            <svg><use xlink:href="#helper-svg"></use></svg>
                        </span>
                    </th>
                    <th class="manage-column">
                        {{Pagination factor}}
                        <span class="sucuriscan-tooltip"
                              content="{{When this option is set, older pages will be cached for longer than newer pages (determined by page number). The configured pagination factor is added to the main maxage and s-maxage options. This allows less popular archives to be served as stale for longer.}}">
                            <svg><use xlink:href="#helper-svg"></use></svg>
                        </span>
                    </th>
                    <th class="manage-column">
                        {{Old age multiplier}}
                        <span class="sucuriscan-tooltip"
                              content="{{When this option is set, the max-age and s-maxage values will be multiplied by the number of years since the last edit or comment (the Last Modified time). This allows posts that were published a long time ago to be cached longer than newer posts.}}">
                            <svg><use xlink:href="#helper-svg"></use></svg>
                        </span>
                    </th>

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

        <div class="sucuriscan-double-box sucuriscan-hstatus sucuriscan-double-box-update sucuriscan-hstatus-%%SUCURI.CacheOptions.CacheControl%%"
             data-cy="sucuriscan_headers_cache_control">
            <p>
                <strong>{{Cache Control Header}}</strong> &mdash; <span>%%SUCURI.CacheOptions.Status%%</span><br/>
                {{WordPress by default does not come with cache control headers, used by WAFs and CDNs that are useful to both improve performance and reduce bandwidth and other resources demand on the hosting server. }}
            </p>

            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%"/>
            <input type="hidden" name="sucuriscan_update_cache_options" value="1"/>

            <div>
                <label><strong>{{Mode:}}</strong></label>

                <select name="sucuriscan_cache_options_mode" data-cy="sucuriscan_headers_cache_control_dropdown">
                    %%%SUCURI.CacheOptions.Modes%%%
                </select>

                <input type="submit" value="{{Submit}}" class="button button-primary"
                       data-cy="sucuriscan_headers_cache_control_submit_btn"/>
            </div>
        </div>
    </form>
</div>
