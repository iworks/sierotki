<?php
class iworks_orphan
{
	private $options;
	private $admin_page;
	private $settings;

	public function __construct() {
		/**
		 * l10n
		 */
		load_plugin_textdomain( 'sierotki', false, dirname( plugin_basename( dirname( dirname( __FILE__ ) ) ) ).'/languages' );

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
			if ( ! in_array( $post->post_type, $this->settings['post_type'] ) ) {
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
			while ( preg_match( '/(\d) (\d)/', $content ) ) {
				$content = preg_replace( '/(\d) (\d)/', '$1&nbsp;$2', $content );
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

		$terms = array(
			'al.',
			'ale',
			'ależ',
			'b.',
			'bm.',
			'bp',
			'br.',
			'by',
			'bym',
			'byś',
			'bł.',
			'cyt.',
			'cz.',
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
			'pt.',
			'pw.',
			'pw.',
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
			'ul.',
			'we',
			'wg',
			'woj.',
			'za',
			'ze',
			'śp.',
			'św.',
			'że',
			'żeby',
			'żebyś',
		);

		preg_match_all( '@(<(script|style)[^>]*>.*?(</(script|style)>))@is', $content, $matches );
		$exceptions = '';

		if ( ! empty( $matches ) && ! empty( $matches[0] ) ) {
			$salt = 'kQc6T9fn5GhEzTM3Sxn7b9TWMV4PO0mOCV06Da7AQJzSJqxYR4z3qBlsW9rtFsWK';
			foreach ( $matches[0] as $one ) {
		        $key = sprintf( '<!-- %s %s -->', $salt, md5( $one ) );
				$exceptions[ $key ] = $one;
				$re = sprintf( '@%s@', preg_replace( '/@/', '\@', $one ) );
				$content = preg_replace( $re, $key, $content );
			}
		}

		$own_orphans = trim( get_option( 'iworks_orphan_own_orphans', '' ), ' \t,' );
		if ( $own_orphans ) {
			$own_orphans = preg_replace( '/\,\+/', ',', $own_orphans );
			$terms = array_merge( $terms, preg_split( '/,[ \t]*/', strtolower( $own_orphans ) ) );
		}
		$terms = apply_filters( 'iworks_orphan_therms', $terms );
		/**
		 * base therms replace
		 */
		$re = '/^([aiouwz]|'.preg_replace( '/\./', '\.', implode( '|', $terms ) ).') +/i';
		$content = preg_replace( $re, '$1$2&nbsp;', $content );
		/**
		 * single letters
		 */
		$re = '/([ >\(]+)([aiouwz]|'.preg_replace( '/\./', '\.', implode( '|', $terms ) ).') +/i';
		$content = preg_replace( $re, '$1$2&nbsp;', $content );
		/**
		 * single letter after previous orphan
		 */
		$re = '/(&nbsp;)([aiouwz]) +/i';
		$content = preg_replace( $re, '$1$2&nbsp;', $content );
		/**
		 * polish year after number
		 */
		$content = preg_replace( '/(\d+) (r\.)/', '$1&nbsp;$2', $content );

		/**
		 * bring back styles & scripts
		 */
		if ( ! empty( $exceptions ) ) {
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
		add_filter( 'plugin_row_meta', array( $this, 'links' ), 10, 2 );
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
	}

	public function links( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			if ( ! is_multisite() ) {
				$dir = explode( '/', dirname( __FILE__ ) );
				$dir = $dir[ count( $dir ) - 1 ];
				$links[] = '<a href="themes.php?page='.$dir.'.php">' . __( 'Settings' ) . '</a>';
			}
			$links[] = '<a href="http://iworks.pl/donate/sierotki.php">' . __( 'Donate' ) . '</a>';
		}
		return $links;
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
}
