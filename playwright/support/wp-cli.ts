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
import { PLUGIN_SLUG } from "./env";

const MAX_BUFFER = 8 * 1024 * 1024;
const WP_ENV_BIN = path.resolve("node_modules/.bin/wp-env");

export type FileSnapshot = Record<string, string | null>;

export interface PluginDataSnapshot {
  backupPath: string;
  dataPath: string;
  existed: boolean;
}

export interface CronSnapshot {
  timestamp: number;
  schedule: string | false;
  interval: number | false;
  args: unknown[];
}

export type AllUserMetaSnapshot = string;

export interface RawOptionSnapshot {
  value: string;
  autoload: string;
}

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
  return wpEnvRun("cat", wpConfigPath());
}

/** Resolve wp-config.php without loading active plugins. */
function wpConfigPath(): string {
  return wp("config", "path", "--skip-plugins", "--skip-themes");
}

/**
 * Read the plugin settings file (JSON written after a `<?php exit(0); ?>` guard line).
 * Returns {} when the file is missing or unparseable.
 */
export function readSettingsFileJson(): Record<string, unknown> {
  const output = wpEnvRun("cat", wpEval('echo SucuriScanOption::optionsFilePath();'));
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
  wpEnvRun(
    "sh",
    "-c",
    'set -e; target="$1"; tmp="${target}.sucuri-restore.$$"; printf %s "$2" | base64 -d > "$tmp"; chmod --reference="$target" "$tmp" 2>/dev/null || true; mv "$tmp" "$target"',
    "sucuri-wp-config-restore",
    wpConfigPath(),
    encoded,
  );
}

/** Snapshot the configured plugin datastore directory with metadata preserved. */
export function snapshotPluginData(loadPlugin = true): PluginDataSnapshot {
  const info = JSON.parse(
    (loadPlugin ? wpEval : (php: string) => wp("eval", php, "--skip-plugins", "--skip-themes"))(
      (loadPlugin
        ? '$raw=SucuriScan::dataStorePath();'
        : '$raw=defined("SUCURI_DATA_STORAGE")?SUCURI_DATA_STORAGE:WP_CONTENT_DIR."/uploads/sucuri";') +
        '$parent=realpath(dirname($raw));' +
        '$path=$parent?$parent."/".basename($raw):$raw;echo wp_json_encode(array(' +
        '"path"=>$path,"unsafe"=>array(rtrim(ABSPATH,"/"),rtrim(WP_CONTENT_DIR,"/"),rtrim(WP_PLUGIN_DIR,"/"),rtrim(dirname(ABSPATH),"/")),"symlink"=>is_link($path)));',
    ),
  ) as { path: string; unsafe: string[]; symlink: boolean };
  const dataPath = info.path.replace(/\/+$/, "");
  if (
    !dataPath.startsWith("/") ||
    dataPath === "/" ||
    info.unsafe.includes(dataPath) ||
    info.symlink
  ) {
    throw new Error(`Unsafe plugin datastore path: ${dataPath}`);
  }
  const backupPath = wpEnvRun(
    "mktemp",
    "-d",
    "/tmp/sucuri-playwright-data.XXXXXX",
  );
  const existed = wpEval(
    `echo is_dir(${JSON.stringify(dataPath)})?"yes":"no";`,
  ) === "yes";
  if (existed) {
    wpEnvRun(
      "sh",
      "-c",
      'mkdir -p "$2/data"; for f in "$1"/sucuri-* "$1"/.htaccess "$1"/index.html; do [ ! -e "$f" ] || cp -a "$f" "$2/data/"; done',
      "sucuri-snapshot",
      dataPath,
      backupPath,
    );
  }
  return { backupPath, dataPath, existed };
}

/** Restore the configured plugin datastore directory and remove its backup. */
export function restorePluginData(snapshot: PluginDataSnapshot): void {
  wpEnvRun(
    "sh",
    "-c",
    'set -e; parent=$(dirname "$1"); stage="$parent/.sucuri-data-restore.$$"; rm -rf "$stage"; if [ "$3" = yes ]; then mkdir -p "$stage"; for f in "$2/data"/* "$2/data"/.htaccess; do [ ! -e "$f" ] || cp -a "$f" "$stage/"; done; fi; if [ -d "$1" ]; then for f in "$1"/sucuri-* "$1"/.htaccess "$1"/index.html; do [ ! -e "$f" ] || rm -rf "$f"; done; fi; if [ "$3" = yes ]; then mkdir -p "$1"; for f in "$stage"/* "$stage"/.htaccess; do [ ! -e "$f" ] || mv "$f" "$1/"; done; else rmdir "$1" 2>/dev/null || true; fi; rm -rf "$stage" "$2"',
    "sucuri-restore",
    snapshot.dataPath,
    snapshot.backupPath,
    snapshot.existed ? "yes" : "no",
  );
}

/** Snapshot selected files relative to the WordPress document root. */
export function snapshotWpFiles(paths: string[]): FileSnapshot {
  for (const file of paths) {
    if (file.startsWith("/") || file.split("/").includes("..")) {
      throw new Error(`Unsafe WordPress file path: ${file}`);
    }
  }
  const encoded = Buffer.from(JSON.stringify(paths)).toString("base64");
  const output = wpEval(
    `$paths=json_decode(base64_decode("${encoded}"),true);$out=array();` +
      'foreach($paths as $p){$f=ABSPATH.$p;$out[$p]=file_exists($f)?base64_encode(file_get_contents($f)):null;}' +
      'echo wp_json_encode($out);',
  );
  return JSON.parse(output || "{}") as FileSnapshot;
}

/** Restore selected WordPress-root files, including their prior absence. */
export function restoreWpFiles(snapshot: FileSnapshot): void {
  const encoded = Buffer.from(JSON.stringify(snapshot)).toString("base64");
  wpEval(
    `$files=json_decode(base64_decode("${encoded}"),true);` +
      'foreach($files as $p=>$data){$f=ABSPATH.$p;' +
      'if($data===null){if(file_exists($f)){@unlink($f);}continue;}' +
      '$d=dirname($f);if(!is_dir($d)){mkdir($d,0755,true);}' +
      'file_put_contents($f,base64_decode($data),LOCK_EX);}',
  );
}

/** Snapshot every scheduled event for one hook. */
export function snapshotCron(hook: string): CronSnapshot[] {
  const output = wpEval(
    `$hook=${JSON.stringify(hook)};$out=array();$cron=_get_cron_array();` +
      'foreach($cron as $timestamp=>$hooks){if(empty($hooks[$hook])){continue;}' +
      'foreach($hooks[$hook] as $event){$out[]=array("timestamp"=>(int)$timestamp,"schedule"=>$event["schedule"]?:false,"interval"=>isset($event["interval"])?(int)$event["interval"]:false,"args"=>$event["args"]);}}' +
      'echo wp_json_encode($out);',
  );
  return JSON.parse(output || "[]") as CronSnapshot[];
}

/** Restore every captured event for one hook. */
export function restoreCron(hook: string, snapshot: CronSnapshot[]): void {
  const encoded = Buffer.from(JSON.stringify(snapshot)).toString("base64");
  wpEval(
    `$hook=${JSON.stringify(hook)};$cron=_get_cron_array();` +
      'foreach($cron as $timestamp=>$hooks){unset($cron[$timestamp][$hook]);if(empty($cron[$timestamp])){unset($cron[$timestamp]);}}' +
      `$e=json_decode(base64_decode("${encoded}"),true);` +
      'foreach($e as $event){$key=md5(serialize($event["args"]));$entry=array("schedule"=>$event["schedule"],"args"=>$event["args"]);' +
      'if($event["interval"]!==false){$entry["interval"]=$event["interval"];} $cron[$event["timestamp"]][$hook][$key]=$entry;}' +
      'uksort($cron,"strnatcasecmp");_set_cron_array($cron);',
  );
}

/** Snapshot raw WordPress options without loading active plugins. */
export function snapshotRawOptions(names: readonly string[]): Map<string, RawOptionSnapshot | null> {
  const encoded = Buffer.from(JSON.stringify(names)).toString("base64");
  const output = wp(
    "eval",
    `$names=json_decode(base64_decode("${encoded}"),true);global $wpdb;$out=array();` +
      'foreach($names as $name){$row=$wpdb->get_row($wpdb->prepare("SELECT option_value,autoload FROM {$wpdb->options} WHERE option_name=%s",$name),ARRAY_A);' +
      '$out[$name]=$row?array("value"=>base64_encode($row["option_value"]),"autoload"=>$row["autoload"]):null;}echo wp_json_encode($out);',
    "--skip-plugins",
    "--skip-themes",
  );
  return new Map(Object.entries(JSON.parse(output) as Record<string, RawOptionSnapshot | null>));
}

/** Restore raw WordPress options to their captured values or absence. */
export function restoreRawOptions(snapshot: Map<string, RawOptionSnapshot | null>): void {
  for (const [name, option] of snapshot) {
    deleteRawOption(name);
    if (option !== null) restoreRawOption(name, option);
  }
}

/** Delete all matching prefixes, then restore captured raw option rows. */
export function restoreRawOptionsByPrefix(
  prefixes: readonly string[],
  snapshot: Map<string, RawOptionSnapshot | null>,
): void {
  const encoded = Buffer.from(JSON.stringify(prefixes)).toString("base64");
  wp(
    "eval",
    `$prefixes=json_decode(base64_decode("${encoded}"),true);global $wpdb;` +
      'foreach($prefixes as $prefix){$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",$wpdb->esc_like($prefix)."%"));}',
    "--skip-plugins",
    "--skip-themes",
  );
  for (const [name, option] of snapshot) {
    if (option !== null) restoreRawOption(name, option);
  }
}

/** Snapshot raw options whose names begin with any provided prefix. */
export function snapshotRawOptionsByPrefix(
  prefixes: readonly string[],
): Map<string, RawOptionSnapshot | null> {
  const encoded = Buffer.from(JSON.stringify(prefixes)).toString("base64");
  const output = wp(
    "eval",
    `$prefixes=json_decode(base64_decode("${encoded}"),true);global $wpdb;$where=array();` +
      'foreach($prefixes as $prefix){$where[]=$wpdb->prepare("option_name LIKE %s",$wpdb->esc_like($prefix)."%");}' +
      '$rows=$wpdb->get_results("SELECT option_name,option_value,autoload FROM {$wpdb->options} WHERE ".implode(" OR ",$where),ARRAY_A);$out=array();' +
      'foreach($rows as $row){$out[$row["option_name"]]=array("value"=>base64_encode($row["option_value"]),"autoload"=>$row["autoload"]);}echo wp_json_encode($out);',
    "--skip-plugins",
    "--skip-themes",
  );
  return new Map(Object.entries(JSON.parse(output || "{}") as Record<string, RawOptionSnapshot>));
}

/** Read user metadata without serializing an absent value as an empty string. */
export function tryGetUserMeta(login: string, key: string): string | null {
  const sentinel = "__SUCURI_META_MISSING__";
  const output = wpEval(
    `$u=get_user_by("login",${JSON.stringify(login)});` +
      `if(!$u){echo ${JSON.stringify(sentinel)};return;}` +
      `$exists=metadata_exists("user",$u->ID,${JSON.stringify(key)});` +
      `echo $exists?(string)get_user_meta($u->ID,${JSON.stringify(key)},true):${JSON.stringify(sentinel)};`,
  );
  return output === sentinel ? null : output;
}

/** Restore user metadata to a captured value or prior absence. */
export function restoreUserMeta(login: string, key: string, value: string | null): void {
  wpEval(
    `$u=get_user_by("login",${JSON.stringify(login)});if($u){` +
      (value === null
        ? `delete_user_meta($u->ID,${JSON.stringify(key)});`
        : `update_user_meta($u->ID,${JSON.stringify(key)},${JSON.stringify(value)});`) +
      '}',
  );
}

/** Snapshot arbitrary user metadata as a serialized base64 payload. */
export function snapshotSerializedUserMeta(
  login: string,
  key: string,
): string | null {
  const sentinel = "__SUCURI_META_MISSING__";
  const output = wpEval(
    `$u=get_user_by("login",${JSON.stringify(login)});` +
      `if(!$u||!metadata_exists("user",$u->ID,${JSON.stringify(key)})){echo ${JSON.stringify(sentinel)};return;}` +
      `echo base64_encode(serialize(get_user_meta($u->ID,${JSON.stringify(key)},true)));`,
  );
  return output === sentinel ? null : output;
}

/** Restore arbitrary serialized user metadata to its captured state. */
export function restoreSerializedUserMeta(
  login: string,
  key: string,
  value: string | null,
): void {
  wpEval(
    `$u=get_user_by("login",${JSON.stringify(login)});if($u){` +
      (value === null
        ? `delete_user_meta($u->ID,${JSON.stringify(key)});`
        : `update_user_meta($u->ID,${JSON.stringify(key)},unserialize(base64_decode(${JSON.stringify(value)})));`) +
      '}',
  );
}

/** Snapshot selected serialized metadata keys for every existing user. */
export function snapshotAllUserMeta(
  keys: readonly string[],
): AllUserMetaSnapshot {
  const encoded = Buffer.from(JSON.stringify(keys)).toString("base64");
  const output = wpEval(
    `$keys=json_decode(base64_decode("${encoded}"),true);$out=array();` +
      '$users=get_users(array("fields"=>"ID"));foreach($users as $uid){$out[$uid]=array();' +
      'foreach($keys as $key){$out[$uid][$key]=metadata_exists("user",$uid,$key)?serialize(get_user_meta($uid,$key,true)):null;}}' +
      '$path=tempnam(sys_get_temp_dir(),"sucuri-user-meta-");file_put_contents($path,serialize($out),LOCK_EX);echo $path;',
  );
  return output;
}

/** Restore selected user metadata keys for all users captured in a snapshot. */
export function restoreAllUserMeta(
  snapshot: AllUserMetaSnapshot,
  keys: readonly string[],
): void {
  const keysEncoded = Buffer.from(JSON.stringify(keys)).toString("base64");
  wpEval(
    `$path=${JSON.stringify(snapshot)};$state=unserialize(file_get_contents($path));` +
      `$keys=json_decode(base64_decode("${keysEncoded}"),true);` +
      'foreach($state as $uid=>$values){foreach($keys as $key){$value=$values[$key];' +
      'if($value===null){delete_user_meta($uid,$key);}' +
      'else{update_user_meta($uid,$key,unserialize($value));}}}@unlink($path);',
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
export function updateRawOption(
  name: string,
  value: unknown,
  autoload = "no",
): void {
  const encoded = Buffer.from(JSON.stringify(value)).toString("base64");
  wp(
    "eval",
    `$name=${JSON.stringify(name)};$value=json_decode(base64_decode("${encoded}"),true);` +
      `add_option($name,$value,"",${JSON.stringify(autoload)});`,
    "--skip-plugins",
    "--skip-themes",
  );
}

/** Restore one raw option row byte-for-byte, including its autoload metadata. */
function restoreRawOption(name: string, option: RawOptionSnapshot): void {
  wp(
    "eval",
    `global $wpdb;$wpdb->insert($wpdb->options,array("option_name"=>${JSON.stringify(name)},` +
      `"option_value"=>base64_decode(${JSON.stringify(option.value)}),` +
      `"autoload"=>${JSON.stringify(option.autoload)}),array("%s","%s","%s"));`,
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
