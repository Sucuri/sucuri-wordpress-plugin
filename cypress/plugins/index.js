/// <reference types="cypress" />
// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

/**
 * @type {Cypress.PluginConfig}
 */
const crypto = require('crypto');

function base32ToBuffer(base32) {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

  const cleaned = (base32 || '').toUpperCase().replace(/=+$/, '');

  let bits = '';

  for (let i = 0; i < cleaned.length; i++) {
    const val = alphabet.indexOf(cleaned[i]);

    if (val === -1) throw new Error('Invalid base32 character');

    bits += val.toString(2).padStart(5, '0');
  }

  const bytes = [];

  for (let i = 0; i + 8 <= bits.length; i += 8) {
    bytes.push(parseInt(bits.substring(i, i + 8), 2));
  }

  return Buffer.from(bytes);
}

function totpNow(secret, stepSeconds = 30, digits = 6) {
  const key = base32ToBuffer(secret);
  const counter = Math.floor(Date.now() / 1000 / stepSeconds);
  const msg = Buffer.alloc(8);

  msg.writeUInt32BE(0, 0);
  msg.writeUInt32BE(counter, 4);

  const hmac = crypto.createHmac('sha1', key).update(msg).digest();

  const offset = hmac[19] & 0x0f;
  const code = ((hmac[offset] & 0x7f) << 24) | (hmac[offset + 1] << 16) | (hmac[offset + 2] << 8) | (hmac[offset + 3]);
  const num = (code % Math.pow(10, digits)).toString().padStart(digits, '0');

  return num;
}

module.exports = (on, config) => {
  // `on` is used to hook into various events Cypress emits
  // `config` is the resolved Cypress config
  on('task', {
    totp({ secret, step = 30, digits = 6 }) {
      try {
        return totpNow(secret, step, digits);
      } catch (e) {
        return null;
      }
    }
  });
}
