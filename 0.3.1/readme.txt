=== Google Calendar Events ===
Contributors: rosshanney
Tags: google, google calendar, calendar, event, events, ajax, widget
Requires at least: 2.9.2
Tested up to: 3.0
Stable tag: 0.3.1

Parses Google Calendar feeds and displays the events as a calendar grid or list on a page, post or widget.

== Description ==

Parses Google Calendar feeds and displays the events as a calendar grid or list on a page, post or widget.

= Features =

* Parses Google Calendar feeds to extract events
* Displays events as a list or within a calendar grid
* Events from multiple Google Calendar feeds can be shown in a single list / grid
* Lists and grids can be displayed in posts, pages or within a widget
* Options to change the number of events retrieved, date / time format, cache duration etc.
* Options to change the information displayed (start time, location, description etc.).
* Calendar grids can have the ability to change month, utilising AJAX

Anyone upgrading from an earlier version should note that the 'Display title?' option has been moved from the feed settings to the widget / shortcode settings. You will need to re-enter any titles you specified. Visit the 0.3 features link below for more information.

Please visit the plugin homepage for how to get started and other help:

* [Plugin Homepage](http://www.rhanney.co.uk/plugins/google-calendar-events)

There is also a demonstration page showing the plugin in action:

* [Demo Page](http://www.rhanney.co.uk/plugins/google-calendar-events/gce-demo)

For a summary of the new features in 0.3, visit:

* [0.3 Features](http://www.rhanney.co.uk/2010/07/26/google-calendar-events-0-3)

== Installation ==

Use the automatic installer from within the WordPress administration, or:

1. Download the `.zip` file by clicking on the Download button on the right
1. Unzip the file
1. Upload the `google-calendar-events` directory to your `plugins` directory
1. Go to the Plugins page from within the WordPress administration
1. Click Activate for Google Calendar Events

After activation a new Google Calendar Events options menu will appear under Settings.

You can now start adding feeds. Visit the [plugin homepage](http://www.rhanney.co.uk/plugins/google-calendar-events) for a more in-depth guide on getting started.

== Screenshots ==

1. The main plugin admin screen.
1. The add feed admin screen.
1. A page showing a full page calendar grid and various widgets.

== Changelog ==

= 0.3.1 =
* l10n / i18n fixes. Dates should now be localized correctly and should maintain localization after an AJAX request
* MU / Multi-site issues. Issues preventing adding of feeds have been addressed

= 0.3 =
* Now allows events from multiple Google Calendar feeds to be displayed on a single calendar grid / list
* Internationalization support added

= 0.2.1 =
* Added option to allow 'More details' links to open in new window / tab.
* Added option to choose a specific timezone for each feed
* Line breaks in an event description will now be preserved
* Fixed a bug casing the title to not be displayed on lists
* Other minor bug fixes

= 0.2 =
* Added customization options for how information is displayed.
* Can now display: start time, end time and date, location, description and event link.
* Tooltips now using qTip jQuery plugin.

= 0.1.4 =
* More bug fixes.

= 0.1.3 =
* Several bug fixes, including fixing JavaScript problems that prevented tooltips appearing.

= 0.1.2 =
* Bug fixes.

= 0.1.1 =
* Fix to prevent conflicts with other plugins.
* Changes to readme.txt.

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.3 =
Fixes for internationalization / localization and MU / multi-site.

== Frequently Asked Questions ==

Please visit the [plugin homepage](http://www.rhanney.co.uk/plugins/google-calendar-events) and leave a comment for help, or [contact me](http://www.rhanney.co.uk/contact) directly.