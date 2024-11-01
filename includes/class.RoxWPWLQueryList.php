<?php
if( ! function_exists( 'add_action' ) ) {
	die();
}

class RoxWPWLQueryList {
	protected $type;
	protected $args = array();
	public function __construct( $args = array(), $type ) {
		$this->type = $type;
		$this->args = $args;
		if( ! isset( $this->args['paged'] ) ) {
			$this->args['paged'] = 1;
		}
	}
	
	protected function __get_lists() {
		$listArgs = array(
			'post_type' => RoxWPWLPostTypes::$config['post-type'],
			'posts_per_page' => rox_wpwl_get_option( 'posts_per_page' ),
			'post_status' => array( 'any' ),
		);
		$usersList = array();
		$publicLists = new WP_Query( array(
			'post_type' => RoxWPWLPostTypes::$config['post-type'],
			'posts_per_page' => -1,
			'post_status' => array( 'publish' ),
			'fields' => 'ids',
		) );
		if( is_user_logged_in() ) {
		    $usersList = rox_wpwl_get_users_wishlists( get_current_user_id(), 'any' );
		}
		$viewAbleLists = array_merge( $usersList, $publicLists->get_posts() );
		$viewAbleLists = array_filter( $viewAbleLists );
		$viewAbleLists = array_map( 'absint', $viewAbleLists );
		$viewAbleLists = array_unique( $viewAbleLists, SORT_NUMERIC );
		$listArgs['post__in'] = $viewAbleLists;
		$listArgs = wp_parse_args( $this->args, $listArgs );
		$listQuery = new wp_query( apply_filters( "wishlist_archive_{$this->type}_loop_args", $listArgs ) );
		return $listQuery;
	}
	
	protected function __loop() {
	    global $wp_query;
		$_wp_query = $wp_query;
		$wp_query = $this->__get_lists();
		ob_start();
		do_action( 'wishlist_before_main_content' );
		if( $wp_query->have_posts() ) {
			while( $wp_query->have_posts() ) {
				$wp_query->the_post();
				rox_wpwl_get_template( 'content-wishlist.php' );
				?><div class="clearfix"></div><?php
			}
			$pagenation_args = array(
				'total'   => $wp_query->max_num_pages,
				'current' => $this->args['paged'],
				'base'    => esc_url_raw( add_query_arg( 'wishlist-page', '%#%', false ) ),
				'format'  => '?wishlist-page=%#%',
			);
			rox_wpwl_get_template( 'pagination.php', $pagenation_args );
			wp_reset_query();
		} else {
			rox_wpwl_get_template( 'content-no-wishlists.php' );
		}
		$classes = apply_filters( "wishlist_{$this->type}_wrapper_classes", array( 'wishlist', 'wishlist-archive'  ) );
		$wp_query = $_wp_query;
		return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . ob_get_clean() . '</div>';
	}
	public function get_content() {
		return $this->__loop();
	}
}
// End of file class.RoxWPWLQueryList.php