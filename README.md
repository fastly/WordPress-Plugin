# The Wordpress Plugin

Integrates Fastly with Wordpress' publishing tools.

## Installation

You can either install from source (you're looking at it), or from the Wordpress [plugin directory](http://wordpress.org/plugins/fastly/).

0. If you don't already have it send us a support request asking to have the Wordpress feature turned on for your account.
1. Add a new WordPress config to a Service and set up the path to the Wordpress install. Examples:
  - If your blog is at `http://blog.example.com/`, your path is `/`
  - If your blog is at `http://example.com/blog/`, your path is `/blog/`
2. Deploy the new Version of the Service.
3. With your API key and the Service id in hand, install the plugin under Wordpress.
4. Set up the Fastly plugin inside your Wordpress config panel - you should just have to input the API key and the Service id that you noted in the last step.
5. That's it! Everything should just work. :metal: If you have any problems, email us.

_Note: you may have to disable other caching plugins like W3TotalCache to avoid getting odd cache behaviour._

## What's going on?

Take a look at the inline comments in the [code](https://github.com/fastly/WordPress-Plugin/tree/master/lib) for an in depth description. But, the plugin:

- Pulls in the [Fastly API](http://docs.fastly.com/api)
- Wires Instant Purging into the publishing process, keeping content up to date
- Includes an admin panel in `wp-admin`

## License

Fastly.com WordPress Plugin
Copyright (C) 2011,2012,2013 Fastly.com

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

