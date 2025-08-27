<?php

/**
 * Integration with Secure Custom Fields plugin for handling orphans in custom fields.
 *
 * @package    Sierotki
 * @subpackage Integrations
 * @since      3.4.0
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-iworks-orphans-integration.php';

/**
 * Class iWorks_Orphans_Integration_Secure_Custom_Fields
 *
 * Handles integration with Secure Custom Fields plugin to process orphans in custom fields.
 *
 * @package Sierotki
 * @subpackage Integrations
 */
class iWorks_Orphans_Integration_Secure_Custom_Fields extends iWorks_Orphans_Integration {

	/**
	 * Class constructor.
	 *
	 * @param iWorks_Orphans $orphans The main plugin instance.
	 */
	public function __construct( $orphans ) {
		$this->orphans = $orphans;
		/**
		 * Add Secure Custom Fields specific options to the integrations settings.
		 *
		 * @since 3.4.0
		 */
		add_filter( 'orphans/etc/config/integrations', array( $this, 'filter_etc_options_integrations' ) );
		/**
		 * Integrations: Secure Custom Fields
		 *
		 * @since 3.3.9
		 */
		add_filter( 'acf/format_value', array( $this, 'filter_acf_format_value' ), 10, 4 );
	}

	/**
	 * Replace in Secure Custom Fields
	 *
	 * @since 3.3.9
	 */
	public function filter_acf_format_value( $value, $post_id, $field, $escape_html ) {
		if ( $this->is_on( 'scf_' . $field['type'] ) ) {
			return $this->orphans->replace( $value );
		}
		return $value;
	}
	/**
	 * Add Secure Custom Fields specific options to the integrations settings.
	 *
	 * @param array $options Existing integration options.
	 * @return array Modified options array with Secure Custom Fields settings.
	 */
	public function filter_etc_options_integrations( $options ) {
		$options[] = array(
			'type'  => 'subheading',
			'label' => __( 'Secure Custom Fields', 'sierotki' ),
		);
		$options[] = array(
			'name'              => 'scf_text',
			'type'              => 'checkbox',
			'th'                => __( 'Text', 'sierotki' ),
			'description'       => __( 'Enabled the substitution of orphans in text fields.', 'sierotki' ),
			'sanitize_callback' => 'absint',
			'default'           => 0,
			'classes'           => array( 'switch-button' ),
		);
		$options[] = array(
			'name'              => 'scf_textarea',
			'type'              => 'checkbox',
			'th'                => __( 'Textarea', 'sierotki' ),
			'description'       => __( 'Enabled the substitution of orphans in textarea fields. (Include WYSIWYG).', 'sierotki' ),
			'sanitize_callback' => 'absint',
			'default'           => 0,
			'classes'           => array( 'switch-button' ),
		);
		$options[] = array(
			'name'              => 'scf_wysiwyg',
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
