<?php

/**
 * Collects Surrogate Keys related post.
 *
 * Attempts to find all Surrogate Keys that are related to an individual post.
 */
class Purgely_Related_Surrogate_Keys
{

    /**
     * The post ID from which relationships are determined.
     *
     * @var string The post ID from which relationships are determined.
     */
    var $_post_id = 0;

    /**
     * The WP_Post object from which relationships are determined.
     *
     * @var null|WP_Post The WP_Post object from which relationships are determined.
     */
    var $_post = null;

    /**
     * Collection of Surrogate Keys
     *
     * @var array
     */
    var $_collection = array();

    /**
     * Construct the object.
     *
     * @param  int $identifier - postID
     */
    public function __construct($identifier)
    {
        // Pull the post object from the $identifiers array and setup a standard post object.
        $this->set_post_id($identifier);
        $this->set_post(get_post($identifier));
        // Insert identifier
        $this->_collection[] = 'p-' . $identifier;
    }

    /**
     * Locate all of the URLs.
     *
     * @return array The related URLs.
     */
    public function locate_all()
    {
        // Collect and store keys
        $this->locate_surrogate_taxonomies($this->get_post_id());
        $this->locate_author_surrogate_key($this->get_post_id());
        $this->include_always_purged_types();

        return $this->_collection;
    }

    /**
     * Includes types that get purged always (for custom themes)
     */
    public function include_always_purged_types()
    {
        $always_purged = $this->get_always_purged_types();
        $this->_collection = array_merge($this->_collection, $always_purged);
    }

    /**
     * Fetches types that get purged always (for custom themes)
     *
     * TODO admin checkbox support for custom managing
     * @return array Keys that always get purged.
     */
    static public function get_always_purged_types()
    {
        return array(
            'tm-post',
            'tm-home',
            'tm-feed',
            'holos'
        );
    }

    /**
     * Get the term link pages for all terms associated with a post in a particular taxonomy.
     *
     * @param  int $post_id Post ID.
     */
    public function locate_surrogate_taxonomies($post_id)
    {

        $taxonomies = apply_filters('purgely_taxonomy_keys', (array)get_taxonomies());

        foreach ($taxonomies as $taxonomy) {
            $this->locate_surrogate_taxonomy_single($post_id, $taxonomy);
        }
    }

    /**
     * Locate single taxonomy terms for post_id
     *
     * @param $post_id
     * @param $taxonomy
     */
    public function locate_surrogate_taxonomy_single($post_id, $taxonomy)
    {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));

        if (is_array($terms)) {
            foreach ($terms as $term) {
                if ($term) {
                    $key = 't-' . $term;
                    $this->_collection[] = $key;
                }
            }
        }
    }

    /**
     * Get author key
     *
     * @param  int $post_id The post ID to search for related author information.
     */
    public function locate_author_surrogate_key($post_id)
    {

        if ($post = $this->get_post($post_id)) {
            $post->post_author;
            $key = 'a-' . absint($post->post_author);
            $this->_collection[] = $key;
        }
    }

    /**
     * Get the main post ID.
     *
     * @return int    The main post ID.
     */
    public function get_post_id()
    {
        return $this->_post_id;
    }

    /**
     * Set the main post ID.
     *
     * @param  int $post_id The main post ID.
     * @return void
     */
    public function set_post_id($post_id)
    {
        $this->_post_id = $post_id;
    }

    /**
     * Get the main post object.
     *
     * @return WP_Post|false    The main post object.
     */
    public function get_post()
    {
        if ($this->_post) {
            return $this->_post;
        }

        $post = get_post($this->get_post_id());

        if (!$post) {
            return false;
        } else {
            $this->set_post($post);
            return $post;
        }
    }

    /**
     * Set the main post object.
     *
     * @param  WP_Post $post The main post object.
     * @return void
     */
    public function set_post($post)
    {
        $this->_post = $post;
    }
}
