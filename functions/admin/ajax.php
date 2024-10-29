<?php
/**
 * Addressy AJAX Functions
 *
 * Functions for ajaxy things.
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
 * addressy_save_settings_callback()
 * callback for the save settigns ajax call
 * @return [type] [description]
 */
function addressy_save_settings_callback() {

	global $wpdb;
	$result = array (
		'success' => false,
		'data' => $_POST['field_mappings']
	);

	try {
		$accCode = $_POST["account_code"];
		$token = $_POST["account_token"];
		$dailyLimit = $_POST["daily_limit"];
		$userLimit = $_POST["user_limit"];
		$licenseKey = $_POST["license_key"];
		$urlRestrictions = $_POST["url_restrictions"];

		$settings = get_option( '_adrsy_settings' );
		$settings[ 'account_code' ] = sanitize_text_field ( $accCode );
		$settings[ 'account_token' ] = sanitize_text_field ( $token );
		$settings[ 'daily_limit' ] = intval ( $dailyLimit );
		$settings[ 'user_limit' ] = intval ( $userLimit );
		$settings[ 'license_key' ] = sanitize_text_field ( $licenseKey );
		$settings[ 'url_restrictions' ] = array_map( 'esc_url' , $urlRestrictions );

		if ( isset( $_POST['field_mappings'] ) ) {
			$settings[ 'field_mappings' ] = json_encode( sanitize_text_field( $_POST['field_mappings'] ) );
		} else {
			$settings[ 'field_mappings' ] = json_encode( 'billingMappings=[{element:"billing_company",field:"{Company}",mode:7},{element:"billing_address_1",field:"{Line1}",mode:3},{element:"billing_address_2",field:"{Line2}",mode:2},{element:"billing_address_3",field:"{Line3}",mode:2},{element:"billing_city",field:"{City}",mode:2},{element:"billing_state",field:"{ProvinceName}",mode:2},{element:"billing_postcode",field:"{PostalCode}",mode:3},{element:"billing_country",field:"{CountryName}",mode:10},{element:"_billing_company",field:"{Company}",mode:7},{element:"_billing_address_1",field:"{Line1}",mode:3},{element:"_billing_address_2",field:"{Line2}",mode:2},{element:"_billing_address_3",field:"{Line3}",mode:2},{element:"_billing_city",field:"{City}",mode:2},{element:"_billing_state",field:"{ProvinceName}",mode:2},{element:"_billing_postcode",field:"{PostalCode}",mode:3},{element:"_billing_country",field:"{CountryName}",mode:10}];shippingMappings=[{element:"shipping_company",field:"{Company}",mode:7},{element:"shipping_address_1",field:"{Line1}",mode:3},{element:"shipping_address_2",field:"{Line2}",mode:2},{element:"shipping_address_3",field:"{Line3}",mode:2},{element:"shipping_city",field:"{City}",mode:2},{element:"shipping_state",field:"{ProvinceName}",mode:2},{element:"shipping_postcode",field:"{PostalCode}",mode:3},{element:"shipping_country",field:"{CountryName}",mode:10},{element:"_shipping_company",field:"{Company}",mode:7},{element:"_shipping_address_1",field:"{Line1}",mode:3},{element:"_shipping_address_2",field:"{Line2}",mode:2},{element:"_shipping_address_3",field:"{Line3}",mode:2},{element:"_shipping_city",field:"{City}",mode:2},{element:"_shipping_state",field:"{ProvinceName}",mode:2},{element:"_shipping_postcode",field:"{PostalCode}",mode:3},{element:"_shipping_country",field:"{CountryName}",mode:10}];' );
		}

		if ( isset( $_POST['custom_javascript'] ) ) {
			$settings[ 'custom_javascript' ] = json_encode( sanitize_text_field( $_POST['custom_javascript'] ) );
		} else {
			$settings[ 'custom_javascript' ] = json_encode( '' );
		} 

		update_option( '_adrsy_settings', $settings );
		$result['success'] = true;
	}
	catch(Exception $ex) {
		// we will only be passing back the default result
	}

	wp_send_json( $result );

}
add_action( 'wp_ajax_addressy_save_settings', 'addressy_save_settings_callback' );



/**
 * addressy_logout_callback()
 * callback for the logout ajax call
 * @return [type] [description]
 */
function addressy_logout_callback() {

	global $wpdb;
	$result = array ( 'success' => false );

	try {
		delete_option( '_adrsy_settings' );
		$result['success'] = true;
	}
	catch(Exception $ex) {
		// we will only be passing back the default result
	}
	wp_send_json( $result );
}
add_action( 'wp_ajax_addressy_logout', 'addressy_logout_callback' );


