/**
 * RFC-6238 TOTP generator, ported verbatim (logic-wise) from the old
 * cypress/plugins/index.js `totp` task. Used to derive a valid 6-digit code
 * from the base32 secret the plugin shows on the 2FA setup page.
 */
import { createHmac } from "node:crypto";

const BASE32_ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";

function base32ToBuffer(base32: string): Buffer {
  const cleaned = (base32 || "").toUpperCase().replace(/=+$/, "");
  let bits = "";

  for (const char of cleaned) {
    const val = BASE32_ALPHABET.indexOf(char);
    if (val === -1) throw new Error(`Invalid base32 character: ${char}`);
    bits += val.toString(2).padStart(5, "0");
  }

  const bytes: number[] = [];
  for (let i = 0; i + 8 <= bits.length; i += 8) {
    bytes.push(parseInt(bits.substring(i, i + 8), 2));
  }
  return Buffer.from(bytes);
}

/**
 * Compute the current TOTP code for a base32 secret.
 *
 * @param secret base32-encoded shared secret (as printed on the setup page)
 * @param stepSeconds time step, default 30s
 * @param digits code length, default 6
 */
export function totp(secret: string, stepSeconds = 30, digits = 6): string {
  const key = base32ToBuffer(secret);
  const counter = Math.floor(Date.now() / 1000 / stepSeconds);

  const msg = Buffer.alloc(8);
  msg.writeUInt32BE(0, 0);
  msg.writeUInt32BE(counter, 4);

  const hmac = createHmac("sha1", key).update(msg).digest();
  const offset = hmac[19] & 0x0f;
  const code =
    ((hmac[offset] & 0x7f) << 24) |
    (hmac[offset + 1] << 16) |
    (hmac[offset + 2] << 8) |
    hmac[offset + 3];

  return (code % 10 ** digits).toString().padStart(digits, "0");
}
