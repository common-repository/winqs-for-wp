<?php
/**
 * Module for authentication with qbeats
 *
 * @package qbeats for WordPress
 */

/**
 * Include application settings
 */
require_once( 'settings-app.php' );

require_once( 'qb-log.php' );

/**
 * Functions related to authentication a user and a blog owner to qbeats
 */
class QB_Auth {
	/**
	 * Cached client id from settings.
	 *
	 * @var string
	 */
	private $client_id = '';

	/**
	 * Cached client secret from settings.
	 *
	 * @var string
	 */
	private $client_secret = '';

	const STATE_OPTION_NAME = 'jizou_auth_state';

	const AUTH_TOKEN_OPTION_NAME = 'jizou_auth_token';

	const REFRESH_TOKEN_OPTION_NAME = 'jizou_refresh_token';

	const AUTH_STATUS = 'jizou_auth_status';

	const AUTH_CONNECTED = 'jizou_connected_on';

	/**
	 * Construct and initialize authentication module
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_redirects' ) );
		add_action( 'admin_notices', array( $this, 'display_status_message' ) );
		$options = get_option( 'plugin_options' );
		if ( $options && isset( $options['application_id'] ) && isset( $options['application_secret'] ) ) {
			$this->set_application_options( $options['application_id'], $options['application_secret'], false );
		}
		unset( $options );
		// Schedule token refreshing job.
		if ( ! wp_next_scheduled( 'refresh_token_task_hook' ) ) {
			wp_schedule_event( time(), 'hourly', 'refresh_token_task_hook' );
		}
		add_action( 'refresh_token_task_hook', array( $this, 'refresh_token_task_handler' ) );
	}

	/**
	 * Starts refreshing process for token
	 */
	function refresh_token_task_handler() {
		$this->refresh_token();
	}

	/**
	 * Cancels refreshing process for token
	 */
	function _deactivate() {
		wp_clear_scheduled_hook( 'refresh_token_task_hook' );
	}

	/**
	 * Set appplicatrion id and secret
	 *
	 * @param string $client_id client id value.
	 * @param string $client_secret client secret value.
	 * @param bool   $reset_auth reset current authentication flag.
	 */
	public function set_application_options( $client_id, $client_secret, $reset_auth = true ) {
		if ( $client_id === $this->client_id  && $client_secret === $this->client_secret ) {
			return;
		}
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		// Token will not be valid with new credentials, so reset it
		// but only if it was changed.
		if ( $reset_auth ) {
			$this->reset_authorization();
		}
	}

	/**
	 * Resets authorization state (e.g when application id changes)
	 */
	private function reset_authorization() {
		// If application id changes, we should reset persistent tokens.
		if ( get_option( $this::AUTH_TOKEN_OPTION_NAME ) ) {
			delete_option( $this::AUTH_TOKEN_OPTION_NAME );
			delete_option( $this::REFRESH_TOKEN_OPTION_NAME );
		}
	}

	/**
	 * Validate given app id
	 *
	 * @param string $client_id value of an app id.
	 * @param string $client_secret value of an app secret.
	 *
	 * @return bool true if application id is valid, else false.
	 */
	public function validate_application_id( $client_id, $client_secret ) {
		if ( ! $client_id || ! $client_secret ) {
			return false;
		}
		$request_args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
			),
			'body' => array(
				'grant_type' => 'client_credentials',
				'scope' => 'validate',
			),
			'method' => 'POST',
		);
		$response = wp_remote_post( QbeatsApplicationSettings::OAUTH_TOKEN_URL, $request_args );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$response_body = json_decode( $response['body'], true );
		if ( array_key_exists( 'error', $response_body ) ) {
			// In case of invalid client id or client secret, server returns invalid_client error.
			return 'invalid_client' !== $response_body['error'];
		} else {
			return true;
		}
	}

	/**
	 * Handle all the auth redirects
	 */
	public function handle_redirects() {
		if ( isset( $_GET['code'] ) ) {
			$auth_code = ( isset( $_GET['code'] ) ? filter_input( INPUT_GET, 'code', FILTER_SANITIZE_STRING ) : '' );
			$state = ( isset( $_GET['state'] ) ? filter_input( INPUT_GET, 'state', FILTER_SANITIZE_STRING ) : '' );
			$redirect_url = $this->retrieve_token( $auth_code, $state );
			wp_redirect( $redirect_url );
			exit;
		}

		if ( isset( $_GET['state'] ) ) {
			$redirect_url = $this->process_auth_error();
			wp_redirect( $redirect_url );
			exit;
		}

		if ( isset( $_GET['qbaction'] ) ) {
			$redirect_url = $this->create_action_links( filter_input( INPUT_GET, 'qbaction', FILTER_SANITIZE_STRING ) );
			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Set auth error flag
	 *
	 * @param string $error_code OAuth error code.
	 */
	public function mark_auth_error( $error_code ) {
		update_option( $this::AUTH_STATUS, $error_code );
		$this->reset_authorization();
	}

	/**
	 * Set auth success status (doesn't reset authorization)
	 */
	public function mark_auth_success() {
		update_option( $this::AUTH_STATUS, 'success' );
	}

	/**
	 * Display auth status if it exists
	 */
	public function display_status_message() {
		$status = get_option( $this::AUTH_STATUS, 'unset' );
		if ( 'unset' !== $status ) {
			delete_option( $this::AUTH_STATUS );
			switch ( $status ) {
				case 'success':
					$class = 'updated';
					$message = 'qbeats for WordPress were successfully connected';
					break;
				case 'wrong_user':
					$class = 'error';
					$message = 'qbeats user is not authorized to use the specified application id.';
					break;
				default:
					$message = 'The Application ID or Application Secret is not valid';
					$class = 'error';
			}
			echo '<div id=\'message\' class=\'' . esc_attr( $class ) . '\'><p>' . esc_attr( $message ) . '</p></div>';
		}
	}

	/**
	 * Check if user is authorized
	 */
	public function is_authorized() {
		return is_user_logged_in() && get_option( $this::AUTH_TOKEN_OPTION_NAME );
	}

	/**
	 * Check if admin has authorized plugin
	 */
	public function is_plugin_authorized() {
		$auth_token = get_option( $this::AUTH_TOKEN_OPTION_NAME, false );
		$auth_refresh_token = get_option( $this::REFRESH_TOKEN_OPTION_NAME, false );
		return $auth_refresh_token && $auth_token && $this->is_valid();
	}

	/**
	 * Return client id
	 */
	public function get_client_id() {
		return $this->client_id;
	}

	/**
	 * Check client id and client secret for validity
	 */
	public function is_valid() {
		return '' !== $this->client_secret  && '' !== $this->client_id;
	}

	/**
	 * Get time of last connection
	 */
	public function get_last_connected() {
		return get_option( $this::AUTH_CONNECTED );
	}

	/**
	 * Perform protected request to qbeats for the given url
	 *
	 * @param string $url request url.
	 * @param array  $args request arguments.
	 * @param string $method request method name.
	 * @return response array.
	 */
	private function _protected_request( $url, $args, $method ) {
		$args['headers']['Authorization'] = 'Bearer ' . get_option( $this::AUTH_TOKEN_OPTION_NAME );
		if ( 'GET' === $method ) {
			$response = wp_remote_get( $url, $args );
		} else {
			$response = wp_remote_post( $url, $args );
		}

		return $response;
	}

	/**
	 * Do protected request to qbeats for the given url
	 *
	 * @param string $url request url.
	 * @param array  $args request arguments.
	 * @param string $method request method name.
	 * @return response array.
	 */
	public function do_protected_request( $url, $args, $method ) {
		$response = $this->_protected_request( $url, $args, $method );
		if ( ! is_wp_error( $response ) && 403 === $response['response']['code'] ) {
			$this->refresh_token();
			$response = $this->_protected_request( $url, $args, $method );
		}
		return $response;
	}

	/**
	 * Do protected request to public qbeats API
	 *
	 * @param string $path path value.
	 * @param array  $query_args request GET arguments.
	 * @param string $method request GET arguments.
	 * @return response array.
	 */
	public function do_public_protected_request( $path, $query_args, $method ) {
		$url = QbeatsApplicationSettings::QBEATS_PUBLIC_API_BASE_URL . $path . '?' . http_build_query( $query_args, null, '&' );
		$args = array(
			'headers' => array(
				'Authorization' => 'X-QB-TOKEN ' . base64_encode( $this->client_id . '|' . $this->client_secret ),
			),
		);
		if ( 'GET' === $method ) {
			$response = wp_remote_get( $url, $args );
		} else {
			$response = wp_remote_post( $url, $args );
		}
		return $response;
	}

	/**
	 * Create link to authorize a user for the given action
	 *
	 * @param string $action action name.
	 *
	 * @return string authorization url.
	 */
	public function build_authorization_link( $action ) {
		$num_args = func_num_args();
		if ( $num_args > 1 ) {
			$args = func_get_args();
			$data = '';
			foreach ( $args as $arg ) {
				if ( $arg === $action ) {
					continue;
				}
				$data .= $arg . '|';
			}
			// Remove trailing '|'.
			$data = substr( $data, 0, -1 );
		} else {
			$data = null;
		}
		// Save action to be performed after authorization.
		$state_data = $action;
		if ( null !== $data ) {
			$state_data .= '|' . $data;
		}
		$state = uniqid();
		$state_hash = $this->create_state_argument( $state, $action, $data );
		update_option( $state, $state_data );
		$desired_scopes = 'read write publish';
		$query_args = array(
			'client_id'       => $this->client_id,
			'response_type'   => 'code',
			'redirect_uri'    => site_url(),
			'approval_prompt' => 'auto',
			'state'           => $state_hash,
			'scope'           => $desired_scopes,
		);

		return QbeatsApplicationSettings::OAUTH_URL . '?' . http_build_query( $query_args, null, '&' );
	}


	/**
	 * Request a token for a user
	 *
	 * @param string $auth_code code.
	 * @param string $state state.
	 * @return string redirect url.
	 */
	public function retrieve_token( $auth_code, $state ) {
		// Checking that this is the state we sent.
		$decoded_state = json_decode( base64_decode( $state ), true );
		if ( ( null === $decoded_state ) || ( ! array_key_exists( 'h', $decoded_state ) ) ) {
			return site_url();
		}
		$state_data     = get_option( $decoded_state['h'] );
		if ( empty( $state_data ) ) {
			return site_url();
		} else {
			delete_option( $state );
		}
		$state        = uniqid();
		$request_data = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
			),
			'body'    => array(
				'grant_type'   => 'authorization_code',
				'code'         => $auth_code,
				'redirect_uri' => site_url(),
				'state'        => $state,
			),
			'method'  => 'POST',
		);
		$response     = wp_remote_post( QbeatsApplicationSettings::OAUTH_TOKEN_URL, $request_data );
		if ( ! is_wp_error( $response ) ) {
			if ( $this->process_token_response( $response ) ) {
				// Set date of last connection.
				update_option( $this::AUTH_CONNECTED, time() );
				$this->mark_auth_success();
			} else {
				$this->mark_auth_error( 'wrong_id' );
			}
			return QB_Settings::plugin_page_link();
		}

		return site_url();
	}

	/**
	 * Create a link for an action with given data
	 *
	 * @param array $state_data action's data.
	 *
	 * @return string url of an action.
	 */
	private function create_action_links( $state_data ) {
		$parts  = explode( '|', $state_data, 2 );
		$result = site_url();
		switch ( $parts[0] ) {
			case 'plugin':
				require_once( 'settings.php' );
				$result = QbeatsSettings::plugin_page_link();
		}

		return $result;
	}

	/**
	 * Process authentication response
	 *
	 * @param string $response the string of auth response.
	 * @return false in case of error, else true.
	 */
	private function process_token_response( $response ) {
		$response_body = json_decode( $response['body'], true );
		if ( ! $response_body || array_key_exists( 'error', $response_body ) ) {
			qb_error_log( 'process_token_response error', $response );
			return false;
		}
		update_option( $this::AUTH_TOKEN_OPTION_NAME, $response_body['access_token'] );
		update_option( $this::REFRESH_TOKEN_OPTION_NAME, $response_body['refresh_token'] );
		return true;
	}

	/**
	 * Process an error for authentication response
	 *
	 * @return url for response handler.
	 */
	private function process_auth_error() {
		$returned_state = filter_input( INPUT_GET, 'state' );
		// Checking that this is the state we sent.
		$decoded_state = json_decode( base64_decode( $returned_state ), true );
		if ( ( null === $decoded_state ) || ( ! array_key_exists( 'h', $decoded_state ) ) ) {
			return site_url();
		}
		$state_data     = get_option( $decoded_state['h'] );
		if ( empty( $state_data ) ) {
			return site_url();
		} else {
			require_once( 'settings.php' );
			delete_option( $returned_state );
			$this->mark_auth_error( 'wrong_user' );
			return QB_Settings::plugin_page_link();
		}
	}

	/**
	 * Create state argument for an URL
	 *
	 * @param string $state_key state name.
	 * @param string $action action name.
	 * @param array  $data action data.
	 *
	 * @return string value of state.
	 */
	private function create_state_argument( $state_key, $action, $data ) {
		// TODO: helper to prepare state object for requests (should be removed in future).
		$state_object = array(
			'h' => $state_key,
			'a' => $action,
			'o' => $data,
		);
		$state_hash = base64_encode( json_encode( $state_object ) );
		return $state_hash;
	}

	/**
	 * Refresh a token for a user
	 */
	public function refresh_token() {
		if ( ! $this->is_plugin_authorized() ) {
			return false;
		}
		$refresh_token = get_option( $this::REFRESH_TOKEN_OPTION_NAME );
		$request_data = array(
			'method' => 'POST',
			'body'   => array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $refresh_token,
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
			),
		);
		$response     = wp_remote_post( QbeatsApplicationSettings::OAUTH_TOKEN_URL, $request_data );
		if ( ! is_wp_error( $response ) ) {
			return $this->process_token_response( $response );
		} else {
			qb_error_log( 'refresh_token $request_data', $request_data );
			qb_error_log( 'refresh_token $response', $response );
			return false;
		}
	}
}

/**
 * Destroy and clean-up Auth call module
 */
function qb_auth_loader_deinit() {
	global $qb_auth;
	$qb_auth->_deactivate();
	remove_action( 'deactivate_' . PLUGIN_BASE_NAME, 'qb_stories_puller_deinit' );
}

/**
 * Create and init Auth call module
 */
function qb_auth_loader_init() {
	global $qb_auth;
	$qb_auth = new QB_Auth();
}

add_action( 'init', 'qb_auth_loader_init' );
add_action( 'deactivate_' . PLUGIN_BASE_NAME, 'qb_auth_loader_deinit' );
