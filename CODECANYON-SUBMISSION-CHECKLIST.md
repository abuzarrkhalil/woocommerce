# CodeCanyon Submission Checklist (WordPress Plugin)

Use this list before every upload.

## 1) Product & Branding

- [ ] Plugin name is final and consistent across files.
- [ ] `Plugin URI`, `Author`, `Author URI` are not placeholders.
- [ ] Product logo/icon/banner screenshots are prepared.
- [ ] Public demo URL works.

## 2) Code Quality

- [ ] No PHP syntax errors.
- [ ] No debug code, `var_dump`, or commented legacy code in production bundle.
- [ ] No unused/duplicate assets in release package.
- [ ] Sanitization/escaping verified for input/output.
- [ ] Nonce + capability checks in admin and AJAX actions.

## 3) WordPress Standards

- [ ] Text domain loaded and i18n strings are translatable.
- [ ] POT language template generated.
- [ ] Activation/deactivation/uninstall behavior documented.
- [ ] Works on supported WP/PHP/WooCommerce versions.

## 4) Buyer Experience

- [ ] Installation guide included.
- [ ] Configuration guide included.
- [ ] FAQ and troubleshooting included.
- [ ] Changelog included.
- [ ] Support policy included.

## 5) Privacy & Compliance

- [ ] Explain what customer data is stored.
- [ ] Explain data retention and delete behavior.
- [ ] Include GDPR/privacy notes where relevant.

## 6) Package Structure

- [ ] Main upload ZIP (plugin only).
- [ ] Documentation folder (HTML/PDF/Markdown).
- [ ] Optional extras/demo links.
- [ ] No node_modules/vendor/test fixtures unless required at runtime.

## 7) Marketplace Listing

- [ ] Fill `marketplace/item-description-template.md`.
- [ ] Fill support policy, requirements, and changelog in listing.
- [ ] Add high-quality screenshots (admin + frontend).
- [ ] Add short setup video (recommended).
