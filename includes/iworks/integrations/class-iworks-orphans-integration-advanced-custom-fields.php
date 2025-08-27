<?php

/**
 * Integration with Advanced Custom Fields plugin for handling orphans in custom fields.
 *
 * @package    Sierotki
 * @subpackage Integrations
 * @since      3.4.0
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-iworks-orphans-integration.php';

/**
 * Class iWorks_Orphans_Integration_Advanced_Custom_Fields
 *
 * Handles integration with Advanced Custom Fields plugin to process orphans in custom fields.
 *
 * @package Sierotki
 * @subpackage Integrations
 */
class iWorks_Orphans_Integration_Advanced_Custom_Fields extends iWorks_Orphans_Integration {

	/**
	 * Class constructor.
	 *
	 * @param iWorks_Orphans $orphans The main plugin instance.
	 *
	 * @since 3.4.0
	 */
	public function __construct( $orphans ) {
		$this->orphans = $orphans;
		/**
		 * WordPress Hooks.
		 */
		add_action( 'init', array( $this, 'action_init' ), 143 );
		/**
		 * Add Advanced Custom Fields specific options to the integrations settings.
		 *
		 * @since 3.4.0
		 */
		add_filter( 'orphans/etc/config/integrations', array( $this, 'filter_etc_options_integrations' ) );
	}

	/**
	 * Integrations: Advanced Custom Fields
	 *
	 * @since 3.4.0
	 */
	public function action_init() {
		/**
		 * Integrations: Advanced Custom Fields
		 *
		 * @since 2.9.1
		 */
		foreach ( array( 'text', 'textarea', 'wysiwyg' ) as $type ) {
			if ( $this->is_on( 'acf_' . $type ) ) {
				add_filter( 'acf/format_value/type=' . $type, array( $this, 'filter_acf_format_value_type' ), 10, 3 );
			}
		}
	}
	/**
	 * Replace in Advanced Custom Fields
	 *
	 * @since 3.4.0
	 *
	 * @param string $value    The value to be replaced.
	 * @param int    $post_id  The ID of the post.
	 * @param array  $field    The field array.
	 *
	 * @return string The replaced value.
	 */
	public function filter_acf_format_value_type( $value, $post_id, $field ) {
		if ( empty( $value ) ) {
			return $value;
		}
		return $this->orphans->replace( $value );
	}
	/**
	 * Add Advanced Custom Fields specific options to the integrations settings.
	 *
	 * @since 3.4.0
	 *
	 * @param array $options Existing integration options.
	 *
	 * @return array Modified options array with Advanced Custom Fields settings.
	 */
	public function filter_etc_options_integrations( $options ) {
		$options[] = array(
			'type'  => 'subheading',
			'label' => __( 'Advanced Custom Fields', 'sierotki' ),
		);
		$options[] = array(
			'name'              => 'acf_text',
			'type'              => 'checkbox',
			'th'                => __( 'Text', 'sierotki' ),
			'description'       => __( 'Enabled the substitution of orphans in text fields.', 'sierotki' ),
			'sanitize_callback' => 'absint',
			'default'           => 0,
			'classes'           => array( 'switch-button' ),
		);
		$options[] = array(
			'name'              => 'acf_textarea',
			'type'              => 'checkbox',
			'th'                => __( 'Textarea', 'sierotki' ),
			'description'       => __( 'Enabled the substitution of orphans in textarea fields. (Include WYSIWYG).', 'sierotki' ),
			'sanitize_callback' => 'absint',
			'default'           => 0,
			'classes'           => array( 'switch-button' ),
		);
		$options[] = array(
			'name'              => 'acf_wysiwyg',
			'type'              => 'checkbox',
			'th'                => __( 'WYSIWYG', 'sierotki' ),
			'description'       => __( 'Enabled the substitution of orphans in WYSIWYG fields.', 'sierotki' ),
			'sanitize_callback' => 'absint',
			'default'           => 0,
			'classes'           => array( 'switch-button' ),
		);
		return $options;
	}
}
