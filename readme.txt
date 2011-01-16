=== Google Calendar Events ===
Contributors: rosshanney
Donate link: http://www.rhanney.co.uk/plugins/google-calendar-events/#donate
Tags: google, google calendar, calendar, event, events, ajax, widget
Requires at least: 2.9.2
Tested up to: 3.1
Stable tag: 0.4.1

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
* Calendar grids can have the ability to change the month displayed, utilising AJAX

Please visit the plugin homepage for how to get started and other help:

* [Plugin Homepage](http://www.rhanney.co.uk/plugins/google-calendar-events)

There is also a demonstration page showing the plugin in action:

* [Demo Page](http://www.rhanney.co.uk/plugins/google-calendar-events/gce-demo)

For those upgrading to 0.4.1; there are a few slight changes to be aware of:

* [0.4.1 Changes](http://www.rhanney.co.uk/2011/01/16/google-calendar-events-0-4-1)

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

= 0.4.1 =

* Fix / workaround for the long-running timezone bug. Please take a look at [this](http://www.rhanney.co.uk/2011/01/16/google-calendar-events-0-4-1) for more information.
* Added additional 'Maximum no. events to display' option to widget / shortcode (mainly to address a further issue caused by the above fix)
* i18n related bug fix
* Added support for widget_title filter (courtesy of [James](http://lunasea-studios.com))
* Added Hungarian (hu_HU) translation ([Takács Dániel](http://ek.klog.hu))
* Now using minified version of jQuery qTip script

= 0.4 =
* More control over how start and end dates / times are displayed
* Events can now be limited to a specified timeframe (number of days)
* Events on the same day in lists can now be shown under a single date title
* JavaScript can now be added to the footer rather than the header, via an option
* The 'Loading...' text can now be customized
* Description text can now be limited to a specified number of words
* Multi-day events can be shown on each day that they span ([sort of](http://www.rhanney.co.uk/2010/08/19/google-calendar-events-0-4#multiday))
* Bug fixes
* i18n / l10n fixes

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

= 0.4.1 =
Bug fixes.

== Frequently Asked Questions ==

Please visit the [plugin homepage](http://www.rhanney.co.uk/plugins/google-calendar-events) and leave a comment for help, or [contact me](http://www.rhanney.co.uk/contact) directly.