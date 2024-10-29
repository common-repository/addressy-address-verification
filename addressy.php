<?php
/*
Plugin Name: Addressy
Plugin URI: https://www.addressy.com/integrations/wordpress-address-verification/ 
Description: Address verification made easy - a faster, smarter way to capture and verify addresses on your website.
Version: 1.0.2
Author: Addressy
Author URI: http://addressy.com
License: GPLv2 or later
Text Domain: addressy
*/

/* exist if directly accessed */
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * initialises plugin
 * @return [type] [description]
 */
function addressy_init() {

	/* define variable for path to this plugin file. */
	define( 'ADDRESSY_LOCATION', dirname( __FILE__ ) );
	define( 'ADDRESSY_LOCATION_URL', plugin_dir_url( __FILE__ ) );

	/* load required files & functions */
	require_once( dirname( __FILE__ ) . '/functions/addressy-functions.php' );
    
    if ( is_admin() ) {
        /* load admin files & functions */
        require_once( dirname( __FILE__ ) . '/functions/admin/admin.php' );
        require_once( dirname( __FILE__ ) . '/functions/admin/ajax.php' );
        require_once( dirname( __FILE__ ) . '/functions/admin/woocommerce/woocommerce.php' );
    }
        
}
add_action( 'init', 'addressy_init' );


/**
 * Returns current plugin version. (https://code.garyjones.co.uk/get-wordpress-plugin-version)
 * 
 * @return string Plugin version
 */
function addressy_plugin_get_version() {
	if ( ! function_exists( 'get_plugins' ) )
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	$plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
	$plugin_file = basename( ( __FILE__ ) );
	return $plugin_folder[$plugin_file]['Version'];
}
