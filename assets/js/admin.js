/**!
 * RoxWPWL
 * Admin Scripts
 * @since 1.0.0
 * @version 1.0.0
 * @author PluginRox
 * @copyright PluginRox
 */
var rox_wpwl_configs = rox_wpwl_configs || {};
window.wp = window.wp || {};
window._wpUpdatesSettings = window._wpUpdatesSettings || {};
;(function ($, window, document, config, wp, wpConf ) {
	"use strict";
	function wishlist() {
		// @link https://johnny.github.io/jquery-sortable/
		var wishlist_container = "ol.wishlist-items.sortable";
		$(wishlist_container).sortable({
			handle: 'a.move-item',
			onDrop: function  ($item, container, _super) {
				_super($item, container);
				update_idx();
			}
		});
		function update_idx() {
			var $container = $(wishlist_container),
				$list = $container.find('li'),
				replacePattern = /\d/g,
				idx = 1;
			$list.each( function() {
				var $item = $(this);
				$item.attr( 'id', $item.attr( 'id' ).replace( replacePattern, idx ) );
				$item.find('label, input, select, textarea').each(function() {
					var $el = $(this);
					if( $el.attr( 'for' ) ) $el.attr( 'for', $el.attr( 'for' ).replace( replacePattern, idx ) );
					if( $el.attr( 'id' ) ) $el.attr( 'id', $el.attr( 'id' ).replace( replacePattern, idx ) );
					if( $el.attr( 'name' ) ) {
						if( $el.attr( 'name' ).match( /\[menu_order\]$/g ) ) $el.val( idx );
						$el.attr( 'name', $el.attr( 'name' ).replace( replacePattern, idx ) );
					}
				});
				idx++;
			} );
		}
		var template = $('#wishlist-item-template').text();
		$(document).on('click', 'a.add-item', function(event){
			event.preventDefault();
			var $container = $('ol.wishlist-items.sortable');
			$container.prepend( template.replace( /__idx__/g, $container.find('li').length+1 ) );
			update_idx();
		}).on('click', 'a.remove-item', function(event){
			event.preventDefault();
			var $item = $(this).closest('li'),
				$itemDelete = $item.find('input[name$="[deleted]"]');
			// idx = $item.attr( 'id' ).replace( 'item_', '' );
			$itemDelete.val(1);
			
			$item.closest('li').appendTo( 'ol.wishlist-items.sortable' );
			$item.hide();
			$item.addClass('deleted');
			update_idx();
		}).on('click', '.undo-delete', function (event) {
			event.preventDefault();
			var $item = $(this).closest('li'),
				$itemDelete = $item.find('input[name$="[deleted]"]');
			$itemDelete.val(0);
			$item.removeClass('deleted');
			$item.closest('li').prependTo( 'ol.wishlist-items.sortable' );
			update_idx();
		});/*.on('click', 'a.edit-item', function(event) {
			event.preventDefault();
			var $item = $(this).closest('li');
			$item.addClass( 'edit' );
		} );*/
		
	}
	function admin_wishlist_shared() {
		$(document).on( 'click', '.share_list', function( event ) {
			event.preventDefault();
			// Thickbox doesn't trigger events on regular modals
			tb_show( config.share_modal_label, '#?TB_inline=1&inlineId=wishlist_contributors' );
		});
		$(document).on( 'change', '.input[name="visibility"]', function(){
			if( $(this).val() == 'private' ) {
			}
		} );
	}
	$(document).ready(function () {
		var $body = $('body');
		if( config.is_edit ) {
			var $wishlistContainer = $body.find('.rox_wpwl_items');
			if( $wishlistContainer.length ) {
				wishlist();
			}
			$(document).on( 'change', '.content_type select', function() {
				var $genericHidden = $(this).closest( '.item-group' ).find('.object_id, .object-html'),
					$object_id = $(this).closest( '.item-group' ).find('.object_id'),
					$object_html = $(this).closest( '.item-group' ).find('.object-html'),
					$genericShown = $(this).closest('.item-group').find('.title,.content');
				if( config._deps_object_id.length > 0 && config._deps_object_id.indexOf( $(this).val() ) !== -1 ) {
					// if( ! $object_id.is( ':visible' ) ) $object_id.show();
					// if( ! $object_html.is( ':visible' ) ) $object_html.show();
					if( ! $genericHidden.is(':visible') ) $genericHidden.show();
					if( $genericShown.is(':visible') ) $genericShown.hide();
				} else {
					// if( $object_id.is( ':visible' ) ) $object_id.hide();
					// if( $object_html.is( ':visible' ) ) $object_html.hide();
					if( $genericHidden.is(':visible') ) $genericHidden.hide();
					if( ! $genericShown.is(':visible') ) $genericShown.show();
				}
			} );
			if( config.enable_sharing ) {
				admin_wishlist_shared();
			}
		}
		$body.find('.wishlist_button_style input[type="radio"]').on('change', function( e ) {
			var $selected = $(this);
			if( $selected.is(':checked') ) {
				var $shortcode = $body.find('.shortcode input');
				$shortcode.val( $shortcode.val().replace( /style=".+"/g, 'style="'+$selected.val()+'"'  ) );
				$shortcode.trigger( 'change' );
				$shortcode.select();
			}
		}).trigger('change');
		var $pluginInstallSearch = $( '.plugin-rox.rox-install .wp-filter-search' );
		if( $pluginInstallSearch.length > 0 ) {
			var $pluginFilter        = $( '#plugin-filter' );
			$pluginInstallSearch.attr( 'aria-describedby', 'live-search-desc' );
			// settings = _.extend( settings, window._wpUpdatesItemCounts || {} );
			/**
			 * Handles changes to the plugin search box on the new-plugin page,
			 * searching the repository dynamically.
			 *
			 */
			$pluginInstallSearch.on( 'keyup input', _.debounce( function( event, eventtype ) {
				var $searchTab = $( '.plugin-install-search' ), data, searchLocation;

				data = {
					_ajax_nonce: wp.updates.ajaxNonce,
					s:           event.target.value,
					tab:         'search',
					page:		 'rox_plugins',
					type:        $( '#typeselector' ).val(),
					pagenow:     pagenow
				};
				searchLocation = location.href.split( '?' )[ 0 ] + '?' + $.param( _.omit( data, [ '_ajax_nonce', 'pagenow' ] ) );

				// Clear on escape.
				if ( 'keyup' === event.type && 27 === event.which ) {
					event.target.value = '';
				}

				if ( wp.updates.searchTerm === data.s && 'typechange' !== eventtype ) {
					return;
				} else {
					$pluginFilter.empty();
					wp.updates.searchTerm = data.s;
				}

				if ( window.history && window.history.replaceState ) {
					window.history.replaceState( null, '', searchLocation );
				}

				if ( ! $searchTab.length ) {
					$searchTab = $( '<li class="plugin-install-search" />' )
						.append( $( '<a />', {
							'class': 'current',
							'href': searchLocation,
							'text': wp.updates.l10n.searchResultsLabel
						} ) );

					$( '.wp-filter .filter-links .current' )
						.removeClass( 'current' )
						.parents( '.filter-links' )
						.prepend( $searchTab );

					$pluginFilter.prev( 'p' ).remove();
					$( '.plugins-popular-tags-wrapper' ).remove();
				}

				if ( 'undefined' !== typeof wp.updates.searchRequest ) {
					wp.updates.searchRequest.abort();
				}
				$( 'body' ).addClass( 'loading-content' );

				wp.updates.searchRequest = wp.ajax.post( 'pluginrox_search_install_plugins', data ).done( function( response ) {
					$( 'body' ).removeClass( 'loading-content' );
					$pluginFilter.append( response.items );
					delete wp.updates.searchRequest;

					if ( 0 === response.count ) {
						wp.a11y.speak( wp.updates.l10n.noPluginsFound );
					} else {
						wp.a11y.speak( wp.updates.l10n.pluginsFound.replace( '%d', response.count ) );
					}
				} );
			}, 500 ) );
		}

	});
})(jQuery, window, document, rox_wpwl_configs, window.wp, window._wpUpdatesSettings);