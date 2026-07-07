/**
 * Thin wrappers around `npx wp-env run tests-cli …` for the e2e suite.
 *
 * These replace the Cypress `cy.task('exec', …)` helper and the inline
 * `php -r` snippets that the WAF specs used. Everything shells out to the
 * wp-env `tests-cli` container, so these helpers require a running wp-env
 * (Docker) — they are used from spec setup/teardown, never from page actions.
 */
import { execSync } from "node:child_process";
import { PLUGIN_SLUG, SETTINGS_FILE_PATH } from "./env";

const MAX_BUFFER = 8 * 1024 * 1024;

/** Run an arbitrary command inside the wp-env `tests-cli` container and return trimmed stdout. */
export function wpEnvRun(command: string): string {
  try {
    return execSync(`npx wp-env run tests-cli ${command}`, {
      encoding: "utf8",
      maxBuffer: MAX_BUFFER,
      stdio: ["ignore", "pipe", "pipe"],
    }).trim();
  } catch (error) {
    const err = error as {
      stderr?: Buffer | string;
      stdout?: Buffer | string;
      message?: string;
    };
    const detail = (err.stderr || err.stdout || err.message || "")
      .toString()
      .trim();
    throw new Error(`wp-env command failed: ${command}\n${detail}`);
  }
}

/** Run a WP-CLI subcommand (e.g. `option get foo --format=json`). */
export function wp(subcommand: string): string {
  return wpEnvRun(`wp ${subcommand}`);
}

/** Run a bash script that lives inside the plugin directory (relative to the plugin root). */
export function runPluginScript(relativePath: string): string {
  return wpEnvRun(`bash wp-content/plugins/${PLUGIN_SLUG}/${relativePath}`);
}

/** Read a wp_option. Returns the parsed JSON when possible, else the trimmed raw string, else null. */
export function getOption<T = unknown>(name: string): T | string | null {
  const raw = wp(`option get ${name} --format=json`);
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
  const raw = wpEnvRun(
    `wp option get ${name} --format=json 2>/dev/null || true`,
  );
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
  // Single-quote the PHP so the shell never expands $variables inside it.
  // Literal single quotes in the PHP are escaped with the '"'"' pattern.
  const escaped = php.replace(/'/g, "'\\''");
  return wpEnvRun(`wp eval '${escaped}'`);
}

/** Read the full contents of wp-config.php. */
export function readWpConfig(): string {
  // `wp config path` resolves wp-config.php whether it lives at the docroot or one
  // directory above, then `cat` returns it verbatim. A bare `php -r` cannot be used
  // here: ABSPATH is only defined inside a WordPress bootstrap, so standalone PHP
  // either fatals or silently yields "" — which is why this used to read empty.
  return wpEnvRun(`sh -c 'cat "$(wp config path)"'`);
}

/**
 * Read the plugin settings file (JSON written after a `<?php exit(0); ?>` guard line).
 * Returns {} when the file is missing or unparseable.
 */
export function readSettingsFileJson(): Record<string, unknown> {
  // The PHP is single-quoted so the OUTER /bin/sh never expands the $-variables.
  // (Double-quoting via JSON.stringify let the shell eat $path/$content before PHP
  // ever ran, leaving a mangled `php -r ="...";=@file_get_contents();` syntax error.)
  // SETTINGS_FILE_PATH has no shell-special chars, so it embeds safely in the
  // double-quoted PHP literal, and the "\n" reaches PHP intact inside the single quotes.
  const php =
    `$p="${SETTINGS_FILE_PATH}";` +
    "$c=@file_get_contents($p);" +
    'if($c===false){echo "";exit(0);}' +
    '$l=explode("\\n",$c,2);' +
    'if(count($l)<2){echo "";exit(0);}' +
    "echo trim($l[1]);";
  const output = wpEnvRun(`php -r '${php}'`);
  if (!output) return {};
  try {
    return JSON.parse(output) as Record<string, unknown>;
  } catch {
    return {};
  }
}

/** Ensure a WordPress user exists with the given role/password (idempotent). */
export function ensureUser(
  login: string,
  email: string,
  role: string,
  password: string,
): void {
  wpEnvRun(
    `wp user get ${login} --field=ID >/dev/null 2>&1 || ` +
      `wp user create ${login} ${email} --role=${role} --user_pass=${JSON.stringify(password)}`,
  );
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
  const id = Number(wp(`user get ${login} --field=ID`));
  if (!Number.isInteger(id) || id <= 0) {
    throw new Error(`Could not resolve user ID for "${login}"`);
  }
  userIdCache.set(login, id);
  return id;
}
