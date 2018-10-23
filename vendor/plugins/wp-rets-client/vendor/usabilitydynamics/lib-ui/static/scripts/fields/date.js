jQuery( function ( $ )
{
	'use strict';

	/**
	 * Update date picker element
	 * Used for static & dynamic added elements (when clone)
	 */
	function uisf_update_date_picker()
	{
		var $this = $( this );
		var options = $.extend( $this.data( 'options' ), {
			onSelect: function(dateText) {
				$(document).trigger('uisf_datetime_change', [ 'date', dateText, this ] );
			}
		} );

		$this.siblings( '.ui-datepicker-append' ).remove();         // Remove appended text
		$this.removeClass( 'hasDatepicker' ).attr( 'id', '' ).datepicker( options );
	}

	$( ':input.uisf-date' ).each( uisf_update_date_picker );
	$( '.uisf-input' ).on( 'clone', ':input.uisf-date', uisf_update_date_picker );
} );
