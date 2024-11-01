<?php
if( ! function_exists( 'add_action' ) ) {
	die();
}
get_header( 'wishlist' );

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'wishlist_before_main_content' );

?>
	<header class="rox-wpwl-archive">
		<?php if ( apply_filters( 'wishlist_show_page_title', true ) ) { ?>
			<h1 class="rox-wpwl-archive-title page-title"><?php rox_wishlist_page_title(); ?></h1>
		<?php } ?>
		<?php
		/**
		 * Hook: wishlist_archive_description.
		 *
		 * @hooked wishlist_taxonomy_archive_description - 10
		 * @hooked wishlist_archive_description - 10
		 */
		do_action( 'wishlist_archive_description' );
		?>
	</header>
<?php
if ( have_posts() ) {
	do_action( 'wishlist_before_archive_loop' );
?>
	<div class="wishlists">
<?php
	while ( have_posts() ) {
		the_post();
		rox_wpwl_get_template_part( 'content', 'wishlist' );
	}
?>
	</div>
<?php
	do_action( 'wishlist_after_archive_loop' );
} else {
	/**
	 * Hook: wishlist_no_lists_found.
	 *
	 * @hooked wishlist_no_lists_found - 10
	 */
	do_action( 'wishlist_no_lists_found' );
	
}

do_action( 'wishlist_after_main_content' );

/**
 * Hook: wishlist_sidebar.
 *
 * @hooked rox_wpwl_get_sidebar - 10
 */
do_action( 'wishlist_sidebar' );

get_footer( 'wishlist' );