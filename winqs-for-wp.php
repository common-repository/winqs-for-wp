<?php
/**
 * Main module of qbeats for WordPress plugin
 *
 * @package qbeats for WordPress
 */

/*
	Plugin Name: qbeats for WordPress
	Plugin URI: http://support.qbeats.com/wordpress
	Description: qbeats for WordPress by qbeats lets you turn more readers into more revenue.
	Version: 1.4
	Author: qbeats inc.
	Author URI: http://qbeats.com/
 */

// DO NOT REMOVE: Version of library that is compatible with plugin.
define( 'QB_LIBRARY_VERSION', '1.0.2' );
define( 'PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

require_once( PLUGIN_DIR_PATH . 'settings.php' );
require_once( 'qb-publishing-tool.php' );
require_once( 'qb-premium-post-filter.php' );
require_once( 'qb-protected-button.php' );
require_once( 'qb-angularjs.php' );
require_once( 'qb-drm.php' );

/**
 * Main class of qbeats for WordPress plugin
 */
class QbeatsPlugin {
	/**
	 * Constructor and initialization of qbeats for WordPress plugin
	 */
	public function __construct() {
		new QB_Settings();
		new QB_PublishingTool();
		new QB_PremiumPostFilter();
		new QB_AngularJS();
		new QB_DRM();

		// Register handlers for plugin activation/deactivation events.
		register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );
	}

	/**
	 * Activation plugin hook
	 */
	function activate_plugin() {
	}

	/**
	 * Deactivation plugin hook
	 */
	function deactivate_plugin() {
	}
}

/**
 * Init plugin
 */
function qb_init() {
	$plugin = new QbeatsPlugin();
}

add_action( 'init', 'qb_init' );
