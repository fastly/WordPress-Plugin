<?php
/**
 * Issues a collections of purge requests related to a single URL.
 *
 * When attempting to purge URLs related to a URL, this class is used to collect all of the individual purge request
 * objects and manage the purge.
 */
class Purgely_Purge_Request_Collection {
	/**
	 * Purge request objects.
	 *
	 * @since 1.0.0.
	 *
	 * @var Purgely_Purge The Purgely_Purge request objects from the purges.
	 */
	var $_purge_requests = array();

	/**
	 * The response objects generated from the purge requests.
	 *
	 * @since 1.0.0.
	 *
	 * @var array The objects from the completed purge requests.
	 */
	var $_responses = array();

	/**
	 * Additional args used for processing the purge request.
	 *
	 * @since 1.0.0.
	 *
	 * @var array Args used for processing the purge request.
	 */
	var $_purge_args = array();

	/**
	 * The categorized list of URLs to purge.
	 *
	 * @var array The categorized list of URLs.
	 */
	var $_urls = array();

	/**
	 * Construct the object.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $url        The main URL to purge.
	 * @param  array  $purge_args The args for the purges.
	 */
	public function __construct( $url, $purge_args = array() ) {
		// Set the args.
		$this->set_purge_args( $purge_args );

		// Get all the related URLs.
		$urls = $this->get_related_urls( $url );

		// Add the initial URL.
		$urls = array_merge( array( 'url' => array( $url ) ), $urls );

		// Save the URLs.
		$this->set_urls( $urls );
	}

	/**
	 * Collect the URLs related to the main URL.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $url The main URL.
	 * @return array             The categorized list of related URLs.
	 */
	public function get_related_urls( $url ) {
		$related = new Purgely_Related_Urls( array( 'url' => $url ) );
		return $related->locate_all();
	}

	/**
	 * Iterate through and purge all related URLs.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $purge_args The arguments to send to the purge request.
	 * @return array                   The list of purge request responses.
	 */
	public function purge_related( $purge_args = array() ) {
		$responses = array();
		$urls      = $this->get_urls();

		// Iterate through each and purge.
		if ( count( $urls ) > 0 ) {
			foreach ( $urls as $categories ) {
				foreach ( $categories as $url ) {
					$purge             = new Purgely_Purge();
					$responses[ $url ] = $purge->purge( 'url', $url, $purge_args );

					// Record the object.
					$this->set_purge_request( $purge );
				}
			}
		}

		$this->set_responses( $responses );

		return $responses;
	}

	/**
	 * Get the collective results of the purge requests.
	 *
	 * @since 1.0.0.
	 *
	 * @return string    "success" if all purges are successful, "failure" if one or more requests fails.
	 */
	public function get_result() {
		$responses = $this->get_responses();
		$result    = 'success';

		if ( count( $responses ) > 0 ) {
			foreach ( $responses as $response ) {
				if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
					$result = 'failure';
				}
			}
		}

		return $result;
	}

	/**
	 * Get all of the Purgely_Purge objects.
	 *
	 * @since 1.0.0.
	 *
	 * @return Purgely_Purge    The list of Purgely_Purge objects.
	 */
	public function get_purge_requests() {
		return $this->_purge_requests;
	}

	/**
	 * Set the purge requests object.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $purge_requests A list of Purgely_Purge objects.
	 * @return void
	 */
	public function set_purge_requests( $purge_requests ) {
		$this->_purge_requests = $purge_requests;
	}

	/**
	 * Set an individual purge request object.
	 *
	 * @since 1.0.0.
	 *
	 * @param  Purgely_Purge $purge_request A Purgely_Purge object.
	 * @return void
	 */
	public function set_purge_request( $purge_request ) {
		$this->_purge_requests[] = $purge_request;
	}

	/**
	 * Get all of the purge responses.
	 *
	 * @since 1.0.0.
	 *
	 * @return array    The list of responses.
	 */
	public function get_responses() {
		return $this->_responses;
	}

	/**
	 * Set all responses.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $responses A list of HTTP responses.
	 * @return void
	 */
	public function set_responses( $responses ) {
		$this->_responses = $responses;
	}

	/**
	 * Set an individual response.
	 *
	 * @since 1.0.0.
	 *
	 * @param  WP_Error|object $response An individual purge response.
	 * @return void
	 */
	public function set_response( $response ) {
		$this->_responses[] = $response;
	}

	/**
	 * Get the list of categorized URLs.
	 *
	 * @since 1.0.0.
	 *
	 * @return array    The list of categorized URLs.
	 */
	public function get_urls() {
		return $this->_urls;
	}

	/**
	 * Set the list of categorized URLs.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $urls The list of categorized URLs.
	 * @return void
	 */
	public function set_urls( $urls ) {
		$this->_urls = $urls;
	}

	/**
	 * Set an individual URL.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $url  The URL.
	 * @param  string $type The type of URL.
	 * @return void
	 */
	public function set_url( $url, $type ) {
		$this->_urls[ $type ][] = $url;
	}

	/**
	 * Get the purge args.
	 *
	 * @since 1.0.0.
	 *
	 * @return array    The list of purge args.
	 */
	public function get_purge_args() {
		return $this->_purge_args;
	}

	/**
	 * Set the purge args.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $purge_args The list of purge args.
	 * @return void
	 */
	public function set_purge_args( $purge_args ) {
		$this->_purge_args = $purge_args;
	}
}