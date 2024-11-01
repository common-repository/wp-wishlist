<?php
if( ! function_exists( 'add_action' ) ) {
	die();
}

class RoxWPWLRenderHelper {
	/**
	 * Allowed Post Types for showing wishlist button
	 * @access Private
	 * @var array
	 */
	private static $__post_types;
	/**
	 * Allowed Taxonomies for showing wishlist button
	 * @access Private
	 * @var array
	 */
	private static $__taxonomies;
	/**
	 * Visibility Option
	 * @access Private
	 * @var string
	 */
	private static $__visibility;
	/**
	 * Visibility Option (WooCommerce)
	 * @access Private
	 * @var string
	 *
	 */
	private static $__wc_visibility;
	
	/**
	 * Initialize Hooks
	 * @return void
	 */
	public static function init() {
		
		self::$__post_types = rox_wpwl_get_option( 'allowed_post_types', 'advanced' );
		if( is_array( self::$__post_types ) ) self::$__post_types = array_keys( self::$__post_types );
		
		self::$__taxonomies = rox_wpwl_get_option( 'allowed_taxonomies', 'advanced' );
		if( is_array( self::$__taxonomies ) ) self::$__taxonomies = array_keys( self::$__taxonomies );
		
		self::$__visibility = rox_wpwl_get_option( 'visibility_option', 'advanced' );
		if( is_array( self::$__visibility ) ) if ( isset( self::$__visibility[0] ) ) self::$__visibility = self::$__visibility[0];
		self::$__visibility = strtolower( self::$__visibility );
		self::$__wc_visibility = rox_wpwl_get_option( 'wc_product_visibility_option', 'advanced' );
		if( is_array( self::$__wc_visibility ) ) if ( isset( self::$__wc_visibility[0] ) ) self::$__wc_visibility = self::$__wc_visibility[0];
		self::$__wc_visibility = strtolower( self::$__wc_visibility );
		if( ! is_admin() ) {
			if( in_array( 'product', self::$__post_types ) ) {
				add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'show_on_product_loop_with_cart_button' ), 9999, 3 );
				if( self::$__wc_visibility == 'before_cart_button' ) {
					add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'show_on_product_before_cart_button' ), 9  );
				}
				if( self::$__wc_visibility == 'after_cart_button' ) {
					add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'show_on_product_after_cart_button' ), 9  );
				}
				add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'show_on_product_after_out_of_stock_notice' ), 30 );
			}
			
			if( strpos( self::$__visibility, 'content' ) !== false || strpos( self::$__wc_visibility, 'content' ) !== false ) {
				add_filter( 'the_content', array( __CLASS__, 'show_with_content' ), 99999, 1 );
			}
		}
		
		add_action( 'wp_footer', array( __CLASS__, 'render_rox_wishlist_pop' ), 9999 );
		add_action( 'wp_ajax___get_user_wishlists', array( __CLASS__, '__get_user_wishlists' ), 10 );
		add_action( 'wp_ajax_nopriv___get_user_wishlists', array( __CLASS__, '__get_user_wishlists' ), 10 );
		add_action( 'wp_ajax___add_list_item', array( __CLASS__, '__add_list_item' ), 10 );
		add_action( 'wp_ajax___remove_list_item', array( __CLASS__, '__remove_list_item' ), 10 );
		add_action( 'wp_ajax___add_new_list_with_item', array( __CLASS__, '__add_new_list_with_item' ), 10 );
		add_action( 'wp_ajax___update_wishlist', array( __CLASS__, '__update_wishlist' ), 10 );
//		add_action( 'wp_ajax_', array( __CLASS__, '' ), 10 );
	}
	
	/**
	 * Ajax Callback for adding new item (wp post/taxonomy) in wishlist
	 * @throws Exception
	 * @return void
	 */
	public static function __add_list_item() {
		if( wp_verify_nonce( $_REQUEST['_nonce'], '__csrf' . ROX_WPWL_PLUGIN_NAME ) ) {
			
			$list = absint( $_REQUEST['list_id'] );
			$list = get_post( $list );
			$object = absint( $_REQUEST['id'] );
			$object_type = sanitize_text_field( $_REQUEST['type'] );
			if( isset( $_REQUEST['usre_id'] ) ) $user_ID = absint( $_REQUEST['usre_id'] );
			else $user_ID = get_current_user_id();
			
			// validate ajax request
			$validate = self::__validate_ajax_payload( $user_ID, $list, $object, $object_type );
			if( is_wp_error( $validate ) ) wp_send_json_error( rox_format_alert( $validate->get_error_message(), 'error' ) );
			
			$item = array(
				'post_id'       => $list->ID,
				'item_type'     => $object_type,
				'object_id'     => $object,
				'deleted'       => 0,
			);
			if( ! is_object_in_wishlist( $object, $list->ID, $object_type ) ) {
				$item = rox_wpwl_insert_item( $item, true );
			} else {
				$item['ID'] = rox_wpwl_get_object_item_ID( $object, $list->ID, $object_type);
				$item = rox_wpwl_update_item( $item, true );
			}
			if( is_wp_error( $item ) ) {
				wp_send_json_error( rox_format_alert( $item->get_error_message(), 'error' ) );
			} else {
				wp_send_json_success( rox_format_alert( __( 'Item added.', 'wordpress-wishlist' ), 'success' ) );
			}
		} else {
			wp_send_json_error( rox_format_alert( __( 'Invalid CSRF.', 'wordpress-wishlist' ), 'error' ) );
		}
		die();
	}
	
	/**
	 * Ajax Callback for removing item (wp post/taxonomy) from wishlist
	 * @throws Exception
	 * @return void
	 */
	public static function __remove_list_item() {
		if( wp_verify_nonce( $_REQUEST['_nonce'], '__csrf' . ROX_WPWL_PLUGIN_NAME ) ) {
			
			$list = absint( $_REQUEST['list_id'] );
			$list = get_post( $list );
			$object = absint( $_REQUEST['id'] );
			$object_type = sanitize_text_field( $_REQUEST['type'] );
			if( isset( $_REQUEST['usre_id'] ) ) $user_ID = absint( $_REQUEST['usre_id'] );
			else $user_ID = get_current_user_id();
			// validate request
			$validate = self::__validate_ajax_payload( $user_ID, $list, $object, $object_type );
			if( is_wp_error( $validate ) ) wp_send_json_error( rox_format_alert( $validate->get_error_message(), 'error' ) );
			// get item db id
			$item = rox_wpwl_get_object_item_ID( $object, $list->ID, $object_type);
			// check the item
			if( ! $item ) wp_send_json_error( rox_format_alert( __( 'Invalid item.', 'wordpress-wishlist' ), 'error' ) );
			// delete item
			if( rox_wpwl_delete_item( $item ) ) {
				wp_send_json_success( rox_format_alert( __( 'Item removed.', 'wordpress-wishlist' ), 'success' ) );
			} else {
				wp_send_json_error( rox_format_alert( __( 'Unable to remove item. Please try again after sometime.', 'wordpress-wishlist' ), 'error' ) );
			}
			if( is_wp_error( $item ) ) {
				wp_send_json_error( rox_format_alert( $item->get_error_message(), 'error' ) );
			} else {
				wp_send_json_success( rox_format_alert( __( 'Item added.', 'wordpress-wishlist' ), 'success' ) );
			}
		} else {
			wp_send_json_error( rox_format_alert( __( 'Invalid CSRF.', 'wordpress-wishlist' ), 'error' ) );
		}
		die();
	}
	
	/**
	 * Validate Common Data for ajax request
	 * @param $user_ID
	 * @param $list
	 * @param $object
	 * @param $object_type
	 *
	 * @return bool|WP_Error
	 */
	private static function __validate_ajax_payload( $user_ID, $list, $object, $object_type ) {
		$currentUser = get_current_user_id();
		if( ! get_user_by( 'ID', $user_ID ) ) {
			return new WP_Error( 'invalid-user', __( 'Invalid User.', 'wordpress-wishlist' ) );
		}
		if( ! is_wishlist_post( $list ) ) {
			return new WP_Error( 'invalid-list', __( 'Invalid list.', 'wordpress-wishlist' ) );
		}
		if( ! rox_wpwl_can_user_edit_list( $list->ID, $user_ID ) ) {
			$error = __( 'User does not have permission to add item in this list.', 'wordpress-wishlist' );
			if( $currentUser == $user_ID ) $error = __( 'You does not have permission to add item in this list.', 'wordpress-wishlist' );
			return new WP_Error( 'invalid-permission', $error );
		}
		$types = array_keys( rox_wpwl_item_types() );
		$object_type = in_array( $object_type, $types )? $object_type : false;
		if( $object <= 0 || $object_type == false ) {
			return new WP_Error( 'invalid-item', __( 'Invalid item.', 'wordpress-wishlist' ) );
		}
		if( $object_type == 'post' ) {
			$object = get_post( $object );
			if( ! is_a( $object, 'WP_Post' ) || ! in_array( $object->post_type, self::$__post_types ) ) {
				return new WP_Error( 'invalid-post', __( 'Invalid item.', 'wordpress-wishlist' ) );
			}
		} else if( $object_type == 'taxonomy_term' ) {
			$object = get_term( $object );
			if( ! is_a( $object, 'WP_Term' ) || ! in_array( $object->taxonomy, self::$__taxonomies ) ) {
				return new WP_Error( 'invalid-taxonomy-term', __( 'Invalid item', 'wordpress-wishlist' ) );
			}
		}
		return true;
	}
	
	/**
	 * Ajax Callback for adding new list and add item (wp post/taxonomy) to the new list
	 * @throws Exception
	 * @return void
	 */
	public static function __add_new_list_with_item() {
		if( wp_verify_nonce( $_REQUEST['_nonce'], '__csrf' . ROX_WPWL_PLUGIN_NAME ) ) {
			$listName = sanitize_text_field( $_REQUEST['list_name'] );
			$listPrivacy = sanitize_text_field( $_REQUEST['list_privacy'] );
			if( ! in_array( $listPrivacy, array( 'public', 'private' ) ) ) $listPrivacy = 'private';
			if( $listPrivacy == 'public' ) $listPrivacy = 'publish';
			$ob = absint( $_REQUEST['id'] );
			$types = array_keys( rox_wpwl_item_types() );
			$ob_type = in_array( $_REQUEST['type'], $types )? $_REQUEST['type'] : false;
			if( $ob <= 0 || $ob_type == false ) {
				wp_send_json_error( rox_format_alert( __( 'Invalid item.', 'wordpress-wishlist' ), 'error' ) );
			}
			$list = rox_wpwl_create_wishlist( $listName, $listPrivacy, true );
			if( is_wp_error( $list ) ) {
				wp_send_json_error( rox_format_alert( $list->get_error_message(), 'error' ) );
			} else {
				$item = array(
					'post_id'       => $list,
					'item_type'     => $ob_type,
					'object_id'     => $ob,
				);
				$item = rox_wpwl_insert_item( $item, true );
				if( is_wp_error( $item ) ) {
					wp_send_json_error( rox_format_alert( $item->get_error_message(), 'error' ) );
				} else {
					wp_send_json_success( rox_format_alert( __( 'Item added.', 'wordpress-wishlist' ), 'success' ) );
				}
			}
		} else {
			wp_send_json_error( rox_format_alert( __( 'Invalid CSRF.', 'wordpress-wishlist' ), 'error' ) );
		}
		die();
	}
	
	/**
	 * Ajax Callback for modifing wishlist
	 * @throws Exception
	 * @return void
	 */
	public static function __update_wishlist() {
		if( wp_verify_nonce( $_REQUEST['_nonce'], '__csrf' . ROX_WPWL_PLUGIN_NAME ) ) {
			if( ! isset( $_REQUEST['post_id'], $_REQUEST['list_name'], $_REQUEST['list_description'] ) ) {
				wp_send_json_error( rox_format_alert( __( '', 'wordpress-wishlist'), 'error' ) );
			}
			$listName = sanitize_text_field( $_REQUEST['list_name'] );
			$listDescription = sanitize_textarea_field( $_REQUEST['list_description'] );
			$listPrivacy = sanitize_text_field( $_REQUEST['list_privacy'] );
			$ID = absint( $_REQUEST['post_id'] );
			
			if( ! rox_wpwl_can_user_edit_list( $ID ) ) {
				wp_send_json_error( rox_format_alert( __( 'You does not have permission edit this list.', 'wordpress-wishlist' ), 'error' ) );
			}
			
			if( ! in_array( $listPrivacy, array( 'public', 'private' ) ) ) $listPrivacy = 'private';
			if( $listPrivacy == 'public' ) $listPrivacy = 'publish';
			
			// update list
			$list = rox_wpwl_update_wishlist( array( 'ID' => $ID, 'post_title' => $listName, 'post_content' => $listDescription, 'post_status' => $listPrivacy ), true );
			if( is_wp_error( $list ) ) {
				wp_send_json_error( rox_format_alert( $list->get_error_message(), 'error' ) );
			} else {
				wp_send_json_success( rox_format_alert( __( 'Wishlist updated.', 'wordpress-wishlist' ), 'success' ) );
			}
		} else {
			wp_send_json_error( rox_format_alert( __( 'Invalid CSRF.', 'wordpress-wishlist' ), 'error' ) );
		}
		die();
	}
	
	/**
	 * Ajax Callback for getting user's wishlists
	 * @throws Exception
	 * @return void
	 */
	public static function __get_user_wishlists() {
		if( wp_verify_nonce( $_REQUEST['_nonce'], '__csrf' . ROX_WPWL_PLUGIN_NAME ) ) {
			if( ! is_user_logged_in() ) {
				wp_send_json_success( rox_format_alert( __( 'Please Login First.', 'wordpress-wishlist' ), 'error' ) );
			}
			$object_id = absint( $_REQUEST['id'] );
			$object_type = sanitize_text_field( $_REQUEST['type'] );
			wp_send_json_success( rox_wpwl_get_popup_items( get_current_user_id(), $object_id, $object_type ) );
		} else {
			wp_send_json_error( rox_format_alert( __( 'Invalid CSRF.', 'wordpress-wishlist' ), 'error' ) );
		}
		die();
	}
	
	/**
	 * Callback for the_content hook for showing wishlist button in post contents
	 * @param $content
	 *
	 * @return string
	 */
	public static function show_with_content( $content ) {
		if( empty( self::$__post_types ) ) return $content;
		$post = get_post();
		if( $post && ! in_array( $post->post_type, self::$__post_types ) || is_wishlist_disallowed_page( $post ) ) return $content;
		if( rox_is_woocommerce_activated() ) {
			if( $post->post_type == 'product' && in_array( self::$__wc_visibility, array( 'before_cart_button', 'after_cart_button') )
			|| is_shop() || is_checkout() || is_cart() || is_account_page() )
				return $content;
		}
		if( is_wp_wishlist() ) return $content;
		$button = RoxWPWLShortCodes::rox_wishlist_button();
		$button = apply_filters( 'wishlist_button_' . $post->post_type . '_' . self::$__visibility, $button, get_the_ID()  );
		$button = apply_filters( 'wishlist_button', $button, get_the_ID()  );
		switch( self::$__visibility ) {
			case 'before_content':
				$content = $button . $content;
				break;
			case 'after_content':
				$content .= $button;
				break;
			default:
				break;
		}
		return $content;
	}
	
	/**
	 * callback for 'woocommerce_loop_add_to_cart_link' hook for showing wishlist button with add-to-cart button withing the loop (archive, taxonomy and related product)
	 * @param $html
	 * @param $product
	 * @param $button_args
	 *
	 * @return string
	 */
	public static function show_on_product_loop_with_cart_button( $html, $product, $button_args ) {
		
		if( is_wp_wishlist() || is_wishlist_disallowed_page( get_the_ID() ) ) return $html;
		$button = RoxWPWLShortCodes::rox_wishlist_button( array( 'type' => 'post' ) );
		$button = apply_filters( 'wishlist_button_product_' . self::$__wc_visibility, $button, get_the_ID()  );
		$button = apply_filters( 'wishlist_button_product_loop', $button, get_the_ID()  );
		switch( self::$__wc_visibility ) {
			case 'before_cart_button':
				$html = $button . $html;
				break;
			case 'after_cart_button':
				$html .= $button;
				break;
			default:
				break;
		}
		return $html;
	}
	
	/**
	 * callback for 'woocommerce_before_add_to_cart_button' hook for showing wishlist button before add-to-cart in single product page
	 * @return void
	 */
	public static function show_on_product_before_cart_button() {
		if( is_wishlist_disallowed_page( get_the_ID() ) ) return;
		echo apply_filters( 'wishlist_button_product_single_before_cart_button', RoxWPWLShortCodes::rox_wishlist_button( array( 'type' => 'post' ) ), get_the_ID()  );
	}
	
	/**
	 * callback for 'woocommerce_after_add_to_cart_button' hook for showing wishlist button after add-to-cart in single product page
	 * @return void
	 */
	public static function show_on_product_after_cart_button() {
		if( is_wishlist_disallowed_page( get_the_ID() ) ) return;
		echo apply_filters( 'wishlist_button_product_single_after_cart_button', RoxWPWLShortCodes::rox_wishlist_button( array( 'type' => 'post' ) ), get_the_ID()  );
	}
	
	/**
	 * callback for 'woocommerce_single_product_summary' hook for showing wishlist button after stock notice when product is out of stock
	 * @return void
	 */
	public static function show_on_product_after_out_of_stock_notice() {
		global $product;
		if( is_wishlist_disallowed_page( get_the_ID() ) ) return;
		if( $product instanceof WC_Product && ! $product->is_in_stock() ) {
			echo apply_filters( 'wishlist_button_product_single_after_stock', RoxWPWLShortCodes::rox_wishlist_button( array( 'type' => 'post' ) ), get_the_ID()  );
		}
	}
	
	/**
	 * callback for 'wp_footer' hook for printing popup container in footer
	 * @return void
	 */
	public static function render_rox_wishlist_pop() {
		rox_wpwl_get_template( 'popup.php' );
	}
}
RoxWPWLRenderHelper::init();
// End of file class.RoxWPWLButtonRenderer.php