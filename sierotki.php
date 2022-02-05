<?php
/*
Plugin Name: Orphans
Plugin URI: http://iworks.pl/2011/02/16/sierotki/
Text Domain: sierotki
Description: Implement Polish grammar rules with orphans.
Author: Marcin Pietrzak
Version: PLUGIN_VERSION
Author URI: http://iworks.pl/
*/

include_once dirname( __FILE__ ) . '/etc/options.php';

load_plugin_textdomain( 'sierotki', false, dirname( __FILE__ ) . '/languages' );

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
	$iworks_orphan_options->set_option_function_name( 'orphang_indicator_options' );
	$iworks_orphan_options->set_option_prefix( 'iworks_orphan_' );
	if ( method_exists( $iworks_orphan_options, 'set_plugin' ) ) {
		$iworks_orphan_options->set_plugin( basename( __FILE__ ) );
	}
	$iworks_orphan_options->init();
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

do_action(
	'iworks-register-plugin',
	plugin_basename( __FILE__ ),
	__( 'Orphans', 'sierotki' ),
	'sierotki'
);

