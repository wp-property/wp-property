(function($) {

	/**
	 *
	 * @param options
	 */
	$.fn.wp_list_table = function(options) {

		var instance = this;

		/** Our Main container */
		var el = $(this);

		/** Making variables public */
		var vars = $.extend({
			'order': 'asc',
			'orderby': 'menu_order title',
			'singular': '',
			'plural': '',
			'class': '',
			'per_page': '20',
			'post_type': false,
			'screen': false,
			'post_status': 'any',
			'_wpnonce': false,
			'extra': {},
			'query': {},
			'callbacks': {
				'update': function(instance, type, r){
					$(document).trigger('wplt-after-update', [instance,type,r]);
				}
			}
		}, options);

		if( !vars.post_type ) {
			return;
		}

		/** Events Handler */
		var list = instance.wplt = {

			vars: vars,

			timer: null,

			delay: 700,

			called: false,

			paged: '1',

			order: vars.order,

			orderby: vars.orderby,

			/**
			 * Register our triggers
			 *
			 * We want to capture clicks on specific links, but also value change in
			 * the pagination input field. The links contain all the information we
			 * need concerning the wanted page number or ordering, so we'll just
			 * parse the URL to extract these variables.
			 *
			 * The page number input is trickier: it has no URL so we have to find a
			 * way around. We'll use the hidden inputs added in TT_Example_List_Table::display()
			 * to recover the ordering variables, and the default paged input added
			 * automatically by WordPress.
			 */
			init: function() {

				/**
				 * Always show Table Navigation.
				 */
				$('.tablenav, .tablenav .pagination-links ', el).show();

				/**
				 * Pagination links, sortable link
 				 */
				$('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a', el).on('click', function(e) {
					// We don't want to actually follow these links
					e.preventDefault();

					// Simple way: use the URL to extract our needed variables
					var query = this.search.substring( 1 );
					list.paged = list.__query( query, 'paged' ) || '1';
					list.order = list.__query( query, 'order' ) || list.order;
					list.orderby = list.__query( query, 'orderby' ) || list.orderby;
					list.update( 'sort' );
				});

				/**
				 * Page number input
 				 */
				$('input[name=paged]', el).on('keyup', function(e) {

					// If user hit enter, we don't want to submit the form
					// We don't preventDefault() for all keys because it would
					// also prevent to get the page number!
					if ( 13 == e.which )
						e.preventDefault();

					list.paged = parseInt( $('input[name=paged]',el).val() ) || '1';
					list.order = $('input[name=order]',el).val() || list.order;
					list.orderby = $('input[name=orderby]',el).val() || list.orderby;

					// Now the timer comes to use: we wait half a second after
					// the user stopped typing to actually send the call. If
					// we don't, the keyup event will trigger instantly and
					// thus may cause duplicate calls before sending the intended
					// value
					window.clearTimeout( list.timer );
					list.timer = window.setTimeout(function() {
						list.update( 'pagination' );
					}, list.delay);
				});

				/**
				 * Bulk Actions handler
				 */
				$('#doaction, #doaction2', el).on('click', function(e) {
					e.preventDefault();

					var action = $(this).parents('.bulkactions').find('select[name="doaction"], select[name="doaction2"]').val();
					// Ignore if not selected
					if( action == '-1' ) {
						return;
					}

					var ids = [];
					$('#the-list th input[type="checkbox"]', el).each(function(i,r){
						if( $(r).is(':checked') ) {
							ids.push($(r).val());
						}
					});

					if( !ids.length > 0 ) {
						list.notice( 'No selected items to proceed.', 'error' );
						return;
					}

					list.paged = '1';

					list.update( 'doaction', {
						'doaction': action,
						'post_ids': ids
					} );

				});


				/**
				 * Checkbox Column ( select/unselect all )
				 */
				$('thead, tfoot', el).find('.check-column :checkbox').on( 'click.wp-toggle-checkboxes', function( event ) {
					var $this = $(this),
						$table = $this.closest( 'table' ),
						controlChecked = $this.prop('checked'),
						toggle = event.shiftKey || $this.data('wp-toggle');

					$table.children( 'tbody' ).filter(':visible')
						.children().children('.check-column').find(':checkbox')
						.prop('checked', function() {
							if ( $(this).is(':hidden') ) {
								return false;
							}

							if ( toggle ) {
								return ! $(this).prop( 'checked' );
							} else if ( controlChecked ) {
								return true;
							}

							return false;
						});

					$table.children('thead,  tfoot').filter(':visible')
						.children().children('.check-column').find(':checkbox')
						.prop('checked', function() {
							if ( toggle ) {
								return false;
							} else if ( controlChecked ) {
								return true;
							}

							return false;
						});
				});

				/**
				 * Init Filter form
				 */
				if( !list.called ) {
					list.init_filter();
				}

				list.called = true;
			},

			/**
			 * Check if Filter exists.
			 * Add Filter Hooks.
			 */
			init_filter: function() {
				var filter = false;
				$('.wplt-filter').each(function(i,e){
					var datafor = $(e).data('for');
					if( datafor.length > 0 && datafor == el.attr('id') ) {
						filter = $(e);
						return;
					}
				});

				list.filter = filter;
				if( !filter ) {
					return;
				}

				$( 'input', filter).on('keyup', function(e) {

					// If user hit enter, we don't want to submit the form
					// We don't preventDefault() for all keys because it would
					// also prevent to get the page number!
					if ( 13 == e.which )
						e.preventDefault();

					// Now the timer comes to use: we wait 0.7 sec after
					// the user stopped filtering data to actually send the call. If
					// we don't, the keyup event will trigger instantly and
					// thus may cause duplicate calls before sending the intended
					// value
					list.change_filter();
				});

				/**
				 * Handles selects for:
				 * Date
				 * Datetime
				 * Time
				 */
				$(document).on('uisf_datetime_change', function(e) {
					list.change_filter();
				});

				$( 'input[type="checkbox"], input[type="radio"]', filter).on('change', function(e) {
					list.change_filter();
				});

				$( 'select', filter).on('change', function(e) {
					list.change_filter();
				});
			},

			/**
			 * Must be run on any filter change.
			 */
			change_filter: function() {
				list.paged = '1';
				// Now the timer comes to use: we wait 0.7 sec after
				// the user stopped filtering data to actually send the call.
				window.clearTimeout( list.timer );
				list.timer = window.setTimeout(function() {
					list.update( 'filter' );
				}, list.delay);
			},

			/**
			 *
			 */
			data: function( data ) {
				// Prepare our filter data to send in request!
				var filter = [];
				if( typeof list.filter == 'object' && list.filter.length > 0 ){
					$( 'input, select, textarea', list.filter).each(function(i,e){
						// Send only attributes with 'extra' (MAP) data.
						var map = $(e).data('extra');
						if( typeof map == 'undefined' || typeof $(e).attr('name') == 'undefined' ) {
							return;
						}
						// Ignore not checked checkboxes
						if( $(e).attr('type') == 'checkbox' && !$(e).is(':checked') ) {
							return;
						}
						// Ignore not checked radio buttons
						if( $(e).attr('type') == 'radio' && !$(e).is(':checked') ) {
							return;
						}

						var value = $(e).val();
						// HACK for checkbox. Value can be stored as '1', 'true', 'on' in DB.
						if( $(e).attr('type') == 'checkbox' ) {
							value = ['1','true','on'];
						}

						filter.push({
							'name': $(e).attr('name').replace(/wplt_filter\.([^\[]*)(\[.*\])?/,"$1"),
							'value': value,
							'map': map
						});
					});
				}

			 	data = $.extend( {
					'order': list.order,
					'orderby': list.orderby,
					'paged': list.paged,
					'action': 'wplt_list_table',
					'singular': vars.singular,
					'plural': vars.plural,
					'class': vars.class,
					'per_page': vars.per_page,
					'post_type': vars.post_type,
					'screen': vars.screen,
					'post_status': vars.post_status,
					'_wpnonce': vars._wpnonce,
					'extra': vars.extra,
					'query': vars.query,
					'query2': filter
				}, data );

				return data;
			},

			/**
			 * AJAX call
			 * Send the call and replace table parts with updated version!
			 *
			 * @param    object    data The data to pass through AJAX
			 */
			update: function( type, data ) {
				$.ajax({
					// /wp-admin/admin-ajax.php
					url: ajaxurl,
					type: 'POST',
					// Add action and nonce to our collected data
					data: list.data( data ),
					// Handle the successful result
					success: function( r ) {

						if( typeof r !== 'object' ) {
							alert( 'Invalid response from server' );
							return;
						}

						// Maybe show notice
						if( typeof r.data !== 'undefined' && typeof r.data.notice !== 'undefined' ) {
							if( typeof r.data.notice.error !== 'undefined' ) {
								list.notice( r.data.notice.error, 'error' );
							} else if( typeof r.data.notice.warning !== 'undefined' ) {
								list.notice( r.data.notice.warning, 'error' );
							} else if( typeof r.data.notice.message !== 'undefined' ) {
								list.notice( r.data.notice.message, 'updated' );
							}
						}

						if( !r.success ) {
							alert( 'Error occurred on proceeding the request.' );
							return;
						}

						// Add the requested rows
						if (typeof r.data.rows !== 'undefined' ) {
							$('#the-list', el).html(r.data.rows);
						}
						// Update column headers for sorting
						if ( typeof r.data.column_headers !== 'undefined' ) {
							$('thead tr, tfoot tr', el).html(r.data.column_headers );
						}
						// Update bulk action
						if ( typeof r.data.bulk_actions.top !== 'undefined' ) {
							$('.tablenav.top .bulkactions', el).html(r.data.bulk_actions.top);
						}
						if ( typeof r.data.bulk_actions.bottom !== 'undefined' ) {
							$('.tablenav.bottom .bulkactions', el).html(r.data.bulk_actions.bottom);
						}
						// Update pagination for navigation
						if ( typeof r.data.pagination.top !== 'undefined' ) {
							$('.tablenav.top .tablenav-pages', el).html( $(r.data.pagination.top).html() );
						}
						if ( typeof r.data.pagination.bottom !== 'undefined' ) {
							$('.tablenav.bottom .tablenav-pages', el).html( $(r.data.pagination.bottom).html() );
						}

						// Init back our event handlers
						list.init();

						if( typeof vars.callbacks.update == 'function' ) {
							vars.callbacks.update( instance, type, r );
						}
					}
				});
			},

			/**
			 * Add Notice or replace message with existing one.
			 */
			notice: function( message, type ) {
				if( !$('.notice', el).length > 0 ) {
					$('.tablenav.top', el).before( '<div class="notice"><span class="content"></span> <a href="javascript:;" class="close">Hide</a></div>' );
					$('.notice .close', el).on( 'click', function(){
						$(this).parents( '.notice').remove();
					} );
				}
				$('.notice', el)
					.removeClass( "updated error")
					.addClass( type)
					.find( '.content').html( message );
			},

			/**
			 * Filter the URL Query to extract variables
			 *
			 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
			 *
			 * @param    string    query The URL query part containing the variables
			 * @param    string    variable Name of the variable we want to get
			 *
			 * @return   string|boolean The variable value if available, false else.
			 */
			__query: function( query, variable ) {
				var vars = query.split("&");
				for ( var i = 0; i <vars.length; i++ ) {
					var pair = vars[ i ].split("=");
					if ( pair[0] == variable )
						return pair[1];
				}
				return false;
			}
		}

		list.init();

		return instance;
	};

})(jQuery);