#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"
TMP_DIR="$DIST_DIR/tmp-build"
PLUGIN_SLUG="whatsapp-woocommerce"
PLUGIN_ZIP="$DIST_DIR/${PLUGIN_SLUG}.zip"
FULL_ZIP="$DIST_DIR/${PLUGIN_SLUG}-codecanyon-package.zip"

rm -rf "$TMP_DIR"
mkdir -p "$TMP_DIR/$PLUGIN_SLUG" "$DIST_DIR"

# Copy plugin files
rsync -av --delete \
  --exclude='.git' \
  --exclude='dist' \
  --exclude='documentation' \
  --exclude='marketplace' \
  --exclude='scripts' \
  --exclude='*.md' \
  "$ROOT_DIR/" "$TMP_DIR/$PLUGIN_SLUG/"

# Build plugin-only zip
(
  cd "$TMP_DIR"
  zip -rq "$PLUGIN_ZIP" "$PLUGIN_SLUG"
)

# Build full marketplace package
mkdir -p "$TMP_DIR/full-package"
cp "$PLUGIN_ZIP" "$TMP_DIR/full-package/"
cp -r "$ROOT_DIR/documentation" "$TMP_DIR/full-package/"
cp -r "$ROOT_DIR/marketplace" "$TMP_DIR/full-package/"
cp "$ROOT_DIR/CHANGELOG.md" "$TMP_DIR/full-package/"
cp "$ROOT_DIR/CODECANYON-SUBMISSION-CHECKLIST.md" "$TMP_DIR/full-package/"
cp "$ROOT_DIR/README.md" "$TMP_DIR/full-package/"

(
  cd "$TMP_DIR/full-package"
  zip -rq "$FULL_ZIP" .
)

rm -rf "$TMP_DIR"

echo "Build complete:"
echo "- $PLUGIN_ZIP"
echo "- $FULL_ZIP"
