/* global jQuery, google */
jQuery( function ( $ ) {
	'use strict';

	/**
	 * Refresh Google maps, make sure they're fully loaded.
	 * The problem is Google maps won't fully display when it's in hidden div (tab).
	 * We need to find all maps and send the 'resize' command to force them to refresh.
	 *
	 * @see https://developers.google.com/maps/documentation/javascript/reference ('resize' Event)
	 */
	function refreshMap() {
		if ( typeof google !== 'object' || typeof google.maps !== 'object' ) {
			return;
		}

		$( '.rwmb-map-field' ).each( function () {
			var controller = $( this ).data( 'mapController' );

			if ( typeof controller !== 'undefined' && typeof controller.map !== 'undefined' ) {
				google.maps.event.trigger( controller.map, 'resize' );
			}
		} );
	}

	$( '.rwmb-tab-nav' ).on( 'click', 'a', function ( e ) {
		e.preventDefault();

		var $li = $( this ).parent(),
			panel = $li.data( 'panel' ),
			$wrapper = $li.closest( '.rwmb-tabs' ),
			$panel = $wrapper.find( '.rwmb-tab-panel-' + panel );

		$li.addClass( 'rwmb-tab-active' ).siblings().removeClass( 'rwmb-tab-active' );
		$panel.show().siblings().hide();

		refreshMap();
	} );

	// Set active tab based on visible pane to better works with Meta Box Conditional Logic.
	if ( ! $( '.rwmb-tab-active' ).is( 'visible' ) ) {
		// Find the active pane.
		var activePane = $( '.rwmb-tab-panel[style*="block"]' ).index();

		if ( activePane >= 0 ) {
			$( '.rwmb-tab-nav li' ).removeClass( 'rwmb-tab-active' ).eq( activePane ).addClass( 'rwmb-tab-active' );
		}
	}

	$( '.rwmb-tab-active a' ).trigger( 'click' );

	// Remove wrapper.
	$( '.rwmb-tabs-no-wrapper' ).closest( '.postbox' ).addClass( 'rwmb-tabs-no-controls' );
} );
