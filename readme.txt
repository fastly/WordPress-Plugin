=== Fastly ===
Contributors: fastly
Tags: fastly, cdn, performance, speed, spike, spike-protection, caching, dynamic, comments, ddos
Requires at least: 3.2
Tested up to: 4.5.2
Stable tag: trunk
License: GPLv2

Integrates Fastly with WordPress' publishing tools.

== Description ==

Take a look at the inline comments in the [code](https://github.com/fastly/WordPress-Plugin/tree/master/lib) for an in depth description. But, the plugin:

- Pulls in the [Fastly API](http://docs.fastly.com/api)
- Wires Instant Purging into the publishing process, keeping content up to date
- Includes an admin panel in `wp-admin`

Using this plugin means you won't have to purge content in Fastly when you make changes to your WordPress content. Purges will automatically happen with no need for manual intervention.

== Installation ==

You can either install from source (you're looking at it), or from the WordPress [plugin directory](http://wordpress.org/plugins/fastly/).

1. To proceed with configuration you will need to sign up for Fastly (at fastly.com/signup) and create and activate a new service. Details of how to create and activate a new service can be found at Fastly's documentation. You will also need to find your Service ID and make a note of the string. 
2. From Fastly's configuration interface, crete an API token with the default scope and access level. Make a note of the credential. 
3. Set up the Fastly plugin inside your WordPress admin panel
4. Once the plugin is installed into your WordPress instance, you will need to enter your API token and Service ID into the plugin's configuration page. 
5. That's it! Everything should just work. In order to route production traffic through Fastly, you will likely need to change some records with your domain registrar. Refer to Fastly's documentation for more instructions about which CNAME records to use. 
6. In order to get the most value out of Fastly, you should create a VCL Snippet on your Fastly service which allows you to define some custom logic for how the Fastly CDN should handle requests to your WordPress instance. For more information, or if you have any problems, please email us.

_Note: you may have to disable other caching plugins like W3TotalCache to avoid getting odd cache behaviour._

_Note: you can enable "soft" purging for urls. You can read more about soft purging [here](https://www.fastly.com/blog/introducing-soft-purge-more-efficient-way-mark-outdated-content) and [here](https://docs.fastly.com/guides/purging/soft-purges)._

== Prequisites ==

The server must have "php5-curl" installed on the server you are hosting Wordpress with e.g

  `sudo apt-get install php5-curl`

== Customization ==

If you need to edit/add/remove headers (for now only Surrogate-Control) outputed by the plugin, it can be done by hooking with wordpress add_action on tag "purgely_post_send_surrogate_control"
and "purgely_post_send_surrogate_control", and calling on object "unset_headers" function for removing headers, or "edit_headers" function for rewriting or adding new headers.

Example (add this to your theme function.php) :

add_action('purgely_pre_send_surrogate_control', 'custom_headers_edit');
function custom_headers_edit($header_object)
{
  $header_object->edit_headers(array('custom-header' => '555', 'max-age' => '99'));
}

== Screenshots ==

1. Configuration

== Changelog ==

= 1.1.1 =
* Code from purgely integrated into fastly
* Added ability to upload VCL snippets directly from the plugin
* Added webhooks support (Slack focused) to log purges and other critical events

= 1.1 = 
* Include fixes for header sending
* Enable "soft" purging

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
Copyright (C) 2011,2012,2013,2014,2015 Fastly.com

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

