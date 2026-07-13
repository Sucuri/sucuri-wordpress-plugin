#!/bin/bash
set -e

npx wp-env start
npx playwright test --project=setup
npx playwright test --ui
