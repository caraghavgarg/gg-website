#!/usr/bin/env bash
# Builds dist/gg-site.zip, dist/gg-theme.zip and dist/versions.json from the two folders.
# The website reads dist/versions.json and offers an update when a version here is newer.
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p dist
rm -f dist/gg-site.zip dist/gg-theme.zip dist/gg-office-web.zip
site_ver=$(sed -n "s/^define( 'GGS_VER', '\([0-9.]*\)' );/\1/p" gg-site/gg-site.php)
theme_ver=$(sed -n 's/^Version: *\([0-9.]*\).*/\1/p' gg-theme/style.css)
office_ver=$(sed -n "s/^define('GGO_VERSION', '\([0-9.]*\)');/\1/p" gg-office-web/index.php)
# Fixed timestamps so an unchanged folder gives an identical zip.
find gg-site gg-theme gg-office-web -exec touch -d '2026-01-01 00:00:00' {} +
zip -qrX dist/gg-site.zip gg-site -x '*.DS_Store'
zip -qrX dist/gg-theme.zip gg-theme -x '*.DS_Store'
zip -qrX dist/gg-office-web.zip gg-office-web -x '*.DS_Store' 'gg-office-web/data/office*' 'gg-office-web/data/backups/*' 'gg-office-web/data/*.json' 'gg-office-web/config.php'
printf '{\n  "gg-site": "%s",\n  "gg-theme": "%s",\n  "gg-office-web": "%s"\n}\n' "$site_ver" "$theme_ver" "$office_ver" > dist/versions.json
cat dist/versions.json
