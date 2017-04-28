<?php
/**
 * Singleton for registering default WP purges.
 */

class Purgely_Purges {
	/**
	 * The one instance of Purgely_Purges.
	 *
	 * @var Purgely_Purges
	 */
	private static $instance;

	/**
	 * Instantiate or return the one Purgely_Purges instance.
	 *
	 * @return Purgely_Purges
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initiate actions.
	 *
	 * @return Purgely_Purges
	 */
	public function __construct() {
		foreach ( $this->_purge_actions() as $action ) {
		    add_action( $action, array( $this, 'purge' ), 10, 1 );
		}
	}

	/**
	 * Callback for post changing events to purge URLs.
	 *
	 * @param  int $post_id Post ID.
	 * @return void
	 */
	public function purge( $post_id , $argsss) {
		if ( ! in_array( get_post_status( $post_id ), array( 'publish', 'trash' ) ) ) {
			return;
		}

        $related_collection_object = new Purgely_Related_Surrogate_Keys( $post_id );
        $collection = $related_collection_object->locate_all();

        purgely_purge_surrogate_key_collection( $collection );
	}

	/**
	 * A list of actions to purge URLs.
	 *
	 * @return array    List of actions.
	 */
	private function _purge_actions() {
		return array(
			'save_post',
			'deleted_post',
			'trashed_post',
			'delete_attachment',
		);
	}
}

/**
 * Instantiate or return the one Purgely_Purges instance.
 *
 * @return Purgely_Purges
 */
function get_purgely_purges_instance() {
	return Purgely_Purges::instance();
}

get_purgely_purges_instance();