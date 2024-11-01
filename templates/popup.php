<?php
/**
 * Created by PhpStorm.
 * User: blackhunter
 * Date: 2018-12-31
 * Time: 18:19
 */

if( ! function_exists( 'add_action' ) ) {
	die();
}
?><div class="rox_wishlist_pop">
	<div class="back_drop"></div>
	<div class="rox_pop_container">
        <div class="pop-header">
			<div class="pop-title"><?php do_action( 'wishlist_popup_header' ); ?></div>
        </div>
		<div class="pop_contents"><?php do_action( 'wishlist_popup_body' ); ?></div>
		<div class="pop-footer"><?php do_action( 'wishlist_popup_footer' ); ?></div>
	</div>
</div>