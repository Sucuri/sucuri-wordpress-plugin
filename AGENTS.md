# Developer Guide (AGENTS.md)

This document provides instructions for AI agents and developers working on the Sucuri Scanner WordPress plugin.

## 1. Build, Lint, and Test Commands

### Setup

Ensure dependencies are installed:

```bash
composer install
npm install
```

### Environment

The project uses `@wordpress/env` (wp-env) for the local development environment.

- Start environment: `npm start`
- Stop environment: `npm stop`

### Testing

**Unit Tests (PHPUnit)**

- Run all unit tests:
  ```bash
  make unit-test
  # OR
  ./vendor/bin/phpunit
  ```
- Run a specific test class:
  ```bash
  ./vendor/bin/phpunit tests/test-class-name.php
  ```
- Run a specific test method:
  ```bash
  ./vendor/bin/phpunit --filter test_method_name
  ```

**End-to-End Tests (Cypress)**

- Run all E2E tests:
  ```bash
  make e2e
  ```
- Run scanner tests only:
  ```bash
  make e2e-scanner
  ```
- Run firewall tests only:
  ```bash
  make e2e-firewall
  ```
- **Note:** E2E tests require `wp-env` to be running.

### Linting

- **PHP:** Adheres to WordPress Coding Standards (WPCS).
  ```bash
  ./vendor/bin/phpcs
  ```
- **JavaScript:** Uses Prettier.
  ```bash
  npx prettier --check .
  ```

### Other Commands

- **Translations:** Update POT files.
  ```bash
  make update-translations
  ```

## 2. Code Style & Conventions

### PHP Guidelines

- **Standard:** Strictly follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).
- **Compatibility:** PHP 5.6+ compatibility. Avoid modern PHP features (like typed properties, arrow functions) unless polyfilled or conditionally used.
- **Formatting:**
  - Indentation: 4 spaces (Real tabs are standard in WP, but this repo seems to use spaces based on observation. Match existing file indentation).
  - Opening braces for **classes** go on the _next_ line.
  - Opening braces for **methods/functions** and **control structures** go on the _same_ line.
- **Naming:**
  - Classes: `PascalCase` (e.g., `SucuriScan`).
  - Methods: `camelCase` (e.g., `throwException`, `varPrefix`).
  - Variables/Properties: `snake_case` (e.g., `$sucuriscan_dependencies`).
  - Constants: `UPPER_SNAKE_CASE` (e.g., `SUCURISCAN_INIT`).
- **Security:**
  - **Direct Access Check:** All PHP files must start with the `SUCURISCAN_INIT` check:
    ```php
    if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
        if (!headers_sent()) {
            header('HTTP/1.1 403 Forbidden');
        }
        exit(1);
    }
    ```
  - **Escaping:** Always escape output (`esc_html`, `esc_attr`, `esc_url`).
  - **Sanitization:** Sanitize all inputs (`sanitize_text_field`, `absint`).
  - **Nonces:** Verify nonces for all actions.

### Documentation (PHPDoc)

- All classes and methods must have PHPDoc blocks.
- Required tags: `@category`, `@package`, `@subpackage`, `@author`, `@copyright`, `@license`.
- Methods must document `@param`, `@return`, and `@throws`.

### Project Structure

- `src/`: Core library files (logic).
- `inc/`: Include files (likely views/templates or initialization).
- `tests/`: PHPUnit tests.
- `cypress/`: E2E tests.

### Error Handling

- Use `SucuriScan::throwException($message, $type)` for handling errors within the library.
- Ensure messages are localized using `__('Message', 'sucuri-scanner')`.

### Git Workflow

- **NEVER COMMIT CODE:** Do not create git commits unless explicitly instructed by the user.
- Do not commit `node_modules`, `vendor`, or `.wp-env` directories.
- Follow conventional commits for commit messages if possible.
