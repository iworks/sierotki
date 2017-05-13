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

include_once dirname( __FILE__ ).'/etc/options.php';


$vendor = dirname( __FILE__ ).'/vendor';

require_once $vendor . '/iworks/orphan.php';
if ( ! class_exists( 'iworks_rate' ) ) {
	include_once $vendor . '/iworks/rate/rate.php';
}
/**
 * since 2.6.8
 */
if ( ! class_exists( 'iworks_options' ) ) {
	include_once $vendor.'/iworks/options/options.php';
}

new iworks_orphan( __FILE__ );

register_activation_hook( __FILE__, 'iworks_orphan_activate' );
register_deactivation_hook( __FILE__, 'iworks_orphan_deactivate' );

/**
 * load options
 *
 * since 2.6.8
 *
 */
function get_orphan_options() {
	$options = new iworks_options();
	$options->set_option_function_name( 'orphang_indicator_options' );
	$options->set_option_prefix( 'iworks_orphan_' );
	$options->init();
	return $options;
}

/**
 * Activate plugin function
 *
 * @since 2.6.0
 *
 */
function iworks_orphan_activate() {
	$options = get_orphan_options();
	$options->activate();
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
	$options_keys = array(
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
	foreach ( $options_keys as $key ) {
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

