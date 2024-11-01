<?php
/**
 * Settings page module.
 *
 * @package qbeats for WordPress
 */

/**
 * Include application settings
 */
require_once( 'settings-app.php' );
/**
 * Include authentication with qbeats module
 */
require_once( 'qb-auth.php' );

/**
 * Powerup type constants
 */
class QB_PowerupType {
	/**
	 * Show qbeats icon on post title
	 *
	 * @var string
	 */
	static $short = 'title_icon';
	/**
	 * Show story price on post title
	 *
	 * @var string
	 */
	static $price = 'title_price';
	/**
	 * Show qbeats powerup panel on post
	 *
	 * @var string
	 */
	static $full = 'content_panel';
	/**
	 * Show qbeats mini paywindow on post
	 *
	 * @var string
	 */
	static $mini = 'title_mini';
}

/**
 * Pay Window size constants
 */
class QB_PayWindowSize {
	/**
	 * Display small Pay Window
	 *
	 * @var string
	 */
	static $small = 'small';
	/**
	 * Display regular Pay Window
	 *
	 * @var string
	 */
	static $medium = 'medium';
	/**
	 * Display large Pay Window
	 *
	 * @var string
	 */
	static $large = 'large';
}

/**
 * Price widget settings
 */
class QB_PriceWidget {
	/**
	 * Show trend arrow before the price
	 *
	 * @var boolean
	 */
	static $trend_arrow = true;
	/**
	 * Show trend in price colored digits
	 *
	 * @var boolean
	 */
	static $trend_in_price = false;
	/**
	 * Show QMV icon
	 *
	 * @var boolean
	 */
	static $qmv_icon = false;
}

/**
 * Functions for settings page
 */
class QB_Settings {

	const MENU_ITEM_POSITION = '80.157';

	/**
	 * Construct plugin settings module
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_plugin_menu' ) );
		add_action( 'admin_init', array( $this, 'plugin_admin_init' ) );
	}

	/**
	 * Initialize plugin settings module
	 */
	public function plugin_admin_init() {
		$image_url = plugins_url( 'static/img/qbeats-circle-orange-20px.png', __FILE__ );

		register_setting( 'plugin_options', 'plugin_options', array( $this, 'validate_plugin_settings' ) );
		add_settings_section( 'plugin_main', "<h2><span><img src='$image_url'/></span> qbeats for WordPress Settings</h2>", null, 'plugin' );
	}

	/**
	 * Validate plugin settings
	 *
	 * @param array $options plugin settings values array.
	 *
	 * @return array validated plugin settings values array.
	 */
	public function validate_plugin_settings( $options ) {
		global $qb_auth;
		$cleaned_options = array();
		if ( isset( $options['application_id'] ) && isset( $options['application_secret'] ) ) {
			$client_id = trim( $options['application_id'] );
			$client_secret = trim( $options['application_secret'] );
			if ( $qb_auth->validate_application_id( $client_id,  $client_secret ) ) {
				$cleaned_options['application_id'] = $client_id;
				$cleaned_options['application_secret'] = $client_secret;
				$qb_auth->set_application_options( $client_id, $client_secret );
			} else {
				$cleaned_options['application_id'] = '';
				$cleaned_options['application_secret'] = '';
				$qb_auth->mark_auth_error( 'wrong_id' );
			}
			unset( $client_id );
			unset( $client_secret );
		}

		$powerup_type = QB_PowerupType::$full;
		if ( isset( $options['powerup_type'] ) ) {
			$powerup_type = $options['powerup_type'];
		}
		$cleaned_options['powerup_type'] = $powerup_type;

		$paywindow_size = QB_PayWindowSize::$medium;
		if ( isset( $options['paywindow_size'] ) ) {
			$paywindow_size = $options['paywindow_size'];
		}
		$cleaned_options['paywindow_size'] = $paywindow_size;

		return $cleaned_options;
	}

	/**
	 * Get plugin settings page url
	 *
	 * @return string plugin settings page url.
	 */
	public static function plugin_page_url() {
		return plugin_basename( __DIR__ ) . '/settings.tpl.php';
	}

	/**
	 * Get plugin settings page link
	 *
	 * @return string plugin settings page link.
	 */
	public static function plugin_page_link() {
		return get_admin_url() . 'admin.php?page=' . self::plugin_page_url();
	}

	/**
	 * Process saving of plugin settings
	 */
	public function process_settings_save() {
		$updated = filter_input( INPUT_GET, 'settings-updated', FILTER_VALIDATE_BOOLEAN );
		if ( $updated ) {
			global $qb_auth;
			if ( ! $qb_auth->is_authorized() && $qb_auth->is_valid() ) {
				// Detect when user is not authorized and redirect.
				$url = $qb_auth->build_authorization_link( 'plugin' );
				wp_redirect( $url );
			}
		}
	}

	/**
	 * Register plugin setting menu
	 */
	public function register_plugin_menu() {
		$menu_item_icon_url = plugin_dir_url( __FILE__ ) . 'static/img/qbeats-circle-orange-20px.png';
		$menu_item_position = QB_Settings::MENU_ITEM_POSITION;
		add_menu_page( 'qbeats Plugin Options', 'qbeats for WordPress', 'manage_options', self::plugin_page_url(), '', $menu_item_icon_url, $menu_item_position );
		add_action( 'load-' . self::plugin_page_url(), array( $this, 'process_settings_save' ) );
	}
}
