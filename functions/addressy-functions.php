<?php
/**
 * Addressy Functions
 *
 * Functions
 *
 * @author 		Addressy
 * @package 	addressy
 * @version     1.0.2
 */
 
 
/* exist if directly accessed */
if( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * function addressy_get_field()
 * gets the value of a meta box field for a addressy post
 * @param (string) $field is the name of the field to return
 * @param (int) $post_id is the id of the post for which to look for the field in - defaults to current loop post
 * @param (string) $prefix is the prefix to use for the custom field key. Defaults to _addressy_
 * return (string) $field the value of the field
 */
function addressy_get_field( $field, $post_id = '', $prefix = '_addressy_' ) {
	
	global $post;
	
	/* if no post id is provided use the current post id in the loop */
	if( empty( $post_id ) )
		$post_id = $post->ID;
	
	/* if we have no field name passed go no further */
	if( empty( $field ) )
		return false;
	
	/* build the meta key to return the value for */
	$key = $prefix . $field;
	
	/* gete the post meta value for this field name of meta key */
	$field = get_post_meta( $post_id, $key, true );
	
	return apply_filters( 'addressy_field_value', $field );
	
}



/**
 * function addressy_get_setting()
 *
 * gets a named plugin settings returning its value
 * @param	mixed	key name to retrieve - this is the key of the stored option
 * @return	mixed	the value of the key
 */
function addressy_get_setting( $name = '' ) {
	
	/* if no name is passed */
	if( empty( $name ) ) {
		return false;
	}
	
	/* get the option */
	$pcaOptions = get_option( '_adrsy_settings' );
	$setting = $pcaOptions[ $name ];
	
	/* check we have a value returned */
	if( empty( $setting ) ) {
		return false;
	}
	
	return apply_filters( 'addressy_get_setting', $setting );
}



/**
 * addressy_on_activation()
 * On plugin activation makes current user a wpbasis user and
 * sets an option to redirect the user to another page.
 */
function addressy_on_activation() {
	
	/* set option to initialise the redirect */
	add_option( 'addressy_activation_redirect', true );

}
register_activation_hook( __FILE__, 'addressy_on_activation' );



/**
 * addressy_activation_redirect()
 * Redirects user to the settings page for wp basis on plugin
 * activation.
 */
function addressy_activation_redirect() {
	
	/* check whether we should redirect the user or not based on the option set on activation */
	if( true == get_option( 'addressy_activation_redirect' ) ) {
		
		/* delete the redirect option */
		delete_option( 'addressy_activation_redirect' );
		
		/* redirect the user to the wp basis settings page */
		wp_redirect( admin_url( 'admin.php?page=addressy_settings' ) );
		exit;
		
	}
	
}
add_action( 'admin_init', 'addressy_activation_redirect' );



/**
 * Display an admin notice, if not on the integration screen and if the account isn't yet connected.
 * @since  1.0.0
 * @return void
 */
function addressy_maybe_display_admin_notices () {
	if ( isset( $_GET['page'] ) && 'addressy_settings' == $_GET['page'] ) return; // Don't show these notices on our admin screen.

	if ( isset( $_GET['page'] ) && 'addressy_register' == $_GET['page'] ) return; // Don't show these notices on our register screen.

	$accCode = addressy_get_setting( 'account_code' ) ;
	
	if ( false === $accCode ) {		
		$url = get_settings_url();
		echo '<div class="updated fade"><p>' . sprintf( __( '%sCreate%s an account or %slog in%s to %sconfigure Addressy%s.', 'addressy' ), '<strong>', '</strong>', '<strong>', '</strong>','<a href="' . esc_url( $url ) . '">', '</a>' ). '</p></div>' . "\n" ;
	}	
} 
add_action( 'admin_notices', 'addressy_maybe_display_admin_notices' );



/**
 * Generate a URL to our specific settings screen.
 * @since  1.0.0
 * @return string Generated URL.
 */
function get_settings_url () {
	$url = admin_url( 'admin.php' );
	$url = add_query_arg( 'page', 'addressy_settings', $url );
	return $url;
}



/**
 * addressy_hook_javascript()
 * 
 * Adds the PCA tag to the head of every page
 */
function addressy_hook_javascript() {
	
	$accCode = addressy_get_setting( 'account_code' );
	$licenceKey = addressy_get_setting( 'license_key' );
	$pcaMappings = json_decode( addressy_get_setting( 'field_mappings' ) );
	$pcaCustomJs = json_decode( addressy_get_setting( 'custom_javascript' ) );
	if ( $accCode ) { ?>
	
        <script>
	        (function (a, c, b, e) {
	        a[b] = a[b] || {}; a[b].initial = { accountCode: "<?php echo ( sanitize_text_field( $accCode ) ); ?>", host: "<?php echo ( sanitize_text_field( $accCode ) ); ?>.addressy.com" };
	        a[b].on = a[b].on || function () { (a[b].onq = a[b].onq || []).push(arguments) }; var d = c.createElement("script");
	        d.async = !0; d.src = e; c = c.getElementsByTagName("script")[0]; c.parentNode.insertBefore(d, c)
	        })(window, document, "pca", "//<?php echo ( sanitize_text_field( $accCode ) ); ?>.addressy.com/js/sensor.js");
			(function($) {
				var capturePlusMappings = [];
				var shippingMappings = [];
				var billingMappings = [];
				<?php if ($pcaMappings) echo sanitize_text_field( stripslashes( $pcaMappings ) ); ?>  

				document.addEventListener('focus', function(e) {
					if (e.target.id.includes('shipping')) {
						var contains = false;
						for (var i = 0; i < shippingMappings.length; i++) {
							if (shippingMappings[i].element === e.target.id) {
								contains = true;
								break;
							}
						}
			            if(contains) {
			            	capturePlusMappings.length = 0;
			            	for (var i = 0; i < shippingMappings.length; i++) {
	                            capturePlusMappings.push(shippingMappings[i]);
	                        }
							pca.load();
			            }
					}
					else if (e.target.id.includes('billing')) {
						var contains = false;
						for (var i = 0; i < billingMappings.length; i++) {
							if (billingMappings[i].element === e.target.id) {
								contains = true;
								break;
							}
						}
			            if(contains) {
			            	capturePlusMappings.length = 0;
			            	for (var i = 0; i < billingMappings.length; i++) {
	                            capturePlusMappings.push(billingMappings[i]);
	                        }
							pca.load();
			            }
					}
				}, true);

				pca.on('fields', function(service, key, fields) {
					if (console && console.log) console.log(service);
					if (console && console.log) console.log(key);
					if (console && console.log) console.log(fields);
					if (key === '<?php echo sanitize_text_field( $licenceKey ); ?>' && service === 'capture+') {
						
						if (capturePlusMappings.length > 0) {
	                        fields.length = 0;
	                        for (var i = 0; i < capturePlusMappings.length; i++) {
	                            fields.push(capturePlusMappings[i]);
	                        }
	                    }  
					}
				});
				pca.on('ready', function () {pca.sourceString = "WordPressPlugin-Addressy-v<?php esc_html_e( addressy_plugin_get_version() ); ?>";});
				pca.on('data', function(source, key, address, variations) {
					var provNameElId = "";
					if (pca.platform.productList.hasOwnProperty(key) && pca.platform.productList[key].hasOwnProperty("PLATFORM_CAPTUREPLUS")) {
						for (var b = 0; b < pca.platform.productList[key].PLATFORM_CAPTUREPLUS.bindings.length; b++) {
							for (var f = 0; f < pca.platform.productList[key].PLATFORM_CAPTUREPLUS.bindings[b].fields.length; f++) {
								var el = document.getElementById(pca.platform.productList[key].PLATFORM_CAPTUREPLUS.bindings[b].fields[f].element);
								if (el) {
									if (pca.platform.productList[key].PLATFORM_CAPTUREPLUS.bindings[b].fields[f].field === "{ProvinceName}" || pca.platform.productList[key].PLATFORM_CAPTUREPLUS.bindings[b].fields[f].field === "{ProvinceCode}") {
										provNameElId = el.id;
									}
								}
							}
						}
						if (provNameElId != "") {
							var el = document.getElementById(provNameElId);
							if (el && el.options) {
								for (var j = 0; j < el.options.length; j++) {
									if (el.options[j].text === address.ProvinceName) {
										el.selectedIndex = j;
										if ($ && Select2) {
											$('select').trigger('change.select2');
										}
										break;
									}
								}
								pca.fire(el, 'change');
							}
						}
					}
				});
				if($){
					$(document).bind('gform_post_render', function(){
						window.setTimeout(function(){
							pca.load();
						}, 200);
					});
				};

				<?php if ($pcaCustomJs) echo sanitize_text_field ( stripslashes( $pcaCustomJs ) ); ?>  
			})(jQuery);
		
        </script>

        
	<?php }
}
add_action( 'wp_head', 'addressy_hook_javascript' );
add_action( 'admin_head', 'addressy_hook_javascript' );




function addressy_allow_setup() {
	
	$qval = isset( $_REQUEST[ 'pcasetup_ts' ] );
	if ( $qval ) {
		set_transient( 'allow_pca_setup', sanitize_text_field( $_REQUEST[ 'pcasetup_ts' ] ), 20 * MINUTE_IN_SECONDS );		
	}	
	if ( get_transient( 'allow_pca_setup' ) ) {
		remove_action( 'template_redirect', 'wc_send_frame_options_header' );
		remove_action( 'admin_init', 'send_frame_options_header' );
	}
}
add_action( 'init', 'addressy_allow_setup', 20, 0 );


