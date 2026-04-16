/**
 * E2E tests for the SUCURI_PLUG_* salt scheme.
 *
 * Verifies that:
 *   1. A v:1 payload (encrypted with AUTH_SALT) is auto-migrated to v:2
 *      (SUCURI_PLUG_* scheme) the first time the firewall page is loaded.
 *   2. SUCURI_PLUG_KEY and SUCURI_PLUG_SALT are written to wp-config.php (not wp_options).
 *   3. No decrypt-error notice is shown after migration.
 *   4. Saving a new key stores it as v:2 from the start.
 *   5. A freshly-saved key can be decrypted back to its original value.
 */

const legacyWafKey =
  "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb";

function runWpCli(command) {
  return cy.task("exec", `npx wp-env run tests-cli ${command}`);
}

function getOption(name) {
  return runWpCli(`wp option get ${name} --format=json`).then((raw) => {
    try {
      return JSON.parse(raw);
    } catch (_) {
      return raw ? raw.trim() : null;
    }
  });
}

/** Read the contents of wp-config.php via WP-CLI. */
function readWpConfig() {
  return runWpCli(
    `php -r '$p=ABSPATH."wp-config.php";if(!file_exists($p))$p=ABSPATH."../wp-config.php";echo file_get_contents($p);'`,
  );
}

beforeEach(() => {
  cy.session("Login to WordPress", () => {
    cy.login();
  });
});

describe("SUCURI_PLUG_* salt migration", () => {
  before(() => {
    // Seed a v:1 payload and strip any existing SUCURI_PLUG_* constants from
    // wp-config.php so the first-run write path is exercised from scratch.
    runWpCli(
      `bash wp-content/plugins/${Cypress.env("plugin_slug") || "sucuri-wordpress-plugin"}/tests/e2e-seed-waf-plug-salt.sh`,
    );
  });

  it("migrates a v:1 payload to v:2 on first firewall page load", () => {
    // Confirm the v:1 payload is in place before visiting the page.
    getOption("sucuriscan_secret_cloudproxy_apikey_enc").then((payload) => {
      expect(payload).to.have.property("v", 1);
    });

    cy.intercept("POST", "/wp-admin/admin-ajax.php?page=sucuriscan", (req) => {
      if (req.body.includes("get_firewall_settings")) {
        req.reply({ status: 1, output: { cache_level: "full", status: "active" } });
      }
    });

    cy.visit("/wp-admin/admin.php?page=sucuriscan_firewall");

    // After the page loads, getSecretOption() is called server-side, triggering migration.
    getOption("sucuriscan_secret_cloudproxy_apikey_enc").then((payload) => {
      expect(payload).to.have.property("v", 2);
      expect(payload).to.have.property("alg", "aes-256-gcm");
      expect(payload).to.have.property("iv");
      expect(payload).to.have.property("tag");
      expect(payload).to.have.property("ct");
    });
  });

  it("writes SUCURI_PLUG_KEY and SUCURI_PLUG_SALT to wp-config.php (not wp_options)", () => {
    readWpConfig().then((config) => {
      expect(config).to.match(/define\('SUCURI_PLUG_KEY',\s*'[0-9a-f]{64}'\)/);
      expect(config).to.match(/define\('SUCURI_PLUG_SALT',\s*'[0-9a-f]{64}'\)/);
    });

    // Must NOT be in wp_options.
    runWpCli("wp option get sucuriscan_plug_key || echo __missing__").then((out) => {
      expect(out).to.include("__missing__");
    });

    runWpCli("wp option get sucuriscan_plug_salt || echo __missing__").then((out) => {
      expect(out).to.include("__missing__");
    });
  });

  it("inserts constants before the stop-editing marker in wp-config.php", () => {
    readWpConfig().then((config) => {
      const plugPos = config.indexOf("SUCURI_PLUG_KEY");
      const stopPos = config.indexOf("That's all");
      expect(plugPos).to.be.greaterThan(-1);
      expect(stopPos).to.be.greaterThan(-1);
      expect(plugPos).to.be.lessThan(stopPos);
    });
  });

  it("does not duplicate constants on repeated reads", () => {
    // Reading does not regenerate — constants must still appear exactly once.
    runWpCli("wp eval 'SucuriScanOption::getOption(\":cloudproxy_apikey\");'");
    runWpCli("wp eval 'SucuriScanOption::getOption(\":cloudproxy_apikey\");'");

    readWpConfig().then((config) => {
      expect((config.match(/SUCURI_PLUG_KEY/g) || []).length).to.eq(1);
      expect((config.match(/SUCURI_PLUG_SALT/g) || []).length).to.eq(1);
    });
  });

  it("no decrypt-error notice is shown after migration", () => {
    cy.visit("/wp-admin/admin.php?page=sucuriscan_firewall");
    cy.get(".sucuriscan-alert-error, .notice-error").should("not.exist");
  });
});

describe("SUCURI_PLUG_* salt - fresh key save and decrypt", () => {
  before(() => {
    // Remove encrypted key; wp-config.php constants will be regenerated on save.
    runWpCli("wp option delete sucuriscan_secret_cloudproxy_apikey_enc || true");
    runWpCli("wp option delete sucuriscan_secret_cloudproxy_apikey || true");
  });

  it("stores a newly saved key as v:2 and replaces constants in wp-config.php", () => {
    const newKey =
      "cccccccccccccccccccccccccccccccc/dddddddddddddddddddddddddddddddd";

    runWpCli(
      `wp eval 'SucuriScanOption::updateOption(":cloudproxy_apikey", "${newKey}");'`,
    );

    getOption("sucuriscan_secret_cloudproxy_apikey_enc").then((payload) => {
      expect(payload).to.have.property("v", 2);
    });

    // Constants must appear exactly once (replaced, not duplicated).
    readWpConfig().then((config) => {
      expect((config.match(/SUCURI_PLUG_KEY/g) || []).length).to.eq(1);
      expect((config.match(/SUCURI_PLUG_SALT/g) || []).length).to.eq(1);
    });
  });

  it("can decrypt the freshly saved key back to the original value", () => {
    const newKey =
      "cccccccccccccccccccccccccccccccc/dddddddddddddddddddddddddddddddd";

    runWpCli(
      `wp eval 'echo SucuriScanOption::getOption(":cloudproxy_apikey");'`,
    ).then((output) => {
      expect(output.trim()).to.eq(newKey);
    });
  });

  it("re-save after corrupt salt: new payload decryptable with new constants", () => {
    const newKey =
      "eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee/ffffffffffffffffffffffffffffffff";

    // Corrupt the constants in wp-config.php.
    runWpCli(
      `wp eval '
$path = realpath(ABSPATH . "wp-config.php") ?: realpath(ABSPATH . "../wp-config.php");
$c = file_get_contents($path);
$c = preg_replace("/define\\\\([\'\\"]SUCURI_PLUG_(?:KEY|SALT)[\'\\"].*\\\\);/", "", $c);
$c .= "define(\\'SUCURI_PLUG_KEY\\',  \\'badbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbad\\');\\n";
$c .= "define(\\'SUCURI_PLUG_SALT\\', \\'badbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbadbad\\');\\n";
file_put_contents($path, $c, LOCK_EX);
'`,
    );

    // Re-save the key — regenerates salt, re-encrypts.
    runWpCli(
      `wp eval 'SucuriScanOption::updateOption(":cloudproxy_apikey", "${newKey}");'`,
    );

    // The key must now be readable.
    runWpCli(
      `wp eval 'echo SucuriScanOption::getOption(":cloudproxy_apikey");'`,
    ).then((output) => {
      expect(output.trim()).to.eq(newKey);
    });

    // Constants in wp-config.php must be valid hex (not the "badbad..." garbage).
    readWpConfig().then((config) => {
      expect(config).to.match(/define\('SUCURI_PLUG_KEY',\s*'[0-9a-f]{64}'\)/);
      expect(config).to.match(/define\('SUCURI_PLUG_SALT',\s*'[0-9a-f]{64}'\)/);
      expect(config).not.to.include("badbad");
    });
  });
});
