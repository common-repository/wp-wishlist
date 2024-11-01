<?php
if( ! function_exists( 'add_action' ) ) {
	die();
}
get_header( 'wishlist' );
rox_wpwl_get_template( 'content-single-wishlist.php' );
get_footer( 'wishlist' );