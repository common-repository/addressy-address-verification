<?php
/**
 * Addressy Admin Functions
 *
 * Functions for admin specific things.
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
 * function addressy_admin_styles()
 * outputs css for the admin pages
 */
function addressy_admin_styles( $hook ) {

	if ( $hook == 'toplevel_page_addressy_settings' ) {
	} elseif ( $hook == 'admin_page_addressy_register' ) {
	} else {
		
		return;
	}

	wp_enqueue_style( 'addressy-admin-foundation-styles', ADDRESSY_LOCATION_URL . '/css/foundation.min.css' );
	wp_enqueue_style( 'addressy-admin-styles', ADDRESSY_LOCATION_URL . '/css/admin.css' );

}
add_action( 'admin_enqueue_scripts', 'addressy_admin_styles' );


/**
 * function addressy_admin_js()
 * outputs css for the admin pages
 */
function addressy_admin_js() {

	wp_enqueue_script( 'addressy-admin-foundation-js', ADDRESSY_LOCATION_URL . '/js/foundation.min.js', array( 'jquery' ), false, true );
	wp_enqueue_script( 'addressy-d3js', 'https://d3js.org/d3.v4.min.js', array( 'addressy-admin-foundation-js' ), false, true );

}
add_action( 'admin_enqueue_scripts', 'addressy_admin_js' );

/**
 * function addressy_admin_angular()
 * outputs files needed for angular
 */
function addressy_admin_angular() {
	wp_enqueue_script( 'addressy-admin-angular.min.js', ADDRESSY_LOCATION_URL . '/js/app/angular.min.js', array( 'jquery' ), false, true );
	wp_enqueue_script( 'addressy-admin-angular.app.js', ADDRESSY_LOCATION_URL . '/js/app/app.js', array( 'jquery' ), false, true );
	wp_enqueue_script( 'addressy-admin-angular.numeral-min.js', ADDRESSY_LOCATION_URL . '/js/app/numeral.min.js', array( 'jquery' ), false, true );
	wp_enqueue_script( 'addressy-rzslider.min.js', ADDRESSY_LOCATION_URL . '/js/app/rzslider.min.js', array( 'addressy-admin-angular.min.js' ), false, true );
	wp_enqueue_style( 'addressy-admin-rzslider-styles', ADDRESSY_LOCATION_URL . '/css/rzslider.css' );	
}
add_action( 'admin_enqueue_scripts', 'addressy_admin_angular' );


/**
 * addressy_add_admin_sub_menus()
 * adds the plugins sub menus under the main admin menu item
 */
function addressy_add_admin_sub_menus() {

	/*** do we want the menu link to be a main menu item or a submenu item? ***/

	// main menu item
	add_menu_page(
		'Addressy',
		'Addressy',
		'manage_options',
		'addressy_settings',
		'addressy_settings_page_content',
		'',
		100
	);
	
	/*	
	// sub menu item - puts the link within the Settings group
	add_submenu_page(
		'options-general.php', // parent_slug,
		'Addressy', // page_title,
		'Addressy', // menu_title,
		'manage_options', // capability,
		'addressy_settings', // menu slug,
		'addressy_settings_page_content' // callback function for the pages content
	);
	*/	

	// sub menu item - hidden page for registering
	add_submenu_page(
		null, // parent_slug,
		'Addressy Register', // page_title,
		'Addressy Register', // menu_title,
		'manage_options', // capability,
		'addressy_register', // menu slug,
		'addressy_register_page_content' // callback function for the pages content
	);

}
add_action( 'admin_menu', 'addressy_add_admin_sub_menus' );



/**
 * addressy_register_settings()
 * Register the settings for this plugin. Just a username and a
 * password for authenticating.
 */
function addressy_register_default_settings() {

	/* build array of setttings to register */
	$addressy_registered_settings = apply_filters( 'addressy_registered_settings', array() );
	
	/* loop through registered settings array */
	foreach( $addressy_registered_settings as $addressy_registered_setting ) {
		
		/* register a setting */
		register_setting( 'addressy_settings', $addressy_registered_setting );
		
	}
		
}
add_action( 'admin_init', 'addressy_register_default_settings' );




function addressy_get_current_plan_details() {
    $pcaOptions = get_option( '_adrsy_settings' );
	
	$pcaAccCode = $pcaOptions[ 'account_code' ];
    $pcaToken = $pcaOptions[ 'account_token' ];               
    if ($pcaAccCode && $pcaToken) 
    {
        
        try {
        	$response = wp_remote_post(
        		'https://app_api.pcapredict.com/api/accountplan',
        		array(
        			'method' => 'POST',
        			'timeout' => 30,
        			'redirection' => 0,
        			'httpversion' => '1.0',
        			'blocking' => true,
        			'headers' => array(
        				'Content-Type' => 'application/json',
        				'Authorization' => 'Basic ' . base64_encode( $pcaAccCode . ':' . $pcaToken )
        			),
        			'body' => '{"AccountCode":"' . $pcaAccCode . '"}',
        			'cookies' => array()
        		)
        	);

        	if ( is_wp_error( $response ) ) {

        	} 
        	else {
        		$jData = json_decode( $response['body'] );
        		$planDetails = array(
        			'current_plan_name' => $jData->currentPlanName,
        			'current_plan_credits' => $jData->currentPlanCredits,
                    'current_plan_period' => $jData->currentPlanPeriod,
                    'current_plan_refresh_date' => str_replace( 'T',' ', $jData->currentPlanRefreshDate ),
					'current_plan_credits_used' => ( $jData->currentPlanCredits - $jData->currentPlanCreditsRemaining ),
					'current_plan_percentage_used' =>  floor( 100 - ( $jData->currentPlanCreditsRemaining * 100 / $jData->currentPlanCredits ) )
        		);
				update_option( '_adrsy_plan_details', $planDetails );
        	}

        } catch ( Exception $e ) {
            die( $e->getMessage() );
        }
    }
}




function addressy_refresh_settings_from_server() {
    $pcaOptions = get_option( '_adrsy_settings' );
	$pcaAccCode = $pcaOptions[ 'account_code' ];
    $pcaToken = $pcaOptions[ 'account_token' ];
    $pcaLicenseKey = $pcaOptions[ 'license_key' ];
    $pcaFieldMappings = $pcaOptions[ 'field_mappings' ];
    $pcaCustomJavaScript = $pcaOptions[ 'custom_javascript' ];

    if ( $pcaAccCode && $pcaToken && $pcaLicenseKey ) {
        
        try {
        	$response = wp_remote_post(
        		'https://app_api.pcapredict.com/api/getlicensedetails',
        		array(
        			'method' => 'POST',
        			'timeout' => 30,
        			'redirection' => 0,
        			'httpversion' => '1.0',
        			'blocking' => true,
        			'headers' => array(
        				'Content-Type' => 'application/json',
        				'Authorization' => 'Basic ' . base64_encode( $pcaAccCode . ':' . $pcaToken )
        			),
        			'body' => '{"Key":"' . $pcaLicenseKey . '", "ReturnKeyusage":true, "UsageDaysBack":10}',
        			'cookies' => array()
        		)
        	);

        	if ( is_wp_error( $response ) ) {

        	} 
        	else {
        		$jData = json_decode( $response['body'] );
        		$settings = array(
					'account_code' => $pcaAccCode,
					'account_token' => $pcaToken,
					'license_key' => $pcaLicenseKey,
					'field_mappings' => $pcaFieldMappings,
					'custom_javascript' => $pcaCustomJavaScript,
					'daily_limit' => $jData->dailyLimit,
                    'user_limit' => $jData->userLimit,
                    'url_restrictions' => $jData->urlRestrictions,
					'key_name' => $jData->keyName,
					'key_usage' => $jData->keyUsage,
					'key_usage_sum' => 0
				);
				
				for ( $i = 0; $i < 10; $i++)
				{
					$settings['key_usage_sum'] += $settings['key_usage'][$i];
				}
				
				/*
				var_dump($settings);
				
				die();
				*/
				update_option( '_adrsy_settings', $settings );
        	}

        } catch ( Exception $e ) {
            die( $e->getMessage() );
        }
    }
}



/**
 * addressy_settings_page_content()
 * Builds the content for the admin settings page.
 */
function addressy_settings_page_content() {

	addressy_get_current_plan_details();
	addressy_refresh_settings_from_server();

	$pcaPlan = 						get_option( '_adrsy_plan_details' );
	$pcaCurrentPlanName = 			$pcaPlan[ 'current_plan_name' ];
	$pcaCurrentPlanCredits =		$pcaPlan[ 'current_plan_credits' ];
	$pcaCurrentPlanPeriod = 		$pcaPlan[ 'current_plan_period' ];
	$pcaCurrentPlanRefreshDate =	$pcaPlan[ 'current_plan_refresh_date' ];
	$pcaCurrentPlanCreditsUsed = 	$pcaPlan[ 'current_plan_credits_used' ];
	$pcaCurrentPlanPercentageUsed = $pcaPlan[ 'current_plan_percentage_used' ];

	$pcaOptions = 		get_option( '_adrsy_settings' );
	$pcaAccCode = 		strtoupper( $pcaOptions[ 'account_code' ] );
	$pcaToken = 		$pcaOptions[ 'account_token' ];
	$pcaLicenseKey = 	$pcaOptions[ 'license_key' ];
	$pcaDailyLimit = 	$pcaOptions[ 'daily_limit' ];
	$pcaLimitPerUser =	$pcaOptions[ 'user_limit' ];
	$pcaLimitByUrl = 	$pcaOptions[ 'url_restrictions' ];
	$pcaMappings = 		json_decode( $pcaOptions[ 'field_mappings' ] );
	$pcaCustomJS = 		json_decode( $pcaOptions[ 'custom_javascript' ] );
	$pcaKeyName = 		$pcaOptions[ 'key_name' ];
	$pcaKeyUsageSum = 		$pcaOptions[ 'key_usage_sum' ];
	
	?>
	
	<script type="text/javascript">
		<?php if ($pcaAccCode) : ?>
			var _dailyLimit = <?php echo esc_js ( intval ( $pcaDailyLimit ) ) ?>;
			var _limitPerUser = <?php echo esc_js ( intval ( $pcaLimitPerUser ) ) ?>;
			var dailyLimitValues = [5, 10, 15, 20, 25, 50, 75, 100, 250, 500, 1000, 999999];
			var individualIpLimitValues = [1, 2, 3, 4, 5, 10, 0];			
		<?php endif ?>
	</script>

	<div ng-app = "AddressyWordPress" ng-controller = 'keyCtrl' class="wrap addressy-container">
			
		<div class="row">
			<div class="small-12 columns no-padding">
			<?php if ($pcaAccCode) : ?>
				<div class="addressy-header dark-header">
			<?php else : ?>	
				<div class="addressy-header">
			<?php endif; ?>
					<div class="row">
						<div class="small-12 medium-3 columns">
							<span class="main-logo">
							<?php if ($pcaAccCode) : ?>
								<img src="https://www.addressy.com/Content/Assets/Logos/Addressy-logo-white.png" alt="<?php esc_attr_e( 'Addressy Settings', 'addressy' ); ?>" class="addressy-logo" />
							<?php else : ?>	
								<img src="https://www.addressy.com/Content/Assets/Logos/Addressy-logo-dark-raspberry.png" alt="<?php esc_attr_e( 'Addressy Settings', 'addressy' ); ?>" class="addressy-logo" />
							<?php endif; ?>
							</span>
						</div>
						<div class = "small-12 medium-6 columns">
							<div class="credit-bar">
								<?php if ($pcaAccCode) : ?>
									<p>
										<?php esc_html_e ( sprintf( __( '%1$s / %2$s ', 'addressy' ), intval( $pcaCurrentPlanCreditsUsed ), intval( $pcaCurrentPlanCredits ) ) ); ?>
										<span><?php esc_html_e ( sprintf( __( '%1$s credits used this %2$s', 'addressy' ), ($pcaCurrentPlanName == "Free" ? ' US' : ''), $pcaCurrentPlanPeriod ) ); ?></span>
									</p>								
									<div class="outer">
										<div class="inner" style="width:<?php esc_attr_e ( intval( $pcaCurrentPlanPercentageUsed ) ) ?>%;"></div>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="small-12 medium-3 columns">
							<?php if ($pcaAccCode) : ?>
								<form id="formLogOut" onsubmit="return false;">
									<button id="btnLogOut" type="submit" form="formLogOut" value="Log out" class="btnNarrow"><?php esc_html_e( 'Log out', 'addressy' ); ?></button>
								</form>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>			
		
		<div class="row">

			<div class="small-12 columns">
				<div class="addressy-message"></div>
			</div>					
			
			<div id="post-body" class="small-12 columns">
				
				<div class="row">					
					<?php if ($pcaAccCode) : ?>
						<div class="small-12 medium-9 columns postbox-container" id="postbox-container-2">						
					<?php else : ?>
						<div class="small-12 medium-8 medium-offset-2 columns postbox-container" id="postbox-container-2">
					<?php endif; ?>
						<?php								
						/* output settings field nonce action fields etc. */
						settings_fields( 'addressy_settings' );

						/* do before settings page action */
						do_action( 'addressy_before_settings_page' );
						
						if ($pcaAccCode) : ?>

							<div class="adrsy-box-container">
								<div class="licence">
									
									<div class="container licence-info">
										<div class="row align-bottom">
						                    <div class="columns small-5">
						                        <h3>Address Verification</h3>
						                        <p class="intro"><?php esc_html_e( $pcaKeyName )?></p>
						                    </div>
						                    <div class="columns small-5 text-center">
						                        <span class="large-stats ng-binding"><?php esc_html_e( $pcaKeyUsageSum ) ?></span> <?php esc_html_e( 'lookups in last 10 days', 'addressy' ); ?>						                        
						                    </div>
						                    <div class="columns small-2">
						                        <a class="settings" ng-click="refresh_slider()">
						                            <div class="cog" ></div>
						                            <p>Settings</p>
						                        </a>						                        
						                    </div>
						                </div>
										<div class="row align-bottom">
						                    <div class="columns medium-6 small-12">
												<!--
						                        <div usage-bar="" class="barContainer clearfix" key="key"><svg class="spark" preserveAspectRatio="xMinYMin slice" viewBox="0 0 362 100"><g transform="translate(50,5)"><g class="x axis" transform="translate(0, 75)" fill="none" font-size="10" font-family="sans-serif" text-anchor="middle"><path class="domain" stroke="#000" d="M0.5,6V0.5H302.5V6"></path><g class="tick" opacity="1" transform="translate(25.16666666666668,0)"><line stroke="#000" y2="6" x1="0.5" x2="0.5"></line><text fill="#000" y="9" x="0.5" dy="0.71em">F</text></g><g class="tick" opacity="1" transform="translate(53.12962962962964,0)"><line stroke="#000" y2="6" x1="0.5" x2="0.5"></line><text fill="#000" y="9" x="0.5" dy="0.71em">S</text></g><g class="tick" opacity="1" transform="translate(81.0925925925926,0)"><line stroke="#000" y2="6" x1="0.5" x2="0.5"></line><text fill="#000" y="9" x="0.5" dy="0.71em">S</text></g><g class="tick" opacity="1" transform="translate(109.05555555555557,0)"><line stroke="#000" y2="6" x1="0.5" x2="0.5"></line><text fill="#000" y="9" x="0.5" dy="0.71em">M</text></g><g class="tick" opacity="1" transform="translate(137.01851851851853,0)"><line stroke="#000" y2="6" x1="0.5" x2="0.5"></line><text fill="#000" y="9" x="0.5" dy="0.71em">T</text></g><g class="tick" opacity="1" transform="translate(164.98148148148152,0)"><line stroke="#000" y2="6" x1="0.5" x2="0.5"></line><text fill="#000" y="9" x="0.5" dy="0.71em">W</text></g><g class="tick" opacity="1" transform="translate(192.94444444444449,0)"><line stroke="#000" y2="6" x1="0.5" x2="0.5"></line><text fill="#000" y="9" x="0.5" dy="0.71em">T</text></g><g class="tick" opacity="1" transform="translate(220.90740740740745,0)"><line stroke="#000" y2="6" x1="0.5" x2="0.5"></line><text fill="#000" y="9" x="0.5" dy="0.71em">F</text></g><g class="tick" opacity="1" transform="translate(248.8703703703704,0)"><line stroke="#000" y2="6" x1="0.5" x2="0.5"></line><text fill="#000" y="9" x="0.5" dy="0.71em">S</text></g><g class="tick" opacity="1" transform="translate(276.83333333333337,0)"><line stroke="#000" y2="6" x1="0.5" x2="0.5"></line><text fill="#000" y="9" x="0.5" dy="0.71em">S</text></g></g><g class="y axis" fill="none" font-size="10" font-family="sans-serif" text-anchor="end"><path class="domain" stroke="#000" d="M-6,75.5H0.5V0.5H-6"></path><g class="tick" opacity="1" transform="translate(0,75)"><line stroke="#000" x2="-6" y1="0.5" y2="0.5"></line><text fill="#000" x="-9" y="0.5" dy="0.32em">0</text></g><g class="tick" opacity="1" transform="translate(0,62.5)"><line stroke="#000" x2="-6" y1="0.5" y2="0.5"></line><text fill="#000" x="-9" y="0.5" dy="0.32em">1</text></g><g class="tick" opacity="1" transform="translate(0,50)"><line stroke="#000" x2="-6" y1="0.5" y2="0.5"></line><text fill="#000" x="-9" y="0.5" dy="0.32em">2</text></g><g class="tick" opacity="1" transform="translate(0,37.5)"><line stroke="#000" x2="-6" y1="0.5" y2="0.5"></line><text fill="#000" x="-9" y="0.5" dy="0.32em">3</text></g><g class="tick" opacity="1" transform="translate(0,25)"><line stroke="#000" x2="-6" y1="0.5" y2="0.5"></line><text fill="#000" x="-9" y="0.5" dy="0.32em">4</text></g><g class="tick" opacity="1" transform="translate(0,12.5)"><line stroke="#000" x2="-6" y1="0.5" y2="0.5"></line><text fill="#000" x="-9" y="0.5" dy="0.32em">5</text></g><g class="tick" opacity="1" transform="translate(0,0)"><line stroke="#000" x2="-6" y1="0.5" y2="0.5"></line><text fill="#000" x="-9" y="0.5" dy="0.32em">6</text></g></g><rect rx="4" ry="4" class="backbar" x="22.37037037037038" width="5.592592592592592" y="0" height="75"></rect><rect rx="4" ry="4" class="backbar" x="50.33333333333334" width="5.592592592592592" y="0" height="75"></rect><rect rx="4" ry="4" class="backbar" x="78.2962962962963" width="5.592592592592592" y="0" height="75"></rect><rect rx="4" ry="4" class="backbar" x="106.25925925925928" width="5.592592592592592" y="0" height="75"></rect><rect rx="4" ry="4" class="backbar" x="134.22222222222223" width="5.592592592592592" y="0" height="75"></rect><rect rx="4" ry="4" class="backbar" x="162.18518518518522" width="5.592592592592592" y="0" height="75"></rect><rect rx="4" ry="4" class="backbar" x="190.14814814814818" width="5.592592592592592" y="0" height="75"></rect><rect rx="4" ry="4" class="backbar" x="218.11111111111114" width="5.592592592592592" y="0" height="75"></rect><rect rx="4" ry="4" class="backbar" x="246.0740740740741" width="5.592592592592592" y="0" height="75"></rect><rect rx="4" ry="4" class="backbar" x="274.03703703703707" width="5.592592592592592" y="0" height="75"></rect><rect class="bar" rx="4" ry="4" x="22.37037037037038" width="5.592592592592592" y="0" height="75"></rect><rect class="bar" rx="4" ry="4" x="50.33333333333334" width="5.592592592592592" y="75" height="0"></rect><rect class="bar" rx="4" ry="4" x="78.2962962962963" width="5.592592592592592" y="75" height="0"></rect><rect class="bar" rx="4" ry="4" x="106.25925925925928" width="5.592592592592592" y="62.5" height="12.5"></rect><rect class="bar" rx="4" ry="4" x="134.22222222222223" width="5.592592592592592" y="75" height="0"></rect><rect class="bar" rx="4" ry="4" x="162.18518518518522" width="5.592592592592592" y="75" height="0"></rect><rect class="bar" rx="4" ry="4" x="190.14814814814818" width="5.592592592592592" y="75" height="0"></rect><rect class="bar" rx="4" ry="4" x="218.11111111111114" width="5.592592592592592" y="75" height="0"></rect><rect class="bar" rx="4" ry="4" x="246.0740740740741" width="5.592592592592592" y="75" height="0"></rect><rect class="bar" rx="4" ry="4" x="274.03703703703707" width="5.592592592592592" y="75" height="0"></rect></g></svg></div>
												-->
						                    </div>
						                    
						                </div>
									</div>

									<div id="keyDisplay" class="settings-container">        
										<form id="formSettings" onsubmit="return false;">
											<legend><?php  esc_html_e( 'Configuration', 'addressy' ); ?></legend>
											<div class="container">
												<div class="fieldset row">
													<div class="small-12 columns">
														<label><?php esc_html_e( 'Daily Limit', 'addressy' ); ?></label>
													</div>
													<div class="small-12 columns">
														<rzslider rz-slider-options="dailyLimitSlider" rz-slider-model="dailyLimitIndex" ></rzslider>
														<input type="text" class="hidden" name="dailyLimit" id="dailyLimit"  ng-value="dailyLimitIndex" />
														<p class="small"><?php esc_html_e( 'Set a maximum daily limit for lookups.', 'addressy' ); ?></p>
													</div>
												</div>
												<div class="fieldset row">
													<div class="small-12 columns">
														<label><?php esc_html_e( 'Limit per user', 'addressy' ); ?></label>
													</div>
													<div class="small-12 columns">
														<rzslider rz-slider-options="individualIpLimitSlider" rz-slider-model="ipLimitIndex" ></rzslider>
														<input type="text" class="hidden" name="limitPeruser" id="limitPerUser"  ng-value="ipLimitIndex" />
														<p class="small"><?php esc_html_e( 'Set a maximum number of lookups for each individual user.', 'addressy' ); ?></p>
													</div>
												</div>												
												<div class="fieldset row">
													<div class="small-12 medium-2 columns">
														<label><?php esc_html_e( 'Limit By URL', 'addressy' ); ?></label>
													</div>
													<div class="small-12 medium-10 columns">
														<textarea name="adrsy_url_restrictions" id="limitbyurl"><?php echo ( esc_textarea ( implode( "\r\n", $pcaLimitByUrl ) ) ); ?></textarea>
														<p class="small"><?php esc_html_e( 'You can restrict the service to specific URLs. One URL per line. Leave blank for no restrictions.', 'addressy' ); ?></p>
													</div>
												</div>

												<div class="fieldset row">
													<div class="small-12 medium-2 columns">
														<label><?php esc_html_e( 'Field Mappings', 'addressy' ); ?></label>
													</div>
													<div class="small-12 medium-10 columns">
														<textarea name="adrsy_field_mappings" id="fieldmappings"><?php echo ( sanitize_text_field( stripslashes( $pcaMappings ) ) ); ?></textarea>
														<p class="small"><?php esc_html_e( 'Do not make changes to the Field Mappings configuration unless advised by Addressy Support team or your developer', 'addressy' ); ?></p>
													</div>
												</div>

												<div class="fieldset row">
													<div class="small-12 medium-2 columns">
														<label><?php esc_html_e( 'Custom JavaScript', 'addressy' ); ?></label>
													</div>
													<div class="small-12 medium-10 columns">
														<textarea name="adrsy_custom_javascript" id="customjavascript"><?php echo ( sanitize_text_field( stripslashes ( $pcaCustomJS ) ) ); ?></textarea>
														<p class="small"><?php esc_html_e( 'Paste any custom JavaScript code that have in here.', 'addressy' ); ?></p>
													</div>
												</div>

												<div class="fieldset row">
													<div class="small-12 medium-2 columns">
														<label></label>
													</div>
													<div class="small-12 medium-10 columns end">
														<button class="fr" id="btnSave" type="submit" form="formSettings" value="Save"><?php esc_html_e( 'Save', 'addressy' ); ?></button>
													</div>
												</div>
											</div>
												
												
										</form>
									</div>

								</div>
							</div>

						<?php else : ?>
								<div class="small-12 columns">
										<h3 class="text-center"><?php esc_html_e( 'Log in to Addressy', 'addressy' ); ?></h3>											
									</div>
								<div class="adrsy-box-container login-container" style="display: block;">
									
									<form id="formLogIn" onsubmit="return false;">
										<div class="fieldset row">
											<div class="small-12 columns">												
												<p class="intro"><?php esc_html_e( 'You need an Addressy account in order to use this extension. If you don\'t have an Addressy account, you can', 'addressy' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=addressy_register' ) );?>"><?php esc_html_e( 'register for free', 'addressy' ); ?></a></p>
											</div>
										</div>
										<div class="fieldset row">
											<div class="small-12 columns">
												<label><?php esc_html_e( 'Email address', 'addressy' ); ?></label>
											</div>
											<div class="small-12 columns end">
												<input type="email" name="email" id="email" placeholder="<?php esc_html_e( 'Email', 'addressy' ); ?>" data-cip-id="email">
											</div>
										</div>
										<div class="fieldset row">
											<div class="small-12 columns">
												<label><?php esc_html_e( 'Password', 'addressy' ); ?></label>
											</div>
											<div class="small-12 columns end">
												<input type="password" name="password" id="password" placeholder="<?php esc_html_e( 'Password', 'addressy' ); ?>" data-cip-id="password">
											</div>                 
										</div>
										<div class="fieldset row">											
											<div class="small-12 columns">
												<button class="secure-container" id="btnLogIn" type="submit" form="formLogIn" value="Log in"><?php esc_html_e( 'Log in', 'addressy' ); ?></button>
											</div>                    
										</div> 
										<!-- <p><?php esc_html_e( 'If you need any help getting started with our WordPress extension, take a look at our', 'addressy' ); ?> <a href="https://addressy.com/support/wordpress-setup-guide/" target="_blank"><?php esc_html_e( 'handy setup guide', 'addressy' ); ?></a>.</p> -->              

									</form>									    
								</div>
								<div class="adrsy-box-container-footer">
									<div class="row">
										<div class="small-12 medium-6 columns">
											<a href="https://www.addressy.com/password/" class="adrsy-lnk-show-forgotten" target="_blank"><?php esc_html_e( 'Forgotten your password?', 'addressy' ); ?></a>
										</div>									
										<div class="small-12 medium-6 columns text-right">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=addressy_register' ) );?>"><?php esc_html_e( 'Register for free here', 'addressy' ); ?></a>
										</div>
									</div>
								</div>
							

						<?php endif; ?>
						
					</div>

					<div class="small-12 medium-3 columns postbox-container" id="postbox-container-1">
							

						<?php if ($pcaAccCode) { ?>	
							<div class="adrsy-box-container help-container text-center">
								<a href="https://www.addressy.com/support/wordpress-setup-guide/">
					                <div class="row">
					                    <div class="small-12 medium-5 large-12 columns">
					                        <img src="https://www.addressy.com/Content/Assets/Integrations/WordPress/wordpress-setup.png" alt="Setup Guide">
					                    </div>
					                    <div class="small-12 medium-6 large-12 columns">
					                        <h3>Need help?</h3>
					                        <p>Read our handy setup guide.</p>
					                    </div>
					                </div>	
				                </a>			                		                
				            </div>
						<?php } ?>

						<?php							
							/* do before settings page action */
							//do_action( 'addressy_settings_page_right_column' );	
						?>													
					</div><!-- // postbox-contaniner -->
					
				</div>
				<?php if ($pcaAccCode) { ?>	
				<div class="row">
					<div class="small-12 columns">
						<div class="adrsy-box-container small-12 columns">
							<div class="row guide-container">
								<div class="small-12 medium-6 columns">									
									<h3><?php esc_html_e( 'Your plan', 'addressy' ); ?></h3>
									<p><?php esc_html_e( sprintf( __( 'You\'re currently on the %1$s plan and get %2$s address credits every %3$s.', 'addressy' ), esc_html( $pcaCurrentPlanName ),	esc_html( $pcaCurrentPlanCredits ) . ($pcaCurrentPlanName == "Free" ? ' US' : ''), $pcaCurrentPlanPeriod )	); ?></p>
									<p><?php esc_html_e( sprintf( __( 'Your credits will be refreshed on %1$s.', 'addressy' ), date_format( date_create( sanitize_text_field ( $pcaCurrentPlanRefreshDate ) ), 'm/d/Y' ) ) ); ?></p>
									<p><?php esc_html_e( 'Need more credits?', 'addressy' ); ?> <a href="https://www.addressy.com/" target="_blank"><?php esc_html_e( 'Visit our website for information on our paid plans', 'addressy' ); ?></a>.</p>
								</div>

								<div class="small-12 medium-6 columns" >        
									<h3><?php esc_html_e( 'Additional forms', 'addressy' ); ?></h3>
									<p><?php esc_html_e( 'This extension covers the standard Wordpress and WooCommerce address forms.', 'addressy' ); ?></p>
									<p><?php esc_html_e( 'If you want to use your Addressy account to add Address Verification to other pages,', 'addressy' ); ?> <a href="https://www.addressy.com/" target="_blank"><?php esc_html_e( 'log in to your account at Addressy.com', 'addressy' ); ?></a></p>
								</div>
							</div>
						</div>
					</div>	
				</div>
				<?php } ?>	
			</div>		
		</div>
	</div>



	<script type="text/javascript">

		(function($){
			
			$('#formSettings').on('submit', function(){
				$('#btnSave').addClass('working');                      
				$.ajax({
					type: 'POST',
					url: ajaxurl, 
					showLoader: true,                  
					data: { 
						"action": 'addressy_save_settings',
						"account_code": "<?php echo esc_html( $pcaAccCode ); ?>",
						"account_token": "<?php echo esc_html( $pcaToken ); ?>",
						"daily_limit": dailyLimitValues[$('#dailyLimit').val()],
						"user_limit": individualIpLimitValues[$('#limitPerUser').val()],
						"license_key": "<?php echo esc_html( $pcaLicenseKey );?>",
						"url_restrictions": $('#limitbyurl').val().replace(/\r/g,"").split("\n"),
						"field_mappings": $('#fieldmappings').val(),
						"custom_javascript": $('#customjavascript').val()
					}
				})
				.done(function(result){
					$('#btnSave').addClass('working');   
					$.ajax({
						type: 'POST',
						url: 'https://app_api.pcapredict.com/api/setlicensedetails',
						processData: false,
						headers: {
							'Content-Type': 'application/json',
							'Authorization': 'Basic ' + btoa('<?php echo esc_html( $pcaAccCode ); ?>:<?php echo esc_html( $pcaToken ); ?>')
						},
						data: JSON.stringify({
							"Key": "<?php echo esc_html( $pcaLicenseKey ); ?>",
							"DailyLimit": dailyLimitValues[$('#dailyLimit').val()],
							"UserLimit": individualIpLimitValues[$('#limitPerUser').val()],
							"UrlRestrictions": $('#limitbyurl').val().replace(/\r\n/g,"\n").split("\n")
						})
					})
					.done(function(result){
						$('.addressy-message')
							.text('<?php esc_html_e( 'Your settings were saved.', 'addressy' ); ?>')
							.addClass('addressy-message-success')
							.slideDown(500, function(){
								hideAddressyMessage(5000, 500);
							});
						//window.location.reload(true);
					})
					.fail(function(){
						$('.addressy-message')
							.text('<?php esc_html_e( 'Sorry, there was a problem updating the settings on the Addressy servers.', 'addressy' ); ?>')
							.addClass('addressy-message-error')
							.slideDown(500, function(){
								hideAddressyMessage(5000, 500);
							});
					})
					.always(function(){
						$('#btnSave').removeClass('working'); 
					});                        
				})
				.fail(function(result){
					$('.addressy-message')
						.text('<?php esc_html_e( 'Sorry, there was a problem saving the settings.', 'addressy' ); ?>')
						.addClass('addressy-message-error')
						.slideDown(500, function(){
							hideAddressyMessage(5000, 500);
						});
				})
				.always(function(){
				});

					
			});

			$('#formLogIn').on('submit', function(){ 
				$('#btnLogIn').addClass('working');           
				$.ajax({
					showLoader: true,
					type: 'POST',
					url: 'https://app_api.pcapredict.com/api/AuthToken',
					processData: false,
					contentType: 'application/json',
					data: JSON.stringify({ 
						"email": $('#email').val(), 
						"password": $('#password').val(), 
						"deviceDescription": "", 
						"devicePushId": "", 
						"deviceType": 0, 
						"brand": "Addressy" 
					})
				})
				.done(function(result){
					if(console && console.log) console.log(result);
					var ac = '';
					for(var a in result.accounts) {
						ac = a;
					}
					var token = result.token.token;
					if (ac) {
						var auth = btoa(ac + ':' + token);
						$.ajax({
							showLoader: true,
							type: 'POST',
							url: 'https://app_api.pcapredict.com/api/license',
							processData: false,
							headers: {
								'Content-Type': 'application/json',
								'Authorization': 'Basic ' + auth
							},
							data: JSON.stringify({ 
								"KeyName": window.location.hostname, 
								"BlockCreate": true,
								"IntegrationType": "WordPress"
							})
						})
						.done(function(result){
							$.ajax({
								type: 'POST',
								url: ajaxurl,                 
								data: { 
									"action": 'addressy_save_settings',
									"account_code": ac,
									"account_token": token,
									"daily_limit": (result.dailyLimit),
									"user_limit": result.userLimit,
									"license_key": result.licenceKey,
									"url_restrictions": result.urlRestrictions
								}
							})
							.done(function(result){
								if(result.success) {

								}
								else {
									$('.addressy-message')
										.text('<?php esc_html_e( 'Sorry, there was a problem saving your login data', 'addressy' ); ?>')
										.addClass('addressy-message-error')
										.slideDown(500, function(){
											hideAddressyMessage(5000, 500);
										});
								}                                                                
							})
							.fail(function(result){
								$('.addressy-message')
									.text('<?php esc_html_e( 'Sorry, there was a problem saving your login data', 'addressy' ); ?>')
									.addClass('addressy-message-error')
									.slideDown(500, function(){
										hideAddressyMessage(5000, 500);
									});
							})
							.always(function(){
								window.location.reload(true);
							});
						})
						.fail(function(result){
							$('.addressy-message')
								.text('<?php esc_html_e( 'Sorry, there was a problem creating your licence. Please email support@addressy.com', 'addressy' ); ?>')
								.addClass('addressy-message-error')
								.slideDown(500, function(){
									hideAddressyMessage(5000, 500);
								});
						});
					}
				})
				.fail(function(result){
					$('#btnLogIn').removeClass('working'); 
					$('#email').val("");
					$('#password').val("");
					$('.addressy-message')
						.text('<?php esc_html_e( 'Sorry, your email address or password was not recognized. Please try again.', 'addressy' ); ?>')
						.addClass('addressy-message-error')
						.slideDown(500, function(){
							hideAddressyMessage(5000, 500);
						});

				})
				.always(function(){
					
				});
			});


			$('#formLogOut').on('submit', function() {
				$('#btnLogOut').addClass('working');  
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					dataType: 'JSON',
					data: { 
						"action": 'addressy_logout'
					}
				})
				.done(function(result){
					if(console && console.log) console.log(result);
					window.location.reload(true);
				})
				.fail(function(result){
					$('.addressy-message')
						.text('<?php esc_html_e( 'Sorry, there was a problem logging out from your Addressy account.', 'addressy' ); ?>')
						.addClass('addressy-message-error')
						.slideDown(500, function(){
							hideAddressyMessage(5000, 500);
						});
				})
				.always(function(){
					hideAddressyMessage(5000, 500);
				});
			});

			$('#formRegister').on('click', function() {
				console.log('register!');
			});


			$('.adrsy-lnk-show-login').on('click', function() {
				adrsyShowLogin();
			});
			
			$('.licence .settings').on('click', function() {
				$(this).toggleClass('selected');
				if ($(this).hasClass('selected')) {
					$('.settings-container').slideDown(500, function(){
						$('.settings-container').addClass('show');
					});
					
				} else {
					$('.settings-container').slideUp(500, function(){
						$('.settings-container').removeClass('show');
					});
				}
				
			});

			var hideAddressyMessage = function(delayMs, animateTime) {
				delayMs = delayMs || 0;
				animateTime = animateTime || 0;
				setTimeout(function(){
					$('.addressy-message').fadeOut(animateTime);
				}, delayMs);
			}

			var adrsyShowLogin = function(){
				$('.adrsy-box-container').css('display', 'none');
				$('.login-container').css('display', 'block');
			}

		})(jQuery);

	</script>
	
	<?php
	
	/* do after settings page action */
	do_action( 'addressy_after_settings_page' );
	
}



/**
 * function addressy_settings_page_cta()
 * adds intro text on the settings page
 */
function addressy_settings_page_cta() {

}
add_action( 'addressy_before_settings_page', 'addressy_settings_page_cta', 10 );



/**
 *
 */
function addressy_settings_page_ctas() {
	
	/* get this plugins data - such as version, author etc. */
	$data = get_plugin_data(
		ADDRESSY_LOCATION . '/addressy.php',
		false // no markup in return
	);
	?>
	
	<div class="adrsy-box-container">

		<h3><?php esc_html_e( 'Plugin Info', 'addressy' ); ?></h3>
		<p class="plugin-info">
			<?php esc_html_e( 'Version: ', 'addressy' ); echo esc_html( $data[ 'Version' ] ) ?><br />
			<?php esc_html_e( 'Written by:', 'addressy' ); ?> <a href="<?php echo esc_url( $data[ 'AuthorURI' ] ); ?>"><?php echo esc_html( $data[ 'AuthorName' ] ); ?></a><br />
			<?php esc_html_e( 'Website:', 'addressy' ); ?> <a href="https://www.addressy.com/integrations/wordpress-address-verification">Addressy</a>
		</p>
		<p>
			<?php esc_html_e( 'If you find this plugin useful then please', 'addressy' ); ?> <a href="https://wordpress.org/support/view/plugin-reviews/addressy-address-verification/"><?php esc_html_e( 'rate it on the plugin repository', 'addressy' ); ?></a>.
		</p>

	</div>
	
	<?php		
}
add_action( 'addressy_settings_page_right_column', 'addressy_settings_page_ctas' );


function addressy_register_page_content() {
	
	?>
	<div class="wrap addressy-container addressy-register">
		
			
		<div class="row">
			<div class="small-12 columns no-padding">
				<div class="addressy-header">
					<div class="row">
						<div class="small-6 medium-9 columns">
							<img src="https://www.addressy.com/Content/Assets/Logos/Addressy-logo-dark-raspberry.png" alt="<?php esc_html_e( 'Addressy Settings', 'addressy' ); ?>" class="addressy-logo" />
						</div>
						<div class="small-6 medium-3 columns">
							<?php if ($pcaAccCode) : ?>
								<form id="formLogOut" onsubmit="return false;">
									<button id="btnLogOut" type="submit" form="formLogOut" value="Log out" class=""><?php esc_html_e( 'Log out', 'addressy' ); ?></button>
								</form>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>			
		
		<div class="row">

			<div class="small-12 columns">
				<div class="addressy-message"></div>
			</div>					
			
			<div id="post-body" class="small-12 columns">
				
				<div class="row">					
					
					<div class="small-12 medium-8 medium-offset-2 columns postbox-container" id="postbox-container-2">
						<div class="small-12 columns">
							<h3 class="text-center"><?php esc_html_e( 'Sign up for free', 'addressy' ); ?></h3>											
						</div>
						<div class="adrsy-box-container login-container" style="display: block;">
								
							<form id="formRegister" onsubmit="return false;">
								
								<div class="fieldset row">
									<div class="small-12 columns">
										<label><?php esc_html_e( 'Email address', 'addressy' ); ?></label>
									</div>
									<div class="small-12  columns end">
										<input type="email" name="email" id="email" placeholder="<?php esc_html_e( 'Email address', 'addressy' ); ?>" data-cip-id="email">
									</div>
								</div>
								<div class="fieldset row">
									<div class="small-12 columns">
										<label><?php esc_html_e( 'Password', 'addressy' ); ?></label>
									</div>
									<div class="small-12 columns end">
										<input type="password" name="password" id="password" placeholder="<?php esc_html_e( 'Password', 'addressy' ); ?>" data-cip-id="password">
									</div>                 
								</div>
								<div class="fieldset row">
									<div class="small-12 columns">
										<label><?php esc_html_e( 'Confirm Password', 'addressy' ); ?></label>
									</div>
									<div class="small-12 columns end">
										<input type="password" name="passwordConfirmed" id="passwordConfirmed" placeholder="<?php esc_html_e( 'Retype Password', 'addressy' ); ?>" data-cip-id="password">
									</div>                 
								</div>
								<p><?php esc_html_e( 'By signing up for an account, you agree to our ', 'addressy' ); ?> <a href="https://www.addressy.com/legals"><?php esc_html_e( 'terms and conditions', 'addressy' ); ?></a>. </p>
								<div class="fieldset row">
									<div class="small-12 columns">
										<button class="secure-container" id="btnRegister" type="submit" form="formRegister" value="Register"><?php esc_html_e( 'Sign up', 'addressy' ); ?></button>
									</div>                    
								</div>
							</form>
							
						</div>
						<div class="adrsy-box-container-footer">
							<div class="row">
								<div class="small-12 columns">
									<?php esc_html_e( 'Already have an account? ', 'addressy' ); ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=addressy_settings' ) );?>"><?php esc_html_e( 'Log in here', 'addressy' ); ?></a>
								</div>
							</div>
						</div>    
					</div>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">

		(function($){
			$('#formRegister').on('submit', function(){ 
				
				//Validation
				var emailRegEx = /\S+@\S+\.\S+/;
				var pwRegex = new RegExp("^(?=.*?[a-zA-z])(?=.*?[0-9#?!@@$%^&-*¬\"£\(\)_\+\=`¦\\\]\[;\'\,\.\/\{\}\:\~\<\>\|]).{6,}$");

            	var email = $('#email').val();
				var pw = $('#password').val();
				var msg = "";

				if(!(email.length > 0 && emailRegEx.test(email))){
					msg = "<?php _e( 'Please check your email address has been entered correctly', 'addressy' ); ?>";
				} else if(!pwRegex.test(pw)){					
					msg = "<?php _e( 'Please enter a password that is 6 or more characters with at least one letter & one number or one symbol.', 'addressy' ); ?>";
				} else if(pw != $('#passwordConfirmed').val() ){					
					msg = "<?php _e( 'Your passwords do not match.', 'addressy' ); ?>";
				}
					
				if(msg != "")
				{
					$('.addressy-message')
						.text(msg)
						.addClass('addressy-message-error')
						.slideDown(500, function(){
							hideAddressyMessage(5000, 500);
						});
				}
				else
				{
					$('#btnRegister').addClass('working');       
					
					$.ajax({
						showLoader: true,
						type: 'POST',
						url: 'https://app_api.pcapredict.com/api/RegisterAccount',
						processData: false,
						contentType: 'application/json',
						data: JSON.stringify({ 
							"email": email, 
							"password": pw, 
							"brand": "Addressy" 
						})
					})
					.done(function(result){
						if(console && console.log) console.log(result);
						
						if ( result.hasOwnProperty("accountCode" ) ) {
							
							$.ajax({
								showLoader: true,
								type: 'POST',
								url: 'https://app_api.pcapredict.com/api/AuthToken',
								processData: false,
								contentType: 'application/json',
								data: JSON.stringify({ 
									"email": email, 
									"password": pw, 
									"deviceDescription": "", 
									"devicePushId": "", 
									"deviceType": 0, 
									"brand": "Addressy" 
								})
							})
							.done(function(result){
								if(console && console.log) console.log(result);
								var ac = '';
								for(var a in result.accounts) {
									ac = a;
								}
								var token = result.token.token;
								if (ac) {									
																	
									setTimeout(createLicenceAndRedirect(ac,token), 10);
								}
								else
								{
									$('#btnRegister').removeClass('working');
									$('.addressy-message')
										.text('<?php esc_html_e( 'Sorry, there was a problem connecting to your account', 'addressy' ); ?>')
										.addClass('addressy-message-error')
										.slideDown(500, function(){
										hideAddressyMessage(5000, 500);
									});
								}
							})
							.fail(function(result){
								$('#btnRegister').removeClass('working');
								$('.addressy-message')
									.text('<?php esc_html_e( 'Sorry, there was a problem connecting to your account', 'addressy' ); ?>')
									.addClass('addressy-message-error')
									.slideDown(500, function(){
									hideAddressyMessage(5000, 500);
								});
							});				
						}
						else
						{
							$('#btnRegister').removeClass('working');
							$('.addressy-message')
								.text('<?php esc_html_e( 'Sorry, there was a problem creating your account', 'addressy' ); ?>')
								.addClass('addressy-message-error')
								.slideDown(500, function(){
								hideAddressyMessage(5000, 500);
							});
						}
					})
					.fail(function(result){
						$('#btnRegister').removeClass('working');
						$('.addressy-message')
							.text('<?php esc_html_e( 'Sorry, there was a problem creating your account', 'addressy' ); ?>')
							.addClass('addressy-message-error')
							.slideDown(500, function(){
							hideAddressyMessage(5000, 500);
						});
					})
					
				}
			});

			var hideAddressyMessage = function(delayMs, animateTime) {
				delayMs = delayMs || 0;
				animateTime = animateTime || 0;
				setTimeout(function(){
					$('.addressy-message').fadeOut(animateTime);
				}, delayMs);
			}		

			function createLicenceAndRedirect(ac, token)			{
				
				var auth = btoa(ac + ':' + token);
				$.ajax({
					showLoader: true,
					type: 'POST',
					url: 'https://app_api.pcapredict.com/api/license',
					processData: false,
					headers: {
						'Content-Type': 'application/json',
						'Authorization': 'Basic ' + auth
					},
					data: JSON.stringify({ 
						"KeyName": window.location.hostname, 
						"BlockCreate": true,
						"IntegrationType": "WordPress"
					})
				})
				.done(function(result){					
					$.ajax({
						type: 'POST',
						url: ajaxurl,                 
						data: { 
							"action": 'addressy_save_settings',
							"account_code": ac,
							"account_token": token,
							"daily_limit": (result.dailyLimit),
							"user_limit": result.userLimit,
							"license_key": result.licenceKey,
							"url_restrictions": result.urlRestrictions							
						}
					})
					.done(function(result){
						window.location.replace("<?php echo esc_url( admin_url( 'admin.php?page=addressy_settings' ) );?>");															
					})
					.fail(function(result){
						$('#btnRegister').removeClass('working');
						$('.addressy-message')
							.text('<?php esc_html_e( 'Sorry, there was a problem saving your login data', 'addressy' ); ?>')
							.addClass('addressy-message-error')
							.slideDown(500, function(){
							hideAddressyMessage(5000, 500);
						});
					})
					
				})
				.fail(function(result){
					$('#btnRegister').removeClass('working');
						$('.addressy-message')
							.text('<?php _e( 'Sorry, there was a problem creating your licence. Please email support@addressy.com', 'addressy' ); ?>')
							.addClass('addressy-message-error')
							.slideDown(500, function(){
							hideAddressyMessage(5000, 500);
						});
					
				})
			}		

		})(jQuery);

	</script>

	<?php		
}