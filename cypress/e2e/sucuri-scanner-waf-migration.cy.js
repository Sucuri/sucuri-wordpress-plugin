const legacyWafKey =
  "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb";
const settingsFilePath = "wp-content/uploads/sucuri/sucuri-settings.php";

function runWpCli(command) {
  return cy.task("exec", `npx wp-env run tests-cli ${command}`);
}

function readSettingsFileJson() {
  const php = `php -r '${
    `\$path=${JSON.stringify(settingsFilePath)};` +
    "$content=@file_get_contents($path);" +
    'if($content===false){echo "";exit(0);} ' +
    '$lines=explode("\\n",$content,2);' +
    'if(count($lines)<2){echo "";exit(0);} ' +
    "$json=trim($lines[1]);" +
    "echo $json;"
  }'`;

  return runWpCli(php).then((output) => {
    if (!output) {
      return {};
    }
    return JSON.parse(output);
  });
}

function seedLegacyWafKey() {
  const php = `php -r '${
    `\$path=${JSON.stringify(settingsFilePath)};` +
    "$content=@file_get_contents($path);" +
    'if($content===false){echo "";exit(0);} ' +
    '$lines=explode("\\n",$content,2);' +
    'if(count($lines)<2){echo "";exit(0);} ' +
    "$json=trim($lines[1]);" +
    "echo $json;"
  }'`;

  return runWpCli(php).then((output) => {
    if (!output) {
      return {};
    }
    return JSON.parse(output);
  });
}

function ensureLegacyWafKey() {
  return seedLegacyWafKey().then((settings) => {
    if (settings.sucuriscan_cloudproxy_apikey) {
      return settings;
    }

    const php = `php -r '${
      `\$path=${JSON.stringify(settingsFilePath)};` +
      "$dir=dirname($path);" +
      "if(!is_dir($dir)){mkdir($dir,0755,true);} " +
      "$data=array(" +
      `"sucuriscan_cloudproxy_apikey"=>"${legacyWafKey}",` +
      '"sucuriscan_addr_header"=>"REMOTE_ADDR",' +
      '"sucuriscan_notify_to"=>"alerts@example.com"' +
      ");" +
      "$json=json_encode($data);" +
      '$content="<?php exit(0); ?>\\n".$json."\\n";' +
      "file_put_contents($path,$content);"
    }'`;

    return runWpCli(php)
      .then(() =>
        runWpCli(
          "wp option delete sucuriscan_secret_cloudproxy_apikey_enc || true",
        ),
      )
      .then(() =>
        runWpCli(
          "wp option delete sucuriscan_secret_cloudproxy_apikey || true",
        ),
      )
      .then(() => readSettingsFileJson());
  });
}

beforeEach(() => {
  cy.session("Login to WordPress", () => {
    cy.login();
  });
});

describe("Run e2e WAF migration tests", () => {
  it("migrates legacy WAF key to encrypted DB option", () => {
    return ensureLegacyWafKey().then((settings) => {
      expect(settings.sucuriscan_cloudproxy_apikey).to.eq(legacyWafKey);
      expect(settings.sucuriscan_addr_header).to.eq("REMOTE_ADDR");

      cy.intercept(
        "POST",
        "/wp-admin/admin-ajax.php?page=sucuriscan",
        (req) => {
          if (req.body.includes("get_firewall_settings")) {
            req.reply({
              status: 1,
              output: {
                cache_level: "full",
                status: "active",
              },
            });
          }
        },
      );

      cy.visit("/wp-admin/admin.php?page=sucuriscan_firewall");

      readSettingsFileJson().then((settings) => {
        expect(settings).not.to.have.property("sucuriscan_cloudproxy_apikey");
        expect(settings.sucuriscan_addr_header).to.eq("REMOTE_ADDR");
      });

      runWpCli(
        "wp option get sucuriscan_secret_cloudproxy_apikey_enc --format=json",
      ).then((output) => {
        const payload = JSON.parse(output);
        expect(payload).to.have.property("alg", "aes-256-gcm");
        expect(payload).to.have.property("iv");
        expect(payload).to.have.property("tag");
        expect(payload).to.have.property("ct");
      });

      runWpCli(
        "wp option get sucuriscan_secret_cloudproxy_apikey || true",
      ).then((output) => {
        expect(output).to.eq("");
      });
    });
  });
});
