<?php
/**
 * Module of qp JS reader library.
 *
 * @package qbeats for WordPress
 */

/**
 * Include application settings
 */
require_once( 'settings-app.php' );

/**
 * Functions to load and initialize qp JS reader library
 */
class QB_AngularJS {
	/**
	 * Prepare loading of qp JS Library
	 */
	public function __construct() {
		add_action( 'wp_footer', array( $this, 'insert_snippet' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'insert_styles' ) );
	}

	/**
	 * Add pay window snippet code
	 */
	function insert_snippet() {
		global $qb_auth;
		echo '<script type="text/javascript">
			(function(d,s,id) {
				if(d.getElementById(id)){return;}
				var proto="http"+(("https:"===d.location.protocol)?"s":""),
					src=proto+":' . esc_url( '//' . QbeatsApplicationSettings::QBEATS_LIBRARY_URL ) . 'pay-window-bootstrap.js",
					e=d.createElement(s);
				e.id=id;e.src=src;e.type="text/javascript";e.async=true;
				e.setAttribute("data-app-id","' . esc_attr( $qb_auth->get_client_id() ) . '");
				d.body.appendChild(e);
			})(document,"script","pay-window-bootstrap");
		</script>';
	}

	/**
	 * Add pay window styles
	 */
	function insert_styles() {
	    wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css' );
	    wp_enqueue_style( 'qb-kit-ui', plugin_dir_url( __FILE__ ) . 'static/css/qb-kit.css' );
	}
}
