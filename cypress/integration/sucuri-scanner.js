
describe( 'Run integration tests', () => {
	beforeEach( function() {
		cy.visit('/wp-login.php');
		cy.wait( 1000 );
		cy.get('#user_login' ).type( Cypress.env('wp_user'));
		cy.get('#user_pass' ).type( Cypress.env('wp_pass'));
		cy.get('#wp-submit' ).click();
	}	);

	// it( 'can activate sucuri-scanner', () => {
	// 	cy.visit( '/wp-admin/plugins.php' );
		
	// 	cy.get( '#activate-sucuri-scanner' ).click();

	// 	cy.get( '.notice' ).should( 'contain', 'Plugin activated.' );
	// } );

	it('can change malware scan target', () => {
		const testDomain = 'sucuri.net';

		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#apiservice');

		cy.get('[data-cy=sucuriscan_sitecheck_target_input]').type(testDomain);
		cy.get('[data-cy=sucuriscan_sitecheck_target_submit]').click();

		cy.reload();

		cy.get('[data-cy=sucuriscan_sitecheck_target]').contains(testDomain);
	} );

	it('can reset logs, hardening and settings', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('[data-cy=sucuriscan_reset_checkbox]').check();

		cy.get('[data-cy=sucuriscan_reset_submit]').click();

		cy.get('.sucuriscan-alert').contains('Local security logs, hardening and settings were deleted');
	});

	it('can update ip address discovery', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('[data-cy=sucuriscan_ip_address_discovery_toggle_submit]').click();

		cy.get('.sucuriscan-alert').contains('The status of the DNS lookups for the reverse proxy detection has been changed');

		cy.get('[data-cy=sucuriscan_ip_address_discovery_toggle_submit]').contains('Enable');
		cy.get('[data-cy=sucuriscan_ip_address_discovery_toggle_submit]').click();

		cy.get('.sucuriscan-alert').contains('The status of the DNS lookups for the reverse proxy detection has been changed');
		cy.get('[data-cy=sucuriscan_ip_address_discovery_toggle_submit]').contains('Disable');

		cy.get('[data-cy=sucuriscan_addr_header_select]').select('HTTP_X_REAL_IP');
		cy.get('[data-cy=sucuriscan_addr_header_proceed]').click();

		cy.get('.sucuriscan-alert').contains('HTTP header was set to HTTP_X_REAL_IP');
		cy.get('.sucuriscan-alert').contains('Reverse proxy support was set to enabled');
	});

	it('can update reverse proxy setting', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('[data-cy=sucuriscan_reverse_proxy_toggle]').click();

		cy.get('.sucuriscan-alert').contains('Reverse proxy support was set to disabled');
		cy.get('.sucuriscan-alert').contains('HTTP header was set to REMOTE_ADDR');

		cy.get('[data-cy=sucuriscan_reverse_proxy_toggle]').click();

		cy.get('.sucuriscan-alert').contains('Reverse proxy support was set to enabled');
		cy.get('.sucuriscan-alert').contains('HTTP header was set to HTTP_X_SUCURI_CLIENTIP');
	});

	it('can delete datastore files', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('input[value="sucuri-auditqueue.php"]').click();
		cy.get('[data-cy=sucuriscan_general_datastore_delete_button]').click();
		cy.get('.sucuriscan-alert').contains('1 out of 1 files have been deleted.');

		cy.get('[data-cy=sucuriscan_general_datastore_delete_checkbox]').click();
		cy.get('[data-cy=sucuriscan_general_datastore_delete_button]').click();
		cy.get('.sucuriscan-alert').contains('8 out of 8 files have been deleted.');
	});

	it('can import JSON settings', () => {
		const jsonPayload = '{"sucuriscan_addr_header":"REMOTE_ADDR","sucuriscan_api_protocol":"https","sucuriscan_api_service":"enabled","sucuriscan_cloudproxy_apikey":"","sucuriscan_diff_utility":"disabled","sucuriscan_dns_lookups":"enabled","sucuriscan_email_subject":"Sucuri Alert, :domain, :event, :remoteaddr","sucuriscan_emails_per_hour":5,"sucuriscan_ignored_events":"","sucuriscan_lastlogin_redirection":"enabled","sucuriscan_maximum_failed_logins":30,"sucuriscan_notify_available_updates":"disabled","sucuriscan_notify_bruteforce_attack":"disabled","sucuriscan_notify_failed_login":"disabled","sucuriscan_notify_plugin_activated":"enabled","sucuriscan_notify_plugin_change":"enabled","sucuriscan_notify_plugin_deactivated":"disabled","sucuriscan_notify_plugin_deleted":"disabled","sucuriscan_notify_plugin_installed":"disabled","sucuriscan_notify_plugin_updated":"disabled","sucuriscan_notify_post_publication":"enabled","sucuriscan_notify_scan_checksums":"disabled","sucuriscan_notify_settings_updated":"enabled","sucuriscan_notify_success_login":"disabled","sucuriscan_notify_theme_activated":"enabled","sucuriscan_notify_theme_deleted":"disabled","sucuriscan_notify_theme_editor":"enabled","sucuriscan_notify_theme_installed":"disabled","sucuriscan_notify_theme_updated":"disabled","sucuriscan_notify_to":"wordpress@example.com","sucuriscan_notify_user_registration":"disabled","sucuriscan_notify_website_updated":"disabled","sucuriscan_notify_widget_added":"disabled","sucuriscan_notify_widget_deleted":"disabled","sucuriscan_prettify_mails":"disabled","sucuriscan_revproxy":"enabled","sucuriscan_selfhosting_fpath":"","sucuriscan_selfhosting_monitor":"disabled","sucuriscan_use_wpmail":"enabled","trusted_ips":[]}';

		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('[data-cy=sucuriscan_import_export_settings_textarea]').type(jsonPayload, { parseSpecialCharSequences: false });
		cy.get('[data-cy=sucuriscan_import_export_settings_checkbox]').click();
		cy.get('[data-cy=sucuriscan_import_export_settings_submit]').click();

		cy.reload();

		cy.get('[data-cy=sucuriscan_addr_header_select]').contains('REMOTE_ADDR');
	});

	it.only('can update timezone setting', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('[data-cy=sucuriscan_timezone_select]').select('UTC-07.00');
		cy.get('[data-cy=sucuriscan_timezone_submit]').click();
		cy.get('.sucuriscan-alert').contains('The timezone for the date and time in the audit logs has been changed');
	})

	// it( 'can deactivate sucuri-scanner', () => {
	// 	cy.visit( '/wp-admin/plugins.php' );
		
	// 	cy.get( '#deactivate-sucuri-scanner' ).click();

	// 	cy.get( '.notice' ).should( 'contain', 'Plugin deactivated.' );
	// }	);
}	);
