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
				$(document).trigger('uisf_datetime_change', [ 'datetime', dateText, this ] );
			}
		} );

		$this.siblings( '.ui-datepicker-append' ).remove();         // Remove appended text
		$this.removeClass( 'hasDatepicker' ).attr( 'id', '' ).datetimepicker( options );

	}

	// Set language if available
	if ( $.timepicker.regional.hasOwnProperty( uisf_datetimepicker.locale ) )
	{
		$.timepicker.setDefaults( $.timepicker.regional[uisf_datetimepicker.locale] );
	}
	else if ( $.timepicker.regional.hasOwnProperty( uisf_datetimepicker.localeShort ) )
	{
		$.timepicker.setDefaults( $.timepicker.regional[uisf_datetimepicker.localeShort] );
	}

	$( ':input.uisf-datetime' ).each( update );
	$( '.uisf-input' ).on( 'clone', ':input.uisf-datetime', update );
} );
