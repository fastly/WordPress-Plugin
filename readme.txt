=== Fastly ===
Contributors: Fastly, Inchoo, CondeNast
Tags: fastly, cdn, performance, speed, spike, spike-protection, caching, dynamic, comments, ddos
Requires at least: 4.6.2
Tested up to: 4.8
Stable tag: trunk
License: GPLv2

Integrates Fastly with WordPress publishing tools.

This is the official Fastly plugin for WordPress.

The official code repository for this plugin is available here:

https://github.com/fastly/WordPress-Plugin/

== Description ==
Usage:
1. To proceed with configuration you will need to sign up for Fastly (at fastly.com/signup) and create and activate a new service. Details of how to create and activate a new service can be found at Fastly's documentation. You will also need to find your Service ID and make a note of the string.
2. From Fastly's configuration interface, create an API token with the default scope and access level. Make a note of the credential. 
3. Set up the Fastly plugin inside your WordPress admin panel
4. Once the plugin is installed into your WordPress instance, you will need to enter your API token and Service ID into the plugin's configuration page. 
5. That's it! Everything should just work. In order to route production traffic through Fastly, you will likely need to change some records with your domain registrar. Refer to Fastly's documentation for more instructions about which CNAME records to use. 
6. In order to get the most value out of Fastly, you should create a number of VCL Snippets that let you define some custom logic for how the Fastly CDN should handle requests to your WordPress instance. You can add Snippets to your service from the side menu when editing the configuration of your Service version. These are the Snippets that you should create:
https://github.com/fastly/WordPress-Plugin/tree/master/vcl_snippets

For more information, or if you have any problems, please email us.

_Note: you may have to disable other caching plugins like W3TotalCache to avoid getting odd cache behaviour._

- Pulls in the [Fastly API](http://docs.fastly.com/api)
- Integrates purging in post/page/taxonomies publishing
- Includes an admin panel in `wp-admin`
- Integrates some of the advanced purging options from Fastly API
- Allows to monitor purging using webhooks for slack

Using this plugin means you won't have to purge content in Fastly when you make changes to your WordPress content. Purges will automatically happen with no need for manual intervention.

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
1. Register new Service with your domain and obtain API token and Service ID [https://manage.fastly.com/account/personal/tokens]
2. Deploy the new Version of the Service.
3. In your Wordpress blog admin panel, Under Fastly->General, enter & save your Fastly API token and Service ID
4. Verify connection by pressing `TEST CONNECTION` button.
5. If connection is ok, press `Update VCL` button
6. That's it! Everything should just work. :metal: If you have any problems, email us.

Note: you may have to disable other caching plugins like W3TotalCache to avoid getting odd cache behaviour.

== Screenshots ==
1. Fastly General Tab
2. Fastly Advanced Tab
3. Fastly Webhooks Tab

== Changelog ==

= 1.2.0 =
* Added purge by url
* Changes regarding logging logic
* VCL update User Interface changes
* Fixed and enabled support for wp_cli

= 1.1.1 =
* Some Purgely plugin functionalities integrated into Fastly (along with some advanced options)
* Purging by Surrogate-Keys is used instead of purging by url
* Added webhooks support (Slack focused) to log purges and other critical events
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

== About Fastly ==

Fastly is the only real-time content delivery network designed to seamlessly integrate with your development stack.

Fastly provides real-time updating of content and the ability to cache dynamic as well as static content. For any content that is truly uncacheable, we'll accelerate it.

In addition we allow you to update your configuration in seconds, provide real time log and stats streaming, powerful edge scripting capabilities, and TLS termination (amongst many other features).

== License ==

Fastly.com WordPress Plugin
Copyright (C) 2011,2012,2013,2014,2015,2016,2017 Fastly.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

== Upgrade Notice ==
Additional features with improvements in purging precision and Fastly API options
