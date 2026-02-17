<?php
/**
 * WC WhatsApp Admin Menu
 *
 * @package WhatsApp_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove the old WooCommerce settings tab (if any) so only our menu is used.
 */
function wcwa_remove_wc_tab( $tabs ) {
	unset( $tabs['whatsapp_order'] );
	return $tabs;
}
add_filter( 'woocommerce_settings_tabs_array', 'wcwa_remove_wc_tab', 200 );

/**
 * Declare the old tab incompatible with React-admin so WooCommerce stops warning.
 */
add_filter(
	'woocommerce_settings_tab_is_compatible',
	function( $is_compatible, $tab_id ) {
		if ( $tab_id === 'whatsapp_order' ) {
			return false; // force legacy handling, no React needed
		}
		return $is_compatible;
	},
	10,
	2
);

/**
 * Suppress React-admin warning for removed tab.
 */
add_filter(
	'woocommerce_admin_get_feature_config',
	function( $config ) {
		unset( $config['settings']['tabs']['whatsapp_order'] );
		return $config;
	},
	200
);

/**
 * Suppress the “incompatible features” admin banner completely for this plugin.
 */
add_filter(
    'woocommerce_admin_incompatible_features_enabled',
    function( $enabled ) {
        // If the current screen is our own pages, do not show the banner.
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'wcwa-settings' ) {
            return false;
        }
        return $enabled;
    }
);

add_filter( 'gettext', function( $translation, $text, $domain ) {
    if ( ! is_admin() ) {
        return $translation;
    }
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( $screen && isset( $screen->post_type ) && $screen->post_type === 'wcwa_order_log' ) {
        if ( $text === 'Publish' || $text === 'Update' ) {
            return __( 'Update Record', 'whatsapp-woocommerce' );
        }
    }
    return $translation;
}, 10, 3 );

/**
 * Create top-level menu and sub-pages.
 */
function wcwa_admin_menu() {
	add_menu_page(
		__( 'WhatsApp', 'whatsapp-woocommerce' ),
		__( 'WhatsApp', 'whatsapp-woocommerce' ),
		'manage_woocommerce',
		'wcwa-settings',
		'wcwa_settings_page_html',
		'dashicons-whatsapp',
		26
	);

	add_submenu_page(
		'wcwa-settings',
		__( 'Settings', 'whatsapp-woocommerce' ),
		__( 'Settings', 'whatsapp-woocommerce' ),
		'manage_woocommerce',
		'wcwa-settings',
		'wcwa_settings_page_html'
	);

	// CPT list table.
	add_submenu_page(
		'wcwa-settings',
		__( 'All Orders', 'whatsapp-woocommerce' ),
		__( 'All Orders', 'whatsapp-woocommerce' ),
		'manage_woocommerce',
		'edit.php?post_type=wcwa_order_log'
	);

	// CPT add new.
	add_submenu_page(
		'wcwa-settings',
		__( 'Add New Order', 'whatsapp-woocommerce' ),
		__( 'Add New Order', 'whatsapp-woocommerce' ),
		'manage_woocommerce',
		'post-new.php?post_type=wcwa_order_log'
	);
}
add_action( 'admin_menu', 'wcwa_admin_menu' );

/**
 * Change CPT menu parent so All Orders / Add New appear under our menu.
 */
function wcwa_cpt_menu_parent( $args, $post_type ) {
	if ( $post_type === 'wcwa_order_log' ) {
		$args['show_in_menu'] = 'wcwa-settings';
	}
	return $args;
}
add_filter( 'register_post_type_args', 'wcwa_cpt_menu_parent', 10, 2 );

/**
 * Product edit screen: per-product overrides.
 */
function wcwa_product_overrides_metabox() {
    add_meta_box(
        'wcwa_product_overrides',
        __( 'WhatsApp Order Overrides', 'whatsapp-woocommerce' ),
        'wcwa_product_overrides_render',
        'product',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'wcwa_product_overrides_metabox' );

function wcwa_product_overrides_render( $post ) {
    wp_nonce_field( 'wcwa_product_overrides_nonce', 'wcwa_product_overrides_nonce_field' );
    $btn_text = get_post_meta( $post->ID, 'wcwa_button_text_override', true );
    $number   = get_post_meta( $post->ID, 'wcwa_product_whatsapp_number_override', true );
    echo '<p><label for="wcwa_button_text_override">' . esc_html__( 'Button Text', 'whatsapp-woocommerce' ) . '</label><br/>';
    echo '<input type="text" id="wcwa_button_text_override" name="wcwa_button_text_override" value="' . esc_attr( $btn_text ) . '" class="widefat" /></p>';
    echo '<p><label for="wcwa_product_whatsapp_number_override">' . esc_html__( 'WhatsApp Number', 'whatsapp-woocommerce' ) . '</label><br/>';
    echo '<input type="text" id="wcwa_product_whatsapp_number_override" name="wcwa_product_whatsapp_number_override" value="' . esc_attr( $number ) . '" class="widefat" /></p>';
}

function wcwa_product_overrides_save( $post_id ) {
    if ( ! isset( $_POST['wcwa_product_overrides_nonce_field'] ) || ! wp_verify_nonce( $_POST['wcwa_product_overrides_nonce_field'], 'wcwa_product_overrides_nonce' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( isset( $_POST['post_type'] ) && 'product' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }
    if ( isset( $_POST['wcwa_button_text_override'] ) ) {
        update_post_meta( $post_id, 'wcwa_button_text_override', sanitize_text_field( $_POST['wcwa_button_text_override'] ) );
    }
    if ( isset( $_POST['wcwa_product_whatsapp_number_override'] ) ) {
        update_post_meta( $post_id, 'wcwa_product_whatsapp_number_override', sanitize_text_field( $_POST['wcwa_product_whatsapp_number_override'] ) );
    }
}
add_action( 'save_post_product', 'wcwa_product_overrides_save' );

function wcwa_order_code_metabox() {
    add_meta_box(
        'wcwa_order_code',
        __( 'Order Code', 'whatsapp-woocommerce' ),
        'wcwa_order_code_render',
        'wcwa_order_log',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'wcwa_order_code_metabox' );

function wcwa_order_code_render( $post ) {
    $code = get_post_meta( $post->ID, '_order_code', true );
    echo '<style>.wcwa-order-code-box{background:#f6f7f7;border:1px solid #ccd0d4;border-radius:4px;padding:10px;margin:0}.wcwa-order-code-box input{width:100%;box-sizing:border-box;background:#fff;border:1px solid #ccd0d4;border-radius:3px}</style>';
    echo '<div class="wcwa-order-code-box">';
    echo '<input type="text" readonly value="' . esc_attr( $code ) . '" class="widefat" style="text-align:center;font-weight:600" />';
    echo '</div>';
}

/**
 * Render the Settings page HTML.
 */
function wcwa_settings_page_html() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

    if ( isset( $_POST['wcwa_save_general'] ) && check_admin_referer( 'wcwa_settings_nonce' ) ) {
        update_option( 'wcwa_whatsapp_number', sanitize_text_field( $_POST['wcwa_whatsapp_number'] ) );
        update_option( 'wcwa_button_text_product', sanitize_text_field( $_POST['wcwa_button_text_product'] ) );
        update_option( 'wcwa_button_text_cart', sanitize_text_field( $_POST['wcwa_button_text_cart'] ) );
        $enable_product = isset( $_POST['wcwa_enable_product_button'] ) ? '1' : '0';
        $enable_cart    = isset( $_POST['wcwa_enable_cart_button'] ) ? '1' : '0';
        update_option( 'wcwa_enable_product_button', $enable_product );
        update_option( 'wcwa_enable_cart_button', $enable_cart );
        update_option( 'wcwa_enable_add_to_cart_button', isset( $_POST['wcwa_enable_add_to_cart_button'] ) ? '1' : '0' );
        update_option( 'wcwa_enable_checkout_button', isset( $_POST['wcwa_enable_checkout_button'] ) ? '1' : '0' );
        $pos = isset( $_POST['wcwa_product_button_position'] ) ? sanitize_key( $_POST['wcwa_product_button_position'] ) : 'bottom_left';
        if ( ! in_array( $pos, array( 'top_left','top_center','bottom_left','bottom_center' ), true ) ) { $pos = 'bottom_left'; }
        update_option( 'wcwa_product_button_position', $pos );
        $unact = isset( $_POST['wcwa_uninstall_action'] ) ? sanitize_key( $_POST['wcwa_uninstall_action'] ) : 'keep';
        if ( ! in_array( $unact, array( 'keep','delete' ), true ) ) { $unact = 'keep'; }
        update_option( 'wcwa_uninstall_action', $unact );
        echo '<div class="updated notice"><p>' . esc_html__( 'General settings saved.', 'whatsapp-woocommerce' ) . '</p></div>';
    }
    if ( isset( $_POST['wcwa_save_messaging'] ) && check_admin_referer( 'wcwa_settings_nonce' ) ) {
        $tpl_product = isset( $_POST['wcwa_template_product'] ) ? wp_unslash( $_POST['wcwa_template_product'] ) : '';
        $tpl_cart    = isset( $_POST['wcwa_template_cart'] ) ? wp_unslash( $_POST['wcwa_template_cart'] ) : '';
        update_option( 'wcwa_template_product', wp_kses_post( $tpl_product ) );
        update_option( 'wcwa_template_cart', wp_kses_post( $tpl_cart ) );
        echo '<div class="updated notice"><p>' . esc_html__( 'Messaging templates saved.', 'whatsapp-woocommerce' ) . '</p></div>';
    }
	if ( isset( $_POST['wcwa_save_display'] ) && check_admin_referer( 'wcwa_settings_nonce' ) ) {
		update_option( 'wcwa_show_only_instock', isset( $_POST['wcwa_show_only_instock'] ) ? '1' : '0' );
		$mode = isset( $_POST['wcwa_display_mode'] ) ? sanitize_key( $_POST['wcwa_display_mode'] ) : 'include';
		update_option( 'wcwa_display_mode', in_array( $mode, array( 'include', 'exclude' ), true ) ? $mode : 'include' );
		$cats = isset( $_POST['wcwa_display_categories'] ) && is_array( $_POST['wcwa_display_categories'] ) ? array_map( 'absint', $_POST['wcwa_display_categories'] ) : array();
		update_option( 'wcwa_display_categories', $cats );
		$hours_enable = isset( $_POST['wcwa_store_hours_enable'] ) ? '1' : '0';
		update_option( 'wcwa_store_hours_enable', $hours_enable );
		$open = isset( $_POST['wcwa_store_hours_open'] ) ? sanitize_text_field( $_POST['wcwa_store_hours_open'] ) : '';
		$close = isset( $_POST['wcwa_store_hours_close'] ) ? sanitize_text_field( $_POST['wcwa_store_hours_close'] ) : '';
		update_option( 'wcwa_store_hours_open', $open );
		update_option( 'wcwa_store_hours_close', $close );
		$days = isset( $_POST['wcwa_store_days'] ) && is_array( $_POST['wcwa_store_days'] ) ? array_map( 'sanitize_key', $_POST['wcwa_store_days'] ) : array();
		$days = array_values( array_intersect( $days, array( 'mon','tue','wed','thu','fri','sat','sun' ) ) );
		update_option( 'wcwa_store_hours_days', $days );
		echo '<div class="updated notice"><p>' . esc_html__( 'Display rules saved.', 'whatsapp-woocommerce' ) . '</p></div>';
	}

	if ( isset( $_POST['wcwa_save_routing'] ) && check_admin_referer( 'wcwa_settings_nonce' ) ) {
		$posted   = isset( $_POST['routing'] ) && is_array( $_POST['routing'] ) ? $_POST['routing'] : array();
		$numbers  = isset( $posted['number'] ) && is_array( $posted['number'] ) ? $posted['number'] : array();
		$contexts = isset( $posted['context'] ) && is_array( $posted['context'] ) ? $posted['context'] : array();
		$modes    = isset( $posted['mode'] ) && is_array( $posted['mode'] ) ? $posted['mode'] : array();
		$cats     = isset( $posted['categories'] ) && is_array( $posted['categories'] ) ? $posted['categories'] : array();
		$compiled = array();
		$rows     = max( count( $numbers ), count( $contexts ), count( $modes ) );
		for ( $i = 0; $i < $rows; $i++ ) {
			$num = isset( $numbers[ $i ] ) ? preg_replace( '/[^0-9]/', '', sanitize_text_field( $numbers[ $i ] ) ) : '';
			if ( $num === '' ) { continue; }
			$ctx = isset( $contexts[ $i ] ) ? sanitize_text_field( $contexts[ $i ] ) : 'any';
			if ( ! in_array( $ctx, array( 'any', 'product', 'cart' ), true ) ) { $ctx = 'any'; }
			$mod = isset( $modes[ $i ] ) ? sanitize_text_field( $modes[ $i ] ) : 'any';
			if ( ! in_array( $mod, array( 'any', 'include', 'exclude' ), true ) ) { $mod = 'any'; }
			$row_cats = isset( $cats[ $i ] ) && is_array( $cats[ $i ] ) ? array_map( 'intval', $cats[ $i ] ) : array();
			$compiled[] = array(
				'number'     => $num,
				'context'    => $ctx,
				'mode'       => $mod,
				'categories' => $row_cats,
			);
		}
		update_option( 'wcwa_routing_rules', $compiled );
		echo '<div class="updated notice"><p>' . esc_html__( 'Routing rules saved.', 'whatsapp-woocommerce' ) . '</p></div>';
	}

    $whatsapp_number       = get_option( 'wcwa_whatsapp_number', '' );
    $button_text_product   = get_option( 'wcwa_button_text_product', __( 'Order on WhatsApp', 'whatsapp-woocommerce' ) );
    $button_text_cart      = get_option( 'wcwa_button_text_cart', __( 'Proceed to WhatsApp Order', 'whatsapp-woocommerce' ) );
    $enable_product_button = get_option( 'wcwa_enable_product_button', '1' );
    $enable_cart_button    = get_option( 'wcwa_enable_cart_button', '1' );
    $product_button_position = get_option( 'wcwa_product_button_position', 'bottom_left' );
    $uninstall_action       = get_option( 'wcwa_uninstall_action', 'keep' );
	$template_product      = get_option( 'wcwa_template_product', '' );
	$template_cart         = get_option( 'wcwa_template_cart', '' );
	$show_only_instock     = get_option( 'wcwa_show_only_instock', '0' );
	$display_mode          = get_option( 'wcwa_display_mode', 'include' );
	$display_categories    = (array) get_option( 'wcwa_display_categories', array() );
	$store_hours_enable    = get_option( 'wcwa_store_hours_enable', '0' );
	$store_hours_open      = get_option( 'wcwa_store_hours_open', '' );
	$store_hours_close     = get_option( 'wcwa_store_hours_close', '' );
	$store_hours_days      = (array) get_option( 'wcwa_store_hours_days', array() );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcwa-settings&tab=general' ) ); ?>" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'whatsapp-woocommerce' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcwa-settings&tab=messaging' ) ); ?>" class="nav-tab <?php echo $active_tab === 'messaging' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Messaging', 'whatsapp-woocommerce' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcwa-settings&tab=display' ) ); ?>" class="nav-tab <?php echo $active_tab === 'display' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Display Rules', 'whatsapp-woocommerce' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcwa-settings&tab=logs' ) ); ?>" class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Logs', 'whatsapp-woocommerce' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcwa-settings&tab=routing' ) ); ?>" class="nav-tab <?php echo $active_tab === 'routing' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Routing', 'whatsapp-woocommerce' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcwa-settings&tab=analytics' ) ); ?>" class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Analytics', 'whatsapp-woocommerce' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcwa-settings&tab=license' ) ); ?>" class="nav-tab <?php echo $active_tab === 'license' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'License', 'whatsapp-woocommerce' ); ?></a>
		</h2>

		<?php if ( $active_tab === 'general' ) : ?>
            <style>
                .wcwa-card{background:#fff;border:1px solid #ccd0d4;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04);padding:16px;margin-top:12px}
                .wcwa-row{display:flex;gap:16px;flex-wrap:wrap}
                .wcwa-col{flex:1 1 320px}
                .wcwa-field label{display:block;margin-bottom:6px;font-weight:600}
                .wcwa-field input[type=text]{width:100%}
                .wcwa-switch{position:relative;display:inline-block;width:54px;height:28px;vertical-align:middle}
                .wcwa-switch input{opacity:0;width:0;height:0}
                .wcwa-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#e2e4e7;transition:.3s;border-radius:999px}
                .wcwa-slider:before{position:absolute;content:"";height:22px;width:22px;left:3px;bottom:3px;background:white;transition:.3s;border-radius:50%}
                .wcwa-switch input:checked + .wcwa-slider{background:#46b450}
                .wcwa-switch input:checked + .wcwa-slider:before{transform:translateX(26px)}
            </style>
            <form method="post">
                <?php wp_nonce_field( 'wcwa_settings_nonce' ); ?>
                <div class="wcwa-card">
                    <h3><?php esc_html_e( 'General Settings', 'whatsapp-woocommerce' ); ?></h3>
                    <div class="wcwa-row">
                        <div class="wcwa-col wcwa-field">
                            <label for="wcwa_whatsapp_number"><?php esc_html_e( 'WhatsApp Number', 'whatsapp-woocommerce' ); ?></label>
                            <input name="wcwa_whatsapp_number" type="text" id="wcwa_whatsapp_number" value="<?php echo esc_attr( $whatsapp_number ); ?>" class="regular-text" placeholder="e.g., 923001234567">
                            <p class="description"><?php esc_html_e( 'Include country code. No spaces or + required.', 'whatsapp-woocommerce' ); ?></p>
                        </div>
                        <div class="wcwa-col wcwa-field">
                            <label for="wcwa_button_text_product"><?php esc_html_e( 'Product Page Button Text', 'whatsapp-woocommerce' ); ?></label>
                            <input name="wcwa_button_text_product" type="text" id="wcwa_button_text_product" value="<?php echo esc_attr( $button_text_product ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Order on WhatsApp', 'whatsapp-woocommerce' ); ?>">
                        </div>
                        <div class="wcwa-col wcwa-field">
                            <label for="wcwa_button_text_cart"><?php esc_html_e( 'Cart Page Button Text', 'whatsapp-woocommerce' ); ?></label>
                            <input name="wcwa_button_text_cart" type="text" id="wcwa_button_text_cart" value="<?php echo esc_attr( $button_text_cart ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Proceed to WhatsApp Order', 'whatsapp-woocommerce' ); ?>">
                        </div>
                    </div>
                </div>
                <div class="wcwa-card">
                    <h3><?php esc_html_e( 'Button Visibility', 'whatsapp-woocommerce' ); ?></h3>
                    <div class="wcwa-row">
                        <div class="wcwa-col">
                            <label style="display:flex;align-items:center;gap:10px">
                                <span><?php esc_html_e( 'Enable on Product Page', 'whatsapp-woocommerce' ); ?></span>
                                <span class="wcwa-switch"><input type="checkbox" name="wcwa_enable_product_button" value="1" <?php checked( $enable_product_button, '1' ); ?>><span class="wcwa-slider"></span></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'Show the WhatsApp order button on single product pages.', 'whatsapp-woocommerce' ); ?></p>
                        </div>
                        <div class="wcwa-col">
                            <label style="display:flex;align-items:center;gap:10px">
                                <span><?php esc_html_e( 'Enable on Cart Page', 'whatsapp-woocommerce' ); ?></span>
                                <span class="wcwa-switch"><input type="checkbox" name="wcwa_enable_cart_button" value="1" <?php checked( $enable_cart_button, '1' ); ?>><span class="wcwa-slider"></span></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'Show the WhatsApp order button on cart page.', 'whatsapp-woocommerce' ); ?></p>
                        </div>
                    </div>
                </div>
                <div class="wcwa-card">
                    <h3><?php esc_html_e( 'Native WooCommerce Buttons', 'whatsapp-woocommerce' ); ?></h3>
                    <div class="wcwa-row">
                        <div class="wcwa-col">
                            <label style="display:flex;align-items:center;gap:10px">
                                <span><?php esc_html_e( 'Show "Add to cart" on Product Page', 'whatsapp-woocommerce' ); ?></span>
                                <span class="wcwa-switch"><input type="checkbox" name="wcwa_enable_add_to_cart_button" value="1" <?php checked( get_option( 'wcwa_enable_add_to_cart_button', '1' ), '1' ); ?>><span class="wcwa-slider"></span></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'Toggle the native WooCommerce add to cart button.', 'whatsapp-woocommerce' ); ?></p>
                        </div>
                        <div class="wcwa-col">
                            <label style="display:flex;align-items:center;gap:10px">
                                <span><?php esc_html_e( 'Show "Proceed to checkout" on Cart Page', 'whatsapp-woocommerce' ); ?></span>
                                <span class="wcwa-switch"><input type="checkbox" name="wcwa_enable_checkout_button" value="1" <?php checked( get_option( 'wcwa_enable_checkout_button', '1' ), '1' ); ?>><span class="wcwa-slider"></span></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'Toggle the native WooCommerce proceed to checkout button.', 'whatsapp-woocommerce' ); ?></p>
                        </div>
                    </div>
                </div>
                <div class="wcwa-card">
                    <h3><?php esc_html_e( 'Product Button Position', 'whatsapp-woocommerce' ); ?></h3>
                    <div class="wcwa-row">
                        <div class="wcwa-col">
                            <label class="wcwa-pill"><input type="radio" name="wcwa_product_button_position" value="top_left" <?php checked( $product_button_position, 'top_left' ); ?>> <?php esc_html_e( 'Top Left', 'whatsapp-woocommerce' ); ?></label>
                            <label class="wcwa-pill"><input type="radio" name="wcwa_product_button_position" value="top_center" <?php checked( $product_button_position, 'top_center' ); ?>> <?php esc_html_e( 'Top Center', 'whatsapp-woocommerce' ); ?></label>
                            <label class="wcwa-pill"><input type="radio" name="wcwa_product_button_position" value="bottom_left" <?php checked( $product_button_position, 'bottom_left' ); ?>> <?php esc_html_e( 'Bottom Left', 'whatsapp-woocommerce' ); ?></label>
                            <label class="wcwa-pill"><input type="radio" name="wcwa_product_button_position" value="bottom_center" <?php checked( $product_button_position, 'bottom_center' ); ?>> <?php esc_html_e( 'Bottom Center', 'whatsapp-woocommerce' ); ?></label>
                            <p class="description"><?php esc_html_e( 'Controls where the WhatsApp order button appears relative to the Add to cart area.', 'whatsapp-woocommerce' ); ?></p>
                        </div>
                    </div>
                </div>
                <div class="wcwa-card">
                    <h3><?php esc_html_e( 'Uninstall Behavior', 'whatsapp-woocommerce' ); ?></h3>
                    <p>
                        <label class="wcwa-pill"><input type="radio" name="wcwa_uninstall_action" value="keep" <?php checked( $uninstall_action, 'keep' ); ?>> <?php esc_html_e( 'Keep data when deleting plugin', 'whatsapp-woocommerce' ); ?></label>
                        <label class="wcwa-pill"><input type="radio" name="wcwa_uninstall_action" value="delete" <?php checked( $uninstall_action, 'delete' ); ?>> <?php esc_html_e( 'Delete all data when deleting plugin', 'whatsapp-woocommerce' ); ?></label>
                    </p>
                    <p class="description"><?php esc_html_e( 'This setting controls whether received orders and settings are removed when the plugin is deleted from the site. Deactivation never deletes data.', 'whatsapp-woocommerce' ); ?></p>
                </div>
                <?php submit_button( __( 'Save Settings', 'whatsapp-woocommerce' ), 'primary', 'wcwa_save_general' ); ?>
            </form>
		<?php elseif ( $active_tab === 'messaging' ) : ?>
			<style>
				.wcwa-card{background:#fff;border:1px solid #ccd0d4;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04);padding:16px;margin-top:12px}
				.wcwa-field textarea{width:100%}
			</style>
			<form method="post">
				<?php wp_nonce_field( 'wcwa_settings_nonce' ); ?>
				<div class="wcwa-card wcwa-field">
					<h3><?php esc_html_e( 'Product Message Template', 'whatsapp-woocommerce' ); ?></h3>
					<textarea name="wcwa_template_product" id="wcwa_template_product" class="large-text" rows="6" placeholder="<?php esc_attr_e( 'Customize the product order message…', 'whatsapp-woocommerce' ); ?>"><?php echo esc_textarea( $template_product ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Placeholders: {customer_name}, {customer_phone}, {address}, {notes}, {product_name}, {product_price}, {product_link}, {product_id}, {order_code}', 'whatsapp-woocommerce' ); ?></p>
					<p><button type="button" class="button" id="wcwa_fill_default_product"><?php esc_html_e( 'Use Default Product Template', 'whatsapp-woocommerce' ); ?></button></p>
				</div>
				<div class="wcwa-card wcwa-field">
					<h3><?php esc_html_e( 'Cart Message Template', 'whatsapp-woocommerce' ); ?></h3>
					<textarea name="wcwa_template_cart" id="wcwa_template_cart" class="large-text" rows="6" placeholder="<?php esc_attr_e( 'Customize the cart order message…', 'whatsapp-woocommerce' ); ?>"><?php echo esc_textarea( $template_cart ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Placeholders: {customer_name}, {customer_phone}, {address}, {notes}, {cart_lines}, {order_code}', 'whatsapp-woocommerce' ); ?></p>
					<p><button type="button" class="button" id="wcwa_fill_default_cart"><?php esc_html_e( 'Use Default Cart Template', 'whatsapp-woocommerce' ); ?></button></p>
				</div>
				<?php submit_button( __( 'Save Templates', 'whatsapp-woocommerce' ), 'primary', 'wcwa_save_messaging' ); ?>
			</form>
			<script>
			(function(){
				function setVal(id, val){ var el = document.getElementById(id); if(el){ el.value = val; el.focus(); } }
				var prodBtn = document.getElementById('wcwa_fill_default_product');
				var cartBtn = document.getElementById('wcwa_fill_default_cart');
				if (prodBtn){
					prodBtn.addEventListener('click', function(){
						var t = "Hello, I'd like to place an order!\n\n" +
							"*--- Customer Details ---*\n" +
							"- Name: {customer_name}\n" +
							"- Phone: {customer_phone}\n" +
							"- Address: {address}\n" +
							"- Notes: {notes}\n" +
							"- Order Code: {order_code}\n\n" +
							"*--- Order Details ---*\n" +
							"- Product: {product_name}\n" +
							"- Price: {product_price}\n" +
							"- ID: {product_id}\n" +
							"- Link: {product_link}";
						setVal('wcwa_template_product', t);
					});
				}
				if (cartBtn){
					cartBtn.addEventListener('click', function(){
						var t = "Hello, I'd like to place an order!\n\n" +
							"*--- Customer Details ---*\n" +
							"- Name: {customer_name}\n" +
							"- Phone: {customer_phone}\n" +
							"- Address: {address}\n" +
							"- Notes: {notes}\n" +
							"- Order Code: {order_code}\n\n" +
							"*--- Order Details ---*\n" +
							"{cart_lines}";
						setVal('wcwa_template_cart', t);
					});
				}
			})();
			</script>
		<?php elseif ( $active_tab === 'display' ) : ?>
			<style>
				.wcwa-card{background:#fff;border:1px solid #ccd0d4;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04);padding:16px;margin-top:12px}
				.wcwa-row{display:flex;gap:16px;flex-wrap:wrap}
				.wcwa-col{flex:1 1 320px}
				.wcwa-pill{display:inline-block;background:#f6f7f7;border:1px solid #e2e4e7;border-radius:999px;padding:6px 10px;margin:2px}
				.wcwa-catlist{max-height:180px;overflow:auto;border:1px solid #e2e4e7;padding:8px;border-radius:4px;background:#f8f9fa}
			</style>
			<form method="post">
				<?php wp_nonce_field( 'wcwa_settings_nonce' ); ?>
				<div class="wcwa-card">
					<h3><?php esc_html_e( 'Visibility Rules', 'whatsapp-woocommerce' ); ?></h3>
					<p><label class="wcwa-pill"><input type="checkbox" name="wcwa_show_only_instock" value="1" <?php checked( $show_only_instock, '1' ); ?>> <?php esc_html_e( 'Only show when in stock', 'whatsapp-woocommerce' ); ?></label></p>
					<p>
						<label class="wcwa-pill"><input type="radio" name="wcwa_display_mode" value="include" <?php checked( $display_mode, 'include' ); ?>> <?php esc_html_e( 'Show only in selected categories', 'whatsapp-woocommerce' ); ?></label>
						<label class="wcwa-pill"><input type="radio" name="wcwa_display_mode" value="exclude" <?php checked( $display_mode, 'exclude' ); ?>> <?php esc_html_e( 'Hide in selected categories', 'whatsapp-woocommerce' ); ?></label>
					</p>
					<div class="wcwa-catlist">
						<?php
						$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
						foreach ( $cats as $cat ) {
							$checked = in_array( (int) $cat->term_id, array_map( 'intval', $display_categories ), true ) ? 'checked' : '';
							echo '<label style="display:block;margin:2px 0"><input type="checkbox" name="wcwa_display_categories[]" value="' . esc_attr( $cat->term_id ) . '" ' . $checked . '> ' . esc_html( $cat->name ) . '</label>';
						}
						?>
					</div>
				</div>
				<div class="wcwa-card">
					<h3><?php esc_html_e( 'Store Hours', 'whatsapp-woocommerce' ); ?></h3>
					<p><label class="wcwa-pill"><input type="checkbox" name="wcwa_store_hours_enable" value="1" <?php checked( $store_hours_enable, '1' ); ?>> <?php esc_html_e( 'Enable store hours', 'whatsapp-woocommerce' ); ?></label></p>
					<div class="wcwa-row">
						<div class="wcwa-col">
							<label><?php esc_html_e( 'Open', 'whatsapp-woocommerce' ); ?></label>
							<input type="text" name="wcwa_store_hours_open" value="<?php echo esc_attr( $store_hours_open ); ?>" placeholder="07:00" style="width:120px">
						</div>
						<div class="wcwa-col">
							<label><?php esc_html_e( 'Close', 'whatsapp-woocommerce' ); ?></label>
							<input type="text" name="wcwa_store_hours_close" value="<?php echo esc_attr( $store_hours_close ); ?>" placeholder="22:00" style="width:120px">
						</div>
					</div>
					<p>
						<?php
						$days_list = array( 'mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun' );
						foreach ( $days_list as $k => $label ) {
							$checked = in_array( $k, $store_hours_days, true ) ? 'checked' : '';
							echo '<label class="wcwa-pill"><input type="checkbox" name="wcwa_store_days[]" value="' . esc_attr( $k ) . '" ' . $checked . '> ' . esc_html( $label ) . '</label>';
						}
						?>
					</p>
					<?php
					$tz   = wp_timezone();
					$now  = new DateTimeImmutable( 'now', $tz );
					$off  = (int) $now->getOffset();
					$sgn  = $off >= 0 ? '+' : '-';
					$abs  = abs( $off );
					$hrs  = (int) floor( $abs / 3600 );
					$min  = (int) ( ( $abs % 3600 ) / 60 );
					$utc  = sprintf( 'UTC%s%02d:%02d', $sgn, $hrs, $min );
					$name = wp_timezone_string();
					?>
					<p class="description">
						<?php echo esc_html__( 'Times use your WordPress site timezone (Settings → General → Timezone).', 'whatsapp-woocommerce' ); ?>
						<br><?php printf( esc_html__( 'Current timezone: %1$s (%2$s).', 'whatsapp-woocommerce' ), esc_html( $utc ), esc_html( $name ) ); ?>
						<br><?php echo esc_html__( 'Enter hours in 24-hour format HH:MM (00:00–23:59). Use 23:59 instead of 24:00.', 'whatsapp-woocommerce' ); ?>
						<br><?php echo esc_html__( 'Select the operating days; if no day is selected, the button is hidden.', 'whatsapp-woocommerce' ); ?>
						<br><?php echo esc_html__( 'Overnight hours are supported (e.g., 22:00–02:00). Select the day the shift starts; the period after midnight is treated as part of that day.', 'whatsapp-woocommerce' ); ?>
					</p>
				</div>
				<?php submit_button( __( 'Save Display Rules', 'whatsapp-woocommerce' ), 'primary', 'wcwa_save_display' ); ?>
			</form>
        <?php elseif ( $active_tab === 'routing' ) : ?>
            <style>
                .wcwa-card{background:#fff;border:1px solid #ccd0d4;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04);padding:16px;margin-top:12px}
                .wcwa-row{display:flex;gap:16px;flex-wrap:wrap;margin-top:8px}
                .wcwa-col{flex:1 1 320px}
                .wcwa-pill{display:inline-block;background:#f6f7f7;border:1px solid #e2e4e7;border-radius:999px;padding:6px 10px;margin:2px}
                .wcwa-catlist{max-height:180px;overflow:auto;border:1px solid #e2e4e7;padding:8px;border-radius:4px;background:#f8f9fa}
            </style>
            <form method="post">
                <?php wp_nonce_field( 'wcwa_settings_nonce' ); ?>
                <div class="wcwa-card">
                    <h3><?php esc_html_e( 'Routing Rules (Multi-number)', 'whatsapp-woocommerce' ); ?></h3>
                    <p class="description"><?php esc_html_e( 'Route orders to different WhatsApp numbers based on context and categories.', 'whatsapp-woocommerce' ); ?></p>
                    <?php
                    $rules = get_option( 'wcwa_routing_rules', array() );
                    if ( ! is_array( $rules ) ) { $rules = array(); }
                    $max_rows = max( 1, count( $rules ) );
                    $max_rows = min( 5, $max_rows + 1 );
                    $cats_all = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
                    for ( $i = 0; $i < $max_rows; $i++ ) {
                        $rule = isset( $rules[ $i ] ) && is_array( $rules[ $i ] ) ? $rules[ $i ] : array();
                        $number   = isset( $rule['number'] ) ? $rule['number'] : '';
                        $context  = isset( $rule['context'] ) ? $rule['context'] : 'any';
                        $mode     = isset( $rule['mode'] ) ? $rule['mode'] : 'any';
                        $cat_ids  = isset( $rule['categories'] ) && is_array( $rule['categories'] ) ? array_map( 'intval', $rule['categories'] ) : array();
                        ?>
                        <div class="wcwa-row" style="border-top:1px dashed #e2e4e7;padding-top:12px;margin-top:12px">
                            <div class="wcwa-col">
                                <label class="wcwa-pill"><?php esc_html_e( 'Destination Number', 'whatsapp-woocommerce' ); ?></label>
                                <input type="text" name="routing[number][]" value="<?php echo esc_attr( $number ); ?>" class="regular-text" placeholder="e.g., 923001234567">
                            </div>
                            <div class="wcwa-col">
                                <label class="wcwa-pill"><?php esc_html_e( 'Context', 'whatsapp-woocommerce' ); ?></label>
                                <select name="routing[context][]">
                                    <option value="any" <?php selected( $context, 'any' ); ?>><?php esc_html_e( 'Any', 'whatsapp-woocommerce' ); ?></option>
                                    <option value="product" <?php selected( $context, 'product' ); ?>><?php esc_html_e( 'Product', 'whatsapp-woocommerce' ); ?></option>
                                    <option value="cart" <?php selected( $context, 'cart' ); ?>><?php esc_html_e( 'Cart', 'whatsapp-woocommerce' ); ?></option>
                                </select>
                            </div>
                            <div class="wcwa-col">
                                <label class="wcwa-pill"><?php esc_html_e( 'Category Match', 'whatsapp-woocommerce' ); ?></label>
                                <select name="routing[mode][]">
                                    <option value="any" <?php selected( $mode, 'any' ); ?>><?php esc_html_e( 'Any', 'whatsapp-woocommerce' ); ?></option>
                                    <option value="include" <?php selected( $mode, 'include' ); ?>><?php esc_html_e( 'Include (intersect)', 'whatsapp-woocommerce' ); ?></option>
                                    <option value="exclude" <?php selected( $mode, 'exclude' ); ?>><?php esc_html_e( 'Exclude (no intersect)', 'whatsapp-woocommerce' ); ?></option>
                                </select>
                            </div>
                            <div class="wcwa-col">
                                <div class="wcwa-catlist">
                                    <?php foreach ( $cats_all as $cat ) { $checked = in_array( (int) $cat->term_id, $cat_ids, true ) ? 'checked' : ''; ?>
                                        <label style="display:block;margin:2px 0"><input type="checkbox" name="routing[categories][<?php echo esc_attr( $i ); ?>][]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php echo $checked; ?>> <?php echo esc_html( $cat->name ); ?></label>
                                    <?php } ?>
                                </div>
                                <p class="description" style="margin-top:6px"><?php esc_html_e( 'When Category Match = Any, categories are ignored.', 'whatsapp-woocommerce' ); ?></p>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php submit_button( __( 'Save Routing Rules', 'whatsapp-woocommerce' ), 'primary', 'wcwa_save_routing' ); ?>
            </form>
        <?php elseif ( $active_tab === 'license' ) : ?>
            <p><?php esc_html_e( 'Pro features (advanced rules, routing, Cloud API, analytics) appear here when the Pro add-on is active.', 'whatsapp-woocommerce' ); ?></p>
		<?php elseif ( $active_tab === 'analytics' ) : ?>
			<?php
			$days  = 30;
			$start = gmdate( 'Y-m-d', time() - $days * DAY_IN_SECONDS );
			$q     = new WP_Query( array(
				'post_type'      => 'wcwa_order_log',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'date_query'     => array( array( 'after' => $start, 'inclusive' => true ) ),
			) );
			$daily = array();
			$type_totals = array( 'product' => 0, 'cart' => 0 );
			$top_products = array();
			for ( $i = 0; $i < $days; $i++ ) {
				$k = gmdate( 'Y-m-d', time() - ( $days - $i - 1 ) * DAY_IN_SECONDS );
				$daily[ $k ] = array( 'total' => 0, 'product' => 0, 'cart' => 0 );
			}
			foreach ( $q->posts as $p ) {
				$d = get_post_time( 'Y-m-d', true, $p );
				$t = get_post_meta( $p->ID, '_order_type', true );
				if ( ! isset( $daily[ $d ] ) ) { $daily[ $d ] = array( 'total' => 0, 'product' => 0, 'cart' => 0 ); }
				$daily[ $d ]['total']++;
				if ( $t === 'cart' ) { $daily[ $d ]['cart']++; $type_totals['cart']++; } else { $daily[ $d ]['product']++; $type_totals['product']++; }
				if ( $t !== 'cart' ) {
					$body = $p->post_content;
					if ( preg_match( '/Product:\s*(.+?)\n/i', $body, $m ) ) {
						$name = trim( $m[1] );
						$top_products[ $name ] = isset( $top_products[ $name ] ) ? $top_products[ $name ] + 1 : 1;
					}
				}
			}
			$labels = array_keys( $daily );
			$values = array_map( function( $v ){ return (int) $v['total']; }, array_values( $daily ) );
			$prod   = array_map( function( $v ){ return (int) $v['product']; }, array_values( $daily ) );
			$cart   = array_map( function( $v ){ return (int) $v['cart']; }, array_values( $daily ) );
			arsort( $top_products );
			$top_rows = array_slice( $top_products, 0, 10, true );
			$total   = $type_totals['product'] + $type_totals['cart'];
			?>
			<style>
				.wcwa-grid{display:grid;grid-template-columns:1fr;gap:16px;margin-top:16px}
				@media (max-width:960px){.wcwa-grid{grid-template-columns:1fr}}
				.wcwa-card{background:#fff;border:1px solid #ccd0d4;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04);padding:16px}
				.wcwa-card h3{margin:0 0 12px;display:flex;align-items:center;gap:8px}
				.wcwa-metrics{display:flex;gap:16px;flex-wrap:wrap;margin-top:8px}
				.wcwa-metric{background:#f6f7f7;border:1px solid #e2e4e7;border-radius:4px;padding:10px 12px;min-width:120px}
				.wcwa-table{width:100%;border-collapse:collapse;margin-top:12px}
				.wcwa-table th,.wcwa-table td{border-bottom:1px solid #e2e4e7;padding:8px;text-align:left}
			</style>
			<div class="wcwa-grid">
				<div class="wcwa-card">
					<h3><span class="dashicons dashicons-chart-bar"></span><?php esc_html_e( 'Orders (Last 30 Days)', 'whatsapp-woocommerce' ); ?></h3>
					<canvas id="wcwaChart" width="900" height="280"></canvas>
					<script>
					(function(){
						var labels = <?php echo wp_json_encode( $labels ); ?>;
						var total = <?php echo wp_json_encode( $values ); ?>;
						var prod  = <?php echo wp_json_encode( $prod ); ?>;
						var cart  = <?php echo wp_json_encode( $cart ); ?>;
						var c = document.getElementById('wcwaChart');
						var ctx = c.getContext('2d');
						var W = c.width, H = c.height, P = 30;
						var max = Math.max.apply(null, total.concat(prod).concat(cart));
						max = Math.max(1, max);
						ctx.clearRect(0,0,W,H);
						ctx.font = '12px sans-serif';
						ctx.fillStyle = '#555';
						ctx.fillText('Count', 6, 12);
						var plotW = W - P*2, plotH = H - P*2;
						var n = total.length;
						var bw = Math.max(2, Math.floor(plotW / (n*3 + (n+1))));
						var x = P;
						for (var i=0;i<n;i++){
							var t = total[i], pr = prod[i], ca = cart[i];
							var h1 = Math.round((t/max)*plotH), h2 = Math.round((pr/max)*plotH), h3 = Math.round((ca/max)*plotH);
							ctx.fillStyle = '#2271b1'; ctx.fillRect(x, H-P-h1, bw, h1);
							ctx.fillStyle = '#46b450'; ctx.fillRect(x+bw+2, H-P-h2, bw, h2);
							ctx.fillStyle = '#d63638'; ctx.fillRect(x+2*bw+4, H-P-h3, bw, h3);

							if (i % 5 === 0){ ctx.fillStyle = '#555'; ctx.fillText(labels[i].slice(5), x, H-6); }
							x += 3*bw + 6;
						}
						ctx.fillStyle = '#2271b1'; ctx.fillRect(W-180, 10, 10, 10); ctx.fillStyle='#555'; ctx.fillText('Total', W-165, 20);
						ctx.fillStyle = '#46b450'; ctx.fillRect(W-120, 10, 10, 10); ctx.fillStyle='#555'; ctx.fillText('Product', W-105, 20);
						ctx.fillStyle = '#d63638'; ctx.fillRect(W-60, 10, 10, 10); ctx.fillStyle='#555'; ctx.fillText('Cart', W-45, 20);
					})();
					</script>
				</div>
				<div class="wcwa-card">
					<h3><span class="dashicons dashicons-chart-pie"></span><?php esc_html_e( 'Summary', 'whatsapp-woocommerce' ); ?></h3>
					<div class="wcwa-metrics">
						<div class="wcwa-metric"><strong><?php echo esc_html( $total ); ?></strong><br><?php esc_html_e( 'Total', 'whatsapp-woocommerce' ); ?></div>
						<div class="wcwa-metric"><strong><?php echo esc_html( $type_totals['product'] ); ?></strong><br><?php esc_html_e( 'Product', 'whatsapp-woocommerce' ); ?></div>
						<div class="wcwa-metric"><strong><?php echo esc_html( $type_totals['cart'] ); ?></strong><br><?php esc_html_e( 'Cart', 'whatsapp-woocommerce' ); ?></div>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
						<?php wp_nonce_field( 'wcwa_analytics_actions' ); ?>
						<input type="hidden" name="action" value="wcwa_export_analytics">
						<label><?php esc_html_e( 'Start date', 'whatsapp-woocommerce' ); ?> <input type="date" name="start_date"></label>
						<label style="margin-left:8px"><?php esc_html_e( 'End date', 'whatsapp-woocommerce' ); ?> <input type="date" name="end_date"></label>
						<?php submit_button( __( 'Export Daily Summary CSV', 'whatsapp-woocommerce' ), 'secondary', '', false ); ?>
					</form>
					<h3 style="margin-top:16px"><span class="dashicons dashicons-star-filled"></span><?php esc_html_e( 'Top Products', 'whatsapp-woocommerce' ); ?></h3>
					<table class="wcwa-table">
						<thead><tr><th><?php esc_html_e( 'Product', 'whatsapp-woocommerce' ); ?></th><th><?php esc_html_e( 'Orders', 'whatsapp-woocommerce' ); ?></th></tr></thead>
						<tbody>
						<?php if ( ! empty( $top_rows ) ) : foreach ( $top_rows as $name => $cnt ) : ?>
							<tr><td><?php echo esc_html( $name ); ?></td><td><?php echo esc_html( $cnt ); ?></td></tr>
						<?php endforeach; else : ?>
							<tr><td colspan="2"><?php esc_html_e( 'No product orders yet.', 'whatsapp-woocommerce' ); ?></td></tr>
						<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php elseif ( $active_tab === 'logs' ) : ?>
			<?php
			$counts      = wp_count_posts( 'wcwa_order_log' );
			$total_logs  = isset( $counts->publish ) ? (int) $counts->publish : 0;
			$last_q      = new WP_Query( array(
				'post_type'      => 'wcwa_order_log',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'date_query'     => array( array( 'after' => gmdate( 'Y-m-d', time() - 7 * DAY_IN_SECONDS ), 'inclusive' => true ) ),
			) );
			$last7_logs  = is_a( $last_q, 'WP_Query' ) ? count( (array) $last_q->posts ) : 0;
			$latest_q    = new WP_Query( array(
				'post_type'      => 'wcwa_order_log',
				'post_status'    => 'publish',
				'posts_per_page' => 10,
				'orderby'        => 'date',
				'order'          => 'DESC',
			) );
			?>
			<style>
				.wcwa-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
				@media (max-width:960px){.wcwa-grid{grid-template-columns:1fr}}
				.wcwa-card{background:#fff;border:1px solid #ccd0d4;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04);padding:16px}
				.wcwa-card h3{margin:0 0 12px;display:flex;align-items:center;gap:8px}
				.wcwa-metrics{display:flex;gap:16px;margin-top:8px}
				.wcwa-metric{background:#f6f7f7;border:1px solid #e2e4e7;border-radius:4px;padding:10px 12px}
				.wcwa-table{width:100%;border-collapse:collapse;margin-top:16px}
				.wcwa-table th,.wcwa-table td{border-bottom:1px solid #e2e4e7;padding:8px;text-align:left}
			</style>
			<div class="wcwa-grid">
				<div class="wcwa-card">
					<h3><span class="dashicons dashicons-download"></span><?php esc_html_e( 'Export Logs', 'whatsapp-woocommerce' ); ?></h3>
					<div class="wcwa-metrics">
						<div class="wcwa-metric"><strong><?php echo esc_html( $total_logs ); ?></strong><br><?php esc_html_e( 'Total Logs', 'whatsapp-woocommerce' ); ?></div>
						<div class="wcwa-metric"><strong><?php echo esc_html( $last7_logs ); ?></strong><br><?php esc_html_e( 'Last 7 Days', 'whatsapp-woocommerce' ); ?></div>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
						<?php wp_nonce_field( 'wcwa_logs_actions' ); ?>
						<input type="hidden" name="action" value="wcwa_export_logs">
						<label><?php esc_html_e( 'Start date', 'whatsapp-woocommerce' ); ?> <input type="date" name="start_date"></label>
						<label style="margin-left:8px"><?php esc_html_e( 'End date', 'whatsapp-woocommerce' ); ?> <input type="date" name="end_date"></label>
						<p class="description" style="margin-top:6px"><?php esc_html_e( 'Leave both blank to export all logs.', 'whatsapp-woocommerce' ); ?></p>
						<?php submit_button( __( 'Export CSV', 'whatsapp-woocommerce' ), 'primary', '', false ); ?>
					</form>
				</div>
				<div class="wcwa-card">
					<h3><span class="dashicons dashicons-trash"></span><?php esc_html_e( 'Retention & Purge', 'whatsapp-woocommerce' ); ?></h3>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'wcwa_logs_actions' ); ?>
						<input type="hidden" name="action" value="wcwa_purge_logs">
						<label><?php esc_html_e( 'Retention (days)', 'whatsapp-woocommerce' ); ?> <input type="number" name="retention_days" min="0" step="1" value="<?php echo esc_attr( get_option( 'wcwa_log_retention_days', '0' ) ); ?>" style="width:120px"></label>
						<p class="description" style="margin-top:6px"><?php esc_html_e( '0 keeps all logs. Purge deletes entries older than the configured days.', 'whatsapp-woocommerce' ); ?></p>
						<?php submit_button( __( 'Save & Purge Now', 'whatsapp-woocommerce' ), 'delete', '', false ); ?>
					</form>
				</div>
			</div>
			<div class="wcwa-card" style="margin-top:16px">
				<h3><span class="dashicons dashicons-list-view"></span><?php esc_html_e( 'Latest Logs', 'whatsapp-woocommerce' ); ?></h3>
				<table class="wcwa-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'whatsapp-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'whatsapp-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Phone', 'whatsapp-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Code', 'whatsapp-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Type', 'whatsapp-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Action', 'whatsapp-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( $latest_q->have_posts() ) : foreach ( $latest_q->posts as $p ) : ?>
							<tr>
								<td><?php echo esc_html( get_post_time( 'Y-m-d H:i', true, $p ) ); ?></td>
								<td><?php echo esc_html( get_post_meta( $p->ID, '_customer_name', true ) ); ?></td>
								<td><?php echo esc_html( get_post_meta( $p->ID, '_customer_phone', true ) ); ?></td>
								<td><?php echo esc_html( get_post_meta( $p->ID, '_order_code', true ) ); ?></td>
								<td><?php echo stripos( get_the_title( $p->ID ), 'Cart Order' ) !== false ? esc_html__( 'Cart', 'whatsapp-woocommerce' ) : esc_html__( 'Product', 'whatsapp-woocommerce' ); ?></td>
								<td><a class="button" href="<?php echo esc_url( admin_url( 'post.php?post=' . $p->ID . '&action=edit' ) ); ?>"><?php esc_html_e( 'View', 'whatsapp-woocommerce' ); ?></a></td>
							</tr>
						<?php endforeach; else : ?>
							<tr><td colspan="6"><?php esc_html_e( 'No logs yet.', 'whatsapp-woocommerce' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
			$days  = 30;
			$start = gmdate( 'Y-m-d', time() - $days * DAY_IN_SECONDS );
			$q     = new WP_Query( array(
				'post_type'      => 'wcwa_order_log',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'date_query'     => array( array( 'after' => $start, 'inclusive' => true ) ),
			) );
			$daily = array();
			$type_totals = array( 'product' => 0, 'cart' => 0 );
			$top_products = array();
			for ( $i = 0; $i < $days; $i++ ) {
				$k = gmdate( 'Y-m-d', time() - ( $days - $i - 1 ) * DAY_IN_SECONDS );
				$daily[ $k ] = array( 'total' => 0, 'product' => 0, 'cart' => 0 );
			}
			foreach ( $q->posts as $p ) {
				$d = get_post_time( 'Y-m-d', true, $p );
				$t = get_post_meta( $p->ID, '_order_type', true );
				if ( ! isset( $daily[ $d ] ) ) { $daily[ $d ] = array( 'total' => 0, 'product' => 0, 'cart' => 0 ); }
				$daily[ $d ]['total']++;
				if ( $t === 'cart' ) { $daily[ $d ]['cart']++; $type_totals['cart']++; } else { $daily[ $d ]['product']++; $type_totals['product']++; }
				if ( $t !== 'cart' ) {
					$body = $p->post_content;
					if ( preg_match( '/Product:\\s*(.+?)\\n/i', $body, $m ) ) {
						$name = trim( $m[1] );
						$top_products[ $name ] = isset( $top_products[ $name ] ) ? $top_products[ $name ] + 1 : 1;
					}
				}
			}
			$labels = array_keys( $daily );
			$values = array_map( function( $v ){ return (int) $v['total']; }, array_values( $daily ) );
			$prod   = array_map( function( $v ){ return (int) $v['product']; }, array_values( $daily ) );
			$cart   = array_map( function( $v ){ return (int) $v['cart']; }, array_values( $daily ) );
			arsort( $top_products );
			$top_rows = array_slice( $top_products, 0, 10, true );
			$total   = $type_totals['product'] + $type_totals['cart'];
			?>
		<?php endif; ?>
		</div>
		<?php
	}

/**
 * Admin-post: Export logs to CSV.
 */
function wcwa_admin_export_logs() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'Access denied', 'whatsapp-woocommerce' ) );
    }
    check_admin_referer( 'wcwa_logs_actions' );
    $start = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
    $end   = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
    $args  = array(
        'post_type'      => 'wcwa_order_log',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    if ( $start || $end ) {
        $args['date_query'] = array( array( 'inclusive' => true ) );
        if ( $start ) {
            $args['date_query'][0]['after'] = $start;
        }
        if ( $end ) {
            $args['date_query'][0]['before'] = $end;
        }
    }
    $q = new WP_Query( $args );
    $filename = 'whatsapp-orders-' . gmdate( 'Ymd-His' ) . '.csv';
    header( 'Content-Type: text/csv; charset=UTF-8' );
    header( 'Content-Disposition: attachment; filename=' . $filename );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'ID', 'Date', 'Type', 'Customer Name', 'Customer Phone', 'Order Code', 'Title', 'Details' ) );
    foreach ( $q->posts as $p ) {
        $id    = $p->ID;
        $date  = get_post_time( 'Y-m-d H:i:s', true, $p );
        $type  = get_post_meta( $id, '_order_type', true );
        $name  = get_post_meta( $id, '_customer_name', true );
        $phone = get_post_meta( $id, '_customer_phone', true );
        $code  = get_post_meta( $id, '_order_code', true );
        fputcsv( $out, array( $id, $date, $type, $name, $phone, $code, $p->post_title, $p->post_content ) );
    }
    fclose( $out );
    exit;
}
add_action( 'admin_post_wcwa_export_logs', 'wcwa_admin_export_logs' );

/**
 * Admin-post: Save retention days and purge old logs.
 */
function wcwa_admin_purge_logs() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'Access denied', 'whatsapp-woocommerce' ) );
    }
    check_admin_referer( 'wcwa_logs_actions' );
    $days = isset( $_POST['retention_days'] ) ? absint( $_POST['retention_days'] ) : 0;
    update_option( 'wcwa_log_retention_days', (string) $days );
    if ( $days > 0 ) {
        $cut = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
        $q = new WP_Query( array(
            'post_type'      => 'wcwa_order_log',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'date_query'     => array( array( 'before' => $cut, 'inclusive' => true ) ),
            'fields'         => 'ids',
        ) );
        foreach ( $q->posts as $id ) {
            wp_delete_post( $id, true );
        }
    }
    wp_safe_redirect( admin_url( 'admin.php?page=wcwa-settings&tab=logs&wcwa=purged' ) );
    exit;
}
add_action( 'admin_post_wcwa_purge_logs', 'wcwa_admin_purge_logs' );

/**
 * Enhance logs list table columns.
 */
function wcwa_logs_columns( $columns ) {
    $columns['customer_name']  = __( 'Customer Name', 'whatsapp-woocommerce' );
    $columns['customer_phone'] = __( 'Customer Phone', 'whatsapp-woocommerce' );
    $columns['order_code']     = __( 'Order Code', 'whatsapp-woocommerce' );
    $columns['type']           = __( 'Type', 'whatsapp-woocommerce' );
    return $columns;
}
add_filter( 'manage_wcwa_order_log_posts_columns', 'wcwa_logs_columns' );

function wcwa_logs_column_content( $column, $post_id ) {
    if ( 'customer_name' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_customer_name', true ) );
    } elseif ( 'customer_phone' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_customer_phone', true ) );
    } elseif ( 'order_code' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_order_code', true ) );
    } elseif ( 'type' === $column ) {
        $t = get_post_meta( $post_id, '_order_type', true );
        echo esc_html( $t === 'cart' ? __( 'Cart', 'whatsapp-woocommerce' ) : __( 'Product', 'whatsapp-woocommerce' ) );
    }
}
add_action( 'manage_wcwa_order_log_posts_custom_column', 'wcwa_logs_column_content', 10, 2 );

function wcwa_logs_sortable( $columns ) {
    $columns['customer_name']  = 'customer_name';
    $columns['customer_phone'] = 'customer_phone';
    $columns['order_code']     = 'order_code';
    return $columns;
}
add_filter( 'manage_edit-wcwa_order_log_sortable_columns', 'wcwa_logs_sortable' );

function wcwa_logs_pre_get_posts( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) return;
    if ( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] === 'wcwa_order_log' ) {
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : '';
        if ( $orderby === 'customer_name' ) {
            $query->set( 'meta_key', '_customer_name' );
            $query->set( 'orderby', 'meta_value' );
        } elseif ( $orderby === 'customer_phone' ) {
            $query->set( 'meta_key', '_customer_phone' );
            $query->set( 'orderby', 'meta_value' );
        } elseif ( $orderby === 'order_code' ) {
            $query->set( 'meta_key', '_order_code' );
            $query->set( 'orderby', 'meta_value' );
        }
        $meta_query = array();
        if ( isset( $_GET['wcwa_type'] ) ) {
            $type = sanitize_key( $_GET['wcwa_type'] );
            if ( in_array( $type, array( 'product', 'cart' ), true ) ) {
                $meta_query[] = array( 'key' => '_order_type', 'value' => $type );
            }
        }
        if ( isset( $_GET['wcwa_phone'] ) && $_GET['wcwa_phone'] !== '' ) {
            $meta_query[] = array( 'key' => '_customer_phone', 'value' => sanitize_text_field( $_GET['wcwa_phone'] ), 'compare' => 'LIKE' );
        }
        if ( isset( $_GET['wcwa_code'] ) && $_GET['wcwa_code'] !== '' ) {
            $meta_query[] = array( 'key' => '_order_code', 'value' => sanitize_text_field( $_GET['wcwa_code'] ), 'compare' => 'LIKE' );
        }
        if ( ! empty( $meta_query ) ) {
            $query->set( 'meta_query', $meta_query );
        }
    }
}
add_action( 'pre_get_posts', 'wcwa_logs_pre_get_posts' );

function wcwa_logs_filters_html() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'wcwa_order_log' ) return;
    $current_type  = isset( $_GET['wcwa_type'] ) ? sanitize_key( $_GET['wcwa_type'] ) : '';
    $current_phone = isset( $_GET['wcwa_phone'] ) ? sanitize_text_field( $_GET['wcwa_phone'] ) : '';
    echo '<select name="wcwa_type" class="postform">';
    echo '<option value="">' . esc_html__( 'All Types', 'whatsapp-woocommerce' ) . '</option>';
    echo '<option value="product"' . selected( $current_type, 'product', false ) . '>' . esc_html__( 'Product', 'whatsapp-woocommerce' ) . '</option>';
    echo '<option value="cart"' . selected( $current_type, 'cart', false ) . '>' . esc_html__( 'Cart', 'whatsapp-woocommerce' ) . '</option>';
    echo '</select>';
    echo '<input type="search" name="wcwa_phone" value="' . esc_attr( $current_phone ) . '" placeholder="' . esc_attr__( 'Phone contains…', 'whatsapp-woocommerce' ) . '" />';
    $current_code = isset( $_GET['wcwa_code'] ) ? sanitize_text_field( $_GET['wcwa_code'] ) : '';
    echo '<input type="search" name="wcwa_code" value="' . esc_attr( $current_code ) . '" placeholder="' . esc_attr__( 'Code contains…', 'whatsapp-woocommerce' ) . '" />';
}
add_action( 'restrict_manage_posts', 'wcwa_logs_filters_html' );
function wcwa_admin_export_analytics() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'Access denied', 'whatsapp-woocommerce' ) );
    }
    check_admin_referer( 'wcwa_analytics_actions' );
    $start = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
    $end   = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
    $args  = array(
        'post_type'      => 'wcwa_order_log',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
    );
    if ( $start || $end ) {
        $args['date_query'] = array( array( 'inclusive' => true ) );
        if ( $start ) { $args['date_query'][0]['after'] = $start; }
        if ( $end ) { $args['date_query'][0]['before'] = $end; }
    }
    $q = new WP_Query( $args );
    $daily = array();
    foreach ( $q->posts as $p ) {
        $d = get_post_time( 'Y-m-d', true, $p );
        $t = get_post_meta( $p->ID, '_order_type', true );
        if ( ! isset( $daily[ $d ] ) ) { $daily[ $d ] = array( 'total' => 0, 'product' => 0, 'cart' => 0 ); }
        $daily[ $d ]['total']++;
        if ( $t === 'cart' ) { $daily[ $d ]['cart']++; } else { $daily[ $d ]['product']++; }
    }
    ksort( $daily );
    header( 'Content-Type: text/csv; charset=UTF-8' );
    header( 'Content-Disposition: attachment; filename=wcwa-analytics-' . gmdate( 'Ymd-His' ) . '.csv' );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'Date', 'Total', 'Product', 'Cart' ) );
    foreach ( $daily as $date => $row ) {
        fputcsv( $out, array( $date, $row['total'], $row['product'], $row['cart'] ) );
    }
    fclose( $out );
    exit;
}
add_action( 'admin_post_wcwa_export_analytics', 'wcwa_admin_export_analytics' );
