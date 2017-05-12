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
	 * @return bool                   The list of purge request responses.
	 */
	public function purge_related( $purge_args = array() ) {
        $response = true;
		$urls      = $this->get_urls();

		// Iterate through each and purge.
		if ( count( $urls ) > 0 ) {
			foreach ( $urls as $categories ) {
				foreach ( $categories as $url ) {
					$purge             = new Purgely_Purge();
					if($purge->purge( 'url', $url, $purge_args ) !== true) {
					    $response = false;
                    }
				}
			}
		}
		return $response;
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