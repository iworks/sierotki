<?php

function orphang_indicator_options() {
	$options = array();
	/**
	 * main settings
	 */
	$options['index'] = array(
		'use_tabs' => true,
		'version'  => '0.0',
		'page_title' => __( 'Orphans configuration', 'sierotki' ),
		'menu_title' => __( 'Orphans', 'sierotki' ),
		'menu' => 'theme',
		'enqueue_scripts' => array(),
		'enqueue_styles' => array(),
		'options'  => array(
			array(
				'type'              => 'heading',
				'label'             => __( 'Entries', 'sierotki' ),
			),
			/**
			 * Since 2.6.8
			 */
			array(
				'name' => 'post_type',
				'type'              => 'select2',
				'th'                => __( 'Post types', 'sierotki' ),
				'default'           => array( 'post', 'page' ),
				'options' => iworks_orphan_post_types(),
				'multiple' => true,
			),
			array(
				'name' => 'the_title',
				'th' => __( 'Title', 'sierotki' ),
				'type'  => 'checkbox',
				'description' => __( 'Enabled the substitution of orphans in the post_title.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'dafault' => 1,
				'classes' => array( 'switch-button' ),
			),
			array(
				'name' => 'the_excerpt',
				'th' => __( 'Excerpt', 'sierotki' ),
				'type'  => 'checkbox',
				'description' => __( 'Enabled the substitution of orphans in the excerpt.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes' => array( 'switch-button' ),
				'dafault' => 1,
			),
			array(
				'name' => 'the_content',
				'th' => __( 'Content', 'sierotki' ),
				'type'  => 'checkbox',
				'description' => __( 'Enabled the substitution of orphans in the content.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes' => array( 'switch-button' ),
				'dafault' => 1,
			),
			array(
				'name' => 'comment_text',
				'type'  => 'checkbox',
				'th' => __( 'Comments', 'sierotki' ),
				'description' => __( 'Enabled the substitution of orphans in the comments.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'dafault' => 1,
				'classes' => array( 'switch-button' ),
			),
			array(
				'type'              => 'heading',
				'label'             => __( 'Widgets', 'sierotki' ),
			),
			/**
			 * Since 2.6.6
			 */
			array(
				'name' => 'widget_title',
				'th' => __( 'Widget title', 'sierotki' ),
				'type'  => 'checkbox',
				'description' => __( 'Enabled the substitution of orphans in the widget title.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes' => array( 'switch-button' ),
				'dafault' => 1,
			),
			/**
			 * Since 2.6.6
			 */
			array(
				'name' => 'widget_text',
				'th' => __( 'Widget text', 'sierotki' ),
				'type'  => 'checkbox',
				'description' => __( 'Enabled the substitution of orphans in the widget text.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes' => array( 'switch-button' ),
				'dafault' => 1,
			),
			/**
			 * Since 2.6.6
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Taxonomies', 'sierotki' ),
			),
			array(
				'name' => 'taxonomies',
				'type'              => 'select2',
				'th'                => __( 'Taxonomies', 'sierotki' ),
				'default'           => array( 'category', 'post_tag', 'post_format' ),
				'options' => iworks_orphan_taxonomies(),
				'multiple' => true,
			),
			array(
				'name' => 'taxonomy_title',
				'th' => __( 'Title', 'sierotki' ),
				'type'  => 'checkbox',
				'description' => __( 'Enabled the substitution of orphans in the taxonomy title.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes' => array( 'switch-button' ),
				'default' => 1,
			),
			array(
				'name' => 'term_description',
				'th' => __( 'Description', 'sierotki' ),
				'type'  => 'checkbox',
				'description' => __( 'Enabled the substitution of orphans in the taxonomy description.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes' => array( 'switch-button' ),
				'default' => 1,
			),
			array(
				'type'              => 'heading',
				'label'             => __( 'Miscellaneous', 'sierotki' ),
			),
			/**
			 * Since 2.6.8
			 */
			array(
				'name' => 'get_the_author_description',
				'type'  => 'checkbox',
				'th' => __( 'Author description', 'sierotki' ),
				'description' => __( 'Enabled the substitution of orphans in the author description.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'dafault' => 1,
				'classes' => array( 'switch-button' ),
			),
			array(
				'name' => 'ignore_language',
				'th' => __( 'Ignore language', 'sierotki' ),
				'type'  => 'checkbox',
				'description' => __( 'Allow to use plugin with another languages then Polish.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes' => array( 'switch-button' ),
				'default' => 0,
			),
			array(
				'name' => 'numbers',
				'th' => __( 'Keep numbers together', 'sierotki' ),
				'type'  => 'checkbox',
				'description' => __( 'Allow to keep together phone number or strings with space between numbers.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes' => array( 'switch-button' ),
			),
			array(
				'name' => 'own_orphans',
				'th' => __( 'User definied orphans', 'sierotki' ),
				'type' => 'textarea',
				'description' => __( 'Use a comma to separate orphans.', 'sierotki' ),
				'sanitize_callback' => 'esc_html',
				'classes' => array( 'large-text' ),
				'rows' => 10,
			),
			array(
				'name' => 'post_meta',
				'th' => __( 'Entries custom fields', 'sierotki' ),
				'type' => 'textarea',
				'description' => __( 'Use a comma to separate custom fields name.', 'sierotki' ),
				'sanitize_callback' => 'esc_html',
				'classes' => array( 'large-text' ),
				'rows' => 10,
			),
		),
		'metaboxes' => array(
			'loved_this_plugin' => array(
				'title' => __( 'Loved this Plugin?', 'sierotki' ),
				'callback' => 'iworks_orphan_options_loved_this_plugin',
				'context' => 'side',
				'priority' => 'core',
			),
			'need_assistance' => array(
				'title' => __( 'Need Assistance?', 'sierotki' ),
				'callback' => 'iworks_orphans_options_need_assistance',
				'context' => 'side',
				'priority' => 'core',
			),
		),
	);
	return $options;
}

function iworks_orphan_options_loved_this_plugin( $iworks_orphan ) {

?>
<p><?php _e( 'Below are some links to help spread this plugin to other users', 'sierotki' ); ?></p>
<ul>
    <li><a href="https://wordpress.org/support/plugin/sierotki/reviews/#new-post"><?php _e( 'Give it a five stars on WordPress.org', 'sierotki' ); ?></a></li>
    <li><a href="<?php _ex( 'https://wordpress.org/plugins/sierotki/', 'plugin home page on WordPress.org', 'sierotki' ); ?>"><?php _e( 'Link to it so others can easily find it', 'sierotki' ); ?></a></li>
</ul>
<?php
}
function iworks_orphan_taxonomies() {
	$data = array();
	$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
	foreach ( $taxonomies as $taxonomy ) {
		$data[ $taxonomy->name ] = $taxonomy->labels->name;
	}
	return $data;
}
function iworks_orphan_post_types() {
	$args = array(
		'public' => true,
	);
	$p = array();
	$post_types = get_post_types( $args, 'names' );
	foreach ( $post_types as $post_type ) {
		$a = get_post_type_object( $post_type );
		$p[ $post_type ] = $a->labels->name;
	}
	return $p;
}

function iworks_orphans_options_need_assistance( $iworks_orphans ) {

?>
<p><?php _e( 'Problems? The links bellow can be very helpful to you', 'sierotki' ); ?></p>
<ul>
    <li><a href="<?php _ex( 'https://wordpress.org/support/plugin/sierotki/', 'link to support forum on WordPress.org', 'sierotki' ); ?>"><?php _e( 'WordPress Help Forum', 'sierotki' ); ?></a></li>
</ul>
<?php
}
