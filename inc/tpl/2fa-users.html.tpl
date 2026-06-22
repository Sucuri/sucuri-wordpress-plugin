<form action="%%SUCURI.URL.2FA%%" method="post" class="sucuriscan-2fa-users">
    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
    <input type="hidden" name="sucuriscan_update_twofactor_bulk" value="1" />

    <div class="inside">
    <div id="sucuriscan-2fa-filters">
        <div class="filter-container">
            <input type="search" id="sucuriscan-2fa-search"
                placeholder="{{Search by username, email or display name}}"
                data-cy="sucuriscan_twofactor_search" autocomplete="off" />
            <button type="button" id="sucuriscan-2fa-search-btn" class="button button-primary"
                data-cy="sucuriscan_twofactor_search_btn">{{Search}}</button>
            <button type="button" id="sucuriscan-2fa-search-clear" class="button button-secondary"
                data-cy="sucuriscan_twofactor_search_clear">{{Clear Filter}}</button>
        </div>
    </div>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-table-fixed-layout sucuriscan-last-logins sucuriscan-2fa-users-table">
            <thead>
                <tr>
                    <th class="manage-column" style="width:32px;"><input type="checkbox" id="sucuri-2fa-select-all" />
                    </th>
                    <th class="manage-column">{{User}}</th>
                    <th class="manage-column">{{Email}}</th>
                    <th class="manage-column">{{Status}}</th>
                </tr>
            </thead>
            <tbody class="sucuriscan-2fa-users-response" data-cy="sucuriscan_twofactor_users_response">
                <tr>
                    <td colspan="4"><em>{{Loading...}}</em></td>
                </tr>
            </tbody>
        </table>

        <div class="sucuriscan-clearfix sucuriscan-pagination-panel sucuriscan-2fa-pagination-panel sucuriscan-hidden">
            <ul class="sucuriscan-pull-left sucuriscan-pagination sucuriscan-2fa-pagination">
                <!-- Populated via JavaScript -->
            </ul>

            <div class="sucuriscan-pull-right sucuriscan-2fa-pagination-loading">
                <!-- Populated via JavaScript -->
            </div>
        </div>
    </div>

    <div class="sucuriscan-double-box sucuriscan-double-box-update sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.TwoFactor.Status%%"
        id="sucuriscan-2fa-bulk-control" data-cy="sucuriscan_twofactor_bulk_control">
        <p>
            <strong>Two-Factor Authentication</strong> — <span class="sucuriscan-2fa-status-text">{{%%SUCURI.TwoFactor.StatusText%%}}</span>
            <br />
            <span>{{Select users above to activate, disable, or reset their two-factor authentication settings.}}</span>
        </p>
        <div>
            <select name="sucuriscan_twofactor_bulk_action" data-cy="sucuriscan_twofactor_bulk_dropdown"
                class="sucuriscan-twofactor-bulk-select">
                %%%SUCURI.BulkOptions%%%
            </select>
            <input type="submit" value="{{Apply}}" class="button button-primary"
                data-cy="sucuriscan_twofactor_bulk_submit_btn" />
        </div>
    </div>
</form>

<script type="text/javascript">
    /* global jQuery */
    jQuery(function ($) {
        var AJAX = '%%SUCURI.AjaxURL.Dashboard%%';
        var NONCE = '%%SUCURI.PageNonce%%';
        var currentSearch = '';
        var activeRequest = null;

        var $response = $('.sucuriscan-2fa-users-response');
        var $paginationPanel = $('.sucuriscan-2fa-pagination-panel');
        var $pagination = $('.sucuriscan-2fa-pagination');
        var $loading = $('.sucuriscan-2fa-pagination-loading');
        var $selectAll = $('#sucuri-2fa-select-all');

        function bindSelectAll() {
            // Per-page selection only: the master checkbox toggles the rows
            // currently rendered in the (single) visible page.
            $selectAll.prop('checked', false).off('change.sucuri2fa').on('change.sucuri2fa', function () {
                $response.find('input[name="sucuriscan_twofactor_users[]"]').prop('checked', this.checked);
            });
        }

        function loadUsers(page, search) {
            page = (page !== undefined && page > 0) ? page : 1;
            currentSearch = (typeof search === 'string') ? search : currentSearch;

            var url = AJAX + '&paged=' + encodeURIComponent(page);

            if (currentSearch !== '') {
                url += '&twofactor_search=' + encodeURIComponent(currentSearch);
            }

            $response.html('<tr><td colspan="4"><em>{{Loading...}}</em></td></tr>');
            $loading.html('{{Loading...}}');

            if (activeRequest) {
                activeRequest.abort();
            }

            activeRequest = $.post(url, {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: NONCE,
                form_action: 'get_twofactor_users'
            }, function (data) {
                activeRequest = null;
                $loading.html('');

                if (!data || typeof data !== 'object') {
                    $response.html('<tr><td colspan="4">{{No users found.}}</td></tr>');
                    return;
                }

                if (data.content && data.content !== '') {
                    $response.html(data.content);
                } else {
                    $response.html('<tr><td colspan="4">{{No users found.}}</td></tr>');
                }

                if (data.pagination && data.pagination !== '') {
                    $pagination.html(data.pagination);
                    $paginationPanel.removeClass('sucuriscan-hidden');
                } else {
                    $pagination.html('');
                    $paginationPanel.addClass('sucuriscan-hidden');
                }

                bindSelectAll();
            }, 'json').fail(function (xhr) {
                if (xhr.statusText === 'abort') {
                    return; // superseded by a newer request — do nothing
                }
                activeRequest = null;
                $loading.html('');
                $response.html('<tr><td colspan="4">{{Could not load the list of users.}}</td></tr>');
            });
        }

        // Pagination clicks (delegated; links are injected via AJAX).
        $pagination.on('click', '.sucuriscan-pagination-link', function (event) {
            event.preventDefault();
            loadUsers($(this).attr('data-page'));
        });

        // Search handlers.
        $('#sucuriscan-2fa-search-btn').on('click', function () {
            loadUsers(1, $.trim($('#sucuriscan-2fa-search').val()));
        });

        $('#sucuriscan-2fa-search').on('keydown', function (event) {
            if (event.which === 13) {
                event.preventDefault();
                loadUsers(1, $.trim($(this).val()));
            }
        });

        $('#sucuriscan-2fa-search-clear').on('click', function () {
            $('#sucuriscan-2fa-search').val('');
            loadUsers(1, '');
        });

        // Initial load.
        loadUsers(1, '');
    });
</script>
