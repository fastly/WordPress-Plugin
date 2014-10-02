<?php
/*
    Plugin Name: Fastly
    Version: 1.0.0
    Plugin URI: https://github.com/fastly/WordPress-Plugin
    Description: Configuration and cache purging for the Fastly CDN.
    Author: Fastly
    Author URI: http://www.fastly.com/
    Text-Domain: fastly
*/

// Basic plugin definitions
define('FASTLY_VERSION', '1.0.0');
define('FASTLY_PLUGIN_URL', plugin_dir_url( __FILE__ ));

// Includes
include_once dirname( __FILE__ ) . '/lib/purge.php';
include_once dirname( __FILE__ ) . '/lib/admin.php';
include_once dirname( __FILE__ ) . '/lib/api.php';

// Check for JSON support
if (!function_exists('json_decode')) {
  require_once dirname( __FILE__ ) . '/lib/JSON.php';
  define('FASTLY_JSON', false);
}

// Plugin Options
add_option('fastly_hostname', '');
add_option('fastly_api_key', '');
add_option('fastly_service_id', '');
add_option('fastly_api_hostname', 'https://api.fastly.com');
add_option('fastly_api_port', null);
add_option('fastly_page', 'welcome');
add_option('fastly_log_purges', '0');

// Setup Purging
new FastlyPurge();

// Setup admin (if needed)
if (is_admin()) {
  new FastlyAdmin();
}

// Custom action links for the plugin.
function fastly_action_links($links, $file) {
  static $this_plugin;
  if (!$this_plugin) {
    $this_plugin = plugin_basename(__FILE__);
  }
  if ($file == $this_plugin) {
    $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=fastly-admin-panel">Settings</a>';
    array_unshift($links, $settings_link);
  }
  return $links;
}
add_filter('plugin_action_links', 'fastly_action_links', 10, 2);

?>
