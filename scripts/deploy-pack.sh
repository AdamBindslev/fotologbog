#!/usr/bin/env bash
#
# Logbog – Deploy Pack Script
# Samler kun de sikre kodefiler til upload på webhotel.
# Udelukker konsekvent: data/, uploads/, config.local.php og .git/
#

set -e

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_DIR="$ROOT_DIR/deploy"
ZIP_FILE="$ROOT_DIR/deploy.zip"

echo "📦 Klargør sikker deploy-pakke..."

# 1. Ryd tidligere deploy mappe og zip
rm -rf "$DEPLOY_DIR" "$ZIP_FILE"
mkdir -p "$DEPLOY_DIR"

# 2. Kopiér sikre mapper
cp -R "$ROOT_DIR/app" "$DEPLOY_DIR/"
cp -R "$ROOT_DIR/assets" "$DEPLOY_DIR/"
cp -R "$ROOT_DIR/includes" "$DEPLOY_DIR/"

# 3. Kopiér sikre enkeltfiler
for file in .htaccess config.php index.php post.php og-image.php rss.php check.php README.md; do
    if [ -f "$ROOT_DIR/$file" ]; then
        cp "$ROOT_DIR/$file" "$DEPLOY_DIR/"
    fi
done

# 4. Ryd eventuelle Mac .DS_Store filer
find "$DEPLOY_DIR" -name ".DS_Store" -delete 2>/dev/null || true

# 5. Opret zip-fil hvis zip er tilgængelig
if command -v zip >/dev/null 2>&1; then
    (cd "$DEPLOY_DIR" && zip -r -q "$ZIP_FILE" .)
    echo "✓ deploy.zip oprettet"
fi

echo "✓ Mappen 'deploy/' er klar med sikre filer til upload."
echo "  (data/, uploads/ og config.local.php er IKKE inkluderet og kan derfor ikke overskrives)"
