<?php
/*
Copyright 2011-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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

class iworks_orphan {

	private $options;
	private $admin_page;
	private $settings;
	private $plugin_file;

	/**
	 * plugin root
	 *
	 * @since 2.9.0
	 */
	private $root;

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

	/**
	 * Filter post meta.
	 *
	 * @since 3.0.0
	 */
	private $version = 'PLUGIN_VERSION';

	/**
	 * tags to avoid replacement
	 *
	 * @since 3.1.0
	 */
	private $protected_tags = array(
		'script',
		'style',
		'iframe',
		'svg',
	);

	/**
	 * nbsp placehlder
	 *
	 * @since 3.1.0
	 */
	private $nbsp_placeholder = '&nbsp;';

	public function __construct() {
		/**
		 * basic settings
		 */
		$file       = dirname( dirname( dirname( __FILE__ ) ) ) . '/sierotki.php';
		$this->root = dirname( $file );
		/**
		 * options
		 */
		$this->options = get_orphan_options();
		/**
		 * plugin ID
		 */
		$this->plugin_file = plugin_basename( $file );
		/**
		 * actions
		 */
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		/**
		 * filters
		 */
		add_filter( 'orphan_replace', array( $this, 'orphan_replace_filter' ) );
		/**
		 * iWorks Rate Class
		 */
		add_filter( 'iworks_rate_notice_logo_style', array( $this, 'filter_plugin_logo' ), 10, 2 );
		/**
		 * Replace in Translations functions.
		 *
		 * Since 3.1.0
		 */
		if ( $this->options->get_option( 'gettext' ) ) {
			add_filter( 'gettext', array( $this, 'filter_gettext' ), 10, 3 );
		}
	}

	/**
	 * base replacement function
	 */
	public function replace( $content ) {
		/**
		 * Filter to allow skip replacement.
		 *
		 * @since 2.7.7
		 */
		if ( apply_filters( 'orphan_skip_replacement', false ) ) {
			return $content;
		}
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
		 * we do not need this in feed
		 *
		 * @since 2.7.6
		 */
		if ( is_feed() ) {
			return $content;
		}
		/**
		 * we do not need this in REST API
		 *
		 * @since 2.7.6
		 */
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $content;
		}
		/**
		 * check post type
		 */
		$entry_related_filters = array( 'the_title', 'the_excerpt', 'the_content' );
		$current_filter        = current_filter();
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
		 * Allow to ignore language.
		 *
		 * @since 2.6.7
		 */
		$all_languages          = $this->is_on( 'ignore_language' );
		$apply_to_all_languages = apply_filters( 'iworks_orphan_apply_to_all_languages', $all_languages );
		if ( ! $apply_to_all_languages ) {
			/**
			 * apply other rules only for Polish language
			 */
			$locale = apply_filters( 'wpml_current_language', get_locale() );
			/**
			 * polylang integration
			 *
			 * @since 3.1.0
			 */
			if ( function_exists( 'pll_current_language' ) ) {
				$locale = pll_current_language( 'slug' );
			}
			if ( ! preg_match( '/^pl/', $locale ) ) {
				return $content;
			}
		}
		/**
		 * finally just replace!
		 */
		return $this->unconditional_replacement( $content );
	}

	/**
	 * Replacement wrapper function for two params filters
	 *
	 * Replacement wrapper function for two params filters allow to check menu
	 * the_tilte settings.
	 *
	 * @since 2.9.4
	 */
	public function replace_two( $content, $object_id = null ) {
		if ( empty( $object_id ) ) {
			return $content;
		}
		/**
		 * Check for menu title
		 *
		 * @since 2.9.4
		 */
		if ( 'nav_menu_item' === get_post_type( $object_id ) ) {
			if ( isset( $this->settings['menu_title'] ) && 0 === $this->settings['menu_title'] ) {
				return $content;
			}
		}
		return $this->replace( $content );
	}

	/**
	 * Parse DOMElement Object
	 *
	 * @since 3.1.0
	 */
	private function parse_item( $item ) {
		/**
		 * no tags, replace
		 */
		if ( ! preg_match( '/</', $item->innertext ) ) {
			$item->innertext = $this->string_replacement( $item->innertext );
			return;
		}
		/**
		 * split to slices & replace!
		 */
		preg_match_all( '/<[^>]+>/', $item->innertext, $matches );
		$text_array = preg_split( '/<[^>]+>/', $item->innertext );
		$text       = '';
		$max        = sizeof( $text_array );
		for ( $i = 0;$i < $max;$i++ ) {
			$text .= $this->string_replacement( $text_array[ $i ] );
			if ( isset( $matches[0][ $i ] ) ) {
				$text .= $matches[0][ $i ];
			}
		}
		$item->innertext = $text;
		return;
	}

	/**
	 * String replacement with super-base check is replacement even
	 * possible.
	 *
	 * @since 3.1.0
	 *
	 * @param string $content String to replace
	 *
	 * @return string $content
	 */
	private function string_replacement( $content ) {
		/**
		 * only super-base check
		 */
		if ( ! is_string( $content ) || empty( $content ) ) {
			return $content;
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
						$to_change = preg_replace( '/(\d+) ([\da-z]+)/i', '$1' . $this->nbsp_placeholder . '$2', $to_change );
					}
					if ( $part != $to_change ) {
						$content = str_replace( $part, $to_change, $content );
					}
				}
			}
		}
		/**
		 * Chunk terms
		 *
		 * @since 2.7.6
		 */
		$terms       = $this->_terms();
		$terms_terms = array_chunk( $terms, 10 );
		/**
		 * avoid to replace tags content
		 *
		 * @since 2.9.4
		 */
		$content_array = array( $content );
		if ( preg_match( '/</', $content ) ) {
			$content_array = preg_split( '/<[^>]+>/', $content );
		}
		foreach ( $content_array as $part_source ) {
			$part_to_change = $part_source;
			foreach ( $terms_terms as $terms ) {
				/**
				 * base therms replace
				 */
				$re             = '/^([aiouwz]|' . preg_replace( '/\./', '\.', implode( '|', $terms ) ) . ') +/i';
				$part_to_change = preg_replace( $re, '$1$2' . $this->nbsp_placeholder, $part_to_change );
				/**
				 * single letters
				 */
				$re = '/([ >\(]+|' . $this->nbsp_placeholder . '|&#8222;|&quot;)([aiouwz]|' . preg_replace( '/\./', '\.', implode( '|', $terms ) ) . ') +/i';
				/**
				 * double call to handle orphan after orphan after orphan
				 */
				$part_to_change = preg_replace( $re, '$1$2' . $this->nbsp_placeholder, $part_to_change );
				$part_to_change = preg_replace( $re, '$1$2' . $this->nbsp_placeholder, $part_to_change );
			}
			if ( $part_source !== $part_to_change ) {
				$content = str_replace( $part_source, $part_to_change, $content );
			}
		}
		/**
		 * single letter after previous orphan
		 */
		$re      = '/(' . $this->nbsp_placeholder . ')([aiouwz]) +/i';
		$content = preg_replace( $re, '$1$2' . $this->nbsp_placeholder, $content );
		/**
		 * bring back styles & scripts
		 */
		if ( ! empty( $exceptions ) && is_array( $exceptions ) ) {
			foreach ( $exceptions as $key => $one ) {
				$re      = sprintf( '/%s/', $key );
				$content = preg_replace( $re, $one, $content );
			}
		}
		/**
		 * revert replaced attributes
		 */
		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $to_change => $part ) {
				$content = str_replace( $part, $to_change, $content );
			}
		}
		/**
		 * year short version
		 *
		 * @since 3.0.4
		 */
		$content = preg_replace( '/(\d) r\./', '$1' . $this->nbsp_placeholder . 'r.', $content );
		/**
		 * return
		 */
		return $content;
	}

	/**
	 * Unconditional replacement with super-base check is replacement even
	 * possible.
	 *
	 * @since 2.7.8
	 * @since 3.1.0 - changed into DOM parsing
	 *
	 *
	 * @param string $content String to replace
	 *
	 * @return string $content
	 */
	private function unconditional_replacement( $content ) {
		if ( ! is_string( $content ) || empty( $content ) ) {
			return $content;
		}
		/**
		 * string, no tags
		 */
		if ( strip_tags( $content ) === $content ) {
			return $this->string_replacement( $content );
		}
		/**
		 * parse
		 */
		$doc = str_get_html( $content );
		/**
		 * remove protected tags
		 */
		$protected = array();
		foreach ( $this->protected_tags as $tag ) {
			foreach ( $doc->find( $tag ) as $item ) {
				$key               = md5( $item->outertext );
				$protected[ $key ] = $item->outertext;
				$item->outertext   = $key;
			}
		}
		/**
		 * replace
		 */
		foreach ( $doc->find( '*' ) as &$item ) {
			$this->parse_item( $item );
		}
		/**
		 * revert protected tags
		 */
		$out = $doc->save();
		foreach ( $protected as $key => $outertext ) {
			$out = str_replace( $key, $outertext, $out );

		}
		return $out;
	}

	/**
	 * Inicialize admin area
	 */
	public function admin_init() {
		$this->options->options_init();
		add_filter( 'plugin_action_links_' . $this->plugin_file, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialize, but not for admin
	 */
	public function init() {
		/**
		 * Turn off all replacements for admin area - we do not need it!
		 */
		if ( is_admin() ) {
			return;
		}
		if ( empty( $this->settings ) ) {
			$this->settings = $this->options->get_all_options();
		}
		/**
		 * Filter allowed filters.
		 *
		 * @since 2.9.4
		 *
		 * @param array $args {
		 *      Array of filters, array key as filter.
		 *
		 *      @type integer priority Filter priority.
		 *      @type integer accepted_args Filter number of accepted args.
		 */
		$allowed_filters = apply_filters(
			'orphan_allowed_filters',
			array(
				'the_title'                  => array(
					'priority'      => PHP_INT_MAX,
					'accepted_args' => 2,
				),
				'the_excerpt'                => array(
					'priority'      => PHP_INT_MAX,
					'accepted_args' => 1,
				),
				'the_content'                => array(
					'priority'      => PHP_INT_MAX,
					'accepted_args' => 1,
				),
				'comment_text'               => array(
					'priority'      => PHP_INT_MAX,
					'accepted_args' => 1,
				),
				'widget_title'               => array(
					'priority'      => PHP_INT_MAX,
					'accepted_args' => 1,
				),
				'widget_text'                => array(
					'priority'      => PHP_INT_MAX,
					'accepted_args' => 1,
				),
				'term_description'           => array(
					'priority'      => PHP_INT_MAX,
					'accepted_args' => 1,
				),
				'get_the_author_description' => array(
					'priority'      => PHP_INT_MAX,
					'accepted_args' => 1,
				),
				'widget_block_content'       => array(
					'priority'      => PHP_INT_MAX,
					'accepted_args' => 1,
				),
			)
		);
		foreach ( $this->settings as $filter => $value ) {
			if ( ! array_key_exists( $filter, $allowed_filters ) ) {
				continue;
			}
			$priority = isset( $allowed_filters[ $filter ]['priority'] ) ? $allowed_filters[ $filter ]['priority'] : PHP_INT_MAX;
			if ( is_integer( $value ) && 1 == $value ) {
				if ( 2 === $allowed_filters[ $filter ]['accepted_args'] ) {
					add_filter( $filter, array( $this, 'replace_two' ), $priority, 2 );
				} else {
					add_filter( $filter, array( $this, 'replace' ), $priority );
				}
				/**
				 * WooCommerce exception: short descripton
				 */
				if ( 'the_excerpt' === $filter ) {
					add_filter( 'woocommerce_short_description', array( $this, 'replace' ), $priority );
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
		/**
		 * Integrations: Advanced Custom Fields
		 *
		 * @since 2.9.1
		 */
		foreach ( array( 'text', 'textarea', 'wysiwyg' ) as $type ) {
			if ( $this->is_on( 'acf_' . $type ) ) {
				add_filter( 'acf/format_value/type=' . $type, array( $this, 'filter_acf' ), 10, 3 );
			}
		}
		/**
		 * Integrations: WP Bakery
		 *
		 * @since 3.0.2
		 */
		add_filter( 'vc_shortcode_output', array( $this, 'replace' ) );
		/**
		 * Filter allowed change protected tags.
		 *
		 * @since 3.1.0
		 *
		 * @param array $args {
		 *      Array of protected tags - all content of this tags will be not
		 *      replaced
		 *
		 *      @type string HTML tag name.
		 */
		$this->protected_tags = apply_filters(
			'iworks_orphan_protected_tags',
			$this->protected_tags
		);
	}

	/**
	 * Is key turned on?
	 *
	 * @param string $key Settings key.
	 *
	 * @return boolean Is this key setting turned on?
	 */
	private function is_on( $key ) {
		return isset( $this->settings[ $key ] ) && 1 === $this->settings[ $key ];
	}

	/**
	 * Add settings link to plugin_action_links.
	 *
	 * @since 2.6.8
	 *
	 * @param array  $actions     An array of plugin action links.
	 */
	public function add_settings_link( $actions ) {
		$page      = $this->options->get_pagehook();
		$url       = add_query_arg( 'page', $page, admin_url( 'themes.php' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'sierotki' ) );
		return $actions;
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
			$value           = explode( ',', trim( $value ) );
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
		/**
		 * Transients
		 *
		 * @since 3.0.0
		 */
		$cache_name = 'orphan_terms' . $this->version;
		$terms      = get_transient( $cache_name );
		if ( empty( $terms ) ) {
			$terms = array();
		} else {
			$this->terms = $terms;
		}
		/**
		 * if already set
		 */
		if ( ! empty( $this->terms ) ) {
			$terms = $this->terms;
			$terms = apply_filters( 'iworks_orphan_therms', $terms );
			$terms = apply_filters( 'iworks_orphan_terms', $terms );
			return $terms;
		}
		/**
		 * read terms from file
		 *
		 * @since 2.9.0
		 */
		$file = apply_filters( 'iworks_orphan_own_terms_file', $this->root . '/etc/terms.txt' );
		$data = preg_split( '/[\r\n]/', file_get_contents( $file ) );
		foreach ( $data as $term ) {
			if ( preg_match( '/^#/', $term ) ) {
				continue;
			}
			$term = trim( $term );
			if ( empty( $term ) ) {
				continue;
			}
			$terms[] = $term;
		}
		/**
		 * get own orphans
		 */
		$own_orphans = trim( get_option( 'iworks_orphan_own_orphans', '' ), ' \t,' );
		if ( $own_orphans ) {
			$own_orphans = preg_replace( '/\,\+/', ',', $own_orphans );
			$terms       = array_merge( $terms, preg_split( '/,[ \t]*/', strtolower( $own_orphans ) ) );
		}
		/**
		 * remove duplicates
		 */
		$terms = array_unique( $terms );
		/**
		 * decode
		 */
		$a = array();
		foreach ( $terms as $t ) {
			$a[] = strtolower( html_entity_decode( $t ) );
		}
		$terms = $a;
		/**
		 * remove empty elements
		 */
		$terms = array_filter( $terms );
		/**
		 * remove duplicates & sort
		 *
		 * @since 2.9.7
		 */
		$terms = array_unique( $terms );
		sort( $terms );
		/**
		 * assign to class property
		 */
		$this->terms = $terms;
		/**
		 * filter it
		 */
		$terms = apply_filters( 'iworks_orphan_therms', $terms );
		$terms = apply_filters( 'iworks_orphan_terms', $terms );
		/**
		 * Transients
		 *
		 * @since 3.0.0
		 */
		set_transient( $cache_name, $terms, DAY_IN_SECONDS );
		/**
		 * set
		 */
		$this->terms = $terms;
		return $terms;
	}

	/**
	 * Filter to use Orphans on any string
	 *
	 * @since 2.7.8
	 *
	 * @param string $content String to replace
	 *
	 * @return string $content
	 */
	public function orphan_replace_filter( $content ) {
		if ( ! is_string( $content ) ) {
			return $content;
		}
		if ( empty( $content ) ) {
			return $content;
		}
		return $this->unconditional_replacement( $content );
	}

	/**
	 * Plugin logo for rate messages
	 *
	 * @since 2.7.9
	 *
	 * @param string $logo Logo, can be empty.
	 * @param object $plugin Plugin basic data.
	 */
	public function filter_plugin_logo( $logo, $plugin ) {
		if ( is_object( $plugin ) ) {
			$plugin = (array) $plugin;
		}
		if ( 'sierotki' === $plugin['slug'] ) {
			return plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . '/assets/images/logo.png';
		}
		return $logo;
	}

	/**
	 * Replace in Translations functions.
	 *
	 * Since 3.1.0
	 */
	public function filter_gettext( $translation, $text, $domain ) {
		/**
		 * Replace in gettext?
		 *
		 * Fillter allow to turn off replacement depend on params.
		 *
		 * @since 3.1.0
		 *
		 * @param boolean
		 * @param string $translation
		 * @param string $text
		 * @param string $domain Translation domain.
		 */
		if ( apply_filters( 'orphan_replace_gettext', true, $translation, $text, $domain ) ) {
			return $this->unconditional_replacement( $translation );
		}
		return $translation;
	}

	/**
	 * Replace in ACF field
	 *
	 * Since 3.1.0
	 *
	 * @param $value (mixed) The field value.
	 * @param $post_id (int|string) The post ID where the value is saved.
	 * @param $field (array) The field array containing all settings.
	 */
	public function filter_acf( $value, $post_id, $field ) {
		/**
		 * Replace in ACF field?
		 *
		 * Fillter allow to turn off replacement depend on params.
		 *
		 * @since 3.1.0
		 *
		 * @param boolean
		 * @param $value (mixed) The field value.
		 * @param $post_id (int|string) The post ID where the value is saved.
		 * @param $field (array) The field array containing all settings.
		 */
		if ( apply_filters( 'orphan_replace_acf', true, $value, $post_id, $field ) ) {
			return $this->unconditional_replacement( $value );
		}
		return $value;
	}
}

