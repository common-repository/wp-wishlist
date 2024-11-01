<?php
if( ! function_exists( 'add_action' ) ) {
	die();
}

class RoxWPWLShortCodes {
	public static function init() {
		add_shortcode( 'rox_single_wishlist_page', array( __CLASS__, 'rox_single_wishlist_page') );
		add_shortcode( 'wp_wishlist', array( __CLASS__, 'rox_wishlist_button' ) );
	}
	public static function rox_single_wishlist_page( $atts ) {
		if( empty( $atts ) ) return '';
		if ( ! isset( $atts['id'] ) || empty( $atts['id'] ) ) return '';
		if( ! isset( $atts['status'] ) ) {
			$atts['status'] = 'publish';
        } else {
			$atts['status'] = explode( ',', $atts['status'] );
		}
		$args = array(
			'posts_per_page'	  => 1,
			'post_type'		   => RoxWPWLPostTypes::$config['post-type'],
			'post_status'		 => $atts['status'],
			'ignore_sticky_posts' => 1,
			'no_found_rows'	   => 1,
		);
		$args['p'] = absint( $atts['id'] );
		$single_list = new WP_Query( $args );
		
		$single_list->is_single = true;
		ob_start();
		global $wp_query;
		$previous_wp_query = $wp_query;
		$wp_query          = $single_list;
		while ( $single_list->have_posts() ) {
			$single_list->the_post();
			?>
			<div class="single-list">
				<?php rox_wpwl_get_template_part( 'content', 'single-wishlist' ); ?>
			</div>
			<?php
		}
		$wp_query = $previous_wp_query;
		wp_reset_postdata();
		$classes = apply_filters( "wishlist_single_list_wrapper_classes", array( 'wishlist', 'wishlist-single'  ) );
		return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . ob_get_clean() . '</div>';
	}
	public static function rox_wishlist_button( $attributes = array() ) {
		$attributes = shortcode_atts( array(
				'style'     => rox_wpwl_get_option( 'wishlist_button_style' ),
				'text'      => __( 'Add To Wishlist', 'wordpress-wishlist' ),
				'id'        => '',
				'type'      => '',
			), $attributes, 'wp_wishlist' );
		
		$attrs = array(
                'class' => array( 'button', 'rox-wishlist-button', $attributes['style'] ),
        );
		global  $wp_query;
		$object = $wp_query->get_queried_object();
//		$this->queried_object = null;
//		$this->queried_object_id = null;
        if( ! empty( $attributes['type'] ) ) {
            $attrs['data-type'] = esc_attr( $attributes['type'] );
        } else {
            if( $wp_query->queried_object !== NULL ) {
                if( $wp_query->queried_object instanceof WP_Post || $wp_query->queried_object instanceof WC_Product ) $attrs['data-type'] = 'post';
                else if( $wp_query->queried_object instanceof WP_Term ) $attrs['data-type'] = 'taxonomy_term';
                else if( $wp_query->queried_object instanceof WP_User ) $attrs['data-type'] = 'user';
            } else {
                // wishlist button shortcode invoke from loop
                global $post;
                if( $post instanceof WP_Post || $post instanceof WC_Product ) {
                    $attrs['data-type'] = 'post';
                }
            }
        }
        if( ! empty( $attributes['data_id'] ) ) {
            $attrs['data-id'] = esc_attr( $attributes['id'] );
        } else {
            if( $wp_query->queried_object_id !== NULL ) {
                $attrs['data-id'] = $wp_query->queried_object_id;
            } else {
                global $post;
                if( $post instanceof WP_Post || $post instanceof WC_Product) {
                    $attrs['data-id'] = $post->ID;
                }
            }
        }

		if( $attributes['style'] == 'style-2' ) {
		    $attrs['aria-label'] = esc_attr( $attributes['text'] );
			$title = sprintf( '<span class="rox-wpwl-icon icon-wishlist">%s</span>', rox_wpwl_get_svg( 'add_to_playlist_01' ) );
		} else if( $attributes['style'] == 'style-3' ) {
			$attrs['aria-label'] = esc_attr( $attributes['text'] );
			$title = sprintf( '<span class="rox-wpwl-icon icon-heart">%s</span>', rox_wpwl_get_svg( 'add_to_playlist_02' ) );
		} else {
		    // style_1
			$title = $attributes['text'];
        }
		if( is_object_in_users_wishlist( $attrs['data-id'], $attrs['data-type'] ) ) {
		    $attrs['class'][] = 'item_in_list';
        }
		$attrs = apply_filters( 'wp_wishlist_button_attributes', $attrs );
		$attrs['href'] = '#';
		$title = apply_filters( 'wp_wishlist_button_text', $title );
		$attributes = '';
		foreach( $attrs as $k=>$v ) {
			if( is_array( $v ) ) $v = implode( ' ', $v );
			$attributes .= " {$k}=\"{$v}\"";
		}
		$attributes = trim( $attributes );
		return PHP_EOL . sprintf( '<a %s>%s</a>', $attributes, $title );
	}
}

RoxWPWLShortCodes::init();
// End of file class.RoxWPWLShortCodes.php