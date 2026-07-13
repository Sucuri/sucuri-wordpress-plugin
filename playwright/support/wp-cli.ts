/**
 * Thin wrappers around `npx wp-env run tests-cli …` for the e2e suite.
 *
 * These replace the Cypress `cy.task('exec', …)` helper and the inline
 * `php -r` snippets that the WAF specs used. Commands are passed as argv without
 * host-shell interpretation. These helpers require a running wp-env
 * (Docker) — they are used from spec setup/teardown, never from page actions.
 */
import { execFileSync } from "node:child_process";
import path from "node:path";
import { PLUGIN_SLUG, SETTINGS_FILE_PATH } from "./env";

const MAX_BUFFER = 8 * 1024 * 1024;
const WP_ENV_BIN = path.resolve("node_modules/.bin/wp-env");

/** Run an argv-safe command inside the wp-env `tests-cli` container. */
export function wpEnvRun(...command: string[]): string {
  try {
    return execFileSync(WP_ENV_BIN, ["run", "tests-cli", "--", ...command], {
      encoding: "utf8",
      maxBuffer: MAX_BUFFER,
      stdio: ["ignore", "pipe", "pipe"],
    }).trim();
  } catch (error) {
    const err = error as { status?: number };
    throw new Error(
      `wp-env command failed${err.status !== undefined ? ` (exit ${err.status})` : ""}`,
    );
  }
}

/** Run a WP-CLI subcommand (e.g. `wp("option", "get", "foo")`). */
export function wp(...subcommand: string[]): string {
  return wpEnvRun("wp", ...subcommand);
}

/** Run a bash script that lives inside the plugin directory (relative to the plugin root). */
export function runPluginScript(relativePath: string): string {
  return wpEnvRun(
    "bash",
    `wp-content/plugins/${PLUGIN_SLUG}/${relativePath}`,
  );
}

/** Read a wp_option. Returns the parsed JSON when possible, else the trimmed raw string, else null. */
export function getOption<T = unknown>(name: string): T | string | null {
  const raw = wp("option", "get", name, "--format=json");
  if (!raw) return null;
  try {
    return JSON.parse(raw) as T;
  } catch {
    return raw.trim();
  }
}

/**
 * Like getOption, but returns null when the option does not exist instead of
 * throwing (WP-CLI exits non-zero for a missing option). Use when absence is a
 * valid, expected outcome (e.g. asserting a plaintext fallback was never written).
 */
export function tryGetOption<T = unknown>(name: string): T | string | null {
  const sentinel = "__SUCURI_OPTION_MISSING__";
  const raw = wp(
    "eval",
    `$v=get_option(${JSON.stringify(name)},${JSON.stringify(sentinel)});` +
      `echo $v===${JSON.stringify(sentinel)}?${JSON.stringify(sentinel)}:wp_json_encode($v);`,
    "--skip-plugins",
    "--skip-themes",
  );
  if (raw === sentinel) return null;
  if (!raw) return null;
  try {
    return JSON.parse(raw) as T;
  } catch {
    return raw.trim();
  }
}

/**
 * Update a plugin option.
 *
 * The Sucuri Scanner plugin stores all sucuriscan_* options in its own flat
 * file (wp-content/uploads/sucuri/sucuri-settings.php), NOT in the WordPress
 * options table.  Using `wp option update` only writes to wp_options — the
 * plugin never reads that and the update is silently ignored.  We call
 * SucuriScanOption::updateOption() through `wp eval` so the write goes to the
 * correct storage layer regardless of whether the option is a flat-file option,
 * a secret option (wp-config.php), or a regular WP option.
 */
export function updateOption(name: string, value: string): void {
  wpEval(
    `SucuriScanOption::updateOption(${JSON.stringify(name)}, ${JSON.stringify(value)});`,
  );
}

/**
 * Delete a plugin option, tolerating absence.
 *
 * Same storage-layer reasoning as updateOption: route through
 * SucuriScanOption::deleteOption() so flat-file and secret options are
 * removed from the right place.
 */
export function deleteOption(name: string): void {
  wpEval(`SucuriScanOption::deleteOption(${JSON.stringify(name)});`);
}

/** Evaluate a short PHP one-liner via `wp eval` (avoid nested quotes — prefer a script file for complex PHP). */
export function wpEval(php: string): string {
  return wp("eval", php);
}

/** Read the full contents of wp-config.php. */
export function readWpConfig(): string {
  return wpEnvRun("cat", wp("config", "path"));
}

/**
 * Read the plugin settings file (JSON written after a `<?php exit(0); ?>` guard line).
 * Returns {} when the file is missing or unparseable.
 */
export function readSettingsFileJson(): Record<string, unknown> {
  const output = wpEnvRun("cat", SETTINGS_FILE_PATH);
  const json = output.split("\n", 2)[1]?.trim();
  if (!json) throw new Error("Plugin settings file is missing its JSON payload");
  try {
    return JSON.parse(json) as Record<string, unknown>;
  } catch (error) {
    throw new Error("Plugin settings file contains invalid JSON", {
      cause: error,
    });
  }
}

/** Replace the complete flat-file option set with a previously captured snapshot. */
export function replaceOptions(options: Record<string, unknown>): void {
  const encoded = Buffer.from(JSON.stringify(options)).toString("base64");
  wpEval(
    `SucuriScanOption::writeNewOptions(json_decode(base64_decode("${encoded}"), true));`,
  );
}

/** Restore wp-config.php byte-for-byte from a previously captured snapshot. */
export function restoreWpConfig(content: string): void {
  const encoded = Buffer.from(content).toString("base64");
  wpEval(
    '$p=ABSPATH."wp-config.php";' +
      'if(!file_exists($p)){$p=ABSPATH."../wp-config.php";}' +
      `file_put_contents($p,base64_decode("${encoded}"),LOCK_EX);`,
  );
}

/** Delete a raw wp_option without loading the plugin's option abstraction. */
export function deleteRawOption(name: string): void {
  wp(
    "eval",
    `delete_option(${JSON.stringify(name)});`,
    "--skip-plugins",
    "--skip-themes",
  );
}

/** Update a raw wp_option without loading active plugins. */
export function updateRawOption(name: string, value: unknown): void {
  wp(
    "option",
    "update",
    name,
    JSON.stringify(value),
    "--format=json",
    "--skip-plugins",
    "--skip-themes",
  );
}

/** Ensure a WordPress user exists with the exact role, email, and password. */
export function ensureUser(
  login: string,
  email: string,
  role: string,
  password: string,
): void {
  let exists = true;
  try {
    wp("user", "get", login, "--field=ID");
  } catch {
    exists = false;
  }

  if (exists) {
    wp(
      "user",
      "update",
      login,
      `--user_email=${email}`,
      `--role=${role}`,
      `--user_pass=${password}`,
    );
  } else {
    wp(
      "user",
      "create",
      login,
      email,
      `--role=${role}`,
      `--user_pass=${password}`,
    );
  }
}

const userIdCache = new Map<string, number>();

/**
 * Resolve a WordPress user's numeric ID by login (cached). Used to target the
 * stable `twofactor-user-checkbox-<id>` test id instead of fragile row-text
 * matching (logins like `sucuri` are substrings of `sucuri-admin`).
 */
export function getUserId(login: string): number {
  const cached = userIdCache.get(login);
  if (cached !== undefined) return cached;
  const id = Number(wp("user", "get", login, "--field=ID"));
  if (!Number.isInteger(id) || id <= 0) {
    throw new Error(`Could not resolve user ID for "${login}"`);
  }
  userIdCache.set(login, id);
  return id;
}
