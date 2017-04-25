<?php

/**
 * Sanitize a surrogate key to only allow hash-like keys.
 *
 * This function will allow surrogate keys to be a-z, A-Z, 0-9, -, and _. This will hopefully ward off any weird issues
 * that might occur with unusual characters.
 *
 * @param  string $key The key to sanitize.
 * @return string            The sanitized key.
 */
function purgely_sanitize_surrogate_key( $key ) {
	return preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key );
}

/**
 * Purge a URL.
 *
 * @since  1.0.0.
 *
 * @param  string $url        The URL to purge.
 * @param  array  $purge_args Additional args to pass to the purge request.
 * @return array|bool|WP_Error                   The purge response.
 */
function purgely_purge_url( $url, $purge_args = array() ) {
	if ( isset( $purge_args['related'] ) && true === $purge_args['related'] ) {
		$purgely = new Purgely_Purge_Request_Collection( $url, $purge_args );
		$purgely->purge_related( $purgely->get_purge_args() );
	} else {
		$purgely = new Purgely_Purge();
		$purgely->purge( 'url', $url, $purge_args );
	}

	return $purgely->get_result();
}

/**
 * Purge a surrogate key.
 *
 * @since  1.0.0.
 *
 * @param  string $key        The surrogate key to purge.
 * @param  array  $purge_args Additional args to pass to the purge request.
 * TODO replace completley with purgely_purge_surrogate_key_collection
 * @return array|bool|WP_Error                   The purge response.
 */
function purgely_purge_surrogate_key( $key, $purge_args = array() ) {
	$purgely = new Purgely_Purge();
	$purgely->purge( 'surrogate-key', $key, $purge_args );
	return $purgely->get_result();
}

/**
 * Purge a surrogate key collection.
 *
 * @since  1.1.1.
 *
 * @param  array $keys        The surrogate key collection to purge.
 * @param  array  $purge_args Additional args to pass to the purge request.
 * @return array|bool|WP_Error                   The purge response.
 */
function purgely_purge_surrogate_key_collection ( $keys, $purge_args = array() ) {
    $purgely = new Purgely_Purge();
    $purgely->purge( 'surrogate-key-collection', $keys, $purge_args );
    return $purgely->get_result();
}

/**
 * Purge the whole cache.
 *
 * @since  1.0.0.
 *
 * @param  array $purge_args Additional args to pass to the purge request.
 * @return array|bool|WP_Error                   The purge response.
 */
function purgely_purge_all( $purge_args = array() ) {
	$purgely    = new Purgely_Purge();
	$purge_args = array_merge( array( 'allow-all' => Purgely_Settings::get_setting( 'allow_purge_all' ) ), $purge_args );

	$purgely->purge( 'all', '', $purge_args );
	return $purgely->get_result();
}

/**
 * Get an individual settings value.
 *
 * @since 1.0.0.
 *
 * @param  string $name The name of the option to retrieve.
 * @return string       The option value.
 */
function purgely_get_option( $name ) {
	$value   = '';
	$options = purgely_get_options();

	if ( isset( $options[ $name ] ) ) {
		$value = $options[ $name ];
	}

	return $value;
}

/**
 * Get all of the Purgely options.
 *
 * Gets the options set by the user and falls back to the constant configuration if the value is not set in options.
 *
 * @since 1.0.0.
 *
 * @return array Array of all Purgely options.
 */
function purgely_get_options() {
	$option_keys = array(
		'fastly_hostname',
		'fastly_api_port',
		'fastly_api_key',
		'fastly_service_id',
        'fastly_page',
        'fastly_log_purges',
		'allow_purge_all',
		'fastly_api_hostname',
		'enable_stale_while_revalidate',
		'stale_while_revalidate_ttl',
		'enable_stale_if_error',
		'stale_if_error_ttl',
		'surrogate_control_ttl',
		'cache_control_ttl',
		'default_purge_type',
	);

	$options = array();

	foreach ( $option_keys as $key ) {
		$constant = 'PURGELY_' . strtoupper( $key );

		if ( defined( $constant ) ) {
			$options[ $key ] = constant( $constant );
		}
	}

	$options = get_option( 'fastly-settings', $options );

	return $options;
}

/**
 * Sanitize a Fastly Service ID or API Key.
 *
 * Restricts a value to only a-z, A-Z, and 0-9.
 *
 * @param  string $key Unsantizied key.
 * @return string      Sanitized key.
 */
function purgely_sanitize_key( $key ) {
	return preg_replace( '/[^a-zA-Z0-9]/', '', $key );
}

/**
 * Callback function for sanitizing a checkbox setting.
 *
 * @since 1.0.0.
 *
 * @param  mixed $value Unsanitized setting.
 * @return bool         Whether or not value is valid.
 */
function purgely_sanitize_checkbox( $value ) {
	return ( in_array( $value, array( '1', 1, 'true', true ), true ) );
}