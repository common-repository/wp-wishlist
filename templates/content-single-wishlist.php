<?php
if( ! function_exists( 'add_action' ) ) {
	die();
}

do_action( 'rox_wpwl_before_single_wishlist' );

if ( post_password_required() ) {
	echo get_the_password_form();
	return;
}
?>
<div id="wishlist-<?php the_ID(); ?>" class="">
	<div class="list-cover">
		<?php do_action( 'wishlist_single_list_cover' ); ?>
	</div>
    <div class="list-actions">
		<?php do_action( 'wishlist_single_list_actions' ); ?>
    </div>
	<div class="list-summmery">
		<?php do_action( 'wishlist_single_list_summery' ); ?>
	</div>
    <div class="list-section"><?php echo apply_filters( 'single_list_item_section_title', sprintf( '<h2>%s</h2>', __( 'List items', 'wordpress-wishlist' ) ) ); ?></div>
	<div class="list-items">
		<?php do_action( 'wishlist_single_list_items' ); ?>
	</div>
	<?php
		do_action( 'wishlist_after_single_list_items' );
	?>
</div>
<?php
do_action( 'rox_wpwl_after_single_wishlist' );
// end of file content-single-wishlist.php