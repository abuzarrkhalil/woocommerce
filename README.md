# WhatsApp for WooCommerce

CodeCanyon-ready packaging template for the plugin source and submission assets.

## Included in this repository

- Plugin source files.
- Marketplace templates under `marketplace/`.
- Buyer documentation under `documentation/`.
- Submission checklist in `CODECANYON-SUBMISSION-CHECKLIST.md`.
- Release build helper script in `scripts/build-release.sh`.

## Quick release steps

1. Update version numbers in plugin header and changelog.
2. Complete all placeholders in marketplace templates.
3. Run:
   ```bash
   bash scripts/build-release.sh
   ```
4. Upload generated files from `dist/` to CodeCanyon.

## Notes

- The generated package intentionally excludes `.git`, `dist`, and local dev artifacts.
- Keep documentation updated per release for faster review approval.
