# Changelog - Orphans (sierotki)

Notable changes from version 3.1.3 and older. For recent releases, see the
[WordPress.org changelog](https://plugins.trac.wordpress.org/browser/sierotki/trunk/readme.txt).

## 3.1.3 - 2023-06-27

* Integration with the Muffin builder (part of the beTheme) has been added. Props for [waveman777](https://wordpress.org/support/users/waveman777/).
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to version 2.8.5.
* The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to version 2.1.2.

## 3.1.2 - 2023-03-10

* The `pre` and `code` tags have been added to the protected tags.
* An issue with removing line ends has been fixed. Props for [joannapl](https://wordpress.org/support/users/joannapl/).

## 3.1.1 - 2023-03-07

* The order of the protected tags has been changed to avoid a replacement issue. Props for [Against The Odds](https://wordpress.org/support/users/againsttheodds/).

## 3.1.0 - 2023-03-06

* Integration with the Polylang plugin has been added.
* Simple string replacement has been replaced with DOMDocument parsing and string replacement.
* The terms cache is now cleared when you enter the Orphans settings page.
* The deprecated data conversion method has been corrected.
* The incorrect trim of the first letter "t" in own orphans has been corrected. Props for [Michał Ruszczyk](https://profiles.wordpress.org/mruszczyk/).
* The `orphan_get_terms` filter has been added. It returns the current terms.
* The `orphan_replace_acf` filter has been added. It allows selected values in ACF fields to be excluded from replacement.
* The `orphan_replace_gettext` filter has been added. It allows replacement in gettext-related functions to be turned off.
* The translation function can now have text replaced. The default setting is off.

## 3.0.5 - 2023-01-11

* The translation function can now have text replaced. The default setting is off.

## 3.0.4 - 2022-11-20

* Handling of a space after a year in the short year format "r." has been added. Props for [Mastafu Design](https://wordpress.org/support/users/mastafu/).
* Integration with "GoodLayers Core" on the `gdlr_core_escape_content` filter has been added.

## 3.0.3 - 2022-09-02

* The ACF integration now works when ACF is network-activated. Props for [maczek6000](https://wordpress.org/support/users/maczek6000/).
* The iWorks Rate module repository has been moved to GitHub.
* The iWorks Rate module has been updated to version 2.1.1.

## 3.0.2 - 2022-08-06

* Integration with "WPBakery Page Builder" on the `vc_shortcode_output` filter has been added.

## 3.0.1 - 2022-07-18

* Protection against replacing content inside tag attributes has been added. Props for [krzyc](https://wordpress.org/support/users/krzyc/).

## 3.0.0 - 2022-04-21

* The Transients API is now used to avoid reading the `terms.txt` file multiple times.
* The unused `add_help_tab()` method has been removed.

## 2.9.11 - 2022-04-05

* The iWorks Options module has been updated to version 2.8.3 (fixed PHP 7.x compatibility).

## 2.9.10 - 2022-04-05

* The iWorks Options module has been updated to version 2.8.3.
* The iWorks Rate module has been updated to version 2.1.0.

## 2.9.9 - 2022-03-29

* Missing filter usage for the priority has been fixed. Props for [Adam Romanowski](https://wordpress.org/support/users/adamromanowski/).

## 2.9.8 - 2022-02-05

* Checking whether the "Advanced Custom Fields" plugin is active has been improved - the `class_exists()` function is no longer used.
* The `load_plugin_textdomain()` function has been moved so that translations load even when the plugin is not active.

## 2.9.7 - 2022-02-05

* Checking whether the "Advanced Custom Fields" plugin is active has been improved to avoid calling the `class_exists()` function multiple times. Props for [Piotr](https://wordpress.org/support/users/leardre/).
* Duplicates have been removed from the "terms to replace" list, and the list is now sorted.

## 2.9.6 - 2022-02-03

* A check for a missing second parameter in the `the_title` filter has been added. Props for [Zbyszek Zalewski](https://www.facebook.com/zbyszek.zalewski).

## 2.9.5 - 2022-02-02

* Missing replacements before and after any tag have been fixed. Props for [gierand](https://wordpress.org/support/users/gierand/).

## 2.9.4 - 2022-02-02

* The ability to turn off replacements on menu title elements has been added. The new option is in the "Miscellaneous" tab.
* The `orphan_allowed_filters` filter has been added. It allows the list of allowed filters to be changed.
* An issue with Font Awesome replacements has been fixed. Props for [lewleo999](https://wordpress.org/support/users/lewleo999/).
* The replacement process has been improved to avoid changing HTML tag parameters.

## 2.9.3 - 2022-01-20

* The doubled "Donate" link on the plugin page has been removed.
* The iWorks Options module has been updated to version 2.8.0.

## 2.9.2 - 2022-01-19

* The iWorks Options module has been updated to version 2.7.3.
* The iWorks Rate module has been updated to version 2.0.6.

## 2.9.1 - 2021-12-09

* Integration with "Advanced Custom Fields" for the "text", "textarea" and "WYSIWYG" field types has been added. Props for [Kamil Lipiński](https://profiles.wordpress.org/create24/) for the tests.

## 2.9.0 - 2021-12-09

* The `iworks_orphan_own_terms_file` filter has been added. It allows the whole terms definition file to be changed.
* The terms files have been moved to `etc/terms.txt`.
* The file `includes/iworks/orphan.php` has been renamed to `includes/iworks/class-iworks-orphan.php`.
* The orphans list has been updated, based on [Sierotka (typografia)](https://pl.wikipedia.org/wiki/Sierotka_(typografia)).

## 2.8.2 - 2021-12-03

* An issue with the "Keep numbers together" option - it also changed inline CSS - has been fixed. Props for [gierand](https://wordpress.org/support/users/gierand/).

## 2.8.1 - 2021-11-26

* Widget blocks content is now included in replacements.
* The `iworks_orphan_terms` filter has been added (the old one contained a typo, `iworks_orphan_therms` - the old name still works).
* Filtering of the default orphans has been improved - it now happens when they are read, not only on init.

## 2.8.0 - 2021-08-31

* A conflict with some plugins has been fixed. Props for Adam Gruntkowski.
* The iWorks Rate module has been updated to version 2.0.4.
* The [WordPress Options Class](https://github.com/iworks/wordpress-options-class) module has been updated to version 2.7.1.

## 2.7.9 - 2021-06-23

* The `vendor` directory has been renamed to `includes`.
* The iWorks Rate module has been updated to version 2.0.1.

## 2.7.8 - 2021-01-13

* The `orphan_replace` filter has been added. It allows replacements to be forced on any string.
* Content filters have been turned off in the admin area - they could interfere with other plugins. Props for [Zbyszek Zalewski](https://profiles.wordpress.org/zalzy/).

## 2.7.7 - 2020-06-20

* The `orphan_skip_replacement` filter has been added. It forces replacements to be skipped. See the FAQ for how to use it.

## 2.7.6 - 2020-06-08

* Replacements in feeds have been turned off.
* Replacements in the REST API have been turned off.

## 2.7.5 - 2019-11-12

* Default values configuration has been fixed.
* A quotation mark before an orphan is now handled.
* The orphan filters have been moved to the end of the run to avoid conflicts with replacements made by other plugins.
* Replacement rules have been split to avoid the "regular expression is too large" problem.
* The [WordPress Options Class](https://github.com/iworks/wordpress-options-class) module has been updated to version 2.6.8.

## 2.7.4 - 2018-03-16

* A problem with too greedy number replacement has been fixed.

## 2.7.3 - 2018-03-15

* A problem with the `$post` object has been fixed. Props for [adpawl](https://wordpress.org/support/users/adpawl/).
* A space after a number but before a word is now handled. Props for M. Hawranek.
* A too early translation load, which sometimes caused missing translations, has been fixed.
* The [WordPress Options Class](https://github.com/iworks/wordpress-options-class) module has been updated to version 2.6.5.

## 2.7.2 - 2018-02-13

* HTML entities are now handled.
* The WooCommerce short description, which uses its own filter, has been added.
* A problem with an orphan directly after another orphan has been fixed.
* Input data handling has been refactored.
* The [WordPress Options Class](https://github.com/iworks/wordpress-options-class) module has been updated to version 2.6.4.

## 2.7.1 - 2017-12-22

* A problem with array handling when the document contains scripts or styles has been fixed.

## 2.7.0 - 2017-06-29

* Custom fields have been added - you can now easily add a custom field name in the configuration.
* The words 'albo', 'bez', 'czy', 'lecz', 'nie', 'niech', 'przez', 'tak', 'tylko' and 'więc' have been added, based on [Sierotka (typografia)](https://pl.wikipedia.org/wiki/Sierotka_(typografia)).

## 2.6.9 - 2017-05-24

* A problem with the class declaration has been fixed. Props for [gierand](https://wordpress.org/support/users/gierand/).

## 2.6.8 - 2017-05-23

* The author description has been added to replacements.
* Taxonomy titles and descriptions have been added to replacements.
* A `preg_replace()` problem, "Compilation failed: range out of order", has been fixed.
* The [WordPress Options Class](https://github.com/iworks/wordpress-options-class) module is now used to handle the new options screen.

## 2.6.7 - 2017-05-09

* Replacement can now be applied to all languages by setting the `iworks_orphan_apply_to_all_languages` filter to true.
* The rate module has been fixed.

## 2.6.6.1 - 2017-04-30

* An incorrect regular expression replacement has been fixed.

## 2.6.6 - 2017-04-30

* The content of "script" and "style" tags is no longer replaced. Props for [m1nified](https://profiles.wordpress.org/m1nified/).
* The widget title has been added to replacements.
* The widget text has been added to replacements (it works only for the classic Text widget).

## 2.6.5 - 2016-11-01

* Rolled back to 2.6.3, because 2.6.4 broke images.

## 2.6.4 - 2016-11-01

* Symbols like FX-10, F-800 and F-600-K3-Z are now handled - "-" is replaced by the non-breaking hyphen (`&#8209;`). Props for [Marcin](https://profiles.wordpress.org/mkazmierczak).

## 2.6.3 - 2016-10-25

* WordPress coding standards for PHP files have been implemented.
* A "Call for ratings" section has been added to the settings page.

## 2.6.2 - 2016-02-27

* A problem with the ignored option for numbers has been fixed. Props for [Kacper](https://profiles.wordpress.org/alento/).
* A check of the site or entry language has been added to avoid replacing when the language is other than Polish. The only exception is numbers.

## 2.6.1 - 2016-01-11

* A problem with the non-breaking space has been fixed - the space after a number is replaced only between numbers.

## 2.6 - 2016-01-09

* The text domain has been changed from `iworks_orphan` to the plugin name `sierotki`, to be compatible with WordPress i18n rules.
* An activation hook to change the options autoload status has been added.
* A deactivation hook to change the options autoload status has been added.
* The `iworks_orphans_capability` filter has been added. Props for Cezary Buliszak.
* A non-breaking space after numbers has been added.
* An uninstall hook has been added.
* Screenshots have been updated.

## 2.5 - 2015-11-06

* The `iworks_orphan_replace` filter has been added.

## 2.4 - 2015-02-12

* A hard space between a number (year) and the Polish year shortcut "r." has been added.
* The WooCommerce product title and short description have been added to the available options. Props for [Dominik Kawula](https://www.facebook.com/dominik.kawula).

## 2.3.2 - 2014-09-12

* An error in the options array has been fixed.

## 2.3.1 - 2014-09-12

* The options array is now checked for non-existing hash keys.
* Screenshots have been updated.

## 2.3 - 2014-07-10

* All forms of the word "number" have been added.

## 2.2 - 2014-01-24

* Links to the forum have been added.
* Capability checks have been verified with WordPress 3.8.

## 2.1 - 2013-11-09

* Capability checks have been verified with WordPress 3.6.
* The orphan class has been refactored to follow PSR-0 rules.

## 2.0.2 - 2013-08-20

* Replacement for a single-letter orphan after another orphan has been fixed. Props for [Szymon Skulimowski](http://wpninja.pl/autorzy/szymon-skulimowski/).
* Capability checks have been verified with WordPress 3.6.
* A help and related section has been added.

## 2.0.1 - 2013-07-10

* Numbers are now handled.

## 2.0 - 2012-08-12

* Permissions to the configuration page have been fixed.
* Replacement for strings starting with an orphan has been fixed.
* The code has been rewritten as a class.
* Some abbreviations of academic degrees have been added.
* The orphans dictionary has been massively extended. Props for [adpawl](http://podbabiogorze.info.pl).

## 1.4.2 - 2012-03-02

* The `the_title` filter has been added.

## 1.4.1 - 2011-02-24

* Character trimming has been added.
* Multiple comma usage has been fixed.

## 1.4 - 2011-02-24

* User-defined orphans have been added.
* The capability name has been corrected.

## 1.3 - 2011-02-19

* An options page to turn on/off filtering in content, excerpt or comments has been added.
* "(" as a character before an orphan has been added.

## 1.2 - 2011-02-18

* The `comment_text` filter has been added.
* Capital letters missed by the plugin have been fixed.

## 1.1 - 2011-02-17

* The elegant anonymous function, which required PHP 5.3.0, has been abandoned.
* The `the_excerpt` filter has been added.

## 1.0.2 - 2011-02-17

* ">" as a character before an orphan has been added.

## 1.0.1 - 2011-02-16

* The word "to" has been added.

## 1.0 - 2011-02-16

* Initial release.
