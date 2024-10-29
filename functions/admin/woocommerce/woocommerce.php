<?php
/**
 * Addressy WooCommerce Functions
 *
 * Functions for WooCommerce specific things.
 *
 * @author 		Addressy
 * @package 	addressy/admin
 * @version     1.0.2
 */
 
 
/* exist if directly accessed */
if( ! defined( 'ABSPATH' ) ) {
    exit;
}



/**
 * addressy_hook_woocommerce_javascript()
 * 
 * Adds the PCA tag to the head of certain admin pages
 * if WooCommerce is installed
 */
function addressy_hook_woocommerce_javascript() {    
    /* Check if WooCommerce is active */
    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        addressy_hook_javascript();
    }
}
add_action( 'show_user_profile', 'addressy_hook_woocommerce_javascript' );
add_action( 'edit_user_profile', 'addressy_hook_woocommerce_javascript' );
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'addressy_hook_woocommerce_javascript');


