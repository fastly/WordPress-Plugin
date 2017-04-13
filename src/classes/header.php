<?php
/**
 * Abstract class that defines basic header behavior.
 *
 * A big part of this plugin is defining response headers that control the Fastly caching behavior. This class
 * simplifies some of the basic functionality of the header classes.
 */
abstract class Purgely_Header {
	/**
	 * The header name that will be set.
	 *
	 * @since 1.0.0.
	 *
	 * @var string The header name.
	 */
	protected $_header_name = '';

	/**
	 * The surrogate key value.
	 *
	 * @since 1.0.0.
	 *
	 * @var string The surrogate key that will be set.
	 */
	protected $_value = '';

	/**
	 * Send the key by setting the header.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function send_header() {
	    // TODO check surrogate-key size - how to handle exceeding size? custom thing that will be always purged?
		header( $this->_header_name . ': ' . $this->get_value(), false );
	}

	/**
	 * Set the header name.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $header_name The header name.
	 * @return void
	 */
	public function set_header_name( $header_name ) {
		$this->_header_name = $header_name;
	}

	/**
	 * Return the header name.
	 *
	 * @since 1.0.0.
	 *
	 * @return string The header name.
	 */
	public function get_header_name() {
		return $this->_header_name;
	}

	/**
	 * Set the header value.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $value The header value.
	 * @return void
	 */
	public function set_value( $value ) {
		$this->_value = $value;
	}

	/**
	 * Return the value of the header.
	 *
	 * @since 1.0.0.
	 *
	 * @return string The header value.
	 */
	public function get_value() {
		return $this->_value;
	}
}