<?php
/**
 * Adds settings to the permalinks admin settings page
 *
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'RoxWPWLAdminPermalink', false ) ) {
	RoxWPWLAdminPermalink::init();
	return;
}

/**
 * Permalink Settings
 */
class RoxWPWLAdminPermalink {
	/**
	 * Permalink settings.
	 *
	 * @var array
	 */
	private static $permalinks = array();
	
	public function __construct() {
	}
	public static function init() {
		self::$permalinks = rox_wpwl_get_permalink_structure();
		self::settings_init();
		self::settings_save();
	}
	/**
	 * Init our settings.
	 */
	public static function settings_init() {
		add_settings_section( 'rox_wpwl-permalink', __( 'Wishlist permalinks', 'wordpress-wishlist' ), array( __CLASS__, 'settings' ), 'permalink' );
		add_settings_field(
			'rox_wpwl_category_slug',
			__( 'Wishlist category base', 'wordpress-wishlist' ),
			array( __CLASS__, 'wishlist_category_slug_input' ),
			'permalink',
			'optional'
		);
		add_settings_field(
			'rox_wpwl_tag_slug',
			__( 'Wishlist tag base', 'wordpress-wishlist' ),
			array( __CLASS__, 'wishlist_tag_slug_input' ),
			'permalink',
			'optional'
		);
	}
	
	/**
	 * Show a slug input box.
	 */
	public static function wishlist_category_slug_input() {
		?>
		<input name="rox_wpwl_category_slug" type="text" class="regular-text code" value="<?php echo esc_attr( self::$permalinks['category_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'product-category', 'slug', 'wordpress-wishlist' ); ?>" />
		<?php
	}
	
	/**
	 * Show a slug input box.
	 */
	public static function wishlist_tag_slug_input() {
		?>
		<input name="rox_wpwl_tag_slug" type="text" class="regular-text code" value="<?php echo esc_attr( self::$permalinks['tag_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'product-tag', 'slug', 'wordpress-wishlist' ); ?>" />
		<?php
	}
	
	/**
	 * Show the settings.
	 */
	public static function settings() {
		/* translators: %s: Home URL */
		echo wp_kses_post( wpautop( sprintf( __( 'If you like, you may enter custom structures for your Wishlist URLs here. For example, using <code>wishlist</code> would make your Wishlist links like <code>%swishlist/wishlist-slug/</code>. This setting affects Wishlist URLs only, not things such as Wishlist categories.', 'wordpress-wishlist' ), esc_url( home_url( '/' ) ) ) ) );
		$whislsit_page_id = (int) rox_wpwl_get_option( 'wishlist_archive' );
		$base_slug    = urldecode( ( $whislsit_page_id > 0 && get_post( $whislsit_page_id ) ) ? get_page_uri( $whislsit_page_id ) : _x( 'wishlists', 'default-slug', 'wordpress-wishlist' ) );
		$wishlist_base = _x( 'wishlist', 'default-slug', 'wordpress-wishlist' );
		
		$structures = array(
			0 => '',
			1 => '/' . trailingslashit( $base_slug ),
			2 => '/' . trailingslashit( $base_slug ) . trailingslashit( '%wishlist_cat%' ),
		);
		?>
		<table class="form-table wc-permalink-structure">
			<tbody>
			<tr>
				<th><label><input name="wishlist_permalink" type="radio" value="<?php echo esc_attr( $structures[0] ); ?>" class="rox_wpwl_permalink_field" <?php checked( $structures[0], self::$permalinks['wishlist_base'] ); ?> /> <?php esc_html_e( 'Default', 'wordpress-wishlist' ); ?></label></th>
				<td><code class="default-example"><?php echo esc_html( home_url() ); ?>/?product=wishlist-slug</code> <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $wishlist_base ); ?>/wishlist-slug/</code></td>
			</tr>
			<?php if ( $whislsit_page_id ) : ?>
				<tr>
					<th><label><input name="wishlist_permalink" type="radio" value="<?php echo esc_attr( $structures[1] ); ?>" class="rox_wpwl_permalink_field" <?php checked( $structures[1], self::$permalinks['wishlist_base'] ); ?> /> <?php esc_html_e( 'Wishlist base', 'wordpress-wishlist' ); ?></label></th>
					<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/wishlist-slug/</code></td>
				</tr>
				<tr>
					<th><label><input name="wishlist_permalink" type="radio" value="<?php echo esc_attr( $structures[2] ); ?>" class="rox_wpwl_permalink_field" <?php checked( $structures[2], self::$permalinks['wishlist_base'] ); ?> /> <?php esc_html_e( 'Wishlist base with category', 'wordpress-wishlist' ); ?></label></th>
					<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/product-category/wishlist-slug/</code></td>
				</tr>
			<?php endif; ?>
			<tr>
				<th><label><input name="wishlist_permalink" id="rox_wpwl_custom_selection" type="radio" value="custom" class="tog" <?php checked( in_array( self::$permalinks['wishlist_base'], $structures, true ), false ); ?> />
						<?php esc_html_e( 'Custom base', 'wordpress-wishlist' ); ?></label></th>
				<td>
					<input name="wishlist_permalink_structure" id="rox_wpwl_permalink_structure" type="text" value="<?php echo esc_attr( self::$permalinks['wishlist_base'] ? trailingslashit( self::$permalinks['wishlist_base'] ) : '' ); ?>" class="regular-text code"> <span class="description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default instead.', 'wordpress-wishlist' ); ?></span>
				</td>
			</tr>
			</tbody>
		</table>
		<?php wp_nonce_field( 'rox_wpwl-permalinks', 'rox_wpwl-permalinks-nonce' ); ?>
		<script type="text/javascript">
			jQuery( function() {
				jQuery('input.rox_wpwl_permalink_field').change(function() {
					jQuery('#rox_wpwl_permalink_structure').val( jQuery( this ).val() );
				});
				jQuery('.permalink-structure input').change(function() {
					jQuery('.wc-permalink-structure').find('code.non-default-example, code.default-example').hide();
					if ( jQuery(this).val() ) {
						jQuery('.wc-permalink-structure code.non-default-example').show();
						jQuery('.wc-permalink-structure input').removeAttr('disabled');
					} else {
						jQuery('.wc-permalink-structure code.default-example').show();
						jQuery('.wc-permalink-structure input:eq(0)').click();
						jQuery('.wc-permalink-structure input').attr('disabled', 'disabled');
					}
				});
				jQuery('.permalink-structure input:checked').change();
				jQuery('#rox_wpwl_permalink_structure').focus( function(){
					jQuery('#rox_wpwl_custom_selection').click();
				} );
			} );
		</script>
		<?php
	}
	/**
	 * Save the settings.
	 */
	public static function settings_save() {
		if ( ! is_admin() ) {
			return;
		}
		// We need to save the options ourselves; settings api does not trigger save for the permalinks page.
		if ( isset( $_POST['permalink_structure'], $_POST['rox_wpwl-permalinks-nonce'], $_POST['rox_wpwl_category_slug'], $_POST['rox_wpwl_tag_slug'] ) && wp_verify_nonce( wp_unslash( $_POST['rox_wpwl-permalinks-nonce'] ), 'rox_wpwl-permalinks' ) ) {
			$permalinks                   = (array) get_option( 'rox_wp_wishlist_permalinks', array() );
			$permalinks['category_base']  = rox_wpwl_sanitize_permalink( wp_unslash( $_POST['rox_wpwl_category_slug'] ) );
			$permalinks['tag_base']       = rox_wpwl_sanitize_permalink( wp_unslash( $_POST['rox_wpwl_tag_slug'] ) );
			
			// Generate Wishlist base.
			$wishlist_base = isset( $_POST['wishlist_permalink'] ) ? sanitize_text_field( wp_unslash( $_POST['wishlist_permalink'] ) ) : '';
			
			if ( 'custom' === $wishlist_base ) {
				if ( isset( $_POST['wishlist_permalink_structure'] ) ) {
					$wishlist_base = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', trim( wp_unslash( $_POST['wishlist_permalink_structure'] ) ) ) );
				} else {
					$wishlist_base = '/';
				}
				// This is an invalid base structure and breaks pages.
				if ( '/%wishlist_cat%/' === trailingslashit( $wishlist_base ) ) {
					$wishlist_base = '/' . _x( 'wishlist', 'slug', 'wordpress-wishlist' ) . $wishlist_base;
				}
			} elseif ( empty( $wishlist_base ) ) {
				$wishlist_base = _x( 'wishlist', 'slug', 'wordpress-wishlist' );
			}
			$permalinks['wishlist_base'] = rox_wpwl_sanitize_permalink( $wishlist_base );
			// Wishlist base may require verbose page rules if nesting pages.
			$whislsit_page_id = (int) rox_wpwl_get_option( 'wishlist_archive' );
			$wishlist_permalink = ( $whislsit_page_id > 0 && get_post( $whislsit_page_id ) ) ? get_page_uri( $whislsit_page_id ) : _x( 'wishlist', 'default-slug', 'wordpress-wishlist' );
			if ( $whislsit_page_id && stristr( trim( $permalinks['wishlist_base'], '/' ), $wishlist_permalink ) ) {
				$permalinks['use_verbose_page_rules'] = true;
			}
			update_option( 'rox_wp_wishlist_permalinks', $permalinks );
		}
	}
}

RoxWPWLAdminPermalink::init();