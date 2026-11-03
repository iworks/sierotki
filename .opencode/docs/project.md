# Project mechanics — Orphans (sierotki)

> Loading flow, replacement engine, integrations, translations, conventions.
> Read only when the task involves these areas.

## Overview

The "Orphans" plugin (slug `sierotki`, textdomain `sierotki`) improves
typography by replacing the space after short words with a non-breaking
space **at display time only** — stored content is never modified.
Sources contain literal placeholders (`PLUGIN_VERSION`, `PLUGIN_TILL_YEAR`,
`BUILDTIMESTAMP`, …) replaced by Grunt at build time (see `build.md`).
The version lives only in `package.json`.

## Directory structure

```
/
├── sierotki.php                # Plugin bootstrap (header, constants, hooks)
├── etc/
│   ├── options.php             # Options page config (iworks_options format)
│   └── terms-{pl,cs,en}.txt    # Orphan word lists (one term per line, # comments)
├── includes/
│   └── iworks/
│       ├── class-iworks-orphan.php        # Core class (replacement engine)
│       ├── integrations/                  # Builder/field-plugin integrations
│       ├── orphans/                       # export/import/github helper classes
│       ├── options/                       # Git SUBMODULE — iworks options framework
│       └── rate/                          # Git SUBMODULE — iworks rate framework
├── vendor/
│   └── simple_html_dom.php     # Bundled HTML parser (guarded by HDOM_TYPE_ELEMENT)
├── languages/                  # sierotki.pot + .po/.mo
├── assets/images/              # logo.svg only
├── package.json                # Version + release metadata (single source of truth)
├── Gruntfile.js                # Build system — see build.md
└── readme.txt                  # WordPress.org readme
```

## Loading flow

1. WordPress loads `sierotki.php` (textdomain `sierotki`):
   - defines `IWORKS_ORPHANS_VERSION` (`'PLUGIN_VERSION'`),
     `IWORKS_ORPHANS_PLUGIN_DIR`, `IWORKS_ORPHANS_PLUGIN_URL`;
   - requires `etc/options.php`, `vendor/simple_html_dom.php`
     (only if `HDOM_TYPE_ELEMENT` is not defined), the core class,
     and the vendored `iworks_rate` / `iworks_options` (class_exists guarded);
   - instantiates `iworks_orphan`, registers activation/deactivation hooks
     (they toggle `autoload` yes/no for the `iworks_orphan_*` options).
2. `iworks_orphan::__construct()`:
   - hooks `init` → `init()` (front-end filters) and `init` (late) → rate
     registration; `admin_init`; options-page cache clearing; import/export
     JSON handlers on `plugins_loaded`;
   - adds the `orphan_replace` filter (apply to any string) and
     `orphan_get_terms`;
   - conditionally loads `includes/iworks/orphans/class-iworks-orphans-github.php`
     (GitHub self-updater) only if the file exists — **excluded from wp.org ZIP**.
3. `init()` (front-end only; returns early in admin):
   - `load_integrations()` — conditionally instantiates integration classes
     (Bricks via `BRICKS_VERSION`, ACF / Secure Custom Fields via
     `is_plugin_active()`);
   - registers `gettext` replacement if the `gettext` option is on;
   - hooks content filters per the `orphan_allowed_filters` map, only for
     options set to `1` (the_title, the_excerpt, the_content, comment_text,
     widget_title, widget_text, term_description, author description,
     widget_block_content); WooCommerce `woocommerce_short_description`
     follows `the_excerpt`;
   - hooks builder filters: `vc_shortcode_output`, `gdlr_core_escape_content`,
     `mfn_builder_items_show`, `et_pb_module_content`.

## Replacement engine (core class)

- `replace( $content )` — guarded entry: skips empty input, admin, feeds,
  REST; checks post type / taxonomy settings for entry-related filters;
  applies only to `pl*` / `cs*` locales unless `ignore_language` is on
  (WPML `wpml_current_language`, Polylang `pll_current_language`);
  then `unconditional_replacement()`.
- `replace_two( $content, $object_id )` — wrapper for 2-arg filters
  (`the_title`); handles the menu-title option.
- `unconditional_replacement()` — plain strings go straight to
  `string_replacement()`; HTML is parsed with `str_get_html()`, protected
  tags (`pre`, `code`, `iframe`, `svg`, `script`, `style` — order matters!)
  are swapped for md5 placeholders, text nodes are replaced, tags restored.
- `string_replacement()` — number-joining (independent of language, option
  `numbers`), then term replacement in chunks of 10 (avoids "regex too
  large"), with double passes for orphan-after-orphan chains and a final
  pass for single letters after a previous nbsp; handles `r.` year format.
- `_terms()` — loads terms from `etc/terms-{locale}.txt` (locale, then
  short code; via `WP_Filesystem_Direct`), merges own orphans option,
  dedupes/sorts, caches in a transient `orphan_terms{version}` (1 day);
  cleared on the settings page load and on `update_option_WPLANG`.

## Options layer (etc/options.php + iworks_options)

- `orphans_indicator_options()` returns the config array; result is cached
  in `wp_cache` group `iworks_orphans` (filter
  `orphans_indicator_options_use_cache` to disable).
- Single page `index` under the **Appearance** menu
  (`load-appearance_page_iworks_orphan_index` hooks rely on this).
- Content filters section, taxonomies selector, post-type selector (options
  come from the `index_iworks_orphan_post_type_data` filter), language
  preset, own orphans, numbers, gettext, integrations (ACF types etc. —
  injected by integrations via the `orphans/etc/config/integrations` filter),
  plus import/export and rate metaboxes.
- Option names get the `iworks_orphan_` prefix
  (`get_orphan_options()` in `sierotki.php`).

## Integrations (includes/iworks/integrations/)

- `class-iworks-orphans-integration.php` — abstract base `iWorks_Orphans_Integration`
  (holds the main instance + `is_on()` helper). Integration classes
  `require_once` it and receive the main instance in the constructor.
- `advanced-custom-fields.php` / `secure-custom-fields.php` — hook
  `acf/format_value/type={text,textarea,wysiwyg}` per option
  (`acf_{type}`), add their own options via `orphans/etc/config/integrations`.
  Two separate classes because ACF and SCF conflict on filter registration.
- `bricks.php` — Bricks builder rendering filter.
- Directly wired builder filters live in the core class (WPBakery,
  GoodLayers, Muffin/beTheme, Divi) — older integrations, not classes.

## Vendored frameworks (git submodules — includes/iworks/)

- `options/` — `iworks_options`: settings pages, option storage and upgrade
  routines. Configured from `etc/options.php`; option names get the
  `iworks_orphan_` prefix. Separate git repo
  (`iworks/wordpress-options-class`) — **treat as vendor, never edit here**;
  upstream changes go to its own repository.
- `rate/` — rate-it / "enjoying this plugin?" admin notices and dashboard
  metaboxes, wired via the `iworks-register-plugin` action and
  `iworks_rate_notice_logo_style` filter. Separate git repo
  (`iworks/iworks-rate`) — vendor, never edit here.
- `vendor/simple_html_dom.php` — bundled HTML parser (not a submodule, but
  also vendor code; loaded only when `HDOM_TYPE_ELEMENT` is not defined).

## Constants & globals

- `IWORKS_ORPHANS_VERSION`, `IWORKS_ORPHANS_PLUGIN_DIR`, `IWORKS_ORPHANS_PLUGIN_URL`.
- Global `$iworks_orphan` (main instance); `get_orphan_options()` function.
- No Composer. Requires WordPress ≥ 6.0, PHP ≥ 8.0 (from `package.json`).

## Translations (i18n)

- Textdomain `sierotki`; `languages/sierotki.pot` + per-locale `.po`/`.mo`.
- `grunt i18n` runs `checktextdomain` + `makepot`; `build:github` compiles
  `.mo` via `potomo`. Optional gettext replacement (option `gettext`,
  filter `orphan_replace_gettext`).

## Tests

- There is **no** `tests/` directory in this repo, despite `grunt test`
  (phpunit + jshint) being registered — do not claim tests pass; use
  `php -l` on changed files instead.

## General conventions

- Formatting: tabs for PHP, LF, final newline (`.editorconfig`);
  WordPress-style docblocks everywhere.
- Every PHP file: `defined( 'ABSPATH' ) || exit;`.
- File naming: kebab-case, `class-iworks-orphans-{name}.php` (core class is
  legacy: `class-iworks-orphan.php`); classes snake_case lowercase
  (`iworks_orphan`, `iWorks_Orphans_Integration_*` style for integrations).
- Hooks and options: `orphan_*` (public API) or `iworks_orphan_*` (internal).
- Public filters: `orphan_replace`, `orphan_skip_replacement`,
  `orphan_get_terms`, `orphan_allowed_filters`, `orphan_replace_acf`,
  `orphan_replace_gettext`, `iworks_orphan_terms` (+ legacy
  `iworks_orphan_therms`), `iworks_orphan_own_terms_file`,
  `iworks_orphan_protected_tags`, `iworks_orphans_capability`,
  `iworks_orphan_apply_to_all_languages`, `iworks_orphan_terms_files_array`.

## Known pitfalls (do not fix without approval)

- Source placeholders (`PLUGIN_VERSION`, `PLUGIN_TILL_YEAR`, …) are
  intentional — never replace them with real values in the repo.
- Legacy filter `iworks_orphan_therms` (typo) must stay — backward compat.
- `mnf_builder_herlper()` typo in the core class — private, harmless.
- `is_plugin_active()` builds its regex with a stray `sprintf` arg — works
  by accident, do not refactor without approval.
- `$protected_tags` order is load-bearing: `script`/`style` must stay after
  `iframe`/`svg` or nested tags break.
- `filter_post_meta()` checks `$unique` (4th param named `$unique` but the
  hook passes `$single`) — behavior depends on the single flag; subtle.
- The `exceptions`/`attributes` revert blocks in `string_replacement()`
  reference variables that are never set — dead code, kept intentionally.
