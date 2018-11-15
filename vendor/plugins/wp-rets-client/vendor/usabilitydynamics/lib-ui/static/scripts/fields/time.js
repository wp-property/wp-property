jQuery( function ( $ )
{
	'use strict';

	/**
	 * Update datetime picker element
	 * Used for static & dynamic added elements (when clone)
	 */
	function update()
	{
		var $this = $( this );
		var options = $.extend( $this.data( 'options' ), {
			onSelect: function(dateText) {
				$(document).trigger('uisf_datetime_change', [ 'time', dateText, this ] );
			}
		} );

		$this.siblings( '.ui-datepicker-append' ).remove();  // Remove appended text
		$this.removeClass( 'hasDatepicker' ).attr( 'id', '' ).timepicker( options );
	}

	// Set language if available
	if ( $.timepicker.regional.hasOwnProperty( uisf_timepicker.locale ) )
	{
		$.timepicker.setDefaults( $.timepicker.regional[uisf_timepicker.locale] );
	}
	else if ( $.timepicker.regional.hasOwnProperty( uisf_timepicker.localeShort ) )
	{
		$.timepicker.setDefaults( $.timepicker.regional[uisf_timepicker.localeShort] );
	}

	$( '.uisf-time' ).each( update );
	$( '.uisf-input' ).on( 'clone', '.uisf-time', update );
} );
