=== What's My Status? ===
Contributors: haliphax
Donate link: 
Tags: statusnet, identi.ca, twitter, feed, social, widget
Requires at least: 2.8
Tested up to: 3.3
Stable tag: 1.2

This plugin provides a widget to display the feed of a given user's posts on identi.ca, Twitter, or any service that offers a Twitter-like API.

== Description ==

The plugin currently offers caching and multi-widget functionality (so that the widget can be placed on a page multiple times, each tracking a different status feed with a separate set of configuration values).

Future versions will include more out-of-the-box service options, status feed filters (to selectively include/exclude status posts containing a given keyword/phrase), and more!

== Screenshots ==

1. Widget
2. Sidebar
3. Rendering

== Installation ==

1. Upload the full directory into your wp-content/plugins directory
2. Activate the plugin at the plugin administration page
3. Drag the new widget into the desired placeholder under Appearance -> Widgets
4. Set your instance configuration values

== Configuration ==

For each instance of the widget, different configuration values can be specified. For the time being only [identi.ca](http://identi.ca) and [Twitter](http://twitter.com) are supported out-of-the-box, but any service that provides a Twitter-like API can be fed to the widget.

You may set the widget's title (which will be linked to your identi.ca profile), the screen name whose feed you will be polling, the GMT offset of your desired location (in minutes), the number of posts to pull (0 for the maximum), and the number of minutes to cache the status (0 for the maximum). As of v1.2, you may also choose to exclude replies and re-posts.

Note: You may customize the styles for the status feed's time stamps, etc., by modifying the styles.css file in the plugin's directory. Future versions will allow for custom CSS to be configured right in the widget options form.

== Frequently Asked Questions ==

= Why doesn't the title properly link to my profile page? =
Earlier versions of the widget had a bug which would not correctly link up identi.ca profiles in the widget title. To fix this, simply set the widget to use a different network (i.e., Twitter), switch it back to identi.ca, and then save your changes.

= Why is it pulling fewer status posts than I've configured it to pull? =
Either you literally don't have enough statuses to fill the queue (i.e., a new account with the service), or you have deleted some. Twitter, and possibly other services, will include a deleted status post in the count (though it is not included in the data).

== Upgrade Notice ==

= 1.1 = 
No considerations need to be made if you are upgrading to v1.2 from v1.1.

= 1.0.4 =
If your status had not been pulling, you should use the "Reset cache" option in the widget settings.

= 1.0.3 =
No considerations need to be made if you are upgrading to v1.0.4 from v1.0.3.

= 1.0.2 =
Make sure that you change your network from identi.ca to Twitter and then back to identi.ca in order to fix the profile link bug.

= 1.0.1 =
Make sure that you change your network from identi.ca to Twitter and then back to identi.ca in order to fix the profile link bug.

= 1.0.0 =
Make sure that you change your network from identi.ca to Twitter and then back to identi.ca in order to fix the profile link bug.

== Changelog ==

= 1.2 =
Added widget configuration options for excluding replies and re-posts.

= 1.1 =
Fixed "Reset cache" command in widget options and added support for curl with URL-based fopen as the fallback method.

= 1.0.4 =
The Twitter API URL was no longer functioning properly, and so it was reconfigured.

= 1.0.3 =
A bug that prevented the widget from properly linking up identi.ca profiles in the widget title has been fixed. In order to correct the previous behavior, though, you must manually set your feed network to something other than identi.ca, then back to identi.ca, and save your changes.

Additionally, the GPLv2 license in the readme.txt file has been updated to GPLv3 to match the license in the source code.

= 1.0.2 =
PHP "short tags" have been converted to standard echo statements so that the short_open_tag php.ini setting should not break the widget.

= 1.0.1 =
Caching has been re-engineered to be more intelligent. Also, the option to clear an instance of the widget's cache has been added to the widget configuration menu.

= 1.0.0 =
First version released!

== License ==

Copyright 2011 Todd Boyd

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

