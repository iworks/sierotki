<?php
/*
Copyright 2023-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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
defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/class-iworks-orphans-integration.php';

class iWorks_Orphans_Integration_Bricks extends iWorks_Orphans_Integration {

	public function __construct( $orphans ) {
		$this->orphans = $orphans;
		add_filter( 'bricks/element/settings', array( $this, 'filter_bricks_element_settings' ), PHP_INT_MAX, 2 );
	}

	public function filter_bricks_element_settings( $settings, $element ) {
		if ( isset( $settings['text'] ) && is_string( $settings['text'] ) ) {
			$settings['text'] = $this->orphans->replace( $settings['text'] );
		}
		return $settings;
	}

}

