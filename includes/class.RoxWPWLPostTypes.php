<?php
/**
 * Created by PhpStorm.
 * User: blackhunter
 * Date: 2018-12-06
 * Time: 13:21
 */

class RoxWPWLPostTypes {
	/**
	 * PostType and Taxonomy Configs
	 * @return array
	 */
	public static $config = array(
		'post-type'     => 'rox_wishlist',
		'tax-cat'       => 'rox_wishlist_cat',
		'tax-tag'       => 'rox_wishlist_tag',
	);
	/**
	 * FireUp hooks
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		
		add_filter( 'display_post_states', array( __CLASS__, 'add_display_post_states' ), 10, 2 );
//		add_action( 'init', array( __CLASS__, 'register_post_status' ), 9 );
		
		
		add_action( 'rox_wpwl_after_register_post_type', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
		add_action( 'rox_wpwl_flush_rewrite_rules', array( __CLASS__, 'flush_rewrite_rules' ) );
		
		// WP Version < 5.0
		 add_filter( 'gutenberg_can_edit_post_type', array( __CLASS__, 'gutenberg_can_edit_post_type' ), 10, 2 );
		// WP Version >= 5.0
		 add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'gutenberg_can_edit_post_type' ), 10, 2 );
	}
	/**
	 * Register Core Taxonomies.
	 * @return void
	 */
	public static function register_taxonomies() {
		if (!is_blog_installed()) return;
		
		if( ! taxonomy_exists( self::$config['tax-cat'] ) ) {
			do_action( 'rox_wpwl_register_taxonomy' );
			$permalinks = rox_wpwl_get_permalink_structure();
			register_taxonomy(
				self::$config['tax-cat'],
				apply_filters( ROX_WPWL_PLUGIN_NAME . '_taxonomy_objects_wishlist_cat', array( self::$config['post-type'] ) ),
				apply_filters(
					ROX_WPWL_PLUGIN_NAME . '_taxonomy_args_wishlist_cat', array(
						'hierarchical'          => true,
						'label'                 => __( 'Categories', 'woocommerce' ),
						'labels'                => array(
							'name'              => __( 'Wishlist categories', 'woocommerce' ),
							'singular_name'     => __( 'Category', 'woocommerce' ),
							'menu_name'         => _x( 'Categories', 'Admin menu name', 'woocommerce' ),
							'search_items'      => __( 'Search categories', 'woocommerce' ),
							'all_items'         => __( 'All categories', 'woocommerce' ),
							'parent_item'       => __( 'Parent category', 'woocommerce' ),
							'parent_item_colon' => __( 'Parent category:', 'woocommerce' ),
							'edit_item'         => __( 'Edit category', 'woocommerce' ),
							'update_item'       => __( 'Update category', 'woocommerce' ),
							'add_new_item'      => __( 'Add new category', 'woocommerce' ),
							'new_item_name'     => __( 'New category name', 'woocommerce' ),
							'not_found'         => __( 'No categories found', 'woocommerce' ),
						),
						'show_ui'               => true,
						'query_var'             => true,
						'rewrite'               => $permalinks['category_rewrite_slug'] ? array(
							'slug'         => $permalinks['category_rewrite_slug'],
							'with_front'   => false,
							'hierarchical' => true,
						): false,
					)
				)
			);
			
			register_taxonomy(
				self::$config['tax-tag'],
				apply_filters( ROX_WPWL_PLUGIN_NAME . '_taxonomy_objects_wishlist_tag', array( self::$config['post-type'] ) ),
				apply_filters(
					ROX_WPWL_PLUGIN_NAME . '_taxonomy_args_wishlist_tag', array(
						'hierarchical'          => false,
						'label'                 => __( 'Wishlist tags', 'woocommerce' ),
						'labels'                => array(
							'name'                       => __( 'Wishlist tags', 'woocommerce' ),
							'singular_name'              => __( 'Tag', 'woocommerce' ),
							'menu_name'                  => _x( 'Tags', 'Admin menu name', 'woocommerce' ),
							'search_items'               => __( 'Search tags', 'woocommerce' ),
							'all_items'                  => __( 'All tags', 'woocommerce' ),
							'edit_item'                  => __( 'Edit tag', 'woocommerce' ),
							'update_item'                => __( 'Update tag', 'woocommerce' ),
							'add_new_item'               => __( 'Add new tag', 'woocommerce' ),
							'new_item_name'              => __( 'New tag name', 'woocommerce' ),
							'popular_items'              => __( 'Popular tags', 'woocommerce' ),
							'separate_items_with_commas' => __( 'Separate tags with commas', 'woocommerce' ),
							'add_or_remove_items'        => __( 'Add or remove tags', 'woocommerce' ),
							'choose_from_most_used'      => __( 'Choose from the most used tags', 'woocommerce' ),
							'not_found'                  => __( 'No tags found', 'woocommerce' ),
						),
						'show_ui'               => true,
						'query_var'             => true,
						'rewrite'               => $permalinks['tag_rewrite_slug']? array(
							'slug'       => $permalinks['tag_rewrite_slug'],
							'with_front' => false,
						): false,
					)
				)
			);
			do_action( 'rox_wpwl_after_register_taxonomy' );
		}
		if( apply_filters( 'rox_wpwl_create_default_terms', true ) ) {
			if( taxonomy_exists( self::$config['tax-cat'] ) ) {
				$createUnCat = false;
				if( $catId = get_option( '__rox_wpwl_default_list_cat', false ) ) {
					$cat = get_term( $catId, self::$config['tax-cat'] );
					if( ! ( $cat instanceof WP_Term ) ) {
						$createUnCat = true;
					}
				} else {
					$createUnCat = true;
				}
				
				if( $createUnCat ) {
					
					$cat = wp_insert_term( apply_filters( 'rox_wpwl_default_cate_name', __( 'Uncategorized', 'wordpress-wishlist' ) ),
						self::$config['tax-cat'],
						array(
							'slug' => apply_filters( 'rox_wpwl_default_cate_slug', 'uncategorized' ),
						)
					);
					if( ! is_wp_error( $cat ) ) {
						update_option( '__rox_wpwl_default_list_cat', $cat['term_id'] );
					}
				}
			}
		}
	}
	
	/**
	 * Register Core PostTypes
	 * @return void
	 */
	public static function register_post_types () {
		if ( ! is_blog_installed() || post_type_exists( self::$config['post-type'] ) ) return;
		do_action( 'rox_wpwl_register_post_type' );
		$permalinks = rox_wpwl_get_permalink_structure();
		$supports   = array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' );
		if ( 'yes' === rox_wpwl_get_option( 'enable_reviews' ) ) $supports[] = 'comments';
		$wishlist_page = get_post( rox_wpwl_get_option( 'wishlist_archive' ) );
		if ( current_theme_supports( 'wp-wishlist' ) ) {
			$has_archive = $wishlist_page && $wishlist_page->post_status == 'publish' ? urldecode( get_page_uri( $wishlist_page ) ) : 'wishlist';
		} else {
			$has_archive = false;
		}
		// If theme support changes, we may need to flush permalinks since some are changed based on this flag.
		if ( update_option( 'current_theme_supports_wp_wishlist', current_theme_supports( 'wp_wishlist' ) ? 'yes' : 'no' ) ) {
			update_option( 'wp_wishlist_queue_flush_rewrite_rules', 'yes' );
		}
		register_post_type(
			self::$config['post-type'],
			apply_filters(
				ROX_WPWL_PLUGIN_NAME . '_register_post_type_wishlist',
				array(
					'labels'              => array(
						'name'                  => __( 'Wishlists', 'woocommerce' ),
						'singular_name'         => __( 'Wishlist', 'woocommerce' ),
						'all_items'             => __( 'All Lists', 'woocommerce' ),
						'menu_name'             => _x( 'Wishlists', 'Admin menu name', 'woocommerce' ),
						'add_new'               => __( 'Add New', 'woocommerce' ),
						'add_new_item'          => __( 'Add new list', 'woocommerce' ),
						'edit'                  => __( 'Edit', 'woocommerce' ),
						'edit_item'             => __( 'Edit Wishlist', 'woocommerce' ),
						'new_item'              => __( 'New Wishlist', 'woocommerce' ),
						'view_item'             => __( 'View Wishlist', 'woocommerce' ),
						'view_items'            => __( 'View Wishlists', 'woocommerce' ),
						'search_items'          => __( 'Search Wishlists', 'woocommerce' ),
						'not_found'             => __( 'No wishlists found', 'woocommerce' ),
						'not_found_in_trash'    => __( 'No wishlists found in trash', 'woocommerce' ),
						'parent'                => __( 'Parent wishlist', 'woocommerce' ),
						'featured_image'        => __( 'Wishlist banner image', 'woocommerce' ),
						'set_featured_image'    => __( 'Set banner image', 'woocommerce' ),
						'remove_featured_image' => __( 'Remove banner image', 'woocommerce' ),
						'use_featured_image'    => __( 'Use as banner image', 'woocommerce' ),
						'insert_into_item'      => __( 'Insert into wishlist', 'woocommerce' ),
						'uploaded_to_this_item' => __( 'Uploaded to this wishlist', 'woocommerce' ),
						'filter_items_list'     => __( 'Filter wishlists', 'woocommerce' ),
						'items_list_navigation' => __( 'Wishlists navigation', 'woocommerce' ),
						'items_list'            => __( 'Wishlists list', 'woocommerce' ),
					),
					'description'         => __( 'This is where you can add new wishlist items.', 'woocommerce' ),
					'menu_icon'           => 'dashicons-editor-ol',
					'public'              => true,
					'show_ui'             => true,
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'publicly_queryable'  => true,
					'exclude_from_search' => false,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'rewrite'             => $permalinks['wishlist_rewrite_slug'] ? array(
						'slug'       => $permalinks['wishlist_rewrite_slug'],
						'with_front' => false,
						'feeds'      => true,
					) : false,
					'query_var'           => true,
					'supports'            => $supports,
					'has_archive'         => $has_archive,
					'show_in_nav_menus'   => true,
					'show_in_rest'        => false,
				)
			)
		);
		do_action( 'rox_wpwl_after_register_post_type' );
	}
//	public static function register_post_status() {
//		register_post_status( 'rox_list_shared', array(
//			'label'                     => _x( 'Shared', 'Wishlist Status', 'wordpress-wishlist' ),
//			'post_type'                 => array( self::$config['post-type'] ),
//			'public'                    => true,
//			'exclude_from_search'       => false,
//			'show_in_admin_all_list'    => true,
//			'show_in_admin_status_list' => true,
//			/* translators: %s: number of list */
//			'label_count'               => _n_noop( 'Shared List <span class="count">(%s)</span>', 'Shared List <span class="count">(%s)</span>', 'wordpress-wishlist' ),
//			'dashicon'                  => 'dashicons-admin-links',
//		) );
//	}
	
	/**
	 * Flush rules if the event is queued.
	 *
	 * @since 3.3.0
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'wp_wishlist_queue_flush_rewrite_rules' ) ) {
			update_option( 'wp_wishlist_queue_flush_rewrite_rules', 'no' );
			self::flush_rewrite_rules();
		}
	}
	
	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}
	/**
	 * Disable Gutenberg for Wishlist.
	 *
	 * @param bool   $can_edit Whether the post type can be edited or not.
	 * @param string $post_type The post type being checked.
	 * @return bool
	 */
	public static function gutenberg_can_edit_post_type( $can_edit, $post_type ) {
		return self::$config['post-type'] === $post_type ? false : $can_edit;
	}
	/**
	 * Add Wishlist Support to Jetpack Omnisearch.
	 */
	public static function support_jetpack_omnisearch() {
		if ( class_exists( 'Jetpack_Omnisearch_Posts' ) ) {
			new Jetpack_Omnisearch_Posts( self::$config['post-type'] );
		}
	}
	/**
	 * Add a post display state for special pages in the page list table.
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post        The current post object.
	 * @return array
	 */
	public static function add_display_post_states( $post_states, $post ) {
		if ( (int) rox_wpwl_get_option( 'wishlist_archive' ) === $post->ID ) {
			$post_states['rox_wpwl_page_for_wishlist'] = __( 'Wishlist Page', 'wordpress-wishlist' );
		}
		return $post_states;
	}
}
RoxWPWLPostTypes::init();