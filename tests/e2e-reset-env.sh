#!/bin/bash
set -e

# `reset`, not the deprecated `clean`; `all`, not `tests`, because this project
# runs a single environment and wp-env throws for a `tests` selector when the
# tests environment is disabled.
npx wp-env start
npx wp-env reset all
npx wp-env run cli bash "wp-content/plugins/${PWD##*/}/tests/e2e-prepare.sh"
