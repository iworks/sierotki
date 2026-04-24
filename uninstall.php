<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$orphan_keys = array(
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

foreach ( $orphan_keys as $orphan_key ) {
	delete_option( 'iworks_orphan_'.$orphan_key );
}

