<?php
/**
 * Template of setting page module.
 *
 * @package qbeats for WordPress
 */

/**
 * Include application settings
 */
require_once( 'settings-app.php' );

	$options = get_option( 'plugin_options' );
	$application_id = isset( $options['application_id'] ) ? $options['application_id'] : '';
	$application_secret = isset( $options['application_secret'] ) ? $options['application_secret'] : '';

	$powerup_type = QB_PowerupType::$full;
if ( isset( $options['powerup_type'] ) ) {
	$powerup_type = $options['powerup_type'];
}

	$paywindow_size = QB_PayWindowSize::$medium;
if ( isset( $options['paywindow_size'] ) ) {
	$paywindow_size = $options['paywindow_size'];
}

	$icon_img_src = plugins_url( 'static/img/premium-icon.svg', __FILE__ );
	$price_img_src = plugins_url( 'static/img/premium-price.svg', __FILE__ );
	$buttons_img_src = plugins_url( 'static/img/premium-buttons.svg', __FILE__ );
?>
<div class="wrap">
<div>
	<form action="options.php" method="post">
		<?php settings_fields( 'plugin_options' ); ?>
		<?php do_settings_sections( 'plugin' ); ?>
		<br>
		<div class="qb-horizontal-container">
			<div class="qb-setting-container">
				<div class="qb-settings-item-container qb-application-id-container">
					<strong>qbeats Application</strong>
					<div class="qb-setting-content-container">
						<div class="qb-table-row">
							<span class="qb-cell-title"><label for="plugin_application_id">Application ID</label></span>
							<input class="qb-app-id-text qb-cell-content" id='plugin_application_id' type='text'
							name='plugin_options[application_id]' value='<?php echo esc_attr( $application_id ); ?>'
							/>
						</div>
						<div class="qb-table-row">
							<span class="qb-cell-title"><label for="plugin_application_secret">Application Secret</label></span>
							<input class="qb-app-id-text qb-cell-content" id='plugin_application_secret' type='text'
							   name='plugin_options[application_secret]' value='<?php echo esc_attr( $application_secret ); ?>'
							/>
						</div>
						<div class="qb-table-row">
						</div>
					</div>
					<i>
						<?php if ( $qb_auth->is_plugin_authorized() ) { ?>
							Connected on <?php echo esc_attr( date( get_option( 'date_format' ), $qb_auth->get_last_connected() ) ); ?>
						<?php } else { ?>
							Create a unique Application ID and Application Secret in your
							<a href='<?php echo esc_url( QbeatsApplicationSettings::QBEATS_PORTAL_URL ); ?>' target='_blank'>
								qbeats Account Settings
							</a>
						<?php }; ?>
					</i>

				</div>
				<br><br>
				<div class="qb-settings-item-container qb-premium-stories-container">
					<strong>Premium Stories</strong>
					<p>Choose a style for the premium content on your homepage and the story list page:</p>
					<div class="qb-setting-content-container">
						<div class="grid-cell col-label">
							<input type="radio" name="plugin_options[powerup_type]" id="full" value="<?php echo esc_attr( QB_PowerupType::$full ) ?>"
								<?php checked( $powerup_type, QB_PowerupType::$full, true ); ?> >
							<label for="full">Show full size PayWindow</label>
						</div>
						<div class="grid-cell col-image">
							<img class="qb-premium-stories-image" src="<?php echo esc_url( $buttons_img_src ) ?>" >
						</div>
						<div class="grid-cell col-label">
							<input type="radio" name="plugin_options[powerup_type]" id="mini" value="<?php echo esc_attr( QB_PowerupType::$mini ) ?>"
								<?php checked( $powerup_type, QB_PowerupType::$mini, true ); ?> >
							<label for="mini">Show mini PayWindow</label>
						</div>
						<div class="grid-cell col-image">
							<img class="qb-premium-stories-image" src="<?php echo esc_url( $price_img_src ) ?>" >
						</div>
						<div class="grid-multi-cell">
							<div class="grid-cell">
								<input type="radio" name="plugin_options[paywindow_size]" id="small" value="<?php echo esc_attr( QB_PayWindowSize::$small ) ?>"
									<?php checked( $paywindow_size, QB_PayWindowSize::$small, true ); ?>
								<label for="small">Small</label>
							</div>
							<div class="grid-cell">
								<input type="radio" name="plugin_options[paywindow_size]" id="medium" value="<?php echo esc_attr( QB_PayWindowSize::$medium ) ?>"
									<?php checked( $paywindow_size, QB_PayWindowSize::$medium, true ); ?>
								<label for="medium">Regular</label>
							</div>
							<div class="grid-cell">
								<input type="radio" name="plugin_options[paywindow_size]" id="large" value="<?php echo esc_attr( QB_PayWindowSize::$large ) ?>"
									<?php checked( $paywindow_size, QB_PayWindowSize::$large, true ); ?>
								<label for="large">Large</label>
							</div>
						</div>
						<div class="grid-cell col-label">
							<input type="radio" name="plugin_options[powerup_type]" id="price" value="<?php echo esc_attr( QB_PowerupType::$price ) ?>"
								<?php checked( $powerup_type, QB_PowerupType::$price, true ); ?> >
							<label for="price">Show a price label</label>
						</div>
						<div class="grid-cell col-image">
							<img class="qb-premium-stories-image" src="<?php echo esc_url( $price_img_src ) ?>" >
						</div>
						<div class="grid-cell col-label">
							<input type="radio" name="plugin_options[powerup_type]" id="short" value="<?php echo esc_attr( QB_PowerupType::$short ) ?>"
								<?php checked( $powerup_type, QB_PowerupType::$short, true ); ?> >
							<label for="short">Show a premium icon</label>
						</div>
						<div class="grid-cell col-image">
							<img class="qb-premium-stories-image" src="<?php echo esc_url( $icon_img_src ) ?>" >
						</div>
					</div>
				</div>
				<br><br>
				<div class="row">
					<div class="col-md-12 ">
						<?php submit_button(); ?>
					</div>
				</div>
			</div>
			<div class="qb-about-container">
				<p class="qb-about-title">About qbeats</p>
				<p> qbeats is making information more valuable by valuing information.
					Enable readers to pay a fair price for your stories with more focus than banner ads.
				</p>
			</div>
		</div>

	</form>
</div>
</div>
