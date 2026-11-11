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

The **Orphans** plugin automatically improves the typography of your WordPress site by preventing single-letter or very short words—known as *orphans*—from appearing alone at the beginning of a line. By replacing the space after these words with a non-breaking space, the plugin ensures cleaner, more professional-looking text layouts, especially for languages like Polish where such typographic rules are important.

= What are Orphans? =
An *orphan* in typography is a short word (often a single character) left isolated at the beginning of a line, separated from the rest of the sentence. This is considered a composition error and can negatively affect readability and the visual appeal of your content.

= Key Features =
- **Automatic Orphan Fixing:** Scans your content and replaces spaces after short words (orphans) with non-breaking spaces, preventing them from being separated by line breaks.
- **Customizable:** Easily modify which words are treated as orphans using filters, or provide your own list of orphan terms.
- **Non-Destructive:** The plugin does not alter your original content in the database; changes are applied only when content is displayed.
- **Builder and Plugin Integration:** Seamless compatibility with popular page builders like Bricks, Divi, Muffin (beTheme), WPBakery, and Advanced Custom Fields.
- **Flexible Usage:** Apply orphan fixes to custom fields or any string using provided filters.

= How It Works =
Once activated, Orphans automatically processes your posts, pages, and other content post types, fixing orphaned words on the fly. You can customize its behavior through the settings panel found under Appearance > Orphans, or extend functionality with WordPress filters for advanced use cases.

= Ideal For =
- Bloggers, publishers, and editors seeking to maintain high typographic standards.
- Sites in languages (like Polish or Czech) where orphan control is a common publishing requirement.
- Anyone wanting to enhance the professional appearance and readability of their WordPress content.

= Open Source & Developer Friendly =
Orphans is open source, actively maintained, and available on [GitHub](https://github.com/iworks/sierotki) for those interested in contributing or extending its features.

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

= How to use this plugin on the custom field? =

Apply the `orphan_replace` filter to the field value — see the developer documentation ([README.md](https://github.com/iworks/sierotki#using-the-plugin-in-code)) for examples.

= I have a problem with the plugin, or I want to suggest a feature. Where can I do this? =

You can do it on [Support Threads](https://wordpress.org/support/plugin/sierotki/#new-topic-0), but please add your ticket to [Github Issues](https://github.com/iworks/sierotki/issues).

== Screenshots ==

1. Options for entries.
1. Options for widgets.
1. Options for taxonomies.
1. Miscellaneous options.

== Changelog ==

Older changelog entries (3.1.x and below) live in [CHANGELOG.md](https://github.com/iworks/sierotki/blob/main/CHANGELOG.md).

= 3.4.7 - 2026-11-11 =
* Fixed Stored Cross-Site Scripting (XSS) via the comment parameter - the bundled `simple_html_dom` library re-serialized comment markup at render time, which allowed an unauthenticated attacker to mutate safe stored markup and inject event-handler attributes. The plugin now parses HTML with the WordPress HTML API (`WP_HTML_Tag_Processor`) and rewrites text nodes only - markup and attributes are passed through untouched, and the vulnerable library has been removed entirely. Props for [peppa (Patchstack)](https://patchstack.com/).
* Removed the bundled `simple_html_dom` library - HTML is now handled by the core WordPress HTML API (`WP_HTML_Tag_Processor`), which also reduces the plugin size by about 100 kB.
* Content inside protected tags (`pre`, `code`, `script`, `style`, `iframe`, `svg`) is now skipped natively during parsing instead of being swapped with placeholders - the previous salted-placeholder mechanism from 3.4.6 is no longer needed and has been removed.

= 3.4.6 - 2026-11-07 =
* Fixed Stored Cross-Site Scripting (XSS) via the comment parameter - the protected-tags placeholder digest was predictable, which allowed an unauthenticated attacker to restore raw markup inside an HTML attribute. The digest is now salted with a site-specific key. (CVE-2026-124216). Props for [Santh](https://www.wordfence.com/threat-intel/vulnerabilities/researchers/santh-2).

= 3.4.5 - 2026-11-03 =
* Build system converted from Grunt to the Bun bundler (`bun run build`).
* Developer documentation moved from `readme.txt` to `README.md` - the WordPress.org readme is now end-user focused only.
* New `bun run update-libs` task to update the vendored iWorks libraries.
* Translation files regenerated with wp-cli (`i18n make-pot` / `update-po`).
* Security hardening: capability check (new `iworks/orphans/capability` filter, default `manage_options`) is now enforced for configuration import and export.
* Security hardening: imported option names are whitelisted to plugin options only - arbitrary options can no longer be overwritten.
* Security hardening: uploaded import file is fully validated before parsing.
* Security hardening: orphan terms are escaped with `preg_quote()` before building replacement patterns.
* Export file name is now properly quoted in the `Content-Disposition` header.
* Import/export admin scripts are loaded only on the Orphans settings screen.
* The `iworks_orphans_capability` filter, previously documented, is now actually applied - renamed to `iworks/orphans/capability` with the old name kept as a deprecated fallback.
* All `iworks_orphan_*` (and `orphans_*`) filters renamed to the namespaced `iworks/orphans/...` schema - legacy names still work as deprecated (3.6.0) fallbacks.
* Fixed release packaging: `.git*`, `.gitmodules`, `.github/` and `.editorconfig` files are no longer included in the ZIPs.
* Fixed build: the `PLUGIN_URL` placeholder replacement no longer corrupts the `IWORKS_ORPHANS_PLUGIN_URL` constant (a fatal error on activation).
* Empty banner-only `sierotki-admin`/`sierotki-frontend` JS and CSS bundles are no longer shipped.
* Plugin root, plugin basename and assets URL are now derived from the plugin constants instead of relative directory guessing.

= 3.4.4 - 2026-07-16 =
* Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 3.1.1.
* Fixed issue in external `simple_html_dom` - missing `global $debug_object`. [#21](https://github.com/iworks/sierotki/issues/21) Props for Marek Szymański.

= 3.4.3 - 2026-06-17 =
* Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 3.1.0.
* Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.3.2.

= 3.4.2 - 2026-04-24 =
* Updated & enhaced build process.

= 3.4.1 - 2025-10-14 =
* Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 3.0.8.

= 3.4.0 - 2025-08-27 =
* Resolved conflict arising from differing implementations of the `acf/format_value` filter in Advanced Custom Fields and Secure Custom Fields plugins.
* Refactored codebase by separating [Secure Custom Fields](https://wordpress.org/plugins/secure-custom-fields/) integration into its own dedicated file.
* Refactored codebase by separating Advanced Custom Fields integration into its own dedicated file.

= 3.3.9 - 2025-08-23 =
* Integration with the [Secure Custom Fields](https://wordpress.org/plugins/secure-custom-fields/) plugin has been added.
* Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 3.0.7.
* Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.3.1.

= 3.3.8 - 2025-05-22 =
* Replaced `logo.png` by `logo.svg`.
* Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 3.0.2.
* Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.3.0.

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

== Upgrade Notice ==

= 3.1.0 - 2023-03-06 =

Switching from simple replacement to DOMDocument parsing and string replacement could cause problems for your sites if there are missing required PHP modules.


