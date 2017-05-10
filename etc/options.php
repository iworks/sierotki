<?php

function orphang_indicator_options() {
    $options = array();
    /**
     * main settings
     */
    $options['index'] = array(
        'use_tabs' => true,
        'version'  => '0.0',
        'page_title' => __('Orphans configuration', 'sierotki'),
        'menu_title' => __('Orphans', 'sierotki'),
        'menu' => 'theme',
        'enqueue_scripts' => array(
        ),
        'enqueue_styles' => array(
        ),
        'options'  => array(
            array(
                'type'              => 'heading',
                'label'             => __( 'Entries', 'upprev' ),
            ),
            /**
             * Since 2.6.8
             */
            array(
                'name' => 'post_type',
                'type'              => 'select2',
                'th'                => __( 'Post types', 'reading-position-indicator' ),
                'default'           => array( 'post', 'page' ),
                'options' => iworks_orphan_post_types(),
                'multiple' => true,
            ),
            array(
                'name' => 'the_title',
                'th' => __( 'Title:', 'sierotki' ),
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
            ),
            array(
                'name' => 'the_content',
                'th' => __( 'Content', 'sierotki' ),
                'type'  => 'checkbox',
                'description' => __( 'Enabled the substitution of orphans in the content.', 'sierotki' ),
                'sanitize_callback' => 'absint',
                'classes' => array( 'switch-button' ),
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
                'label'             => __( 'Widgets', 'upprev' ),
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
            ),
            /**
             * Since 2.6.6
             */
            array(
                'type'              => 'heading',
                'label'             => __( 'Taxonomies', 'upprev' ),
            ),
            array(
                'name' => 'taxonomies',
                'type'              => 'select2',
                'th'                => __( 'Display On', 'reading-position-indicator' ),
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
                'type'              => 'heading',
                'label'             => __( 'Other', 'upprev' ),
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
                'th' => __( 'Keep numbers together:', 'sierotki' ),
                'type'  => 'checkbox',
                'description' => __( 'Allow to keep together phone number or strings with space between numbers.', 'sierotki' ),
                'sanitize_callback' => 'absint',
                'classes' => array( 'switch-button' ),
            ),
            array(
                'name' => 'own_orphans',
                'th' => __( 'User definied orphans:', 'sierotki' ),
                'type' => 'text',
                'description' => __( 'Use a comma to separate orphans.', 'sierotki' ),
                'sanitize_callback' => 'esc_html',
                'classes' => array( 'large-text' ),
            ),
        ),
    );
    return $options;
}

function iworks_orphan_taxonomies() {
    $data = array();
    $taxonomies = get_taxonomies( array( 'public' => true, ), 'objects' );
    foreach( $taxonomies as $taxonomy ) {
        $data[$taxonomy->name] = $taxonomy->labels->name;
    }
    return $data;
}
function iworks_orphan_post_types() {
    $args = array(
        'public' => true,
    );
    $p = array();
    $post_types = get_post_types( $args, 'names' );
    foreach( $post_types as $post_type ) {
        $a = get_post_type_object( $post_type );
        $p[$post_type] = $a->labels->name;
    }
    return $p;
}

