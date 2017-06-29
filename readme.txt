=== Orphans ===
Contributors: iworks
Donate link: http://iworks.pl/donate/sierotki.php
Tags: sierotka, sierotki, spójniki, twarda spacja
Requires at least: 3.6
Tested up to: 4.8.0
Stable tag: 2.7.0

Plugin supports some of the grammatical rules of the Polish language.

== Description ==

Plugin fix orphans position and replace space after orphan to hard space, to avoid line break incorrect position.

= Asset image =

[Manuscript by Muffet, on Flickr](http://www.flickr.com/photos/calliope/306564541/)

== Installation ==

There are 3 ways to install this plugin:

= The super easy way =


1. **Log in** to your WordPress Admin panel.
1. **Go to: Plugins > Add New.**
1. **Type** ‘orphans’ into the Search Plugins field and hit Enter. Once found, you can view details such as the point release, rating and description.
1. **Click** Install Now. After clicking the link, you’ll be asked if you’re sure you want to install the plugin.
1. **Click** Yes, and WordPress completes the installation.
1. **Activate** the plugin.
1. A new menu `Orphans` in `Appearance` will appear in your Admin Menu.

***

= The easy way =

1. Download the plugin (.zip file) on the right column of this page
1. In your Admin, go to menu Plugins > Add
1. Select button `Upload Plugin`
1. Upload the .zip file you just downloaded
1. Activate the plugin
1. A new menu `Orphans` in `Appearance` will appear in your Admin Menu.

***

= The old and reliable way (FTP) =

1. Upload `sierotki` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. A new menu `Orphans` in `Appearance` will appear in your Admin Menu.

== Frequently Asked Questions ==

= When this plugin replace spaces? =

Plugin works when viewing the content and does not modify your content.

= How to use this plugin on custom field? =

Use this code:

`
if ( class_exists( 'iworks_orphan' ) {
    $orphan = new iworks_orphan();
    echo $orphan->replace( get_post_meta($post_id, 'meta_key', true ) );
}
`

= How to use this plugin on any string? =

Use this code:

`
if ( class_exists( 'iworks_orphan' ) {
    $orphan = new iworks_orphan();
    echo $orphan->replace( 'any_string' );
}
`

= How to change plugin capability? =

By default to use this plugin you must have `manage_options` capability, that usually mean site administrator. If you want to allow manage Orphans by "Editors" then you need to use other capability, e.g.  `unfiltered_html`. You can use `iworks_orphans_capability` filter:

`
add_filter('iworks_orphans_capability', 'my_orphans_capability');
function my_orphans_capability($capability) {
    return 'unfiltered_html';
}
`
== Screenshots ==

1. Options for entries.
1. Options for widgets.
1. Options for taxonomies.
1. Miscellaneous options.

== Changelog ==

= 2.7.0 - 2017-06-29 =

* Added custom fields - now you can easily add custom field name to the configuration.
* Added words: 'albo', 'bez', 'czy', 'lecz', 'nie', 'niech', 'przez', 'tak', 'tylko', 'więc' base on [Sierotka](https://pl.wikipedia.org/wiki/Sierotka_(typografia))

= 2.6.9 - 2017-05-24 =

* Fixed a problem with class declaration. Props for [gierand](https://wordpress.org/support/users/gierand/)

= 2.6.8 - 2017-05-23 =

* Added author description to replacements.
* Added a taxonomies title and description to replacements.
* Fixed a problem with preg_replace() "Compilation failed: range out of order".
* Use [WordPress Options Class](https://github.com/iworks/wordpress-options-class) to handle new options screen.

= 2.6.7 - 2017-05-09 =

* Allow to apply replacement to all languages by using filter `iworks_orphan_apply_to_all_languages' set on true.
* Fixed rate module.

= 2.6.6.1 - 2017-04-30 =

* Fixed wrong regexp replacement.

= 2.6.6 - 2017-04-30 =

* Avoid to replace  "script" and "styles" content. Props for [m1nified](https://profiles.wordpress.org/m1nified/)
* Added a widget title to replacements.
* Added a widget text to replacements (it work only for the widget text).

= 2.6.5 - 2016-11-01 =

* Rollback to 2.6.3, because 2.6.4 broke images.

= 2.6.4 - 2016-11-01 =

* Added symbols, like FX-10, F-800, F-600-K3-Z. "-" is replaced by &amp;#8209; char. Props for [Marcin](https://profiles.wordpress.org/mkazmierczak)

= 2.6.3 - 2016-10-25 =

* Implement WP syntax rules for PHP files.
* Added "Call for ratings" on settings page.

= 2.6.2 - 2016-02-27 =

* Fixed a problem with ignored option for numbers. Props for [Kacper](https://profiles.wordpress.org/alento/)
* Added check site or entry language to avoid replacing if language is other then Polish. It is one exception: numbers.

= 2.6.1 - 2016-01-11 =

* Fixed a problem with non-breakable space. Replace space after number to space between numbers.

= 2.6 - 2016-01-09 =

* Change language domain from `iworks_orphan` to plugin name `sierotki' to be compatible with i18n WordPress rules.
* Added activate plugin hook to change options autoload status.
* Added deactivate plugin hook to change options autoload status.
* Added filter `iworks_orphans_capability`, Props for Cezary Buliszak. 
* Added non-breakable space after numbers.
* Added uninstall plugin hook.
* Update screenshots.

= 2.5 - 2015-11-06 =

* IMPROVEMENT: added filter `iworks_orphan_replace` 

= 2.4 - 2015-02-12 =

* Added hard space between number (year) and polish year shortcut "r."
* Added WooCommerce product title and short description to available options. Props for [Dominik Kawula](https://www.facebook.com/dominik.kawula)

= 2.3.2 - 2014-09-12 =

* Fixed error in options array

= 2.3.1 - 2014-09-12 =

* Checked option array for non existing hash.
* Updated screenshots.

= 2.3 - 2014-07-10 =

* IMPROVEMENT: added all forms of word number

= 2.2 - 2014-01-24 =

* IMPROVEMENT:added links to forum
* IMPROVEMENT:checked capability with WP 3.8

= 2.1 - 2013-11-09 =

* IMPROVEMENT:checked capability with WP 3.6
* REFACTORING: implement PSR-0 rules to orphan class

= 2.0.2 - 2013-08-20 =

* BUGFIX: fixed replacement for single letter orphan after orphan. Props for [Szymon Skulimowski](http://wpninja.pl/autorzy/szymon-skulimowski/)
* IMPROVEMENT:checked capability with WP 3.6
* IMPROVEMENT:added help and related section

= 2.0.1 - 2013-07-10 =

* IMPROVEMENT:added numbers

= 2.0 - 2012-08-12 =

* BUGFIX: fixed permissions to configuration page
* BUGFIX: fixed replacement for strings starting with a orphan
* REFACTORING: rewrite code to the class
* IMPROVEMENT:added some shorts of academic degree
* IMPROVEMENT:massive increase orphans dictionary. Props for [adpawl](http://podbabiogorze.info.pl)

= 1.4.2 - 2012-03-02 =

* NEW: added the_title filter.

= 1.4.1 - 2011-02-24 =

* NEW: Trim chars.
* BUGFIX: Fixed multi coma use.

= 1.4 - 2011-02-24 =

* NEW: Added user defined orphans.
* BUGFIX: Corrected capability name.

= 1.3 - 2011-02-19 =

* NEW: Added option page to turn on/off filtering in content, excerpt or comments.
* NEW: Added "(" as char before a orphan.

= 1.2 - 2011-02-18 =

* NEW: Added filter comment_text.
* BUGFIX: Capital letters was missing by plugin.

= 1.1 - 2011-02-17 =

* Abandoning elegant anonymous function, which requires PHP 5.3.0 :(
* NEW: Added filter to the_excerpt.

= 1.0.2 - 2011-02-17 =

* NEW: Added ">" as char before a orphan.

= 1.0.1 - 2011-02-16 =

* NEW: Added word "to".

= 1.0 - 2011-02-16 =

* INIT
