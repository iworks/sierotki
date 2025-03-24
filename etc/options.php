<?php

function orphans_indicator_options() {
	/**
	 * cache
	 */
	if ( apply_filters( 'orphans_indicator_options_use_cache', true ) ) {
		$cached = wp_cache_get( 'orphans_indicator_options', 'iworks_orphans' );
		if ( ! empty( $cached ) ) {
			return apply_filters(
				'orphans_indicator_options_cached',
				$cached
			);
		}
	}
	/**
	 * Query Monitor profiling
	 */
	do_action( 'qm/start', 'orphans_indicator_options' );
	$options = array();
	/**
	 * main settings
	 */
	$options['index'] = array(
		'use_tabs'        => true,
		'version'         => '0.0',
		'page_title'      => __( 'Orphans Configuration', 'sierotki' ),
		'menu_title'      => __( 'Orphans', 'sierotki' ),
		'menu'            => 'theme',
		'enqueue_scripts' => array(),
		'enqueue_styles'  => array(),
		'options'         => array(
			array(
				'type'  => 'heading',
				'label' => __( 'Terms', 'sierotki' ),
				'since' => '3.1.4',
			),
			array(
				'name'     => 'language',
				'type'     => 'radio',
				'th'       => __( 'Language File', 'sierotki' ),
				'default'  => 'function_get_locale',
				'options'  => array(
					'function_get_locale' => array(
						'label'       => __( 'Try to use the site locale to determine the file name', 'sierotki' ),
						'description' => __( 'Select if you want the plugin to determine which file to load based on the site\'s language settings.', 'sierotki' ),
					),
					'pl_PL'               => array(
						'label'       => __( 'Polish', 'sierotki' ),
						'description' => __( 'Select if you want to force the loading of Polish language rules.', 'sierotki' ),
					),
					'cs_CZ'               => array(
						'label'       => __( 'Czech', 'sierotki' ),
						'description' => __( 'Select if you want to force the loading of Czech language rules.', 'sierotki' ),
					),
					'en'                  => array(
						'label'       => __( 'English', 'sierotki' ),
						'description' => __( 'Select if you want to force the loading of English short words.', 'sierotki' ),
					),
					array(
						'label'       => __( 'Do not load any files', 'sierotki' ),
						'description' => __( 'Select if you want to rely only on your terms.', 'sierotki' ),
					),
				),
				'multiple' => true,
				'default'  => 'pl_PL',
				'since'    => '3.1.4',
				/**
				 * sanitize_callback for multiple (remove after it will be implemented into
				 * iWorks_Options class.
				 *
				 * https://github.com/iworks/wordpress-options-class/issues/4
				 */
				'sanitize_callback' => 'iworks_orphans_sanitize_callback_multiple',
			),
			array(
				'name'              => 'own_orphans',
				'th'                => __( 'User Definied Orphans', 'sierotki' ),
				'type'              => 'textarea',
				'description'       => __( 'Use a comma to separate orphans.', 'sierotki' ),
				'sanitize_callback' => 'esc_html',
				'classes'           => array( 'large-text' ),
				'rows'              => 10,
			),
			array(
				'type'  => 'heading',
				'label' => __( 'Entries', 'sierotki' ),
			),
			/**
			 * Since 2.6.8
			 */
			'post_type' => array(
				'name'     => 'post_type',
				'type'     => 'select2',
				'th'       => __( 'Post Types', 'sierotki' ),
				'default'  => array( 'post', 'page' ),
				'options'  => apply_filters(
					'orphan_get_post_types',
					array(
						'post' => __( 'Posts', 'sierotki' ),
						'page' => __( 'Pages', 'sierotki' ),
					)
				),
				'multiple' => true,
				/**
				 * sanitize_callback for multiple (remove after it will be implemented into
				 * iWorks_Options class.
				 *
				 * https://github.com/iworks/wordpress-options-class/issues/4
				 */
				'sanitize_callback' => 'iworks_orphans_sanitize_callback_multiple',
			),
			array(
				'name'              => 'the_title',
				'th'                => __( 'Title', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Enabled the substitution of orphans in the post_title.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'default'           => 1,
				'classes'           => array( 'switch-button' ),
			),
			array(
				'name'              => 'the_excerpt',
				'th'                => __( 'Excerpt', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Enabled the substitution of orphans in the excerpt.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => 1,
			),
			array(
				'name'              => 'the_content',
				'th'                => __( 'Content', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Enabled the substitution of orphans in the content.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => true,
			),
			array(
				'name'              => 'comment_text',
				'type'              => 'checkbox',
				'th'                => __( 'Comments', 'sierotki' ),
				'description'       => __( 'Enabled the substitution of orphans in the comments.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'default'           => 1,
				'classes'           => array( 'switch-button' ),
			),
			array(
				'type'  => 'heading',
				'label' => __( 'Widgets', 'sierotki' ),
			),
			/**
			 * Since 2.6.6
			 */
			array(
				'name'              => 'widget_title',
				'th'                => __( 'Widget Title', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Enabled the substitution of orphans in the widget title.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => 1,
			),
			/**
			 * Since 2.6.6
			 */
			array(
				'name'              => 'widget_text',
				'th'                => __( 'Widget Text', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Enabled the substitution of orphans in the widget text.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => 1,
			),
			/**
			 * Since 2.8.1
			 */
			array(
				'name'              => 'widget_block_content',
				'th'                => __( 'Widget Block Content', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Enabled the substitution of orphans in the widget blocks.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => 1,
			),
			/**
			 * Since 2.6.6
			 */
			array(
				'type'  => 'heading',
				'label' => __( 'Taxonomies', 'sierotki' ),
			),
			array(
				'name'     => 'taxonomies',
				'type'     => 'select2',
				'th'       => __( 'Taxonomies', 'sierotki' ),
				'default'  => array( 'category', 'post_tag', 'post_format' ),
				'options'  => iworks_orphan_taxonomies(),
				'multiple' => true,
				/**
				 * sanitize_callback for multiple (remove after it will be implemented into
				 * iWorks_Options class.
				 *
				 * https://github.com/iworks/wordpress-options-class/issues/4
				 */
				'sanitize_callback' => 'iworks_orphans_sanitize_callback_multiple',
			),
			array(
				'name'              => 'taxonomy_title',
				'th'                => __( 'Title', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Enabled the substitution of orphans in the taxonomy title.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => 1,
			),
			array(
				'name'              => 'term_description',
				'th'                => __( 'Description', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Enabled the substitution of orphans in the taxonomy description.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => 1,
			),
			array(
				'type'  => 'heading',
				'label' => __( 'Miscellaneous', 'sierotki' ),
			),
			/**
			 * Replace in Translations functions.
			 *
			 * Since 3.1.0
			 */
			array(
				'name'              => 'gettext',
				'type'              => 'checkbox',
				'th'                => __( 'Translation Functions', 'sierotki' ),
				'description'       => __( 'Enabled the substitution of orphans in Translations functions. <a href="https://developer.wordpress.org/themes/functionality/internationalization/" target="_blank">Read more.</a> <b>WARNING: this can slow your site!</b>', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'default'           => 0,
				'classes'           => array( 'switch-button' ),
			),
			/**
			 * Since 2.6.8
			 */
			array(
				'name'              => 'get_the_author_description',
				'type'              => 'checkbox',
				'th'                => __( 'Author Description', 'sierotki' ),
				'description'       => __( 'Enabled the substitution of orphans in the author description.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'default'           => 1,
				'classes'           => array( 'switch-button' ),
			),
			array(
				'name'              => 'ignore_language',
				'th'                => __( 'Ignore Language', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Allow to use plugin with another languages then Polish.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => 0,
			),
			array(
				'name'              => 'menu_title',
				'th'                => __( 'Menu Title', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Enabled the substitution of orphans in the menu title.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => 1,
			),
			array(
				'name'              => 'numbers',
				'th'                => __( 'Keep Numbers Together', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Allow to keep together phone number or strings with space between numbers.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
			),
			array(
				'name'              => 'attributes',
				'th'                => __( 'Protect Tag Attributes', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Allow to ignore spaces in "class", "style" and "data-*" attributes.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => 1,
			),
			array(
				'name'              => 'post_meta',
				'th'                => __( 'Entries Custom Fields', 'sierotki' ),
				'type'              => 'textarea',
				'description'       => __( 'Use a comma to separate custom fields name (meta fields).', 'sierotki' ),
				'sanitize_callback' => 'esc_html',
				'classes'           => array( 'large-text' ),
				'rows'              => 10,
			),
			array(
				'type'  => 'heading',
				'label' => __( 'Export/Import', 'sierotki' ),
				'since' => '3.3.0',
			),
			array(
				'type'  => 'subheading',
				'label' => __( 'Export', 'sierotki' ),
				'since' => '3.3.0',
			),
			array(
				'name'              => 'export_extra',
				'th'                => __( 'Add extra information', 'sierotki' ),
				'type'              => 'checkbox',
				'description'       => __( 'Add site configuration data like language, url. Nothing sensitive.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'default'           => 1,
			),
			array(
				'name'  => 'export',
				'value' => __( 'Export JSON', 'sierotki' ),
				'type'  => 'button',
				'since' => '3.3.0',
			),
			array(
				'type'  => 'subheading',
				'label' => __( 'Import', 'sierotki' ),
				'since' => '3.3.0',
			),
			array(
				'name'     => 'import',
				'type'     => 'serialize',
				'callback' => 'iworks_orphans_options_import',
				'since'    => '3.3.0',
			),
		),
		'metaboxes'       => array(
			'assistance' => array(
				'title'    => __( 'We are waiting for your message', 'sierotki' ),
				'callback' => 'iworks_orphans_options_need_assistance',
				'context'  => 'side',
				'priority' => 'core',
			),
			'love'       => array(
				'title'    => __( 'I love what I do!', 'sierotki' ),
				'callback' => 'iworks_orphan_options_loved_this_plugin',
				'context'  => 'side',
				'priority' => 'core',
			),
		),
	);
	/**
	 * integrations
	 */
	$integrations = iworks_orphan_options_check_available_integrations();
	if ( ! empty( $integrations ) ) {
		$options['index']['options'][] = array(
			'type'  => 'heading',
			'label' => __( 'Integrations', 'sierotki' ),
		);
		if ( in_array( 'acf.php', $integrations ) ) {
			$options['index']['options'][] = array(
				'type'  => 'subheading',
				'label' => __( 'Advanced Custom Fields', 'sierotki' ),
			);
			$options['index']['options'][] = array(
				'name'              => 'acf_text',
				'type'              => 'checkbox',
				'th'                => __( 'Text', 'sierotki' ),
				'description'       => __( 'Enabled the substitution of orphans in text fields.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'default'           => 0,
				'classes'           => array( 'switch-button' ),
			);
			$options['index']['options'][] = array(
				'name'              => 'acf_textarea',
				'type'              => 'checkbox',
				'th'                => __( 'Textarea', 'sierotki' ),
				'description'       => __( 'Enabled the substitution of orphans in textarea fields. (Include WYSIWYG).', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'default'           => 0,
				'classes'           => array( 'switch-button' ),
			);
			$options['index']['options'][] = array(
				'name'              => 'acf_wysiwyg',
				'type'              => 'checkbox',
				'th'                => __( 'WYSIWYG', 'sierotki' ),
				'description'       => __( 'Enabled the substitution of orphans in WYSIWYG fields.', 'sierotki' ),
				'sanitize_callback' => 'absint',
				'default'           => 0,
				'classes'           => array( 'switch-button' ),
			);
		}
	}
	/**
	 * cache it
	 */
	if ( apply_filters( 'orphans_indicator_options_use_cache', true ) ) {
		wp_cache_set( 'orphans_indicator_options', $options, 'iworks_orphans' );
	}
	/**
	 * Query Monitor profiling
	 */
	do_action( 'qm/stop', 'orphans_indicator_options' );
	return $options;
}

/**
 * check available integrations
 *
 * @since 2.9.8
 */
function iworks_orphan_options_check_available_integrations() {
	$integrations = array();
	$plugins      = get_option( 'active_plugins' );
	/**
	 * check multisite network wide plugins.
	 *
	 * @since 3.0.3
	 */
	if ( is_multisite() ) {
		$network_plugins = array_flip( get_site_option( 'active_sitewide_plugins' ) );
		$plugins         = array_merge( $plugins, $network_plugins );
		$plugins         = array_unique( $plugins );
	}
	/**
	 * no plugins
	 */
	if ( empty( $plugins ) ) {
		return $integrations;
	}
	/**
	 * check ACF plugin
	 *
	 * @since 2.9.7
	 */
	foreach ( $plugins as $plugin ) {
		if ( preg_match( '/acf\.php$/', $plugin ) ) {
			$integrations[] = basename( $plugin );
		}
	}
	return $integrations;
}

function iworks_orphan_options_loved_this_plugin( $iworks_orphan ) {
	$content = apply_filters( 'iworks_rate_love', '', 'sierotki' );
	if ( ! empty( $content ) ) {
		echo wp_kses_post( $content );
		return;
	}
	?>
<p><?php esc_html_e( 'Below are some links to help spread this plugin to other users', 'sierotki' ); ?></p>
<ul>
	<li><a href="https://wordpress.org/support/plugin/sierotki/reviews/#new-post"><?php _e( 'Give it a five stars on WordPress.org', 'sierotki' ); ?></a></li>
	<li><a href="<?php echo esc_html( _x( 'https://wordpress.org/plugins/sierotki/', 'plugin home page on WordPress.org', 'sierotki' ) ); ?>"><?php _e( 'Link to it so others can easily find it', 'sierotki' ); ?></a></li>
</ul>
	<?php
}
function iworks_orphan_taxonomies() {
	$data       = array();
	$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
	foreach ( $taxonomies as $taxonomy ) {
		$data[ $taxonomy->name ] = $taxonomy->labels->name;
	}
	return $data;
}
function iworks_orphans_options_need_assistance( $iworks_orphans ) {
	$content = apply_filters( 'iworks_rate_assistance', '', 'sierotki' );
	if ( ! empty( $content ) ) {
		echo $content;
		return;
	}

	?>
<p><?php _e( 'We are waiting for your message', 'sierotki' ); ?></p>
<ul>
	<li><a href="<?php _ex( 'https://wordpress.org/support/plugin/sierotki/', 'link to support forum on WordPress.org', 'sierotki' ); ?>"><?php _e( 'WordPress Help Forum', 'sierotki' ); ?></a></li>
</ul>
	<?php
}

function iworks_orphans_options_import() {
	$content = '';
	$content = wp_kses_post( get_option( 'iworks_orphans_options_import_messages' ) );
	delete_option( 'iworks_orphans_options_import_messages' );
	$content .= '<input type="file" name="iworks_orphan_import_file" accept="application/json" />';
	$content .= sprintf(
		'<button class="button" data-nonce="%s" name="iworks_orphan_import_button" disabled="disabled">%s</button>',
		wp_create_nonce( 'iworks_orphan_import' ),
		esc_html__( 'Import JSON', 'sierotki' )
	);
	return $content;
}

/**
 * sanitize_callback for multiple (remove after it will be implemented into
 * iWorks_Options class.
 *
 * https://github.com/iworks/wordpress-options-class/issues/4
 */
function iworks_orphans_sanitize_callback_multiple( $value ) {
	return $value;
}


