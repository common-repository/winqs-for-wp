<?php
/**
 * Publishing tool module.
 *
 * @package qbeats for WordPress
 */

/**
 * Include application settings
 */
require_once( 'settings-app.php' );
/**
 * Include qbeats story wrapper module
 */
require_once( 'qb-story.php' );
/**
 * Include qbeats puller
 */
if ( file_exists( PLUGIN_DIR_PATH . 'qb-stories-puller.php' ) && defined( 'PULLER_ENABLED' ) ) {
	require_once( 'qb-stories-puller.php' );
}

/**
 * Functions related to publishing tool
 */
class QB_PublishingTool {

	/**
	 * Construct and initialize publishing tool module
	 */
	public function __construct() {
		add_action( 'load-post.php', array( $this, 'post_meta_boxes_setup' ) );
		add_action( 'load-post-new.php', array( $this, 'post_meta_boxes_setup' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'transition_post_status', array( $this, 'story_status_transitions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'admin_error_notice' ) );
	}

	/**
	 * Setup add post meta boxes
	 */
	public function post_meta_boxes_setup() {
		add_action( 'add_meta_boxes', array( $this, 'add_post_meta_boxes' ) );
	}

	/**
	 * Show publishing error
	 */
	public function admin_error_notice() {
		global $post;
		if ( isset( $post ) ) {
			$story = new QB_Story( $post->ID );
			if ( $story->publish_error ) {
				$message = 'Story was not published with qbeats. Please complete title and body and try again.';
				echo '<div class=\'error\'><p>' . esc_attr( $message ) . '</p></div>';
			}
		}
	}

	/**
	 * Loads JS scripts for publishing tool page
	 */
	public function enqueue_admin_scripts() {
		global $qb_auth;
		wp_enqueue_script( 'angular-core', plugin_dir_url( __FILE__ ) . 'static/js/angular.min.js', array( 'jquery' ), null, false );
		$proto = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'http': 'https' );
		$qb_kit_js = $proto . '://' . QbeatsApplicationSettings::QBEATS_LIBRARY_URL . 'pay-window-bootstrap.js';
		wp_enqueue_script( 'qb-kit', $qb_kit_js, array( 'backbone' ), ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : QB_LIBRARY_VERSION ), true );
		wp_localize_script(
			'qb-kit',
			'CONFIG',
			array(
				'client_id'    => $qb_auth->get_client_id(),
				'analyze_url' => '',
				'stats_url' => '',
				'story_base' => QbeatsApplicationSettings::QBEATS_STORY_BASE_URL,
			)
		);
		wp_enqueue_script( 'angular-admin-app', plugin_dir_url( __FILE__ ) . 'static/js/angular-admin-app.js', array( 'angular-core', 'qb-kit' ), null, true );
		wp_enqueue_script( 'sidebar-adapter', plugin_dir_url( __FILE__ ) . 'static/js/sidebar-adapter.js', array( 'jquery', 'qb-kit' ), null, true );

		wp_enqueue_style( 'selectize', plugin_dir_url( __FILE__ ) . 'static/css/selectize.default.css' );
		wp_enqueue_style( 'bootstrap-grid-system', plugin_dir_url( __FILE__ ) . 'static/css/bootstrap.css' );
		wp_enqueue_style( 'qb-wp-style', plugin_dir_url( __FILE__ ) . 'static/css/qb-kit.css' );
	}

	/**
	 * Adds post meta boxes
	 */
	public function add_post_meta_boxes() {
		add_meta_box(
			'qb-sidebar-panel',                      // Unique ID.
			'Publish with qbeats',                   // Title.
			array( $this, 'sidebar_class_meta_box' ),// Callback function.
			'post',                                  // Admin page (or post type).
			'side',                                  // Context.
			'default'                                // Priority.
		);
	}

	/**
	 * Handler for story status transitions
	 *
	 * @param string $new_status value of new post status.
	 * @param string $old_status value of old post status.
	 * @param object $post wp post.
	 */
	function story_status_transitions( $new_status, $old_status, $post ) {
		if ( 'draft' === $new_status || 'publish' === $new_status ) {
			$this->save_post( $post );
		}
		if ( 'publish' === $new_status ) {
			$this->check_for_wp_post_errors( $post );
		}
	}

	/**
	 * Saves qbeats meta data for the given post.
	 *
	 * @param object $post wp post.
	 * @param array  $post_data qbeats meta data.
	 *
	 * @return qbeats post object.
	 */
	function save_post( $post, $post_data = null ) {
		if ( empty( $post_data ) ) {
		    // This line is not passing WordPress code standard with message 'a non-sanitized input variable',
		    // skip code styling checking here because all the fields will be sanitized below.
		    // @codingStandardsIgnoreStart
			$post_data = &$_POST;
			// @codingStandardsIgnoreEnd
		}

		$post_id = $post->ID;

		if ( $this->is_post_form_safe() ) {
			return null;
		}

		$post_type = get_post_type_object( $post->post_type );

		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return null;
		}

		$story                     = new QB_Story( $post_id );
		$story->qb_publish_enabled = ( isset( $post_data['qb-publish-with-qbeats-enabled'] ) ? esc_attr( $post_data['qb-publish-with-qbeats-enabled'] ) : 0 );
		$story->pricing_enabled    = false;

		$story->draft_id           = ( isset( $post_data['qb-draft-id'] ) ? $post_data['qb-draft-id'] : 0 );
		$story->story_id           = ( isset( $post_data['qb-story-id'] ) ? $post_data['qb-story-id'] : 0 );

		$story->public_content     = ( isset( $post_data['qb-public-content'] ) ? $post_data['qb-public-content'] : '' );

		$story->allow_print        = ( isset( $post_data['qp-wp-story-drm-print'] ) ? esc_attr( $post_data['qp-wp-story-drm-print'] ) : 0 );
		$story->allow_copy         = ( isset( $post_data['qp-wp-story-drm-copy'] ) ? esc_attr( $post_data['qp-wp-story-drm-copy'] ) : 0 );

		return $story;
	}

	/**
	 * Check the given post for errors
	 *
	 * @param object $post wp post.
	 */
	public function check_for_wp_post_errors( $post ) {
		$story = new QB_Story( $post->ID );

		if ( strlen( $post->post_title ) === 0 ) {
			$story->publish_error = QBStoryError::$empty_title;
			return;
		}

		if ( strlen( $post->post_content ) === 0 ) {
			$story->publish_error = QBStoryError::$empty_content;
			return;
		}

		$story->publish_error = '';
	}

	/**
	 * Add qbeats sidebar panel.
	 *
	 * @param object $object unused.
	 */
	public function sidebar_class_meta_box( $object ) {
		wp_nonce_field( basename( __FILE__ ), 'qb_post_nonce' );
		require_once( 'qb-sidebar-tool.tpl.php' );
	}

	/**
	 * Check if post form is safe
	 *
	 * @return bool true if post form safe, else false.
	 */
	protected function is_post_form_safe() {
		return isset( $post_data['qb_post_nonce'] ) && wp_verify_nonce( esc_attr( $post_data['qb_post_nonce'] ), basename( __FILE__ ) );
	}
}
