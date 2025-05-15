<?php
/**
 * GitHub Updater for Orphans Plugin
 *
 * This class handles the update functionality for the Orphans plugin
 * by checking for new releases on GitHub.
 *
 * @package    WordPress
 * @subpackage Sierotki
 * @author     Marcin Pietrzak <marcin@iworks.pl>
 * @copyright  2025-PLUGIN_TILL_YEAR Marcin Pietrzak
 * @license    GPL-2.0+ <https://www.gnu.org/licenses/gpl-2.0.html>
 * @since      1.0.0
 */

/*
This program is free software; you can redistribute it and/or modify
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

// Prevent class duplication
if ( class_exists( 'iworks_sierotki_github' ) ) {
	return;
}

/**
 * Handles plugin updates from GitHub repository.
 *
 * This class provides functionality to check for updates from GitHub
 * and handle the update process through the WordPress update system.
 *
 * @since 1.0.0
 */
class iworks_orphans_github {

	/**
	 * GitHub repository in format 'username/repository'.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $repository = 'iworks/sierotki';

	/**
	 * Plugin main file name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $plugin_file = 'sierotki.php';

	/**
	 * Plugin directory name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $plugin_folder = 'sierotki';

	/**
	 * Plugin basename.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $basename = 'sierotki';

	/**
	 * Cached GitHub API response.
	 *
	 * @since 1.0.0
	 * @var array|null
	 */
	private $github_response;

	/**
	 * Class constructor.
	 *
	 * Initializes the GitHub updater by setting up hooks and callbacks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->plugin_folder = dirname( __DIR__, 3 );

		// Register WordPress hooks
		add_action( 'init', array( $this, 'action_init_load_plugin_textdomain' ), 0 );
		add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'modify_transient' ) );
		add_filter( 'upgrader_post_install', array( $this, 'install_update' ), 10, 3 );
	}

	/**
	 * Loads the plugin's translated strings.
	 *
	 * @since 1.0.0
	 * @action init
	 *
	 * @return void
	 */
	public function action_init_load_plugin_textdomain() {
		$dir = plugin_basename( $this->plugin_folder ) . '/languages';
		load_plugin_textdomain( 'sierotki', false, $dir );
	}

	/**
	 * Fetches the latest release information from the GitHub repository.
	 *
	 * Makes an API request to GitHub to retrieve the most recent release
	 * information for the configured repository.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array containing release data or empty array on failure.
	 */
	private function get_latest_repository_release(): array {
		// Create the request URI
		$request_uri = sprintf(
			'https://api.github.com/repos/%s/releases',
			$this->repository
		);
		// Get the response from the API
		$request = wp_remote_get( $request_uri );
		// If the API response has an error code, stop
		$response_codes = wp_remote_retrieve_response_code( $request );
		if ( $response_codes < 200 || $response_codes >= 300 ) {
			return array();
		}
		// Decode the response body
		$response = json_decode( wp_remote_retrieve_body( $request ), true );
		// If the response is an array, return the first item
		if ( is_array( $response ) && ! empty( $response[0] ) ) {
			$response = $response[0];
		}
		return $response;
	}

	/**
	 * Retrieves and caches repository information from GitHub.
	 *
	 * Gets the latest release information from GitHub and caches it
	 * in the object to avoid multiple API requests.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array containing repository release information.
	 */
	private function get_repository_info(): array {
		if ( ! empty( $this->github_response ) ) {
			return $this->github_response;
		}

		// Get the latest repo
		$response = $this->get_latest_repository_release();

		// Set the github_response property for later use
		$this->github_response = $response;

		// Return the response
		return $response;
	}

	/**
	 * Filters the plugin information displayed in the plugin details popup.
	 *
	 * @since 1.0.0
	 *
	 * @param bool|object|array $result The result object or array. Default false.
	 * @param string            $action The type of information being requested from the Plugin Installation API.
	 * @param object            $args   Plugin API arguments.
	 *
	 * @return bool|object|array The filtered result or the original value if not our plugin.
	 */
	public function plugin_popup( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}
		if ( $args->slug !== $this->basename ) {
			return $result;
		}
		$repo = $this->get_repository_info();
		if ( empty( $repo ) ) {
			return $result;
		}
		$details = get_plugin_data( $this->plugin_folder . '/' . $this->plugin_file );
		// Create array to hold the plugin data
		$plugin = array(
			'name'              => $details['Name'],
			'slug'              => $this->basename,
			'requires'          => $details['RequiresWP'],
			'requires_php'      => $details['RequiresPHP'],
			'version'           => $repo['tag_name'],
			'author'            => $details['AuthorName'],
			'author_profile'    => $details['AuthorURI'],
			'last_updated'      => $repo['published_at'],
			'homepage'          => $details['PluginURI'],
			'short_description' => $details['Description'],
			'sections'          => array(
				'Description' => $details['Description'],
				'Updates'     => $repo['body'],
			),
			'download_link'     => $repo['assets'][0]['browser_download_url'],
		);
		// Return the plugin data as an object
		return (object) $plugin;
	}

	/**
	 * Filters the plugin update transient to include our GitHub update data.
	 *
	 * @since 1.0.0
	 *
	 * @param object $transient Plugin update transient data.
	 *
	 * @return object Modified plugin update transient data.
	 */
	public function modify_transient( object $transient ): object {
		// Stop if the transient does not have a checked property
		if ( ! isset( $transient->checked ) ) {
			return $transient;
		}

		// Check if WordPress has checked for updates
		$checked = $transient->checked;

		// Stop if WordPress has not checked for updates
		if ( empty( $checked ) ) {
			return $transient;
		}

		// If the basename is not in $checked, stop
		if ( ! array_key_exists( $this->plugin_file, $checked ) ) {
			return $transient;
		}

		// Get the repo information
		$repo_info = $this->get_repository_info();

		// Stop if the repository information is empty
		if ( empty( $repo_info ) ) {
			return $transient;
		}

		// Github version, trim v if exists
		$github_version = ltrim( $repo_info['tag_name'], 'v' );

		// Compare the module's version to the version on GitHub
		$out_of_date = version_compare(
			$github_version,
			$checked[ $this->plugin_file ],
			'gt'
		);

		// Stop if the module is not out of date
		if ( ! $out_of_date ) {
			return $transient;
		}

		// Add our module to the transient
		$transient->response[ $this->plugin_file ] = (object) array(
			'id'          => $repo_info['html_url'],
			'url'         => $repo_info['html_url'],
			'slug'        => current( explode( '/', $this->basename ) ),
			'package'     => $repo_info['zipball_url'],
			'new_version' => $repo_info['tag_name'],
		);

		return $transient;
	}

	/**
	 * Handles the post-installation process after an update.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $response      Whether the cleanup was successful.
	 * @param array $hook_extra    Extra arguments passed to hooked filters.
	 * @param array $install_result Installation result data.
	 *
	 * @return array Modified installation result data.
	 */
	public function install_update( $response, $hook_extra, $result ) {
		global $wp_filesystem;
		$directory = plugin_dir_path( $this->plugin_file );

		// Get the correct directory name
		$correct_directory_name = basename( $directory );

		// Get the path to the downloaded directory
		$downloaded_directory_path = $result['destination'];

		// Get the path to the parent directory
		$parent_directory_path = dirname( $downloaded_directory_path );

		// Construct the correct path
		$correct_directory_path = $parent_directory_path . '/' . $correct_directory_name;

		// Move and rename the downloaded directory
		$wp_filesystem->move( $downloaded_directory_path, $correct_directory_path );

		// Update the destination in the result
		$result['destination'] = $correct_directory_path;

		// If the plugin was active, reactivate it
		if ( is_plugin_active( $this->plugin_file ) ) {
			activate_plugin( $this->plugin_file );
		}

		// Return the result
		return $result;
	}
}
