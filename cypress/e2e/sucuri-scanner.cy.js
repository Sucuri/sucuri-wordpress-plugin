const adminUser = { login: Cypress.env('wp_user') || 'admin', pass: Cypress.env('wp_pass') || 'password' };

const testAdminUser = {
    login: Cypress.env('test_admin_user') || 'sucuri-admin',
    pass: Cypress.env('test_admin_pass') || 'password'
};

const extraUser = {
    login: Cypress.env('extra_user') || 'sucuri',
    pass: Cypress.env('extra_user_pass') || 'password'
};

function go2faPage() {
    cy.visit('/wp-admin/admin.php?page=sucuriscan_2fa');
}

function setModeAllUsers(mode = 'activate_all') {
    go2faPage();

    cy.get('[data-cy=sucuriscan_twofactor_bulk_control] select').select(mode);
    cy.get('[data-cy=sucuriscan_twofactor_bulk_control] input[type=submit]').click();
    cy.get('.sucuriscan-alert, .updated, .notice').should('contain.text', 'Two-Factor');
}

function setModeSelectedUsersFor(users, mode = 'activate_selected') {
    go2faPage();

    cy.get('[data-cy="twofactor-user-checkbox-1"]').check({ force: true });
    cy.get('[data-cy=sucuriscan_twofactor_bulk_control] select').select('deactivate_all', { force: true });
    cy.get('[data-cy=sucuriscan_twofactor_bulk_control] input[type=submit]').click();

    users.forEach((u) => {
        cy.contains('table tr', u.login, { matchCase: false })
            .find('input[name="sucuriscan_twofactor_users[]"]').check({ force: true });
    });

    cy.get('[data-cy=sucuriscan_twofactor_bulk_control] select').select(mode);
    cy.get('[data-cy=sucuriscan_twofactor_bulk_control] input[type=submit]').click();
}

function resetForSelectedUsers(users) {
    go2faPage();
    users.forEach((u) => {
        cy.contains('table tr', u.login, { matchCase: false })
            .find('input[name="sucuriscan_twofactor_users[]"]').check({ force: true });
    });
    cy.get('[data-cy=sucuriscan_twofactor_bulk_control] select').select('reset_selected');
    cy.get('[data-cy=sucuriscan_twofactor_bulk_control] input[type=submit]').click();
}


function completeSetupWithGeneratedCode() {
    return extractSecretFromSetupPage().then((secret) => {
        return cy.task('totp', { secret }).then((code) => {
            expect(code).to.match(/^\d{6}$/);
            finishWithCode(code);

            cy.url().should('contain', '/wp-admin/');

            return cy.wrap(secret, { log: false });
        });
    });
}

function loginAndExpect2FA(username, password, expected = 'verify', { fresh = true } = {}) {
    if (fresh) {
        Cypress.session.clearAllSavedSessions();
        cy.clearCookies();
    }

    cy.visit('/wp-login.php');
    cy.get('#user_login').clear().wait(100).type(username);
    cy.get('#user_pass').clear().wait(100).type(password);
    cy.get('#wp-submit').click();

    if (expected === 'setup') {
        cy.url().should('include', 'action=sucuri-2fa-setup');
        cy.contains('Set up Two-Factor Authentication');
    } else {
        cy.url().should('include', 'action=sucuri-2fa');
        cy.contains('Two-Factor Authentication');
    }

}

function finishWithCode(code) {
    cy.get('[name="sucuriscan_totp_code"]').clear().type(code);
    cy.get('#sucuriscan-totp-submit').click();
}

function extractSecretFromSetupPage() {
    return cy.get('code').first().invoke('text').then((txt) => txt.trim());
}

beforeEach(() => {
    cy.session('Login to WordPress', () => {
        cy.login();
    });

});

describe("Run e2e tests", () => {
    it("WAF API Key modal appears only on Dashboard, dismisses once, and CTA navigates to WAF", () => {
        const wafModalSelector = '.sucuriscan-activate-your-waf-key-modal-modal';

        cy.clearCookies();

        cy.login(adminUser.login, adminUser.pass);

        cy.setCookie('sucuriscan_waf_dismissed', '0');

        cy.visit('/wp-admin/admin.php?page=sucuriscan');
        cy.setCookie('sucuriscan_waf_dismissed', '0');

        cy.get(wafModalSelector).should('exist');

        cy.get('[data-cy="sucuriscan-waf-modal-main-action"]').click();
        cy.url().should('include', 'page=sucuriscan_firewall');

        cy.visit('/wp-admin/admin.php?page=sucuriscan_2fa');
        cy.reload();
        cy.get(wafModalSelector).should('not.exist');
        cy.visit('/wp-admin/admin.php?page=sucuriscan_firewall');
        cy.reload();
        cy.get(wafModalSelector).should('not.exist');
        cy.visit('/wp-admin/admin.php?page=sucuriscan_settings');
        cy.reload();
        cy.get(wafModalSelector).should('not.exist');

        cy.visit('/wp-admin/admin.php?page=sucuriscan');
        cy.get(wafModalSelector).should('not.exist');
    });

    it("can change malware scan target", () => {
        const testDomain = "sucuri.net";

        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#apiservice");

        cy.get("[data-cy=sucuriscan_sitecheck_target_input]").type(testDomain);
        cy.get("[data-cy=sucuriscan_sitecheck_target_submit]").click();

        cy.reload();

        cy.get("[data-cy=sucuriscan_sitecheck_target]").contains(testDomain);
    });

    it("can reset logs, hardening and settings", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#general");

        cy.get("[data-cy=sucuriscan_reset_checkbox]").check();

        cy.get("[data-cy=sucuriscan_reset_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "Local security logs, hardening and settings were deleted",
        );
    });

    it("can update ip address discovery", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#general");

        cy.get("[data-cy=sucuriscan_ip_address_discovery_toggle_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The status of the DNS lookups for the reverse proxy detection has been changed",
        );

        cy.get("[data-cy=sucuriscan_ip_address_discovery_toggle_submit]").contains(
            "Enable",
        );
        cy.get("[data-cy=sucuriscan_ip_address_discovery_toggle_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The status of the DNS lookups for the reverse proxy detection has been changed",
        );
        cy.get("[data-cy=sucuriscan_ip_address_discovery_toggle_submit]").contains(
            "Disable",
        );

        cy.get("[data-cy=sucuriscan_addr_header_select]").select("HTTP_X_REAL_IP");
        cy.get("[data-cy=sucuriscan_addr_header_proceed]").click();

        cy.get(".sucuriscan-alert").contains(
            "HTTP header was set to HTTP_X_REAL_IP",
        );
        cy.get(".sucuriscan-alert").contains(
            "Reverse proxy support was set to enabled",
        );
    });

    it("can update reverse proxy setting", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#general");

        cy.get("[data-cy=sucuriscan_reverse_proxy_toggle]").click();

        cy.get(".sucuriscan-alert").contains(
            "Reverse proxy support was set to disabled",
        );
        cy.get(".sucuriscan-alert").contains("HTTP header was set to REMOTE_ADDR");

        cy.get("[data-cy=sucuriscan_reverse_proxy_toggle]").click();

        cy.get(".sucuriscan-alert").contains(
            "Reverse proxy support was set to enabled",
        );
        cy.get(".sucuriscan-alert").contains(
            "HTTP header was set to HTTP_X_SUCURI_CLIENTIP",
        );
    });

    it("can delete datastore files", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#general");

        cy.get('input[value="sucuri-integrity.php"]').click();
        cy.get("[data-cy=sucuriscan_general_datastore_delete_button]").click();
        cy.get(".sucuriscan-alert").contains("1 out of 1 files have been deleted.");

        cy.get("[data-cy=sucuriscan_general_datastore_delete_checkbox]").click();
        cy.get("[data-cy=sucuriscan_general_datastore_delete_button]").click();
        cy.get(".sucuriscan-alert").contains("files have been deleted.");
    });

    it("can import JSON settings", () => {
        const jsonPayload =
            '{"sucuriscan_addr_header":"REMOTE_ADDR","sucuriscan_api_protocol":"https","sucuriscan_api_service":"enabled","sucuriscan_cloudproxy_apikey":"","sucuriscan_diff_utility":"disabled","sucuriscan_dns_lookups":"enabled","sucuriscan_email_subject":"Sucuri Alert, :domain, :event, :remoteaddr","sucuriscan_emails_per_hour":5,"sucuriscan_ignored_events":"","sucuriscan_lastlogin_redirection":"enabled","sucuriscan_maximum_failed_logins":30,"sucuriscan_notify_available_updates":"disabled","sucuriscan_notify_bruteforce_attack":"disabled","sucuriscan_notify_failed_login":"disabled","sucuriscan_notify_plugin_activated":"enabled","sucuriscan_notify_plugin_change":"enabled","sucuriscan_notify_plugin_deactivated":"disabled","sucuriscan_notify_plugin_deleted":"disabled","sucuriscan_notify_plugin_installed":"disabled","sucuriscan_notify_plugin_updated":"disabled","sucuriscan_notify_post_publication":"enabled","sucuriscan_notify_scan_checksums":"disabled","sucuriscan_notify_settings_updated":"enabled","sucuriscan_notify_success_login":"disabled","sucuriscan_notify_theme_activated":"enabled","sucuriscan_notify_theme_deleted":"disabled","sucuriscan_notify_theme_editor":"enabled","sucuriscan_notify_theme_installed":"disabled","sucuriscan_notify_theme_updated":"disabled","sucuriscan_notify_to":"wordpress@example.com","sucuriscan_notify_user_registration":"disabled","sucuriscan_notify_website_updated":"disabled","sucuriscan_notify_widget_added":"disabled","sucuriscan_notify_widget_deleted":"disabled","sucuriscan_prettify_mails":"disabled","sucuriscan_revproxy":"enabled","sucuriscan_selfhosting_fpath":"","sucuriscan_selfhosting_monitor":"disabled","sucuriscan_use_wpmail":"enabled","trusted_ips":[]}';

        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#general");

        cy.get("[data-cy=sucuriscan_import_export_settings_textarea]").type(
            jsonPayload,
            { parseSpecialCharSequences: false },
        );
        cy.get("[data-cy=sucuriscan_import_export_settings_checkbox]").click();
        cy.get("[data-cy=sucuriscan_import_export_settings_submit]").click();

        cy.reload();

        cy.get("[data-cy=sucuriscan_addr_header_select]").contains("REMOTE_ADDR");
    });

    it("can update timezone setting", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#general");

        cy.get("[data-cy=sucuriscan_timezone_select]").select("UTC-07.00");
        cy.get("[data-cy=sucuriscan_timezone_submit]").click();
        cy.get(".sucuriscan-alert").contains(
            "The timezone for the date and time in the audit logs has been changed",
        );
    });

    it("can deactivate sucuri-scanner", () => {
        cy.visit("/wp-admin/plugins.php");

        cy.get("[data-slug=sucuri-scanner] .deactivate").click();

        cy.contains("Plugin deactivated.");
    });

    it("can activate sucuri-scanner", () => {
        cy.visit("/wp-admin/plugins.php");

        cy.get("[data-slug=sucuri-scanner] .activate").click();

        cy.contains("Plugin activated.");
    });

    it("can modify scheduled tasks", () => {
        cy.visit("wp-admin/admin.php?page=sucuriscan_settings#scanner");

        cy.get('input[value="wp_update_plugins"]').click();
        cy.get("[data-cy=sucuriscan_cronjobs_select]").select(
            "Quarterly (every 7776000 seconds)",
        );
        cy.get("[data-cy=sucuriscan_cronjobs_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "1 tasks has been re-scheduled to run quarterly.",
        );

        cy.get("[data-cy=sucuriscan_row_wp_update_plugins]")
            .find("td:nth-child(3)")
            .contains("quarterly");
    });

    it("can ignore and unignore false positives (integrity diff utility)", () => {
        cy.intercept("POST", "/wp-admin/admin-ajax.php?page=sucuriscan", (req) => {
            if (req.body.includes("check_wordpress_integrity")) {
                req.alias = "integrityCheck";
            }
        });

        cy.visit("/wp-admin/admin.php?page=sucuriscan");

        cy.wait("@integrityCheck");

        cy.get("[data-cy=sucuriscan_integrity_files_per_page]").select("1000");

        cy.get('input[value="added@wp-config-test.php"]').click();
        cy.get("[data-cy=sucuriscan_integrity_incorrect_checkbox]").click();
        cy.get("[data-cy=sucuriscan_integrity_incorrect_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "1 out of 1 files were successfully processed.",
        );

        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#scanner");

        cy.get("[data-cy=sucuriscan_integrity_diff_false_positive_table]").contains(
            "wp-config-test.php",
        );
        cy.get('input[value="wp-config-test.php"]').click();
        cy.get("[data-cy=sucuriscan_integrity_diff_false_positive_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The selected files have been successfully processed.",
        );
        cy.get("[data-cy=sucuriscan_integrity_diff_false_positive_table]").contains(
            "no data available",
        );

        cy.visit("/wp-admin/admin.php?page=sucuriscan");

        cy.wait("@integrityCheck");

        cy.get("[data-cy=sucuriscan_integrity_list_table]").contains(
            "wp-config-test.php",
        );
    });

    it("can use new dropdown in integrity diff utility", () => {
        cy.intercept("POST", "/wp-admin/admin-ajax.php?page=sucuriscan", (req) => {
            if (req.body.includes("check_wordpress_integrity")) {
                req.alias = "integrityCheck";
            }
        });

        cy.visit("/wp-admin/admin.php?page=sucuriscan");

        cy.wait("@integrityCheck");

        cy.get(
            ".sucuriscan-pagination-integrity .sucuriscan-pagination-link",
        ).should("have.length", 7);

        cy.get("#sucuriscan_integrity_files_per_page").select("200");
        cy.get(".sucuriscan-is-loading").contains("Loading...");
        cy.get(".sucuriscan-integrity-filepath").should("have.length", 105);

        cy.get("#sucuriscan_integrity_files_per_page").select("15");
        cy.get(".sucuriscan-is-loading").contains("Loading...");
        cy.get(".sucuriscan-integrity-filepath").should("have.length", 15);

        cy.get("#sucuriscan_integrity_files_per_page").select("50");
        cy.get(".sucuriscan-is-loading").contains("Loading...");
        cy.get(".sucuriscan-integrity-filepath").should("have.length", 50);

        cy.get("#cb-select-all-1").click();

        cy.get("[data-cy=sucuriscan_integrity_incorrect_checkbox]").click();
        cy.get("[data-cy=sucuriscan_integrity_incorrect_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "50 out of 50 files were successfully processed.",
        );

        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#scanner");

        cy.get('*[class^="sucuriscan-integrity"]').should("have.length", 50);

        cy.get(
            "[data-cy=sucuriscan_integrity_diff_false_positive_table] #cb-select-all-1",
        ).click();

        cy.get("[data-cy=sucuriscan_integrity_diff_false_positive_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The selected files have been successfully processed.",
        );
        cy.get("[data-cy=sucuriscan_integrity_diff_false_positive_table]").contains(
            "no data available",
        );

        cy.visit("/wp-admin/admin.php?page=sucuriscan");

        cy.wait("@integrityCheck");

        cy.get("#sucuriscan_integrity_files_per_page").select("200");
        cy.get(".sucuriscan-is-loading").contains("Loading...");
        cy.get(".sucuriscan-integrity-filepath").should("have.length", 105);
    });

    it("can use pagination in integrity diff utility", () => {
        cy.intercept("POST", "/wp-admin/admin-ajax.php?page=sucuriscan", (req) => {
            if (req.body.includes("check_wordpress_integrity")) {
                req.alias = "integrityCheck";
            }
        });

        cy.visit("/wp-admin/admin.php?page=sucuriscan");

        cy.wait("@integrityCheck");

        cy.get(
            ".sucuriscan-pagination-integrity .sucuriscan-pagination-link",
        ).should("have.length", 7);

        cy.get(".sucuriscan-pagination-integrity [data-page=2]").click();

        cy.get("[data-cy=sucuriscan_integrity_list_table]").contains(
            "wp-test-file-21.php",
        );

        cy.get("#cb-select-all-1").click();
        cy.get("[data-cy=sucuriscan_integrity_incorrect_checkbox]").click();
        cy.get("[data-cy=sucuriscan_integrity_incorrect_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "15 out of 15 files were successfully processed.",
        );

        cy.get(".sucuriscan-pagination-integrity [data-page=2]").click();

        cy.wait("@integrityCheck");

        cy.get("[data-cy=sucuriscan_integrity_list_table]").contains(
            "wp-test-file-35.php",
        );

        cy.get(".sucuriscan-pagination-integrity [data-page=6]").click();

        cy.get("[data-cy=sucuriscan_integrity_list_table]").contains(
            "wp-test-file-99.php",
        );
    });

    it("can activate and deactivate the WordPress integrity diff utility", () => {
        cy.visit("wp-admin/admin.php?page=sucuriscan_settings#scanner");

        cy.get(
            "[data-cy=sucuriscan_scanner_integrity_diff_utility_toggle]",
        ).click();
        cy.get(".sucuriscan-alert").contains(
            "The status of the integrity diff utility has been changed",
        );
        cy.get(
            "[data-cy=sucuriscan_scanner_integrity_diff_utility_toggle]",
        ).contains("Disable");

        cy.get(
            "[data-cy=sucuriscan_scanner_integrity_diff_utility_toggle]",
        ).click();
        cy.get(".sucuriscan-alert").contains(
            "The status of the integrity diff utility has been changed",
        );
        cy.get(
            "[data-cy=sucuriscan_scanner_integrity_diff_utility_toggle]",
        ).contains("Enable");
    });

    it("can ignore files and folders during the scans", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#scanner");

        cy.get("[data-cy=sucuriscan_ignore_files_folders_input]").type(
            "sucuri-images",
        );
        cy.get("[data-cy=sucuriscan_ignore_files_folders_ignore_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "Selected files have been successfully processed.",
        );
        cy.get("[data-cy=sucuriscan_ignore_files_folders_table]").contains(
            "sucuri-images",
        );

        cy.get('input[value="sucuri-images"]').click();
        cy.get("[data-cy=sucuriscan_ignore_files_folders_unignore_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "Selected files have been successfully processed.",
        );
        cy.get("[data-cy=sucuriscan_ignore_files_folders_table]").contains(
            "no data available",
        );
    });

    it.skip("can toggle hardening options", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_hardening_prevention");

        cy.get("input[name=sucuriscan_hardening_firewall]").click();
        cy.get(".sucuriscan-alert").contains(
            "The firewall is a premium service that you need purchase at - Sucuri Firewall",
        );

        cy.get("input[name=sucuriscan_hardening_wpuploads]").click();
        cy.get(".sucuriscan-alert").contains(
            "Hardening applied to the uploads directory",
        );
        cy.get("input[name=sucuriscan_hardening_wpuploads_revert]").click();
        cy.get(".sucuriscan-alert").contains(
            "Hardening reverted in the uploads directory",
        );

        cy.get("input[name=sucuriscan_hardening_wpcontent]").click();
        cy.get(".sucuriscan-alert").contains(
            "Hardening applied to the content directory",
        );
        cy.get("input[name=sucuriscan_hardening_wpcontent_revert]").click();
        cy.get(".sucuriscan-alert").contains(
            "Hardening reverted in the content directory",
        );

        cy.get("input[name=sucuriscan_hardening_wpincludes]").click();
        cy.get(".sucuriscan-alert").contains(
            "Hardening applied to the library directory",
        );
        cy.get("input[name=sucuriscan_hardening_wpincludes_revert]").click();
        cy.get(".sucuriscan-alert").contains(
            "Hardening reverted in the library directory",
        );

        cy.get("input[name=sucuriscan_hardening_fileeditor]").click();
        cy.get(".sucuriscan-alert").contains(
            "Hardening applied to the plugin and theme editor",
        );
        cy.get("input[name=sucuriscan_hardening_fileeditor_revert]").click();
        cy.get(".sucuriscan-alert").contains(
            "Hardening reverted in the plugin and theme editor",
        );

        cy.get("input[name=sucuriscan_hardening_autoSecretKeyUpdater]").click();
        cy.get(".sucuriscan-alert").contains(
            'Automatic Secret Keys Updater enabled. The default frequency is "Weekly"',
        );
        cy.get(
            "input[name=sucuriscan_hardening_autoSecretKeyUpdater_revert]",
        ).click();
        cy.get(".sucuriscan-alert").contains(
            "Automatic Secret Keys Updater disabled.",
        );
    });

    it("cannot add the same file twice to the allowlist", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_hardening_prevention");

        cy.get("[data-cy=sucuriscan_hardening_allowlist_input]").type(
            "test-1/testing.php",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_select]").select(
            "/var/www/html/wp-includes",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_submit]").click();
        cy.get(".sucuriscan-alert").contains("The file has been allowed");

        cy.get("[data-cy=sucuriscan_hardening_allowlist_input]").type(
            "test-1/testing.php",
        );

        cy.get("[data-cy=sucuriscan_hardening_allowlist_select]").select(
            "/var/www/html/wp-includes",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_submit]").click();
        cy.get(".sucuriscan-alert").contains("File is already in the allowlist");
    });

    it("can remove legacy rules from allow blocked PHP files", () => {
        cy.visit("/wp-content/archive-legacy.php").contains("Hello, world!");

        cy.visit("/wp-admin/admin.php?page=sucuriscan_hardening_prevention");

        cy.get(".sucuriscan-hardening-allowlist-table")
            .contains("archive-legacy.php")
            .parent()
            .find('input[type="checkbox"]')
            .click();

        // The /*/ means is using the legacy rule.
        cy.get(".sucuriscan-hardening-allowlist-table").contains(
            "/var/www/html/wp-content/.*/archive-legacy.php",
        );

        cy.get("[data-cy=sucuriscan_hardening_remove_allowlist_submit]").click();

        cy.get(".sucuriscan-alert").contains("Selected files have been removed");

        // Now it should be a 403: Forbidden. Check 403 status code.
        cy.request(
            {
                url: "/wp-content/archive-legacy.php",
                failOnStatusCode: false,
            },
            (response) => {
                expect(response.status).to.eq(403);
                expect(response.body).contains("Forbidden");
            },
        );
    });

    it("can add and remove from allowlist of blocked PHP files", () => {
        cy.request(
            {
                url: "/wp-content/archive.php",
                failOnStatusCode: false,
            },
            (response) => {
                expect(response.status).to.eq(403);
                expect(response.body).contains("Forbidden");
            },
        );

        cy.visit("/wp-admin/admin.php?page=sucuriscan_hardening_prevention");

        cy.get("[data-cy=sucuriscan_hardening_allowlist_input]").type(
            "archive.php",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_select]").select(
            "/var/www/html/wp-content",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_submit]").click();

        cy.get(".sucuriscan-alert").contains("The file has been allowed");

        cy.request(
            {
                url: "/wp-content/archive.php",
            },
            (response) => {
                expect(response.status).to.eq(200);
                expect(response.body).contains("Hello, world!");
            },
        );

        cy.get(".sucuriscan-hardening-allowlist-table")
            .contains("archive.php")
            .parent()
            .find('input[type="checkbox"]')
            .click();

        cy.get("[data-cy=sucuriscan_hardening_remove_allowlist_submit]").click();

        cy.get(".sucuriscan-alert").contains("Selected files have been removed");

        cy.request(
            {
                url: "/wp-content/archive.php",
                failOnStatusCode: false,
            },
            (response) => {
                expect(response.status).to.eq(403);
                expect(response.body).contains("Forbidden");
            },
        );
    });

    it("Can add and remove multiple files from the allowlist of blocked PHP files", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_hardening_prevention");

        cy.get("[data-cy=sucuriscan_hardening_allowlist_input]").type(
            "/test-1/test-1.php",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_select]").select(
            "/var/www/html/wp-includes",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_submit]").click();
        cy.get(".sucuriscan-alert").contains("The file has been allowed");

        cy.get("[data-cy=sucuriscan_hardening_allowlist_input]").type(
            "test-1/test-2.php",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_select]").select(
            "/var/www/html/wp-includes",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_submit]").click();
        cy.get(".sucuriscan-alert").contains("The file has been allowed");

        cy.get("[data-cy=sucuriscan_hardening_allowlist_input]").type(
            "test-1/test-3.php",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_select]").select(
            "/var/www/html/wp-includes",
        );
        cy.get("[data-cy=sucuriscan_hardening_allowlist_submit]").click();
        cy.get(".sucuriscan-alert").contains("The file has been allowed");

        cy.get("[data-cy=sucuriscan_hardening_select_all]").click();

        cy.get("[data-cy=sucuriscan_hardening_remove_allowlist_submit]").click();

        cy.get(".sucuriscan-alert").contains("Selected files have been removed");
    });

    it("can update the secret keys", () => {
        cy.visit("http://localhost:8889/wp-admin/admin.php?page=sucuriscan_post_hack_actions");

        cy.get("[data-cy=sucuriscan_security_keys_checkbox]").click();
        cy.get("[data-cy=sucuriscan_security_keys_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "Secret keys updated successfully (summary of the operation bellow).",
        );

        cy.wait(3000);

        cy.reload();

        //TODO: Double check this.
        //cy.url().should('contain', 'wp-login.php');

        Cypress.session.clearAllSavedSessions();
        cy.login();

        cy.visit("http://localhost:8889/wp-admin/admin.php?page=sucuriscan_post_hack_actions");

        cy.get("[data-cy=sucuriscan_security_keys_autoupdater]").contains(
            "Automatic Secret Keys Updater — Disabled",
        );
        cy.get("[data-cy=sucuriscan_security_keys_autoupdater_select]").select(
            "Quarterly",
        );
        cy.get("[data-cy=sucuriscan_security_keys_autoupdater_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "Automatic Secret Keys Updater enabled.",
        );
        cy.get("[data-cy=sucuriscan_security_keys_autoupdater]").contains(
            "Automatic Secret Keys Updater — Enabled",
        );

        cy.get("[data-cy=sucuriscan_security_keys_autoupdater_select]").select(
            "Disabled",
        );
        cy.get("[data-cy=sucuriscan_security_keys_autoupdater_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "Automatic Secret Keys Updater disabled.",
        );
    });

    it.skip("can reset installed plugins", () => {
        cy.visit(
            "/wp-admin/admin.php?page=sucuriscan_settings&sucuriscan_lastlogin=1#posthack",
        );

        cy.get('input[value="akismet/akismet.php"]').click();
        cy.get("[data-cy=sucuriscan_reset_plugins_submit]").click();

        cy.get("[data-cy=sucuriscan_reset_plugin_response]").contains("Loading");

        cy.wait(2000);

        cy.get("[data-cy=sucuriscan_reset_plugin_response]").contains("Installed");
    });

    it("can modify alerts recipients", () => {
        cy.visit(
            "/wp-admin/admin.php?page=sucuriscan_settings&sucuriscan_lastlogin=1#alerts",
        );

        cy.get('input[value="wordpress@example.com"]').click();
        cy.get("[data-cy=sucuriscan_alerts_test_recipient_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "A test alert was sent to your email, check your inbox",
        );

        cy.get("[data-cy=sucuriscan_alerts_recipient_input]").type(
            "admin@sucuri.net",
        );
        cy.get("[data-cy=sucuriscan_alerts_recipient_add_email_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The email alerts will be sent to: admin@sucuri.net",
        );

        cy.get('input[value="admin@sucuri.net"]').click();
        cy.get("[data-cy=sucuriscan_alerts_delete_recipient_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "These emails will stop receiving alerts: admin@sucuri.net",
        );

        cy.get('input[value="wordpress@example.com"]').click();
        cy.get("[data-cy=sucuriscan_alerts_test_recipient_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "A test alert was sent to your email, check your inbox",
        );
    });

    it("can modify trusted ip addresses", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#alerts");

        cy.get("[data-cy=sucuriscan_trusted_ip_table]").contains(
            "no data available",
        );

        cy.get("[data-cy=sucuriscan_trusted_ip_input]").type("182.190.190.0/24");
        cy.get("[data-cy=sucuriscan_trusted_ip_add_ip_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "Events generated from this IP will be ignored: 182.190.190.0/24",
        );

        cy.get("[data-cy=sucuriscan_trusted_ip_input]").type("182.190.190.0/24");
        cy.get("[data-cy=sucuriscan_trusted_ip_add_ip_submit]").click();

        cy.get(".error").contains("The IP specified address was already added.");

        cy.get("[data-cy=sucuriscan_trusted_ip_table]")
            .find("td:nth-child(2)")
            .contains("182.190.190.0");
        cy.get("[data-cy=sucuriscan_trusted_ip_table]")
            .find("td:nth-child(3)")
            .contains("182.190.190.0/24");

        cy.get('input[name="sucuriscan_del_trust_ip[]"]').click();
        cy.get("[data-cy=sucuriscan_trusted_ip_delete_ip_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The selected IP addresses were successfully deleted.",
        );
    });

    it("can modify alert subject", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#alerts");

        cy.get('input[value="Sucuri Alert, :event, :hostname"]').click();
        cy.get("[data-cy=sucuriscan_alerts_subject_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The email subject has been successfully updated",
        );

        cy.get('input[value="custom"]').click();
        cy.get("[data-cy=sucuriscan_alerts_subject_input]").type(
            "Security alert: :event",
        );
        cy.get("[data-cy=sucuriscan_alerts_subject_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The email subject has been successfully updated",
        );
    });

    it("can update max alerts per hour", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#alerts");

        cy.get("[data-cy=sucuriscan_alerts_per_hour_select]").select(
            "Maximum 160 per hour",
        );
        cy.get("[data-cy=sucuriscan_alerts_per_hour_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The maximum number of alerts per hour has been updated",
        );
    });

    it("can update value after a brute force attack is considered", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#alerts");

        cy.get("[data-cy=sucuriscan_max_failed_logins_select]").select(
            "480 failed logins per hour",
        );
        cy.get("[data-cy=sucuriscan_max_failed_logins_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The plugin will assume that your website is under a brute-force attack after 480 failed logins are detected during the same hour",
        );
    });

    it("can update the events that fire security alerts", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#alerts");

        cy.get('input[name="sucuriscan_notify_plugin_deleted"][value="1"]').click();
        cy.get("[data-cy=sucuriscan_save_alert_events_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The alert settings have been updated",
        );

        cy.get('input[name="sucuriscan_notify_plugin_deleted"][value="1"]').should(
            "have.attr",
            "checked",
            "checked",
        );

        cy.get('input[name="sucuriscan_notify_plugin_deleted"][value="1"]').click();
        cy.get("[data-cy=sucuriscan_save_alert_events_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The alert settings have been updated",
        );

        cy.get('input[name="sucuriscan_notify_plugin_deleted"][value="1"]').should(
            "not.have.attr",
            "checked",
        );
    });

    it("can update alerts per post type", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#alerts");

        const custom_post_type = "new_sucuri_post_type";

        cy.get("[data-cy=sucuriscan_alerts_post_type_input]").type(
            custom_post_type,
        );
        cy.get("[data-cy=sucuriscan_alerts_post_type_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "Post-type has been successfully ignored.",
        );

        cy.reload();

        cy.get(
            "[data-cy=sucuriscan_alerts_post_type_toggle_post_type_list]",
        ).click();

        cy.get(`input[value="${custom_post_type}"]`).should(
            "not.have.attr",
            "checked",
        );

        cy.get("[data-cy=sucuriscan_alerts_post_type_input]").type(
            custom_post_type,
        );
        cy.get("[data-cy=sucuriscan_alerts_post_type_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "The post-type is already being ignored (duplicate).",
        );

        cy.get(
            "[data-cy=sucuriscan_alerts_post_type_toggle_post_type_list]",
        ).click();
        cy.get('input[value="nav_menu_item"]').click();
        cy.get("[data-cy=sucuriscan_alerts_post_type_save_submit]").click();

        cy.get(".sucuriscan-alert").contains(
            "List of monitored post-types has been updated.",
        );
    });

    it("can toggle api service communication", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#apiservice");

        cy.get("[data-cy=sucuriscan_api_status_toggle]").click();

        cy.get("[data-cy=sucuriscan_api_status_toggle]").contains("Enable");

        cy.get(".sucuriscan-alert").contains(
            "The status of the API service has been changed",
        );

        cy.get("[data-cy=sucuriscan_api_status_toggle]").click();

        cy.get(".sucuriscan-alert").contains(
            "The status of the API service has been changed",
        );

        cy.get("[data-cy=sucuriscan_api_status_toggle]").contains("Disable");
    });

    it("can update the wordpress checksum api ", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#apiservice");

        cy.get("[data-cy=sucuriscan_wordpress_checksum_api_input]").type(
            "https://api.wordpress.org/core/checksums/1.0/?version=5.5.1&locale=es_ES",
        );
        cy.get("[data-cy=sucuriscan_wordpress_checksum_api_submit]").click();

        cy.get(".updated").contains(
            "The URL to retrieve the WordPress checksums has been changed",
        );
    });

    it("can load website info OK", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_settings#webinfo");

        cy.get("[data-cy=ABSPATH]").find("td:first-child").contains("ABSPATH");
        cy.get("[data-cy=ABSPATH]")
            .find("td:last-child")
            .contains("/var/www/html/");

        cy.get("[data-cy=sucuriscan_access_file_integrity]").contains(
            "Your website has no .htaccess file or it was not found in the default location.",
        );
    });

    it("can send audit logs to sucuri servers", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_events_reporting#auditlogs");

        cy.intercept("POST", "/wp-admin/admin-ajax.php?page=sucuriscan", (req) => {
            if (req.body.includes("get_audit_logs")) {
                req.reply((res) => {
                    res.send({ fixture: "audit_logs.json" });
                });
            }

            if (req.body.includes("auditlogs_send_logs")) {
                req.reply((res) => {
                    res.send({ fixture: "auditlogs_send_logs.json" });
                });
            }
        });

        cy.get("[data-cy=sucuriscan_dashboard_send_audit_logs_submit]").click();

        cy.get("[data-cy=sucuriscan_auditlog_response_loading]").contains(
            "Loading...",
        );

        cy.get(".sucuriscan-auditlog-entry-title").contains(
            "User authentication succeeded: admin",
        );
    });

    it.skip("can see last logins tab and delete last logins file", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_lastlogins#allusers");

        cy.get("[data-cy=sucuriscan_last_logins_table]")
            .find("td:nth-child(1)")
            .contains("admin (admin)");

        cy.get("[data-cy=sucuriscan_last_logins_delete_logins_button]").click();

        cy.get(".sucuriscan-alert").contains("sucuri-lastlogins.php was deleted.");
        cy.get("[data-cy=sucuriscan_last_logins_table]").contains(
            "no data available",
        );

        cy.get("[data-cy=sucuriscan_lastlogins_nav_admins]").click();

        cy.get("[data-cy=sucuriscan_successful_logins_table]")
            .find("td:nth-child(1)")
            .contains("admin");

        cy.get("[data-cy=sucuriscan_lastlogins_nav_loggedin]").click();

        cy.get("[data-cy=sucuriscan_successful_loggedin_table]")
            .find("td:nth-child(2)")
            .contains("admin");

        cy.get("[data-cy=sucuriscan_lastlogins_nav_failed]").click();

        cy.login("admin_x", "NOT_A_WP_PASS");

        cy.login();

        cy.visit("/wp-admin/admin.php?page=sucuriscan_lastlogins#failed");

        cy.get("[data-cy=sucuriscan_failedlogins_table]")
            .find("td:nth-child(1)")
            .contains("admin_x");

        cy.get("[data-cy=sucuriscan_failedlogins_delete_logins_button]").click();

        cy.get(".sucuriscan-alert").contains(
            "sucuri-failedlogins.php was deleted.",
        );

        cy.get("[data-cy=sucuriscan_failedlogins_table]").contains(
            "no data available",
        );
    });

    it("can reset password", () => {
        Cypress.session.clearAllSavedSessions();
        cy.visit("/");

        cy.login("sucuri-reset", "password");

        Cypress.session.clearAllSavedSessions();
        cy.login();

        cy.visit("http://localhost:8889/wp-admin/admin.php?page=sucuriscan_post_hack_actions");

        cy.get('.sucuriscan-reset-password-table')
            .contains('tr', 'sucuri-reset', { matchCase: false })
            .find('input[type="checkbox"]').check({ force: true });
        cy.get("[data-cy=sucuriscan-reset-password-button]").click();

        cy.get("[data-cy=sucuriscan-reset-password-user-field]").contains(
            "sucuri-reset (Done)",
        );

        Cypress.session.clearCurrentSessionData();

        cy.visit("/wp-login.php");

        cy.get("#user_login").clear().wait(200).type("sucuri-reset");
        cy.get("#user_pass").clear().wait(200).type("password");

        cy.get("#wp-submit").click();

        cy.get("#login_error").contains(
            "The password you entered for the username sucuri-reset is incorrect.",
        );
    });

    it("Can toggle the header cache control setting", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("[data-cy=sucuriscan_headers_cache_control_dropdown]").select(
            "Busy",
        );
        cy.get("[data-cy=sucuriscan_headers_cache_control_submit_btn]").click({
            force: true,
        });
        cy.get(".sucuriscan-alert").contains("Cache-Control header was activated.");

        cy.get("[data-cy=sucuriscan_headers_cache_control_dropdown]").select(
            "Disabled",
        );
        cy.get("[data-cy=sucuriscan_headers_cache_control_submit_btn]").click({
            force: true,
        });
        cy.get(".sucuriscan-alert").contains(
            "Cache-Control header was deactivated.",
        );
    });

    it("Can set the Cache-Control header properly", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("[data-cy=sucuriscan_headers_cache_control_dropdown]").select(
            "Busy",
        );
        cy.get("[data-cy=sucuriscan_headers_cache_control_submit_btn]").click({
            force: true,
        });
        cy.get(".sucuriscan-alert").contains("Cache-Control header was activated.");

        cy.visit("/wp-admin/admin.php?page=sucuriscan_lastlogins#allusers");

        cy.clearCookies();

        // main page
        cy.request("/").then((response) => {
            expect(response.headers["cache-control"]).to.exist;
            expect(response.headers["cache-control"]).to.equal("max-age=300");
        });

        // posts
        cy.request("/?p=1").then((response) => {
            expect(response.headers["cache-control"]).to.exist;
            expect(response.headers["cache-control"]).to.equal("max-age=600");
        });

        // pages
        cy.request("/?page_id=2").then((response) => {
            expect(response.headers["cache-control"]).to.exist;
            expect(response.headers["cache-control"]).to.equal("max-age=600");
        });

        // categories
        cy.request("/?cat=1").then((response) => {
            expect(response.headers["cache-control"]).to.exist;
            expect(response.headers["cache-control"]).to.equal("max-age=600");
        });

        // authors
        cy.request("/?author=1").then((response) => {
            expect(response.headers["cache-control"]).to.exist;
            expect(response.headers["cache-control"]).to.equal("max-age=600");
        });

        // 404s
        cy.request({ url: "/?p=12", failOnStatusCode: false }).then((response) => {
            expect(response.headers["cache-control"]).to.exist;
            expect(response.headers["cache-control"]).to.equal("max-age=600");
        });
    });

    it("Can customize the Cache-Control header properly", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        // Deactivate the cache control header
        cy.get("[data-cy=sucuriscan_headers_cache_control_dropdown]").select(
            "Disabled",
        );
        cy.get("[data-cy=sucuriscan_headers_cache_control_submit_btn]").click({
            force: true,
        });
        cy.get(".sucuriscan-alert").contains(
            "Cache-Control header was deactivated.",
        );

        cy.get("[data-cy=sucuriscan-row-posts]").click();
        cy.get("[name=sucuriscan_posts_max_age]").clear().type("12345");
        cy.get("[data-cy=sucuriscan_headers_cache_control_dropdown]").should(
            "have.value",
            "custom",
        );
        cy.get("[data-cy=sucuriscan-row-posts]").click();

        // Meaning that the box is now green and the status is now marked as Enabled
        cy.get(".sucuriscan-hstatus-1").contains("Enabled");

        cy.visit("/wp-admin/admin.php?page=sucuriscan_lastlogins#allusers");

        cy.clearCookies();

        cy.request("/?p=1").then((response) => {
            expect(response.headers["cache-control"]).to.exist;
            expect(response.headers["cache-control"]).to.equal("max-age=12345");
        });
    });

    it("Can customize the old age multiplier for the Cache-Control header", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        // Deactivate the cache control header
        cy.get("[data-cy=sucuriscan_headers_cache_control_dropdown]").select(
            "Disabled",
        );
        cy.get("[data-cy=sucuriscan_headers_cache_control_submit_btn]").click({
            force: true,
        });
        cy.get(".sucuriscan-alert").contains(
            "Cache-Control header was deactivated.",
        );

        cy.get("[name=sucuriscan_posts_old_age_multiplier]").should(
            "have.prop",
            "checked",
            false,
        );

        cy.get("[data-cy=sucuriscan-row-posts]").click();
        cy.get("[name=sucuriscan_posts_old_age_multiplier]").click();
        cy.get("[data-cy=sucuriscan-row-posts]").click();

        // Meaning that the box is now green and the status is now marked as Enabled
        cy.get(".sucuriscan-hstatus-1").contains("Enabled");

        cy.visit("/wp-admin/admin.php?page=sucuriscan_lastlogins#allusers");
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("[name=sucuriscan_posts_old_age_multiplier]").should(
            "have.prop",
            "checked",
            true,
        );

        cy.get("[data-cy=sucuriscan-row-posts]").click();
        cy.get("[name=sucuriscan_posts_old_age_multiplier]").click();
        cy.get("[data-cy=sucuriscan-row-posts]").click();

        cy.get("[name=sucuriscan_posts_old_age_multiplier]").should(
            "have.prop",
            "checked",
            false,
        );

        // Meaning that the box is now green and the status is now marked as Enabled
        cy.get(".sucuriscan-hstatus-1").contains("Enabled");
    });

    it("Cache-Control header functionality pages protected by log in", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("[data-cy=sucuriscan_headers_cache_control_dropdown]").select(
            "Frequent",
        );
        cy.get("[data-cy=sucuriscan_headers_cache_control_submit_btn]").click({
            force: true,
        });
        cy.get(".sucuriscan-alert").contains("Cache-Control header was activated.");

        cy.visit("/wp-admin/admin.php?page=sucuriscan_lastlogins#allusers");

        // main page
        cy.request("/wp-admin/index.php").then((response) => {
            expect(response.headers["cache-control"]).to.exist;
            expect(response.headers["cache-control"]).to.equal(
                "no-cache, must-revalidate, max-age=0, no-store, private",
            );
        });

        cy.clearCookies();

        // main page
        cy.request("/").then((response) => {
            expect(response.headers["cache-control"]).to.exist;
            expect(response.headers["cache-control"]).to.equal("max-age=1800");
        });
    });

    it('can filter auditlogs', () => {
        cy.visit('/wp-admin/admin.php?page=sucuriscan_events_reporting#auditlogs');

        cy.get('.sucuriscan-auditlog-response').should('exist');
        cy.get('.sucuriscan-auditlog-entry').should('have.length.greaterThan', 0);

        // Test plugins filter
        cy.get('#plugins').select('Activated', { force: true });
        cy.get('[data-cy=sucuriscan_auditlogs_filter_button]').click();

        // Verify that all logs are plugin activations
        cy.get('.sucuriscan-auditlog-entry').each(($row) => {
            cy.wrap($row).find('.sucuriscan-auditlog-entry-title').should('contain.text', 'Plugin activated');
        });

        // Clear filters and also verify clear filter functionality
        cy.get('[data-cy=sucuriscan_auditlogs_clear_filter_button]').click();

        cy.wait(200);

        // Test users filter
        cy.get('#logins').select('Succeeded');
        cy.get('[data-cy=sucuriscan_auditlogs_filter_button]').click();

        // Verify that all logs are user account updates
        cy.get('.sucuriscan-auditlog-entry').each(($row) => {
            cy.wrap($row).find('.sucuriscan-auditlog-entry-title').should('contain.text', 'User authentication succeeded');
        });

        cy.get('[data-cy=sucuriscan_auditlogs_clear_filter_button]').click();

        // Combine plugins and users filters
        cy.get('#plugins').select('Activated', { force: true });
        cy.get('#logins').select('Succeeded');
        cy.get('[data-cy=sucuriscan_auditlogs_filter_button]').click();

        cy.wait(3000);

        cy.get('.sucuriscan-auditlog-entry').each(($row) => {
            cy.wrap($row).find('.sucuriscan-auditlog-entry-title').invoke('text').then((text) => {
                expect(text).to.match(/Plugin activated|User authentication succeeded/);
            });
        });

        cy.get('[data-cy=sucuriscan_auditlogs_clear_filter_button]').click();

        // Combine time and login filters
        cy.get('#time').select('Last 7 Days', { force: true });
        cy.get('#logins').select('Succeeded');
        cy.get('[data-cy=sucuriscan_auditlogs_filter_button]').click();

        cy.get('.sucuriscan-auditlog-entry').each(($row) => {
            cy.wrap($row).find('.sucuriscan-auditlog-entry-title').invoke('text').then((text) => {
                expect(text).to.match(/User authentication succeeded/);
            });
        });

        cy.get('[data-cy=sucuriscan_auditlogs_clear_filter_button]').click();

        cy.wait(200);

        // Test 'Custom' date range filter
        // cy.get('#time').select('Custom', {force: true});
        // cy.get('#startDate').should('be.visible').type('2021-01-01', {force: true});
        // cy.get('#endDate').should('be.visible').type('2021-12-31', {force: true});
        // cy.get('[data-cy=sucuriscan_auditlogs_filter_button]').click();
        //
        // // Verify there are no logs:
        // cy.get('.sucuriscan-auditlog-entry').should('have.length', 0);
        // cy.get('.sucuriscan-auditlog-response').should('contain.text', 'There are no logs.');
        //
        // cy.get('[data-cy=sucuriscan_auditlogs_clear_filter_button]').click();
        //
        // cy.get('#startDate').should('not.be.visible');
        // cy.get('#endDate').should('not.be.visible');
        //
        // cy.get('.sucuriscan-auditlog-entry').should('have.length.greaterThan', 0);
    });

    it("Toggling enforce checkbox enables/disables inputs interactively", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("input[name='sucuriscan_enforced_default_src']").should("not.be.checked");
        cy.get("input[name='sucuriscan_csp_default_src']").should("be.disabled");

        cy.get("input[name='sucuriscan_enforced_default_src']").check({ force: true });
        cy.get("input[name='sucuriscan_csp_default_src']").should("not.be.disabled");

        cy.get("input[name='sucuriscan_enforced_default_src']").uncheck({ force: true });
        cy.get("input[name='sucuriscan_csp_default_src']").should("be.disabled");
    });


    it("Saves enforced state and value changes and persists after reload", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("input[name='sucuriscan_enforced_default_src']").check({ force: true });
        cy.get("input[name='sucuriscan_csp_default_src']").clear().type("'none'");

        cy.get("[data-cy=sucuriscan_csp_options_mode_button]").select(
            "Report Only",
        );
        cy.get("[data-cy=sucuriscan_headers_csp_control_submit_btn]").click({ force: true });


        cy.get("input[name='sucuriscan_csp_default_src']").should("have.value", "'none'").and("not.be.disabled");

        cy.request("/").then((response) => {
            expect(response.headers["content-security-policy-report-only"]).to.exist;
            expect(response.headers["content-security-policy-report-only"]).to.equal("default-src 'none'");
        });

        cy.get("[data-cy=sucuriscan_csp_options_mode_button]").select(
            "Disabled",
        );
        cy.get("[data-cy=sucuriscan_headers_csp_control_submit_btn]").click({ force: true });

        cy.request("/").then((response) => {
            expect(response.headers["content-security-policy-report-only"]).to.not.exist;
        });
    });

    it("Test multi_checkbox directive (sandbox)", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("input[name='sucuriscan_enforced_sandbox']").check({ force: true });
        cy.get("input[name='sucuriscan_csp_sandbox_allow-forms']").check({ force: true });
        cy.get("input[name='sucuriscan_csp_sandbox_allow-popups']").check({ force: true });
        cy.get("input[name='sucuriscan_csp_sandbox_allow-orientation-lock']").check({ force: true });

        cy.get("[data-cy=sucuriscan_csp_options_mode_button]").select(
            "Report Only",
        );
        cy.get("[data-cy=sucuriscan_headers_csp_control_submit_btn]").click({ force: true });

        cy.request("/").then((response) => {
            expect(response.headers["content-security-policy-report-only"]).to.exist;
            // default-src 'none' should be present because set in previous test case.
            expect(response.headers["content-security-policy-report-only"]).to.equal("default-src 'none'; sandbox allow-forms allow-orientation-lock allow-popups");
        });

        cy.get("input[name='sucuriscan_csp_sandbox_allow-forms']").uncheck({ force: true });
        cy.get("input[name='sucuriscan_csp_sandbox_allow-popups']").uncheck({ force: true });
        cy.get("input[name='sucuriscan_csp_sandbox_allow-orientation-lock']").uncheck({ force: true });
        cy.get("input[name='sucuriscan_csp_sandbox_allow-same-origin']").check({ force: true });

        cy.get("[data-cy=sucuriscan_headers_csp_control_submit_btn]").click({ force: true });

        cy.request("/").then((response) => {
            expect(response.headers["content-security-policy-report-only"]).to.exist;
            // default-src 'none' should be present because set in previous test case.
            expect(response.headers["content-security-policy-report-only"]).to.equal("default-src 'none'; sandbox allow-same-origin");
        });
    });

    it("Upgrade Insecure Requests directive should not appear unless enforced", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("input[name='sucuriscan_enforced_upgrade_insecure_requests']").should("not.be.checked");
        cy.get("input[name='sucuriscan_csp_upgrade_insecure_requests_upgrade-insecure-requests']").should("be.disabled");

        cy.request("/").then((response) => {
            expect(response.headers["content-security-policy-report-only"]).not.to.include("upgrade-insecure-requests");
        });

        cy.get("input[name='sucuriscan_enforced_upgrade_insecure_requests']").check({ force: true });
        cy.get("input[name='sucuriscan_csp_upgrade_insecure_requests_upgrade-insecure-requests']").should("not.be.disabled");
        cy.get("input[name='sucuriscan_csp_upgrade_insecure_requests_upgrade-insecure-requests']").check({ force: true });

        cy.get("[data-cy=sucuriscan_headers_csp_control_submit_btn]").click({ force: true });

        cy.request("/").then((response) => {
            const cspHeader = response.headers["content-security-policy-report-only"];
            expect(cspHeader).to.include("upgrade-insecure-requests");
        });
    });

    it("Toggling enforce checkbox enables/disables inputs for Access-Control-Allow-Origin", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("input[name='sucuriscan_enforced_Access-Control-Allow-Origin']")
            .uncheck({ force: true })
            .should("not.be.checked");

        cy.get("input[name='sucuriscan_cors_Access-Control-Allow-Origin']")
            .should("be.disabled");

        cy.get("input[name='sucuriscan_enforced_Access-Control-Allow-Origin']")
            .check({ force: true })
            .should("be.checked");

        cy.get("input[name='sucuriscan_cors_Access-Control-Allow-Origin']")
            .should("not.be.disabled");
    });

    it("Saves enforced state and value changes for Access-Control-Allow-Origin and persists after reload", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("input[name='sucuriscan_enforced_Access-Control-Allow-Origin']").check({ force: true });
        cy.get("input[name='sucuriscan_cors_Access-Control-Allow-Origin']")
            .clear()
            .type("example.com");

        cy.get("[data-cy=sucuriscan_cors_options_mode_button]").select("enabled");
        cy.get("[data-cy=sucuriscan_headers_cors_control_submit_btn]").click({ force: true });

        cy.reload();
        cy.get("input[name='sucuriscan_cors_Access-Control-Allow-Origin']")
            .should("have.value", "example.com")
            .and("not.be.disabled");

        cy.request("/").then((response) => {
            expect(response.headers["access-control-allow-origin"]).to.exist;
            expect(response.headers["access-control-allow-origin"]).to.equal("example.com");
        });
    });

    it("Multi-checkbox for Access-Control-Allow-Methods works correctly", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("input[name='sucuriscan_enforced_Access-Control-Allow-Methods']").check({ force: true });

        cy.get("input[name='sucuriscan_cors_Access-Control-Allow-Methods_GET']")
            .check({ force: true });
        cy.get("input[name='sucuriscan_cors_Access-Control-Allow-Methods_OPTIONS']")
            .check({ force: true });

        cy.get("[data-cy=sucuriscan_cors_options_mode_button]").select("enabled");
        cy.get("[data-cy=sucuriscan_headers_cors_control_submit_btn]").click({ force: true });

        cy.request("/").then((response) => {
            expect(response.headers["access-control-allow-methods"]).to.exist;
            const allowMethods = response.headers["access-control-allow-methods"];
            expect(allowMethods).to.include("GET");
            expect(allowMethods).to.include("OPTIONS");
            expect(allowMethods).not.to.include("PUT");
        });

        cy.get("input[name='sucuriscan_cors_Access-Control-Allow-Methods_POST']")
            .check({ force: true });
        cy.get("input[name='sucuriscan_cors_Access-Control-Allow-Methods_OPTIONS']")
            .uncheck({ force: true });
        cy.get("[data-cy=sucuriscan_headers_cors_control_submit_btn]").click({ force: true });

        cy.request("/").then((response) => {
            const allowMethods = response.headers["access-control-allow-methods"];
            expect(allowMethods).to.include("GET");
            expect(allowMethods).to.include("POST");
            expect(allowMethods).not.to.include("PUT");
            expect(allowMethods).not.to.include("OPTIONS");
        });
    });

    it("Allows setting and unsetting Access-Control-Allow-Credentials", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("input[name='sucuriscan_enforced_Access-Control-Allow-Credentials']")
            .uncheck({ force: true });

        cy.get("[data-cy=sucuriscan_headers_cors_control_submit_btn]").click({ force: true });

        cy.request("/").then((response) => {
            expect(response.headers["access-control-allow-credentials"]).to.not.exist;
        });

        cy.get("input[name='sucuriscan_enforced_Access-Control-Allow-Credentials']")
            .check({ force: true });
        cy.get("input[name='sucuriscan_cors_Access-Control-Allow-Credentials_Access-Control-Allow-Credentials']").check({ force: true });

        cy.get("[data-cy=sucuriscan_headers_cors_control_submit_btn]").click({ force: true });

        cy.request("/").then((response) => {
            expect(response.headers["access-control-allow-credentials"]).to.exist;
            expect(response.headers["access-control-allow-credentials"]).to.equal("true");
        });
    });

    it("Test disabling entire CORS mode removes all CORS headers", () => {
        cy.visit("/wp-admin/admin.php?page=sucuriscan_headers_management");

        cy.get("input[name='sucuriscan_enforced_Access-Control-Allow-Origin']").check({ force: true });
        cy.get("input[name='sucuriscan_cors_Access-Control-Allow-Origin']")
            .clear()
            .type("example.org");

        cy.get("[data-cy=sucuriscan_cors_options_mode_button]").select("enabled");
        cy.get("[data-cy=sucuriscan_headers_cors_control_submit_btn]").click({ force: true });

        cy.request("/").then((response) => {
            expect(response.headers["access-control-allow-origin"]).to.exist;
            expect(response.headers["access-control-allow-origin"]).to.equal("example.org");
        });

        cy.get("[data-cy=sucuriscan_cors_options_mode_button]").select("disabled");
        cy.get("[data-cy=sucuriscan_headers_cors_control_submit_btn]").click({ force: true });

        cy.request("/").then((response) => {
            expect(response.headers["access-control-allow-origin"]).to.not.exist;
        });
    });

    it("Test Light Theme", () => {
        cy.visit("wp-admin/admin.php?page=sucuriscan");

        cy.get('.unlock-premium ').should('be.visible');
        cy.get('.sucuriscan-upgrade-banner ').should('be.visible');

        cy.get('#core-vulnerability-results').should('not.be.visible');
        cy.get("#php-vulnerability-results").should('not.be.visible');
        cy.get('.sucuriscan-themes-list-body').should('not.be.visible');
    })

    it("Test Dark Theme", () => {
        const FAKE_API_KEY = "abcdefghiabcegasabcdefghiabcegas/abcdefghiabcegasabcdefghiabcegas";

        cy.visit("/wp-admin/admin.php?page=sucuriscan_firewall");

        cy.get("[name=sucuriscan_cloudproxy_apikey]").type(FAKE_API_KEY);
        cy.get("[data-cy=sucuriscan-save-wafkey]").click();

        cy.visit("wp-admin/admin.php?page=sucuriscan");

        cy.get('.unlock-premium ').should('not.be.visible');
        cy.get('.sucuriscan-upgrade-banner ').should('not.be.visible');

        // The API will not return any vulnerability information because the API is invalid.
        cy.get('#core-vulnerability-results').contains('Error: Could not fetch WordPress Core vulnerabilities.');
        cy.get("#php-vulnerability-results").contains('Error: Could not fetch PHP vulnerabilities.');
        cy.get('.sucuriscan-themes-list-body').should('have.length', 2);
    })
});


describe('Two-Factor Authentication', () => {
    it('enforces 2FA for all users and completes verify with a valid code', () => {
        cy.login();

        setModeAllUsers();

        cy.contains('table tr', testAdminUser.login, { matchCase: false })
            .find('input[name="sucuriscan_twofactor_users[]"]').check({ force: true });
        cy.get('[data-cy=sucuriscan_twofactor_bulk_control] select').select('reset_selected');
        cy.get('[data-cy=sucuriscan_twofactor_bulk_control] input[type=submit]').click();

        loginAndExpect2FA(testAdminUser.login, testAdminUser.pass, 'setup');
        completeSetupWithGeneratedCode();

        loginAndExpect2FA(testAdminUser.login, testAdminUser.pass, 'verify');
        loginAndExpect2FA(extraUser.login, extraUser.pass, 'setup');

        cy.login();

        go2faPage();
        setModeAllUsers('deactivate_all');
        resetForSelectedUsers([testAdminUser]);
    });

    it('enforces 2FA for selected users and completes setup for a non-admin user', () => {
        cy.login();

        setModeSelectedUsersFor([extraUser]);

        loginAndExpect2FA(extraUser.login, extraUser.pass, 'setup');
        completeSetupWithGeneratedCode();

        loginAndExpect2FA(extraUser.login, extraUser.pass, 'verify');
        finishWithCode('000000');
        cy.get('#login_error').should('contain.text', 'Invalid');

        cy.login();

        go2faPage();

        setModeAllUsers('deactivate_all');
        resetForSelectedUsers([extraUser]);


        cy.login(extraUser.login, extraUser.pass);
    });

    it('resets 2FA from Profile page for non-admin user', () => {
        cy.login();

        setModeSelectedUsersFor([extraUser], 'activate_selected');

        loginAndExpect2FA(extraUser.login, extraUser.pass, 'setup');
        completeSetupWithGeneratedCode();

        cy.visit('/wp-admin/profile.php');
        cy.get('[data-cy="sucuriscan-2fa-status-text"]').should('contain.text', 'Two-Factor Authentication is enabled for this account.');

        cy.on('window:confirm', (txt) => {
            expect(txt).to.contains('This will disable two-factor for this user. Continue?');
            return true;
        });

        cy.get('[data-cy="sucuriscan-2fa-reset-btn"]').click();

        cy.get('code').first().should('exist');

        cy.get('#sucuriscan-topt-qr', { timeout: 10000 }).should('exist').and(($el) => {
            if (!$el.find('img').length) {
                expect($el).to.exist;
            }
        });

        cy.login();

        setModeAllUsers('reset_all');
    });

    it('reset_selected forces re-setup only for the chosen user (selected mode)', () => {
        cy.login();

        setModeSelectedUsersFor([extraUser, testAdminUser], 'activate_selected');

        loginAndExpect2FA(extraUser.login, extraUser.pass, 'setup');
        completeSetupWithGeneratedCode();

        loginAndExpect2FA(testAdminUser.login, testAdminUser.pass, 'setup');
        completeSetupWithGeneratedCode();

        cy.login();

        go2faPage();

        cy.contains('table tr', extraUser.login, { matchCase: false })
            .find('input[name="sucuriscan_twofactor_users[]"]').check({ force: true });
        cy.get('[data-cy=sucuriscan_twofactor_bulk_control] select').select('reset_selected');
        cy.get('[data-cy=sucuriscan_twofactor_bulk_control] input[type=submit]').click();

        loginAndExpect2FA(extraUser.login, extraUser.pass, 'setup');
        loginAndExpect2FA(testAdminUser.login, testAdminUser.pass, 'verify');

        cy.login();

        setModeAllUsers('reset_all');
    });


    it('non-selected user bypasses 2FA when only another user is enforced', () => {
        cy.login();

        setModeAllUsers('reset_all');
        setModeSelectedUsersFor([extraUser], 'activate_selected');

        cy.login(adminUser.login, adminUser.pass);

        loginAndExpect2FA(extraUser.login, extraUser.pass, 'setup');
    });

    it('activates 2fa for all users and disables it again', () => {
        cy.login();

        setModeAllUsers();

        loginAndExpect2FA(adminUser.login, adminUser.pass);

        completeSetupWithGeneratedCode();

        cy.setCookie('sucuriscan_waf_dismissed', '1');

        setModeAllUsers('deactivate_all');
        resetForSelectedUsers([adminUser]);

        cy.login(adminUser.login, adminUser.pass);
    });

    it('locks out after 5 invalid verification attempts', () => {
        cy.login();

        setModeAllUsers('reset_all');
        setModeSelectedUsersFor([extraUser], 'activate_selected');

        loginAndExpect2FA(extraUser.login, extraUser.pass, 'setup');

        extractSecretFromSetupPage().then((secret) => {
            cy.task('totp', { secret }).then((code) => {
                finishWithCode(code);

                cy.url().should('contain', '/wp-admin/');

                loginAndExpect2FA(extraUser.login, extraUser.pass, 'verify');

                for (let i = 0; i < 5; i++) {
                    finishWithCode('111111');

                    if (i < 4) {
                        cy.get('#login_error').should('contain.text', 'Invalid');
                        cy.url().should('include', 'action=sucuri-2fa');
                    }
                }

                cy.url().should('match', /wp-login\.php(?!.*action=sucuri-2fa)/);
                cy.get('#user_login').should('exist');
            });
        });

        cy.login();

        setModeAllUsers('reset_all');
    });


    it('verify flow rejects a replayed TOTP code in same timestep', () => {
        cy.login();

        setModeSelectedUsersFor([testAdminUser], 'activate_selected');

        loginAndExpect2FA(testAdminUser.login, testAdminUser.pass, 'setup');

        extractSecretFromSetupPage().then((secret) => {
            cy.task('totp', { secret }).then((code) => {
                finishWithCode(code);
                cy.url().should('contain', '/wp-admin/');

                loginAndExpect2FA(testAdminUser.login, testAdminUser.pass, 'verify');
                finishWithCode(code);

                cy.get('#login_error').should('contain.text', 'Invalid');
                cy.url().should('include', 'action=sucuri-2fa');
            });
        });

        cy.login();

        setModeAllUsers('deactivate_all');
    });

    it('reset_everything wipes all 2FA secrets and disables enforcement (no challenges after)', () => {
        cy.login();

        setModeAllUsers('reset_all');
        setModeAllUsers();

        loginAndExpect2FA(testAdminUser.login, testAdminUser.pass, 'setup');
        completeSetupWithGeneratedCode();

        loginAndExpect2FA(extraUser.login, extraUser.pass, 'setup');
        completeSetupWithGeneratedCode();

        cy.login();
        go2faPage();

        cy.get('[data-cy=sucuriscan_twofactor_bulk_control] select').select('reset_everything');
        cy.get('[data-cy=sucuriscan_twofactor_bulk_control] input[type=submit]').click();
        cy.get('.sucuriscan-alert, .updated, .notice').should('contain.text', 'All Two-Factor data deleted');

        function expectNormalLogin(user) {
            Cypress.session.clearAllSavedSessions();
            cy.clearCookies();

            cy.visit('/wp-login.php');

            cy.get('#user_login').clear().wait(100).type(user.login);
            cy.get('#user_pass').clear().wait(100).type(user.pass);
            cy.get('#wp-submit').click();

            cy.url().should('contain', '/wp-admin/');
            cy.url().should('not.include', 'sucuri-2fa');
        }

        expectNormalLogin(testAdminUser);
        expectNormalLogin(extraUser);

        cy.login();
        go2faPage();
        cy.contains('Deactivated');
    });

    it('enforces 2FA for all users from sucuri dashboard', () => {
        cy.login(testAdminUser.login, testAdminUser.pass);

        setModeAllUsers('reset_all');

        go2faPage();

        extractSecretFromSetupPage().then((secret) => {
            cy.task('totp', { secret }).then((code) => {
                expect(code).to.match(/^\d{6}$/);
                cy.get('[name=sucuriscan_2fa_enforce_all]').click({ force: true });
                finishWithCode(code);
                cy.url().should('contain', '/wp-admin/');
            });
        });

        loginAndExpect2FA(testAdminUser.login, testAdminUser.pass, 'verify');
        loginAndExpect2FA(extraUser.login, extraUser.pass, 'setup');
        loginAndExpect2FA(adminUser.login, adminUser.pass, 'setup');
    });
});