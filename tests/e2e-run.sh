#!/bin/bash
set -e

npx wp-env start
npx playwright test "$@"
