jQuery( function ( $ )
{
	'use strict';

	/**
	 *
	 * @param repo
	 * @returns {*}
	 */
	function formatItemResult (item) {
		if (item.loading) return item.text;
		return '<div>' + item.label + '</div>';
	}

	/**
	 *
	 * @param repo
	 * @returns {*}
	 */
	function formatItemSelection (item) {
		return item.label;
	}

	/**
	 * Turn select field into beautiful dropdown with select2 library
	 * This function is called when document ready and when clone button is clicked (to update the new cloned field)
	 *
	 * @return void
	 */
	function update()  {
		var $this = $( this ),
				options = $this.data( 'options' );

		if( typeof options._ajax_url !== 'undefined' && options._ajax_url.length > 0 ) {

			options = $.extend( options, {
				dataType: 'json',
				ajax: {
					//url: 'https://api.github.com/search/repositories',
					url: options._ajax_url,
					dataType: 'json',
					delay: 250,
					data: function (params) {
						return {
							q: params.term, // search term
							page: params.page
						};
					},
					processResults: function (data, page) {
						// parse the results into the format expected by Select2.
						// since we are using custom formatting functions we do not need to
						// alter the remote JSON data
						return {
							results: data
						};
					},
					cache: true
				},
				escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
				minimumInputLength: 1,
				templateResult: formatItemResult,
				templateSelection: formatItemSelection
			} );

		}

		$this.siblings( '.select2-container' ).remove();
		$this.show().select2( options );
	}

	$( ':input.uisf-select-advanced' ).each( update );
	$( '.uisf-input' ).on( 'clone', ':input.uisf-select-advanced', update );
} );
