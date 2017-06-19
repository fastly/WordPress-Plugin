<?php
/*
Plugin Name: Fastly
Plugin URI: http://fastly.com/
Description: Configuration and cache purging for the Fastly CDN.
Authors: Zack Tollman (github.com/tollmanz), WIRED Tech Team (github.com/CondeNast) & Fastly
Version: 1.2.0
Author URI: http://fastly.com/
*/

/**
 * Singleton for kicking off functionality for this plugin.
 */
class Purgely
{
    /**
     * The one instance of Purgely.
     *
     * @var Purgely
     */
    private static $instance;

    /**
     * The Purgely_Surrogate_Key object to manage Surrogate Keys.
     *
     * @var Purgely_Surrogate_Keys_Header    The Purgely_Surrogate_Key object to manage Surrogate Keys.
     */
    private static $surrogate_keys_header;

    /**
     * The Purgely_Surrogate_Control_Header to manage the TTL.
     *
     * @var Purgely_Surrogate_Control_Header    The Purgely_Surrogate_Control_Header to manage the TTL.
     */
    private static $surrogate_control_header;

    /**
     * An array of cache control headers to manage the caching behavior.
     *
     * @var Purgely_Cache_Control_Header[]    An array of cache control headers to manage the caching behavior.
     */
    private static $cache_control_headers = array();

    /**
     * Last plugin version.
     * Increment when making schema and code changes. Also change version in comment above class.
     * If there are schema changes, create function in upgrades class and increment.
     * If just code changes, only increment.
     *
     * @var   string    Plugin version.
     */
    var $version = '1.2.0';

    /**
     * Currently installed plugin version.
     *
     * @var   string    Currently installed plugin version number.
     */
    var $current_version = null;

    /**
     * Last vcl version.
     *
     * @var   string    Last updated vcl version. Increment when making vcl changes
     */
    var $vcl_last_version = '1.1.1';

    /**
     * File path to the plugin dir (e.g., /var/www/mysite/wp-content/plugins/purgely).
     *
     * @var   string    Path to the root of this plugin.
     */
    var $root_dir = '';

    /**
     * File path to the plugin src files (e.g., /var/www/mysite/wp-content/plugins/purgely/src).
     *
     * @var   string    Path to the root of this plugin.
     */
    var $src_dir = '';

    /**
     * File path to the plugin main file (e.g., /var/www/mysite/wp-content/plugins/mixed-content-detector/purgely.php).
     *
     * @var   string    Path to the plugin's main file.
     */
    var $file_path = '';

    /**
     * The URI base for the plugin (e.g., http://domain.com/wp-content/plugins/purgely).
     *
     * @var   string    The URI base for the plugin.
     */
    var $url_base = '';

    /**
     * Instantiate or return the one Purgely instance.
     *
     * @return Purgely
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initiate actions.
     *
     * @return Purgely
     */
    public function __construct()
    {
        // Set the main paths for the plugin.
        $this->root_dir = dirname(__FILE__);
        $this->src_dir = $this->root_dir . '/src';
        $this->vcl_dir = $this->root_dir . '/vcl_snippets';
        $this->file_path = $this->root_dir . '/' . basename(__FILE__);
        $this->url_base = untrailingslashit(plugins_url('/', __FILE__));
        $this->current_version = get_option("fastly-schema-version", false);

        // Include dependent files.
        include $this->src_dir . '/config.php';
        include $this->src_dir . '/utils.php';
        include $this->src_dir . '/classes/settings.php';
        include $this->src_dir . '/classes/upgrades.php';
        include $this->src_dir . '/classes/vcl-handler.php';
        include $this->src_dir . '/classes/related-surrogate-keys.php';
        include $this->src_dir . '/classes/purge-request.php';
        include $this->src_dir . '/classes/surrogate-key-collection.php';
        include $this->src_dir . '/classes/header.php';
        include $this->src_dir . '/classes/header-surrogate-control.php';
        include $this->src_dir . '/classes/header-cache-control.php';
        include $this->src_dir . '/classes/header-surrogate-keys.php';

        if (is_admin()) {
            include $this->src_dir . '/settings-page.php';
        }

        // Handle all automatic purges.
        include $this->src_dir . '/wp-purges.php';

        // First install DB schema changes
        $upgrades = new Upgrades($this);
        $upgrades->check_and_run_upgrades();

        // Initialize the key collector.
        $this::$surrogate_keys_header = new Purgely_Surrogate_Keys_Header();

        // Initialize cache control header.
        $this::$cache_control_headers = new Purgely_Cache_Control_Header();

        // Initialize the surrogate control header.
        $this::$surrogate_control_header = new Purgely_Surrogate_Control_Header();

        // Add the surrogate keys.
        add_action('wp', array($this, 'set_standard_keys'), 100);

        // Send the surrogate keys.
        add_action('wp', array($this, 'send_surrogate_keys'), 101);

        // Set and send the surrogate control header.
        add_action('wp', array($this, 'send_surrogate_control'), 101);

        // Set and send the surrogate control header.
        add_action('wp', array($this, 'send_cache_control'), 101);

        // Load in WP CLI.
        if (defined('WP_CLI') && WP_CLI) {
            include $this->src_dir . '/wp-cli.php';
        }

        // Set plugin url
        if (!defined('FASTLY_PLUGIN_URL')) {
            define('FASTLY_PLUGIN_URL', plugin_dir_url(__FILE__));
        }

        // Set version
        if (!defined('FASTLY_VERSION')) {
            define('FASTLY_VERSION', $this->version);
        }

        // Load the textdomain.
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    /**
     * Set all the surrogate keys for the requests.
     *
     * @return void
     */
    public function set_standard_keys()
    {

        if (is_user_logged_in()) {
            return;
        }

        global $wp_query;
        $key_collection = new Purgely_Surrogate_Key_Collection($wp_query);
        $keys = $key_collection->get_keys();

        $this::$surrogate_keys_header->add_keys($keys);
    }

    /**
     * Send the currently registered surrogate keys.
     *
     * This function takes all of the surrogate keys that are currently recorded and flattens them into a single header
     * and sends the header. Any other keys need to be set by 3rd party code before "init", 101.
     *
     * This function does allow for a filtering of the keys before they are sent, to allow for the keys to be
     * de-registered when and if necessary.
     *
     * @return void
     */
    public function send_surrogate_keys()
    {

        if (is_user_logged_in()) {
            return;
        }

        $keys_header = $this::$surrogate_keys_header;
        $keys = apply_filters('purgely_surrogate_keys', $keys_header->get_keys());

        do_action('purgely_pre_send_keys', $keys);

        $this::$surrogate_keys_header->set_keys($keys);
        $this::$surrogate_keys_header->send_header();

        do_action('purgely_post_send_keys', $keys);
    }

    /**
     * Set the TTL for the object and send the header.
     *
     * This is the main function for setting the TTL for the page.
     * To change it, use the "purgely_pre_send_surrogate_control" and "purgely_post_send_surrogate_control"
     * actions.
     *
     * Note that any alterations must be done before init, 101.
     *
     * The default set here is 5 minutes. This has proven to be a reasonable default for caches for WordPress pages.
     *
     * @return void
     */
    public function send_surrogate_control()
    {
        /**
         * If a user is logged in, surrogate control headers should be ignored. We do not want to cache any logged in
         * user views. WordPress sets a "Cache-Control:no-cache, must-revalidate, max-age=0" header for logged in views
         * and this should be sufficient for keeping logged in views uncached.
         */
        if (is_user_logged_in()) {
            return;
        }

        $surrogate_control = $this::$surrogate_control_header;

        do_action('purgely_pre_send_surrogate_control', $surrogate_control);
        $surrogate_control->send_header();
        do_action('purgely_post_send_surrogate_control', $surrogate_control);
    }

    /**
     * Send each of the control control headers.
     *
     * @return void
     */
    public function send_cache_control()
    {
        /**
         * If a user is logged in, surrogate control headers should be ignored. We do not want to cache any logged in
         * user views. WordPress sets a "Cache-Control:no-cache, must-revalidate, max-age=0" header for logged in views
         * and this should be sufficient for keeping logged in views uncached.
         */
        if (is_user_logged_in()) {
            return;
        }

        $cache_control = $this::$cache_control_headers;

        do_action('purgely_pre_send_cache_control', $cache_control);
        $cache_control->send_header();
        do_action('purgely_post_send_cache_control', $cache_control);
    }

    /**
     * Load the plugin text domain.
     *
     * @return void
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain('purgely', false, basename(dirname(__FILE__)) . '/languages/');
    }
}

/**
 * Instantiate or return the one Purgely instance.
 *
 * @return Purgely
 */
function get_purgely_instance()
{
    return Purgely::instance();
}

get_purgely_instance();
