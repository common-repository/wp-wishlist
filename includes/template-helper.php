<?php
if( ! function_exists( 'add_action' ) ) {
	die();
}

if( ! function_exists( 'wishlist_single_list_item_attachment_link_title' ) ) {
	/**
     * Attachment Link Title
     * @param string $title
	 * @param int $item_id
	 * @param WP_Post|WP_Term $attachment
     * @return string
	 */
    function wishlist_single_list_item_attachment_link_title( $title, $item_id, $attachment ) {
        if( is_admin() ) return $title;
	    return __( 'View', 'wordpress-wishlist' );
    }
}

if( ! function_exists( 'wishlist_single_list_item_attachment_attributes' ) ) {
    function wishlist_single_list_item_attachment_attributes( $attributes, $item_id, $attachment ) {
	    if( $attachment instanceof WP_Post ) {
		    $attributes['aria-label'] = sprintf( __( 'View “%s”', 'wordpress-wishlist' ), get_the_title( $attachment->ID ) );
	    }
	    if( $attachment instanceof WP_Term ) {
		    $attributes['aria-label'] = sprintf( __( 'View “%s”', 'wordpress-wishlist' ), $attachment->name );
        }
        return $attributes;
    }
}

if( ! function_exists( 'wishlist_single_list_item_attachment_html' ) ) {
    function wishlist_single_list_item_attachment_html( $html, $item_id, $attachment ) {
	    if ( $attachment instanceof WP_Post && $attachment->post_type == 'product' && rox_is_woocommerce_activated() ) {
		    return wishlist_get_woocommerce_add_to_cart_button( $attachment->ID );
	    }
	    return $html;
    }
}
/**
 * Get Wishlist Item Title
 * @param Rox_List_Item $item
 * @return string
 */
function get_list_item_title( \Rox_List_Item $item ) {
	$attachment = $item->get_attachment();
	if( $attachment instanceof WP_Post ) {
	    $raw_title = get_the_title( $attachment );
	    $title = sprintf( '<a href="%s">%s</a>', get_the_permalink( $attachment ), $raw_title );
    } else if( $attachment instanceof WP_Term ) {
	    $raw_title = esc_html( $attachment->name );
		$title = sprintf( '<a href="%s">%s</a>', get_term_link( $attachment ), $raw_title );
    } else {
	    $title = esc_html( $item->item_title );
    }
	
	return apply_filters( 'wishlist_single_item_title', $title, $raw_title, $item, $attachment, $item->item_title );
}

function rox_wpwl_set_product_stock_text( $availability, $_product ) {
	if ( $_product->is_in_stock() ) $availability['availability'] = __( 'In Stock', 'woocommerce' );
	return $availability;
}

/**
 * Get Wishlist Item Content
 * @param Rox_List_Item $item
 * @return string
 */
function get_list_item_content( \Rox_List_Item $item ) {
	$attachment = $item->get_attachment();
	if( $attachment instanceof WP_Post ) {
		$content = rox_wpwl_format_content( $attachment->post_content );
	} else if( $attachment instanceof WP_Term ) {
		$content = rox_wpwl_format_content( $attachment->description );
	} else {
		$content = rox_wpwl_format_content( $item->item_content );
	}
	return apply_filters( 'wishlist_single_item_content', $content, $item, $attachment );
}

function wishlist_item_product_meta( $content, Rox_List_Item $item ) {
	$attachment = $item->get_attachment();
	if( $attachment instanceof WP_Post ) {
		if( rox_is_woocommerce_activated() && $attachment->post_type == 'product' ) {
			global $product;
			$product = wc_get_product( $attachment );
			ob_start();
			woocommerce_template_loop_price();
			$price = sprintf( '<p class="rox-product-price">%s</p>', ob_get_clean() );
			$content .= PHP_EOL . $price . PHP_EOL;
			$content .= rox_wpwl_get_product_stock_status( $product );
			$content .= rox_wpwl_product_rating_html( $product );
			
		}
	}
	return $content;
}

function rox_wpwl_get_item_classes( \Rox_List_Item $item ) {
	$attachment = $item->get_attachment();
	$classes = array( 'list-item', $item->item_type );
	if( $attachment instanceof WP_Post ) {
		$classes[] = $attachment->post_type;
		if( $attachment->post_type == 'product' && rox_is_woocommerce_activated() ) {
			$classes[] = 'woocommerce';
		}
	} else if( $attachment instanceof WP_Term ) {
		$classes[] = $attachment->taxonomy;
	} else {
    }
	return $classes;
}

if( ! function_exists( 'wishlist_get_single_list_items' ) ) {
	/**
	 * Single List Items Loop
     * @return void
	 */
	function wishlist_get_single_list_items() {
		$listItems = rox_wpwl_get_list();
        if( isset( $listItems ) && count( $listItems ) > 0 ) {
            foreach( $listItems as $item ) {
                if( $item instanceof Rox_List_Item ) {
                    $classes = rox_wpwl_get_item_classes( $item );
            ?>
                <div class="<?php echo implode( ' ', $classes ); ?>" id="<?php echo $item->ID; ?>">
                    <?php do_action( 'before_single_wishlist_item_content', $item ); ?>
                    <div class="list-contents">
                        <h3 class="item-name"><?php echo get_list_item_title( $item ); ?></h3>
                        <div class="item-desc"><?php echo get_list_item_content( $item ); ?></div>
                        <div class="clearfix"></div>
                        <div class="item-actions">
                            <?php do_action( 'single_item_action_links', $item ); ?>
                        </div>
                    </div>
                    <?php do_action( 'after_single_wishlist_item_content', $item ); ?>
                </div><?php
                }
            }
        } else {
            rox_wpwl_get_template( 'content-no-item.php' );
        }
	}
}
/**
 * Callback for rendering post thumbnail before wishlist item content
 * @param Rox_List_Item $item
 * @return void
 */
function wishlist_item_thumbnail( \Rox_List_Item $item ) {
	$attachment = $item->get_attachment();
	$thumb = '';
	if( $attachment instanceof WP_Post ) {
		if( has_post_thumbnail( $attachment ) ) {
			$thumb = '<div class="item-thumb">'.get_the_post_thumbnail( $attachment, 'thumbnail', '' ).'</div>' . PHP_EOL;
		}
	}
	echo apply_filters( 'single_wishlist_item_thumbnail', $thumb, $item );
}
function wishlist_wc_sale_flash( $thumb, \Rox_List_Item $item ){
	$attachment = $item->get_attachment();
	$sale_flash = '';
	if( $attachment->post_type == 'product' && rox_is_woocommerce_activated() ) {
		$product = wc_get_product( $attachment );
		if ( $product->is_on_sale() ) {
			$sale_flash = apply_filters( 'wishlist_woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>', $attachment, $product );
		}
	}
    return $sale_flash . PHP_EOL . $thumb;
}
if( ! function_exists( 'wishlist_single_list_actions' ) ) {
	/**
	 * Single List Actions
     * @return void
	 */
	function wishlist_single_list_actions() {
	    if( ! is_user_logged_in() ) return;
		?>
		<div class="button list-status" title="<?php echo rox_get_wishlist_status( null, true ); ?>"><?php echo rox_get_wishlist_icon(); ?></div>
		<?php if( get_the_author_meta( 'ID' ) == get_current_user_id() ) { ?>
			<?php /* <a class="button button-add-item" data-list_id="<?php the_ID(); ?>" href="#"><?php echo esc_html( apply_filters( 'rox_wpwl_loop_add_item_link_text', __( 'Add Item', 'wordpress-wishlist' ) ) ); ?></a> */ ?>
			<a class="button button-list-settings" href="#"><?php
				$icon = rox_wpwl_get_svg( 'settings' );
				if( $icon ) echo apply_filters( '', sprintf( '<span class="rox-wpwl-icon icon-settings">%s</span>', $icon ) );
				echo esc_html( apply_filters( 'rox_wpwl_list_settings', __( 'Settings', 'wordpress-wishlist' ) ) ); ?></a>
			<a class="button button-delete-list" href="<?php echo rox_wpwl_get_list_delete_link(); ?>"><?php echo esc_html( apply_filters( 'rox_wpwl_remove_list_link_text', __( 'Delete This List', 'wordpress-wishlist' ) ) ); ?></a>
			<?php /* <a class="button button-duplicate-list" href="<?php echo rox_wpwl_get_list_duplicate_link(); ?>"><?php echo esc_html( apply_filters( 'rox_wpwl_loop_duplicate_list_link_text', __( 'Duplicate This List', 'wordpress-wishlist' ) ) ); ?></a> */ ?>
		<?php } else { ?>
			<?php /* <a class="button button-duplicate-list" href="<?php echo rox_wpwl_get_list_duplicate_link(); ?>"><?php echo esc_html( apply_filters( 'rox_wpwl_loop_copy_list_link_text', __( 'Copy This List', 'wordpress-wishlist' ) ) ); ?></a> */ ?>
		<?php } ?>
		<?php
	}
}

if( ! function_exists( 'wishlist_single_item_actions' ) ) {
	/**
     * Single List Item Actions
	 * @param Rox_List_Item $item
     * @return void
	 */
	function wishlist_single_item_actions( \Rox_List_Item $item ) {
		echo $item->get_attachment_link();
		if( is_user_logged_in() && rox_wpwl_can_user_edit_list() ) {
        ?>
        <a href="<?php echo rox_wpwl_get_item_delete_link( $item->ID ); ?>" class="button item-remove"><?php _e( 'Remove', 'wordpress-wishlist' ); ?></a>
        <?php
        }
	}
}

if( ! function_exists( 'wishlist_single_post_content' ) ) {
	function wishlist_single_post_content() {
		the_content();
	}
}

if( ! function_exists( 'wishlist_single_list_banner' ) ) {
	function wishlist_single_list_banner() {
		if( has_post_thumbnail() ){
			the_post_thumbnail( 'rox_wpwishlist_cover' );
		}
		/*<div class="list-author">
			<?php
			printf(
				'<span class="list-by sr-only">%1$s </span><a href="%2$s" class="url fn" rel="author">%3$s<span class="author-name sr-only">%4$s</span></a>',
				__( 'by', 'storefront' ),
				esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
				get_avatar( get_the_author_meta( 'ID' ), 150 ),
				esc_html( get_the_author() )
			);
			?>
		</div>*/
	}
}

function add_to_list_popup_title(){
	if( ! is_wp_wishlist() ) {
		printf( '<div class="pop-title"><h3>%s</h3></div>', __( 'Save to...', 'wordpress-wishlist' ) );
	}
}
function modify_wishlsit_popup_title() {
	if( is_wishlist() ) {
		printf( '<div class="pop-title"><h3>%s</h3></div>', __( 'Change Settings...', 'wordpress-wishlist' ) );
	}
}
function add_new_wishlist_popup_title() {
	if( is_wishlist_archive() ) {
		printf( '<div class="pop-title"><h3>%s</h3></div>', __( 'Create a new list...', 'wordpress-wishlist' ) );
	}
}

function add_new_wishlist_popup_content() {
	if( ! is_wishlist_archive() ) return;
	?>
	<div class="rox-pop-form" style="display: none;">
		<div class="list-name">
			<label for="list-name"><?php esc_html_e( 'Name', 'wordpress-wishlist' ); ?></label>
			<input type="text" id="list-name" name="list-name" required placeholder="<?php esc_attr_e( 'Enter a list name...', 'wordpress-wishlist' ); ?>">
			<span class="character-counter"><span class="counter-now">0</span>/<span class="counter-max"><?php echo rox_wpwl_get_option( 'list_name_max_length' ); ?></span></span>
		</div>
		<div class="list-privacy">
			<label for="list-privacy"><?php esc_html_e( 'Privacy', 'wordpress-wishlist' ); ?></label>
			<select id="list-privacy" name="list-privacy" required>
				<?php
				foreach( array( 'public' => __( 'Public', 'wordpress-wishlist' ), 'private' => __( 'Private', 'wordpress-wishlist' ) ) as $k => $v ) {
					printf( '<option value="%s">%s</option>', esc_attr( $k ), esc_html( $v ) );
				}
				?>
			</select>
		</div>
	</div>
	<?php
}

/**
 * Render Edit List form in single list page
 */
function modify_wishlist_popup_content() {
	if( ! is_wishlist() || ! rox_wpwl_can_user_edit_list() ) return;
	$post = get_post();
	$description = trim( $post->post_content );
	?>
	<div class="rox-pop-form edit-list">
  
		<div class="list-name">
			<label for="list-name"><?php esc_html_e( 'Name', 'wordpress-wishlist' ); ?></label>
			<input type="text" id="list-name" name="list-name" required placeholder="<?php esc_attr_e( 'Enter a list name...', 'wordpress-wishlist' ); ?>" value="<?php echo $post->post_title; ?>">
			<span class="character-counter"><span class="counter-now"><?php
				echo strlen( $post->post_title );
			?></span>/<span class="counter-max"><?php echo rox_wpwl_get_option( 'list_name_max_length' ); ?></span></span>
		</div>
		<div class="list-privacy">
			<label for="list-privacy"><?php esc_html_e( 'Privacy', 'wordpress-wishlist' ); ?></label>
			<select id="list-privacy" name="list-privacy" required>
				<?php
				foreach( array( 'public' => __( 'Public', 'wordpress-wishlist' ), 'private' => __( 'Private', 'wordpress-wishlist' ) ) as $k => $v ) {
					$status = ( $post->post_status == 'publish' ) ? 'public' : $post->post_status;
					printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $status, $k, false), esc_html( $v ) );
				}
				?>
			</select>
		</div>
		<div class="list-description">
			<label for="list-description"><?php esc_html_e( 'Short description about this list', 'wordpress-wishlist' ); ?></label>
			<textarea name="list-description" id="list-description"><?php echo $post->post_content; ?></textarea>
		</div>
        <br>
	</div>
	<?php
}

function add_new_wishlist_popup_footer() {
	if( ! is_wishlist_archive() ) return;
	?>
	<div class="rox-pop-actions">
		<a href="#" class="button rox_cancle"><?php _e( 'Cancle', 'wordpress-wishlist' ); ?></a>
		<a href="#" class="button rox_save"><?php _e( 'Create', 'wordpress-wishlist' ); ?></a>
	</div>
	<?php
}
function modify_wishlist_popup_footer() {
	if( is_wishlist() ) {
		?>
		<div class="rox-pop-actions">
			<a href="#" class="button rox_save"><?php _e( 'Update', 'wordpress-wishlist' ); ?></a>
			<a href="#" class="button rox_cancle"><?php _e( 'Cancle', 'wordpress-wishlist' ); ?></a>
		</div>
		<?php
	}
}

/**
 * Render create new list form in add to wishlist footer
 */
function wishlist_list_popup_add_to_new_list() {
	if( is_wp_wishlist () ) return;
	$icon = rox_wpwl_get_svg( 'add-new-list' );
	$icon = ( $icon )? sprintf( '<span class="rox-wpwl-icon icon-add-new-list">%s</span>', $icon ) : '';
	printf( '<div class="pop-new-list"><a href="#">%s%s</a></div>', $icon, __( 'Create new list', 'wordpress-wishlist' ) );
	?>
	<div class="rox-pop-form add-new-list" style="display: none;">
		<div class="list-name">
			<label for="list-name"><?php esc_html_e( 'Name', 'wordpress-wishlist' ); ?></label>
			<input type="text" id="list-name" name="list-name" required placeholder="<?php esc_attr_e( 'Enter a list name...', 'wordpress-wishlist' ); ?>">
			<span class="character-counter"><span class="counter-now">0</span>/<span class="counter-max"><?php echo rox_wpwl_get_option( 'list_name_max_length' ); ?></span></span>
		</div>
		<div class="list-privacy">
			<label for="list-privacy"><?php esc_html_e( 'Privacy', 'wordpress-wishlist' ); ?></label>
			<select id="list-privacy" name="list-privacy" required>
				<?php
				foreach( array( 'public' => __( 'Public', 'wordpress-wishlist' ), 'private' => __( 'Private', 'wordpress-wishlist' ) ) as $k => $v ) {
					printf( '<option value="%s">%s</option>', esc_attr( $k ), esc_html( $v ) );
				}
				?>
			</select>
		</div>
		<div class="rox-pop-actions">
			<a href="#" class="button rox_cancle"><?php _e( 'Cancle', 'wordpress-wishlist' ); ?></a>
			<a href="#" class="button rox_save"><?php _e( 'Create', 'wordpress-wishlist' ); ?></a>
		</div>
	</div>
	<?php
}

/**
 * Get Ajax POPUP Items for add to wishlist button
 *
 * @param int|null $user_ID     default to current user
 * @param int $object_ID
 * @param string $item_type
 *
 * @return string
 */
function rox_wpwl_get_popup_items( $user_ID = NULL, $object_ID, $item_type ) {
    $output = array();
	if( empty( $user_ID ) ) $user_ID = get_current_user_id();
	$lists = rox_wpwl_get_users_wishlists( $user_ID, 'edit' );
	$lists = get_posts( array( 'posts_per_page' => -1, 'post_type' => RoxWPWLPostTypes::$config['post-type'], 'post_status' => array( 'publish', 'private', ), 'post__in' => $lists  ) );
	foreach(  $lists as $list ) {
	    if( $list instanceof WP_Post ) {
		    $checked = checked( is_object_in_wishlist( $object_ID, $list->ID, $item_type ), true, false );
	        $list_status = rox_get_wishlist_status( $list );
		    $list_icon = rox_get_wishlist_icon( $list );
		    $format = '<label><span class="list-name"><input class="rox_wishlist_add" type="checkbox" name="wp_wishlist" value="%s"'.$checked.'> %s</span> <span class="list-extra">%s</span></label>';
		    $output[] = apply_filters( 'wishlist_popup_item', sprintf( '<li>'.$format.'</li>', $list->ID, $list->post_title, $list_icon ), $list->ID, $list_status );
        }
    }
    $output = apply_filters( 'wishlist_popup_items', $output );
	if( empty( $output ) ) {
	    return sprintf( '<p class="rox-alert rox-info pop-new-list">%s</p>', __( 'Please create a list first.', 'wordpress-wishlist' ) );
    }
    return '<ul>' . implode( PHP_EOL, $output ) . '</ul>';
}

if ( ! function_exists( 'rox_wishlist_page_title' ) ) {
	
	/**
	 * Page Title function.
	 *
	 * @param  bool $echo Should echo title.
	 * @return string
	 */
	function rox_wishlist_page_title( $echo = true ) {
		if ( is_search() ) {
			/* translators: %s: search query */
			$page_title = sprintf( __( 'Search results: &ldquo;%s&rdquo;', 'wordpress-wishlist' ), get_search_query() );
			if ( get_query_var( 'paged' ) ) {
				/* translators: %s: page number */
				$page_title .= sprintf( __( '&nbsp;&ndash; Page %s', 'wordpress-wishlist' ), get_query_var( 'paged' ) );
			}
		} elseif ( is_tax() ) {
			$page_title = single_term_title( '', false );
		} else {
			$wishlist_page_id = rox_wpwl_get_option( 'wishlist_archive' );
			$page_title   = get_the_title( $wishlist_page_id );
		}
		$page_title = apply_filters( 'rox_wpwl_page_title', $page_title );
		if ( $echo ) {
			echo $page_title;
		} else {
			return $page_title;
		}
	}
}

function rox_wpwl_format_content( $raw_content ) {
	$content = apply_filters( 'the_content', $raw_content );
	$content = str_replace( ']]>', ']]&gt;', $content );
	return apply_filters( 'rox_wpwl_format_content', $content, $raw_content );
}

if ( ! function_exists( 'wishlist_taxonomy_archive_description' ) ) {
	
	/**
	 * Show an archive description on taxonomy archives.
	 * @return void
	 */
	function wishlist_taxonomy_archive_description() {
		if ( is_product_taxonomy() && 0 === absint( get_query_var( 'paged' ) ) ) {
			$term = get_queried_object();
			if ( $term && ! empty( $term->description ) ) {
				echo '<div class="rox-wpwl-term-description">' . rox_wpwl_format_content( $term->description ) . '</div>';
			}
		}
	}
}
if ( ! function_exists( 'wishlist_archive_description' ) ) {
	/**
	 * Show Wishlist page description on wishlist archives.
	 * @return void
	 */
	function wishlist_archive_description() {
		// Don't display the description on search results page.
		if ( is_search() ) return;
		if ( is_post_type_archive( RoxWPWLPostTypes::$config['post-type'] ) && in_array( absint( get_query_var( 'paged' ) ), array( 0, 1 ), true ) ) {
			$wishlist_archive = get_post( (int) rox_wpwl_get_option( 'wishlist_archive' ) );
			if ( $wishlist_archive ) {
				$description = rox_wpwl_format_content( $wishlist_archive->post_content );
				if ( $description ) {
					echo '<div class="rox-wpwl-archive-description">' . $description . '</div>';
				}
			}
		}
	}
}
if( ! function_exists( 'wishlist_no_lists_found' ) ) {
	function wishlist_no_lists_found() {
		rox_wpwl_get_template_part( 'content', 'no-lists' );
	}
}

if( ! function_exists( 'rox_wpwl_get_sidebar' ) ) {
	function rox_wpwl_get_sidebar() {
		rox_wpwl_get_template( 'wishlist-sidebar.php' );
	}
}