<?php
/**
 * Module for application settings
 *
 * @package qbeats for WordPress
 */

/**
 * Wrapper for application settings
 */
class QbeatsApplicationSettings {
	/**
	 * Url of qbeats portal
	 *
	 * @var string
	 */
	const QBEATS_PORTAL_URL = 'https://publish.qbeats.com/';
	/**
	 * Url of qbeats library
	 *
	 * @var string
	 */
	const QBEATS_LIBRARY_URL = 'static-my.qbeats.com/static/qb_platform/assets/js/';
	/**
	 * Url of qbeats platform API
	 *
	 * @var string
	 */
	const QBEATS_API_BASE_URL = 'https://my.qbeats.com/platform/api/wp/';
	/**
	 * Url of qbeats platform API
	 *
	 * @var string
	 */
	const QBEATS_PUBLIC_API_BASE_URL = 'https://my.qbeats.com/api/';
	/**
	 * Url of qbeats story
	 *
	 * @var string
	 */
	const QBEATS_STORY_BASE_URL = 'https://my.qbeats.com/stories/';
	/**
	 * OAuth service url
	 *
	 * @var string
	 */
	const OAUTH_URL = 'https://auth.qbeats.com/';
	/**
	 * OAuth token service url
	 *
	 * @var string
	 */
	const OAUTH_TOKEN_URL = 'https://auth.qbeats.com/token/';
}
?>
