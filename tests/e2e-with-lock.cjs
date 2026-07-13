#!/usr/bin/env node
"use strict";

const net = require("node:net");
const path = require("node:path");
const { spawn } = require("node:child_process");

const command = process.argv.slice(2);
if (!command.length) {
  console.error("Usage: e2e-with-lock <command> [args...]");
  process.exit(2);
}

function lockPort() {
  const key = `${path.resolve(__dirname, "..")}::${process.env.WP_ENV_TESTS_PORT || "8889"}`;
  let hash = 0;
  for (const char of key) hash = (hash * 31 + char.charCodeAt(0)) >>> 0;
  return 20_000 + (hash % 20_000);
}

function acquire() {
  return new Promise((resolve, reject) => {
    const attempt = () => {
      const server = net.createServer();
      server.once("error", (error) => {
        if (error.code !== "EADDRINUSE") {
          reject(error);
          return;
        }
        setTimeout(attempt, 250);
      });
      server.listen(lockPort(), "127.0.0.1", () => resolve(server));
    };
    attempt();
  });
}

(async () => {
  const server = await acquire();
  const child = spawn(command[0], command.slice(1), {
    stdio: "inherit",
    env: { ...process.env, SUCURI_E2E_LOCK_OWNER: String(process.pid) },
  });

  for (const signal of ["SIGINT", "SIGTERM"]) {
    process.on(signal, () => child.kill(signal));
  }

  child.once("error", (error) => {
    console.error(error.message);
    server.close(() => process.exit(1));
  });
  child.once("exit", (code, signal) => {
    server.close(() => {
      if (signal) process.kill(process.pid, signal);
      else process.exit(code ?? 1);
    });
  });
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
