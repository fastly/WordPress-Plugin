<?php
/**
 * Issue a purge request for a resource.
 *
 * This is the main class to handle all purge related activities. This class will handle individual URL, key, and all
 * purges. Additionally, it can set soft purges and purge links related to the passed URL.
 */
class Purgely_Purge {
	/**
	 * The url or surrogate key to purge.
	 *
	 * @since 1.0.0.
	 *
	 * @var string The thing that will be purged.
	 */
	private $_thing = '';

	/**
	 * The type of purge request, which is 'url', 'surrogate-key', or 'all'.
	 *
	 * @since 1.0.0.
	 *
	 * @var string The type of purge request.
	 */
	private $_type = 'url';

	/**
	 * Additional args used for processing the purge request.
	 *
	 * @since 1.0.0.
	 *
	 * @var array Args used for processing the purge request.
	 */
	private $_purge_args = array();

	/**
	 * The response object generated from the purge request.
	 *
	 * @since 1.0.0.
	 *
	 * @var bool|object The object from the completed purge request.
	 */
	private $_response = false;

	/**
	 * Issue the purge request.
	 *
	 * @since 1.0.0.
	 *
	 * @param string $type       The type of purge request.
	 * @param string $thing      The identifier for the item to purge.
	 * @param array  $purge_args Additional args to pass to the purge request.
	 * @return array|bool|WP_Error The response from the purge request.
	 */
	public function purge( $type = 'url', $thing = '', $purge_args = array() ) {
		if ( 'all' === $type && ( ! isset( $purge_args['allow-all'] ) || true !== $purge_args['allow-all'] ) ) {
			return false;
		} else {
			return $this->_issue_purge_request( $type, $thing, $purge_args );
		}
	}

	/**
	 * Issue the purge request.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $type       The type of purge request.
	 * @param  string|array $thing      The identifier for the item to purge.
	 * @param  array  $purge_args Additional args to pass to the purge request.
	 * @return array|bool|WP_Error The response from the purge request.
	 */
	private function _issue_purge_request( $type = 'url', $thing = '', $purge_args = array() ) {
		$response = false;

		$this->set_type( $type );
		$this->set_thing( $thing );
		$this->set_purge_args( $purge_args );

		// Set up our default args.
		$default_args = array(
			'purge-type' => Purgely_Settings::get_setting( 'default_purge_type' ),
		);

		$purge_args = array_merge( $default_args, $purge_args );

		// Setup the shared remote request args.
		$remote_request_args = array(
			'method' => 'PURGE',
		);

		// Add credentials if necessary.
		$remote_request_args = $this->_maybe_add_credentials( $remote_request_args, $type );

		// Add soft purge header if necessary.
		$remote_request_args = $this->_maybe_add_soft_purge( $purge_args, $remote_request_args );

		if ( 'url' === $type ) {
			$request_uri = $this->_build_request_uri_for_single_item( $thing );
		} else if ( 'surrogate-key' === $type ) {
			$request_uri = $this->_build_request_uri_for_surrogate_key( $thing );
		} else if ( 'surrogate-key-collection' === $type ) {
            $request_uri = $this->_build_request_uri_for_surrogate_key_collection( $thing );
        } else if ( 'all' ) {
			$request_uri = $this->_build_request_uri_for_purge_all();
		}

		if ( ! empty( $request_uri ) ) {

		    // Handle multiple
		    if(is_array($request_uri))
            {
                $requests = array();
                foreach($request_uri as $request_url) {
                    $request = array();
                    $request['url'] = $request_url;
                    $request['headers'] = $remote_request_args['headers'];
                    $request['type'] = $remote_request_args['method'];
//                    $request['headers']['Fastly-Debug'] = 1; // TODO on off switch with logging possibility?

                    $requests[] = $request;
                }

                $response_output = Requests::request_multiple($requests);
                $response = true;


                if($log = Purgely_Settings::get_setting( 'fastly_log_purges' )) {
                    $log_arr = array();
                }

                foreach ($response_output as $k => $single_response) {
                    $resp = json_decode($single_response->body);
                    if($resp->status !== 'ok') {
                        $response = false;
                    }

                    if($log) {
                        $item = isset($thing[$k]) ? $thing[$k] : "invalid-{$k}";
                        $status = isset($resp->status) ? $resp->status : 'error';
                        $log_arr[$item] = $status;
                    }
                }

                if($log) {
                    error_log("Purging " . json_encode($log_arr));
                }

            } else {
		        //Handle single
                $response = wp_remote_request( $request_uri, $remote_request_args );

                // Log the purge
                if(Purgely_Settings::get_setting( 'fastly_log_purges' )) {
                    error_log("Purging " . $request_uri . " - " . $response['response']['message']);
                }
            }
		}

		// Record the response.
		$this->set_response( $response );

		return $response;
	}

	/**
	 * Get the result of the purge.
	 *
	 * @since 1.0.0.
	 *
	 * @return string "success" if purge was successful, "failure" if it was not.
	 */
	public function get_result() {
		$response = $this->get_response();
		$result   = 'success';

		if ( false === $response || is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			$result = 'failure';
		}

		return $result;
	}

	/**
	 * Build the URI for the purge request.
	 *
	 * Note that this is just a wrapper for the URI itself. It is set up this way for consistency with the rest of the
	 * class methods, as well as for updating should this request ever become more complicated.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $uri The URI to purge.
	 * @return string The purge URI.
	 */
	private function _build_request_uri_for_single_item( $uri ) {
		return $uri;
	}

	/**
	 * Build the URI for the purge request.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $key The surrogate key for the group of items to purge.
	 * @return string The purge URI.
	 */
	private function _build_request_uri_for_surrogate_key( $key ) {
		$api_endpoint      = Purgely_Settings::get_setting( 'fastly_api_hostname' );
		$fastly_service_id = Purgely_Settings::get_setting( 'fastly_service_id' );

		return trailingslashit( $api_endpoint ) . 'service/' . $fastly_service_id . '/purge/' . purgely_sanitize_surrogate_key( $key );
	}

    /**
     * Build the URI for the multiple purge requests.
     *
     * @since 1.0.0.
     *
     * @param  array $keys The surrogate key for the group of items to purge.
     * @return array The purge URIs.
     */
	public function _build_request_uri_for_surrogate_key_collection( $keys) {
        $api_endpoint      = Purgely_Settings::get_setting( 'fastly_api_hostname' );
        $fastly_service_id = Purgely_Settings::get_setting( 'fastly_service_id' );

        $collection = array();
        foreach($keys as $key){
            $collection[] = trailingslashit( $api_endpoint ) . 'service/' . $fastly_service_id . '/purge/' . purgely_sanitize_surrogate_key( $key );
        }

        return $collection;
    }

	/**
	 * Build the URI for the purge request.
	 *
	 * @since 1.0.0.
	 *
	 * @return string The purge URI to purge all items.
	 */
	private function _build_request_uri_for_purge_all() {
		$api_endpoint      = Purgely_Settings::get_setting( 'fastly_api_hostname' );
		$fastly_service_id = Purgely_Settings::get_setting( 'fastly_service_id' );

		return trailingslashit( $api_endpoint ) . 'service/' . $fastly_service_id . '/purge_all';
	}

	/**
	 * Add the soft purge headers if requested.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $purge_args          The purge request args.
	 * @param  array $remote_request_args The current remote request args.
	 * @return array The potentially modified remote request args.
	 */
	private function _maybe_add_soft_purge( $purge_args, $remote_request_args ) {
		if ( isset( $purge_args['purge-type'] ) && 'soft' === $purge_args['purge-type'] ) {
			$remote_request_args = $this->_add_soft_purge( $remote_request_args );
		}

		return $remote_request_args;
	}

	/**
	 * Add the Fastly soft purge header to the request.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $remote_request_args The current remote request args.
	 * @return array The modified remote request args.
	 */
	private function _add_soft_purge( $remote_request_args ) {
		$remote_request_args['headers']['Fastly-Soft-Purge'] = 1;
		return $remote_request_args;
	}

	/**
	 * Add the Fastly API credential header if requested.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array  $remote_request_args The current remote request args.
	 * @param  string $type                The type of request being issues.
	 * @return array The potentially modified remote request args.
	 */
	private function _maybe_add_credentials( $remote_request_args, $type ) {
		if ( in_array( $type, array( 'surrogate-key', 'all', 'surrogate-key-collection' ) ) ) {
			$remote_request_args = $this->_add_credentials( $remote_request_args );
			$remote_request_args = $this->_make_post_request( $remote_request_args );
		}

		return $remote_request_args;
	}

	/**
	 * Add the Fastly API credential header to the request.
	 *
	 * @since 1.0.0.
	 *
	 * @param array $remote_request_args The current remote request args.
	 * @return array The modified remote request args.
	 */
	private function _add_credentials( $remote_request_args ) {
		$remote_request_args['headers']['Fastly-Key'] = Purgely_Settings::get_setting( 'fastly_api_key' );
		return $remote_request_args;
	}

	/**
	 * Make the request a post request.
	 *
	 * @since 1.0.0.
	 *
	 * @param array $remote_request_args The current remote request args.
	 * @return array The modified remote request args.
	 */
	private function _make_post_request( $remote_request_args ) {
		$remote_request_args['method'] = 'POST';
		return $remote_request_args;
	}

	/**
	 * Set the thing to purge.
	 *
	 * @since 1.0.0.
	 *
	 * @param string $thing The identifier for the purged item.
	 * @return void
	 */
	public function set_thing( $thing ) {
		$this->_thing = $thing;
	}

	/**
	 * Get the thing to purge.
	 *
	 * @since 1.0.0.
	 *
	 * @return string The identifier for the purged item.
	 */
	public function get_thing() {
		return $this->_thing;
	}

	/**
	 * Set the type of purge.
	 *
	 * @since 1.0.0.
	 *
	 * @param string $type The type of purge to perform.
	 * @return void
	 */
	public function set_type( $type ) {
		$this->_type = $type;
	}

	/**
	 * Set the args for the purge.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $purge_args The args to modify the purge request.
	 * @return void
	 */
	public function set_purge_args( $purge_args ) {
		$this->_purge_args = $purge_args;
	}

	/**
	 * Get the type of purge.
	 *
	 * @since 1.0.0.
	 *
	 * @return string The type of purge being performed.
	 */
	public function get_type() {
		return $this->_type;
	}

	/**
	 * Get the args for the purge.
	 *
	 * @since 1.0.0.
	 *
	 * @return string The args used in the purge request.
	 */
	public function get_purge_args() {
		return $this->_purge_args;
	}

	/**
	 * Get the response object for the purge.
	 *
	 * @since 1.0.0.
	 *
	 * @return array|WP_Error The response from the purge request.
	 */
	public function get_response() {
		return $this->_response;
	}

	/**
	 * Set the response for the purge.
	 *
	 * @since 1.0.0.
	 *
	 * @param  WP_Error|object $response The response for the purge request.
	 * @return void
	 */
	public function set_response( $response ) {
		$this->_response = $response;
	}
}