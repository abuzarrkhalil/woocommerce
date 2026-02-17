<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}
$action = get_option( 'wcwa_uninstall_action', 'keep' );
if ( $action !== 'delete' ) {
    return;
}
$keys = array(
    'wcwa_whatsapp_number',
    'wcwa_button_text_product',
    'wcwa_button_text_cart',
    'wcwa_enable_product_button',
    'wcwa_enable_cart_button',
    'wcwa_enable_add_to_cart_button',
    'wcwa_enable_checkout_button',
    'wcwa_product_button_position',
    'wcwa_template_product',
    'wcwa_template_cart',
    'wcwa_show_only_instock',
    'wcwa_display_mode',
    'wcwa_display_categories',
    'wcwa_store_hours_enable',
    'wcwa_store_hours_open',
    'wcwa_store_hours_close',
    'wcwa_store_hours_days',
    'wcwa_menu_seen',
    'wcwa_flushed_once',
    'wcwa_log_retention_days',
    'wcwa_routing_rules'
);
foreach ( $keys as $k ) {
    delete_option( $k );
}
$q = new WP_Query( array(
    'post_type'      => 'wcwa_order_log',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
) );
if ( is_a( $q, 'WP_Query' ) && ! empty( $q->posts ) ) {
    foreach ( $q->posts as $id ) {
        wp_delete_post( (int) $id, true );
    }
}
