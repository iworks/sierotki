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
	 * @since 3.4.5 - restricted to the plugin settings screen.
	 */
	public function action_admin_print_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || 'appearance_page_iworks_orphan_index' !== $screen->id ) {
			return;
		}
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
		/**
		 * validate upload
		 *
		 * @since 3.4.5
		 */
		if (
			! isset( $_FILES['iworks_orphan_import_file'] )
			|| ! is_array( $_FILES['iworks_orphan_import_file'] )
		) {
			$this->add_message_fail();
			return;
		}
		$file = $_FILES['iworks_orphan_import_file'];
		if (
			! isset( $file['error'] )
			|| UPLOAD_ERR_OK !== $file['error']
			|| empty( $file['tmp_name'] )
			|| ! is_uploaded_file( $file['tmp_name'] )
		) {
			$this->add_message_fail();
			return;
		}
		$data = json_decode( file_get_contents( $file['tmp_name'] ), true );
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
			$allowed = $this->get_allowed_option_names();
			foreach ( $data['Orphans'] as $one ) {
				/**
				 * only whitelisted plugin options can be imported
				 *
				 * @since 3.4.5
				 */
				if (
					empty( $one['option_name'] )
					|| ! is_string( $one['option_name'] )
					|| ! in_array( $one['option_name'], $allowed, true )
				) {
					continue;
				}
				update_option( $one['option_name'], $one['option_value'] );
			}
			$this->add_message_success();
			return;
		}
		$this->add_message_fail();
	}

	/**
	 * Build the whitelist of option names that may be imported.
	 *
	 * Only options defined in the plugin configuration (all prefixed with
	 * `iworks_orphan_`) are accepted - never an arbitrary option name.
	 *
	 * @since 3.4.5
	 *
	 * @return array Allowed option names.
	 */
	private function get_allowed_option_names() {
		$allowed = array();
		$options = get_orphan_options()->get_group();
		if ( is_array( $options ) && isset( $options['options'] ) ) {
			foreach ( $options['options'] as $one ) {
				if ( empty( $one['name'] ) || ! is_string( $one['name'] ) ) {
					continue;
				}
				$allowed[] = 'iworks_orphan_' . $one['name'];
			}
		}
		return $allowed;
	}
}

