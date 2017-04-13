<?php
/**
 * Set the Surrogate Keys header for a request.
 *
 * This class gathers, sanitizes and sends all of the Surrogate Keys for a request.
 */
class Purgely_Surrogate_Keys_Header extends Purgely_Header {
	/**
	 * The lists that will compose the Surrogate-Keys header value.
	 *
	 * @since 1.0.0.
	 *
	 * @var array    List of Surrogate Keys.
	 */
	private $_keys = array();

	/**
	 * Construct the new object.
	 *
	 * @since 1.0.0.
	 */
	public function __construct() {
		$this->set_header_name( 'Surrogate-Key' );
	}

	/**
	 * Send the key by setting the header.
	 *
	 * @since 1.0.0.
	 *
	 * @return void
	 */
	public function send_header() {
		$this->set_value( $this->prepare_keys( $this->get_keys() ) );
		parent::send_header();
	}

	/**
	 * Prepare the keys into a header value string.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $keys The keys for the header.
	 * @return string Space delimited list of sanitized keys.
	 */
	public function prepare_keys( $keys ) {
		$keys = array_map( array( $this, 'sanitize_key' ), $keys );
		return implode( ' ', $keys );
	}

	/**
	 * Sanitize a surrogate key.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $key The unsanitized key.
	 * @return string The sanitized key.
	 */
	public function sanitize_key( $key ) {
		return purgely_sanitize_surrogate_key( $key );
	}

	/**
	 * Add a key to the list.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $key The key to add to the list.
	 * @return array       The full list of keys.
	 */
	public function add_key( $key ) {
		$keys   = $this->get_keys();
		$keys[] = $key;

		$this->set_keys( $keys );
		return $keys;
	}

	/**
	 * Add multiple keys to the list.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $keys The keys to add to the list.
	 * @return array The full list of keys.
	 */
	public function add_keys( $keys ) {
		$current_keys = $this->get_keys();

		// Combine keys.
		$keys = array_merge( $current_keys, $keys );

		// De-dupe keys.
		$keys = array_unique( $keys );

		// Rekey the keys.
		$keys = array_values( $keys );

		$this->set_keys( $keys );
		return $keys;
	}

	/**
	 * Set the keys for the Surrogate Keys header.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $keys The keys for the header.
	 * @return void
	 */
	public function set_keys( $keys ) {
		$this->_keys = $keys;
	}

	/**
	 * Key the list of Surrogate Keys.
	 *
	 * @since 1.0.0.
	 *
	 * @return array The list of Surrogate Keys.
	 */
	public function get_keys() {
		return $this->_keys;
	}
}