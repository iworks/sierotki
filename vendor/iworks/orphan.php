<?php
class iworks_orphan
{
	private $options;
	private $admin_page;

	public function __construct() {
		/**
		 * l10n
		 */
		load_plugin_textdomain( 'sierotki', false, dirname( plugin_basename( dirname( dirname( __FILE__ ) ) ) ).'/languages' );

		/**
		 * actions
		 */
		add_action( 'init',       array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'iworks_rate_css', array( $this, 'iworks_rate_css' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

		/**
		 * options
		 */
		$this->options = array(
			'comment_text' => array(
				'description' => __( 'Use for comments:', 'sierotki' ),
				'type'  => 'checkbox',
				'label' => __( 'Enabled the substitution of orphans in the comments.', 'sierotki' ),
				'sanitize_callback' => 'absint',
			),
			'the_title'    => array(
				'description' => __( 'Use for post title:', 'sierotki' ),
				'type'  => 'checkbox',
				'label' => __( 'Enabled the substitution of orphans in the post_title.', 'sierotki' ),
				'sanitize_callback' => 'absint',
			),
			'the_excerpt'  => array(
				'description' => __( 'Use for excerpt:', 'sierotki' ),
				'type'  => 'checkbox',
				'label' => __( 'Enabled the substitution of orphans in the excerpt.', 'sierotki' ),
				'sanitize_callback' => 'absint',
			),
			'the_content'  => array(
				'description' => __( 'Use for content:', 'sierotki' ),
				'type'  => 'checkbox',
				'label' => __( 'Enabled the substitution of orphans in the content.', 'sierotki' ),
				'sanitize_callback' => 'absint',
			),
			/**
			 * Since 2.6.6
			 */
			'widget_title'  => array(
				'description' => __( 'Use for widget title:', 'sierotki' ),
				'type'  => 'checkbox',
				'label' => __( 'Enabled the substitution of orphans in the widget title.', 'sierotki' ),
				'sanitize_callback' => 'absint',
			),
			/**
			 * Since 2.6.6
			 */
			'widget_text'  => array(
				'description' => __( 'Use for widget text:', 'sierotki' ),
				'type'  => 'checkbox',
				'label' => __( 'Enabled the substitution of orphans in the widget text.', 'sierotki' ),
				'sanitize_callback' => 'absint',
			),
			'woocommerce_product_title'  => array(
				'description' => __( 'Use for WooCommerce product title:', 'sierotki' ),
				'type'  => 'checkbox',
				'label' => __( 'Enabled the substitution of orphans in the WooCommerce product title.', 'sierotki' ),
				'sanitize_callback' => 'absint',
			),
			'woocommerce_short_description'  => array(
				'description' => __( 'Use for WooCommerce short description:', 'sierotki' ),
				'type'  => 'checkbox',
				'label' => __( 'Enabled the substitution of orphans in the WooCommerce short description.', 'sierotki' ),
				'sanitize_callback' => 'absint',
			),
			'numbers' => array(
				'description' => __( 'Keep numbers together:', 'sierotki' ),
				'type'  => 'checkbox',
				'label' => __( 'Allow to keep together phone number or strings with space between numbers.', 'sierotki' ),
				'sanitize_callback' => 'absint',
			),
			'own_orphans'  => array(
				'description' => __( 'User definied orphans:', 'sierotki' ),
				'type' => 'text',
				'label' => __( 'Use a comma to separate orphans.', 'sierotki' ),
				'sanitize_callback' => 'esc_html',
			),
		);
	}

	public function replace( $content ) {
		if ( empty( $content ) ) {
			return;
		}

		/**
		 * Keep numbers together - this is independed of current language
		 */
		$numbers = get_option( 'iworks_orphan_numbers' );
		if ( ! empty( $numbers ) ) {
			while ( preg_match( '/(\d) (\d)/', $content ) ) {
				$content = preg_replace( '/(\d) (\d)/', '$1&nbsp;$2', $content );
			}
		}

		/**
		 * apply other rules only for Polish language
		 */
		$locale = apply_filters( 'wpml_current_language', get_locale() );
		if ( ! preg_match( '/^pl/', $locale ) ) {
			return $content;
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
				$content = preg_replace( $key, $one, $content );
			}
		}

		/**
		 * return
		 */
		return $content;
	}

	public function option_page() {
?>
<div class="wrap">
    <h2><?php _e( 'Orphans', 'sierotki' ) ?></h2>
    <div class="postbox-container" style="width:75%">
    <form method="post" action="options.php">
        <?php settings_fields( 'sierotki' ); ?>
        <table class="form-table">
            <tbody>
<?php
foreach ( $this->options as $filter => $option ) {
	/**
			 * check option type
			 */
	if (
		0
		|| ! is_array( $option )
		|| empty( $option )
		|| ! array_key_exists( 'type', $option )
	) {
		continue;
	}
	$field = 'iworks_orphan_'.$filter;
	printf(
		'<tr valign="top"><th scope="row">%s</th><td>',
		array_key_exists( 'description', $option )? $option['description']:'&nbsp;'
	);
	switch ( $option['type'] ) {
		case 'checkbox':
			printf(
				'<label for="%s"><input type="checkbox" name="%s" value="1"%s id="%s"/> %s</label>',
				$field,
				$field,
				checked( 1, get_option( $field, 1 ), false ),
				$field,
				isset( $option['label'] )? $option['label']:'&nbsp;'
			);
		break;
		case 'text':
		default:
			printf(
				'<input type="text" name="%s" value="%s" id="%s" class="regular-text code" />%s',
				$field,
				get_option( $field, '' ),
				$field,
				isset( $option['label'] )? '<p class="description">'.$option['label'].'</p>':''
			);
		break;
	}
	print '</td></tr>';
}
?>
            </tbody>
        </table>
        <p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" /></p>
    </form>
        </div>
        <div class="postbox-container" style="width:23%;margin-left:2%">
            <div class="metabox-holder">
                <div id="links" class="postbox">
                    <h3 class="hndle"><?php _e( 'Loved this Plugin?', 'sierotki' ); ?></h3>
                    <div class="inside">
                        <p><?php _e( 'Below are some links to help spread this plugin to other users', 'sierotki' ); ?></p>
                        <ul>
                            <li><a href="http://wordpress.org/extend/plugins/sierotki/"><?php _e( 'Give it a 5 star on Wordpress.org', 'sierotki' ); ?></a></li>
                            <li><a href="http://wordpress.org/extend/plugins/sierotki/"><?php _e( 'Link to it so others can easily find it', 'sierotki' ); ?></a></li>
                        </ul>
                    </div>
                </div>
                <div id="help" class="postbox">
                    <h3 class="hndle"><?php _e( 'Need Assistance?', 'sierotki' ); ?></h3>
                    <div class="inside">
                        <p><?php _e( 'Problems? The links bellow can be very helpful to you', 'sierotki' ); ?></p>
                        <ul>
                            <li><a href="<?php _e( 'http://wordpress.org/support/plugin/sierotki', 'sierotki' ); ?>"><?php _e( 'Wordpress Help Forum', 'sierotki' ); ?></a></li>
                            <li><a href="mailto:<?php echo antispambot( 'marcin@iworks.pl' ); ?>"><?php echo antispambot( 'marcin@iworks.pl' ); ?></a></li>
                        </ul>
                        <hr />
                        <p class="description"><?php _e( 'Created by: ', 'sierotki' ); ?> <a href="http://iworks.pl/"><span>iWorks.pl</span></a></p>
                    </div>
                </div>
            </div>
        </div>
    </div><?php
	}

	private function get_capability() {
				return apply_filters( 'iworks_orphans_capability', 'manage_options' );
	}

	public function admin_menu() {
		if ( function_exists( 'add_theme_page' ) ) {
			$this->admin_page = add_theme_page(
				__( 'Orphans', 'sierotki' ),
				__( 'Orphans', 'sierotki' ),
				/**
				 * Allow to change capability.
				 *
				 * This filter allow to change capability which is needed to
				 * access to Orphans configuration page.
				 *
				 * @since 2.6.0
				 *
				 * @param string  $capability current capability
				 *
				 */
				$this->get_capability(),
				basename( __FILE__ ),
				array( $this, 'option_page' )
			);
			add_action( 'load-'.$this->admin_page, array( $this, 'add_help_tab' ) );
		}
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
		foreach ( $this->options as $filter => $option ) {
			$sanitize_callback = isset( $option['sanitize_callback'] )? $option['sanitize_callback']:null;
			register_setting( 'sierotki', 'iworks_orphan_'.$filter, $sanitize_callback );
		}
		add_filter( 'plugin_row_meta', array( $this, 'links' ), 10, 2 );
	}

	public function init() {
		if ( 0 == get_option( 'iworks_orphan_initialized', 0 ) ) {
			foreach ( $this->options as $filter => $option ) {
				if ( ! isset( $option['type'] ) ) {
					$option['type'] = 'undefinied';
				}
				switch ( $option['type'] ) {
					case 'checkbox':
						update_option( 'iworks_orphan_'.$filter, 1 );
					break;
					case 'text':
					default:
						update_option( 'iworks_orphan_'.$filter, '' );
					break;
				}
			}
			update_option( 'iworks_orphan_initialized', 1 );
		}
		foreach ( array_keys( $this->options ) as $filter ) {
			if ( 1 == get_option( 'iworks_orphan_'.$filter, 1 ) ) {
				add_filter( $filter, array( $this, 'replace' ) );
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
	 * Change the admin footer text on Orphans admin pages.
	 *
	 * @since  2.3
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		if ( ! current_user_can( $this->get_capability() ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! preg_match( '/page_orphan$/', $screen->id ) ) {
			return;
		}
		return sprintf( __( 'If you like <strong>Orphans</strong> please leave us a %s&#9733;&#9733;&#9733;&#9733;&#9733;%s rating. A huge thanks in advance!', 'sierotki' ), '<a href="https://wordpress.org/support/plugin/sierotki/reviews/?rate=5#new-post" target="_blank">', '</a>' );
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
}
