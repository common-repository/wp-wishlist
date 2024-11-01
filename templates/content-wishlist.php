<?php
if( ! function_exists( 'add_action' ) ) {
	die();
}
?>
<div class="wishlist-item <?php echo has_post_thumbnail()? 'has_thumb' : ''; ?>">
	<?php if( has_post_thumbnail() ) { ?>
	<div class="thumb">
		<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
			<?php the_post_thumbnail( 'thumbnail' ); ?>
		</a>
	</div>
	<?php } ?>
	<div class="summery">
		<div class="list-title">
			<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		</div>
		<div class="info-meta">
            <div class="list-status" title="<?php echo rox_get_wishlist_status( null, true ); ?>"><?php echo rox_get_wishlist_icon(); ?></div>
			<div class="item-count">
				<a href="<?php the_permalink(); ?>#items"><?php printf( _n( '%d Item', '%d Items', wishlist_count_list_items(), 'wordpress-wishlist' ), wishlist_count_list_items() ); ?></a>
			</div>
			<?php if( rox_wpwl_get_option( 'enable_reviews' ) == 'yes' ) { ?>
			<div class="comment-count">
				<?php
				if ( ! post_password_required() && ( comments_open() || 0 !== intval( get_comments_number() ) ) ) {
					printf(
						'<a href="%1$s">%2$s</a>',
						esc_url( get_comments_link() ),
						get_comments_number_text()
					);
				}
				?>
			</div>
			<?php } ?>
            <div class="list-author">
                <?php
                printf(
                    '<span class="list-by">%1$s </span><a href="%2$s" class="url fn" rel="author"><span class="author-name">%3$s</span></a>',
                    __( 'by', 'wordpress-wishlist' ),
                    esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
                    esc_html( get_the_author() )
                );
                ?>
            </div>
		</div>
	</div>
	<?php if( is_user_logged_in() ) { ?>
	<div class="list-actions">
		<?php if( get_the_author_meta( 'ID' ) == get_current_user_id() ) { ?>
		<a class="button button-delete-list" href="<?php echo rox_wpwl_get_list_delete_link(); ?>"><?php echo esc_html( apply_filters( 'rox_wpwl_loop_remove_list_link_text', __( 'Remove This List', 'wordpress-wishlist' ) ) ); ?></a>
		<?php /* <a class="button button-duplicate-list" href="<?php echo rox_wpwl_get_list_duplicate_link(); ?>"><?php echo esc_html( apply_filters( 'rox_wpwl_loop_duplicate_list_link_text', __( 'Duplicate This List', 'wordpress-wishlist' ) ) ); ?></a> */ ?>
		<?php } else { ?>
		<?php /* <a class="button button-duplicate-list" href="<?php echo rox_wpwl_get_list_duplicate_link(); ?>"><?php echo esc_html( apply_filters( 'rox_wpwl_loop_copy_list_link_text', __( 'Copy This List', 'wordpress-wishlist' ) ) ); ?></a> */ ?>
		<?php } ?>
	</div>
	<?php } ?>
</div>