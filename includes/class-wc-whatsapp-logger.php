<?php
/**
 * WC WhatsApp Logger
 *
 * @package WhatsApp_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Custom Post Type for WhatsApp Orders.
 */
function wcwa_register_order_cpt() {
	$labels = array(
		'name'               => __( 'WhatsApp Orders', 'whatsapp-woocommerce' ),
		'singular_name'      => __( 'WhatsApp Order', 'whatsapp-woocommerce' ),
		'menu_name'          => __( 'WhatsApp Orders', 'whatsapp-woocommerce' ),
		'all_items'          => __( 'All Orders', 'whatsapp-woocommerce' ),
		'add_new_item'       => __( 'Add New Order', 'whatsapp-woocommerce' ),
		'new_item'           => __( 'New Order', 'whatsapp-woocommerce' ),
		'view_item'          => __( 'View Order', 'whatsapp-woocommerce' ),
		'search_items'       => __( 'Search Orders', 'whatsapp-woocommerce' ),
		'not_found'          => __( 'No orders found', 'whatsapp-woocommerce' ),
		'not_found_in_trash' => __( 'No orders found in trash', 'whatsapp-woocommerce' ),
	);

	$args = array(
		'label'               => __( 'WhatsApp Order', 'whatsapp-woocommerce' ),
		'description'         => __( 'Logs of orders placed via WhatsApp form.', 'whatsapp-woocommerce' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'custom-fields' ),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 5,
		'menu_icon'           => 'dashicons-whatsapp',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => false,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'capability_type'     => 'post',
	);

	register_post_type( 'wcwa_order_log', $args );
}
add_action( 'init', 'wcwa_register_order_cpt', 0 );

/**
 * Log a WhatsApp order.
 *
 * @param string $subject       Log title suffix.
 * @param string $details       Full message body.
 * @param string $customer_name Customer name.
 * @param string $customer_phone Customer phone.
 * @return int|WP_Error Post ID on success.
 */
function wcwa_log_order( $subject, $details, $customer_name, $customer_phone ) {
    $order_data = array(
        'post_title'   => sanitize_text_field( $subject ) . ' – ' . sanitize_text_field( $customer_name ),
        'post_content' => sanitize_textarea_field( $details ),
        'post_status'  => 'publish',
        'post_type'    => 'wcwa_order_log',
    );

    $new_order_id = wp_insert_post( $order_data );

    if ( ! is_wp_error( $new_order_id ) ) {
        update_post_meta( $new_order_id, '_customer_name', sanitize_text_field( $customer_name ) );
        update_post_meta( $new_order_id, '_customer_phone', sanitize_text_field( $customer_phone ) );
        $type = ( false !== stripos( $subject, 'Cart' ) ) ? 'cart' : 'product';
        update_post_meta( $new_order_id, '_order_type', $type );
    }

    return $new_order_id;
}
