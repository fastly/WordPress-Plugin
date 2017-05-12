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
	 * @var string The thing that will be purged.
	 */
	private $_thing = '';

	/**
	 * The type of purge request, which is 'url', 'key-collection', or 'all'.
	 *
	 * @var string The type of purge request.
	 */
	private $_type = 'url';

	/**
	 * Issue the purge request.
	 *
	 * @param string $type       The type of purge request.
	 * @param string $thing      The identifier for the item to purge.
	 * @param array  $purge_args Additional args to pass to the purge request. TODO implement support if needed
	 * @return array|bool|WP_Error The response from the purge request.
	 */
	public function purge( $type = 'url', $thing = '', $purge_args = array() ) {
		if ( 'all' === $type && ( ! isset( $purge_args['allow-all'] ) || true !== $purge_args['allow-all'] ) ) {
			return false;
		} else {
			return $this->_issue_purge_request( $type, $thing );
		}
	}

	/**
	 * Issue the purge request.
	 *
	 * @param  string $type       The type of purge request.
	 * @param  string|array $thing      The identifier for the item to purge.
	 * @return array|bool|WP_Error The response from the purge request.
	 */
	private function _issue_purge_request( $type = 'url', $thing = '' ) {

        if ( !in_array( $type, array( 'all', 'key-collection', 'url' ) ) ) {
            return false;
        }

		$this->set_type( $type );
		$this->set_thing( $thing );

        // Build up headers & request url
        $headers = $this->_build_headers();
        $request_uri = $this->_build_request_uri_for_purge( $type );

		if ($request_uri && !empty($thing)) {
		    try {
                $response = Requests::request($request_uri, $headers, array(), Requests::POST);
                if(!$response->success) {
                    if(Purgely_Settings::get_setting( 'fastly_log_purges' )) {
                        error_log("Purging " . $request_uri . " - " . json_decode($response->body));
                    }
                }

                // Send Slack Webhooks request message
                if(Purgely_Settings::get_setting( 'webhooks_activate' )) {
                    $message = 'Purged keys : ' . $headers['Surrogate-Key'];
                    sendWebHook($message);
                }

                return $response->success;
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
		}
		return false;
	}

    /**
     * Builds request headers
     *
     * @return array
     */
    private function _build_headers() {
        $headers = array();

        // Credentials
        $headers['Fastly-Key'] = Purgely_Settings::get_setting( 'fastly_api_key' );

        // Purge type
        if(Purgely_Settings::get_setting( 'default_purge_type' ) === 'soft') {
            $headers['Fastly-Soft-Purge'] = 1;
        }

        // Add Surrogate-Key header
        // TODO - if header size exceeded, split in multiple request
        $keys = implode(' ', $this->get_thing());
        $headers['Surrogate-Key'] = $keys;

        return $headers;
    }

    /**
     * Build the URI for the purge request.
     *
     * @type string Type of the purge request (key, url, all)
     * @return string The purge URI to purge all items.
     */
    private function _build_request_uri_for_purge( $type ) {
        $api_endpoint      = Purgely_Settings::get_setting( 'fastly_api_hostname' );
        $fastly_service_id = Purgely_Settings::get_setting( 'fastly_service_id' );

        switch ($type){
            case 'key-collection':
                return trailingslashit( $api_endpoint ) . 'service/' . $fastly_service_id . '/purge';
                break;
            case 'url':
                return $this->get_thing();
                break;
            case 'all':
                return trailingslashit( $api_endpoint ) . 'service/' . $fastly_service_id . '/purge_all';
                break;
            default :
                return false;
        }
    }

	/**
	 * Set the thing to purge.
	 *
	 * @param string|array $thing The identifier for the purged item.
	 * @return void
	 */
	public function set_thing( $thing ) {
		$this->_thing = $thing;
	}

	/**
	 * Get the thing to purge.
	 *
	 * @return string|array The identifier for the purged item.
	 */
	public function get_thing() {
		return $this->_thing;
	}

	/**
	 * Set the type of purge.
	 *
	 * @param string $type The type of purge to perform.
	 * @return void
	 */
	public function set_type( $type ) {
		$this->_type = $type;
	}

	/**
	 * Get the type of purge.
	 *
	 * @return string The type of purge being performed.
	 */
	public function get_type() {
		return $this->_type;
	}
}