=== Fastly ===
Contributors: Fastly, Inchoo
Tags: fastly, cdn, performance, speed, spike, spike-protection, caching, dynamic, comments, ddos
Requires at least: 4.6.2
Tested up to: 4.7.5
Stable tag: trunk
License: GPLv2

Integrates Fastly with WordPress publishing tools.

This is the official Fastly plugin for WordPress.

The official code repository for this plugin is available here:

https://github.com/fastly/WordPress-Plugin/

== Description ==
Usage:

- Pulls in the [Fastly API](http://docs.fastly.com/api)
- Integrates purging in post/page/taxonomies publishing
- Includes an admin panel in `wp-admin`
- Integrates some of the advanced purging options from Fastly API
- Allows to monitor purging using webhooks for slack

Using this plugin means you won\'t have to purge content in Fastly when you make changes to your WordPress content. Purges will automatically happen with no need for manual intervention.

Customization:

Available wordpress hooks (add_action) on:

Editing purging keys output
 purgely_pre_send_keys
 purgely_post_send_keys
    functions: add_keys

Editing surrogate control headers output(max-age, stale-while-revalidate, stale-if-error)
 purgely_pre_send_surrogate_control
 purgely_post_send_surrogate_control
    functions: edit_headers, unset_headers

Edit cache control headers output (max-age)
 purgely_pre_send_cache_control
 purgely_post_send_cache_control
    functions: edit_headers, unset_headers

Example:
add_action(\'purgely_pre_send_surrogate_control\', \'custom_headers_edit\');
function custom_headers_edit($header_object)
{
  $header_object->edit_headers(array(\'custom-header\' => \'555\', \'max-age\' => \'99\'));
}

== Installation ==
You can either install from source (you\'re looking at it), or from the WordPress [plugin directory](http://wordpress.org/plugins/fastly/).

0. Register on `https://www.fastly.com/signup`
1. Register new Service with your domain and obtain API token and Service ID
2. Deploy the new Version of the Service.
3. In your Wordpress blog admin panel, Under Fastly->General, enter & save your Fastly API token and Service ID
4. Verify connection by pressing `TEST CONNECTION` button.
5. If connection is ok, press `Update VCL` button
6. That\'s it! Everything should just work. :metal: If you have any problems, email us.

Note: you may have to disable other caching plugins like W3TotalCache to avoid getting odd cache behaviour.

== Screenshots ==
1. Fastly General Tab
2. Fastly Advanced Tab
3. Fastly Webhooks Tab

== Changelog ==
= 1.1.1 =
* Some Purgely plugin functionalities integrated into Fastly (along with some advanced options)
* Purging by Surrogate-Keys is used instead of purging by url
* Integrated webhooks for slack (monitoring for purges and vcl updates)
* Added debugging logs option, purge all button for emergency
* Advanced options: Surrogate Cache TTL, Cache TTL, Default Purge Type, Allow Full Cache Purges, Log purges in error log,
Debug mode, Enable Stale while Revalidate, Stale while Revalidate TTL, Enable Stale if Error, Stale if Error TTL.
* Fastly VCL update
* Curl no longer needed

= 1.1 =
* Include fixes for header sending
* Enable \"soft\" purging

= 1.0 =
* Mark as deprecated
* Recommend Purgely from Condé Nast
* Add in link to GitHub repo

= 0.99 =
* Add a guard function for cURL prequisite
* Bring up to date with WP Plugin repo standards

= 0.98 =
* Security fixes for XSS/CSRF
* Only load CSS/JS on admin page
* Properly enqueue scripts and styles
* Use WP HTTP API methods
* Properly register scripts

= 0.94 =
* Change to using PURGE not POST for purges
* Correct URL building for comments purger

= 0.92 =
* Fix bug in port addition

= 0.91 =
* Make work in PHP 5.3

= 0.9 =
* Fix comment purging

= 0.8 =
* Fix url purging

= 0.7 =
* Fix category purging

= 0.6 =
* Remove bogus error_log call

= 0.5 =
* Switch to using curl
* Change PURGE methodology
* Performance enhancements

== Upgrade Notice ==
Additional features with improvements in purging precision and Fastly API options