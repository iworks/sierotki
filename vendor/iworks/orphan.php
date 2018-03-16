<?php
/*

Copyright 2011-2018 Marcin Pietrzak (marcin@iworks.pl)

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

class iworks_orphan
{
	private $options;
	private $admin_page;
	private $settings;
	private $plugin_file;

	/**
	 * terms cache
	 */
	private $terms = array();

	/**
	 * Filter post meta.
	 *
	 * @since 2.7.0
	 */
	private $meta_keys = null;

	public function __construct() {
		$file = dirname( dirname( dirname( __FILE__ ) ) ).'/sierotki.php';
		/**
		 * plugin ID
		 */
		$this->plugin_file = plugin_basename( $file );
		/**
		 * options
		 */
		$this->options = get_orphan_options();
		/**
		 * actions
		 */
		add_action( 'init',       array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'iworks_rate_css', array( $this, 'iworks_rate_css' ) );
		add_action( 'plugins_loaded', array( $this, 'load_translation' ) );
	}

	/**
	 * Load Translation
	 *
	 * @since 2.7.3
	 */
	public function load_translation() {
		load_plugin_textdomain( 'sierotki', false, dirname( $this->plugin_file ).'/languages' );
	}

	public function replace( $content ) {
		/**
		 * do not replace empty content
		 */
		if ( empty( $content ) ) {
			return $content;
		}
		/**
		 * we do not need this in admin
		 */
		if ( is_admin() ) {
			return $content;
		}
		/**
		 * check post type
		 */
		$entry_related_filters = array( 'the_title', 'the_excerpt', 'the_content' );
		$current_filter = current_filter();
		if ( in_array( $current_filter, $entry_related_filters ) ) {
			if ( empty( $this->settings['post_type'] ) || ! is_array( $this->settings['post_type'] ) ) {
				return $content;
			}
			global $post;
			if ( is_a( $post, 'WP_Post' ) && ! in_array( $post->post_type, $this->settings['post_type'] ) ) {
				return $content;
			}
		}
		/**
		 * check taxonomy
		 */
		if ( 'term_description' == $current_filter ) {
			if ( empty( $this->settings['taxonomies'] ) || ! is_array( $this->settings['taxonomies'] ) ) {
				return $content;
			}
			$queried_object = get_queried_object();
			if ( ! in_array( $queried_object->taxonomy, $this->settings['taxonomies'] ) ) {
				return $content;
			}
		}
		/**
		 * Keep numbers together - this is independed of current language
		 */
		$numbers = $this->is_on( 'numbers' );
		if ( $numbers ) {
			preg_match_all( '/(>[^<]+<)/', $content, $parts );
			if ( $parts && is_array( $parts ) && ! empty( $parts ) ) {
				$parts = array_shift( $parts );
				foreach ( $parts as $part ) {
					$to_change = $part;
					while ( preg_match( '/(\d+) ([\da-z]+)/i', $to_change, $matches ) ) {
						$to_change = preg_replace( '/(\d+) ([\da-z]+)/i', '$1&nbsp;$2', $to_change );
					}
					if ( $part != $to_change ) {
						$content = str_replace( $part, $to_change, $content );
					}
				}
			}
		}
		/**
		 * Allow to ignore language.
		 *
		 * @since 2.6.7
		 */
		$all_languages = $this->is_on( 'ignore_language' );
		$apply_to_all_languages = apply_filters( 'iworks_orphan_apply_to_all_languages', $all_languages );
		if ( ! $apply_to_all_languages ) {
			/**
			 * apply other rules only for Polish language
			 */
			$locale = apply_filters( 'wpml_current_language', get_locale() );
			if ( ! preg_match( '/^pl/', $locale ) ) {
				return $content;
			}
		}
		$terms = $this->_terms();
		/**
		 * Avoid to replace inside script or styles tags
		 */
		preg_match_all( '@(<(script|style)[^>]*>.*?(</(script|style)>))@is', $content, $matches );
		$exceptions = array();
		if ( ! empty( $matches ) && ! empty( $matches[0] ) ) {
			$salt = 'kQc6T9fn5GhEzTM3Sxn7b9TWMV4PO0mOCV06Da7AQJzSJqxYR4z3qBlsW9rtFsWK';
			foreach ( $matches[0] as $one ) {
				$key = sprintf( '<!-- %s %s -->', $salt, md5( $one ) );
				$exceptions[ $key ] = $one;
				$re = sprintf( '@%s@', preg_replace( '/@/', '\@', preg_quote( $one, '/' ) ) );
				$content = preg_replace( $re, $key, $content );
			}
		}
		/**
		 * base therms replace
		 */
		$re = '/^([aiouwz]|'.preg_replace( '/\./', '\.', implode( '|', $terms ) ).') +/i';
		$content = preg_replace( $re, '$1$2&nbsp;', $content );
		/**
		 * single letters
		 */
		$re = '/([ >\(]+|&nbsp;)([aiouwz]|'.preg_replace( '/\./', '\.', implode( '|', $terms ) ).') +/i';
		/**
		 * double call to handle orphan after orphan after orphan
		 */
		$content = preg_replace( $re, '$1$2&nbsp;', $content );
		$content = preg_replace( $re, '$1$2&nbsp;', $content );
		/**
		 * single letter after previous orphan
		 */
		$re = '/(&nbsp;)([aiouwz]) +/i';
		$content = preg_replace( $re, '$1$2&nbsp;', $content );
		/**
		 * bring back styles & scripts
		 */
		if ( ! empty( $exceptions ) && is_array( $exceptions ) ) {
			foreach ( $exceptions as $key => $one ) {
				$re = sprintf( '/%s/', $key );
				$content = preg_replace( $re, $one, $content );
			}
		}
		/**
		 * return
		 */
		return $content;
	}

	public function add_help_tab() {
		$screen = get_current_screen();
		if ( $screen->id != $this->admin_page ) {
			return;
		}
		// Add my_help_tab if current screen is My Admin Page
		$screen->add_help_tab( array(
			'id'    => 'overview',
			'title' => __( 'Orphans', 'sierotki' ),
			'content'   => '<p>' . __( 'Plugin fix some Polish gramary rules with orphans.', 'sierotki' ) . '</p>',
		) );
			/**
			 * make sidebar help
			 */
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
				'<p>' . __( '<a href="http://wordpress.org/extend/plugins/sierotki/" target="_blank">Plugin Homepage</a>', 'sierotki' ) . '</p>' .
				'<p>' . __( '<a href="http://wordpress.org/support/plugin/sierotki/" target="_blank">Support Forums</a>', 'sierotki' ) . '</p>' .
				'<p>' . __( '<a href="http://iworks.pl/en/" target="_blank">break the web</a>', 'sierotki' ) . '</p>'
			);
	}

	public function admin_init() {
		$this->options->options_init();
		/** This filter is documented in wp-admin/includes/class-wp-plugins-list-table.php */
		add_filter( 'plugin_row_meta', array( $this, 'add_donate_link' ), 10, 2 );
		/** This filter is documented in wp-admin/includes/class-wp-plugins-list-table.php */
		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 2 );
	}

	public function init() {
		$this->settings = $this->options->get_all_options();
		$allowed_filters = array(
			'the_title',
			'the_excerpt',
			'the_content',
			'comment_text',
			'widget_title',
			'widget_text',
			'term_description',
			'get_the_author_description',
		);
		foreach ( $this->settings as $filter => $value ) {
			if ( ! in_array( $filter, $allowed_filters ) ) {
				continue;
			}
			if ( is_integer( $value ) && 1 == $value ) {
				add_filter( $filter, array( $this, 'replace' ) );
				/**
				 * WooCommerce exception: short descripton
				 */
				if ( 'the_excerpt' === $filter ) {
					add_filter( 'woocommerce_short_description', array( $this, 'replace' ) );
				}
			}
		}
		/**
		 * taxonomies
		 */
		if ( 1 == $this->settings['taxonomy_title'] && ! empty( $this->settings['taxonomies'] ) ) {
			add_filter( 'single_term_title', array( $this, 'replace' ) );
			if ( in_array( 'category', $this->settings['taxonomies'] ) ) {
				add_filter( 'single_cat_title', array( $this, 'replace' ) );
			}
			if ( in_array( 'post_tag', $this->settings['taxonomies'] ) ) {
				add_filter( 'single_tag_title', array( $this, 'replace' ) );
			}
		}
		add_filter( 'iworks_orphan_replace', array( $this, 'replace' ) );
		/**
		 * Filter post meta.
		 *
		 * @since 2.7.0
		 */
		add_filter( 'get_post_metadata', array( $this, 'filter_post_meta' ), 10, 4 );
	}

	/**
	 * Change logo for "rate" message.
	 *
	 * @since 2.6.6
	 */
	public function iworks_rate_css() {
		$logo = plugin_dir_url( dirname( dirname( __FILE__ ) ) ).'assets/images/logo.png';
		echo '<style type="text/css">';
		printf( '.iworks-notice-sierotki .iworks-notice-logo{background-color:#fed696;background-image:url(%s);}', esc_url( $logo ) );
		echo '</style>';
	}

	private function is_on( $key ) {
		return isset( $this->settings[ $key ] ) && 1 === $this->settings[ $key ];
	}

	/**
	 * Add settings link to plugin_action_links.
	 *
	 * @since 2.6.8
	 *
	 * @param array  $actions     An array of plugin action links.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 */
	public function add_settings_link( $actions, $plugin_file ) {
		if ( is_multisite() ) {
			return $actions;
		}
		if ( $plugin_file == $this->plugin_file ) {
			$page = $this->options->get_pagehook();
			$url = add_query_arg( 'page', $page, admin_url( 'themes.php' ) );
			$url = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'sierotki' ) );
			$settings = array( $url );
			$actions = array_merge( $settings, $actions );
		}
		return $actions;
	}

	/**
	 * Add donate link to plugin_row_meta.
	 *
	 * @since 2.6.8
	 *
	 * @param array  $plugin_meta An array of the plugin's metadata,
	 *                            including the version, author,
	 *                            author URI, and plugin URI.
	 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
	 */
	public function add_donate_link( $plugin_meta, $plugin_file ) {
		/* start:free */
		if ( $plugin_file == $this->plugin_file ) {
			$plugin_meta[] = '<a href="http://iworks.pl/donate/sierotki.php">' . __( 'Donate', 'sierotki' ) . '</a>';
		}
		/* end:free */
		return $plugin_meta;
	}

	/**
	 * Replace orphans in custom fields.
	 *
	 * @since 2.7.0
	 *
	 * @param null|bool $check      Whether to allow adding metadata for the given type.
	 * @param int       $object_id  Object ID.
	 * @param string    $meta_key   Meta key.
	 * @param mixed     $meta_value Meta value. Must be serializable if non-scalar.
	 * @param bool      $unique     Whether the specified meta key should be unique
	 *                              for the object. Optional. Default false.
	 * @return null|bool|string $value Post meta value with orphans rules.
	 */
	public function filter_post_meta( $check, $object_id, $meta_key, $unique ) {
		if ( ! $unique ) {
			return $check;
		}
		if ( false === $this->meta_keys ) {
			return $check;
		}
		if ( null === $this->meta_keys ) {
			$value = $this->options->get_option( 'post_meta' );
			if ( empty( $value ) || ! is_string( $value ) ) {
				return $check;
			}
			$value = explode( ',', trim( $value ) );
			$this->meta_keys = array_map( 'trim', $value );
		}
		if ( empty( $this->meta_keys ) ) {
			$this->meta_keys = false;
			return $check;
		}
		if ( ! in_array( $meta_key, $this->meta_keys ) ) {
			return $check;
		}
		remove_filter( 'get_post_metadata', array( $this, 'filter_post_meta' ), 10, 4 );
		$value = get_post_meta( $object_id, $meta_key, true );
		add_filter( 'get_post_metadata', array( $this, 'filter_post_meta' ), 10, 4 );
		if ( ! empty( $value ) ) {
			return $this->replace( $value );
		}
		return $check;
	}

	/**
	 * get terms array
	 *
	 * @since 2.7.1
	 *
	 * @return $terms array Array of terms to replace.
	 */
	private function _terms() {
		if ( ! empty( $this->terms ) ) {
			return $this->terms;
		}
		$terms = array(
			'al.',
			'albo',
			'ale',
			'ależ',
			'b.',
			'bez',
			'bm.',
			'bp',
			'br.',
			'by',
			'bym',
			'byś',
			'bł.',
			'cyt.',
			'cz.',
			'czy',
			'czyt.',
			'dn.',
			'do',
			'doc.',
			'dr',
			'ds.',
			'dyr.',
			'dz.',
			'fot.',
			'gdy',
			'gdyby',
			'gdybym',
			'gdybyś',
			'gdyż',
			'godz.',
			'im.',
			'inż.',
			'jw.',
			'kol.',
			'komu',
			'ks.',
			'która',
			'którego',
			'której',
			'któremu',
			'który',
			'których',
			'którym',
			'którzy',
			'lecz',
			'lic.',
			'm.in.',
			'max',
			'mgr',
			'min',
			'moich',
			'moje',
			'mojego',
			'mojej',
			'mojemu',
			'mych',
			'mój',
			'na',
			'nad',
			'nie',
			'niech',
			'np.',
			'nr',
			'nr.',
			'nrach',
			'nrami',
			'nrem',
			'nrom',
			'nrowi',
			'nru',
			'nry',
			'nrze',
			'nrze',
			'nrów',
			'nt.',
			'nw.',
			'od',
			'oraz',
			'os.',
			'p.',
			'pl.',
			'pn.',
			'po',
			'pod',
			'pot.',
			'prof.',
			'przed',
			'przez',
			'pt.',
			'pw.',
			'pw.',
			'tak',
			'tamtej',
			'tamto',
			'tej',
			'tel.',
			'tj.',
			'to',
			'twoich',
			'twoje',
			'twojego',
			'twojej',
			'twych',
			'twój',
			'tylko',
			'ul.',
			'we',
			'wg',
			'woj.',
			'więc',
			'za',
			'ze',
			'śp.',
			'św.',
			'że',
			'żeby',
			'żebyś',
			'—',
		);
		/**
		 * get own orphans
		 */
		$own_orphans = trim( get_option( 'iworks_orphan_own_orphans', '' ), ' \t,' );
		if ( $own_orphans ) {
			$own_orphans = preg_replace( '/\,\+/', ',', $own_orphans );
			$terms = array_merge( $terms, preg_split( '/,[ \t]*/', strtolower( $own_orphans ) ) );
		}
		$terms = apply_filters( 'iworks_orphan_therms', $terms );
		/**
		 * remove duplicates
		 */
		$terms = array_unique( $terms );
		/**
		 * decode
		 */
		$a = array();
		foreach ( $terms as $t ) {
			$a[] = html_entity_decode( $t );
		}
		$terms = $a;
		/**
		 * remove empty elements
		 */
		$terms = array_filter( $terms );
		$this->terms = $terms;
		return $this->terms;
	}
}
