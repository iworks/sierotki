=== PLUGIN_TITLE ===
Contributors: iworks
Donate link: https://ko-fi.com/iworks?utm_source=sierotki&utm_medium=readme-donate
Tags: PLUGIN_TAGS
Requires at least: PLUGIN_REQUIRES_WORDPRESS
Tested up to: PLUGIN_TESTED_WORDPRESS
Stable tag: PLUGIN_VERSION
Requires PHP: PLUGIN_REQUIRES_PHP
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

PLUGIN_TAGLINE

== Description ==

To avoid line breaks in the incorrect position, the plugin fixes orphans' positions and replaces space after orphan with a hard space. 

**Orphan** - a text composition error, which consists in leaving a lonely short word at the end or at the beginning of a verse, especially a single-character one. The word "lonely" here means separation from a closely related word by line breaks.

= Asset image =

[Manuscript by Muffet, on Flickr](http://www.flickr.com/photos/calliope/306564541/)

= GitHub =

The Orphans plugin is available also on [GitHub - Orphans](https://github.com/iworks/sierotki).

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

1. Download the plugin (.zip file) on the right column of this page.
1. In your Admin, go to the menu Plugins > Add.
1. Select the button `Upload Plugin`.
1. Upload the .zip file you just downloaded.
1. Activate the plugin.
1. A new menu `Orphans` in `Appearance` will appear in your Admin Menu.

***

= The old and reliable way (FTP) =

1. Upload `sierotki` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. A new menu `Orphans` in `Appearance` will appear in your Admin Menu.

== Frequently Asked Questions ==

= When does this plugin replace spaces? =

Plugin works when viewing the content and does not modify your content.

= I have a problem with the plugin, or I want to suggest a feature. Where can I do this? =

You can do it on [Support Threads](https://wordpress.org/support/plugin/sierotki/#new-topic-0), but please add your ticket to [Github Issues](https://github.com/iworks/sierotki/issues).

= How to use this plugin on the custom field? =

Use `orphan_replace` filter. Example code:

`
$value = apply_filters(
    'orphan_replace',
    get_post_meta($post_id, 'meta_key', true )
);
`

= How to use this plugin on any string? =

Use `orphan_replace` filter. Example code:

`
$value = apply_filters( 'orphan_replace', 'any string' );
`

= How to change plugin capability? =

By default to using this plugin you must have `manage_options` capability, which usually means site administrator. If you want to allow manage Orphans by "Editors" then you need to use other capabilities, e.g.  `unfiltered_html`. You can use `iworks_orphans_capability` filter:

`
add_filter('iworks_orphans_capability', 'my_orphans_capability');
function my_orphans_capability($capability) {
    return 'unfiltered_html';
}
`

= How to turn of replacement in my piece of code? =

At the beginning of your block just add:
`
add_filter( 'orphan_skip_replacement', '__return_true' );
`
and at the end, to again turn on replacements:
`
remove_filter( 'orphan_skip_replacement', '__return_true' );
`

= How can I change default orphans? =

Please use `iworks_orphan_terms` filter. It is array of default orphans. You can remove, add or even replace whole array. For example, to remove words "oraz", "na" and ""nie", use code bellow:

`
add_filter( 'iworks_orphan_terms', 'remove_iworks_orphan_terms' );
function remove_iworks_orphan_terms( $terms ) {
    $default_orphans_to_remove = array( 'oraz', 'na', 'nie', );
    foreach( $default_orphans_to_remove as $value ) {
        if ( $key = array_search( $value, $terms ) ) {
            unset( $terms[ $key ] );
        }
    }
    return $terms;
}
`

= How can I change the default orphan file? =

The default file with definitions is located in the `etc/` directory.

You can change it using the filter `iworks_orphan_own_terms_file`:

`
add_filter(
    'iworks_orphan_own_terms_file',
    function( $file ) {
        return '<own path to file';
    }
);
'

== Screenshots ==

1. Options for entries.
1. Options for widgets.
1. Options for taxonomies.
1. Miscellaneous options.

== Changelog ==

= 3.3.7 - 2025-05-15 =
* Enhanced PHPDoc documentation across the codebase for better maintainability.
* Improved code organization and added proper escaping for output security.
* Added constants for plugin paths and URLs to improve code reliability.
* Fixed URL escaping in admin interface for better security.
* Updated documentation with more detailed function descriptions and parameter information.
* Improved code comments and inline documentation for better developer experience.
* Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 3.0.1.

= 3.3.6 - 2025-04-16 =
* Enhanced integration with the Bricks Builder (Bricks Theme). [#16](https://github.com/iworks/sierotki/issues/16). Props for [Cactoo](https://profiles.wordpress.org/cactoo/).
* Removed replacements for translation functions within the admin interface.
* Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 3.0.0.

= 3.3.5 - 2025-03-24 =
* The issue with the saving multiple option value has been resolved.

= 3.3.4 - 2025-03-24 =
* The issue with the saving options has been resolved. [#14](https://github.com/iworks/sierotki/issues/14). Props for [e-poka](https://e-poka.com/).

= 3.3.3 - 2025-03-11 =
* Added translation support to [GitHub releases](https://github.com/iworks/sierotki/releases).
* Improved build process for better performance and efficiency.
* Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.9.8.
* Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.2.3.
* Enhanced plugin update mechanism for smoother updates from GitHub releases.
* Added escaping functions in multiple areas for improved security.

= 3.3.2 - 2025-01-03 =
* Check for `iworks_options` object has been added. [#12](https://github.com/iworks/sierotki/issues/12). Props for [maciejsb](https://wordpress.org/support/users/maciejsb/).

= 3.3.1 - 2024-12-05 =
* [Translation loading time has been fixed.](https://github.com/iworks/sierotki/issues/7)

= 3.3.0 - 2024-12-02 =
* Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.2.1.
* The configuration import ability has been added. [#10](https://github.com/iworks/sierotki/issues/10)
* The configuration export ability has been added. [#9](https://github.com/iworks/sierotki/issues/9)
* [Translation loading time has been fixed.](https://github.com/iworks/sierotki/issues/7)

= 3.2.9 - 2024-11-17 =
* [Translation loading time has been fixed.](https://github.com/iworks/sierotki/issues/7)

= 3.2.8 - 2024-08-21 =
* Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.9.2.
* Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.2.0.

= 3.2.7 - 2024-02-21 =
* Integration with the Bricks builder (part of the Bricks Theme) has been added.
* Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.9.1.
* Update the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.1.7.

= 3.2.6 - 2023-12-27 =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to version 2.9.0.
* The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to version 2.1.6.

= 3.2.5 - 2023-11-30 =
* [The post types selector has been refactored.](https://github.com/iworks/sierotki/issues/5) Props for [gierand](https://wordpress.org/support/users/gierand/).
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to version 2.8.9.
* The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to version 2.1.4.
* The `strip_tags()` function has been replaced by the `wp_strip_all_tags()` function.
* The `wp_remote_get()` function has been replaced by the `WP_Filesystem_Direct` class object.

= 3.2.4 - 2023-10-26 =
* Handling of multiple term files has been added.
* Handling short language codes has been added.
* The EN terms file has been added—only one-letter and some two-letter words from Wiktionary.
* Checking whether a data file has already been loaded to avoid multiple attempts to load it has been added.
* The term cache will be cleared after updating the WPLANG option.
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to version 2.8.7.
* The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to version 2.1.3.
* The file `terms-cs_CZ.txt` has been renamed to `terms-cs.txt`.
* The file `terms-pl_PL.txt` has been renamed to `terms-pl.txt`.

= 3.2.3 - 2023-09-04 =

* The cache for `orphans_indicator_options` has been added. Props for [Krystian Marcisz](https://github.com/simivar).
* The filter `orphans_indicator_options_use_cache` has been added to allow cache turn-off.

= 3.2.2 - 2023-07-12 =

* Data type check has been added. Props for [LeoBullero](https://wordpress.org/support/users/leobullero/).

= 3.2.1 - 2023-07-07 =

* Integration with the Divi builder (part of the Divi Theme) has been added.

= 3.2.0 - 2023-07-05 =

* Fixed missing replacements before and after any tag. Props for [gierand](https://wordpress.org/support/users/gierand/).
* The ability to choose a preset language file has been added.
* The ability to use only your own defined terms has been added.
* The Czech rules have been added.

= 3.1.3 - 2023-06-27 =

* Integration with the Muffin builder (part of the beTheme) has been added. Props for [waveman777](https://wordpress.org/support/users/waveman777/).
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to version 2.8.5.
* The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to version 2.1.2.

= 3.1.2 - 2023-03-10 =

* Tags `pre` and `code` have been added to protected tags.
* The issue with removing line ends has been fixed. Props for [joannapl](https://wordpress.org/support/users/joannapl/).

= 3.1.1 - 2023-03-07 =

* The order of protected tags has been changed to avoid a replacement issue. Props for [Against The Odds](https://wordpress.org/support/users/againsttheodds/).

= 3.1.0 - 2023-03-06 =

* Integration with the Polylang plugin has been added.
* Switched from simple replacement to DOMDocument parsing and string replacement.
* The ability to clear the terms cache when you enter the Orphans Settings Page has been added.
* The deprecated data conversion method has been corrected.
* The incorrect trim for the first letter "t" in own orphans has been corrected. Props for [Michał Ruszczyk](https://profiles.wordpress.org/mruszczyk/).
* The `orphan_get_terms` filter has been added. It allows you to get current terms.
* The `orphan_replace_acf` filter has been added. It allows you to turn off selected values in ACF fields.
* The `orphan_replace_gettext` filter has been added. It allows you to turn off selected values in gettext related functions.
* The translation function now includes the ability to replace text. The default setting is off.

= 3.0.5 - 2023-01-11 =
* The translation function now includes the ability to replace text. The default setting is off.

= 3.0.4 - 2022-11-20 =
* Handle space after year for short year format "r.". Props for [Mastafu Design](https://wordpress.org/support/users/mastafu/)
* Added integration with "Goodlayers Core" on `gdlr_core_escape_content` filter.

= 3.0.3 - 2022-09-02 =
* Handle ACF integration if it is network activated plugin. Props for [maczek6000](https://profiles.wordpress.org/maczek6000/).
* Changed iWorks Rate Module repository to GitHub.
* Updated iWorks Rate to 2.1.1.

= 3.0.2 - 2022-08-06 =
* Added integration with "WPBakery Page Builder" on `vc_shortcode_output` filter.

= 3.0.1 - 2022-07-18 =
* Added tag attributes replacement protection. Props for [krzyc](https://wordpress.org/support/users/krzyc/).

= 3.0.0 - 2022-04-21 =
* Added Transients API to avoid multiple read from `terms.txt` file.
* Removed unused method `add_help_tab()`.

= 2.9.11 - 2022-04-05 =
* Updated iWorks Options to 2.8.3. (Fixed PHP 7.x compatibility).

= 2.9.10 - 2022-04-05 =
* Updated iWorks Options to 2.8.3.
* Updated iWorks Rate to 2.1.0.

= 2.9.9 - 2022-03-29 =
* Fixed missing filter usage for priority. Props for [Adam Romanowski](https://wordpress.org/support/users/adamromanowski/).

= 2.9.8 - 2022-02-05 =
* Improved checking is plugin "Advanced Custom Fields" - removed usage of `class_exists` function.
* Moved `load_plugin_textdomain()` function to allow load i18n even plugin is not active.

= 2.9.7 - 2022-02-05 =
* Improved checking is plugin "Advanced Custom Fields" to avoid multiple calling function `class_exists`. Props for [Piotr](https://wordpress.org/support/users/leardre/).
* Remove duplicates from "terms to replace" list and sort this list.

= 2.9.6 - 2022-02-03 =
* Added check for missing second param in `the_title` filter. Props for [Zbyszek Zalewski](https://www.facebook.com/zbyszek.zalewski)

= 2.9.5 - 2022-02-02 =
* Fixed missing replacements before and after any tag. Props for [gierand](https://wordpress.org/support/users/gierand/).

= 2.9.4 - 2022-02-02 =
* Added ability to turn off replacements on menu title elements. New option is in "Miscellaneous" tab.
* Added `orphan_allowed_filters` to add ability to change allowed filters.
* Fixed issue with Font Awesome replacements. Props for [lewleo999](https://wordpress.org/support/users/lewleo999/)
* Improved replacement process to avoid change HTML tags params.

= 2.9.3 - 2022-01-20 =
* Removed doubled "Donate" link on plugin page.
* Updated iWorks Options to 2.8.0.

= 2.9.2 - 2022-01-19 =
* Updated iWorks Options to 2.7.3.
* Updated iWorks Rate to 2.0.6.

= 2.9.1 - 2021-12-09 =
* Added integration with "Advanced Custom Fields" for fields types: "text", "textarea", "WYSIWYG". Props for [Kamil Lipiński](https://profiles.wordpress.org/create24/) for the tests.

= 2.9.0 - 2021-12-09 =
* Added filter `iworks_orphan_own_terms_file` to add ability to change whole terms definition file.
* Moved terms into `etc/terms.txt`.
* Renamed file `includes/iworks/orphan.php` into `includes/iworks/class-iworks-orphan.php`.
* Updated orphans list, based on [Sierotka (typografia)](https://pl.wikipedia.org/wiki/Sierotka_(typografia))

= 2.8.2 - 2021-12-03 =
* Fixed problem with option "Keep numbers together" - it changed inline CSS too. Props for [gierand](https://wordpress.org/support/users/gierand/).

= 2.8.1 - 2021-11-26 =
* Added a widget blocks content to replacements.
* Added filter `iworks_orphan_terms` (old one has a typo `iworks_orphan_therms` - but old one stays too).
* Improved filtering defaults orphans - now it is filtered when we get it, not only on init.

= 2.8.0 - 2021-08-31 =
* Fixed conflict with some plugins. Props for Adam Gruntkowski.
* Updated iWorks Rate to 2.0.4.
* Updated [WordPress Options Class](https://github.com/iworks/wordpress-options-class) to version 2.7.1.

= 2.7.9 - 2021-06-23 =
* Renamed directory `vendor` into `includes`.
* Updated iWorks Rate to 2.0.1.

= 2.7.8 - 2021-01-13 =
* Added `orphan_replace` filter to add ability to force replacements on any string.
* Turned off content filters for admin area - it could mess with another plugins. Props for [Zbyszek Zalewski](https://profiles.wordpress.org/zalzy/).

= 2.7.7 - 2020-06-20 =
* Added `orphan_skip_replacement` filter to force skip replacements.  Check FAQ to know how to use it.

= 2.7.6 - 2020-06-08 =
* Turned off replacements in feeds.
* Turned off replacements in REST API.

= 2.7.5 - 2019-11-12 =
* Fixed default values configuration.
* Handled quotation mark before orphan.
* Moved orphan's filters at the end of run, to try to avoid different plugins replacements.
* Split replacement rules to avoid problem with `regular expression is too large`.
* Updated [WordPress Options Class](https://github.com/iworks/wordpress-options-class) to version 2.6.8

= 2.7.4 - 2018-03-16 =
* Fixed problem with too greedy number replacement.

= 2.7.3 - 2018-03-15 =
* Fixed problem with $post object. Props for [adpawl](https://wordpress.org/support/users/adpawl/)
* Fixed space after a number, but before a word. Props for M. Hawranek.
* Fixed too early translation load, it causes sometimes missing translation.
* Updated [WordPress Options Class](https://github.com/iworks/wordpress-options-class) to version 2.6.5.

= 2.7.2 - 2018-02-13 =
* Added html entities handling.
* Added WooCommerce short description, which use they own filter.
* Fixed problem with orphan directly after orphan.
* Refactored input data.
* Updated [WordPress Options Class](https://github.com/iworks/wordpress-options-class) to version 2.6.4.

= 2.7.1 - 2017-12-22 =
* Fixed a problem with array handling, when document contains scripts or styles.

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

== Upgrade Notice ==

= 3.1.0 - 2023-03-06 =

Switching from simple replacement to DOMDocument parsing and string replacement could cause problems for your sites if there are missing required PHP modules.


