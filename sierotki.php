<?php
/*
Plugin Name: Orphans
Text Domain: sierotki
Plugin URI: PLUGIN_URI
Description: PLUGIN_TAGLINE
Version: PLUGIN_VERSION
Author: Marcin Pietrzak
Author URI: http://iworks.pl/
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Copyright 2025-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
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

include_once dirname( __FILE__ ) . '/etc/options.php';

if ( ! defined( 'HDOM_TYPE_ELEMENT' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/simple_html_dom.php';
}

$includes = dirname( __FILE__ ) . '/includes';

require_once $includes . '/iworks/class-iworks-orphan.php';
if ( ! class_exists( 'iworks_rate' ) ) {
	include_once $includes . '/iworks/rate/rate.php';
}
/**
 * since 2.6.8
 */
if ( ! class_exists( 'iworks_options' ) ) {
	include_once $includes . '/iworks/options/options.php';
}

new iworks_orphan();

register_activation_hook( __FILE__, 'iworks_orphan_activate' );
register_deactivation_hook( __FILE__, 'iworks_orphan_deactivate' );

/**
 * load options
 *
 * since 2.6.8
 *
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
 * Activate plugin function
 *
 * @since 2.6.0
 *
 */
function iworks_orphan_activate() {
	$iworks_orphan_options = get_orphan_options();
	$iworks_orphan_options->activate();
	iworks_orphan_change_options_autoload_status( 'yes' );
}

/**
 * Deactivate plugin function
 *
 * @since 2.6.0
 *
 */
function iworks_orphan_deactivate() {
	iworks_orphan_change_options_autoload_status( 'no' );
}
/**
 * Activate/Deactivate helper function
 *
 * @since 2.6.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $status status of autoload, possible values: yes or no
 *
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

