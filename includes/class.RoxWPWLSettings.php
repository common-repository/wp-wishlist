<?php
/**
 * Created by PhpStorm.
 * User: blackhunter
 * Date: 2018-12-06
 * Time: 14:51
 */

class RoxWPWLSettings {
	public static $instance;
	private static $settings;
	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	public function __construct() {
		$this->__get_options();
	}
	/**
	 * Get All Settings
	 * @return void
	 */
	private function __get_options() {
		$fields = self::__settings_fields();
		self::$settings = array();
		foreach( self::__settings_sections() as $section ) {
			self::$settings = wp_parse_args( get_option( $section['id'], array() ), self::$settings );
//			self::$settings[$section['id']] = get_option( $section['id'], array() );
			foreach( $fields[$section['id']] as $field ) {
				if( isset( $field['type'] ) && $field['type'] == 'html' ) continue;
				if( isset( $field['name'] ) && ! isset( self::$settings[$field['name']] ) ) {
					self::$settings[$field['name']] = isset( $field['default'] ) ? $field['default'] : NULL;
				}
//				if( isset( $field['name'] ) && ! isset( self::$settings[$section['id']][$field['name']] ) ) {
//					self::$settings[$section['id']][$field['name']] = isset( $field['default'] ) ? $field['default'] : NULL;
//				}
			}
		}
	}
	public function get_option( $settings, $section = 'general' ) {
//		if( isset( self::$settings[ ROX_WPWL_PLUGIN_NAME . '_' . $section ] ) && isset( self::$settings[ ROX_WPWL_PLUGIN_NAME . '_' . $section ][$settings] ) ) {
//			return self::$settings[ ROX_WPWL_PLUGIN_NAME . '_' . $section ][$settings];
//		}
		if( isset( self::$settings[$settings] ) ) return self::$settings[$settings];
		return false;
	}
	public static function __settings_fields() {
		$loaded_options = array(
			ROX_WPWL_PLUGIN_NAME . '_general' => get_option( ROX_WPWL_PLUGIN_NAME . '_general', array() ),
			ROX_WPWL_PLUGIN_NAME . '_advanced' => get_option( ROX_WPWL_PLUGIN_NAME . '_advanced', array() ),
		);
		$wishlist_icon_1 = (file_exists( RoxWPWL()->plugin_path( 'assets/images/add_to_playlist_01.svg' ) ) )? file_get_contents( RoxWPWL()->plugin_path( 'assets/images/add_to_playlist_01.svg' ) ) : '';
		$wishlist_icon_2 = (file_exists( RoxWPWL()->plugin_path( 'assets/images/add_to_playlist_02.svg' ) ) )? file_get_contents( RoxWPWL()->plugin_path( 'assets/images/add_to_playlist_02.svg' ) ) : '';
		$wishlist_icon_1 = str_replace( "<?xml version=\"1.0\" ?>\n", '', $wishlist_icon_1 );
		$wishlist_icon_2 = str_replace( "<?xml version=\"1.0\" ?>\n", '', $wishlist_icon_2 );
		
		$settings_fields = array(
			ROX_WPWL_PLUGIN_NAME . '_general' => array(
				array(
					'name'              => 'wishlist_archive',
					'label'             => __( 'Wishlist Page', 'wordpress-wishlist' ),
					'desc'              => sprintf( __( 'This will be the wishlist archive page.<br>The base page can also be used in your <a href="%s">wishlist permalinks</a>.', 'wordpress-wishlist' ), admin_url( 'options-permalink.php' ) ),
					'placeholder'       => __( 'Wishlist Archive Page', 'wordpress-wishlist' ),
					'type'              => 'pages',
					'default'           => '',
				),
				array(
					'name'              => 'posts_per_page',
					'label'             => __( 'Number of Wishlist Per Page', 'wordpress-wishlist' ),
					'desc'              => __( 'Number of Wishlist In list pages (Eg. Wishlist archive page).<br>Setting this to -1 or 0 will disable pagination.', 'wordpress-wishlist' ),
					'placeholder'       => __( '10', 'wordpress-wishlist' ),
					'min'               => -1,
					//'max'               => 0,
					'step'              => '1',
					'type'              => 'number',
					'default'           => '10',
					'sanitize_callback' => 'intval'
				),
//				array(
//					'name'              => 'items_per_page',
//					'label'             => __( 'Number of list item Per Page', 'wordpress-wishlist' ),
//					'desc'              => __( 'Number of list item In single wishlist pages (Eg. Wishlist archive page).<br>Setting this to -1 or 0 will disable pagination.', 'wordpress-wishlist' ),
//					'placeholder'       => __( '9', 'wordpress-wishlist' ),
//					'min'               => -1,
//					//'max'               => 0,
//					'step'              => '1',
//					'type'              => 'number',
//					'default'           => '15',
//					'sanitize_callback' => 'intval'
//				),
				array(
					'name'  => 'wishlist_button_style',
					'label' => __( 'Button Style', 'wordpress-wishlist' ),
					'desc'  => __( '', 'wordpress-wishlist' ),
					'type'  => 'radio',
					'default' => 'style-1',
					'options' => array(
						'style-1'         => sprintf( '<a href="#" onclick="return false;" class="button" style="pointer-events: none;">%s</a>', __( 'Add To Wishlist', 'wordpress-wishlist' ) ),
						'style-2'         => sprintf( '<a href="#" onclick="return false;" class="button" style="pointer-events: none;" aria-label="%s"><span class="rox-wpwl-icon icon-wishlist">%s</span></a>', __( 'Add To Wishlist', 'wordpress-wishlist' ), $wishlist_icon_1 ),
						'style-3'         => sprintf( '<a href="#" onclick="return false;" class="button" style="pointer-events: none;" aria-label="%s"><span class="rox-wpwl-icon icon-heart">%s</span></a>', __( 'Add To Wishlist', 'wordpress-wishlist' ), $wishlist_icon_2 ),
					),
				),
				array(
					'name'        => 'shortcode',
					'label'       => __( 'Shortcode', 'wordpress-wishlist' ),
					'desc'        => sprintf( '<input class="regular-text" type="text" onclick="select(this);" value=\'[%s style="style_1"]\' readonly><br>Other Style options are <code>style-2</code> and <code>style-3</code>', RoxWPWL()->get_shortcode() ),
					'type'        => 'html'
				),
//				array(
//					'name'  => 'enable_reviews',
//					'label' => __( 'Enable Comments', 'wordpress-wishlist' ),
//					// 'desc'  => __( '', 'wordpress-wishlist' ),
//					'type'  => 'radio',
//					'default' => 'no',
//					'options' => array(
//						'no'            => __( 'No', 'wordpress-wishlist' ),
//						'yes'           => __( 'Yes', 'wordpress-wishlist' ),
//					),
//				),
//				array(
//					'name'              => 'text_val',
//					'label'             => __( 'Text Input', 'wordpress-wishlist' ),
//					'desc'              => __( 'Text input description', 'wordpress-wishlist' ),
//					'placeholder'       => __( 'Text Input placeholder', 'wordpress-wishlist' ),
//					'type'              => 'text',
//					'default'           => 'Title',
//					'sanitize_callback' => 'sanitize_text_field'
//				),
//				array(
//					'name'              => 'number_input',
//					'label'             => __( 'Number Input', 'wordpress-wishlist' ),
//					'desc'              => __( 'Number field with validation callback `floatval`', 'wordpress-wishlist' ),
//					'placeholder'       => __( '1.99', 'wordpress-wishlist' ),
//					'min'               => 0,
//					'max'               => 100,
//					'step'              => '0.01',
//					'type'              => 'number',
//					'default'           => 'Title',
//					'sanitize_callback' => 'floatval'
//				),
//				array(
//					'name'        => 'textarea',
//					'label'       => __( 'Textarea Input', 'wordpress-wishlist' ),
//					'desc'        => __( 'Textarea description', 'wordpress-wishlist' ),
//					'placeholder' => __( 'Textarea placeholder', 'wordpress-wishlist' ),
//					'type'        => 'textarea'
//				),
//				array(
//					'name'    => 'radio',
//					'label'   => __( 'Radio Button', 'wordpress-wishlist' ),
//					'desc'    => __( 'A radio button', 'wordpress-wishlist' ),
//					'type'    => 'radio',
//					'options' => array(
//						'yes' => 'Yes',
//						'no'  => 'No'
//					)
//				),
//				array(
//					'name'    => 'selectbox',
//					'label'   => __( 'A Dropdown', 'wordpress-wishlist' ),
//					'desc'    => __( 'Dropdown description', 'wordpress-wishlist' ),
//					'type'    => 'select',
//					'default' => 'no',
//					'options' => array(
//						'yes' => 'Yes',
//						'no'  => 'No'
//					)
//				),
//				array(
//					'name'    => 'password',
//					'label'   => __( 'Password', 'wordpress-wishlist' ),
//					'desc'    => __( 'Password description', 'wordpress-wishlist' ),
//					'type'    => 'password',
//					'default' => ''
//				),
//				array(
//					'name'    => 'file',
//					'label'   => __( 'File', 'wordpress-wishlist' ),
//					'desc'    => __( 'File description', 'wordpress-wishlist' ),
//					'type'    => 'file',
//					'default' => '',
//					'options' => array(
//						'button_label' => 'Choose Image'
//					)
//				),
			),
			ROX_WPWL_PLUGIN_NAME . '_advanced' => array(
				array(
					'name'    => 'allowed_post_types',
					'label'   => __( 'Enable Wishlist For', 'wordpress-wishlist' ),
					'desc'    => __( 'Choose Post Types you want to show wishlist button', 'wordpress-wishlist' ),
					'type'    => 'multicheck',
					'default' => apply_filters( 'rox_wpwl_default_allowed_post_types', array( 'post' => 'post' ) ),
					'options' => self::__get_post_types(),
				),
//				array(
//					'name'    => 'allowed_taxonomies',
//					'label'   => __( 'Enable Wishlist For', 'wordpress-wishlist' ),
//					'desc'    => __( 'Choose Post Types you want to show wishlist button', 'wordpress-wishlist' ),
//					'type'    => 'multicheck',
//					'default' => apply_filters( 'rox_wpwl_default_allowed_taxonomies', array( ) ),
//					'options' => self::__get_taxonomies(),
//				),
				array(
					'name'  => 'visibility_option',
					'label' => __( 'Visibility Option', 'wordpress-wishlist' ),
					'desc'  => __( 'Choose where the wishlist button will apare.', 'wordpress-wishlist' ),
					'type'  => 'select',
					'default' => array( apply_filters( 'rox_wpwl_default_visibility_option', 'after_content' ) ),
					'options' => self::__get_visibility_options(),
				),
//				array(
//					'name'  => 'enable_sharing',
//					'label' => __( 'Enable Sharing Options', 'wordpress-wishlist' ),
//					// 'desc'  => __( '', 'wordpress-wishlist' ),
//					'type'  => 'radio',
//					'default' => 'no',
//					'options' => array(
//						'no'            => __( 'No', 'wordpress-wishlist' ),
//						'yes'           => __( 'Yes', 'wordpress-wishlist' ),
//					),
//				),
//				array(
//					'name'    => 'color',
//					'label'   => __( 'Color', 'wordpress-wishlist' ),
//					'desc'    => __( 'Color description', 'wordpress-wishlist' ),
//					'type'    => 'color',
//					'default' => ''
//				),
//				array(
//					'name'    => 'password',
//					'label'   => __( 'Password', 'wordpress-wishlist' ),
//					'desc'    => __( 'Password description', 'wordpress-wishlist' ),
//					'type'    => 'password',
//					'default' => ''
//				),
//				array(
//					'name'    => 'wysiwyg',
//					'label'   => __( 'Advanced Editor', 'wordpress-wishlist' ),
//					'desc'    => __( 'WP_Editor description', 'wordpress-wishlist' ),
//					'type'    => 'wysiwyg',
//					'default' => ''
//				),
				
			)
		);
		if( isset( $loaded_options[ROX_WPWL_PLUGIN_NAME . '_advanced']['allowed_post_types'] )
		    && is_array( $loaded_options[ROX_WPWL_PLUGIN_NAME . '_advanced']['allowed_post_types'] )
		    && array_key_exists( 'product', $loaded_options[ROX_WPWL_PLUGIN_NAME . '_advanced']['allowed_post_types'] )
		    && rox_is_woocommerce_activated() ) {
			$settings_fields[ROX_WPWL_PLUGIN_NAME . '_advanced'][] = array(
				'name'  => 'wc_product_visibility_option',
				'label' => __( 'Visibility Option For WooCommerce Products', 'wordpress-wishlist' ),
				'desc'  => __( 'Choose where the wishlist button will apare in WooCommerce Product.', 'wordpress-wishlist' ),
				'type'  => 'select',
				'default' => apply_filters( 'rox_wpwl_default_wc_visibility_option', 'after_cart_button' ),
				'options' => self::__get_wc_visibility_options(),
			);
		}
		$settings_fields[ROX_WPWL_PLUGIN_NAME . '_advanced'][] = array(
			'name'              => 'list_name_max_length',
			'label'             => __( 'Max Length for Wishlist Name', 'wordpress-wishlist' ),
			'desc'              => __( '', 'wordpress-wishlist' ),
			'placeholder'       => __( '150', 'wordpress-wishlist' ),
			'min'               => -1,
			'max'               => 150,
			'step'              => '1',
			'type'              => 'number',
			'default'           => 150,
			'sanitize_callback' => 'intval'
		);
		$settings_fields[ROX_WPWL_PLUGIN_NAME . '_advanced'][] = array(
			'name'  => 'use_hashid_slug',
			'label' => __( 'Enable unique slug for wishlist', 'wordpress-wishlist' ),
			'desc'  => sprintf( __( 'This will enable YouTube like unique slug, when user create Wishlist from Frontend using <a href="%s" target="_blank">HashIds</a>', 'wordpress-wishlist' ), 'https://hashids.org/php/' ),
			'type'  => 'radio',
			'default' => 'no',
			'options' => array(
				'no'            => __( 'No', 'wordpress-wishlist' ),
				'yes'           => __( 'Yes', 'wordpress-wishlist' ),
			),
		);
		return apply_filters( 'rox_wpwl_settings_fields', $settings_fields );
	}
	public static function __settings_sections() {
		$sections = array(
			array(
				'id'    => ROX_WPWL_PLUGIN_NAME . '_general',
				'title' => __( 'Basic Settings', 'wordpress-wishlist' )
			),
			array(
				'id'    => ROX_WPWL_PLUGIN_NAME . '_advanced',
				'title' => __( 'Advanced Settings', 'wordpress-wishlist' )
			)
		);
		return apply_filters( 'rox_wpwl_settings_sections', $sections );
	}
	public static function __get_visibility_options() {
		return wp_parse_args( apply_filters( 'rox_wpwl_visibility_options', array(
			// 'before_title'      => __( 'Before Post Title', 'wordpress-wishlist' ),
			// 'after_title'       => __( 'After Post Title', 'wordpress-wishlist' ),
			'before_content'     => __( 'Before Post Content', 'wordpress-wishlist' ),
			'after_content'     => __( 'After Post Content', 'wordpress-wishlist' ),
		) ), array(
			'none'              => __( 'None', 'wordpress-wishlist' ),
		) );
	}
	public static function __get_wc_visibility_options() {
		$base = self::__get_visibility_options();
		if( isset( $base['none'] ) )
		return wp_parse_args( apply_filters( 'rox_wpwl_visibility_options', array(
			'before_cart_button'        => __( 'Before Add To Cart Button', 'wordpress-wishlist' ),
			'after_cart_button'         => __( 'After Add To Cart Button', 'wordpress-wishlist' ),
			// 'before_title'              => __( 'Before Post Title', 'wordpress-wishlist' ),
			// 'after_title'               => __( 'After Post Title', 'wordpress-wishlist' ),
			'before_content'             => __( 'Before Post Content', 'wordpress-wishlist' ),
			'after_content'             => __( 'After Post Content', 'wordpress-wishlist' ),
		) ), array(
			'none'              => __( 'None', 'wordpress-wishlist' ),
		) );
	}
	private static function __get_post_types() {
		$post_types = get_post_types( apply_filters( 'rox_wpwl_get_post_types_args', array( 'public'   => true, '_builtin' => false, ) ), 'object' );
		// Get Default Page & Post post_type object
		$postType_post = get_post_types( array( 'name' => 'post' ), 'object' );
		$postType_page = get_post_types( array( 'name' => 'page' ), 'object' );
		
		$post_types = array_merge( $postType_post, $postType_page, $post_types );
		$post_types = array_map( function( $post_type ){ return $post_type->label; }, $post_types );
		
		if( isset( $post_types[RoxWPWLPostTypes::$config['post-type']] ) ) unset( $post_types[RoxWPWLPostTypes::$config['post-type']] );
		
		return apply_filters( 'rox_wpwl_wp_post_types', $post_types );
	}
	private static function __get_taxonomies() {
		$taxonomies = get_taxonomies( apply_filters( 'rox_wpwl_get_taxonomies_args', array( 'public'   => true, '_builtin' => false, ) ), 'object' );
		
		$taxonomy_cat = get_taxonomies( array( 'name' => 'category' ), 'object' );
		$taxonomy_tag = get_taxonomies( array( 'name' => 'post_tag' ), 'object' );
		
		$taxonomies = array_merge( $taxonomy_cat, $taxonomy_tag, $taxonomies );
		$taxonomies = array_map( function( $taxonomy ){ return $taxonomy->label; }, $taxonomies );
		
		if( isset( $taxonomies[RoxWPWLPostTypes::$config['tax-cat']] ) ) unset( $taxonomies[RoxWPWLPostTypes::$config['tax-cat']] );
		if( isset( $taxonomies[RoxWPWLPostTypes::$config['tax-tag']] ) ) unset( $taxonomies[RoxWPWLPostTypes::$config['tax-tag']] );
		if( rox_is_woocommerce_activated() ) {
			if( isset( $taxonomies['product_shipping_class'] ) ) unset( $taxonomies['product_shipping_class'] );
		}
		
		return apply_filters( 'rox_wpwl_wp_taxonomies', $taxonomies );
	}
}