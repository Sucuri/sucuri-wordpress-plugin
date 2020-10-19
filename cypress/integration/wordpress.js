
describe('WordPress install works', () => {
	beforeEach( function() {
		cy.visit('/wp-login.php');
		cy.wait( 1000 );
		cy.get('#user_login').type( Cypress.env('wp_user'));
		cy.get('#user_pass').type( Cypress.env('wp_pass'));
		cy.get('#wp-submit').click();
	} );

	it('can login to wp dashboard', function() {
		cy.url().should('include', '/wp-admin/');
	}	);
}	);
