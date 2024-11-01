<?php
/**
 * Created by PhpStorm.
 * User: blackhunter
 * Date: 2018-12-18
 * Time: 11:59
 */
if( ! function_exists( 'add_action' ) ) {
	die();
}
if( class_exists( 'RoxWPWLMetaBoxes', false ) ) {
	RoxWPWLMetaBoxes::init();
	return;
}
class RoxWPWLMetaBoxes {
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, '__add_meta_box' ) );
//		add_action( 'save_post', array( __CLASS__, '__save_meta_box' ) );
		add_action( 'save_post_' . RoxWPWLPostTypes::$config['post-type'], array( __CLASS__, '__save_meta_box' ) );
		add_action( 'post_submitbox_minor_actions', array( __CLASS__, '__render_list_share_button' ) );
	}
	
	/**
	 * Add Metabox to Wishlist Editor Page
	 */
	public static function __add_meta_box() {
		add_meta_box( ROX_WPWL_PLUGIN_NAME . '_item_metabox', esc_html__( 'Wishlist Items', 'wordpress-wishlist' ), array( __CLASS__, '__render_item_meta_box' ), RoxWPWLPostTypes::$config['post-type'], 'advanced', 'high' );
		// add_meta_box( ROX_WPWL_PLUGIN_NAME . '_list_shared', esc_html__( 'Share with others', 'wordpress-wishlist' ), array( __CLASS__, '__render_list_share_box' ), RoxWPWLPostTypes::$config['post-type'], 'side', 'high' );
	}
	
	public static function __render_list_share_button( \WP_Post $post ) {
		if( $post->post_type == RoxWPWLPostTypes::$config['post-type'] ) {
			?>
			<style>#rox_wpwl_share { padding: 6px 0 0; width: 100%; } </style>
			<div class="clear"></div>
			<?php if( 'yes' == rox_wpwl_get_option( 'enable_sharing', 'advanced' ) ) { ?>
			<div id="rox_wpwl_share">
				<button class="share_list button-primary"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span> <?php _e('Share', 'wordpress-wishlist'); ?></button>
			</div>
			<?php
			}
		}
	}
	
	/**
	 *
	 * Render Wishlist Editor Metabox
	 * @param WP_Post $post
	 * @return void
	 */
	private static function __render_list_share_box( \WP_Post $post ) {
		if( 'yes' !== rox_wpwl_get_option( 'enable_sharing', 'advanced' ) ) return;
		?>
		<div id="wishlist_contributors" class="rox_wpwl_items" style="display: none;">
			<div class="add_contributor">
				<label for="add_contributor"><?php _e( 'People', 'wordpress-wishlist' ); ?></label>
				<input type="text" id="add_contributor" placeholder="<?php _e( 'Enter User ID, Name or Email Addresses...', 'wordpress-wishlist' ); ?>">
				<span class="permission">
					<label class="view"><input type="radio" name="list_permission"> <span class="dashicons dashicons-visibility" aria-hidden="true"></span> <span class="screen-reader-text"><?php _e( 'Can View', 'wordpress-wishlist' ) ?></span></label>
					<label class="edit"><input type="radio" name="list_permission"> <span class="dashicons dashicons-edit" aria-hidden="true"></span> <span class="screen-reader-text"><?php _e( 'Can Edit', 'wordpress-wishlist' ) ?></span></label>
				</span>
			</div>
			<div class="contributors"></div>
		</div>
		<?php
		
		// contributors Shared with Md. Jaed Mosharraf, shiti swaranya and 7 others
		// Enter names or email addresses...

	}
	/**
	 * Render Wishlist Editor Metabox
	 * @param WP_Post $post
	 * @return void
	 */
	public static function __render_item_meta_box( \WP_Post $post ) {
		$listItems = rox_wpwl_get_list( $post );
		$itemTypes = rox_wpwl_item_types();
		?>
		<div class="rox_wpwl_items" data-group="<?php $post->ID; ?>">
			<a href="#" class="button add-item"><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Add Item', 'wordpress-wishlist' ); ?></a>
			<?php do_action( 'rox_wpwl_admin_edit_before_items', $post ); ?>
			<hr class="clear">
			<ol class="wishlist-items sortable">
				<?php if( $listItems && count( $listItems ) > 0  ) { ?>
					<?php
					$i = 1;
					foreach( $listItems as $item ) {
						$item->menu_order = $i;
						$i++;
					?>
						<li id="item_<?php echo $item->menu_order; ?>" class="<?php echo $item->deleted == 1 ? 'deleted': ''; ?>" data-idx="<?php echo $item->menu_order; ?>">
							<?php do_action( 'rox_wpwl_admin_edit_before_item', $item ); ?>
							<a class="move-item" href="#" aria-label="<?php esc_attr_e( 'Move Item', 'wordpress-wishlist' ); ?>"><span class="dashicons dashicons-move"></span></a>
							<a class="edit-item" href="#" aria-label="<?php esc_attr_e( 'Edit Item', 'wordpress-wishlist' ); ?>"><span class="dashicons dashicons-edit"></span></a>
							<a class="remove-item" href="#" aria-label="<?php esc_attr_e( 'Remove Item', 'wordpress-wishlist' ); ?>"><span class="dashicons dashicons-trash"></span></a>
							<a class="undo-delete" href="#" aria-label="<?php esc_attr_e( 'Restore Item', 'wordpress-wishlist' ); ?>"><span class="dashicons dashicons-undo"></span></a>
							<input type="hidden" id="item_order_<?php echo $item->menu_order; ?>" name="items[<?php echo $item->menu_order; ?>][menu_order]" value="<?php echo $item->menu_order; ?>">
							<input type="hidden" id="item_ID_<?php echo $item->menu_order; ?>" name="items[<?php echo $item->menu_order; ?>][ID]" value="<?php echo $item->ID; ?>">
							<input type="hidden" id="item_deleted_<?php echo $item->menu_order; ?>" name="items[<?php echo $item->menu_order; ?>][deleted]" value="<?php echo $item->deleted; ?>">
							<div class="item-group">
								<div class="group title" style="<?php echo ( $item->item_type !== 'generic' )? 'display: none;': ''; ?>">
									<label for="item_title_<?php echo $item->menu_order; ?>"><?php esc_html_e( 'Item Title', 'wordpress-wishlist' ); ?></label>
									<input class="regular-text" type="text" id="item_title_<?php echo $item->menu_order; ?>" name="items[<?php echo $item->menu_order; ?>][item_title]" value="<?php echo esc_attr( $item->item_title ); ?>" placeholder="<?php esc_attr_e( 'Type a title', 'wordpress-wishlist' ); ?>">
								</div>
								<div class="group-half content_type">
									<label  for="item_type"><?php esc_html_e( 'Item Type', 'wordpress-wishlist' ); ?></label>
									<select name="items[<?php echo $item->menu_order; ?>][item_type]" id="item_type_<?php echo $item->menu_order; ?>"><?php
										foreach( $itemTypes as $k => $v ) {
											printf( '<option value="%s"%s>%s</option>', $k, selected( $k, $item->item_type, false ), $v );
										}
									?></select>
								</div>
								<div class="group-half" style="margin-right: 0;">
									<label for="item_updated_at_<?php echo $item->menu_order; ?>"><?php _e( 'Updated At', 'wordpress-wishlist' ); ?></label>
									<input class="regular-text" type="text" id="item_updated_at_<?php echo $item->menu_order; ?>" name="items[<?php echo $item->menu_order; ?>][updated_at]" value="<?php printf( __( 'last update: %s', 'wordpress-wishlist' ), $item->updated_at->format('Y-m-d H:i:s') ); ?>" readonly disabled>
								</div>
								<div class="group content" style="<?php echo ( $item->item_type !== 'generic' )? 'display: none;': ''; ?>">
									<label  for="item_content_<?php echo $item->menu_order; ?>"><?php esc_html_e( 'Item Description', 'wordpress-wishlist' ); ?></label>
									<textarea name="items[<?php echo $item->menu_order; ?>][item_content]" id="item_content_<?php echo $item->menu_order; ?>" placeholder="<?php esc_attr_e( 'Type a description', 'wordpress-wishlist' ); ?>"><?php
										echo $item->item_content;
										?></textarea>
								</div>
								<div class="group object_id" style="<?php echo ( $item->item_type !== 'generic' )? '': 'display: none;'; ?>">
                                    <label  for="item_object_id_<?php echo $item->menu_order; ?>"><?php esc_html_e( 'Object ID', 'wordpress-wishlist' ); ?></label>
									<input class="regular-text" type="number" min="0" step="1" id="item_object_id_<?php echo $item->menu_order; ?>" name="items[<?php echo $item->menu_order; ?>][object_id]" value="<?php echo esc_attr( $item->object_id ); ?>" placeholder="<?php esc_attr_e( 'Object ID', 'wordpress-wishlist' ); ?>">
                                    <span class="help"><code><?php _e( 'WordPress Post/Taxonomy Term ID based on selected <b>Item Type</b>', 'wordpress-wishlist' ); ?></code></span>
								</div>
								<div class="group object-html" style="<?php echo ( $item->item_type !== 'generic' )? '': 'display: none;'; ?>">
									<?php echo $item->get_attachment_link(); ?>
								</div>
							</div>
							<?php do_action( 'rox_wpwl_admin_edit_after_item', $item ); ?>
						</li>
					<?php } ?>
				<?php } ?>
			</ol>
			<hr class="clear">
			<?php do_action( 'rox_wpwl_admin_edit_after_items', $post ); ?>
		</div>
		<script type="text/template" id="wishlist-item-template">
			<li id="item___idx__" data-idx="__idx__">
				<?php do_action( 'rox_wpwl_admin_edit_before_item', NULL ); ?>
				<a class="move-item" href="#" aria-label="<?php esc_attr_e( 'Move Item', 'wordpress-wishlist' ); ?>"><span class="dashicons dashicons-move"></span></a>
				<a class="remove-item" href="#" aria-label="<?php esc_attr_e( 'Remove Item', 'wordpress-wishlist' ); ?>"><span class="dashicons dashicons-trash"></span></a>
				<a class="undo-delete" href="#" aria-label="<?php esc_attr_e( 'Restore Item', 'wordpress-wishlist' ); ?>"><span class="dashicons dashicons-undo"></span></a>
				<input type="hidden" id="item_order___idx__" name="items[__idx__][menu_order]" value="__idx__">
				<input type="hidden" id="item_ID___idx__" name="items[__idx__][ID]">
				<input type="hidden" id="item_deleted___idx__" name="items[__idx__][deleted]">
				<div class="item-group">
					<div class="group title">
						<label for="item_title___idx__"><?php esc_html_e( 'Item Title', 'wordpress-wishlist' ); ?></label>
						<input type="text" id="item_title___idx__" name="items[__idx__][item_title]" placeholder="<?php esc_attr_e( 'Type a title', 'wordpress-wishlist' ); ?>">
					</div>
					<div class="group-half content_type">
						<label  for="item_type"><?php esc_html_e( 'Item Type', 'wordpress-wishlist' ); ?></label>
						<select name="items[__idx__][item_type]" id="item_type___idx__"><?php
							foreach( $itemTypes as $k => $v ) {
								printf( '<option value="%s"%s>%s</option>', $k, selected( $k, 'generic', false ), $v );
							}
							?></select>
					</div>
					<div class="group-half" style="margin-right: 0;">
						<label for="item_updated_at___idx__"><?php _e( 'Updated At', 'wordpress-wishlist' ); ?></label>
						<input class="regular-text" type="tex" id="item_updated_at___idx__" name="items[__idx__][updated_at]" value="<?php esc_attr_e( 'last update: Now', 'wordpress-wishlist' ); ?>" readonly disabled>
					</div>
					<div class="group content">
						<label  for="item_content___idx__"><?php esc_html_e( 'Item Description', 'wordpress-wishlist' ); ?></label>
						<textarea name="items[__idx__][item_content]" id="item_content___idx__" placeholder="<?php esc_attr_e( 'Type a description', 'wordpress-wishlist' ); ?>"></textarea>
					</div>
					<div class="group object_id" style="display: none;">
						<label  for="item_object_id___idx__"><?php esc_html_e( 'Object ID', 'wordpress-wishlist' ); ?></label>
						<input class="regular-text" type="number" min="0" step="1" id="item_object_id___idx__" name="items[__idx__][object_id]" value="0" placeholder="<?php esc_attr_e( 'Object ID', 'wordpress-wishlist' ); ?>">
                        <span class="help"><code><?php _e( 'WordPress Post/Taxonomy Term ID based on selected <b>Item Type</b>', 'wordpress-wishlist' ); ?></code></span>
					</div>
					<div class="group object-html" style="display: none;"></div>
				</div>
				<?php do_action( 'rox_wpwl_admin_edit_after_item', NULL ); ?>
			</li>
		</script>
		<?php
		self::__render_list_share_box( $post );
	}
	public static function __save_meta_box( $post_id ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! isset( $_POST['items'] ) ) return;
		
		$itemTypes = array_keys( rox_wpwl_item_types() );
		// Prepare Items
		$items = array_map( function( $item ) use ( $post_id, $itemTypes ) {
			$item = array(
				'ID'            => isset( $item['ID'] ) && ! empty( $item['ID'] ) ? (int) $item['ID'] : NULL,
				'post_id'       => $post_id,
				'item_title'    => sanitize_text_field( $item['item_title'] ),
				'item_content'  => sanitize_textarea_field( $item['item_content'] ),
				'item_type'     => in_array( $item['item_type'], $itemTypes )? $item['item_type'] : 'generic',
				'object_id'     => isset( $item['object_id'] ) && ! empty( $item['object_id'] )? (int) $item['object_id'] : NULL,
				'deleted'       => (int) $item['deleted'],
				'menu_order'    => (int) $item['menu_order'],
			);
			if( $item['deleted'] == 1 ) return rox_wpwl_delete_item( $item['ID'] );
			return rox_wpwl_insert_item( $item, false );
		}, $_POST['items'] );
		// Set default cat to wishlist if no cat added
		$cat_input = array();
		if( isset( $_POST['tax_input'][RoxWPWLPostTypes::$config['tax-cat']] ) ) {
			$cat_input = array_filter( $_POST['tax_input'][RoxWPWLPostTypes::$config['tax-cat']] );
		}
		if( empty( $cat_input ) ) {
			if( $cat = get_option( '__rox_wpwl_default_list_cat', false ) ) {
				$cat = get_term( $cat, RoxWPWLPostTypes::$config['tax-cat'] );
				wp_set_object_terms( $post_id, $cat->slug, RoxWPWLPostTypes::$config['tax-cat'] );
			}
		}
	}
}
RoxWPWLMetaBoxes::init();

