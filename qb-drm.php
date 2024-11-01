<?php
/**
 * Module for DRM of qbeats stories.
 *
 * @package qbeats for WordPress
 */

/**
 * Include qbeats story wrapper module
 */
require_once( 'qb-story.php' );

/**
 * Functions enable DRM protection for qbeats stories
 */
class QB_DRM {
	/**
	 * Construct DRM module
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue JS module to protect DRM of qbeats stories
	 */
	function enqueue_scripts() {
		if ( is_single() && ! is_admin() ) {
			global $post;
			$story = new QB_Story( $post->ID );
			if ( $story && $story->is_protected_by_qbeats() ) {
				if ( ! $story->allow_copy || ! $story->allow_print ) {
					wp_enqueue_script( 'qb-drm', plugin_dir_url( __FILE__ ) . 'static/js/drm.js', array( 'jquery' ), null, false );
					wp_localize_script(
						'qb-drm',
						'DRM_DATA',
						array(
							'allow_print' => $story->allow_print,
							'allow_copy' => $story->allow_copy,
						)
					);
				}

				if ( ! $story->allow_print ) {
					wp_enqueue_style( 'qb-print', plugin_dir_url( __FILE__ ) . 'static/css/print.css' );
				}
			}
		}
	}
}
