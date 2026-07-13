#!/bin/bash
set -e

npx wp-env start
npx wp-env clean tests
npx wp-env run tests-cli bash "wp-content/plugins/${PWD##*/}/tests/e2e-prepare.sh"
