<?php
/**
 * Module for logging
 *
 * @package qbeats for WordPress
 */

/**
 * Print message to log file
 *
 * @param string $message logging message.
 * @param object $object logging object.
 */
function qb_error_log( $message, $object = null ) {
	ob_start();
	print_r( $message . ': ' );
	var_dump( $object );
	$contents = ob_get_contents();
	ob_end_clean();
	error_log( $contents );
}
