# Orphans

The **Orphans** plugin automatically improves the typography of your WordPress site by preventing single-letter or very short words—known as *orphans*—from appearing alone at the end or beginning of a line. By replacing the space after these words with a non-breaking space, the plugin ensures cleaner, more professional-looking text layouts, especially for languages like Polish where such typographic rules are important.

## What are Orphans?
An *orphan* in typography is a short word (often a single character) left isolated at the end or start of a line, separated from the rest of the sentence. This is considered a composition error and can negatively affect readability and the visual appeal of your content.

## Key Features
- **Automatic Orphan Fixing:** Scans your content and replaces spaces after short words (orphans) with non-breaking spaces, preventing them from being separated by line breaks.
- **Customizable:** Easily modify which words are treated as orphans using filters, or provide your own list of orphan terms.
- **Non-Destructive:** The plugin does not alter your original content in the database; changes are applied only when content is displayed.
- **Builder and Plugin Integration:** Seamless compatibility with popular page builders like Bricks, Divi, Muffin (beTheme), WPBakery, and Advanced Custom Fields.
- **Flexible Usage:** Apply orphan fixes to custom fields or any string using provided filters.

## How It Works
Once activated, Orphans automatically processes your posts, pages, and other custom post types, fixing orphaned words on the fly. You can customize its behavior through the settings panel found under Appearance > Orphans, or extend functionality with WordPress filters for advanced use cases.

## Ideal For
- Bloggers, publishers, and editors seeking to maintain high typographic standards.
- Sites in languages (like Polish or Czech) where orphan control is a common publishing requirement.
- Anyone wanting to enhance the professional appearance and readability of their WordPress content.

## WordPress.org

[Orphans plugin on the WordPress.org](https://wordpress.org/plugins/sierotki/)

## For developers

### Requirements

- [Bun](https://bun.sh) ≥ 1.3 (package manager, script runner, bundler)
- PHP ≥ 8.0 (for the translation tooling via wp-cli)

### Setup

```bash
git clone --recurse-submodules git@github.com:iworks/sierotki.git
cd sierotki
bun install          # installs devDependencies and downloads wp-cli.phar
```

The plugin uses two git submodules (`includes/iworks/options`,
`includes/iworks/rate`) — clone with `--recurse-submodules`, or run
`git submodule update --init` after a regular clone.

### Build

| Command | Description |
| --- | --- |
| `bun run dev` | Watcher — recompile assets on change (no ZIP, no i18n) |
| `bun run compile` | One-shot asset compile |
| `bun run build` | Build **both** release ZIPs (wporg + github) |
| `bun run build:wporg` | WordPress.org ZIP → `release/wporg/sierotki.zip` |
| `bun run build:github` | GitHub ZIP (with `.po`/`.mo`) → `release/github/sierotki.zip` |
| `bun run make-pot` | Regenerate `languages/sierotki.pot` and update all `.po` files |
| `bun run update-libs` | Update the vendored iWorks libraries (git pull in the submodules) |
| `bun run test` | PHPUnit (no test suite is bundled yet) |

The release ZIPs are built from a staging copy: build-time placeholders
(`PLUGIN_VERSION`, `PLUGIN_TILL_YEAR`, …) are replaced there — never in the
sources. The version lives **only** in `package.json`.

Flavor differences:

| | `build:wporg` | `build:github` |
| --- | --- | --- |
| `readme.txt` | included | excluded |
| `languages/*.po` / `*.mo` | excluded | included / compiled |
| GitHub self-updater class | excluded | included |

### Using the plugin in code

Apply orphan replacement to any string or custom field:

```php
// any string
$value = apply_filters( 'orphan_replace', 'any string' );

// a custom field
$value = apply_filters(
    'orphan_replace',
    get_post_meta( $post_id, 'meta_key', true )
);
```

Temporarily disable replacement in your own code:

```php
add_filter( 'orphan_skip_replacement', '__return_true' );
// … your code …
remove_filter( 'orphan_skip_replacement', '__return_true' );
```

Customize the orphan terms (default lists live in `etc/terms-*.txt`):

```php
// add your own terms
add_filter( 'iworks_orphan_terms', function ( $terms ) {
    $terms[] = 'your-word';
    return $terms;
} );

// or remove selected defaults
add_filter( 'iworks_orphan_terms', 'remove_iworks_orphan_terms' );
function remove_iworks_orphan_terms( $terms ) {
    $default_orphans_to_remove = array( 'oraz', 'na', 'nie' );
    foreach ( $default_orphans_to_remove as $value ) {
        if ( $key = array_search( $value, $terms ) ) {
            unset( $terms[ $key ] );
        }
    }
    return $terms;
}
```

Use your own terms definition file instead of the bundled `etc/` lists:

```php
add_filter(
    'iworks_orphan_own_terms_file',
    function ( $file ) {
        return '/own/path/to/terms-file.txt';
    }
);
```

Change the capability required to manage the plugin settings (default
`manage_options`):

```php
add_filter( 'iworks_orphans_capability', 'my_orphans_capability' );
function my_orphans_capability( $capability ) {
    return 'unfiltered_html';
}
```

#### Filter reference

| Filter | Purpose |
| --- | --- |
| `orphan_replace` | Apply replacement to any string / custom field |
| `orphan_skip_replacement` | Temporarily disable replacement |
| `orphan_get_terms` | Get the current orphan terms |
| `iworks_orphan_terms` | Add/remove/replace orphan terms (legacy alias: `iworks_orphan_therms`) |
| `iworks_orphan_own_terms_file` | Use a custom terms definition file |
| `iworks_orphan_own_terms_filename` | Use a custom terms file name |
| `iworks_orphan_terms_files_array` | Change the array of terms files to load |
| `iworks_orphans_capability` | Change the settings-page capability |
| `iworks_orphan_apply_to_all_languages` | Apply replacements to all languages |
| `orphan_allowed_filters` | Change the list of content filters the plugin hooks |
| `iworks_orphan_protected_tags` | Change HTML tags whose content is never replaced |
| `orphan_replace_acf` | Turn off replacement for selected ACF fields |
| `orphan_replace_gettext` | Turn off replacement in translation functions |

For the full behavior, see the docblocks in
`includes/iworks/class-iworks-orphan.php`.
