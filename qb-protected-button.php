<?php
/**
 * Module for the editor's toolbar protected button.
 *
 * @package qbeats for WordPress
 */

/**
 * Functions related to protected content button
 */
class QB_ProtectedButtonFilter {
	/**
	 * Construct and initialize premium post filters.
	 */
	public function __construct() {
		add_filter( 'mce_external_plugins', array( $this, 'qbeats_add_buttons' ) );
		add_filter( 'mce_buttons',   array( $this, 'qbeats_register_buttons' ) );
	}

	/**
	 * Register wordpress editor buttons.
	 *
	 * @param array $buttons Array of the editor buttons.
	 *
	 * @return array $buttons Updated array of the editor buttons.
	 */
	function qbeats_register_buttons( $buttons ) {
		array_push( $buttons, 'qb-protected' );
		return $buttons;
	}

	/**
	 * Add script to display editor custom buttons.
	 *
	 * @param array $plugin_array Array of the editor plugins.
	 *
	 * @return array $plugin_array Updated array of the editor plugins.
	 */
	function qbeats_add_buttons( $plugin_array ) {
		$plugin_array['qbeats_editor_buttons'] = plugins_url( '/static/js/qbeats-editor-buttons.js', __FILE__ );
		return $plugin_array;
	}
}
