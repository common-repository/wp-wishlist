<?php
/**
 *
 */

$hook_suffix = '';
if ( isset( $page_hook ) ) {
	$hook_suffix = $page_hook;
} elseif ( isset( $plugin_page ) ) {
	$hook_suffix = $plugin_page;
} elseif ( isset( $pagenow ) ) {
	$hook_suffix = $pagenow;
}
final class RoxWPWLAdmin {
	/**
	 * The single instance of the class.
	 *
	 * @var RoxWPWLAdmin
	 */
	protected static $instance;
	
	private static $settingsApi;
	/**
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @return RoxWPWLAdmin
	 */
	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'includes' ) );
		add_action( 'current_screen', array( __CLASS__, 'conditional_includes' ) );
		
		if( ! class_exists( 'WeDevs_Settings_API', false ) ) require_once( RoxWPWL()->plugin_path( 'includes/class.WeDevs_Settings_API.php' ) );
		self::$settingsApi = new WeDevs_Settings_API();
		
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, '__enqueue' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'rox_option_save', array( __CLASS__, 'after_settings_save' ), 10, 3 );
	}
	public static function includes() {
	
	}
	public static function conditional_includes() {
		if ( ! $screen = get_current_screen() ) return;
		switch( $screen->id ) {
			case 'toplevel_page_rox_plugins':
				require_once( RoxWPWL()->plugin_path( 'includes/class.WeDevs_Settings_API.php' ) );
				break;
			case 'toplevel_page_rox_plugins':
				// require_once( RoxWPWL()->plugin_path( 'includes/.php' ) );
				break;
//			case 'options-permalink':
//				require_once( RoxWPWL()->plugin_path( 'includes/class.RoxWPWLAdminPermalink.php' ) );
//				break;
			case 'edit-rox_wishlist':
				// require_once( RoxWPWL()->plugin_path( 'includes/.php' ) );
				break;
			case 'rox_wishlist':
				require_once( RoxWPWL()->plugin_path( 'includes/class.RoxWPWLMetaBoxes.php' ) );
				break;
			default:
				break;
		}
	}
	public static function after_settings_save() {
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		do_action( 'woocommerce_settings_saved' );
	}
	public static function __enqueue ( $hookname ) {
		global $post_type, $post;
		$prefix = defined( 'WP_DEBUG' ) && WP_DEBUG? '' : '.min';
		wp_enqueue_style( 'rox-wpwl-admin-style', RoxWPWL()->plugin_url( 'assets/css/admin'.$prefix.'.css' ), array(), ROX_WPWL_VERSION );
		wp_enqueue_script( 'jquery' );
		$rox_wpwl_js_conf = array();
		$adminJsDeps = array( 'jquery' );
		if( 'rox-plugins_page_wp_wishlist' === $hookname || RoxWPWLPostTypes::$config['post-type'] === $post_type) {
		    
		    $rox_wpwl_js_conf = apply_filters( ROX_WPWL_PLUGIN_NAME . '_js_admin_configs', array(
				'rox_ajax'          => RoxWPWL()->ajax_url(),
				'rox_csrf'          => wp_create_nonce( '__csrf' . ROX_WPWL_PLUGIN_NAME ),
				'rox_action'        => ROX_WPWL_PLUGIN_NAME.'_admin_actions',
				'rox_error'         => __( 'We encountered an error while processing your request. Please try again after sometime.', 'wordpress-wishlist' ),
				'is_edit'           => ( 'rox_wishlist' === $post_type && ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) ) ? 1 : 0,
				'wishlist'          => $post,
				'_deps_object_id'   => array( 'post', 'taxonomy_term' ),
				'enable_sharing'    => ( 'yes' == rox_wpwl_get_option( 'enable_sharing', 'advanced' ) ) ? 1 : 0,
				'share_modal_label' => __( 'Share with others', 'wordpress-wishlist' ),
			) );
			wp_register_script( 'jquery_sortable', RoxWPWL()->plugin_url( 'assets/js/jquery-sortable'.$prefix.'.js' ), array( 'jquery' ), ROX_WPWL_VERSION, true );
			$adminJsDeps[] = 'jquery_sortable';
		}
		$adminJsDeps[] = 'updates';
		wp_register_script( ROX_WPWL_PLUGIN_NAME . '_admin_script', RoxWPWL()->plugin_url( 'assets/js/admin'.$prefix.'.js' ), $adminJsDeps, ROX_WPWL_VERSION, true );
		wp_localize_script( ROX_WPWL_PLUGIN_NAME . '_admin_script', 'rox_wpwl_configs', $rox_wpwl_js_conf );
		wp_enqueue_script( ROX_WPWL_PLUGIN_NAME . '_admin_script' );
	}
	public static function add_admin_menu() {
		if ( empty ( $GLOBALS['admin_page_hooks']['rox_plugins'] ) ) {
			add_menu_page(
				__( 'Rox Plugins', 'wordpress-wishlist' ),
				__( 'Rox Plugins', 'wordpress-wishlist' ),
				'manage_options',
				'rox_plugins',
				array( __CLASS__, 'add_base_menu_page' ),
				rox_wpwl_get_svg( 'plugin-rox-icon', false ),
				( is_multisite() )? 20 : 65
			);
		}
		add_submenu_page(
			'rox_plugins',
			__( 'Wishtlist', 'wordpress-wishlist' ),
			__( 'Wishtlist', 'wordpress-wishlist' ),
			'manage_options',
			ROX_WPWL_PLUGIN_NAME,
			array( __CLASS__, 'add_menu_page' )
		);
	}
	public static function admin_init() {
	    global $tab, $paged, $roxPlugins, $pagenum, $total_pages, $wp_list_table;
		//set the settings
		self::$settingsApi->set_sections( RoxWPWLSettings::__settings_sections() );
		self::$settingsApi->set_fields( RoxWPWLSettings::__settings_fields() );
		self::$settingsApi->admin_init();
		$tab = 'rox_plugins';
		$roxPlugins = new RoxPluginTable();
		$wp_list_table = $roxPlugins;
		$roxPlugins->prepare_items();
		$pagenum = $roxPlugins->get_pagenum();
		$paged = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;
		$total_pages = $roxPlugins->get_pagination_arg( 'total_pages' );
		if ( $pagenum > $total_pages && $total_pages > 0 ) {
			wp_redirect( add_query_arg( 'paged', $total_pages ) );
			exit;
		}
	}
	public static function add_menu_page() {
		?>
		<div class="wrap">
			<h1><span class="dashicons dashicons-heart"></span> <?php echo get_admin_page_title(); ?></h1>
			<hr class="wp-header-end">
			<?php self::$settingsApi->settings_status(); ?>
			<hr>
			<div class="rox-admin-wrap">
				<form method="post" action="options.php">
					<?php
					self::$settingsApi->show_navigation();
					self::$settingsApi->show_forms();
					?>
				</form>
			</div>
			<hr>
			<br class="clear">
		</div>
		<?php
	}
	public static function add_base_menu_page () {
		global $tab, $roxPlugins, $paged, $pagenum, $total_pages;
		wp_enqueue_script( 'plugin-install' );
		add_thickbox();
		wp_enqueue_script( 'updates' );
		?>
		<div class="wrap plugin-rox rox-install">
			<h1 class="page-title"><img src="<?php echo rox_wpwl_get_svg( 'plugin-rox-logo', false ); ?>" alt="<?php echo get_admin_page_title(); ?>"><span class="screen-reader-text"><?php echo get_admin_page_title(); ?></span></h1>
			<hr class="wp-header-end">
            <?php
//                $roxPlugins->views();
                echo '<br class="clear" />';
                /**
                 * Fires after the plugins list table in each tab of the Install Plugins screen.
                 *
                 * The dynamic portion of the action hook, `$tab`, allows for targeting
                 * individual tabs, for instance 'install_plugins_plugin-information'.
                 *
                 * @since 2.7.0
                 *
                 * @param int $paged The current page number of the plugins list table.
                 */
                // do_action( "install_plugins_{$tab}", $paged );
            ?>
            <form id="plugin-filter" method="post">
                <?php $roxPlugins->display(); ?>
            </form>
            <span class="spinner"></span>
            <br class="clear">
            <div class="services"></div>
            <br class="clear">
        </div>
		<?php
		wp_print_request_filesystem_credentials_modal();
		wp_print_admin_notice_templates();
	}
}
RoxWPWLAdmin::getInstance();

if( ! class_exists( 'RoxPluginTable', false ) ) {
    if( ! class_exists( 'WP_Plugin_Install_List_Table', false ) ) {
	    if( ! class_exists( 'WP_List_Table', false ) ) {
		    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
        }
	    require_once( ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php' );
    }
	class RoxPluginTable extends WP_Plugin_Install_List_Table {
		public $order = 'ASC';
		public $orderby = null;
		public $groups = array();
		protected $author = 'pluginrox';
		
		private $error;
		
		/**
		 *
		 * @return bool
		 */
		public function ajax_user_can() {
			return current_user_can('install_plugins');
		}
		
		/**
		 * Return the list of known plugins.
		 *
		 * Uses the transient data from the updates API to determine the known
		 * installed plugins.
		 *
		 * @since 4.9.0
		 * @access protected
		 *
		 * @return array
		 */
		protected function get_installed_plugins() {
			$plugins = array();
			
			$plugin_info = get_site_transient( 'update_plugins' );
			if ( isset( $plugin_info->no_update ) ) {
				foreach ( $plugin_info->no_update as $plugin ) {
					$plugin->upgrade          = false;
					$plugins[ $plugin->slug ] = $plugin;
				}
			}
			
			if ( isset( $plugin_info->response ) ) {
				foreach ( $plugin_info->response as $plugin ) {
					$plugin->upgrade          = true;
					$plugins[ $plugin->slug ] = $plugin;
				}
			}
			
			return $plugins;
		}
		
		/**
		 * Return a list of slugs of installed plugins, if known.
		 *
		 * Uses the transient data from the updates API to determine the slugs of
		 * known installed plugins. This might be better elsewhere, perhaps even
		 * within get_plugins().
		 *
		 * @since 4.0.0
		 *
		 * @return array
		 */
		protected function get_installed_plugin_slugs() {
			return array_keys( $this->get_installed_plugins() );
		}
		
		/**
		 *
		 * @global array  $tabs
		 * @global string $tab
		 * @global int    $paged
		 * @global string $type
		 * @global string $term
		 */
		public function prepare_items() {
		    
		    global $tabs, $tab, $paged, $type, $term;
			
			wp_reset_vars( array( 'tab' ) );
			
			$paged = $this->get_pagenum();
			
			$per_page = 30;
			
			// These are the tabs which are shown on the page
			$tabs = array();
			$tabs['rox_plugins']    = _x( 'Plugins By PluginRox', 'Plugin Installer' );
			if ( 'search' === $tab ) {
				$tabs['search'] = __( 'Search Results' );
			}
			
			
			$nonmenu_tabs = array( 'plugin-information' ); // Valid actions to perform which do not have a Menu item.
			
			/**
			 * Filters the tabs shown on the Plugin Install screen.
			 *
			 *
			 *
			 * @param array $tabs The tabs shown on the Plugin Install screen. Defaults include 'featured', 'popular',
			 *                    'recommended', 'favorites', and 'upload'.
			 */
			$tabs = apply_filters( 'rox_install_plugins_tabs', $tabs );
			
			// If a non-valid menu tab has been selected, And it's not a non-menu action.
			if ( empty( $tab ) || ( !isset( $tabs[ $tab ] ) && !in_array( $tab, (array) $nonmenu_tabs ) ) )
				$tab = key( $tabs );
			
			$installed_plugins = $this->get_installed_plugins();
			
			$args = array(
				'page' => $paged,
				'per_page' => $per_page,
				'fields' => array(
					'last_updated' => true,
					'icons' => true,
					'active_installs' => true
				),
				'author' => $this->author,
				// Send the locale and installed plugin slugs to the API so it can provide context-sensitive results.
				'locale' => get_user_locale(),
				'installed_plugins' => array_keys( $installed_plugins ),
			);
			// $args['fields']['group'] = true;
			// $this->orderby = 'group';
			// $args['browse'] = $tab;
			switch ( $tab ) {
				case 'search':
					$type = isset( $_REQUEST['type'] ) ? wp_unslash( $_REQUEST['type'] ) : 'term';
					$term = isset( $_REQUEST['s'] ) ? wp_unslash( $_REQUEST['s'] ) : '';
					switch ( $type ) {
						case 'tag':
							$args['tag'] = sanitize_title_with_dashes( $term );
							break;
						case 'term':
							$args['search'] = $term;
							break;
					}
					break;
				default:
					break;
			}
			
			/**
			 * Filters API request arguments for each Plugin Install screen tab.
			 *
			 * The dynamic portion of the hook name, `$tab`, refers to the plugin install tabs.
			 * Default tabs include 'featured', 'popular', 'recommended', 'favorites', and 'upload'.
			 *
			 * @since 3.7.0
			 *
			 * @param array|bool $args Plugin Install API arguments.
			 */
			$args = apply_filters( "install_plugins_table_api_args_{$tab}", $args );
			if ( !$args ) return;
			
			$api = rox_wp_plugins_api( 'query_plugins', $args );
			
			if ( is_wp_error( $api ) ) {
				$this->error = $api;
				return;
			}
			
			$this->items = $api->plugins;
			
			if ( $this->orderby ) {
				uasort( $this->items, array( $this, 'order_callback' ) );
			}
			
			$this->set_pagination_args( array(
				'total_items' => $api->info['results'],
				'per_page' => $args['per_page'],
			) );
			
			if ( isset( $api->info['groups'] ) ) {
				$this->groups = $api->info['groups'];
			}
			
			if ( $installed_plugins ) {
				$js_plugins = array_fill_keys(
					array( 'all', 'search', 'active', 'inactive', 'recently_activated', 'mustuse', 'dropins' ),
					array()
				);
				
				$js_plugins['all'] = array_values( wp_list_pluck( $installed_plugins, 'plugin' ) );
				$upgrade_plugins   = wp_filter_object_list( $installed_plugins, array( 'upgrade' => true ), 'and', 'plugin' );
				
				if ( $upgrade_plugins ) {
					$js_plugins['upgrade'] = array_values( $upgrade_plugins );
				}
				
				wp_localize_script( 'updates', '_wpUpdatesItemCounts', array(
					'plugins' => $js_plugins,
					'totals'  => wp_get_update_data(),
				) );
			}
		}
		
		/**
		 */
		public function no_items() {
			if ( isset( $this->error ) ) { ?>
                <div class="inline error"><p><?php echo $this->error->get_error_message(); ?></p>
                    <p class="hide-if-no-js"><button class="button try-again"><?php _e( 'Try Again' ); ?></button></p>
                </div>
			<?php } else { ?>
                <div class="no-plugin-results"><?php _e( 'No plugins found. Try a different search.' ); ?></div>
				<?php
			}
		}
		
		/**
		 *
		 * @global array $tabs
		 * @global string $tab
		 *
		 * @return array
		 */
		protected function get_views() {
			global $tabs, $tab;
			
			$display_tabs = array();
			foreach ( (array) $tabs as $action => $text ) {
				$current_link_attributes = ( $action === $tab ) ? ' class="current" aria-current="page"' : '';
				$href = self_admin_url('admin.php?page=rox_plugins&tab=' . $action);
				$display_tabs['plugin-install-'.$action] = "<a href='$href'$current_link_attributes>$text</a>";
			}
			// No longer a real tab.
			unset( $display_tabs['plugin-install-upload'] );
			
			return $display_tabs;
		}
		
		/**
		 * Override parent views so we can use the filter bar display.
		 */
		public function views() {
			$views = $this->get_views();
			
			/** This filter is documented in wp-admin/inclues/class-wp-list-table.php */
			$views = apply_filters( "views_{$this->screen->id}", $views );
			
			$this->screen->render_screen_reader_content( 'heading_views' );
			?>
            <div class="wp-filter">
                <ul class="filter-links">
					<?php
					if ( ! empty( $views ) ) {
						foreach ( $views as $class => $view ) {
							$views[ $class ] = "\t<li class='$class'>$view";
						}
						echo implode( " </li>\n", $views ) . "</li>\n";
					}
					?>
                </ul>
	            <?php
	            $type = isset( $_REQUEST['type'] ) ? wp_unslash( $_REQUEST['type'] ) : 'term';
	            $term = isset( $_REQUEST['s'] ) ? wp_unslash( $_REQUEST['s'] ) : '';
	            ?><form class="search-form search-plugins" method="get">
                    <input type="hidden" name="tab" value="search" />
                    <label class="screen-reader-text" for="typeselector"><?php _e( 'Search plugins by:' ); ?></label>
                    <select name="type" id="typeselector">
                        <option value="term"<?php selected( 'term', $type ); ?>><?php _e( 'Keyword' ); ?></option>
                        <option value="tag"<?php selected( 'tag', $type ); ?>><?php _ex( 'Tag', 'Plugin Installer' ); ?></option>
                    </select>
                    <label><span class="screen-reader-text"><?php _e( 'Search Plugins' ); ?></span>
                        <input type="search" name="s" value="<?php echo esc_attr( $term ) ?>" class="wp-filter-search" placeholder="<?php esc_attr_e( 'Search plugins...' ); ?>" />
                    </label>
		            <?php submit_button( __( 'Search Plugins' ), 'hide-if-js', false, false, array( 'id' => 'search-submit' ) ); ?>
                </form>
            </div>
			<?php
		}
		
		/**
		 * Override the parent display() so we can provide a different container.
		 */
		public function display() {
			$singular = $this->_args['singular'];
			
			$data_attr = '';
			
			if ( $singular ) {
				$data_attr = " data-wp-lists='list:$singular'";
			}
			
			$this->display_tablenav( 'top' );
			
			?>
            <div class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
				<?php
				$this->screen->render_screen_reader_content( 'heading_list' );
				?>
                <div id="the-list"<?php echo $data_attr; ?>>
					<?php $this->display_rows_or_placeholder(); ?>
                </div>
            </div>
			<?php
			$this->display_tablenav( 'bottom' );
		}
		
		/**
		 * @global string $tab
		 *
		 * @param string $which
		 */
		protected function display_tablenav( $which ) {
			if ( $GLOBALS['tab'] === 'featured' ) {
				return;
			}
			
			if ( 'top' === $which ) {
				wp_referer_field();
				?>
                <div class="tablenav top">
                    <div class="alignleft actions">
						<?php
						/**
						 * Fires before the Plugin Install table header pagination is displayed.
						 *
						 * @since 2.7.0
						 */
						do_action( 'install_plugins_table_header' ); ?>
                    </div>
					<?php $this->pagination( $which ); ?>
                    <br class="clear" />
                </div>
			<?php } else { ?>
                <div class="tablenav bottom">
					<?php $this->pagination( $which ); ?>
                    <br class="clear" />
                </div>
				<?php
			}
		}
		
		/**
		 * @return array
		 */
		protected function get_table_classes() {
			return array( 'widefat', $this->_args['plural'] );
		}
		
		/**
		 * @return array
		 */
		public function get_columns() {
			return array();
		}
		
		/**
		 * @param object $plugin_a
		 * @param object $plugin_b
		 * @return int
		 */
		private function order_callback( $plugin_a, $plugin_b ) {
			$orderby = $this->orderby;
			if ( ! isset( $plugin_a->$orderby, $plugin_b->$orderby ) ) {
				return 0;
			}
			
			$a = $plugin_a->$orderby;
			$b = $plugin_b->$orderby;
			
			if ( $a == $b ) {
				return 0;
			}
			
			if ( 'DESC' === $this->order ) {
				return ( $a < $b ) ? 1 : -1;
			} else {
				return ( $a < $b ) ? -1 : 1;
			}
		}
		
		public function display_rows() {
			if( ! function_exists( 'install_plugin_install_status' ) ) {
				include( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			}
			$plugins_allowedtags = array(
				'a' => array( 'href' => array(),'title' => array(), 'target' => array() ),
				'abbr' => array( 'title' => array() ),'acronym' => array( 'title' => array() ),
				'code' => array(), 'pre' => array(), 'em' => array(),'strong' => array(),
				'ul' => array(), 'ol' => array(), 'li' => array(), 'p' => array(), 'br' => array()
			);
			
			$plugins_group_titles = array(
				'Performance' => _x( 'Performance', 'Plugin installer group title' ),
				'Social'      => _x( 'Social',      'Plugin installer group title' ),
				'Tools'       => _x( 'Tools',       'Plugin installer group title' ),
			);
			
			$group = null;
			
			foreach ( (array) $this->items as $plugin ) {
				if ( is_object( $plugin ) ) {
					$plugin = (array) $plugin;
				}
				
				// Display the group heading if there is one
				if ( isset( $plugin['group'] ) && $plugin['group'] != $group ) {
					if ( isset( $this->groups[ $plugin['group'] ] ) ) {
						$group_name = $this->groups[ $plugin['group'] ];
						if ( isset( $plugins_group_titles[ $group_name ] ) ) {
							$group_name = $plugins_group_titles[ $group_name ];
						}
					} else {
						$group_name = $plugin['group'];
					}
					
					// Starting a new group, close off the divs of the last one
					if ( ! empty( $group ) ) {
						echo '</div></div>';
					}
					
					echo '<div class="plugin-group"><h3>' . esc_html( $group_name ) . '</h3>';
					// needs an extra wrapping div for nth-child selectors to work
					echo '<div class="plugin-items">';
					
					$group = $plugin['group'];
				}
				$title = wp_kses( $plugin['name'], $plugins_allowedtags );
				
				// Remove any HTML from the description.
				$description = strip_tags( $plugin['short_description'] );
				$version = wp_kses( $plugin['version'], $plugins_allowedtags );
				
				$name = strip_tags( $title . ' ' . $version );
				
				$author = wp_kses( $plugin['author'], $plugins_allowedtags );
				if ( ! empty( $author ) ) {
					$author = ' <cite>' . sprintf( __( 'By %s' ), $author ) . '</cite>';
				}
				
				$action_links = array();
				
				if ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) {
					$status = install_plugin_install_status( $plugin );
					
					switch ( $status['status'] ) {
						case 'install':
							if ( $status['url'] ) {
								/* translators: 1: Plugin name and version. */
								$action_links[] = '<a class="install-now button" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Install %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '">' . __( 'Install Now' ) . '</a>';
							}
							break;
						
						case 'update_available':
							if ( $status['url'] ) {
								/* translators: 1: Plugin name and version */
								$action_links[] = '<a class="update-now button aria-button-if-js" data-plugin="' . esc_attr( $status['file'] ) . '" data-slug="' . esc_attr( $plugin['slug'] ) . '" href="' . esc_url( $status['url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Update %s now' ), $name ) ) . '" data-name="' . esc_attr( $name ) . '">' . __( 'Update Now' ) . '</a>';
							}
							break;
						
						case 'latest_installed':
						case 'newer_installed':
							if ( is_plugin_active( $status['file'] ) ) {
								$action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x( 'Active', 'plugin' ) . '</button>';
							} elseif ( current_user_can( 'activate_plugin', $status['file'] ) ) {
								$button_text  = __( 'Activate' );
								/* translators: %s: Plugin name */
								$button_label = _x( 'Activate %s', 'plugin' );
								$activate_url = add_query_arg( array(
									'_wpnonce'    => wp_create_nonce( 'activate-plugin_' . $status['file'] ),
									'action'      => 'activate',
									'plugin'      => $status['file'],
								), network_admin_url( 'plugins.php' ) );
								
								if ( is_network_admin() ) {
									$button_text  = __( 'Network Activate' );
									/* translators: %s: Plugin name */
									$button_label = _x( 'Network Activate %s', 'plugin' );
									$activate_url = add_query_arg( array( 'networkwide' => 1 ), $activate_url );
								}
								
								$action_links[] = sprintf(
									'<a href="%1$s" class="button activate-now" aria-label="%2$s">%3$s</a>',
									esc_url( $activate_url ),
									esc_attr( sprintf( $button_label, $plugin['name'] ) ),
									$button_text
								);
							} else {
								$action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x( 'Installed', 'plugin' ) . '</button>';
							}
							break;
					}
				}
				
				$details_link   = self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=' . $plugin['slug'] .
				                                  '&amp;TB_iframe=true&amp;width=600&amp;height=550' );
				
				/* translators: 1: Plugin name and version. */
				$action_links[] = '<a href="' . esc_url( $details_link ) . '" class="thickbox open-plugin-details-modal" aria-label="' . esc_attr( sprintf( __( 'More information about %s' ), $name ) ) . '" data-title="' . esc_attr( $name ) . '">' . __( 'More Details' ) . '</a>';
				
				if ( !empty( $plugin['icons']['svg'] ) ) {
					$plugin_icon_url = $plugin['icons']['svg'];
				} elseif ( !empty( $plugin['icons']['2x'] ) ) {
					$plugin_icon_url = $plugin['icons']['2x'];
				} elseif ( !empty( $plugin['icons']['1x'] ) ) {
					$plugin_icon_url = $plugin['icons']['1x'];
				} else {
					$plugin_icon_url = $plugin['icons']['default'];
				}
				
				/**
				 * Filters the install action links for a plugin.
				 *
				 * @since 2.7.0
				 *
				 * @param array $action_links An array of plugin action hyperlinks. Defaults are links to Details and Install Now.
				 * @param array $plugin       The plugin currently being listed.
				 */
				$action_links = apply_filters( 'plugin_install_action_links', $action_links, $plugin );
				
				$last_updated_timestamp = strtotime( $plugin['last_updated'] );
				?>
                <div class="plugin-card plugin-card-<?php echo sanitize_html_class( $plugin['slug'] ); ?>">
                    <div class="plugin-card-top">
                        <div class="name column-name">
                            <h3>
                                <a href="<?php echo esc_url( $details_link ); ?>" class="thickbox open-plugin-details-modal">
									<?php echo $title; ?>
                                    <img src="<?php echo esc_attr( $plugin_icon_url ) ?>" class="plugin-icon" alt="">
                                </a>
                            </h3>
                        </div>
                        <div class="action-links">
							<?php
							if ( $action_links ) {
								echo '<ul class="plugin-action-buttons"><li>' . implode( '</li><li>', $action_links ) . '</li></ul>';
							}
							?>
                        </div>
                        <div class="desc column-description">
                            <p><?php echo $description; ?></p>
                            <p class="authors"><?php echo $author; ?></p>
                        </div>
                    </div>
                    <div class="plugin-card-bottom">
                        <div class="vers column-rating">
							<?php wp_star_rating( array( 'rating' => $plugin['rating'], 'type' => 'percent', 'number' => $plugin['num_ratings'] ) ); ?>
                            <span class="num-ratings" aria-hidden="true">(<?php echo number_format_i18n( $plugin['num_ratings'] ); ?>)</span>
                        </div>
                        <div class="column-updated">
                            <strong><?php _e( 'Last Updated:' ); ?></strong> <?php printf( __( '%s ago' ), human_time_diff( $last_updated_timestamp ) ); ?>
                        </div>
                        <div class="column-downloaded">
							<?php
							if ( $plugin['active_installs'] >= 1000000 ) {
								$active_installs_text = _x( '1+ Million', 'Active plugin installations' );
							} elseif ( 0 == $plugin['active_installs'] ) {
								$active_installs_text = _x( 'Less Than 10', 'Active plugin installations' );
							} else {
								$active_installs_text = number_format_i18n( $plugin['active_installs'] ) . '+';
							}
							printf( __( '%s Active Installations' ), $active_installs_text );
							?>
                        </div>
                        <div class="column-compatibility">
							<?php
							$wp_version = get_bloginfo( 'version' );
							
							if ( ! empty( $plugin['tested'] ) && version_compare( substr( $wp_version, 0, strlen( $plugin['tested'] ) ), $plugin['tested'], '>' ) ) {
								echo '<span class="compatibility-untested">' . __( 'Untested with your version of WordPress' ) . '</span>';
							} elseif ( ! empty( $plugin['requires'] ) && version_compare( substr( $wp_version, 0, strlen( $plugin['requires'] ) ), $plugin['requires'], '<' ) ) {
								echo '<span class="compatibility-incompatible">' . __( '<strong>Incompatible</strong> with your version of WordPress' ) . '</span>';
							} else {
								echo '<span class="compatibility-compatible">' . __( '<strong>Compatible</strong> with your version of WordPress' ) . '</span>';
							}
							?>
                        </div>
                    </div>
                </div>
				<?php
			}
			
			// Close off the group divs of the last one
			if ( ! empty( $group ) ) {
				echo '</div></div>';
			}
		}
    }
}
if( ! function_exists( 'rox_wp_plugins_api' ) ) {
	/**
	 * @see plugins_api()
	 * Retrieves plugin installer pages from the WordPress.org Plugins API.
	 *
	 * It is possible for a plugin to override the Plugin API result with three
	 * filters. Assume this is for plugins, which can extend on the Plugin Info to
	 * offer more choices. This is very powerful and must be used with care when
	 * overriding the filters.
	 *
	 * The first filter, {@see 'plugins_api_args'}, is for the args and gives the action
	 * as the second parameter. The hook for {@see 'plugins_api_args'} must ensure that
	 * an object is returned.
	 *
	 * The second filter, {@see 'plugins_api'}, allows a plugin to override the WordPress.org
	 * Plugin Installation API entirely. If `$action` is 'query_plugins' or 'plugin_information',
	 * an object MUST be passed. If `$action` is 'hot_tags' or 'hot_categories', an array MUST
	 * be passed.
	 *
	 * Finally, the third filter, {@see 'plugins_api_result'}, makes it possible to filter the
	 * response object or array, depending on the `$action` type.
	 *
	 * Supported arguments per action:
	 *
	 * | Argument Name        | query_plugins | plugin_information | hot_tags | hot_categories |
	 * | -------------------- | :-----------: | :----------------: | :------: | :------------: |
	 * | `$slug`              | No            |  Yes               | No       | No             |
	 * | `$per_page`          | Yes           |  No                | No       | No             |
	 * | `$page`              | Yes           |  No                | No       | No             |
	 * | `$number`            | No            |  No                | Yes      | Yes            |
	 * | `$search`            | Yes           |  No                | No       | No             |
	 * | `$tag`               | Yes           |  No                | No       | No             |
	 * | `$author`            | Yes           |  No                | No       | No             |
	 * | `$user`              | Yes           |  No                | No       | No             |
	 * | `$browse`            | Yes           |  No                | No       | No             |
	 * | `$locale`            | Yes           |  Yes               | No       | No             |
	 * | `$installed_plugins` | Yes           |  No                | No       | No             |
	 * | `$is_ssl`            | Yes           |  Yes               | No       | No             |
	 * | `$fields`            | Yes           |  Yes               | No       | No             |
	 *
	 * @since 2.7.0
	 *
	 * @param string       $action API action to perform: 'query_plugins', 'plugin_information',
	 *                             'hot_tags' or 'hot_categories'.
	 * @param array|object $args   {
	 *     Optional. Array or object of arguments to serialize for the Plugin Info API.
	 *
	 *     @type string  $slug              The plugin slug. Default empty.
	 *     @type int     $per_page          Number of plugins per page. Default 24.
	 *     @type int     $page              Number of current page. Default 1.
	 *     @type int     $number            Number of tags or categories to be queried.
	 *     @type string  $search            A search term. Default empty.
	 *     @type string  $tag               Tag to filter plugins. Default empty.
	 *     @type string  $author            Username of an plugin author to filter plugins. Default empty.
	 *     @type string  $user              Username to query for their favorites. Default empty.
	 *     @type string  $browse            Browse view: 'popular', 'new', 'beta', 'recommended'.
	 *     @type string  $locale            Locale to provide context-sensitive results. Default is the value
	 *                                      of get_locale().
	 *     @type string  $installed_plugins Installed plugins to provide context-sensitive results.
	 *     @type bool    $is_ssl            Whether links should be returned with https or not. Default false.
	 *     @type array   $fields            {
	 *         Array of fields which should or should not be returned.
	 *
	 *         @type bool $short_description Whether to return the plugin short description. Default true.
	 *         @type bool $description       Whether to return the plugin full description. Default false.
	 *         @type bool $sections          Whether to return the plugin readme sections: description, installation,
	 *                                       FAQ, screenshots, other notes, and changelog. Default false.
	 *         @type bool $tested            Whether to return the 'Compatible up to' value. Default true.
	 *         @type bool $requires          Whether to return the required WordPress version. Default true.
	 *         @type bool $rating            Whether to return the rating in percent and total number of ratings.
	 *                                       Default true.
	 *         @type bool $ratings           Whether to return the number of rating for each star (1-5). Default true.
	 *         @type bool $downloaded        Whether to return the download count. Default true.
	 *         @type bool $downloadlink      Whether to return the download link for the package. Default true.
	 *         @type bool $last_updated      Whether to return the date of the last update. Default true.
	 *         @type bool $added             Whether to return the date when the plugin was added to the wordpress.org
	 *                                       repository. Default true.
	 *         @type bool $tags              Whether to return the assigned tags. Default true.
	 *         @type bool $compatibility     Whether to return the WordPress compatibility list. Default true.
	 *         @type bool $homepage          Whether to return the plugin homepage link. Default true.
	 *         @type bool $versions          Whether to return the list of all available versions. Default false.
	 *         @type bool $donate_link       Whether to return the donation link. Default true.
	 *         @type bool $reviews           Whether to return the plugin reviews. Default false.
	 *         @type bool $banners           Whether to return the banner images links. Default false.
	 *         @type bool $icons             Whether to return the icon links. Default false.
	 *         @type bool $active_installs   Whether to return the number of active installations. Default false.
	 *         @type bool $group             Whether to return the assigned group. Default false.
	 *         @type bool $contributors      Whether to return the list of contributors. Default false.
	 *     }
	 * }
	 * @return object|array|WP_Error Response object or array on success, WP_Error on failure. See the
	 *         {@link https://developer.wordpress.org/reference/functions/plugins_api/ function reference article}
	 *         for more information on the make-up of possible return values depending on the value of `$action`.
	 */
	function rox_wp_plugins_api( $action, $args = array() ) {
		
		if ( is_array( $args ) ) {
			$args = (object) $args;
		}
		
		if ( ! isset( $args->per_page ) ) {
			$args->per_page = 24;
		}
		
		if ( ! isset( $args->locale ) ) {
			$args->locale = get_user_locale();
		}
		
		/**
		 * Filters the WordPress.org Plugin Installation API arguments.
		 *
		 * Important: An object MUST be returned to this filter.
		 *
		 * @since 2.7.0
		 *
		 * @param object $args   Plugin API arguments.
		 * @param string $action The type of information being requested from the Plugin Installation API.
		 */
		$args = apply_filters( 'plugins_api_args', $args, $action );
		
		/**
		 * Filters the response for the current WordPress.org Plugin Installation API request.
		 *
		 * Passing a non-false value will effectively short-circuit the WordPress.org API request.
		 *
		 * If `$action` is 'query_plugins' or 'plugin_information', an object MUST be passed.
		 * If `$action` is 'hot_tags' or 'hot_categories', an array should be passed.
		 *
		 * @since 2.7.0
		 *
		 * @param false|object|array $result The result object or array. Default false.
		 * @param string             $action The type of information being requested from the Plugin Installation API.
		 * @param object             $args   Plugin API arguments.
		 */
		$res = apply_filters( 'plugins_api', false, $action, $args );
		
		if ( false === $res ) {
			global $wp_version;
			// include an unmodified $wp_version
			include( ABSPATH . WPINC . '/version.php' );
			
			$url = $http_url = 'http://api.wordpress.org/plugins/info/1.0/';
			if ( $ssl = wp_http_supports( array( 'ssl' ) ) )
				$url = set_url_scheme( $url, 'https' );
			
			$http_args = array(
				'timeout' => 15,
				'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
				'body' => array(
					'action' => $action,
					'request' => serialize( $args )
				)
			);
			$request = wp_remote_post( $url, $http_args );
			
			if ( $ssl && is_wp_error( $request ) ) {
				trigger_error(
					sprintf(
					/* translators: %s: support forums URL */
						__( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
						__( 'https://wordpress.org/support/' )
					) . ' ' . __( '(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)' ),
					headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE
				);
				$request = wp_remote_post( $http_url, $http_args );
			}
			
			if ( is_wp_error($request) ) {
				$res = new WP_Error( 'plugins_api_failed',
					sprintf(
					/* translators: %s: support forums URL */
						__( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
						__( 'https://wordpress.org/support/' )
					),
					$request->get_error_message()
				);
			} else {
				$res = maybe_unserialize( wp_remote_retrieve_body( $request ) );
				if ( ! is_object( $res ) && ! is_array( $res ) ) {
					$res = new WP_Error( 'plugins_api_failed',
						sprintf(
						/* translators: %s: support forums URL */
							__( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
							__( 'https://wordpress.org/support/' )
						),
						wp_remote_retrieve_body( $request )
					);
				}
			}
		} elseif ( !is_wp_error($res) ) {
			$res->external = true;
		}
		
		/**
		 * Filters the Plugin Installation API response results.
		 *
		 * @since 2.7.0
		 *
		 * @param object|WP_Error $res    Response object or WP_Error.
		 * @param string          $action The type of information being requested from the Plugin Installation API.
		 * @param object          $args   Plugin API arguments.
		 */
		return apply_filters( 'plugins_api_result', $res, $action, $args );
	}
}

if( ! function_exists( 'pluginrox_search_install_plugins' ) ) {
    add_action( 'wp_ajax_pluginrox_search_install_plugins', 'pluginrox_search_install_plugins' );
	/**
	 * Ajax handler for searching plugins to install.
	 *
	 * @since 4.6.0
	 */
	
	function pluginrox_search_install_plugins() {
		check_ajax_referer( 'updates' );
		
		$pagenow = isset( $_POST['pagenow'] ) ? sanitize_key( $_POST['pagenow'] ) : '';
		if ( 'plugin-install-network' === $pagenow || 'plugin-install' === $pagenow ) {
			set_current_screen( $pagenow );
		}
		
		/** @var WP_Plugin_Install_List_Table $wp_list_table */
		$wp_list_table = new RoxPluginTable( array(
			'screen' => get_current_screen(),
		) );
		
		$status = array();
		
		if ( ! $wp_list_table->ajax_user_can() ) {
			$status['errorMessage'] = __( 'Sorry, you are not allowed to manage plugins for this site.' );
			wp_send_json_error( $status );
		}
		
		// Set the correct requester, so pagination works.
		$_SERVER['REQUEST_URI'] = add_query_arg( array_diff_key( $_POST, array(
			'_ajax_nonce' => null,
			'action'      => null,
		) ), network_admin_url( 'plugin-install.php', 'relative' ) );
		
		$wp_list_table->prepare_items();
		
		ob_start();
		$wp_list_table->display();
		$status['count'] = (int) $wp_list_table->get_pagination_arg( 'total_items' );
		$status['items'] = ob_get_clean();
		
		wp_send_json_success( $status );
	}
}