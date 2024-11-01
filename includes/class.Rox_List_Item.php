<?php
/**
 * Created by PhpStorm.
 * User: blackhunter
 * Date: 2018-12-23
 * Time: 17:27
 */
if( ! function_exists( 'add_action' ) ) {
	die();
}
class Rox_List_Item {
	/**
	 * Item ID.
	 * @var int
	 */
	public $ID;
	
	/**
	 * ID of wishlist post.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	public $post_id = 0;
	
	/**
	 * The post's title.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $item_title = '';
	
	/**
	 * The post's content.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $item_content = '';
	
	public $item_type = 'generic';
	
	public $object_id = 0;
	
	/**
	 * Item Created At
	 * @var string
	 */
	public $created_at = '0000-00-00 00:00:00';
	
	/**
	 * Modified/Updated At
	 * @var string
	 */
	public $updated_at = '0000-00-00 00:00:00';
	
	public $deleted;
	
	/**
	 * A field used for ordering posts.
	 *
	 * @var int
	 */
	public $menu_order = 0;
	
	/**
	 * Retrieve WP_Post instance.
	 *
	 * @since 3.5.0
	 * @static
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $item_id Post ID.
	 * @return Rox_List_Item|false Rox_List_Item object, false otherwise.
	 * @throws Exception
	 */
	public static function get_instance( $item_id ) {
		global $wpdb;
		$item_id = (int) $item_id;
		if ( ! $item_id ) return false;
		$_item = wp_cache_get( $item_id, '__rox_items' );
		if ( ! $_item ) {
			$_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".RoxWPWL()->get_tables( 'item' )." WHERE ID = %d LIMIT 1", $item_id ) );
			if ( ! $_item ) return false;
			wp_cache_add( $_item->ID, $_item, '__rox_items' );
		}
		return new Rox_List_Item( $_item );
	}
	
	/**
	 * Rox_List_Item constructor.
	 * @param Rox_List_Item|object $item Rox_List_Item object.
	 * @throws Exception
	 */
	public function __construct( $item ) {
		foreach ( get_object_vars( $item ) as $key => $value ) {
			if( $key == 'created_at' || $key == 'updated_at' ) {
				$value = new DateTime( $value );
			}
			if( in_array( $key, array( 'ID', 'post_id', 'object_id', 'deleted', 'menu_order' ) ) ) {
				$value = (int) $value;
			}
			$this->$key = $value;
		}
	}
	
	/**
	 * Get Attached Object
	 *
	 * @return WP_Post|WP_Term|null
	 */
	public function get_attachment() {
		$attachment = NULL ;
		if( $this->item_type == 'post' ) {
			$post = get_post( $this->object_id );
			if( ! is_wp_error( $post ) ) {
				if( $post->post_type !== RoxWPWLPostTypes::$config['post-type'] ) {
					$currentUser = wp_get_current_user();
					$list = get_post( $this->post_id );
					if( $post->post_status == 'publish' || ( $currentUser->ID == $list->post_author && $currentUser->ID == $post->post_author ) ) {
						return $post;
					}
				}
				
			}
		}
		if( $this->item_type == 'taxonomy_term' ) {
			$term = get_term( $this->object_id );
			if( ! is_wp_error( $term ) ) {
				return $term;
			}
		}
		return NULL;
	}
	
	/**
	 * Get Preview/Link html of attached object (WP_Post or WP_Term)
	 * @param array $classes
	 * @param bool $new_tab
	 * @return NULL|string
	 */
	public function get_attachment_link( $classes = array(), $new_tab = true ) {
		$attachment = $this->get_attachment();
		if( ! $attachment ) return NULL;
		$classes = (array) $classes;
		$classes[] = 'wishlist_item_link';
		$classes[] = 'item_type_' . $this->item_type;
		$title = $link = $attr = $suffix = '';
		if( $attachment instanceof WP_Post ) {
			$title = sprintf( __( 'View “%s”', 'wordpress-wishlist' ), get_the_title( $attachment->ID ) );
			$suffix = ( $attachment->post_status != 'publish' )? sprintf( ' <b class="post-status">&mdash; %s</b>', rox_get_translate_post_status( $attachment->post_status ) ) : '';
			$link = get_permalink( $attachment->ID );
			$classes[] = $attachment->post_type;
			$classes[] = $attachment->post_status;
		}
		if( $attachment instanceof WP_Term ) {
			$link = get_term_link( $attachment->term_id );
			$title = sprintf( __( 'View “%s”', 'wordpress-wishlist' ), $attachment->name );
			$classes[] = $attachment->taxonomy;
		}
		if( empty( $link ) && empty( $title ) ) {
			$classes[] = 'object_404';
			$classes[] = $this->item_type;
			return sprintf( '<span class="%s">%s</span>',
				implode( ' ', apply_filters( 'rox_wpwl_object_link_classes', $classes ) ),
				__( 'Object Not Found!', 'wordpress-wishlist' ) );
		}
		$title = sprintf( apply_filters( 'rox_wpwl_item_object_title', $title, $this->ID, $attachment ), $attachment->name );
		$classes[] = 'button';
		$classes = apply_filters( 'rox_wpwl_object_link_classes', $classes );
		$suffix = apply_filters( 'rox_wpwl_item_object_title_suffix', $suffix, $attachment );
		$attributes = array( 'class' => implode( ' ', $classes ), );
		$attributes['href'] = $link;
		$attributes['aria-label'] = $title;
		if( $new_tab ) $attributes['target'] = '_blank';
		$attributes = array_filter( apply_filters( 'rox_wpwl_item_attachment_link_attributes', $attributes, $this->ID, $attachment ) );
		foreach( $attributes as $k => $v ) {
			$attr .= " {$k}=\"{$v}\"";
		}
		return apply_filters( 'rox_wpwl_item_attachment_html', sprintf( '<a %s>%s%s</a>', trim( $attr ), $title, $suffix ), $this->ID, $attachment );
	}
	/**
	 * Convert object to array.
	 * @return array Object as array.
	 */
	public function to_array() {
		$post = get_object_vars( $this );
		if( $post['created_at'] instanceof DateTime ) $post['created_at'] = $post['created_at']->format( 'Y-m-d H:i:s' );
		if( $post['updated_at'] instanceof DateTime ) $post['updated_at'] = $post['updated_at']->format( 'Y-m-d H:i:s' );
		return $post;
	}
}