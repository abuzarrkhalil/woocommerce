diff --git a/CHANGELOG.md b/CHANGELOG.md
new file mode 100644
index 0000000000000000000000000000000000000000..48b79413b62f30004a4187f7bac213ba74072762
--- /dev/null
+++ b/CHANGELOG.md
@@ -0,0 +1,14 @@
+# Changelog
+
+All notable changes to this plugin should be documented in this file.
+
+## [2.0.0] - 2026-02-17
+### Added
+- Initial CodeCanyon submission pack templates.
+- Marketplace item description template.
+- Support policy template.
+- Installation + usage documentation skeleton.
+- Release build script and submission checklist.
+
+### Notes
+- Replace this entry with your real shipped changes before submission.
diff --git a/CODECANYON-SUBMISSION-CHECKLIST.md b/CODECANYON-SUBMISSION-CHECKLIST.md
new file mode 100644
index 0000000000000000000000000000000000000000..40b62999423e213c619c028bbc05bf0c69605341
--- /dev/null
+++ b/CODECANYON-SUBMISSION-CHECKLIST.md
@@ -0,0 +1,53 @@
+# CodeCanyon Submission Checklist (WordPress Plugin)
+
+Use this list before every upload.
+
+## 1) Product & Branding
+
+- [ ] Plugin name is final and consistent across files.
+- [ ] `Plugin URI`, `Author`, `Author URI` are not placeholders.
+- [ ] Product logo/icon/banner screenshots are prepared.
+- [ ] Public demo URL works.
+
+## 2) Code Quality
+
+- [ ] No PHP syntax errors.
+- [ ] No debug code, `var_dump`, or commented legacy code in production bundle.
+- [ ] No unused/duplicate assets in release package.
+- [ ] Sanitization/escaping verified for input/output.
+- [ ] Nonce + capability checks in admin and AJAX actions.
+
+## 3) WordPress Standards
+
+- [ ] Text domain loaded and i18n strings are translatable.
+- [ ] POT language template generated.
+- [ ] Activation/deactivation/uninstall behavior documented.
+- [ ] Works on supported WP/PHP/WooCommerce versions.
+
+## 4) Buyer Experience
+
+- [ ] Installation guide included.
+- [ ] Configuration guide included.
+- [ ] FAQ and troubleshooting included.
+- [ ] Changelog included.
+- [ ] Support policy included.
+
+## 5) Privacy & Compliance
+
+- [ ] Explain what customer data is stored.
+- [ ] Explain data retention and delete behavior.
+- [ ] Include GDPR/privacy notes where relevant.
+
+## 6) Package Structure
+
+- [ ] Main upload ZIP (plugin only).
+- [ ] Documentation folder (HTML/PDF/Markdown).
+- [ ] Optional extras/demo links.
+- [ ] No node_modules/vendor/test fixtures unless required at runtime.
+
+## 7) Marketplace Listing
+
+- [ ] Fill `marketplace/item-description-template.md`.
+- [ ] Fill support policy, requirements, and changelog in listing.
+- [ ] Add high-quality screenshots (admin + frontend).
+- [ ] Add short setup video (recommended).
diff --git a/README.md b/README.md
new file mode 100644
index 0000000000000000000000000000000000000000..e64d00204c6d8a6c9f944acecabd2a0fd7665e85
--- /dev/null
+++ b/README.md
@@ -0,0 +1,26 @@
+# WhatsApp for WooCommerce
+
+CodeCanyon-ready packaging template for the plugin source and submission assets.
+
+## Included in this repository
+
+- Plugin source files.
+- Marketplace templates under `marketplace/`.
+- Buyer documentation under `documentation/`.
+- Submission checklist in `CODECANYON-SUBMISSION-CHECKLIST.md`.
+- Release build helper script in `scripts/build-release.sh`.
+
+## Quick release steps
+
+1. Update version numbers in plugin header and changelog.
+2. Complete all placeholders in marketplace templates.
+3. Run:
+   ```bash
+   bash scripts/build-release.sh
+   ```
+4. Upload generated files from `dist/` to CodeCanyon.
+
+## Notes
+
+- The generated package intentionally excludes `.git`, `dist`, and local dev artifacts.
+- Keep documentation updated per release for faster review approval.
diff --git a/assets/js/whatsapp-modal.js b/assets/js/whatsapp-modal.js
index 89442249cb65f56d403db19143cebb1087012f52..866889b0f5ef3819a1f27e6af58667984672501f 100644
--- a/assets/js/whatsapp-modal.js
+++ b/assets/js/whatsapp-modal.js
@@ -1,87 +1,93 @@
-jQuery(document).ready(function($) {
-    var modal = $('#whatsapp-order-modal');
-
-    // Show the modal for single product buttons
-    $('.whatsapp-order-button').on('click', function(e) {
-        e.preventDefault();
-        var product_id = $(this).data('product-id');
-        $('#product_id').val(product_id); // Store the product ID in a hidden field
-        $('#is_cart_order').val('0'); // Mark as single product order
-
-        var form = $(this).closest('.product').find('form.cart').first();
-        var qty  = form.find('input.qty').val() || '1';
-        var vid  = form.find('input.variation_id').val() || '';
-        var attrs = {};
-        form.find('[name^="attribute_"]').each(function(){
-            var n = $(this).attr('name');
-            var v = $(this).val();
-            if (n) { attrs[n] = v; }
-        });
-        $('#quantity').val(qty);
-        $('#variation_id').val(vid);
-        $('#attributes_json').val(JSON.stringify(attrs));
-
-        modal.css('display', 'flex'); 
-    });
-
-    // Show the modal for cart page button
-     $('.whatsapp-order-cart-button').on('click', function(e) {
-        e.preventDefault();
-        $('#is_cart_order').val('1'); // Mark as cart order
-        $('#product_id').val(''); // Clear single product ID
-        modal.css('display', 'flex'); 
-    });
-
-
-    // Close the modal
-    $('.whatsapp-close-button').on('click', function() {
-        modal.hide();
-    });
-
-    // Close the modal if the user clicks outside
-    $(window).on('click', function(event) {
-        if ($(event.target).is(modal)) {
-            modal.hide();
-        }
-    });
-
-    // Handle form submission via AJAX
-    $('#whatsapp-order-form').on('submit', function(e) {
-        e.preventDefault();
-
-        if (!$('#gdpr_consent').is(':checked')) {
-            alert('You must accept the privacy policy.');
-            return;
-        }
-
-        var formData = {
-            'action': 'wcwa_process_order',
-            'nonce': $('#wcwa_order_nonce_field').val(),
-            'name': $('#customer_name').val(),
-            'phone': $('#customer_phone').val(),
-            'address': $('#customer_address').val(),
-            'notes': $('#customer_notes').val(),
-            'product_id': $('#product_id').val(),
-            'is_cart_order': $('#is_cart_order').val(),
-            'consent': $('#gdpr_consent').is(':checked') ? '1' : '0',
-            'quantity': $('#quantity').val(),
-            'variation_id': $('#variation_id').val(),
-            'attributes_json': $('#attributes_json').val(),
-        };
-
-        $('#send-whatsapp-order').prop('disabled', true).text('Processing...');
-
-        $.post(wcwa_ajax.ajax_url, formData, function(response) {
-            if (response && response.success && response.data && response.data.whatsapp_url) {
-                window.open(response.data.whatsapp_url, "_blank", "noopener");
-            } else {
-                var msg = (response && response.data && response.data.message) ? response.data.message : 'There was an error processing your order.';
-                alert(msg);
-                $('#send-whatsapp-order').prop('disabled', false).text('Send Order');
-            }
-        }).fail(function() {
-            alert('Server error. Please try again.');
-            $('#send-whatsapp-order').prop('disabled', false).text('Send Order');
-        });
-    });
-});
+jQuery(document).ready(function($) {
+    var modal = $('#whatsapp-order-modal');
+
+    // Show the modal for single product buttons
+    $('.whatsapp-order-button').on('click', function(e) {
+        e.preventDefault();
+        var product_id = $(this).data('product-id');
+        $('#product_id').val(product_id); // Store the product ID in a hidden field
+        $('#is_cart_order').val('0'); // Mark as single product order
+
+        var form = $(this).closest('.product').find('form.cart').first();
+        var qty  = form.find('input.qty').val() || '1';
+        var vid  = form.find('input.variation_id').val() || '';
+        var attrs = {};
+        form.find('[name^="attribute_"]').each(function(){
+            var n = $(this).attr('name');
+            var v = $(this).val();
+            if (n) { attrs[n] = v; }
+        });
+        $('#quantity').val(qty);
+        $('#variation_id').val(vid);
+        $('#attributes_json').val(JSON.stringify(attrs));
+
+        modal.css('display', 'flex'); 
+    });
+
+    // Show the modal for cart page button
+     $('.whatsapp-order-cart-button').on('click', function(e) {
+        e.preventDefault();
+        $('#is_cart_order').val('1'); // Mark as cart order
+        $('#product_id').val(''); // Clear single product ID
+        modal.css('display', 'flex'); 
+    });
+
+
+    // Close the modal
+    $('.whatsapp-close-button').on('click', function() {
+        modal.hide();
+    });
+
+    // Close the modal if the user clicks outside
+    $(window).on('click', function(event) {
+        if ($(event.target).is(modal)) {
+            modal.hide();
+        }
+    });
+
+    // Handle form submission via AJAX
+    $('#whatsapp-order-form').on('submit', function(e) {
+        e.preventDefault();
+
+        if (!$('#gdpr_consent').is(':checked')) {
+            alert('You must accept the privacy policy.');
+            return;
+        }
+
+        var formData = {
+            'action': 'wcwa_process_order',
+            'nonce': $('#wcwa_order_nonce_field').val(),
+            'name': $('#customer_name').val(),
+            'phone': $('#customer_phone').val(),
+            'address': $('#customer_address').val(),
+            'notes': $('#customer_notes').val(),
+            'product_id': $('#product_id').val(),
+            'is_cart_order': $('#is_cart_order').val(),
+            'consent': $('#gdpr_consent').is(':checked') ? '1' : '0',
+            'quantity': $('#quantity').val(),
+            'variation_id': $('#variation_id').val(),
+            'attributes_json': $('#attributes_json').val(),
+        };
+
+        $('#send-whatsapp-order').prop('disabled', true).text('Processing...');
+
+        $.post(wcwa_ajax.ajax_url, formData, function(response) {
+            if (response && response.success && response.data && response.data.whatsapp_url) {
+                // Use api.whatsapp.com for all devices because it reliably
+                // keeps the chat payload and then routes to app/web.
+                if (response.data.whatsapp_api_url) {
+                    window.open(response.data.whatsapp_api_url, "_blank", "noopener");
+                } else {
+                    window.open(response.data.whatsapp_url, "_blank", "noopener");
+                }
+            } else {
+                var msg = (response && response.data && response.data.message) ? response.data.message : 'There was an error processing your order.';
+                alert(msg);
+                $('#send-whatsapp-order').prop('disabled', false).text('Send Order');
+            }
+        }).fail(function() {
+            alert('Server error. Please try again.');
+            $('#send-whatsapp-order').prop('disabled', false).text('Send Order');
+        });
+    });
+});
diff --git a/dist/.gitkeep b/dist/.gitkeep
new file mode 100644
index 0000000000000000000000000000000000000000..e69de29bb2d1d6434b8b29ae775ad8c2e48c5391
diff --git a/documentation/index.md b/documentation/index.md
new file mode 100644
index 0000000000000000000000000000000000000000..df98a312aa825328d4bea643c3efdaa7dadfdbb2
--- /dev/null
+++ b/documentation/index.md
@@ -0,0 +1,83 @@
+# WhatsApp for WooCommerce Documentation
+
+## 1. Introduction
+
+WhatsApp for WooCommerce allows customers to submit product or cart order requests via WhatsApp from your store.
+
+## 2. Requirements
+
+- WordPress: **[set minimum]**
+- WooCommerce: **[set minimum]**
+- PHP: **[set minimum]**
+
+## 3. Installation
+
+1. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**.
+2. Upload the plugin ZIP and click **Install Now**.
+3. Click **Activate**.
+4. Ensure WooCommerce is active.
+
+## 4. First-Time Setup
+
+1. Open **WhatsApp → Settings**.
+2. Enter default destination WhatsApp number.
+3. Configure button text and placement.
+4. Save settings.
+
+## 5. Features
+
+- Single product “Order on WhatsApp” flow.
+- Cart-level WhatsApp order flow.
+- Message templates for product/cart contexts.
+- Product-level override for button text and destination number.
+- Category-based display rules.
+- Store-hours based visibility.
+- Routing rules by context/category.
+- Order logging, filtering, export, and purge tools.
+
+## 6. Message Template Variables
+
+Supported placeholders:
+
+- `{customer_name}`
+- `{customer_phone}`
+- `{address}`
+- `{notes}`
+- `{order_code}`
+- `{cart_lines}`
+- `{product_name}`
+- `{product_price}`
+- `{product_link}`
+- `{product_id}`
+- `{quantity}`
+- `{attributes}`
+
+## 7. Data & Privacy
+
+Stored fields may include customer name, phone, address notes, and generated order code in order logs.
+
+If uninstall cleanup is set to **Delete**, plugin options and order logs are removed during uninstall.
+
+## 8. Troubleshooting
+
+### WhatsApp button not visible
+
+- Verify plugin setting to enable button.
+- Check category display rules.
+- Check store-hours restrictions.
+- Verify product stock rule if enabled.
+
+### AJAX error when submitting
+
+- Confirm WordPress nonce/session is valid.
+- Verify no security plugin blocks `admin-ajax.php`.
+- Check browser console and server PHP logs.
+
+## 9. Changelog
+
+See root `CHANGELOG.md`.
+
+## 10. Support
+
+- Support channel: **[your support URL/email]**
+- Support hours: **[timezone + schedule]**
diff --git a/marketplace/item-description-template.md b/marketplace/item-description-template.md
new file mode 100644
index 0000000000000000000000000000000000000000..e57ca56d491ffe7b7c922daa33eac19e416db73b
--- /dev/null
+++ b/marketplace/item-description-template.md
@@ -0,0 +1,63 @@
+# CodeCanyon Item Description Template
+
+> Replace all placeholders before publishing.
+
+## Title
+
+WhatsApp for WooCommerce – Direct Order Requests via WhatsApp
+
+## Short Description
+
+Enable your customers to place product or cart order requests instantly through WhatsApp with customizable templates, smart routing, and detailed WooCommerce admin logs.
+
+## Key Features
+
+- Product and cart WhatsApp order buttons.
+- Front-end modal form for customer details.
+- Customizable WhatsApp message templates.
+- Per-product destination number and button text overrides.
+- Category include/exclude display rules.
+- Store-hours visibility control.
+- Routing rules by context and category.
+- Order log CPT with filters and CSV export.
+- Analytics export by date range.
+- Uninstall behavior control (keep/delete data).
+
+## Why Choose This Plugin
+
+- Faster pre-checkout customer conversations.
+- Useful for COD/manual workflows.
+- Easy setup through a dedicated admin panel.
+- Designed for WooCommerce-based stores.
+
+## Admin Panels Included
+
+- General Settings
+- Messaging Templates
+- Display Rules
+- Routing Rules
+- Analytics
+- Logs
+
+## Compatibility
+
+- WordPress: **[x.x+]**
+- WooCommerce: **[x.x+]**
+- PHP: **[x.x+]**
+
+## What You Get
+
+- Plugin ZIP
+- Documentation
+- Changelog
+- Support policy
+
+## Support
+
+- Includes: bug fixes and setup guidance.
+- Excludes: custom development and third-party plugin conflicts.
+- Contact: **[support email/url]**
+
+## Changelog
+
+Use latest entries from `CHANGELOG.md`.
diff --git a/marketplace/support-policy-template.md b/marketplace/support-policy-template.md
new file mode 100644
index 0000000000000000000000000000000000000000..83c7ffed2ae75b7ed6a13e7fea9ab32d0af7f91a
--- /dev/null
+++ b/marketplace/support-policy-template.md
@@ -0,0 +1,36 @@
+# Support Policy Template
+
+## Included Support
+
+- Installation guidance.
+- Help using documented features.
+- Bug fixes for verified plugin issues.
+
+## Not Included
+
+- Custom feature development.
+- Theme-specific custom styling.
+- Server management and DevOps tasks.
+- Third-party service outages.
+
+## Support Window
+
+- Business days: **[days]**
+- Hours: **[hours + timezone]**
+- First response target: **[e.g., 24–48h]**
+
+## Required Information for Support Requests
+
+Please provide:
+
+1. Site URL
+2. WordPress, WooCommerce, PHP versions
+3. Plugin version
+4. Steps to reproduce
+5. Screenshots/video
+6. Error logs (if any)
+
+## Update Policy
+
+- Maintenance updates: bug fixes and compatibility.
+- Feature updates: released periodically and announced in changelog.
diff --git a/scripts/build-release.sh b/scripts/build-release.sh
new file mode 100755
index 0000000000000000000000000000000000000000..cffcd0816b141841200f1ecf9e60c7693318685e
--- /dev/null
+++ b/scripts/build-release.sh
@@ -0,0 +1,48 @@
+#!/usr/bin/env bash
+set -euo pipefail
+
+ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
+DIST_DIR="$ROOT_DIR/dist"
+TMP_DIR="$DIST_DIR/tmp-build"
+PLUGIN_SLUG="whatsapp-woocommerce"
+PLUGIN_ZIP="$DIST_DIR/${PLUGIN_SLUG}.zip"
+FULL_ZIP="$DIST_DIR/${PLUGIN_SLUG}-codecanyon-package.zip"
+
+rm -rf "$TMP_DIR"
+mkdir -p "$TMP_DIR/$PLUGIN_SLUG" "$DIST_DIR"
+
+# Copy plugin files
+rsync -av --delete \
+  --exclude='.git' \
+  --exclude='dist' \
+  --exclude='documentation' \
+  --exclude='marketplace' \
+  --exclude='scripts' \
+  --exclude='*.md' \
+  "$ROOT_DIR/" "$TMP_DIR/$PLUGIN_SLUG/"
+
+# Build plugin-only zip
+(
+  cd "$TMP_DIR"
+  zip -rq "$PLUGIN_ZIP" "$PLUGIN_SLUG"
+)
+
+# Build full marketplace package
+mkdir -p "$TMP_DIR/full-package"
+cp "$PLUGIN_ZIP" "$TMP_DIR/full-package/"
+cp -r "$ROOT_DIR/documentation" "$TMP_DIR/full-package/"
+cp -r "$ROOT_DIR/marketplace" "$TMP_DIR/full-package/"
+cp "$ROOT_DIR/CHANGELOG.md" "$TMP_DIR/full-package/"
+cp "$ROOT_DIR/CODECANYON-SUBMISSION-CHECKLIST.md" "$TMP_DIR/full-package/"
+cp "$ROOT_DIR/README.md" "$TMP_DIR/full-package/"
+
+(
+  cd "$TMP_DIR/full-package"
+  zip -rq "$FULL_ZIP" .
+)
+
+rm -rf "$TMP_DIR"
+
+echo "Build complete:"
+echo "- $PLUGIN_ZIP"
+echo "- $FULL_ZIP"
diff --git a/whatsapp-woocommerce.php b/whatsapp-woocommerce.php
index f5b82a7ebd14c9239baef526c627dde7601a766d..87161e3e0fcf2f2d7fc26eca6ab0c80bcd899d0f 100644
--- a/whatsapp-woocommerce.php
+++ b/whatsapp-woocommerce.php
@@ -1,42 +1,47 @@
 <?php
 /**
  * Plugin Name: WhatsApp for WooCommerce
  * Plugin URI:  https://example.com
  * Description: Allow customers to place orders via WhatsApp using a modal form. Includes admin settings and order logging.
  * Version:     2.0
- * Author:      Your Name
- * Author URI:  https://example.com
+ * Author:      Abuzarr Ghafaari
+ * Author URI:  https://www.codegeniusoft.com
  * Text Domain: whatsapp-woocommerce
  * Domain Path: /languages
  * WC tested up to: 8.0
  */
 
 if ( ! defined( 'ABSPATH' ) ) {
 	exit;
 }
 
+// Prevent duplicate loading (e.g., old + new plugin copies active together).
+if ( defined( 'WCWA_PLUGIN_FILE' ) ) {
+	return;
+}
+
 if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
 	return;
 }
 
 define( 'WCWA_VERSION', '2.0' );
 define( 'WCWA_PLUGIN_FILE', __FILE__ );
 define( 'WCWA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
 define( 'WCWA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
 
 // Autoload includes.
 require_once WCWA_PLUGIN_DIR . 'includes/class-wc-whatsapp-logger.php';
 require_once WCWA_PLUGIN_DIR . 'includes/class-wc-whatsapp-admin-menu.php';
 
 /**
  * Enqueue front-end assets.
  */
 function wcwa_enqueue_assets() {
 	wp_enqueue_style( 'wcwa-modal', WCWA_PLUGIN_URL . 'assets/css/whatsapp-modal.css', array(), WCWA_VERSION );
 	wp_enqueue_script( 'wcwa-modal', WCWA_PLUGIN_URL . 'assets/js/whatsapp-modal.js', array( 'jquery' ), WCWA_VERSION, true );
 
 	wp_localize_script(
 		'wcwa-modal',
 		'wcwa_ajax',
 		array(
 			'ajax_url' => admin_url( 'admin-ajax.php' ),
@@ -271,53 +276,63 @@ function wcwa_ajax_process_order() {
     $message = wcwa_build_message( $context, $data );
 
     // Log order and store code meta.
     $log_id = wcwa_log_order( $subject, $message, $customer_name, $customer_phone );
     if ( ! is_wp_error( $log_id ) ) {
         update_post_meta( $log_id, '_order_code', $order_code );
     }
 
     // Resolve destination number with per-product override and filters.
     $recipient = $whatsapp_number;
     if ( 'product' === $context ) {
         $override_number = get_post_meta( $variation_id ? $product_id : $product_id, 'wcwa_product_whatsapp_number_override', true );
         if ( $override_number ) {
             $recipient = $override_number;
             $data['had_override'] = true;
         }
     }
     $recipient = apply_filters( 'wcwa_destination_number', $recipient, $context, $data );
 
     // Normalize recipient to digits-only.
     $recipient = preg_replace( '/\D+/', '', (string) $recipient );
     if ( empty( $recipient ) ) {
         wp_send_json_error( array( 'message' => __( 'Invalid destination number.', 'whatsapp-woocommerce' ) ) );
     }
 
-    // Build WhatsApp link.
-    $wa_url = 'https://wa.me/' . rawurlencode( $recipient ) . '?text=' . rawurlencode( $message );
-    wp_send_json_success( array( 'whatsapp_url' => $wa_url ) );
+    // Build WhatsApp links for better cross-device compatibility.
+    $encoded_message = rawurlencode( $message );
+    $wa_url          = 'https://wa.me/' . $recipient . '?text=' . $encoded_message;
+    $api_url         = 'https://api.whatsapp.com/send?phone=' . $recipient . '&text=' . $encoded_message;
+    $web_url         = 'https://web.whatsapp.com/send?phone=' . $recipient . '&text=' . $encoded_message;
+
+    wp_send_json_success(
+        array(
+            'whatsapp_url'     => $wa_url,
+            'whatsapp_api_url' => $api_url,
+            'whatsapp_web_url' => $web_url,
+        )
+    );
 }
 add_action( 'wp_ajax_wcwa_process_order', 'wcwa_ajax_process_order' );
 add_action( 'wp_ajax_nopriv_wcwa_process_order', 'wcwa_ajax_process_order' );
 
 /**
  * Message builder with optional templates and filter.
  */
 function wcwa_build_message( $context, $data ) {
     $template = '';
     if ( 'product' === $context ) {
         $template = get_option( 'wcwa_template_product', '' );
     } else {
         $template = get_option( 'wcwa_template_cart', '' );
     }
     if ( ! empty( $template ) ) {
         $replacements = array(
             '{customer_name}'  => $data['customer_name'],
             '{customer_phone}' => $data['customer_phone'],
             '{address}'        => $data['address'],
             '{notes}'          => $data['notes'],
             '{cart_lines}'     => isset( $data['order_lines'] ) ? $data['order_lines'] : '',
             '{product_name}'   => isset( $data['product_name'] ) ? $data['product_name'] : '',
             '{product_price}'  => isset( $data['product_price'] ) ? $data['product_price'] : '',
             '{product_link}'   => isset( $data['product_link'] ) ? $data['product_link'] : '',
             '{product_id}'     => isset( $data['product_id'] ) ? $data['product_id'] : '',
@@ -550,30 +565,31 @@ function wcwa_format_price_plain( $amount ) {
 }
 function wcwa_generate_unique_code() {
     $attempts = 0;
     do {
         $code = (string) wp_rand( 1000000, 9999999 );
         $q = new WP_Query( array(
             'post_type'      => 'wcwa_order_log',
             'post_status'    => 'publish',
             'posts_per_page' => 1,
             'no_found_rows'  => true,
             'fields'         => 'ids',
             'meta_query'     => array( array( 'key' => '_order_code', 'value' => $code ) ),
         ) );
         $exists = is_a( $q, 'WP_Query' ) && ! empty( $q->posts );
         $attempts++;
     } while ( $exists && $attempts < 5 );
     if ( $exists ) {
         $code = substr( preg_replace( '/\D/', '', (string) microtime( true ) . (string) wp_rand() ), 0, 7 );
         if ( strlen( $code ) < 7 ) {
             $code = str_pad( $code, 7, '0' );
         }
     }
     return $code;
 }
 add_action( 'before_woocommerce_init', function() {
-    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
-        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
-        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', __FILE__, true );
+    $features_util_class = 'Automattic\\WooCommerce\\Utilities\\FeaturesUtil';
+    if ( class_exists( $features_util_class ) ) {
+        call_user_func( array( $features_util_class, 'declare_compatibility' ), 'custom_order_tables', __FILE__, true );
+        call_user_func( array( $features_util_class, 'declare_compatibility' ), 'orders_cache', __FILE__, true );
     }
 } );
