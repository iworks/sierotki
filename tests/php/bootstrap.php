<?php
/**
 * Unit test bootstrap.
 *
 * Loads the plugin's pure PHP class with a minimal WordPress function
 * stub set, so tests can run without a WordPress install.
 *
 * @package sierotki
 */

define( 'WPINC', 'wp-includes' );
define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
define( 'IWORKS_ORPHANS_PLUGIN_DIR', ABSPATH );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'PLUGIN_VERSION_TEST', 'test' );

// --- Minimal WordPress stubs -------------------------------------------------

$GLOBALS['test_filters']    = array();
$GLOBALS['test_is_admin']   = false;
$GLOBALS['test_is_feed']    = false;
$GLOBALS['test_locale']     = 'pl_PL';
$GLOBALS['test_orphan_options'] = array();
$GLOBALS['test_terms']      = array();

function add_action( $hook, $cb, $priority = 10, $args = 1 ) {
	return true;
}
function add_filter( $hook, $cb, $priority = 10, $args = 1 ) {
	return true;
}
function apply_filters( $hook, $value, ...$args ) {
	global $test_filters;
	foreach ( ( $test_filters[ $hook ] ?? array() ) as $callback ) {
		$value = call_user_func( $callback, $value, ...$args );
	}
	return $value;
}
function add_test_filter( $hook, $callback ) {
	global $test_filters;
	$test_filters[ $hook ][] = $callback;
}
function clear_test_filters() {
	global $test_filters;
	$test_filters = array();
}
function has_filter( $hook, $callback = false ) {
	global $test_filters;
	return ! empty( $test_filters[ $hook ] );
}
function remove_filter( $hook, $callback, $priority = 10 ) {
	return true;
}
function get_post_meta( $object_id, $meta_key, $single = false ) {
	global $test_post_meta;
	return $test_post_meta[ $object_id ][ $meta_key ] ?? '';
}
function apply_filters_deprecated( $hook, $args, $version, $replacement = null ) {
	return $args[0];
}
function current_filter() {
	global $test_current_filter;
	return $test_current_filter ?? '';
}
function is_admin() {
	global $test_is_admin;
	return $test_is_admin;
}
function is_feed() {
	global $test_is_feed;
	return $test_is_feed;
}
function get_locale() {
	global $test_locale;
	return $test_locale;
}
function get_transient( $name ) {
	return false;
}
function set_transient( $name, $value, $expiry = 0 ) {
	return true;
}
function delete_transient( $name ) {
	return true;
}
function wp_cache_get( $key, $group = '' ) {
	return false;
}
function wp_cache_set( $key, $value, $group = '' ) {
	return true;
}
function wp_salt( $scheme = 'auth' ) {
	return 'test-salt';
}
function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}
function plugin_dir_path( $file ) {
	return trailingslashit( dirname( $file ) );
}
function plugin_dir_url( $file ) {
	return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}
function trailingslashit( $path ) {
	return rtrim( $path, '/' ) . '/';
}
function __( $text, $domain = 'default' ) {
	return $text;
}
function esc_html__( $text, $domain = 'default' ) {
	return $text;
}
function esc_attr__( $text, $domain = 'default' ) {
	return $text;
}
function esc_html( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8', false );
}
function esc_attr( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8', false );
}
function _x( $text, $context, $domain = 'default' ) {
	return $text;
}
function is_plugin_active( $plugin ) {
	return false;
}
function get_post_type( $post ) {
	if ( is_object( $post ) && isset( $post->post_type ) ) {
		return $post->post_type;
	}
	return false;
}
function get_queried_object() {
	global $test_queried_object;
	return $test_queried_object ?? null;
}

/**
 * Minimal WP_Term stand-in for taxonomy tests.
 */
class WP_Term {
	public $term_id;
	public $taxonomy;
	public $name;
	public $description;

	public function __construct( $args = array() ) {
		foreach ( $args as $key => $value ) {
			$this->{$key} = $value;
		}
	}
}

/**
 * Minimal WP_Post stand-in for entry-filter tests.
 */
class WP_Post {
	public $ID;
	public $post_type;
	public $post_title;
	public $post_content;

	public function __construct( $args = array() ) {
		foreach ( $args as $key => $value ) {
			$this->{$key} = $value;
		}
	}
}
function wp_strip_all_tags( $string, $remove_breaks = false ) {
	$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
	$string = strip_tags( $string );
	if ( $remove_breaks ) {
		$string = preg_replace( '/[\\r\\n\\t ]+/', ' ', $string );
	}
	return trim( $string );
}
function get_orphan_options() {
	return new OrphanFakeOptions();
}
function get_option( $name, $default = false ) {
	global $test_orphan_options;
	return $test_orphan_options[ $name ] ?? $default;
}

/**
 * Minimal iworks_options stand-in: get_option()/get_all_options() backed by
 * a global array the tests can manipulate.
 */
class OrphanFakeOptions {
	public function get_option( $name, $default = false ) {
		global $test_orphan_options;
		return $test_orphan_options[ $name ] ?? $default;
	}
	public function get_all_options() {
		global $test_orphan_options;
		return $test_orphan_options;
	}
	public function get_pagehook() {
		return '';
	}
}

// --- Load the plugin class ---------------------------------------------------

// The replacement engine uses the core WP_HTML_Tag_Processor; load the real
// HTML API classes straight from the local WordPress checkout. The path is
// resolved from IWORKS_ORPHANS_PLUGIN_DIR (wp-content/plugins/dev-sierotki)
// up to the WordPress root; skipped when the layout differs.
$wp_includes = dirname( IWORKS_ORPHANS_PLUGIN_DIR, 3 ) . '/wp-includes';
if ( ! class_exists( 'WP_HTML_Tag_Processor' ) && is_dir( $wp_includes ) ) {
	require $wp_includes . '/class-wp-token-map.php';
	foreach ( array(
		'class-wp-html-decoder.php',
		'class-wp-html-span.php',
		'class-wp-html-text-replacement.php',
		'class-wp-html-token.php',
		'class-wp-html-attribute-token.php',
		'class-wp-html-tag-processor.php',
	) as $html_api_file ) {
		require $wp_includes . '/html-api/' . $html_api_file;
	}
}

// The language-file loader uses WP_Filesystem_Direct; load the real one
// from the local WordPress checkout (WP_Error is a tiny stub here).
if ( ! class_exists( 'WP_Filesystem_Direct' ) && is_dir( $wp_includes ) ) {
	require $wp_includes . '/../wp-admin/includes/class-wp-filesystem-base.php';
	require $wp_includes . '/../wp-admin/includes/class-wp-filesystem-direct.php';
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $errors = array();
		public function __construct( $code = '', $message = '' ) {
			$this->errors[ $code ] = $message;
		}
		public function get_error_message( $code = '' ) {
			return reset( $this->errors ) ?: '';
		}
	}
}

require dirname( __DIR__, 2 ) . '/includes/iworks/class-iworks-orphan.php';
