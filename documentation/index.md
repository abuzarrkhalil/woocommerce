# WhatsApp for WooCommerce Documentation

## 1. Introduction

WhatsApp for WooCommerce allows customers to submit product or cart order requests via WhatsApp from your store.

## 2. Requirements

- WordPress: **[set minimum]**
- WooCommerce: **[set minimum]**
- PHP: **[set minimum]**

## 3. Installation

1. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**.
2. Upload the plugin ZIP and click **Install Now**.
3. Click **Activate**.
4. Ensure WooCommerce is active.

## 4. First-Time Setup

1. Open **WhatsApp → Settings**.
2. Enter default destination WhatsApp number.
3. Configure button text and placement.
4. Save settings.

## 5. Features

- Single product “Order on WhatsApp” flow.
- Cart-level WhatsApp order flow.
- Message templates for product/cart contexts.
- Product-level override for button text and destination number.
- Category-based display rules.
- Store-hours based visibility.
- Routing rules by context/category.
- Order logging, filtering, export, and purge tools.

## 6. Message Template Variables

Supported placeholders:

- `{customer_name}`
- `{customer_phone}`
- `{address}`
- `{notes}`
- `{order_code}`
- `{cart_lines}`
- `{product_name}`
- `{product_price}`
- `{product_link}`
- `{product_id}`
- `{quantity}`
- `{attributes}`

## 7. Data & Privacy

Stored fields may include customer name, phone, address notes, and generated order code in order logs.

If uninstall cleanup is set to **Delete**, plugin options and order logs are removed during uninstall.

## 8. Troubleshooting

### WhatsApp button not visible

- Verify plugin setting to enable button.
- Check category display rules.
- Check store-hours restrictions.
- Verify product stock rule if enabled.

### AJAX error when submitting

- Confirm WordPress nonce/session is valid.
- Verify no security plugin blocks `admin-ajax.php`.
- Check browser console and server PHP logs.

## 9. Changelog

See root `CHANGELOG.md`.

## 10. Support

- Support channel: **[your support URL/email]**
- Support hours: **[timezone + schedule]**
