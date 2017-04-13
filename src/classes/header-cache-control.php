<?php
/**
 * Class for managing cache control headers.
 *
 * This class extends the Purgely_Header class to control Cache-Control header behavior. In particular, this is only
 * intended to work for the `stale-while-revalidate` and `stale-if-error` directives.
 */
class Purgely_Cache_Control_Header extends Purgely_Header {
	/**
	 * The TTL for the resource.
	 *
	 * @since 1.0.0.
	 *
	 * @var int The TTL for the resource.
	 */
	private $_seconds = 0;

	/**
	 * The directive to set.
	 *
	 * @since 1.0.0.
	 *
	 * @var int The directive to set.
	 */
	private $_directive = '';

	/**
	 * Construct the object.
	 *
	 * @since 1.0.0.
	 *
	 * @param int    $seconds   The TTL for the object.
	 * @param string $directive The cache control directive to set.
	 * @return Purgely_Cache_Control_Header
	 */
	public function __construct( $seconds, $directive ) {
		$this->set_seconds( $seconds );
		$this->set_directive( $directive );
		$this->set_header_name( 'Cache-Control' );
		$this->set_value( $this->prepare_value( $seconds, $directive ) );
	}

	/**
	 * Generate the full header value string.
	 *
	 * @since 1.0.0.
	 *
	 * @param int    $seconds   The number of seconds to cache the resource.
	 * @param string $directive The cache control directive to set.
	 * @return string
	 */
	public function prepare_value( $seconds, $directive ) {
		return sanitize_key( $directive ) . '=' . absint( $seconds );
	}

	/**
	 * Get the TTL for an object.
	 *
	 * @since 1.0.0.
	 *
	 * @return int The TTL in seconds.
	 */
	public function get_seconds() {
		return $this->_seconds;
	}

	/**
	 * Set the TTL for the object.
	 *
	 * @since 1.0.0.
	 *
	 * @param int $seconds The TTL for the object.
	 * @return void
	 */
	public function set_seconds( $seconds ) {
		$this->_seconds = $seconds;
	}

	/**
	 * Get the directive.
	 *
	 * @since 1.0.0.
	 *
	 * @return int The directive.
	 */
	public function get_directive() {
		return $this->_directive;
	}

	/**
	 * Set the directive.
	 *
	 * @since 1.0.0.
	 *
	 * @param int $directive The directive.
	 * @return void
	 */
	public function set_directive( $directive ) {
		$this->_directive = $directive;
	}
}
