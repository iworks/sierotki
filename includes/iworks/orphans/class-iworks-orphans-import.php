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
class iWorks_Orphans_Import {
	private $options;

	public function __construct() {
		add_action( 'admin_print_scripts', array( $this, 'action_admin_print_scripts' ), PHP_INT_MAX );
	}

	/**
	 * add admin scripts
	 *
	 * @since 3.3.0
	 */
	public function action_admin_print_scripts() {
		echo PHP_EOL;
		?><script id="<?php echo esc_attr( __CLASS__ ); ?>">
jQuery(document).ready(function($) {
	$('input[name=iworks_orphan_import_file]').on( 'change', function(e) {
		if ($(this).val()) {
			$('button[name=iworks_orphan_import_button]').removeAttr('disabled' );
		}
	});
	$('button[name=iworks_orphan_import_button]').on( 'click', function(e) {
		e.preventDefault();
		$form = $('<form method="post" enctype="multipart/form-data"></form>');
		$form.append('<input type="hidden" name="nonce" value="'+$(this).data('nonce') +'">');
		$form.append($('input[name=iworks_orphan_import_file]'));
		$('body').append($form);
		$form.submit();
	});
});
</script>
		<?php
	}

	private function import( $data ) {
		return true;
	}

	private function add_message( $class, $message ) {
		update_option(
			'iworks_orphans_options_import_messages',
			sprintf(
				'<div class="notice notice-%s inline">%s</div>',
				esc_attr( $class ),
				wpautop( $message )
			),
			'',
			'no'
		);
	}

	private function add_message_success() {
		$this->add_message(
			'info',
			esc_html__( 'Configuration has been imported.', 'sierotki' )
		);
	}

	private function add_message_fail() {
		$this->add_message(
			'error',
			esc_html__( 'Something went wrong!', 'sierotki' )
		);
	}

	public function import_json() {
		$data = json_decode( file_get_contents( $_FILES['iworks_orphan_import_file']['tmp_name'] ), true );
		if (
			is_array( $data )
			&& isset( $data['Meta'] )
			&& isset( $data['Meta']['plugin'] )
			&& isset( $data['Meta']['plugin']['name'] )
			&& 'Orphans' === $data['Meta']['plugin']['name']
			&& isset( $data['Orphans'] )
			&& is_array( $data['Orphans'] )
			&& ! empty( $data['Orphans'] )
		) {
			foreach ( $data['Orphans'] as $one ) {
				update_option( $one['option_name'], $one['option_value'] );
			}
			$this->add_message_success();
			return;
		}
		$this->add_message_fail();
	}

}

