<?php
/*
Copyright 2024-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly
/**
 * Export Orphans Configuration.
 *
 * The class allows to export plugin configuration to JSON file.
 *
 * @since 3.3.0
 */
class iWorks_Orphans_Export {
	private $options;

	/**
	 * Prepare and send JSON file
	 *
	 * @since 3.3.0
	 */
	public function send_json() {
		$this->options = get_orphan_options();
		$options       = $this->options->get_group();
		if ( ! is_array( $options ) ) {
			return;
		}
		if ( ! isset( $options['options'] ) ) {
			return;
		}
		$add_wordpress_data = 'true' === filter_input( INPUT_POST, 'extra' );
		/**
		 * data
		 */
		$data = array(
			'Meta'      => array(
				'date'   => date( 'c' ),
				'plugin' => array(
					'name'    => 'Orphans',
					'version' => 'PLUGIN_VERSION',
				),
				'url'    => array(
					'GitHub'    => 'https://github.com/iworks/sierotki',
					'WordPress' => 'https://wordpress.org/plugins/sierotki/',
				),
			),
			'Orphans'   => $this->get_settings_plugin(),
			'WordPress' => $add_wordpress_data ? $this->get_wordpress_settings() : array(),
		);
		/**
		 * filename
		 */
		$filename = sanitize_file_name(
			sprintf(
				'%s-%s.json',
				get_option( 'blogname' ),
				date( 'c' )
			)
		);
		/**
		 * export file
		 */
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		echo json_encode( $data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * get plugin settings
	 *
	 * @since 3.3.0
	 */
	private function get_settings_plugin() {
		$data = array();
		foreach ( $options['options'] as $one ) {
			if ( ! isset( $one['name'] ) ) {
				continue;
			}
			$option_name = $this->options->get_option_name( $one['name'] );
			$data[]      = array(
				'name'         => $one['name'],
				'option_name'  => $this->options->get_option_name( $one['name'] ),
				'option_value' => $this->options->get_option( $one['name'] ),
			);
		}
		return $data;
	}

	/**
	 * get WordPress settings
	 *
	 * @since 3.3.0
	 */
	private function get_wordpress_settings() {
		$fields = array(
			'siteurl',
			'blogname',
			'blog_charset',
			'active_plugins',
			'WPLANG',
		);
		$data   = array();
		foreach ( $fields as $option_name ) {
			$data[ $option_name ] = get_option( $option_name );
		}
		return $data;
	}
}

