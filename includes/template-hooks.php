<?php
if( ! function_exists( 'add_action' ) ) {
	die();
}

// front end messages
add_action( 'wishlist_before_main_content', 'rox_wpwl_print_forntend_messages', 10 );
add_action( 'rox_wpwl_before_single_wishlist', 'rox_wpwl_print_forntend_messages', 10 );

// archive page

add_action( 'wishlist_archive_description', 'wishlist_taxonomy_archive_description', 10 );
add_action( 'wishlist_archive_description', 'wishlist_archive_description', 10 );
add_action( 'wishlist_no_lists_found', 'wishlist_no_lists_found', 10 );
add_action( 'wishlist_sidebar', 'rox_wpwl_get_sidebar', 10 );

// single wishlist

add_action( 'wishlist_single_list_cover', 'wishlist_single_list_banner', 10 );
add_action( 'wishlist_single_list_summery', 'wishlist_single_post_content', 10 );
add_action( 'wishlist_single_list_actions', 'wishlist_single_list_actions', 10 );
add_action( 'wishlist_single_list_items', 'wishlist_get_single_list_items', 10 );
add_action( 'rox_wpwl_item_object_title', 'wishlist_single_list_item_attachment_link_title', 10, 3 );
add_action( 'rox_wpwl_item_attachment_link_attributes', 'wishlist_single_list_item_attachment_attributes', 10, 3 );
add_action( 'rox_wpwl_item_attachment_html', 'wishlist_single_list_item_attachment_html', 10, 3 );

// single list item
add_action( 'before_single_wishlist_item_content', 'wishlist_item_thumbnail', 10, 1 );
add_filter( 'single_wishlist_item_thumbnail', 'wishlist_wc_sale_flash', 10, 2 );
add_filter( 'wishlist_single_item_content', 'wishlist_item_product_meta', 10, 2 );
add_action( 'single_item_action_links', 'wishlist_single_item_actions', 10, 1 );

// list popup
add_action( 'wishlist_popup_header', 'add_to_list_popup_title', 10 );
add_action( 'wishlist_popup_header', 'modify_wishlsit_popup_title', 10 );
add_action( 'wishlist_popup_header', 'add_new_wishlist_popup_title', 10 );
add_action( 'wishlist_popup_body', 'add_new_wishlist_popup_content' );
add_action( 'wishlist_popup_body', 'modify_wishlist_popup_content' );
add_action( 'wishlist_popup_footer', 'add_new_wishlist_popup_footer', 10 );
add_action( 'wishlist_popup_footer', 'modify_wishlist_popup_footer', 10 );
add_action( 'wishlist_popup_footer', 'wishlist_list_popup_add_to_new_list', 10 );
// End of file template-hooks.php