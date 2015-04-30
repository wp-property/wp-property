(function( $, l10n ){

	/**
	 * Adds Autocomplete functionality.
	 */
	function updateAutocomplete( e )  {
		var $this = $( this ),
			$result = $this.next(),
			name = $this.data( 'name' );

		$this.removeClass( 'ui-autocomplete-input' ).attr( 'id', '' )
			.autocomplete( {
				minLength: 0,
				source   : $this.data( 'options' ),
				select   : function ( event, ui ) {
					$result.append(
						'<div class="rwmb-autocomplete-result">' +
						'<div class="label">' + ( typeof ui.item.excerpt !== 'undefined' ? ui.item.excerpt : ui.item.label ) + '</div>' +
						'<div class="actions">' + l10n.delete + '</div>' +
						'<input type="hidden" class="rwmb-autocomplete-value" name="' + name + '" value="' + ui.item.value + '">' +
						'</div>'
					);

					$this.hide();

					// Reinitialize value
					this.value = '';

					return false;
				}
			} );

		if( $result.find( '.rwmb-autocomplete-result').length > 0 ) {
			$this.hide();
		}
	}

	$( '.rwmb-wpp_parent-wrapper input[type="text"]' ).each( updateAutocomplete );

	// Handle remove action
	$( document ).on( 'click', '.rwmb-autocomplete-result .actions', function ()  {
		$( this ).parents('.rwmb-input').find('input[type="text"]').show();
		$( this ).parent().remove();
	} );

})( jQuery, wpp.strings );