jQuery( function ( $ )
{
	'use strict';

	$( '.rwmb-tab-nav' ).on( 'click', 'a', function ( e )
	{
		e.preventDefault();

		var $li = $( this ).parent(),
			panel = $li.data( 'panel' ),
			$wrapper = $li.closest( '.rwmb-tabs' ),
			$panel = $wrapper.find( '.rwmb-tab-panel-' + panel );

		$li.addClass( 'rwmb-tab-active' ).siblings().removeClass( 'rwmb-tab-active' );
		$panel.show().siblings().hide();
	} );
	$( '.rwmb-tab-active a' ).trigger( 'click' );
} );