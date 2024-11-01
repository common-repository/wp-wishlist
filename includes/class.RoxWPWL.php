<?php
/**
 * The Bootstrap
 * @package RoxWPWL
 * @version 1.0.0
 * @since RoxWPWL 1.0.0
 *
 */

if( ! function_exists( 'add_action' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die( "<h1>Forbidden</h1><br><br><p>Go Away!</p><hr><p>Just Go Away!!!</p>" );
}

/**
 * Class RoxWPWL
 */
final class RoxWPWL {
	/**
	 * Plugin Slug
	 * @access Private
	 * @var string
	 */
	private $plugin_name;
	/**
	 * Plugin Version
	 * @access Private
	 * @var string
	 */
	private $plugin_version = '1.0.0';
	/**
	 * Wishlist Button Shortcode
	 * @access Private
	 * @var string
	 */
	private static $button_shortcode;
	/**
	 * Plugin Settings
	 * @access Private
	 * @var RoxWPWLSettings
	 */
	public static $settings;
	/**
	 * Form ID Index
	 * @access Static Public
	 * @var string
	 */
	public static $form_idx = 1;
	/**
	 * Wishlist Plugin Table Names
	 * @access Private
	 * @var array
	 */
	private $plugin_tables = [
		'item'          => 'rox_wishlist_items',
		'contributor'   => 'rox_wishlist_contributors',
	];
	/**
	 * Plugin ajax action name
	 * @access Private
	 * @var string
	 */
	private $ajax_action;
	/**
	 * The single instance of the class.
	 *
	 * @var RoxWPWL
	 */
	protected static $instance;
	/**
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @return RoxWPWL
	 */
	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function __construct() {
		$this->plugin_name = ROX_WPWL_PLUGIN_NAME;
		self::$button_shortcode = apply_filters( 'rox_wp_wishlist_shortcode', 'wp_wishlist' );
		$this->includes();
		$this->init_hooks();
		/**
		 * Plugin Loaded (after plugin init)
		 */
		do_action( $this->plugin_name . '_loaded' );
	}
	/**
	 * Hook into actions and filters.
	 * @return void
	 */
	private function init_hooks() {
		RoxWPWLInstaller::init();
		add_action( 'init', array( $this, 'init' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts'), 10 );
		// ajax response
		add_action( 'wp_ajax_'.$this->ajax_action, array( $this, 'rox_wpwl_ajax_response') );
		add_action( 'wp_ajax_nopriv_'.$this->ajax_action, array( $this, 'rox_wpwl_ajax_response') );
		// shortcodes
		add_shortcode( self::$button_shortcode, array( $this, 'get_wishlist_button' ) );
		add_action( 'admin_notices', array( $this, '__rox_admin_notice' ) );
	}
	
	function __rox_admin_notice() {
		// printf( '<div class="notice notice-%s"><p>%s</p></div>', 'error', __( '', 'wordpress-wishlist' ) );
	}
	/**
	 * Init
	 * @return void
	 */
	public function init() {
		// Before init action.
		do_action( 'before_' . $this->plugin_name . '_init' );
		//RoxWPWLIcons::getInstance();
		self::$settings = RoxWPWLSettings::getInstance();
		/**
		 * Default Settings
		 * @param array
		 */
//		$defaultSettings = apply_filters( $this->plugin_name . '_default_settings', array(
//			'max_results'        => 5,
//		) );
		/**
		 * Administrator's Settings
		 * @param array
		 */
//		$this->settings = apply_filters( $this->plugin_name . '_settings', wp_parse_args( $settings->get(), $defaultSettings ) );
//		unset( $defaultSettings );
		/**
		 * Ajax Action Name
		 * @param string
		 */
		$this->ajax_action = apply_filters( $this->plugin_name . '_ajax_action', '__rox_wpwl' );
		// Set up localisation.
		$this->load_plugin_textdomain();
		// Init action.
		do_action( $this->plugin_name . '_init' );
	}
	private function includes() {
		require_once( ROX_WPWL_PATH . '/vendor/autoload.php' );
		require_once( ROX_WPWL_PATH . '/includes/helper.php' );
		require_once( ROX_WPWL_PATH . '/includes/template-helper.php' );
		require_once( ROX_WPWL_PATH . '/includes/template-hooks.php' );
		require_once( ROX_WPWL_PATH . '/includes/class.RoxWPWLSettings.php' );
		require_once( ROX_WPWL_PATH . '/includes/class.RoxWPWLInstaller.php' );
		require_once( ROX_WPWL_PATH . '/includes/class.RoxWPWLPostTypes.php' );
//		require_once( ROX_WPWL_PATH . '/includes/class.RoxWPWLAjax.php' );
		require_once( ROX_WPWL_PATH . '/includes/class.Rox_List_Item.php' );
		require_once( ROX_WPWL_PATH . '/includes/class.RoxWPWLQueryList.php' );
		require_once( ROX_WPWL_PATH . '/includes/class.RoxWPWLShortCodes.php' );
		require_once( ROX_WPWL_PATH . '/includes/class.RoxWPWLTemplateLoader.php' );
		require_once( ROX_WPWL_PATH . '/includes/class.RoxWPWLRenderHelper.php' );
		if( is_admin() ) {
			require_once( ROX_WPWL_PATH . '/includes/class.RoxWPWLAdmin.php' );
		}
		$this->theme_supports();
	}
	private function theme_supports() {
		switch ( get_template() ) {
			case 'twentynineteen':
				require_once( ROX_WPWL_PATH . '/includes/theme-support/twenty-nineteen.php' );
				break;
		}
	}
	/**
	 * Enqueue frontend script and styles
	 * @return void
	 */
	public function frontend_scripts() {
		
		$prefix = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min';
		/**
		 *
		 */
		$rox_wpwl_js_conf = apply_filters( $this->plugin_name . '_js_configs', array(
			'rox_ajax'              => $this->ajax_url(),
			'rox_csrf'              => wp_create_nonce( '__csrf' . $this->plugin_name ),
			'rox_action'            => $this->ajax_action,
			'rox_error'             => __( 'We encountered an error while processing your request. Please try again after sometime.', 'wordpress-wishlist' ),
			'list_name_max_len'     => rox_wpwl_get_option( 'list_name_max_length' ),
			'list_desc_max_len'     => rox_wpwl_get_option( 'list_description_max_length' ),
			'errors'                => array(
				'invalid_or_empty'              => __( 'Invalid Or Empty Value', 'wordpress-wishlist' ),
				'list_name_max_len'             => __( 'Wishlist name exceeds maximum character limit.', 'wordpress-wishlist' ),
			),
			'confirm_delete_list'   => __( 'Are you really want to delete this list?', 'wordpress-wishlist' ),
			'confirm_delete_item'   => __( 'Are you really want to remove this item from this list?', 'wordpress-wishlist' ),
			'is_user_logged_in'     => (int) is_user_logged_in(),
			'is_wishlist'           => (int) is_wishlist(),
			'is_wishlist_tag'       => (int) is_wishlist_tag(),
			'is_wishlist_category'  => (int) is_wishlist_category(),
			'is_wishlist_taxonomy'  => (int) is_wishlist_taxonomy(),
			'is_wishlist_archive'   => (int) is_wishlist_archive(),
			'is_wp_wishlist'        => (int) is_wp_wishlist(),
			'post_id'               => (int) get_the_ID(),
		) );
		wp_enqueue_script( 'jquery' );
		wp_register_script( $this->plugin_name . '_frontend_script', $this->plugin_url( 'assets/js/scripts'.$prefix.'.js' ), array( 'jquery' ), $this->plugin_version, true );
		wp_localize_script( $this->plugin_name . '_frontend_script', 'rox_wpwl_configs', $rox_wpwl_js_conf );
		wp_enqueue_script( $this->plugin_name . '_frontend_script' );
		wp_enqueue_style( $this->plugin_name . '_frontend_style', $this->plugin_url( 'assets/css/styles.css' ), array(), $this->plugin_version );
		$customStyle = $this->__get_customized_css();
		if( ! empty( $customStyle ) ) {
			wp_add_inline_style( $this->plugin_name . '_frontend_style', $customStyle );
		}
	}
	
	/**
	 * Get Customized Styles
	 *
	 * @return string
	 */
	private function __get_customized_css() {
		global $post;
		$css = '';
		if( $post instanceof WP_Post ) {
			// wc
			if( $post->post_type == 'product' ) {
				$product = wc_get_product( $post );
				if( ! $product->is_in_stock() ) {
					$css .= '.outofstock .stock { position: relative; display: inline-block; margin-right: 5px; }';
				}
			}
		}
		
		return trim( $css );
	}
	/**
	 * Frontend Ajax Responses
	 * @return void
	 */
	public function rox_wpwl_ajax_response() {
		if( wp_verify_nonce( $_REQUEST['_nonce'], '__csrf' . $this->plugin_name ) ) {
		
		} else {
			wp_send_json_error( array(
				'message' => __( 'Invalid CSRF', 'wordpress-wishlist' ),
			) );
		}
		die();
	}
	/**
	 * Return Search form for shortcode and widgets
	 *
	 * @return string
	 */
	public function get_wishlist_button(){
	
	}
	
	/**
	 * callback for the_content filter.
	 * This will display wishlist button with content on single post.
	 * @param string $title
	 * @return string
	 */
	public function the_post_title( $title ) {
		return $title;
	}
	
	/**
	 * callback for the_content filter.
	 * This will display wishlist button with content on single post.
	 * @param string $content
	 * @return string
	 */
	public function the_post_content( $content ) {
		return $content;
	}
	
	/**
	 * Include Template file
	 * @param string $name
	 * @param array $args
	 * @param bool $load_once
	 * @return void
	 */
	public function __get_template( $name, $args = array(), $load_once = false ) {
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}
		$fn = $name;
		$fn = ltrim( $fn, '/' );
		$paths = array(
			'theme' => get_template_directory() . $this->__template_path() . '/' . $fn . '.php',
			'core' => $this->plugin_path( ROX_WPWL_TEMPLATES . '/' . $fn . '.php' ),
		);
		if( ROX_WPWL_TEMPLATE_DEBUG ) unset( $paths['theme'] );
		foreach( $paths as $ctx => $path ) {
			$fn = apply_filters( $this->plugin_name . '_template', $path, $ctx );
			if( file_exists( $fn ) ) {
				if( $load_once ) require_once( $fn );
				else require( $fn ); // files that need to be include in loop
				break;
			}
		}
	}
	/**
	 * Get Template Content
	 * @param $name
	 * @param array $args
	 * @return false|string
	 */
	public function __get_template_content( $name, $args = array() ) {
		ob_start();
		$this->__get_template( $name, $args );
		return ob_get_clean();
	}
	
	/**
	 * Load Language files
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'wordpress-wishlist', false, ROX_WPWL_URL . '/languages' );
	}
	/**
	 * Get the plugin url.
	 * @param string $file
	 * @return string
	 */
	public function plugin_url( $file = null ) {
		if( ! $file ) return untrailingslashit( ROX_WPWL_URL );
		$file = ltrim( $file, '/' );
		return untrailingslashit( ROX_WPWL_URL ) . '/' . $file;
	}
	/**
	 * Get the plugin path.
	 * @param string $file
	 * @return string
	 */
	public function plugin_path( $file = null ) {
		if( ! $file ) return untrailingslashit( ROX_WPWL_PATH );
		$file = ltrim( $file, '/' );
		return untrailingslashit( ROX_WPWL_PATH ) . '/' . $file;
	}
	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function __template_path() {
		$path = apply_filters( 'rox_wpwl_emplate_path', 'wp-wishlist' );
		$path = trim_slashes( $path );
		return $path;
	}
	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php' );
	}
	public function get_plugin_name() {
		return $this->plugin_name;
	}
	public function get_shortcode() {
		return self::$button_shortcode;
	}
	
	/**
	 * Get Plugin Table Name/s
	 * @param string $table
	 * @return array
	 */
	public function get_tables( $table = NULL ) {
		global $wpdb;
		$tables = array_map( function( $tableName ) use( $wpdb ) {
			return $wpdb->prefix . $tableName;
		}, $this->plugin_tables );
		if( empty( $table ) ) return $tables;
		if( isset( $tables[$table] ) ) return $tables[$table];
		else return array();
	}
	// stop magician
	public function __get( $key ) {}
	public function __set( $key, $value ) {}
	private function __clone() {}
	private function __sleep() {}
	private function __wakeup() {}
}
// End of file class.RoxWPWL.php