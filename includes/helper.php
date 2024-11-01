<?php
/**
 * Helper Functions
 * @version 1.0.0
 * @since ROXWPWL 1.0.0
 *
 */
if( ! function_exists( 'add_action' ) ) {
	die();
}
use Hashids\Hashids;
use Hashids\HashidsException;
if( ! function_exists( 'trim_slashes' ) ) {
	/**
	 * Trim begining and ending slashes from string
	 * @param string $string
	 * @return string
	 */
	function trim_slashes ( $string )  {
		$string = ltrim( $string, '/' );
		$string = rtrim( $string, '/' );
		return $string;
	}
}
if( ! function_exists( 'rox_is_woocommerce_activated' ) ) {
	/**
	 * Check WooCommerce activation
	 * @return bool
	 */
	function rox_is_woocommerce_activated() {
		return class_exists( 'WooCommerce', false ) ? true : false;
	}
}
/**
 * Get WC Product Stock Information HTML
 * @param WC_Product $product
 *
 * @return string|void
 */
function rox_wpwl_get_product_stock_status( $product ) {
	if( ! ( $product instanceof WC_Product ) ) return;
	add_filter( 'woocommerce_get_availability', 'rox_wpwl_set_product_stock_text', 1, 2);
	return wc_get_stock_html( $product );
}
/**
 * Get Product Ratign markup
 * @param WC_Product $product		current product id
 * @return string|void
 */
function rox_wpwl_product_rating_html( $product ) {
	if( $product instanceof WC_Product ) {
		$ratingHtml = wc_get_star_rating_html( $product->get_average_rating(), $product->get_rating_count() );
		return apply_filters( 'wishlist_item_product_rating', sprintf( '<div class="wishlist-item-rating"><div class="star-rating">%s</div></div>', $ratingHtml ), $product->get_average_rating(), $product->get_rating_count() );
	}
	return;
}
if( ! function_exists( 'wishlist_get_woocommerce_add_to_cart_button' ) ) {
	/**
	 * get woocommerce product add to cart button
	 * @return null|false|string
	 */
	function wishlist_get_woocommerce_add_to_cart_button( $the_product = false ) {
		if( ! rox_is_woocommerce_activated() ) return;
		global $product;
		$_product = $product;
		$product = wc_get_product( $the_product );
		add_filter( 'woocommerce_product_add_to_cart_url', 'rox_wpwl_wc_add_to_cart_ajax_url_fix', 10, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_args', 'rox_wpwl_wc_add_to_cart_btn_args', 10, 2 );
		ob_start();
		woocommerce_template_loop_add_to_cart();
		$product = $_product;
		return ob_get_clean();
	}
	function rox_wpwl_wc_add_to_cart_ajax_url_fix( $url, \WC_Product $product ) {
		remove_filter( 'rox_wpwl_wc_add_to_cart_ajax_url_fix', __FUNCTION__, 10 );
		if( $product->is_type( 'simple' ) ) {
			if( strpos( $url, 'ajax' ) !== FALSE ) {
				$url = $product->is_purchasable() && $product->is_in_stock() ? add_query_arg( array( 'add-to-cart' => $product->get_id() ), get_permalink( $product->get_id() ) ) : get_permalink( $product->get_id() );
			}
		}
		return $url;
	}
	function rox_wpwl_wc_add_to_cart_btn_args( $args, \WC_Product $product ) {
		remove_filter( 'rox_wpwl_wc_add_to_cart_btn_args', __FUNCTION__, 10 );
		$args['class'] .= ' rox-wcms-btn';
		$args['class'] = trim( $args['class'] );
		return $args;
	}
}

/**
 * Get permalink settings for wishlist.
 *
 * @since  1.0.0
 * @return array
 */
function rox_wpwl_get_permalink_structure() {
	$saved_permalinks = (array) get_option( 'rox_wp_wishlist_permalinks', array() );
	$permalinks       = wp_parse_args(
		array_filter( $saved_permalinks ), array(
			'wishlist_base'           => _x( 'wishlist', 'slug', 'wordpress-wishlist' ),
			'category_base'          => _x( 'wishlist-category', 'slug', 'wordpress-wishlist' ),
			'tag_base'               => _x( 'wishlist-tag', 'slug', 'wordpress-wishlist' ),
			'use_verbose_page_rules' => false,
		)
	);
	
	if ( $saved_permalinks !== $permalinks ) {
		update_option( 'rox_wp_wishlist_permalinks', $permalinks );
	}
	
	$permalinks['wishlist_rewrite_slug']  = untrailingslashit( $permalinks['wishlist_base'] );
	$permalinks['category_rewrite_slug']  = untrailingslashit( $permalinks['category_base'] );
	$permalinks['tag_rewrite_slug']       = untrailingslashit( $permalinks['tag_base'] );
	
	return $permalinks;
}
/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function rox_wpwl_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'wc_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}
/**
 * Sanitize permalink values before insertion into DB.
 *
 * @see wc_sanitize_permalink()
 * @param  string $value Permalink.
 * @return string
 */
function rox_wpwl_sanitize_permalink( $value ) {
	global $wpdb;
	
	$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
	
	if ( is_wp_error( $value ) ) {
		$value = '';
	}
	
	$value = esc_url_raw( trim( $value ) );
	$value = str_replace( 'http://', '', $value );
	return untrailingslashit( $value );
}
if( ! function_exists( 'rox_get_page_children' ) ) {
	/**
	 * Recursively get page children.
	 *
	 * @param  int $page_id Page ID.
	 * @return int[]
	 */
	function rox_get_page_children( $page_id ) {
		$page_ids = get_posts(
			array(
				'post_parent' => $page_id,
				'post_type'   => 'page',
				'numberposts' => -1, 
				'post_status' => 'any',
				'fields'      => 'ids',
			)
		);
		
		if ( ! empty( $page_ids ) ) {
			foreach ( $page_ids as $page_id ) {
				$page_ids = array_merge( $page_ids, wc_get_page_children( $page_id ) );
			}
		}
		
		return $page_ids;
	}
}
if( ! function_exists( 'flush_rewrite_rules_wishlist_page_save' ) ) {
	/**
	 * Flushes rewrite rules when the shop page (or it's children) gets saved.
	 * @return void
	 */
	function flush_rewrite_rules_wishlist_page_save() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		if ( 'page' !== $screen_id || empty( $_GET['post'] ) || empty( $_GET['action'] ) || ( isset( $_GET['action'] ) && 'edit' !== $_GET['action'] ) ) {
			return;
		}
		$post_id      = intval( $_GET['post'] );
		$whislsit_page_id = (int) rox_wpwl_get_option( 'wishlist_archive' );
		if ( $whislsit_page_id === $post_id || in_array( $post_id, wc_get_page_children( $whislsit_page_id ), true ) ) {
			do_action( 'rox_wpwl_flush_rewrite_rules' );
		}
	}
	add_action( 'admin_footer', 'flush_rewrite_rules_wishlist_page_save' );
}
function custom_rewrite_tag() {
	add_rewrite_tag('%wishlist_cat%', '([^&]+)');
}
add_action('init', 'custom_rewrite_tag', 10, 0);
/**
 * Various rewrite rule fixes.
 * @since 1.0.0
 * @param array $rules Rules.
 * @return array
 */
function rox_wpwl_fix_rewrite_rules( $rules ) {
	global $wp_rewrite;
	$permalinks = rox_wpwl_get_permalink_structure();
	if ( preg_match( '`/(.+)(/%wishlist_cat%)`', $permalinks['wishlist_rewrite_slug'], $matches ) ) {
		foreach ( $rules as $rule => $rewrite ) {
			if ( preg_match( '`^' . preg_quote( $matches[1], '`' ) . '/\(`', $rule ) && preg_match( '/^(index\.php\?'.RoxWPWLPostTypes::$config['tax-cat'].')(?!(.*'.RoxWPWLPostTypes::$config['post-type'].'))/', $rewrite ) ) {
				unset( $rules[ $rule ] );
			}
		}
	}
	
	if ( ! $permalinks['use_verbose_page_rules'] ) {
		return $rules;
	}
	$whislsit_page_id = (int) rox_wpwl_get_option( 'wishlist_archive' );
	if ( $whislsit_page_id ) {
		$page_rules = array();
		$subpages           = rox_get_page_children( $whislsit_page_id );
		// Subpage rules.
		foreach ( $subpages as $subpage ) {
			$uri                                = get_page_uri( $subpage );
			$page_rules[ $uri . '/?$' ] = 'index.php?pagename=' . $uri;
			$wp_generated_rules         = $wp_rewrite->generate_rewrite_rules( $uri, EP_PAGES, true, true, false, false );
			foreach ( $wp_generated_rules as $key => $value ) {
				$wp_generated_rules[ $key ] = $value . '&pagename=' . $uri;
			}
			$page_rules = array_merge( $page_rules, $wp_generated_rules );
		}
		$rules = array_merge( $page_rules, $rules ); // Merge All Rules
	}
	return $rules;
}
add_filter( 'rewrite_rules_array', 'rox_wpwl_fix_rewrite_rules' );

/**
 * Get Wishlist Status
 *
 * @param WP_Post $post
 * @param bool $translated
 *
 * @return mixed|string|void
 */
function rox_get_wishlist_status( $post = NULL, $translated = false ) {
	$post = get_post( $post );
	
	$status = 'public';
	if( $post->post_status == 'private' ) {
		$status = 'private';
		global $wpdb;
		$table = RoxWPWL()->get_tables( 'contributor' );
		$count = $wpdb->get_row( $wpdb->prepare( " SELECT COUNT( ID ) as total FROM {$table} WHERE `post_id` = %d", $post->ID ) );
		if( $count && $count->total > 0 ) $status = 'shared';
	}
	$status = apply_filters( 'rox_wpwl_post_status', $status, $post->post_status );
	if( $translated ) {
		$status = apply_filters( 'rox_wpwl_post_status_translated', rox_get_translate_post_status( $status ), $status );
	}
	return $status;
}

/**
 *
 * Clean svg header (xml header and doctype)
 *
 * @param $svg
 * @return string
 */
function rox_wpwl_clean_svg( $svg ) {
	$svg = preg_replace( '/<\?xml.+\?>\n/ms', '', $svg );
	$svg = preg_replace( '/<!DOCTYPE.+>.+<svg/ms', PHP_EOL . '<svg', $svg );
	$svg = preg_replace( '/\s+/ms', ' ', $svg );
	/* $svg = str_replace( "<?xml version=\"1.0\" ?>\n", '', $svg );*/
	return $svg;
}

/**
 * Get Svg icon by file name
 *
 * @param $file_name
 * @param bool $content         Optional. default true, get the content of svg file or the url
 * @param bool $clean           Optional. default false, clean svg header (xml header and doctype)
 * @param bool $base64          Optional. default false, base64 encode svg content.
 *
 * @return false|string
 */
function rox_wpwl_get_svg( $file_name, $content = true, $clean = true, $base64 = false ) {
	$file = ROXWPWL()->plugin_path( '/assets/images/'.$file_name.'.svg' );
	$file = file_exists( $file )? $file : false;
	if( ! $content ) {
		if( $file ) return ROXWPWL()->plugin_url( '/assets/images/' . $file_name . '.svg' );
		return false;
	}
	if( $file ) $file = file_get_contents( $file );
	if( $clean ) $file = rox_wpwl_clean_svg( $file );
	if( ! $base64 ) return $file;
	return base64_encode( $file );
}

/**
 * Get wishlist status icon svg
 * @param WP_Post $list
 * @return string
 */
function rox_get_wishlist_icon( $list = null, $icon_wrapper = true ) {
	$list_status = rox_get_wishlist_status( $list );
	if( empty( $list_status ) ) return '';
	if( in_array( $list_status, array( 'private', 'shared', 'public' ) ) ) $file = 'list-' . $list_status;
	else $file = 'list-private';
	$list_icon = apply_filters( 'wishlist_status_icon', rox_wpwl_get_svg( $file ), $list_status );
	if( $list_icon && $icon_wrapper ) {
		$list_icon = sprintf( ' <span class="rox-wpwl-icon icon-%s" aria-label="%s">%s</span>', $list_status, rox_get_wishlist_status( $list, true ), $list_icon );
	}
	return $list_icon;
}


/**
 * Get Wishlist Item Database ID by object id, list id and object type
 *
 * @param int $object_ID
 * @param int $list_ID
 * @param string $item_type
 *
 * @return int|bool
 */
function rox_wpwl_get_object_item_ID( $object_ID, $list_ID, $item_type ) {
	global $wpdb;
	$table = RoxWPWL()->get_tables( 'item' );
	$item = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM {$table} WHERE post_id = %d AND object_id = %d AND item_type = %s", $list_ID, $object_ID, $item_type ) );
	if( $item && isset( $item->ID ) && $item->ID > 0 ) return (int) $item->ID;
	return false;
}

/**
 * Check if object (WP Post/Taxonomy) already exists in wishlist
 *
 * @param int $object_ID
 * @param int $list_ID
 * @param string $item_type
 *
 * @return bool
 */
function is_object_in_wishlist( $object_ID, $list_ID, $item_type ) {
	return rox_wpwl_get_object_item_ID( $object_ID, $list_ID, $item_type ) == ! false;
}

/**
 * Check if object (WP Post/Taxonomy) already exists in wishlist
 *
 * @param int $object_ID
 * @param string $object_Type
 * @param int $list_ID
 *
 * @return bool
 */
function is_object_in_users_wishlist( $object_ID, $object_Type, $user_ID = NULL ) {
	global $wpdb;
	if( empty( $user_ID ) ) $user_ID = get_current_user_id();
	if( get_user_by( 'ID', $user_ID ) ) {
		$lists = rox_wpwl_get_users_wishlists( $user_ID, 'any' );
		if( count( $lists ) ) $lists = implode( ',', $lists );
		else return false;
		$table = RoxWPWL()->get_tables( 'item' );
		$count = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(ID) AS total FROM {$table} WHERE post_id IN({$lists}) AND `object_id` = %d AND `item_type` = %s AND `deleted` = 0", $object_ID, $object_Type ) );
		if( $count && $count->total > 0 ) return true;
	}
	return false;
}

/**
 * Get Translated Post status (Post Status Label)
 * @param string $status
 * @return string|null
 */
function rox_get_translate_post_status( $status ) {
	$post_status = get_post_status_object( $status );
	if( $post_status !== NULL && is_object( $post_status ) && isset( $post_status->label ) ) return $post_status->label;
	else if( $status == 'public' ) return esc_html__( 'Public', 'wordpress-wishlist' );
	else if( $status == 'shared' ) return esc_html__( 'Shared', 'wordpress-wishlist' );
	else return NULL;
}

function rox_wpwl_get_item_delete_link( $item_ID ) {
	$item = Rox_List_Item::get_instance( $item_ID );
	if( $item ) {
		$nonce_action = 'delete_list_item_' . $item->ID .'_'. get_current_user_id();
		$url = add_query_arg( array( 'action' => '_delete_list_item', 'list' => $item->post_id, 'item' => $item->ID ), admin_url( 'admin-post.php' ) );
		return wp_nonce_url( $url, $nonce_action, '_nonce' );
	}
	return '';
}
function rox_wpwl_get_list_delete_link( $post = NULL ) {
	$post = get_post( $post );
	if( $post ) {
		$nonce_action = 'delete_list_' . $post->ID .'_'. get_current_user_id();
		$url = add_query_arg( array( 'action' => '_delete_list', 'list' => $post->ID ), admin_url( 'admin-post.php' ) );
		return wp_nonce_url( $url, $nonce_action, '_nonce' );
	}
	return '';
}
function rox_wpwl_get_list_duplicate_link( $post = NULL ) {
	$post = get_post( $post );
	if( $post ) {
		$nonce_action = 'duplicate_list_' . $post->ID .'_'. get_current_user_id();
		$url = add_query_arg( array( 'action' => '_duplicate_list', 'ID' => $post->ID ), admin_url( 'admin-post.php' ) );
		return wp_nonce_url( $url, $nonce_action, '_nonce' );
	}
	return '';
}
add_action( 'admin_post__delete_list', 'rox_wpwl_frontend_list_actions', 10 );
add_action( 'admin_post__delete_list_item', 'rox_wpwl_frontend_list_actions', 10 );
add_action( 'admin_post__duplicate_list', 'rox_wpwl_frontend_list_actions', 10 );

function rox_wpwl_frontend_list_actions() {
	$redirect = rox_wpwl_get_option( 'wishlist_archive' )? get_permalink( rox_wpwl_get_option( 'wishlist_archive' ) ) : get_home_url();
//	if( ! is_user_logged_in() ) wp_redirect( $redirect );
	if( isset( $_GET['list'], $_GET['_nonce'] ) ) {
		$list = absint( $_GET['list'] );
		$user_ID = get_current_user_id();
		$post_type = RoxWPWLPostTypes::$config['post-type'];
		$message = '';
		switch ( $_GET['action'] ) {
			case '_delete_list':
				$list = get_post( $list );
				$nonce_action = 'delete_list_' . $list->ID .'_'. $user_ID;
				if( wp_verify_nonce( $_GET['_nonce'], $nonce_action ) ) {
					$deleted = rox_wpwl_delete_list( $list, $user_ID );
					if( $deleted ) {
						$message = rox_format_alert( __( 'List deleted.', 'wordpress-wishlist' ), 'info' );
					} else {
						$message = rox_format_alert( __( 'Unable to delete the list', 'wordpress-wishlist' ), 'info' );
					}
				} else {
					$message = rox_format_alert( __( 'Invalid request.', 'wordpress-wishlist' ), 'error' );
				}
				break;
			case '_duplicate_list':
				$list = get_post( $list );
				$nonce_action = 'duplicate_list_' . $list->ID .'_'. $user_ID;
				if( wp_verify_nonce( $_GET['_nonce'], $nonce_action ) ) {
					// $redirect = get_permalink( 'duplicate_post_id' );
				} else {
					$message = rox_format_alert( __( 'Invalid request.', 'wordpress-wishlist' ), 'error' );
				}
				break;
			case '_delete_list_item':
				$list = get_post( $list );
				$item = absint( $_GET['item'] );
				$item = Rox_List_Item::get_instance( $item );
				$nonce_action = 'delete_list_item_' . $item->ID .'_'. $user_ID;
				if( wp_verify_nonce( $_GET['_nonce'], $nonce_action ) ) {
					$redirect = get_permalink( $item->post_id );
					if( $list->post_author == $user_ID && $list->post_type == $post_type && $item->post_id == $list->ID ) {
						$deleted = rox_wpwl_delete_item( $item->ID );
						if( ! $deleted ) {
							$message = rox_format_alert( __( 'Unable to delete item.', 'wordpress-wishlist' ), 'error' );
						} else {
							$message = rox_format_alert( __( 'Item deleted.', 'wordpress-wishlist' ), 'info' );
						}
					}
				} else {
					$message = rox_format_alert( __( 'Invalid request.', 'wordpress-wishlist' ), 'error' );
				}
				break;
			default:
				break;
		}
	}
	if( ! empty( $message ) ) {
		rox_wpwl_add_frontend_messages( $message );
	}
	wp_redirect( $redirect );
	exit();
}

/**
 * Add Messages/Alert/Info for showing in frontend
 * use formated message or array of messages
 * formate message using rox_format_alert()
 * @see rox_format_alert()
 *
 * @param string|array  $message
 * @return void
 */
function rox_wpwl_add_frontend_messages( $message ) {
	$messages = get_option( 'rox-wishlist-frontend-messages', array() );
	$messages = wp_parse_args( (array)$message, $messages );
	update_option( 'rox-wishlist-frontend-messages', $messages, false );
}

/**
 * Remove all frontend messages
 * @return void
 */
function rox_wpwl_clean_frontend_messages() {
	delete_option( 'rox-wishlist-frontend-messages' );
}

/**
 * Display and clean Messages
 * @return void
 */
function rox_wpwl_print_forntend_messages() {
	$messages = get_option( 'rox-wishlist-frontend-messages', array() );
	rox_wpwl_clean_frontend_messages();
	if( ! empty( $messages ) ) {
		foreach( $messages as $message ) {
			echo $message;
		}
	}
}

/**
 * Prevent Wishlist attachment links from breaking when using complex rewrite structures.
 *
 * @param  string $link
 * @param  int $post_id
 * @return string
 */
function rox_wpwl_attachment_link( $link, $post_id ) {
	$parent_type = get_post_type( wp_get_post_parent_id( $post_id ) );
	if ( RoxWPWLPostTypes::$config['post-type'] === $parent_type ) {
		$link = home_url( '/?attachment_id=' . $post_id );
	}
	return $link;
}
add_filter( 'attachment_link', 'rox_wpwl_attachment_link', 10, 2 );

/**
 * Filter to allow wishlist category in the permalinks for products.
 *
 * @param  string  $permalink The existing permalink URL.
 * @param  WP_Post $post WP_Post object.
 * @return string
 */
function rox_wpwl_post_type_link( $permalink, $post ) {
	// Abort if post is not a product.
	if ( RoxWPWLPostTypes::$config['post-type'] !== $post->post_type ) {
		return $permalink;
	}
	
	// Abort early if the placeholder rewrite tag isn't in the generated URL.
	if ( false === strpos( $permalink, '%' ) ) {
		return $permalink;
	}
	
	// Get the custom taxonomy terms in use by this post.
	$terms = get_the_terms( $post->ID, RoxWPWLPostTypes::$config['tax-cat'] );
	
	if ( ! empty( $terms ) ) {
		if ( function_exists( 'wp_list_sort' ) ) {
			$terms = wp_list_sort( $terms, 'term_id', 'ASC' );
		} else {
			usort( $terms, '_usort_terms_by_ID' );
		}
		$category_object = apply_filters( 'wc_product_post_type_link_product_cat', $terms[0], $terms, $post );
		$category_object = get_term( $category_object, RoxWPWLPostTypes::$config['tax-tag'] );
		$wishlist_cat     = $category_object->slug;
		
		if ( $category_object->parent ) {
			$ancestors = get_ancestors( $category_object->term_id, 'product_cat' );
			foreach ( $ancestors as $ancestor ) {
				$ancestor_object = get_term( $ancestor, 'product_cat' );
				$wishlist_cat     = $ancestor_object->slug . '/' . $wishlist_cat;
			}
		}
	} else {
		// If no terms are assigned to this post, use a string instead (can't leave the placeholder there).
		$wishlist_cat = _x( 'uncategorized', 'slug', 'wordpress-wishlist' );
	}
	
	$find = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		'%post_id%',
		'%category%',
		'%wishlist_cat%',
	);
	
	$replace = array(
		date_i18n( 'Y', strtotime( $post->post_date ) ),
		date_i18n( 'm', strtotime( $post->post_date ) ),
		date_i18n( 'd', strtotime( $post->post_date ) ),
		date_i18n( 'H', strtotime( $post->post_date ) ),
		date_i18n( 'i', strtotime( $post->post_date ) ),
		date_i18n( 's', strtotime( $post->post_date ) ),
		$post->ID,
		$wishlist_cat,
		$wishlist_cat,
	);
	
	$permalink = str_replace( $find, $replace, $permalink );
	
	return $permalink;
}
add_filter( 'post_type_link', 'rox_wpwl_post_type_link', 10, 2 );

/**
 * Get Wishlist Items
 * @param WP_Post|int $post
 * @param string $output
 * @return array|null
 */
function rox_wpwl_get_list( $post = null, $output = OBJECT ) {
	try {
		$post = get_post( $post );
		if ( ! $post || $post->post_type !== RoxWPWLPostTypes::$config['post-type'] ) return NULL;
		global $wpdb;
		$items = $wpdb->get_results( $wpdb->prepare( 'SELECT `ID` FROM `'.RoxWPWL()->get_tables( 'item' ).'` WHERE `post_id` = %d AND `deleted` = 0 ORDER BY `menu_order` ASC, `updated_at` DESC', $post->ID ) );
		$items = array_map( function( $item ) {
			return Rox_List_Item::get_instance( $item->ID );
		}, $items );
		wp_cache_add( $post->ID, count( $items ), 'rox_wpwl_item_counts' );
		return $items;
	} catch ( Exception $e ) {
		error_log( $e );
		return false;
	}
}

/**
 * Get Item count by list
 * @param int|WP_Post $post
 * @return int
 */
function wishlist_count_list_items( $post = NULL ) {
	$post = get_post( $post );
	if ( ! $post || $post->post_type !== RoxWPWLPostTypes::$config['post-type'] ) return NULL;
	$count = wp_cache_get( $post->ID, 'rox_wpwl_item_counts' );
	if( ! $count ) {
		global $wpdb;
		$count = $wpdb->get_row( $wpdb->prepare( 'SELECT COUNT(*) AS total FROM `'. RoxWPWL()->get_tables( 'item' ).'` WHERE `post_id` = %d AND `deleted` = 0', $post->ID ) );
		if( $count && isset( $count->total ) ) {
			wp_cache_add( $post->ID, $count->total, 'rox_wpwl_item_counts' );
			return $count->total;
		}
		return 0;
	}
	return $count;
}
function rox_wpwl_get_list_item( $item = null, $output = OBJECT ) {
	if ( $item instanceof Rox_List_Item ) {
		$_item = $item;
	} elseif ( is_object( $item ) && isset( $item->ID ) ) {
		$_item = Rox_List_Item::get_instance( $item->ID );
	} else {
		$_item = Rox_List_Item::get_instance( $item );
	}
	
	if ( ! $_item )
		return null;
	if ( $output == ARRAY_A )
		return $_item->to_array();
	elseif ( $output == ARRAY_N )
		return array_values( $_item->to_array() );
	
	return $_item;
}

/**
 * Create New WishList
 *
 * @param $name
 * @param $privacy
 * @param bool $wp_error
 *
 * @return int|WP_Error
 */
function rox_wpwl_create_wishlist( $name, $privacy, $wp_error = false ) {
	if( strlen( $name ) > rox_wpwl_get_option( 'list_name_max_length' ) ) {
		if ( $wp_error ) {
			return new WP_Error('list_name_exceed_max_limit', __( 'List Name exceeds maximum limit.', 'wordpress-wishlist' ), $name);
		} else {
			return 0;
		}
	}
	$user_id = get_current_user_id();
	$args = array(
		'post_author' => $user_id,
		'post_title' => $name,
		'post_status' => $privacy,
		'post_type' => RoxWPWLPostTypes::$config['post-type'],
	);
	$postId = wp_insert_post( $args, true );
	if( is_wp_error( $postId )) return $postId;
	if( 'yes' === rox_wpwl_get_option( 'enable_reviews' ) ) {
		$hashId = generate_list_hash( $postId );
		if( $hashId ) {
			wp_update_post( array(
				'ID' => $postId,
				'post_name' => $hashId,
			) );
		}
	}
	return $postId;
}

/**
 * Create New WishList
 *
 * @param $name
 * @param $privacy
 * @param bool $wp_error
 *
 * @return int|WP_Error
 */
function rox_wpwl_update_wishlist( $wishlist_data, $wp_error = false ) {
	$wishlist_data = wp_parse_args( $wishlist_data, array( 'ID' => NULL, 'post_title' => '', 'post_content' => '', 'post_status' => ''  ) );
	$wishlist_data['post_type'] = RoxWPWLPostTypes::$config['post-type'];
	$wishlist = get_post( $wishlist_data['ID'] );
	if ( is_null( $wishlist ) || $wishlist->post_type !== RoxWPWLPostTypes::$config['post-type'] ) {
		if ( $wp_error )
			return new WP_Error( 'invalid_wishlist', __( 'Invalid wishlist ID.' ) );
		return 0;
	}
	if( strlen( $wishlist_data['post_title'] ) > rox_wpwl_get_option( 'list_name_max_length' ) ) {
		if ( $wp_error ) {
			return new WP_Error('list_name_exceed_max_limit', __( 'List Name exceeds maximum limit.', 'wordpress-wishlist' ), $wishlist_data['post_title'] );
		} else {
			return 0;
		}
	}
	$postId = wp_update_post( $wishlist_data, true );
	if( is_wp_error( $postId )) return $postId;
	return $postId;
}

/**
 * Generate HashId like YouTube video id
 * @link https://github.com/ivanakimov/hashids.php
 * @param int $post_ID
 * @return string|bool      Generated Hash ID or false on failior
 */
function generate_list_hash( $post_ID ) {
	$salt = apply_filters( 'rox_wpwl_wishlist_slug_hashId_salt', '' );
	$minHashLength = apply_filters( 'rox_wpwl_wishlist_slug_hashId_length', 11 );
	$alphabet = apply_filters( 'rox_wpwl_wishlist_slug_hashId_alphabets', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890' );
	try{
		$hashids = new Hashids( $salt, $minHashLength, $alphabet );
		return $hashids->encode( $post_ID );
	} catch ( HashidsException $e ) {
		if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $e->getMessage() );
		}
		return false;
	}
}

/**
 * Insert or update wishlist item
 *
 * @param $item_data            Required. Item Data
 * @param bool $wp_error        Optional. Whether to return a WP_Error on failure. Default false.
 * @return bool|int|WP_Error    The Item ID on success. False or WP_Error on failure.
 * @throws Exception
 */
function rox_wpwl_insert_item( $item_data, $wp_error = false ) {
	global $wpdb;
	$defaults = array(
		'post_id'       => NULL,
		'item_title'    => '',
		'item_content'  => '',
		'item_type'     => 'generic',
		'object_id'     => NULL,
		'created_at'    => NULL,
		'updated_at'    => NULL,
		'deleted'       => 0,
		'menu_order'    => 0,
	);
	$item_data = wp_parse_args( $item_data, $defaults );
	$item_ID = 0;
	$update = false;
	if( empty( $item_data['post_id'] ) ) {
		if ( $wp_error ) {
			return new WP_Error('pre_insert_error', __( 'Invalid List ID', 'wordpress-wishlist' ) );
		} else {
			return false;
		}
	}
	if ( ! empty( $item_data['ID'] ) ) {
		$_item = rox_wpwl_get_list_item( $item_data['ID'] );
		if( $_item instanceof Rox_List_Item ) {
			$item_ID = $item_data['ID'];
			$update = true;
		} else {
			unset( $item_data['ID'] );
		}
	} else {
		// try to guess if item is already associated with the list/post id
		$item_ID = rox_wpwl_get_object_item_ID( $item_data['object_id'], $item_data['post_id'], $item_data['item_type'] );
		if( $item_ID ) {
			$_item = rox_wpwl_get_list_item( $item_ID );
			$item_data['ID'] = $item_ID;
			$update = true;
		}
	}
	
	$itemTypes = array_keys( rox_wpwl_item_types() );
	if( ! in_array( $item_data['item_type'], $itemTypes ) ) {
		$item_data['item_type'] = 'generic';
	}
	
	if( isset( $item_data['object_id'] ) && ! empty( $item_data['object_id'] ) && $item_data['item_type'] !== 'generic' ) {
		$object_id = $item_data['object_id'];
		$item_type = $item_data['item_type'];
		$item_data['item_type'] = 'generic';
		unset( $item_data['object_id'] );
		// check object id
		switch( $item_type ) {
			case 'post':
				$object = get_post( $object_id );
				if( $object instanceof WP_Post ) {
					if( $object->post_type !== RoxWPWLPostTypes::$config['post-type'] ) {
						// Get Allowed Post Types
						$allowed = rox_wpwl_get_option( 'allowed_post_types' );
						if( in_array( $object->post_type, $allowed ) ) {
							if( $object->post_status == 'publish' || ( $object->post_type == 'attachment' && $object->post_status == 'inherit' ) ) {
								$item_data['object_id'] = $object->ID;
								$item_data['item_type'] = $item_type;
							}
						}
					}
				}
				break;
			case 'taxonomy_term':
				$object = get_term( $object_id );
				if( $object instanceof WP_Term) {
					if( $object->taxonomy !== RoxWPWLPostTypes::$config['tax-cat'] || $object->taxonomy !== RoxWPWLPostTypes::$config['tax-tag'] ) {
						// Get Allowed Taxonomies
						$allowed = rox_wpwl_get_option( 'allowed_taxonomies', 'advanced' );
						if( in_array( $object->taxonomy, $allowed ) ) {
							$item_data['object_id'] = $object->term_id;
							$item_data['item_type'] = $item_type;
						}
					}
				}
				break;
			default:
				break;
		}
	}
	
	$item_data['deleted'] = (int) $item_data['deleted'];
	$item_data['menu_order'] = (int) $item_data['menu_order'];
	if( $item_data['deleted'] < 0 || $item_data['deleted'] > 1 ) $item_data['deleted'] = 0;
	$current= current_time( 'mysql' );
	$item_data = wp_unslash( $item_data );
	$item_data = array_filter( $item_data );
	$where = array( 'ID' => $item_ID );
	
	if( $update ) {
		$item_data = new Rox_List_Item( (object) $item_data );
		if( $item_data !== $_item ) {
			$item_data = $item_data->to_array();
			unset( $item_data['ID'] );
			unset( $item_data['created_at'] );
			$item_data['updated_at'] = $current;
			do_action( 'pre_wishlist_item_update',$item_ID, $item_data );
			if ( false === $wpdb->update( RoxWPWL()->get_tables( 'item' ), $item_data, $where ) ) {
				if ( $wp_error ) {
					return new WP_Error('db_update_error', __( 'Could not update wishlist item in the database', 'wordpress-wishlist' ), $wpdb->last_error);
				} else {
					return false;
				}
			}
		}
	} else {
		if( empty( $item_data['created_at'] ) || $item_data['created_at'] == '0000-00-00 00:00:00' ) {
			$item_data['created_at'] = $current;
		}
		if( empty( $item_data['updated_at'] ) || $item_data['updated_at'] == '0000-00-00 00:00:00' ) {
			$item_data['updated_at'] = $current;
		}
		if ( false === $wpdb->insert( RoxWPWL()->get_tables( 'item' ), $item_data ) ) {
			if ( $wp_error ) {
				return new WP_Error('db_insert_error', __( 'Could not insert wishlist item into the database', 'wordpress-wishlist' ), $wpdb->last_error);
			} else {
				return false;
			}
		}
		$item_ID = (int) $wpdb->insert_id;
	}
	
	wp_cache_delete( $item_ID, '__rox_items' );
	wp_cache_delete( $item_data['post_id'], 'rox_wpwl_item_counts' );
	
	$item = Rox_List_Item::get_instance( $item_ID );
	
	do_action( 'save_wishlist_item', $item_ID, $item, $update );
	
	return $item_ID;
}

/**
 * Update Item
 * @param array|object $item
 * @param bool $wp_error
 * @return bool|int|WP_Error
 * @throws Exception
 */
function rox_wpwl_update_item( $item, $wp_error = false ) {
	if( is_object( $item ) ) {
		$item = get_object_vars( $item );
	}
	$_item = rox_wpwl_get_list_item($item['ID']);
	if ( is_null( $_item ) ) {
		if ( $wp_error ) return new WP_Error( 'invalid_item', __( 'Invalid Item ID.' ) );
		return false;
	}
	$item['updated_at'] = current_time( 'mysql' );
	$item = array_merge( $_item->to_array(), $item );
	
	return rox_wpwl_insert_item( $item, $wp_error );
}

/**
 * Delete Wishlist Item from Database
 * @param int $item_ID      Required. Item ID
 * @param bool $trash       Optional. Default is false.
 *                          If this set to true then the item will be kept in database with deleted = 1,
 *                          much like as trashed.
 * @return bool
 * @throws Exception
 */
function rox_wpwl_delete_item( $item_ID, $trash = false ) {
	global $wpdb;
	wp_cache_delete( $item_ID, '__rox_items' );
	if( $trash ) {
		return (bool) rox_wpwl_update_item( array( 'ID' => $item_ID, 'deleted' => 1 ), false );
	}
	return $wpdb->delete( RoxWPWL()->get_tables( 'item' ), array( 'ID' => $item_ID ) );
}

function rox_wpwl_delete_list( $list, $user_id, $trash = true ) {
	$list = get_post( $list );
	if( $list instanceof WP_Post && $list->post_type == RoxWPWLPostTypes::$config['post-type'] && $list->post_author == $user_id ) {
		$list_id = $list->ID;
		$deleted = rox_wpwl_delete_list_items( $list, $trash );
		if( ! $deleted ) return false;
		if( $trash ) {
			$list = wp_trash_post( $list_id );
		} else {
			$list = wp_delete_post( $list_id );
		}
		return ( $list instanceof WP_Post );
	} else return false;
}

function rox_wpwl_delete_list_items( $list, $trash = true ) {
	$list = get_post( $list );
	if( $list instanceof WP_Post && $list->post_type == RoxWPWLPostTypes::$config['post-type'] ) {
		global $wpdb;
		$itemTable = RoxWPWL()->get_tables( 'item' );
		if( $trash ) {
			$update = $wpdb->update( $itemTable, array( 'deleted' => 1 ), array( 'post_id' => $list->ID) );
			if( ! $update ) return false;
			return true;
		} else {
			$delete = $wpdb->delete( $itemTable, array( 'post_id' => $list->ID) );
			if( ! $delete ) return false;
			else {
				$contributorTable = RoxWPWL()->get_tables( 'contributor' );
				$wpdb->delete( $contributorTable, array( 'post_id' => $list->ID) );
				return true;
			}
		}
	} else return false;
}

/**
 * Generates an excerpt from the content, if needed.
 * This to prevent generating PHP 7.2 Warning generated in get_the_content() [count( $pages )]
 * and also works for Terms.
 * @see wp_trim_excerpt()
 * @param string|WP_Post|WP_Term
 * @return string
 */
function rox_wpwl_get_excerpt( $thing = '' ) {
	if ( empty( $thing ) ) return '';
	if( $thing instanceof WP_Post ) {
		if( $thing->post_excerpt ) return $thing->post_excerpt;
		$text = $thing->post_content;
	} else if( $thing instanceof WP_Term ) {
		$text = $thing->description;
	} else {
		$text = (string) $thing;
	}
	$raw_excerpt = $text;
	
	$text = strip_shortcodes( $text );
	$text = excerpt_remove_blocks( $text );
	
	/** This filter is documented in wp-includes/post-template.php */
	$text = apply_filters( 'the_content', $text );
	$text = str_replace(']]>', ']]&gt;', $text);
	
	/** This filter is documented in wp-includes/formatting.php */
	$excerpt_length = apply_filters( 'excerpt_length', 55 );
	
	/** This filter is documented in wp-includes/formatting.php */
	$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
	$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );
	
	/** This filter is documented in wp-includes/formatting.php */
	return apply_filters( 'wp_trim_excerpt', $text, $raw_excerpt );
}
/**
 * Add contributor to list
 * @param int $list_ID      List ID
 * @param int $user_ID      WP User ID
 * @param array $can_edit  Permission
 * @return int|false
 */
function rox_wpwl_add_contributor( $list_ID, $user_ID, $can_edit = array( 'view' ) ) {
	//@TODO wpdb
	return 1;
}
/**
 * Get Wishlist Contributors User ID's and permissions of user
 * @param int $list_ID
 * @return array
 */
function rox_wpwl_get_list_contributors( $list_ID ) {
	global $wpdb;
	$contributorTable = RoxWPWL()->get_tables( 'contributor' );
	return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$contributorTable} WHERE post_id = %d", $list_ID ) );
}

/**
 * Get User's Contribution list Post IDs
 * @param $user_ID
 * @param string $permission
 *
 * @return array
 */
function rox_wpwl_get_user_contribution_list( $user_ID, $permission = NULL ) {
	global $wpdb;
	$contributorTable = RoxWPWL()->get_tables( 'contributor' );
	if( ! in_array( $permission, array( 'view', 'edit', 'any' ) ) || empty( $permission ) ) $permission = 'view';
	if( $permission == 'any' ) return $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$contributorTable} WHERE user_id = %d", $user_ID ) );
	return $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$contributorTable} WHERE user_id = %d AND permissions = %s", $user_ID, $permission ) );
}
function rox_wpwl_can_user_edit_list( $list = NULL, $user_ID = NULL ) {
	$list = get_post( $list );
	if( ! is_wishlist_post( $list ) ) return false;
	if( empty( $user_ID ) ) $user_ID = get_current_user_id();
	if( $list->post_author == $user_ID ) return true;
	global $wpdb;
	$contributorTable = RoxWPWL()->get_tables( 'contributor' );
	$data = $wpdb->get_row( $wpdb->prepare( "SELECT permissions FROM {$contributorTable} WHERE post_id = %d AND user_id = %d", $list->ID, $user_ID ) );
	return ( $data && isset( $data->permissions ) && $data->permissions == 'edit' );
}

/**
 * Check if post is a wishlist or not
 * @param int|WP_Post|null $post   Optional. Post ID or post object. Defaults to global $post.
 * @return bool
 *
 */
function is_wishlist_post( $post = NULL ) {
	$post = get_post( $post );
	return ( $post instanceof WP_Post && $post->post_type == RoxWPWLPostTypes::$config['post-type'] );
}
/**
 * Get User's Wishlist Including Contribute lists
 * @param int $user_ID
 * @param string $permission
 *
 * @return array    array of post IDs
 *
 */
function rox_wpwl_get_users_wishlists( $user_ID = NULL, $permission = NULL ) {
	if( empty( $user_ID ) ) $user_ID = get_current_user_id();
	$lists = new WP_Query( array(
		'post_type' => RoxWPWLPostTypes::$config['post-type'],
//	    'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
		'post_status' => array('publish', 'private', ),
		'posts_per_page' => -1,
		'fields' => 'ids',
		'author' => $user_ID,
	) );
	$lists = array_merge( $lists->get_posts(), rox_wpwl_get_user_contribution_list( $user_ID, $permission ) );
	$lists = array_filter( $lists );
	$lists = array_map( 'absint', $lists );
	return array_unique( $lists, SORT_NUMERIC );
}

/**
 * Wishlist Item Types
 *
 * @return array
 */
function rox_wpwl_item_types() {
	$builtin = array(
		'generic'           => esc_html__( 'Generic', 'wordpress-wishlist' ),
		'post'              => esc_html__( 'WP Post Type', 'wordpress-wishlist' ),
		'taxonomy_term'     => esc_html__( 'WP Taxonomy Term', 'wordpress-wishlist' ),
		// 'user'              => esc_html__( 'WP User', 'wordpress-wishlist' ),
	);
	/**
	 * Filter Wishlist Item Types.
	 * Add new type/s to the items, type key will be stored in database as identifire.
	 * So the key should avoid using whitespaces.
	 * @param array
	 */
	$types = apply_filters( 'rox_wpwl_list_item_types', $builtin );
	if( count( array_diff( array_keys( $builtin ), array_keys( $types ) ) ) > 0 ) {
		if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			trigger_error( sprintf( '<code>rox_wpwl_list_item_types</code> filter was called <strong>incorrectly</strong>. Builtin Types cannot be removed.' ), E_USER_NOTICE );
		}
		return $builtin;
	}
	// Move builtin types to the top
	$types = array_merge( array_flip( array_keys( $builtin ) ), $types );
	return $types;
}

/**
 * Check if wishlist button is disallowed in current page
 * This function should be called after init, as it's utilitize the WP_Query to be loaded.
 * @return bool
 */
function is_wishlist_disallowed_page( $post = NULL ) {
	$post = get_post( $post );
	$disallowedPosts = (string) rox_wpwl_get_option( 'disallowed_posts' );
	$disallowedPosts = array_filter( explode( ',', $disallowedPosts ) );
	$disallowedPosts = array_map( function( $postId ){
		return (int) trim( $postId);
	}, $disallowedPosts );
	$disallowedPosts = wp_parse_args( $disallowedPosts, array(
		get_option( 'page_on_front' ),
		get_option( 'page_for_posts' ),
		get_option( 'wp_page_for_privacy_policy' ),
	) );
	$disallowedPosts = (array) apply_filters( 'rox_wp_wishlist_disallowed_posts', $disallowedPosts );
	if( $post instanceof WP_Post ) {
		return ( in_array( $post->ID, $disallowedPosts ) );
	}
	return false;
}

// Template Functions

/**
 * Get template part (for templates like the shop-loop).
 *
 * ROX_WPWL_TEMPLATE_DEBUG will prevent overrides in themes from taking priority.
 *
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function rox_wpwl_get_template_part( $slug, $name = '' ) {
	$template = '';
	// Look in yourtheme/slug-name.php and yourtheme/wp-wishlist/slug-name.php.
	if ( $name && ! ROX_WPWL_TEMPLATE_DEBUG ) {
		$template = locate_template( array( "{$slug}-{$name}.php", RoxWPWL()->__template_path() . "/{$slug}-{$name}.php" ) );
	}
	
	// Get default slug-name.php.
	if ( ! $template && $name && file_exists( RoxWPWL()->plugin_path() . "/templates/{$slug}-{$name}.php" ) ) {
		$template = RoxWPWL()->plugin_path() . "/templates/{$slug}-{$name}.php";
	}
	
	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/wp-wishlist/slug.php.
	if ( ! $template && ! ROX_WPWL_TEMPLATE_DEBUG ) {
		$template = locate_template( array( "{$slug}.php", RoxWPWL()->__template_path() . "/{$slug}.php" ) );
	}
	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'rox_wpwl_get_template_part', $template, $slug, $name );
	
	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Get other templates
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 *
 */
function rox_wpwl_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args ); 
	}
	$located = rox_wpwl_locate_template( $template_name, $template_path, $default_path );
	if ( ! file_exists( $located ) ) {
		/* translators: %s template */
		rox_wpwl_doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'woocommerce' ), '<code>' . $located . '</code>' ), '2.1' );
		return;
	}
	// Allow 3rd party plugin filter template file from their plugin.
	$located = apply_filters( 'rox_wpwl_get_template', $located, $template_name, $args, $template_path, $default_path );
	do_action( 'rox_wpwl_before_template_part', $template_name, $template_path, $located, $args );
	include $located;
	do_action( 'rox_wpwl_after_template_part', $template_name, $template_path, $located, $args );
}
/**
 * Like rox_wpwl_get_template, but returns the HTML instead of outputting.
 *
 * @see rox_wpwl_get_template
 * @since 2.5.0
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 *
 * @return string
 */
function rox_wpwl_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	rox_wpwl_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
}
/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 * @return string
 * 
 */
function rox_wpwl_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = RoxWPWL()->__template_path();
	}
	if ( ! $default_path ) {
		$default_path = RoxWPWL()->plugin_path() . '/templates/';
	}
	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);
	// Get default template/.
	if ( ! $template || ROX_WPWL_TEMPLATE_DEBUG ) {
		$template = $default_path . $template_name;
	}
	// Return what we found.
	return apply_filters( 'rox_wpwl_locate_template', $template, $template_name, $template_path );
}

/**
 *
 * Get Wishlist Plugin Settings
 * @param string $settings      Required. Settings Name
 * @param string $section       Required. Settings Section Name. Default 'general'
 * @return bool
 */
function rox_wpwl_get_option( $settings, $section = 'general' ) {
	return RoxWPWLSettings::getInstance()->get_option( $settings, $section );
}

/**
 * Check if current page is related to wp-wishlist
 * this will return true if current page is any of following:
 *      wishlist-archive, wishlist taxonomy, wishlist taxonomy term, single wishlsit page.
 * @return bool
 */
function is_wp_wishlist() {
	return (bool) apply_filters( 'is_rox_wpwl', is_wishlist_archive() || is_wishlist_taxonomy() || is_wishlist() );
}

/**
 * Check if current page is wishlist archive page
 * @return bool
 */
function is_wishlist_archive() {
	return ( is_post_type_archive( RoxWPWLPostTypes::$config['post-type'] ) || is_page( rox_wpwl_get_option( 'wishlist_archive' ) ) );
}

/**
 * Check if current taxonomy is attached with wishlist post_type
 * @return bool
 */
function is_wishlist_taxonomy() {
	return is_tax( get_object_taxonomies( RoxWPWLPostTypes::$config['post-type'] ) );
}

/**
 * Check if current taxonomy term is wishlist category
 * @param string $term
 *
 * @return bool
 */
function is_wishlist_category( $term = '' ) {
	return is_tax( RoxWPWLPostTypes::$config['tax-cat'], $term );
}

/**
 * check if current taxonomy term is wishlist tag
 * @param string $term
 *
 * @return bool
 */
function is_wishlist_tag( $term = '' ) {
	return is_tax( RoxWPWLPostTypes::$config['tax-tag'], $term );
}

/**
 * Check if current page is single wishlist page
 * @return bool
 */
function is_wishlist() {
	return is_singular( array( RoxWPWLPostTypes::$config['post-type'] ) );
}
if( ! function_exists( 'rox_post_has_shortcode' ) ) {
	/**
	 * Checks whether the Post contents has shortcode
	 * @param string $tag       Shortcode tag to check.
	 * @param int|WP_Post|null $post   Optional. Post ID or post object. Defaults to global $post.
	 * @return bool
	 */
	function rox_post_has_shortcode( $tag = '', $post = NULL ) {
		$post = get_post( $post );
		return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
	}
}
if( ! function_exists( 'rox_format_alert' ) ) {
	/**
	 * Format Alert Message/s
	 * @param string|array|object   $data       Alert Message To Show
	 * @param string                $type       optional. alert type
	 *
	 * @return string
	 */
	function rox_format_alert( $data, $type = 'info' ) {
		$output = '';
		if( is_object( $data ) ) $data = get_object_vars( $data );
		if( is_array( $data ) ) {
			foreach( $data as $k => $v ) $output .= rox_format_alert( $v, $k );
		} else {
			$output .= sprintf( '<div class="rox-alert rox-%s">%s</div>', $type, $data );
		}
		return $output;
	}
}
/**
 * Wrapper for rox_wpwl_doing_it_wrong.
 *
 * @since  3.0.0
 * @param string $function Function used.
 * @param string $message Message to log.
 * @param string $version Version the message was added in.
 */
function rox_wpwl_doing_it_wrong( $function, $message, $version ) {
	
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();
	
	if ( is_ajax() ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong($function, $message, $version);
	}
}
if( ! function_exists( '__rox_dump' ) ) {
	/**
	 * Wrapper for var_dump, var_export and print_r
	 * @param mixed $data       Default NULL.
	 * @param string $type      Default 'dump' for var_dump(), other options are 'print' for print_r() and 'export' for var_export(),
	 * @param bool $return      Default false. print or return the output.
	 * @param bool $backtrace   Default true. backtrace the function call
	 * @return mixed
	 */
	function __rox_dump( $data = NULL, $type = 'dump', $return = false, $backtrace = true ) {
		$output = '';
		if( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG  ) && ! ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ) return;
		switch( $type ) {
			case 'print':
				$output .= print_r( $data, true );
				break;
			case 'export':
				$output .= var_export($data, true);
				break;
			case 'dump':
			default:
				ob_start();
				var_dump( $data );
				$output .= ob_get_clean();
				break;
		}
		if( $backtrace ) {
			$output .= "\n\n" . join( "\n", wp_debug_backtrace_summary( NULL, 0, false ) );
		}
		if( ! extension_loaded( 'xdebug' ) ) {
			$output = highlight_string( "<?php\n\n" . $output . "\n\n?>", true );
		}
		if( ! $return ) {
			if( defined( 'WP_DEBUG' ) && WP_DEBUG  ) {
				$styles = 'position: relative; display: block; width: calc( 100% - 20px ); margin: 10px auto; background: #f1f1f1; padding: 20px; box-sizing: border-box;';
				echo '<div class="__rox_dump_wrapper__" style="'.$styles.'">' . PHP_EOL . $output . PHP_EOL . '</div>';
			} else if( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG  ) {
				error_log( $output );
			}
		}
		else return $output;
	}
}