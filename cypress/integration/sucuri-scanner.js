
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

	it.only('can delete datastore files', () => {
		cy.visit('/wp-admin/admin.php?page=sucuriscan_settings#general');

		cy.get('input[value="sucuri-auditqueue.php"]').click();
		cy.get('[data-cy=sucuriscan_general_datastore_delete_button]').click();
		cy.get('.sucuriscan-alert').contains('1 out of 1 files have been deleted.');

		cy.get('[data-cy=sucuriscan_general_datastore_delete_checkbox]').click();
		cy.get('[data-cy=sucuriscan_general_datastore_delete_button]').click();
		cy.get('.sucuriscan-alert').contains('8 out of 8 files have been deleted.');
	});

	// it( 'can deactivate sucuri-scanner', () => {
	// 	cy.visit( '/wp-admin/plugins.php' );
		
	// 	cy.get( '#deactivate-sucuri-scanner' ).click();

	// 	cy.get( '.notice' ).should( 'contain', 'Plugin deactivated.' );
	// }	);
}	);
