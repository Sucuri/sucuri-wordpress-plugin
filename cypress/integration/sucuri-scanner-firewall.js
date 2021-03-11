describe( '', () => {
  beforeEach( function() {
		cy.login();
	}	);

  it( 'can activate api key', () => {
    cy.visit( '/wp-admin/admin.php?page=sucuriscan#auditlogs' );

    cy.contains( 'Firewall (WAF)' ).click();

    cy.contains( 'Firewall Settings' );

    cy.get( 'input[name=sucuriscan_cloudproxy_apikey]' ).type( Cypress.env( 'waf_api_key' ), { log: false } );
    cy.get( 'button[data-cy=sucuriscan-save-wafkey]').click();

    cy.get( '.sucuriscan-alert-updated' ).contains( 'SUCURI: Firewall API key was successfully saved' );
    cy.get( '.sucuriscan-alert-updated' ).contains( 'SUCURI: Reverse proxy support was set to enabled' );
    cy.get( '.sucuriscan-alert-updated' ).contains( 'SUCURI: HTTP header was set to HTTP_X_SUCURI_CLIENTIP' );
  } );

  it( 'can try to load audit logs', () => {
    cy.visit( '/wp-admin/admin.php?page=sucuriscan_firewall#auditlogs' );

    // We either see the message "no data available", or we get at least an entry with the Target field on it.
    cy.get( '.sucuriscan-firewall-auditlogs' ).contains( /no data available.|Target:.*/g );
  } );

  it( 'can try to add ip address to the blocklist', () => {
    const ipAddress = '82.165.185.18'; // known attacker IP

    cy.visit( '/wp-admin/admin.php?page=sucuriscan_firewall#settings' );

    cy.contains( 'IP Access' ).click();

    cy.get( '[data-cy=sucuriscan_ip_access_input]' ).type( ipAddress );
    cy.get( '[data-cy=sucuriscan_ip_access_submit]' ).click();

    cy.wait( 7000 );

    cy.get( '#sucuriscan-ipaccess-response' ).contains( 'IP address 82.165.185.18' ); // Can connect to the WAF API
  } );

  it( 'can clear cache when post/page is updated', () => {
    cy.visit( '/wp-admin/admin.php?page=sucuriscan#auditlogs' );

    cy.get( '[data-cy=sucuriscan-main-nav-firewall]').click();
    cy.contains( 'Clear Cache' ).click();

    cy.get( 'input[name=sucuriscan_auto_clear_cache]').check();

    cy.get( '#firewall-clear-cache-button' ).click();

    cy.wait( 2000 );

    cy.get( '#firewall-clear-cache-response' ).contains( /The cache for the domain ".*" is being cleared. Note that it may take up to two minutes for it to be fully flushed./g );
  } );

  it( 'can delete api key', () => {
    cy.visit( '/wp-admin/admin.php?page=sucuriscan#auditlogs' );

    cy.contains( 'Firewall (WAF)' ).click();

    cy.get( 'button[data-cy=sucuriscan-delete-wafkey] ').click();

    cy.get( '.sucuriscan-alert-updated' ).contains( 'SUCURI: Firewall API key was successfully removed' );
    cy.get( '.sucuriscan-alert-updated' ).contains( 'SUCURI: Reverse proxy support was set to disabled' );
    cy.get( '.sucuriscan-alert-updated' ).contains( 'SUCURI: HTTP header was set to REMOTE_ADDR' );
  } );
} );