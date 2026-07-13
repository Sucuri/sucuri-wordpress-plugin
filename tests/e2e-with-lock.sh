#!/bin/bash
set -e

exec node tests/e2e-with-lock.cjs "$@"
