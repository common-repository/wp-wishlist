<?php
/**
 * WordPress Wishlist
 *
 * Plugin Name: WordPress Wishlist
 * Plugin URI: https://pluginrox.com/plugin/wordpress-wishlist/
 * Description: Easy List/Bookmark Management for WordPress
 * Version: 1.0.0
 * Author: PluginRox
 * Author URI: https://pluginrox.com/
 * License: GPLv2 or later
 * Text Domain: wordpress-wishlist
 * Domain Path: /languages/
 * @package RoxWPWL
 * @subpackage SearchLite
 *
 * MinWP: 4.6.0
 */
if( ! function_exists( 'add_action' ) ) {
    header('HTTP/1.0 403 Forbidden');
    die("<h1>Forbidden</h1><br><br><p>Go Away!</p><hr><p>Just Go Away!!!</p>");
}

/**
 * Plugin __FILE__
 * @var string
 */
define( 'ROX_WPWL_PLUGIN_FILE', __FILE__ );
define( 'ROX_WPWL_PLUGIN_NAME', 'wp_wishlist' );
/**
 * Plugin basename
 * @var string
 */
define( 'ROX_WPWL_PLUGIN_BASENAME', plugin_basename( ROX_WPWL_PLUGIN_FILE ) );
/**
 * Plugin Path
 * @var string
 */
define( 'ROX_WPWL_PATH', plugin_dir_path( ROX_WPWL_PLUGIN_FILE ) );
/**
 * Plugin URL
 * @var string
 */
define( 'ROX_WPWL_URL', plugins_url( '/', ROX_WPWL_PLUGIN_FILE ) );
/**
 * Plugin Template Folder
 * @var string
 */
define( 'ROX_WPWL_TEMPLATES', 'templates' );
/**
 * Plugin Version
 * @var string
 */
define( 'ROX_WPWL_VERSION', '1.0.0' );
/**
 * Plugin Database Table Schema Version
 * @var string
 */
define( 'ROX_WPWL_DB_VERSION', '1.0.0' );

if( ! defined( 'ROX_WPWL_TEMPLATE_DEBUG' ) ) {
	/**
	 * Plugin Template Debug Mode
	 * @var bool
	 */
	define( 'ROX_WPWL_TEMPLATE_DEBUG', false );
}

//add_theme_support( 'wp-wishlist' );
// current_theme_supports( 'wp-wishlist' );
// Include Classes
if( ! class_exists( 'RoxWPWL' ) ) {
	require_once( ROX_WPWL_PATH . '/includes/class.RoxWPWL.php' );
}
function RoxWPWL() {
    return RoxWPWL::getInstance();
}
$GLOBALS['RoxWPWL']  = RoxWPWL();
// End of file wp-wishlist.php