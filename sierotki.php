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

require_once( dirname( __FILE__ ) ).'/vendor/iworks/orphan.php';
include_once( dirname( __FILE__ ) ).'/vendor/iworks/rate/rate.php';

new iworks_orphan();

register_activation_hook( __FILE__, 'iworks_orphan_activate' );
register_deactivation_hook( __FILE__, 'iworks_orphan_deactivate' );

/**
 * Activate plugin function
 *
 * @since 2.6.0
 *
 */
function iworks_orphan_activate() {
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
	__('Orphans',
	'sierotki' ),
	__( 'https://wordpress.org/plugins/sierotki/', 'sierotki' )
);

