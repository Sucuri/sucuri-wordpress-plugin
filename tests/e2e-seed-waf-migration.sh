#!/bin/bash
set -e

mkdir -p wp-content/uploads/sucuri
cat <<'EOF' > wp-content/uploads/sucuri/sucuri-settings.php
<?php exit(0); ?>
{"sucuriscan_cloudproxy_apikey":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb","sucuriscan_addr_header":"REMOTE_ADDR","sucuriscan_notify_to":"alerts@example.com"}
EOF

wp option delete sucuriscan_secret_cloudproxy_apikey_enc
wp option delete sucuriscan_secret_cloudproxy_apikey
