<?php
/**
 * RoxWPWL Installer
 * @package RoxWPWL
 * @version 1.0.0
 * @since 1.0.0
 */

/**
 * Class RoxWPWLInstaller
 * Plugin Installer Script
 * @see https://developer.wordpress.org/plugins/the-basics/activation-deactivation-hooks/
 * @see https://developer.wordpress.org/plugins/the-basics/uninstall-methods/
 *
 */
class RoxWPWLInstaller {
	/**
	 * Init Installer Hooks
	 * @return void
	 */
	public static function init() {
		register_activation_hook( ROX_WPWL_PLUGIN_FILE, array( __CLASS__, 'install' ) );
		register_deactivation_hook( ROX_WPWL_PLUGIN_FILE, 'deactivation' );
		register_uninstall_hook( ROX_WPWL_PLUGIN_FILE, array( __CLASS__, 'uninstall' ) );
		add_action( 'wpmu_new_blog', array( __CLASS__, 'on_create_blog' ), 10, 6 );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'on_delete_blog' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'aditional_supports' ) );
		add_action( 'init', array( __CLASS__, 'wpdb_tables' ), 0 );
	}
	/**
	 * Fx WPDB
	 * @return void
	 */
	public static function wpdb_tables() {
		global $wpdb;
		foreach( RoxWPWL()->get_tables() as $k => $tableName ) {
			$wpdb->tables[] = $tableName;
		}
		$wpdb->wishlist_items = RoxWPWL()->get_tables()['item'];
	}
	/**
	 * Aditional Supports for wishlist posts
	 * @return void
	 */
	public static function aditional_supports() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
		add_post_type_support( RoxWPWLPostTypes::$config['post-type'], 'thumbnail' );
		$thumbArgs = wp_parse_args( apply_filters( 'rox_wpwishlist_thumbnail', array() ), array(
			'width' => 1200,
			'height' => 630,
			'crop' => true,
		) );
		add_image_size( 'rox_wpwishlist_cover', $thumbArgs['width'], $thumbArgs['height'], $thumbArgs['crop'] );
	}
	/**
	 * Plugin Installation/Activation Callback
	 * @return void
	 */
	public static function install( $network_wide ) {
		global $wpdb;
		if ( is_multisite() && $network_wide ) {
			// Get all blogs in the network and activate plugin on each one
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				if( get_option( 'rox_wp_wishlist_installed', false ) != false ) continue;
				self::__db_structure();
				self::__update_default_settings();
				restore_current_blog();
			}
		} else {
			if( get_option( 'rox_wp_wishlist_installed', false ) == false ) {
				self::__db_structure();
				self::__update_default_settings();
			}
		}
	}
	/**
	 *
	 */
	private static function __update_default_settings() {
		$wishlist = '';
		$pages = get_posts( array(
			'name'        => 'wishlists',
			'post_type'   => 'page',
			'post_status' => 'publish',
			'numberposts' => 1
		) );
		if( count( $pages ) < 1 ) {
			$wishlist = wp_insert_post( array(
				'post_title' => __( 'Wishlists', 'wordpress-wishlist' ),
				'post_name'  => __( 'wishlists', 'wordpress-wishlist' ),
				'post_type' => 'page',
				'post_status' => 'publish',
			) );
			if( is_wp_error( $wishlist ) ) $wishlist = '';
		}
		$allowedPostTypes = array( 'post' );
		if( rox_is_woocommerce_activated() ) $allowedPostTypes[] = 'product';
		$defaultSettings = array(
			ROX_WPWL_PLUGIN_NAME . '_general' => array(
				'wishlist_archive'  => $wishlist,
			),
			ROX_WPWL_PLUGIN_NAME . '_advanced' => array(
				'allowed_post_types' => $allowedPostTypes,
				'enable_review' => 'no',
				'enable_sharing' => 'no',
				'visibility_option' => 'after_content',
			),
		);
		foreach( $defaultSettings as $section => $settings ) {
			if( ! empty( $settings ) ) {
				update_option( $section, $settings );
			}
		}
		update_option( 'rox_wp_wishlist_installed', 1 );
	}
	/**
	 * Plugin Deactivation Callback
	 * @return void
	 */
	public static function deactivation( $network_deactivating ) {
		// unregister the post type, so the rules are no longer in memory
		unregister_post_type( RoxWPWLPostTypes::$config['post-type'] );
		// clear the permalinks to remove our post type's rules from the database
		flush_rewrite_rules();
		// Clear any cached data that has been removed.
		wp_cache_flush();
	}
	/**
	 * Plugin Uninstallation Callback
	 * @return void
	 */
	public static function uninstall() {}
	public static function on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
		if ( is_plugin_active_for_network( ROX_WPWL_PLUGIN_FILE ) ) {
			switch_to_blog( $blog_id );
			self::__db_structure();
			self::__update_default_settings();
			restore_current_blog();
		}
	}
	public static function on_delete_blog( $tables ) {
		foreach( RoxWPWL()->get_tables() as $k => $tableName ) {
			$tables[] = $tableName;
		}
		return $tables;
	}
	private static function __db_structure() {
		global $wpdb;
		$dbVersion = get_option( RoxWPWL()->get_plugin_name() . '_db_version', false );
		$tables = RoxWPWL()->get_tables();
		$charset_collate = $wpdb->get_charset_collate();
		$strutcutes = array();
		if( ! function_exists( 'dbDelta' ) ) require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$strutcutes['create']['item'] = <<<SQL
CREATE TABLE `__rox_table__` (
    `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
    `post_id` BIGINT UNSIGNED NOT NULL ,
    `item_title` TEXT NULL ,
    `item_content` TEXT NULL ,
    `item_type` VARCHAR(100) NOT NULL DEFAULT 'generic' ,
    `object_id` BIGINT UNSIGNED NULL ,
    `created_at` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
    `updated_at` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ,
    `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' ,
    `menu_order` INT(11) NOT NULL DEFAULT '0' ,
    PRIMARY KEY (`ID`), INDEX (`post_id`), INDEX (`item_type`), INDEX (`deleted`)
) $charset_collate;
SQL;
		$strutcutes['create']['contributor'] = <<<SQL
CREATE TABLE `__rox_table__` (
    `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
    `post_id` BIGINT UNSIGNED NOT NULL ,
    `user_id` BIGINT UNSIGNED NOT NULL ,
    `permissions` VARCHAR(100) NOT NULL DEFAULT 'view' ,
    PRIMARY KEY (`ID`), INDEX (`post_id`), INDEX (`user_id`)
) $charset_collate;
SQL;

		if( ! $dbVersion ) {
			if( isset( $strutcutes['create'] ) ) {
				foreach( $strutcutes['create'] as $name => $strutcute ) {
					if( ! isset( $tables[$name] ) ) continue;
					if( $wpdb->get_var( "show tables like '{$tables[$name]}'" ) == $tables[$name] ) continue;
					$sql = str_replace( '__rox_table__', $tables[$name], $strutcute );
					dbDelta( $sql );
				}
			}
			update_option( RoxWPWL()->get_plugin_name() . '_db_version', ROX_WPWL_DB_VERSION, false );
		}
	}
}
// End of file class.RoxWPWLInstaller.php