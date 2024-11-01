<?php
/**
 * Pagination - Show numbered pagination for catalog pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current = isset( $current ) ? $current : get_query_var( 'page' );
$base    = isset( $base ) ? $base : esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) );
$format  = isset( $format ) ? $format : '';
$end_size = isset( $end_size ) ? $end_size : 3;
$mid_size = isset( $mid_size ) ? $mid_size : 3;

if ( $total <= 1 ) return;
?>
<nav class="wishlist-pagination">
	<?php
		echo paginate_links( apply_filters( 'rox_wpwl_pagination_args', array(
			'base'         => $base,
			'format'       => $format,
			'add_args'     => false,
			'current'      => max( 1, $current ),
			'total'        => $total,
			'prev_text'    => '&larr;',
			'next_text'    => '&rarr;',
			'type'         => 'list',
			'end_size'     => $end_size,
			'mid_size'     => $mid_size,
		) ) );
	?>
</nav>