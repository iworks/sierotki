<?php
/**
 * Plugin Name:       Orphans
 * Plugin URI:        PLUGIN_URI
 * Description:       PLUGIN_TAGLINE
 * Version:           PLUGIN_VERSION
 * Author:            Marcin Pietrzak
 * Author URI:        http://iworks.pl/
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       sierotki
 * Domain Path:       /languages
 *
 * @package WordPress
 * @subpackage Sierotki
 * @author     Marcin Pietrzak <marcin@iworks.pl>
 * @copyright  2025-PLUGIN_TILL_YEAR Marcin Pietrzak
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0 or later
 */

/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Define plugin constants
define( 'IWORKS_ORPHANS_VERSION', 'PLUGIN_VERSION' );
define( 'IWORKS_ORPHANS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IWORKS_ORPHANS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once IWORKS_ORPHANS_PLUGIN_DIR . 'etc/options.php';

// Include simple_html_dom if not already loaded
if ( ! defined( 'HDOM_TYPE_ELEMENT' ) ) {
	require_once IWORKS_ORPHANS_PLUGIN_DIR . 'vendor/simple_html_dom.php';
}

// Set includes directory path
$includes = IWORKS_ORPHANS_PLUGIN_DIR . 'includes';

// Load main plugin class
require_once $includes . '/iworks/class-iworks-orphan.php';

// Include iWorks Rate class if not already loaded
if ( ! class_exists( 'iworks_rate' ) ) {
	include_once $includes . '/iworks/rate/rate.php';
}

// Include iWorks Options class if not already loaded (since 2.6.8)
if ( ! class_exists( 'iworks_options' ) ) {
	include_once $includes . '/iworks/options/options.php';
}

// Initialize the plugin
$iworks_orphan = new iworks_orphan();

// Register activation and deactivation hooks
register_activation_hook( __FILE__, 'iworks_orphan_activate' );
register_deactivation_hook( __FILE__, 'iworks_orphan_deactivate' );

/**
 * Retrieves and initializes the plugin options.
 *
 * Creates a new instance of the iWorks options class, configures it with
 * the appropriate settings, and initializes the options.
 *
 * @since 2.6.8
 *
 * @return iworks_options Initialized instance of the iWorks options class.
 */
function get_orphan_options() {
	$iworks_orphan_options = new iworks_options();
	$iworks_orphan_options->set_option_function_name( 'orphans_indicator_options' );
	$iworks_orphan_options->set_option_prefix( 'iworks_orphan_' );
	if ( method_exists( $iworks_orphan_options, 'set_plugin' ) ) {
		$iworks_orphan_options->set_plugin( basename( __FILE__ ) );
	}
	$iworks_orphan_options->options_init();
	return $iworks_orphan_options;
}

/**
 * Handles plugin activation.
 *
 * Initializes plugin options and sets autoload status to 'yes' for better performance.
 *
 * @since 2.6.0
 *
 * @return void
 */
function iworks_orphan_activate() {
	$iworks_orphan_options = get_orphan_options();
	$iworks_orphan_options->activate();
	iworks_orphan_change_options_autoload_status( 'yes' );
}

/**
 * Handles plugin deactivation.
 *
 * Updates autoload status to 'no' for plugin options to improve performance
 * when the plugin is not active.
 *
 * @since 2.6.0
 *
 * @return void
 */
function iworks_orphan_deactivate() {
	iworks_orphan_change_options_autoload_status( 'no' );
}
/**
 * Updates autoload status for plugin options in the database.
 *
 * This helper function is used during plugin activation and deactivation
 * to manage the autoload behavior of plugin options.
 *
 * @since 2.6.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $status Autoload status. Accepts 'yes' or 'no'.
 *
 * @return void
 */
function iworks_orphan_change_options_autoload_status( $status ) {
	if ( ! preg_match( '/^(yes|no)$/', $status ) ) {
		return;
	}
	$iworks_orphan_options_keys = array(
		'comment_text',
		'initialized',
		'numbers',
		'own_orphans',
		'the_content',
		'the_excerpt',
		'the_title',
		'woocommerce_product_title',
		'woocommerce_short_description',
	);
	global $wpdb;
	foreach ( $iworks_orphan_options_keys as $key ) {
		$wpdb->update(
			$wpdb->options,
			array(
				'autoload' => $status,
			),
			array(
				'option_name' => sprintf( 'iworks_orphan_%s', $key ),
			)
		);
	}
}
