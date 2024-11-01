/**!
 * RoxWPWL
 * Frontend Scripts
 * @since 1.0.0
 * @version 1.0.0
 * @author PluginRox
 * @copyright PluginRox
 */
;(function ($, window, document, config) {
	"use strict";
	// GLOBAL variables
	var  __CSRF__ = config.rox_csrf, __URL__ = config.rox_ajax,
		__IS_USER_LOGGED_IN__ = ( config.is_user_logged_in == '1' ), post_id = parseInt( config.post_id );
	/**
	 * Loading Animation helper
	 * insert html tags to render loading and complete animation
	 * @param jquery element $el		parent element to insert animation tags
	 * @return void
	 */
	function RoxLoader( $el, timeout ) {
		var self = this,
			container = $el || $('body'),
			animation = $('<div class="roxLoaderContainer"><span class="roxLoader"><span class="checkmark"></span></span></div>'),
			hideTimeout = timeout || 1000,
			init = function() {
				container.append(animation);
				container.trigger('rox.loader.init');
			};
		init();
		self.animation = animation;
		self.show = function(timeout, callback) {
			var timeout = timeout == null || typeof(timeout) == 'undefined' ? hideTimeout : timeout;
			self.reset( 0 );
			if (!timeout) {
				container.css('overflow', 'hidden');
				animation.css('display', 'block');
				container.trigger('rox.loader.shown');
				if (typeof callback === "function") callback(self);
			} else {
				setTimeout(function() {
					container.css('overflow', 'hidden');
					animation.css('display', 'block');
					container.trigger('rox.loader.shown');
					if (typeof callback === "function") callback(self);
				}, timeout);
			}
		};
		self.hide = function(timeout, callback) {
			var timeout = timeout == null || typeof(timeout) == 'undefined' ? hideTimeout : timeout;
			if (!timeout) {
				container.css('overflow', '');
				animation.css('display', 'none');
				container.trigger('rox.loader.hidden');
				if (typeof callback === "function") callback(self);
			} else {
				setTimeout(function() {
					container.css('overflow', '');
					animation.css('display', 'none');
					container.trigger('rox.loader.hidden');
					if (typeof callback === "function") callback(self);
				}, timeout );
			}
		};
		self.complete = function(checkmark, timeout, callback) {
			var checkmark = checkmark == null || typeof( checkmark ) == 'undefined' || checkmark == false ? false : true,
				timeout = timeout == null || typeof(timeout) == 'undefined' ? hideTimeout : timeout;
			if( checkmark ) {
				container.find('.roxLoader').addClass('complete');
			}
			this.hide( timeout, function( self ) {
				container.trigger('rox.loader.completed');
				if (typeof callback === "function") callback(self);
			});
		};
		self.reset = function (timeout, callback) {
			var timeout = timeout == null || typeof(timeout) == 'undefined' ? hideTimeout : timeout;
			if (!timeout) {
				container.find('.roxLoader').removeClass('complete');
				if (typeof callback === "function") callback(self);
			} else {
				setTimeout(function() {
					container.find('.roxLoader').removeClass('complete');
					if (typeof callback === "function") callback(self);
				}, timeout );
			}
		};
		self.restart = function(callback) {
			container.find('.roxLoader').removeClass('complete');
			self.show(0, callback);
		};
		self.destroy = function(callback) {
			animation.hide(0, function(self){
				animation.remove();
				container.trigger('rox.loader.destroyed');
				if (typeof callback === "function") callback(self);
			});
		};
	}
	/**
	 * Rox Popup
	 * @param $el
	 * @constructor
	 */
	function RoxPop( $el ) {
		var self = this;
		self.pop = $el || $('.rox_wishlist_pop'),
		self.contentWrapper = self.pop.find('.rox_pop_container'),
		self.contentContainer = self.contentWrapper.find( '.pop_contents' ),
		self.infoContainer = $('<div class="pop-info" style="display: none;"><div class="info-contents"></div></div>'),
		self.infos = self.infoContainer.find( '.info-contents' ),
		self.infoInDelay = 150,
		self.infoTimeout = 1500;
		// inset info/alert container
		self.contentWrapper.prepend( self.infoContainer );
		self.pop.find( '.back_drop' ).on( 'click', function () {
			self.hide();
		});
		$(document).on( 'keydown', function ( event ) {
			if ( event.keyCode == 27 ) self.hide();
		});
		/**
		 * @function
		 *
		 * Set PopUp Body Content
		 * @return {this}
		 * @param {(string|HTMLElement|object)} content html string/element object or jquery object
		 */
		self.content = function( content ) {
			self.contentContainer.html( content );
			return self;
		};
		/**
		 * @function
		 *
		 * Set alert/info message content
		 * @param {(string|HTMLElement|object)}     content html string/element object or jquery object
		 * @param {int}                             display delay time.
		 * @param {int}                             visibility duration.
		 * @return {void}
		 */
		self.info = function ( content, inTimeout, outTimeout ) {
			var inDuration = inTimeout == null || typeof(inTimeout) == 'undefined' ? self.infoInDelay : inTimeout,
				outDuration = outTimeout == null || typeof(outTimeout) == 'undefined' ? self.infoTimeout : outTimeout;
			// set data
			self.infos.html( content );
			setTimeout(function(){
				self.infoContainer.slideDown({
					complete: function () {
						$(document).trigger( 'rox.popup.info.shown' );
					}
				});
			}, inDuration );
			setTimeout( function() {
				self.infoContainer.slideUp( {
					complete: function () {
						self.infos.html('');
						$(document).trigger( 'rox.popup.info.hidden' );
					}
				} );
			}, outDuration );
		};
		/**
		 * @function
		 * Show the Popup
		 * return {void}
		 */
		self.show = function(){
			self.pop.show({
				duration: 0,
				done: function(){
					self.contentWrapper.slideDown( {
						duration: 200,
						done: function () {
							$('body').addClass( 'has_rox_pop' );
							$(document).trigger( 'rox.popup.open' );
						}
					});
				}
			});
			// $popup.show({
			// 	duration: 0,
			// 	done:function () {
			// 		$('body').addClass( 'has_rox_pop' );
			// 		$('body').trigger( 'rox.popup.open', { id: _id, type: _type } );
			// 	}
			// });
		};
		/**
		 * @function
		 * Hide the Popup
		 * return {void}
		 */
		self.hide = function () {
			self.contentWrapper.slideUp({
				duration: 200,
				done: function(){
					self.pop.hide({
						duration: 0,
						done:function () {
							$('body').removeClass( 'has_rox_pop' );
							$(document).trigger( 'rox.popup.close' );
						}
					});
				}
			});
			// $popup.hide({
			// 	duration: 0,
			// 	done:function () {
			// 		$('body').removeClass( 'has_rox_pop' );
			// 		$('body').trigger( 'rox.popup.close', { id: _id, type: _type } );
			// 	}
			// });
		};
	}
	/**
	 * Add To Wishlist
	 */
	function add_to_wishlist() {
		var $popup = new RoxPop( $('.rox_wishlist_pop') ),
			$popLoader = new RoxLoader( $popup.contentWrapper ),
			__PreviousObjectID__, // cache last popup
			$newListBtn = $('.pop-new-list a'), // create new list form trigger
			$newListForm = $('.add-new-list'),
			$newListSave = $newListForm.parent().find( '.rox_save' ),
			$newListCancle = $newListForm.parent().find( '.rox_cancle' );
		var _id, _type,
			nameMaxLength = parseInt( config.list_name_max_len );
		// Show Wishlist Popup
		$(document).on( 'click', '.rox-wishlist-button', function (event) {
			event.preventDefault();
			_id = $(this).data( 'id');
			_type = $(this).data( 'type');
			if( __PreviousObjectID__ !== _id ) {
				$popup.content('').show();
				$popLoader.show( 0 );
				__PreviousObjectID__ = _id;
				// Get User's Wishlist
				$.get( __URL__, { action: '__get_user_wishlists', id: _id, type: _type, _nonce: __CSRF__ }, response => {
					if( response.success ) {
						$popLoader.complete( false, 100 );
						$popup.content( response.data );
					}
				} ).fail(ajaxError);
			} else {
				$popup.show();
			}
		});
		// Add/Remove wishlist item on check box state change
		$(document).on( 'change', '.rox_wishlist_add', function(event) {
			$popLoader.show( 0 );
			var list_ID = $(this).val(), __data__, $item = $(this), __removing__ = false;
			if( $item.is(':checked') ) {
				__data__ = { action: '__add_list_item', id: _id, list_id: list_ID, type: _type, _nonce: __CSRF__ };
			} else {
				__data__ = { action: '__remove_list_item', id: _id, list_id: list_ID, type: _type, _nonce: __CSRF__ };
				__removing__ = true;
			}
			$.post( __URL__, __data__, response => {
				$popLoader.complete( true, null, () => { $popup.info( response.data ); } );
				if( response.success == true ) {
					if( __removing__ ) $item.prop( 'checked', false );
					else $item.prop( 'checked', true );
				} else {
					if( __removing__ ) $item.prop( 'checked', true );
					else $item.prop( 'checked', false );
				}
			}).fail(ajaxError);
		});
		// Create New List and add current item to it.
		$newListSave.on( 'click', function (event) {
			event.preventDefault();
			let list_name = $('#list-name').val().trim(),
				list_privacy = $('#list-privacy').val(),
				id = ( _id )? _id : '',
				type = ( _type )? _type : '',
				errors = [];
			// check
			if( list_name == '' ) errors.push('<p class="rox-alert rox-error">' + config.errors.invalid_or_empty + '</p>');
			if( list_name.length > nameMaxLength )  errors.push('<p class="rox-alert rox-error">' + config.errors.list_name_max_len + '</p>');
			if( ['public', 'private'].indexOf( list_privacy ) == -1 ) list_privacy = 'private';
			let __data__ = {
				action: '__add_new_list_with_item',
				list_name, list_privacy, id, type,
				_nonce : __CSRF__ };
			if( errors.length > 0 ) {
				$popup.info( errors.join( "\n" ) );
			} else {
				$popLoader.show( 0 );
				$.post( __URL__, __data__, response => {
					if( response.success ) {
						var __ItemResponse__ = response.data;
						reset_create_form();
						$.get( __URL__, { action: '__get_user_wishlists', id: _id, type: _type, _nonce: __CSRF__ }, response => {
							if( response.success ) {
								$popLoader.complete( true, false, () => { $popup.info( __ItemResponse__ ); } );
								$popup.content( response.data );
							}
						} ).fail(ajaxError);
					} else {
						$popLoader.complete( false, false, () => { $popup.info( response.data ); } );
					}
				}).fail(ajaxError);
			}
		});
		// check max length for new list
		$('#list-name').on('keyup', function( event ) {
			update_character_counter( $(this), $(this).val().length );
		} ).on( 'keypress', function( event ) {
			if( $(this).val().length >= nameMaxLength ) return false;
		} );
		// hide for guest visitor
		if( ! __IS_USER_LOGGED_IN__ ) $newListBtn.parent().hide( 0 );
		$newListBtn.on( 'click', function( event ) {
			event.preventDefault();
			$(this).hide();
			$newListForm.show();
		} );
		$newListCancle.on( 'click', function(event){
			event.preventDefault();
			reset_create_form();
		});
		/**
		 * Reset Popup Create New List Form
		 * @return void
		 */
		function reset_create_form() {
			$newListBtn.show();
			$newListForm.find( 'input' ).val('');
			$newListForm.find( 'select option' ).removeAttr('selected');
			$newListForm.hide();
		}
		/**
		 * Display Default Ajax Error Message
		 * @return void
		 */
		function ajaxError() {
			$popLoader.hide(0);
			$popLoader.reset(0);
			$popup.info( '<p class="rox-alert rox-error">'+config.rox_error+'</p>', 5000, 400 );
		}
		// Clear new clist form on wishlist close.
		$(document).on( 'rox.popup.close', function(){
			reset_create_form();
		});
	}

	/**
	 * Update Character Counter next to input/textarea
	 * @function
	 * @param {object} $el jquery selector element
	 * @param {int} count
	 */
	function update_character_counter( $el, count ) {
		$el.next('.character-counter').find('.counter-now').text( parseInt( count ) );
	}
	function edit_wishlist() {
		var settingsBtn = '.button-list-settings',
			$popup = new RoxPop( $('.rox_wishlist_pop') ),
			$popLoader = new RoxLoader( $popup.contentWrapper ),
			_list_name = '', _list_description = '', _list_privacy = 'private', // Initial Data for backing up before editing
			nameMaxLength = parseInt( config.list_name_max_len ), __UPDATED__ = false;
		if( ! $popup.contentWrapper.hasClass('wishlist-editor') ) $popup.contentWrapper.addClass('wishlist-editor');
		var $listEditForm = $('.edit-list'),
			$listSave = $listEditForm.closest('.rox_pop_container').find( '.rox_save' ),
			$listCancle = $listEditForm.closest('.rox_pop_container').find( '.rox_cancle' );
		// check max length for new list
		$('#list-name').on('keyup', function( event ) {
			update_character_counter( $(this), $(this).val().length );
		} ).on( 'keypress', function( event ) {
			if( $(this).val().length >= nameMaxLength ) return false;
		} );
		var rollBackData = function() {
			// rollback modifications if cancle clicked
			$('#list-name').val( _list_name );
			$('#list-description').val( _list_description );
			$('#list-privacy').val( _list_privacy );
			$('#list-privacy').find('option[value="'+_list_privacy+'"]').prop( 'selected', true );
		};
		$(document).on('rox.popup.close', rollBackData );
		// Open PopUp
		$(document).on('click', settingsBtn, function (event) {
			event.preventDefault();
			_list_name = $('#list-name').val().trim();
			_list_description = $('#list-description').val().trim();
			_list_privacy = $('#list-privacy').val();
			$popup.show();
		})
		.on( 'click', '.rox_cancle', function (event) {
			event.preventDefault();
			$popup.hide();
		} )
		.on('click', '.rox_save', function(event){
			event.preventDefault();
			let list_name = $('#list-name').val().trim(),
				list_description = $('#list-description').val().trim(),
				list_privacy = $('#list-privacy').val(),
				errors = [];
			// check
			if( list_name == '' ) errors.push('<p class="rox-alert rox-error">' + config.errors.invalid_or_empty + '</p>');
			if( list_name.length > nameMaxLength )  errors.push('<p class="rox-alert rox-error">' + config.errors.list_name_max_len + '</p>');
			if( ['public', 'private'].indexOf( list_privacy ) == -1 ) list_privacy = 'private';
			let __data__ = {
				// action: '__add_new_list_with_item',
				action: '__update_wishlist',
				list_name, list_description, list_privacy, post_id,
				_nonce : __CSRF__ };
			if( errors.length > 0 ) {
				$popup.info( errors.join( "\n" ) );
			} else {
				$popLoader.show( 0 );
				$.post( __URL__, __data__, response => {
					if( response.success ) {
						$popLoader.complete( true, false, () => {
							__UPDATED__ = true;
							$popup.info( response.data );
						} );
					} else {
						__UPDATED__ = false;
						$popLoader.complete( false, false, () => { $popup.info( response.data ); } );
					}
				}).fail(ajaxError);
			}
			// $popup.hide();
		} );
		$(document).on( 'rox.popup.info.hidden', function () {
			if( __UPDATED__ ) {
				__UPDATED__ = false;
				window.location.reload();
			}
		});
	}
	$(document).ready(function () {
		if( config.is_wp_wishlist != 1 ) add_to_wishlist();
		if( config.is_wishlist == 1 ) edit_wishlist();
		if( $('.button-delete-list').length > 0 ) {
			$('.button-delete-list').on('click', function( event ) {
				if ( ! confirm( config.confirm_delete_list ) ) return false;
			} );
		}
		if( $('.item-remove').length > 0 ) {
			$('.item-remove').on('click', function( event ) {
				if ( ! confirm( config.confirm_delete_item ) ) return false;
			} );
		}
	});
})(jQuery, window, document, rox_wpwl_configs);