<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$iworks_orphan_uninstall_keys = array(
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

foreach ( $iworks_orphan_uninstall_keys as $iworks_orphan_uninstall_key ) {
	delete_option( 'iworks_orphan_' . $iworks_orphan_uninstall_key );
}

