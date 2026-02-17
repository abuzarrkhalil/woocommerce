<?php
/**
 * Plugin Name: WhatsApp for WooCommerce
 * Plugin URI:  https://example.com
 * Description: Allow customers to place orders via WhatsApp using a modal form. Includes admin settings and order logging.
 * Version:     2.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: whatsapp-woocommerce
 * Domain Path: /languages
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		)
	);
}
add_action( 'wp_enqueue_scripts', 'wcwa_enqueue_assets' );

/**
 * Add "Order on WhatsApp" button on single product pages.
 */
function wcwa_add_product_button() {
    global $product;
    if ( ! $product ) {
        return;
    }
    static $wcwa_printed = false;
    if ( $wcwa_printed ) {
        return;
    }
    if ( '1' !== get_option( 'wcwa_enable_product_button', '1' ) ) {
        return;
    }
    if ( ! wcwa_should_show_button_for_product( $product ) ) {
        return;
    }
    $pos = get_option( 'wcwa_product_button_position', 'bottom_left' );
    $align = 'flex-start';
    if ( strpos( $pos, '_center' ) !== false ) {
        $align = 'center';
    } elseif ( strpos( $pos, '_right' ) !== false ) {
        $align = 'flex-end';
    }
    $wrap_style = 'display:flex;justify-content:' . esc_attr( $align ) . ';gap:8px;margin:10px 0;width:100%;';
    $anchor_style = 'display:inline-block';
    if ( $align === 'center' ) {
        $anchor_style = 'display:block;margin:0 auto;text-align:center';
    } elseif ( $align === 'flex-end' ) {
        $anchor_style = 'display:inline-block;margin-left:auto';
    }
    // Per-product button text override.
    $override = get_post_meta( $product->get_id(), 'wcwa_button_text_override', true );
    $text = get_option( 'wcwa_button_text_product', __( 'Order on WhatsApp', 'whatsapp-woocommerce' ) );
    $text = $override ? $override : $text;
    echo '<div class="wcwa-product-button-wrap" style="' . $wrap_style . '">';
    echo '<a href="#" class="whatsapp-order-button button alt" style="' . esc_attr( $anchor_style ) . '" data-product-id="' . esc_attr( $product->get_id() ) . '">' . esc_html( $text ) . '</a>';
    echo '</div>';
    $wcwa_printed = true;
}
function wcwa_hook_product_button() {
    $pos = get_option( 'wcwa_product_button_position', 'bottom_left' );
    if ( strpos( $pos, 'top_' ) === 0 ) {
        add_action( 'woocommerce_before_single_variation', 'wcwa_add_product_button', 10 );
        add_action( 'woocommerce_before_add_to_cart_button', 'wcwa_add_product_button', 10 );
    } else {
        add_action( 'woocommerce_single_product_summary', 'wcwa_add_product_button', 35 );
    }
}
add_action( 'init', 'wcwa_hook_product_button' );


/**
 * Replace "Proceed to checkout" with WhatsApp button on cart page.
 */
function wcwa_replace_cart_checkout_button() {
    if ( ! is_cart() ) {
        return;
    }
    if ( '1' !== get_option( 'wcwa_enable_cart_button', '1' ) ) {
        return;
    }
    if ( ! wcwa_is_within_store_hours() ) {
        return;
    }
    $text = get_option( 'wcwa_button_text_cart', __( 'Proceed to WhatsApp Order', 'whatsapp-woocommerce' ) );
    echo '<a href="#" class="whatsapp-order-cart-button button alt" style="display:block;text-align:center;margin-top:15px;">' . esc_html( $text ) . '</a>';
}
add_action( 'woocommerce_after_cart_totals', 'wcwa_replace_cart_checkout_button' );

add_action( 'wp', function() {
    if ( is_product() && '1' !== get_option( 'wcwa_enable_add_to_cart_button', '1' ) ) {
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
    }
    if ( is_cart() && '1' !== get_option( 'wcwa_enable_checkout_button', '1' ) ) {
        remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
    }
} );

/**
 * Output modal HTML (hidden by default).
 */
function wcwa_output_modal() {
	?>
	<div id="whatsapp-order-modal" class="whatsapp-modal-overlay">
		<div class="whatsapp-modal-content">
			<span class="whatsapp-close-button">&times;</span>
			<h3><?php esc_html_e( 'Complete Your Order via WhatsApp', 'whatsapp-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'Please provide your details to send your order request.', 'whatsapp-woocommerce' ); ?></p>
            <form id="whatsapp-order-form">
                <?php wp_nonce_field( 'wcwa_order_nonce', 'wcwa_order_nonce_field' ); ?>
                <input type="text" id="customer_name" placeholder="<?php esc_attr_e( 'Full Name', 'whatsapp-woocommerce' ); ?>" required>
                <input type="tel" id="customer_phone" placeholder="<?php esc_attr_e( 'Phone Number', 'whatsapp-woocommerce' ); ?>" required>
                <textarea id="customer_address" rows="3" placeholder="<?php esc_attr_e( 'Delivery Address', 'whatsapp-woocommerce' ); ?>" required></textarea>
                <textarea id="customer_notes" rows="2" placeholder="<?php esc_attr_e( 'Additional Notes (Optional)', 'whatsapp-woocommerce' ); ?>"></textarea>
                <label>
                    <input type="checkbox" id="gdpr_consent" required>
                    <?php esc_html_e( 'I agree to share my details for this order.', 'whatsapp-woocommerce' ); ?>
                </label>
                <button type="submit" id="send-whatsapp-order" class="button alt"><?php esc_html_e( 'Send Order', 'whatsapp-woocommerce' ); ?></button>
                <input type="hidden" id="product_id" value="">
                <input type="hidden" id="is_cart_order" value="0">
                <input type="hidden" id="variation_id" value="">
                <input type="hidden" id="quantity" value="1">
                <input type="hidden" id="attributes_json" value="">
            </form>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'wcwa_output_modal' );

/**
 * AJAX handler: build WhatsApp message, log order, return wa.me link.
 */
function wcwa_ajax_process_order() {
    check_ajax_referer( 'wcwa_order_nonce', 'nonce' );

    $whatsapp_number = get_option( 'wcwa_whatsapp_number', '' );
    if ( empty( $whatsapp_number ) ) {
        wp_send_json_error( array( 'message' => __( 'WhatsApp number not configured.', 'whatsapp-woocommerce' ) ) );
    }

	$customer_name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$customer_phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$address        = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
	$notes          = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';
	$is_cart        = isset( $_POST['is_cart_order'] ) ? sanitize_text_field( wp_unslash( $_POST['is_cart_order'] ) ) : '0';

	if ( empty( $customer_name ) || empty( $customer_phone ) || empty( $address ) ) {
		wp_send_json_error( array( 'message' => __( 'Missing required fields.', 'whatsapp-woocommerce' ) ) );
	}

    $consent = isset( $_POST['consent'] ) ? sanitize_text_field( wp_unslash( $_POST['consent'] ) ) : '0';
    if ( $consent !== '1' ) {
        wp_send_json_error( array( 'message' => __( 'Consent is required.', 'whatsapp-woocommerce' ) ) );
    }

    $order_lines = array();
    $subject     = '';

    if ( '1' === $is_cart ) {
        $subject = __( 'New Cart Order Request', 'whatsapp-woocommerce' );
        foreach ( WC()->cart->get_cart() as $item ) {
            $product = $item['data'];
            $amount  = wc_get_price_to_display( $product );
            $price_t = wcwa_format_price_plain( $amount );
            $order_lines[] = sprintf(
                "- %s\nQty: %d\nPrice: %s\nID: %d\nLink: %s\n",
                $product->get_name(),
                $item['quantity'],
                $price_t,
                $product->get_id(),
                get_permalink( $product->get_id() )
            );
        }
    } else {
        $product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
        $quantity     = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1;
        $attrs_json   = isset( $_POST['attributes_json'] ) ? wp_unslash( $_POST['attributes_json'] ) : '';
        $attrs        = array();
        if ( is_string( $attrs_json ) && $attrs_json !== '' ) {
            $decoded = json_decode( $attrs_json, true );
            if ( is_array( $decoded ) ) { $attrs = $decoded; }
        }

        $product    = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );
        if ( ! $product ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'whatsapp-woocommerce' ) ) );
        }
        $subject       = __( 'New Product Order Request', 'whatsapp-woocommerce' );
        $amount  = wc_get_price_to_display( $product );
        $price_t = wcwa_format_price_plain( $amount );
        $line  = sprintf( "- Product: %s\n- Price: %s\n- Qty: %d\n- ID: %d\n- Link: %s",
            $product->get_name(),
            $price_t,
            $quantity,
            $variation_id ? $variation_id : $product_id,
            get_permalink( $variation_id ? $variation_id : $product_id )
        );
        if ( ! empty( $attrs ) ) {
            $line .= "\n- Attributes:";
            foreach ( $attrs as $k => $v ) {
                $line .= "\n  • " . sanitize_text_field( $k ) . ": " . sanitize_text_field( (string) $v );
            }
        }
        $order_lines[] = $line;
    }

    // Build message using templates if available.
    $order_code = wcwa_generate_unique_code();
    $context = ( '1' === $is_cart ) ? 'cart' : 'product';
    $data    = array(
        'customer_name'  => $customer_name,
        'customer_phone' => $customer_phone,
        'address'        => $address,
        'notes'          => $notes,
        'order_lines'    => implode( "\n", $order_lines ),
        'order_code'     => $order_code,
    );
    if ( 'product' === $context ) {
        $data['product_name']  = $product->get_name();
        $data['product_price'] = wcwa_format_price_plain( wc_get_price_to_display( $product ) );
        $data['product_link']  = get_permalink( $variation_id ? $variation_id : $product_id );
        $data['product_id']    = $variation_id ? $variation_id : $product_id;
        $data['quantity']      = isset( $quantity ) ? (int) $quantity : 1;
        if ( ! empty( $attrs ) ) { $data['product_attributes'] = $attrs; }
    }
    // Add helpful metadata for routing
    if ( 'product' === $context ) {
        $data['product_cat_ids'] = method_exists( $product, 'get_category_ids' ) ? (array) $product->get_category_ids() : array();
    } else {
        $cart_cat_ids = array();
        foreach ( WC()->cart->get_cart() as $ci ) {
            $p = isset( $ci['data'] ) ? $ci['data'] : null;
            if ( $p && method_exists( $p, 'get_category_ids' ) ) {
                $cart_cat_ids = array_merge( $cart_cat_ids, (array) $p->get_category_ids() );
            }
        }
        $data['cart_cat_ids'] = array_values( array_unique( array_map( 'intval', $cart_cat_ids ) ) );
    }
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

    // Build WhatsApp links for better cross-device compatibility.
    $encoded_message = rawurlencode( $message );
    $wa_url          = 'https://wa.me/' . $recipient . '?text=' . $encoded_message;
    $api_url         = 'https://api.whatsapp.com/send?phone=' . $recipient . '&text=' . $encoded_message;
    $web_url         = 'https://web.whatsapp.com/send?phone=' . $recipient . '&text=' . $encoded_message;

    wp_send_json_success(
        array(
            'whatsapp_url'     => $wa_url,
            'whatsapp_api_url' => $api_url,
            'whatsapp_web_url' => $web_url,
        )
    );
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
            '{order_code}'     => isset( $data['order_code'] ) ? $data['order_code'] : '',
            '{quantity}'       => isset( $data['quantity'] ) ? (string) $data['quantity'] : '',
            '{attributes}'     => isset( $data['product_attributes'] ) && is_array( $data['product_attributes'] ) ? wcwa_format_attributes_inline( $data['product_attributes'] ) : '',
        );
        $message = strtr( $template, $replacements );
    } else {
        $message = sprintf(
            "Hello, I'd like to place an order!\n\n" .
            "*--- Customer Details ---*\n" .
            "- Name: %s\n" .
            "- Phone: %s\n" .
            "- Address: %s\n" .
            "- Notes: %s\n" .
            "- Order Code: %s\n\n" .
            "*--- Order Details ---*\n%s",
            $data['customer_name'],
            $data['customer_phone'],
            $data['address'],
            $data['notes'],
            isset( $data['order_code'] ) ? $data['order_code'] : '',
            isset( $data['order_lines'] ) ? $data['order_lines'] : ''
        );
    }
    return apply_filters( 'wcwa_build_message', $message, $context, $data );
}

function wcwa_format_attributes_inline( $attrs ) {
    if ( ! is_array( $attrs ) ) {
        return '';
    }
    $parts = array();
    foreach ( $attrs as $k => $v ) {
        $kk = sanitize_text_field( (string) $k );
        $vv = sanitize_text_field( (string) $v );
        if ( $kk !== '' && $vv !== '' ) {
            $parts[] = $kk . ': ' . $vv;
        }
    }
    return implode( ', ', $parts );
}

/**
 * Add nonce to localized script.
 */
function wcwa_add_nonce_to_script( $data ) {
	$data['nonce'] = wp_create_nonce( 'wcwa_order_nonce' );
	return $data;
}
add_filter( 'whatsapp_woocommerce_script_data', 'wcwa_add_nonce_to_script' );

// Localize nonce on enqueue.
add_filter(
	'wcwa_ajax_data',
	function( $data ) {
		$data['nonce'] = wp_create_nonce( 'wcwa_order_nonce' );
		return $data;
	}
);

/**
 * Add Settings action link on Plugins screen.
 *
 * @param array $links Existing links.
 * @return array
 */
function wcwa_plugin_action_links( $links ) {
    $settings_url = admin_url( 'admin.php?page=wcwa-settings' );
    array_unshift( $links, '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'whatsapp-woocommerce' ) . '</a>' );
    $un_url = admin_url( 'admin.php?page=wcwa-settings&tab=general#uninstall' );
    $links[] = '<a href="' . esc_url( $un_url ) . '">' . esc_html__( 'Uninstall Behavior', 'whatsapp-woocommerce' ) . '</a>';
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( WCWA_PLUGIN_FILE ), 'wcwa_plugin_action_links' );

/**
 * Flush rewrite rules on activation so the CPT admin menu appears immediately.
 */
function wcwa_activate() {
	wcwa_register_order_cpt();
	flush_rewrite_rules();
}
register_activation_hook( WCWA_PLUGIN_FILE, 'wcwa_activate' );

// Flush once on next admin init in case user re-activated via UI.
if ( ! get_option( 'wcwa_flushed_once' ) ) {
	add_action( 'admin_init', function() {
		flush_rewrite_rules();
		update_option( 'wcwa_flushed_once', 1 );
	}, 100 );
}

/**
 * Clean up on deactivation.
 */
function wcwa_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( WCWA_PLUGIN_FILE, 'wcwa_deactivate' );

/**
 * Show an admin notice until the user visits the new menu once.
 */
function wcwa_admin_notice_menu() {
	if ( get_option( 'wcwa_menu_seen' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-success is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
		esc_html__( 'WhatsApp for WooCommerce is ready! Please visit the new', 'whatsapp-woocommerce' ),
		admin_url( 'admin.php?page=wcwa-settings' ),
		esc_html__( 'Settings page', 'whatsapp-woocommerce' )
	);
	update_option( 'wcwa_menu_seen', 1 );
}
add_action( 'admin_notices', 'wcwa_admin_notice_menu' );
function wcwa_should_show_button_for_product( $product ) {
    if ( '1' === get_option( 'wcwa_show_only_instock', '0' ) && ! $product->is_in_stock() ) {
        return false;
    }
    $mode = get_option( 'wcwa_display_mode', 'include' );
    $cats = (array) get_option( 'wcwa_display_categories', array() );
    if ( ! empty( $cats ) ) {
        $product_cats = (array) $product->get_category_ids();
        $intersect = array_intersect( array_map( 'intval', $cats ), array_map( 'intval', $product_cats ) );
        if ( 'include' === $mode && empty( $intersect ) ) {
            return false;
        }
        if ( 'exclude' === $mode && ! empty( $intersect ) ) {
            return false;
        }
    }
    if ( ! wcwa_is_within_store_hours() ) {
        return false;
    }
    return true;
}

function wcwa_is_within_store_hours() {
    if ( '1' !== get_option( 'wcwa_store_hours_enable', '0' ) ) {
        return true;
    }
    $open  = trim( (string) get_option( 'wcwa_store_hours_open', '' ) );
    $close = trim( (string) get_option( 'wcwa_store_hours_close', '' ) );
    $days  = (array) get_option( 'wcwa_store_hours_days', array() );
    $tz    = wp_timezone();
    $now   = new DateTimeImmutable( 'now', $tz );
    $day   = strtolower( $now->format( 'D' ) ); // mon,tue,...

    // Validate HH:MM format (00:00–23:59). "24:00" is not supported.
    $time_re = '/^(?:[01]?\d|2[0-3]):[0-5]\d$/';
    if ( empty( $open ) || empty( $close ) || ! preg_match( $time_re, $open ) || ! preg_match( $time_re, $close ) ) {
        return true;
    }

    // Convert to minutes since midnight.
    list( $oh, $om ) = array_map( 'intval', explode( ':', $open ) );
    list( $ch, $cm ) = array_map( 'intval', explode( ':', $close ) );
    $open_min  = $oh * 60 + $om;
    $close_min = $ch * 60 + $cm;
    $now_min   = (int) $now->format( 'H' ) * 60 + (int) $now->format( 'i' );

    $days_order = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
    $idx        = array_search( $day, $days_order, true );
    $prev_day   = $days_order[ $idx === false ? 0 : ( $idx + 6 ) % 7 ];

    if ( $open_min <= $close_min ) {
        // Same-day window
        if ( ! in_array( $day, $days, true ) ) {
            return false;
        }
        return ( $now_min >= $open_min && $now_min <= $close_min );
    } else {
        // Overnight window: e.g., 22:00–02:00
        // Segment A (current day): time >= open -> require current day selected
        if ( $now_min >= $open_min ) {
            return in_array( $day, $days, true );
        }
        // Segment B (after midnight): time <= close -> require previous day selected
        if ( $now_min <= $close_min ) {
            return in_array( $prev_day, $days, true );
        }
        return false;
    }
}
/**
 * Routing filter: choose destination number based on saved rules.
 */
function wcwa_apply_routing_rules( $recipient, $context, $data ) {
    $rules = get_option( 'wcwa_routing_rules', array() );
    if ( empty( $rules ) || ! is_array( $rules ) ) return $recipient;
    if ( isset( $data['had_override'] ) && $data['had_override'] ) return $recipient;
    $ctx = $context === 'product' ? 'product' : 'cart';
    $cats = array();
    if ( $ctx === 'product' && isset( $data['product_cat_ids'] ) ) {
        $cats = array_map( 'intval', (array) $data['product_cat_ids'] );
    } elseif ( $ctx === 'cart' && isset( $data['cart_cat_ids'] ) ) {
        $cats = array_map( 'intval', (array) $data['cart_cat_ids'] );
    }
    foreach ( $rules as $rule ) {
        if ( ! is_array( $rule ) ) continue;
        $num  = isset( $rule['number'] ) ? preg_replace( '/[^0-9]/', '', (string) $rule['number'] ) : '';
        if ( $num === '' ) continue;
        $rctx = isset( $rule['context'] ) ? (string) $rule['context'] : 'any';
        $mode = isset( $rule['mode'] ) ? (string) $rule['mode'] : 'any';
        $rcats = isset( $rule['categories'] ) && is_array( $rule['categories'] ) ? array_map( 'intval', $rule['categories'] ) : array();
        if ( $rctx !== 'any' && $rctx !== $ctx ) continue;
        if ( $mode === 'any' ) {
            return $num;
        }
        $intersect = array_intersect( $cats, $rcats );
        if ( $mode === 'include' && ! empty( $intersect ) ) {
            return $num;
        }
        if ( $mode === 'exclude' && empty( $intersect ) ) {
            return $num;
        }
    }
    return $recipient;
}
add_filter( 'wcwa_destination_number', 'wcwa_apply_routing_rules', 10, 3 );

function wcwa_format_price_plain( $amount ) {
    $dp  = (int) wc_get_price_decimals();
    $ds  = wc_get_price_decimal_separator();
    $ts  = wc_get_price_thousand_separator();
    return number_format( (float) $amount, $dp, $ds, $ts );
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
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', __FILE__, true );
    }
} );
