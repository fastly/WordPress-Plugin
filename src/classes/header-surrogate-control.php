<?php
/**
 * Control the surrogate control header.
 *
 * The surrogate control header controls the TTL for objects on Fastly. This class extends the basic header class and
 * ensures Surrogate-Control header is set.
 */
class Purgely_Surrogate_Control_Header extends Purgely_Header {

    /**
     * Headers used.
     *
     * @since 1.1.1.
     *
     * @var array Headers that will be built and set
     */
    private $_headers= array();

	/**
	 * Construct the object.
	 *
	 * @since 1.0.0.
	 *
	 * @param  int $seconds The TTL for the object.
	 */
	public function __construct() {
		$this->set_header_name( 'Surrogate-Control' );
		$this->build_original_headers();
	}

    /**
     * Sets original headers
     *
     * @since 1.1.1.
     */
    public function build_original_headers()
    {
        if ( true === Purgely_Settings::get_setting( 'enable_stale_while_revalidate' ) ) {
            $this->_headers['max-age'] = absint( Purgely_Settings::get_setting( 'surrogate_control_ttl' ) );
        }
        if ( true === Purgely_Settings::get_setting( 'enable_stale_while_revalidate' ) ) {
            $this->_headers['stale-while-revalidate'] = absint( Purgely_Settings::get_setting( 'stale_while_revalidate_ttl' ) );
        }
        if ( true === Purgely_Settings::get_setting( 'enable_stale_if_error' ) ) {
            $this->_headers['stale-if-error'] = absint( Purgely_Settings::get_setting( 'stale_if_error_ttl' ) );
        }
    }

    /**
     * Remove wanted headers
     *
     * @since 1.1.1.
     *
     * @param array|string $key
     */
    public function unset_headers($key)
    {
        if(is_array($key)) {
            foreach($key as $k) {
                if(!empty($this->_headers[$k])) {
                    unset($this->_headers[$k]);
                }
            }
        } else {
            if(!empty($this->_headers[$key])) {
                unset($this->_headers[$key]);
            }
        }
    }

    /**
     * Edit headers - allows to overwrite or add new headers
     *
     * @since 1.1.1.
     *
     * @param array $key
     */
    public function edit_headers($key)
    {
        if(is_array($key)) {
            foreach($key as $k => $val) {
                $this->_headers[$k] = $val;
            }
        }
    }

    /**
     * Build header string based on current headers
     *
     * @since 1.1.1.
     *
     * @return string
     */
    public function build_header_value()
    {
        $headers = '';
        foreach($this->_headers as $name => $val) {
            $headers .= $name . '=' . $val . ', ';
        }

        return rtrim($headers, ', ');
    }

    /**
     * Return the value of the header.
     *
     * @since 1.1.1.
     *
     * @return string The header value.
     */
    public function get_value() {
        return $this->build_header_value();
    }
}