<?php

$cache_file = ABSPATH . 'wp-content/object-cache.php';

if ( ! file_exists( $cache_file ) ) {
	return;
}

require_once $cache_file;
wp_cache_init();

/**
 * Maybe rate limit this request.
 *
 * @return void
 */
function maybe_rate_limit() {

	$key = get_cache_key();
	if ( empty( $key ) ) {
		return;
	}

	$debug_data = [
		'key' => $key,
	];

	$access_data = wp_cache_get( $key );

	if ( empty( $access_data ) ) {

		$access_data = [
			'first' => time(),
			'count' => 1,
		];

		$debug_data['access'] = $access_data;

		wp_cache_set( $key, $access_data, '', cache_seconds() );
	} else {

		$access_data['count']++;


		$debug_data['access'] = $access_data;

		wp_cache_set( $key, $access_data, '', cache_seconds() );
	}

	if ( is_debug() ) {
		var_dump( $debug_data ); die();
		exit;
	}
}

function cache_seconds() {
	return 15 * 60;
}

function get_cache_key() {

	$data = filter_var_array(
		$_SERVER,
		[
			'REMOTE_ADDR' => FILTER_SANITIZE_STRING,
		]
	);

	if ( empty( $data['REMOTE_ADDR'] ) ) {
		return false;
	}

	$ip = $data['REMOTE_ADDR'];

	$ip_parts = explode( '.', $ip );

	if ( count( $ip_parts ) === 4 ) {
		foreach ( $ip_parts as $part ) {
			if ( intval( $part ) > 999 ) {
				return false;
			}
		}

		return 'wp-rate-limit-' . implode( '.', $ip_parts );
	}

	return false;
}

function is_debug() {
	$data = filter_var_array(
		$_GET,
		[
			'wp-rate-limit-debug' => FILTER_SANITIZE_STRING,
		]
	);

	return ! empty( $data['wp-rate-limit-debug'] );
}

maybe_rate_limit();
