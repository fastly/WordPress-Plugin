<?php
/**
 * Control the surrogate control header.
 *
 * The surrogate control header controls the TTL for objects on Fastly. This class extends the basic header class and
 * ensures that the TTL is properly set.
 */
class Purgely_Surrogate_Control_Header extends Purgely_Header {
	/**
	 * The TTL for the resource.
	 *
	 * @since 1.0.0.
	 *
	 * @var int The TTL for the resource.
	 */
	private $_seconds = 0;

	/**
	 * Construct the object.
	 *
	 * @since 1.0.0.
	 *
	 * @param  int $seconds The TTL for the object.
	 */
	public function __construct( $seconds ) {
		$this->set_seconds( $seconds );
		$this->set_header_name( 'Surrogate-Control' );
		$this->set_value( $this->prepare_value( $seconds ) );
	}

	/**
	 * Generate the full header value string.
	 *
	 * @since 1.0.0.
	 *
	 * @param  int $seconds The number of seconds to cache the resource.
	 * @return string
	 */
	public function prepare_value( $seconds ) {
		return 'max-age=' . absint( $seconds );
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
	 */
	public function set_seconds( $seconds ) {
		$this->_seconds = $seconds;
	}
}