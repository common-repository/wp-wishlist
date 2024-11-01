<?php
if( ! function_exists( 'add_action' ) ) {
	die();
}
class RoxWPWLTemplateLoader {
	// rox_wpwl_get_template();
	// rox_wpwl_get_template_content();
	/**
	 * Store the shop page ID.
	 *
	 * @var integer
	 */
	private static $wishlist_page_id = 0;
	
	/**
	 * Store whether we're processing a product inside the_content filter.
	 *
	 * @var boolean
	 */
	private static $in_content_filter = false;
	
	/**
	 * Is theme support defined?
	 *
	 * @var boolean
	 */
	private static $theme_support = false;
	
	/**
	 * Hook in methods.
	 */
	public static function init() {
		self::$theme_support = current_theme_supports( 'wp-wishlist' );
		self::$wishlist_page_id  = (int) rox_wpwl_get_option( 'wishlist_archive' );
		
		// Supported themes.
		if ( self::$theme_support ) {
			add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
			add_filter( 'comments_template', array( __CLASS__, 'comments_template_loader' ) );
		} else {
			// Unsupported themes.
			add_action( 'template_redirect', array( __CLASS__, 'unsupported_theme_init' ) );
		}
	}
	
	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. woocommerce looks for theme.
	 * overrides in /theme/woocommerce/ by default.
	 *
	 * For beginners, it also looks for a woocommerce.php template first. If the user adds.
	 * this to the theme (containing a woocommerce() inside) this will be used for all.
	 * woocommerce templates.
	 *
	 * @param string $template Template to load.
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}
		
		$default_file = self::get_template_loader_default_file();
		
		if ( $default_file ) {
			/**
			 * Filter hook to choose which files to find before WooCommerce does it's own logic.
			 *
			 * @since 3.0.0
			 * @var array
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );
			
			if ( ! $template || ROX_WPWL_TEMPLATE_DEBUG ) {
				$template = RoxWPWL()->plugin_path() . '/templates/' . $default_file;
			}
		}
		
		return $template;
	}
	
	/**
	 * Get the default filename for a template.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	private static function get_template_loader_default_file() {
		if ( is_singular( RoxWPWLPostTypes::$config['post-type'] ) ) {
			$default_file = 'single-wishlist.php';
		} elseif ( is_wishlist_taxonomy() ) {
			$object = get_queried_object();
			
			if ( is_tax( RoxWPWLPostTypes::$config['tax-cat'] ) || is_tax( RoxWPWLPostTypes::$config['tax-tag'] ) ) {
				$default_file = 'taxonomy-' . $object->taxonomy . '.php';
			} else {
				$default_file = 'archive-wishlist.php';
			}
		} elseif ( is_post_type_archive( RoxWPWLPostTypes::$config['post-type'] ) || is_page( self::$wishlist_page_id ) ) {
			$default_file = self::$theme_support ? 'archive-wishlist.php' : '';
		} else {
			$default_file = '';
		}
		return $default_file;
	}
	
	/**
	 * Get an array of filenames to search for a given template.
	 *
	 * @since  3.0.0
	 * @param  string $default_file The default file name.
	 * @return string[]
	 */
	private static function get_template_loader_files( $default_file ) {
		$templates   = apply_filters( 'rox_wpwl_template_loader_files', array(), $default_file );
		$templates[] = 'wishlist.php';
		
		if ( is_page_template() ) {
			$templates[] = get_page_template_slug();
		}
		
		if ( is_singular( RoxWPWLPostTypes::$config['post-type'] ) ) {
			$object       = get_queried_object();
			$name_decoded = urldecode( $object->post_name );
			if ( $name_decoded !== $object->post_name ) {
				$templates[] = "single-wishlist-{$name_decoded}.php";
			}
			$templates[] = "single-wishlist-{$object->post_name}.php";
		}
		if ( is_wishlist_taxonomy() ) {
			$object      = get_queried_object();
			$templates[] = 'taxonomy-' . $object->taxonomy . '-' . $object->slug . '.php';
			$templates[] = RoxWPWL()->__template_path() . '/taxonomy-' . $object->taxonomy . '-' . $object->slug . '.php';
			$templates[] = 'taxonomy-' . $object->taxonomy . '.php';
			$templates[] = RoxWPWL()->__template_path() . '/taxonomy-' . $object->taxonomy . '.php';
		}
		
		$templates[] = $default_file;
		$templates[] = RoxWPWL()->__template_path() . '/' . $default_file;
		
		return array_unique( $templates );
	}
	
	/**
	 * Load comments template.
	 *
	 * @param string $template template to load.
	 * @return string
	 */
	public static function comments_template_loader( $template ) {
		if ( get_post_type() !== RoxWPWLPostTypes::$config['post-type'] ) {
			return $template;
		}
		
		$check_dirs = array(
			trailingslashit( get_stylesheet_directory() ) . RoxWPWL()->__template_path() . '/',
			trailingslashit( get_template_directory() ) . RoxWPWL()->__template_path() . '/',
			trailingslashit( get_stylesheet_directory() ),
			trailingslashit( get_template_directory() ),
			trailingslashit( RoxWPWL()->plugin_path() ) . '/templates/',
		);
		
		if ( ROX_WPWL_TEMPLATE_DEBUG ) {
			$check_dirs = array( array_pop( $check_dirs ) );
		}
		
		foreach ( $check_dirs as $dir ) {
			if ( file_exists( trailingslashit( $dir ) . 'single-wishlist-comments.php' ) ) {
				return trailingslashit( $dir ) . 'single-wishlist-comments.php';
			}
		}
	}
	
	/**
	 * Unsupported theme compatibility methods.
	 */
	
	/**
	 * Hook in methods to enhance the unsupported theme experience on pages.
	 *
	 * @since 3.3.0
	 */
	public static function unsupported_theme_init() {
		if ( 0 < self::$wishlist_page_id ) {
			if ( is_wishlist_taxonomy() ) {
				self::unsupported_theme_tax_archive_init();
			} elseif ( is_wishlist() ) {
				self::unsupported_theme_wishlist_page_init();
			} else {
				self::unsupported_theme_wishlist_archive_page_init();
			}
		}
	}
	
	/**
	 * Hook in methods to enhance the unsupported theme experience on the Shop page.
	 *
	 * @since 3.3.0
	 */
	private static function unsupported_theme_wishlist_archive_page_init() {
		add_filter( 'the_title', array( __CLASS__, 'unsupported_theme_title_filter' ), 10, 2 );
		add_filter( 'private_title_format', array( __CLASS__, 'single_private_wishlist_title' ), 10, 2 );
		add_filter( 'the_content', array( __CLASS__, 'unsupported_theme_wishlsit_archive_content_filter' ), 10 );
		// add_filter( 'comments_number', array( __CLASS__, 'unsupported_theme_comments_number_filter' ) );
	}
	
	/**
	 * Hook in methods to enhance the unsupported theme experience on single wishlist pages.
	 *
	 * @since 3.3.0
	 */
	private static function unsupported_theme_wishlist_page_init() {
		// add_filter( 'the_title', array( __CLASS__, 'unsupported_theme_title_filter' ), 10, 2 );
		add_filter( 'private_title_format', array( __CLASS__, 'single_private_wishlist_title' ), 10, 2 );
		add_filter( 'the_content', array( __CLASS__, 'unsupported_theme_wishlist_content_filter' ), 10 );
		add_filter( 'post_thumbnail_html', array( __CLASS__, 'unsupported_theme_single_featured_image_filter' ) );
//		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
//		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	}
	
	/**
	 * Enhance the unsupported theme experience on Product Category and Attribute pages by rendering
	 * those pages using the single template and shortcode-based content. To do this we make a dummy
	 * post and set a shortcode as the post content. This approach is adapted from bbPress.
	 *
	 * @since 3.3.0
	 */
	private static function unsupported_theme_tax_archive_init() {
		global $wp_query, $post;
		
		$queried_object = get_queried_object();
		$args           = self::get_current_wishlist_page_view_args();
		$shortcode_args = array(
			'page'     => $args->page,
			'columns'  => $args->columns,
			'rows'     => $args->rows,
			'orderby'  => '',
			'order'    => '',
			'paginate' => true,
			'cache'    => false,
		);
		
//		if ( is_wishlist_category() ) {
//			$shortcode_args['category'] = sanitize_title( $queried_object->slug );
//		} elseif ( is_wishlist_tag() ) {
//			$shortcode_args['tag'] = sanitize_title( $queried_object->slug );
//		} else {
//			// Default theme archive for all other taxonomies.
//			return;
//		}
		
		// Description handling.
		if ( ! empty( $queried_object->description ) && ( empty( $_GET['wishlist-page'] ) || 1 === absint( $_GET['wishlist-page'] ) ) ) {
			$prefix = '<div class="wishlist-term-description">' . rox_wpwl_get_excerpt( $queried_object->description ) . '</div>';
		} else {
			$prefix = '';
		}
		// $shortcode = new WC_Shortcode_Products( $shortcode_args );

		$shortcode = new RoxWPWLQueryList( array(
			'page'     => $args->page,
			'tax_query' => array(
				array(
					'taxonomy' => $queried_object->taxonomy,
					'field' => 'slug',
					'terms' => $queried_object->slug,
				),
			),
		), 'taxonomy-archive' );
		$shop_page = get_post( self::$wishlist_page_id );
		$dummy_post_properties = array(
			'ID'                    => 0,
			'post_status'           => 'publish',
			'post_author'           => $shop_page->post_author,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => $shop_page->post_date,
			'post_date_gmt'         => $shop_page->post_date_gmt,
			'post_modified'         => $shop_page->post_modified,
			'post_modified_gmt'     => $shop_page->post_modified_gmt,
			'post_content'          => $prefix . $shortcode->get_content(),
			'post_title'            => rox_wpwl_clean( $queried_object->name ),
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => $queried_object->slug,
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',
		);
		// Set the $post global.
		$post = new WP_Post( (object) $dummy_post_properties );
		// Copy the new post global into the main $wp_query.
		$wp_query->post  = $post;
		$wp_query->posts = array( $post );
		
		// Prevent comments form from appearing.
		$wp_query->post_count    = 1;
		$wp_query->is_404        = false;
		$wp_query->is_page       = true;
		$wp_query->is_single     = true;
		$wp_query->is_archive    = false;
		$wp_query->is_tax        = true;
		$wp_query->max_num_pages = 0;
		
		// Prepare everything for rendering.
		setup_postdata( $post );
		remove_all_filters( 'the_content' );
		remove_all_filters( 'the_excerpt' );
		add_filter( 'template_include', array( __CLASS__, 'force_single_template_filter' ) );
	}
	
	/**
	 * Force the loading of one of the single templates instead of whatever template was about to be loaded.
	 *
	 * @param string $template Path to template.
	 * @return string
	 */
	public static function force_single_template_filter( $template ) {
		$possible_templates = array(
			'page',
			'single',
			'singular',
			'index',
		);
		
		foreach ( $possible_templates as $possible_template ) {
			$path = get_query_template( $possible_template );
			if ( $path ) {
				return $path;
			}
		}
		
		return $template;
	}
	
	/**
	 * Get information about the current shop page view.
	 *
	 * @since 3.3.0
	 * @return array
	 */
	private static function get_current_wishlist_page_view_args() {
		$page = 1;
		if( self::$theme_support ) {
			if( get_query_var( 'page' ) ) {
				$page = get_query_var( 'page' );
			} else if( get_query_var( 'paged' ) ) {
				$page = get_query_var( 'paged' );
			}
		} else {
			if( isset( $_GET['wishlist-page'] ) ) $page = $_GET['wishlist-page'];
		}
		return (object) array(
			'page'    => absint( max( 1, absint( $page ) ) ),
		);
	}
	
	/**
	 * Filter the title and insert Wishlist content on the Wishlist Archive|Single Page.
	 *
	 * @param string $title Existing title.
	 * @param int    $id ID of the post being filtered.
	 * @return string
	 */
	public static function unsupported_theme_title_filter( $title, $id ) {
		$post = get_post( $id );
		if ( self::$theme_support || ! $post->ID !== self::$wishlist_page_id || ! $post->post_type !== RoxWPWLPostTypes::$config['post-type'] ) {
			return $title;
		}
		
		if ( is_page( self::$wishlist_page_id ) || ( is_home() && 'page' === get_option( 'show_on_front' ) && absint( get_option( 'page_on_front' ) ) === self::$wishlist_page_id ) ) {
			$args         = self::get_current_wishlist_page_view_args();
			$title_suffix = array();
			
			if ( $args->page > 1 ) {
				/* translators: %d: Page number. */
				$title_suffix[] = sprintf( esc_html__( 'Page %d', 'wordpress-wishlist' ), $args->page );
			}
			
			if ( $title_suffix ) {
				$title = $title . ' &ndash; ' . implode( ', ', $title_suffix );
			}
		}
		return $title;
	}
	
	public static function single_private_wishlist_title( $format, $post ) {
		if( $post->post_type == RoxWPWLPostTypes::$config['post-type'] ) return '%s';
		return $format;
	}
	
	/**
	 * Filter the content and insert wishlist content on the archive page.
	 *
	 * For non-supported themes, this will setup the main archive page to be shortcode based to improve default appearance.
	 *
	 * @since 3.3.0
	 * @param string $content Existing post content.
	 * @return string
	 */
	public static function unsupported_theme_wishlsit_archive_content_filter( $content ) {
		global $wp_query;
		$page = 1;
		if( self::$theme_support ) {
			if( get_query_var( 'page' ) ) {
				$page = abs( get_query_var( 'page' ) );
			}
		} else {
			if( isset( $_GET['wishlist-page'] ) ) {
				$page = abs( $_GET['wishlist-page'] );
			}
		}
		if ( self::$theme_support || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}
		self::$in_content_filter = true;
		
		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( __CLASS__, 'unsupported_theme_wishlsit_archive_content_filter' ) );
		// Unsupported theme wishlist archive.
		if ( is_page( self::$wishlist_page_id ) ) {
			$args      = self::get_current_wishlist_page_view_args();
			$shortcode = new RoxWPWLQueryList( array(
				'paged'     => $args->page,
			), 'archive' );
			$content = $content . PHP_EOL . $shortcode->get_content();
		}
		self::$in_content_filter = false;
		
		return $content;
	}
	
	/**
	 * Filter the content and insert wishlist content on the archive page.
	 *
	 * For non-supported themes, this will setup the main wishlist page to be shortcode based to improve default appearance.
	 *
	 * @since 3.3.0
	 * @param string $content Existing post content.
	 * @return string
	 */
	public static function unsupported_theme_wishlist_content_filter( $content ) {
		global $wp_query;
		if ( self::$theme_support || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}
		
		self::$in_content_filter = true;
		
		
		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( __CLASS__, 'unsupported_theme_wishlist_content_filter' ) );
		
		if ( is_wishlist() ) {
			$content = do_shortcode( '[rox_single_wishlist_page status="any" id="' . get_the_ID() . '"]' );
		}
		
		self::$in_content_filter = false;
		
		return $content;
	}
	
	/**
	 * Suppress the comments number on the Shop page for unsupported themes since there is no commenting on the Shop page.
	 *
	 * @param string $comments_number The comments number text.
	 * @return string
	 */
//	public static function unsupported_theme_comments_number_filter( $comments_number ) {
//		if ( is_page( self::$wishlist_page_id ) ) {
//			return '';
//		}
//
//		return $comments_number;
//	}
	
	/**
	 * Are we filtering content for unsupported themes?
	 *
	 * @since 3.3.2
	 * @return bool
	 */
	public static function in_content_filter() {
		return (bool) self::$in_content_filter;
	}
	
	/**
	 * Prevent the main featured image on product pages because there will be another featured image
	 * in the gallery.
	 *
	 * @since 3.3.0
	 * @param string $html Img element HTML.
	 * @return string
	 */
	public static function unsupported_theme_single_featured_image_filter( $html ) {
		if ( self::in_content_filter() || ! is_wishlist() || ! is_main_query() ) {
			return $html;
		}
		return '';
	}
	
	/**
	 * Remove the Review tab and just use the regular comment form.
	 *
	 * @param array $tabs Tab info.
	 * @return array
	 */
	public static function unsupported_theme_remove_review_tab( $tabs ) {
		unset( $tabs['reviews'] );
		return $tabs;
	}
}

add_action( 'init', array( 'RoxWPWLTemplateLoader', 'init' ) );