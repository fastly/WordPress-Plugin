<?php

/**
 * Collects URLs related to another URL.
 *
 * Attempts to find all URLs that are related to an individual post URL. This is helpful when purging a group of URLs
 * based on an individual URL.
 */
class Purgely_Related_Urls {
	/**
	 * The URL from which relationships are determined.
	 *
	 * @since 1.0.0.
	 *
	 * @var string The URL from which relationships are determined.
	 */
	var $_url = '';

	/**
	 * The post ID from which relationships are determined.
	 *
	 * @since 1.0.0.
	 *
	 * @var string The post ID from which relationships are determined.
	 */
	var $_post_id = 0;

	/**
	 * The WP_Post object from which relationships are determined.
	 *
	 * @since 1.0.0.
	 *
	 * @var null|WP_Post The WP_Post object from which relationships are determined.
	 */
	var $_post = null;

	/**
	 * The list of URLs related to the main URL.
	 *
	 * @since 1.0.0.
	 *
	 * @var array The list of URLs related to the main URL.
	 */
	var $_related_urls = array();

	/**
	 * Construct the object.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $identifiers An array with an 'id', 'post', or 'url' index. You can send a
	 *                            post ID, a post object or a URL to the class and it will find
	 *                            related URLs.
	 * @return Purgely_Related_Urls
	 */
	public function __construct( $identifiers ) {
		// Pull the post object from the $identifiers array and setup a standard post object.
		$this->set_post( $this->_determine_post( $identifiers ) );

		// Now that we have the post, let's fill out the other identifiers.
		$this->set_url( get_permalink( $this->get_post() ) );
		$this->set_post_id( $this->get_post()->ID );
	}

	/**
	 * Locate all of the URLs.
	 *
	 * @since 1.0.0.
	 *
	 * @return array The related URLs.
	 */
	public function locate_all() {
		// Set all of the URLs.
		$this->locate_terms_urls( $this->get_post_id(), 'category' );
		$this->locate_terms_urls( $this->get_post_id(), 'post_tag' );
		$this->locate_author_urls( $this->get_post() );
		$this->locate_post_type_archive_url( $this->get_post() );
		$this->locate_feed_urls( $this->get_post() );

		// Return what has been found.
		return $this->get_related_urls();
	}

	/**
	 * Get the post from which to identify related URLs.
	 *
	 * This class takes a URL, ID or post object as input. This method will use that input to standardize it to a
	 * WP_Post object. This makes all of the other methods much simpler in that they can operate on a WP_Post object
	 * instead of various inputs.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $identifiers The list of identifiers used when instantiating the object.
	 * @return null|WP_Post                    null if post is not found, otherwise a WP_Post object.
	 */
	private function _determine_post( $identifiers ) {
		$post = null;

		if ( isset( $identifiers['post'] ) && is_a( $identifiers['post'], 'WP_Post' ) ) {
			$post = $identifiers['post'];
		} else {
			if ( isset( $identifiers['url'] ) ) {
				$post_id = url_to_postid( $identifiers['url'] );
			}

			// 'id' can override 'url' because it is more specific.
			if ( isset( $identifiers['id'] ) ) {
				$post_id = $identifiers['id'];
			}

			// Get the post from the ID.
			if ( isset( $post_id ) && absint( $post_id ) > 0 ) {
				$post = get_post( absint( $post_id ) );
			}
		}

		return $post;
	}

	/**
	 * Get the term link pages for all terms associated with a post in a particular taxonomy.
	 *
	 * @since 1.0.0.
	 *
	 * @param  int    $post_id  Post ID.
	 * @param  string $taxonomy The taxonomy to look for associated terms.
	 * @return array                  The URLs for term pages associated with this post.
	 */
	public function locate_terms_urls( $post_id, $taxonomy ) {
		$terms   = get_the_terms( $post_id, $taxonomy );
		$related = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$link = get_term_link( $term, $taxonomy );

				if ( ! is_wp_error( $link ) ) {
					$related[] = $link;
					$this->set_related_url( $link, $taxonomy );
				}
			}
		}

		return $related;
	}

	/**
	 * Get author links related to this post.
	 *
	 * @since 1.0.0.
	 *
	 * @param  WP_Post $post The post object to search for related author information.
	 * @return array               The related author URLs.
	 */
	public function locate_author_urls( $post ) {
		$author = $post->post_author;

		$author_page = get_author_posts_url( $author );
		$author_feed = get_author_feed_link( $author );

		$this->set_related_url( $author_page, 'author' );
		$this->set_related_url( $author_feed, 'author' );

		return array(
			$author_page,
			$author_feed,
		);
	}

	/**
	 * Get the post type archives associated with the post.
	 *
	 * @since 1.0.0.
	 *
	 * @param  WP_Post $post The post object to search for post type information.
	 * @return array         The related post type archive URLs.
	 */
	public function locate_post_type_archive_url( $post ) {
		$related   = array();
		$post_type = get_post_type( $post );

		$post_type_page = get_post_type_archive_link( $post_type );
		$post_type_feed = get_post_type_archive_feed_link( $post_type );

		if ( false !== $post_type_page ) {
			$related[] = $post_type_page;
		}

		if ( false !== $post_type_feed ) {
			$related[] = $post_type_feed;
		}

		if ( ! empty( $related ) ) {
			$this->set_related_urls_by_category( $related, 'post-type-archive' );
		}

		return $related;
	}

	/**
	 * Get all of the feed URLs.
	 *
	 * @since 1.0.0.
	 *
	 * @param  WP_Post $post The post object to search for the feed information.
	 * @return array               The feed URLs.
	 */
	public function locate_feed_urls( $post ) {
		$feeds = array(
			get_bloginfo_rss( 'rdf_url' ),
			get_bloginfo_rss( 'rss_url' ),
			get_bloginfo_rss( 'rss2_url' ),
			get_bloginfo_rss( 'atom_url' ),
			get_bloginfo_rss( 'comments_rss2_url' ),
			get_post_comments_feed_link( $post->ID ),
		);

		$this->set_related_urls_by_category( $feeds, 'feed' );

		return $feeds;
	}

	/**
	 * Get the main URL.
	 *
	 * @since 1.0.0.
	 *
	 * @return string    The main URL.
	 */
	public function get_url() {
		return $this->_url;
	}

	/**
	 * Set the main URL.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $url The main URL.
	 * @return void
	 */
	public function set_url( $url ) {
		$this->_url = $url;
	}

	/**
	 * Get the main post ID.
	 *
	 * @since 1.0.0.
	 *
	 * @return int    The main post ID.
	 */
	public function get_post_id() {
		return $this->_post_id;
	}

	/**
	 * Set the main post ID.
	 *
	 * @since 1.0.0.
	 *
	 * @param  int $post_id The main post ID.
	 * @return void
	 */
	public function set_post_id( $post_id ) {
		$this->_post_id = $post_id;
	}

	/**
	 * Get the main post object.
	 *
	 * @since 1.0.0.
	 *
	 * @return WP_Post|null    The main post object.
	 */
	public function get_post() {
		return $this->_post;
	}

	/**
	 * Set the main post object.
	 *
	 * @since 1.0.0.
	 *
	 * @param  WP_Post $post The main post object.
	 * @return void
	 */
	public function set_post( $post ) {
		$this->_post = $post;
	}

	/**
	 * Get the related URLs.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $type The category of URL to get. All will be returned if this is left blank.
	 * @return array              The related URLs.
	 */
	public function get_related_urls( $type = '' ) {
		$urls = $this->_related_urls;

		if ( ! empty( $type ) && isset( $urls[ $type ] ) ) {
			$urls = $urls[ $type ];
		}

		return $urls;
	}

	/**
	 * Set the related URLs array.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array $urls The related URLs.
	 * @return void
	 */
	public function set_related_urls( $urls ) {
		$this->_related_urls = $urls;
	}

	/**
	 * Set a single related URL by type of URL.
	 *
	 * @since 1.0.0.
	 *
	 * @param  string $url  The url to add to the collection.
	 * @param  string $type The category to place the URL in.
	 * @return void
	 */
	public function set_related_url( $url, $type ) {
		$this->_related_urls[ $type ][] = $url;
	}

	/**
	 * Set a group of related URLs by type of URL.
	 *
	 * @since 1.0.0.
	 *
	 * @param  array  $urls The urls to add to the collection.
	 * @param  string $type The category to place the URLs in.
	 * @return void
	 */
	public function set_related_urls_by_category( $urls, $type ) {
		$this->_related_urls[ $type ] = $urls;
	}
}
