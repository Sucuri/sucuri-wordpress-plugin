// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })

Cypress.Commands.add('login', (username, password) => {
  const loginUsername = username || Cypress.env('wp_user');
  const loginPassword = password || Cypress.env('wp_pass');

  cy.session([username, password], () => {
    cy.setCookie('sucuriscan_waf_dismissed', '1');

    cy.visit('/wp-login.php');

    cy.get('#user_login').clear().wait(200).type(loginUsername);
    cy.get('#user_pass').clear().wait(200).type(loginPassword);

    cy.get('#wp-submit').click();

    cy.url().should('contain', '/wp-admin/')
  })
});