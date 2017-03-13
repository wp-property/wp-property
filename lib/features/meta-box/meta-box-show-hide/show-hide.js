jQuery( function ( $ )
{
	'use strict';

	// Global variables
	var $pageTemplate = $( '#page_template ' ),
		$postFormat = $( 'input[name="post_format"]' ),
		$parent = $( '#parent_id' );

	// Callback functions to check for each condition
	var checkCallbacks = {
		template   : function ( templates )
		{
			return -1 != templates.indexOf( $pageTemplate.val() );
		},
		post_format: function ( formats )
		{
			// Make sure registered formats in lowercase
			formats = formats.map( function ( format )
			{
				return format.toLowerCase();
			} );

			var value = $postFormat.filter( ':checked' ).val();
			if ( !value || 0 == value )
			{
				value = 'standard';
			}

			return -1 != formats.indexOf( value );
		},
		taxonomy   : function ( taxonomy, terms )
		{
			var values = [],
				$inputs = $( '#' + taxonomy + 'checklist :checked' );

			$inputs.each( function ()
			{
				var $input = $( this ),
					text = $.trim( $input.parent().text() );
				values.push( parseInt( $input.val() ) );
				values.push( text );
			} );

			for ( var i = 0, len = values.length; i < len; i++ )
			{
				if ( -1 != terms.indexOf( values[i] ) )
					return true;
			}
			return false;
		},
		input_value: function ( inputValues, relation )
		{
			relation = relation || 'OR';

			for ( var i in inputValues )
			{
				var $element = $( i ),
					value = $.trim( $element.val() ),
					checked = null;

				if ( $element.is( ':checkbox' ) )
				{
					checked = $element.is( ':checked' ) === !!inputValues[i];
				}

				if ( 'OR' == relation )
				{
					if ( ( value == inputValues[i] && checked === null ) || checked === true )
						return true;
				}
				else
				{
					if ( ( value != inputValues[i] && checked === null ) || checked === false )
						return false;
				}
			}
			return relation != 'OR';
		},
		is_child   : function ()
		{
			return '' != $parent.val();
		}
	};

	// Callback functions to addEventListeners for "change" event in each condition
	var addEventListenersCallbacks = {
		/**
		 * Check by page templates
		 *
		 * @param callback Callback function
		 *
		 * @return bool
		 */
		template   : function ( callback )
		{
			$pageTemplate.on( 'change', callback );
		},
		post_format: function ( callback )
		{
			$postFormat.on( 'change', callback );
		},
		taxonomy   : function ( taxonomy, callback )
		{
			// Fire "change" event when click on popular category
			// See wp-admin/js/post.js
			$( '#' + taxonomy + 'checklist-pop' ).on( 'click', 'input', function ()
			{
				var t = $( this ), val = t.val(), id = t.attr( 'id' );
				if ( !val )
					return;

				var tax = id.replace( 'in-popular-', '' ).replace( '-' + val, '' );
				$( '#in-' + tax + '-' + val ).trigger( 'change' );
			} );

			$( '#' + taxonomy + 'checklist' ).on( 'change', 'input', callback );
		},
		input_value: function ( callback, selector )
		{
			$( selector ).on( 'change', callback );
		},
		is_child   : function ( callback )
		{
			$parent.on( 'change', callback );
		}
	};

	/**
	 * Add JS addEventListenersers to check conditions to show/hide a meta box
	 * @param type
	 * @param conditions
	 * @param $metaBox
	 *
	 * @returns void
	 */
	function maybeShowHide( type, conditions, $metaBox )
	{
		var condition = checkAllConditions( conditions );

		if ( 'show' == type )
		{
			condition ? $metaBox.show() : $metaBox.hide();
		}
		else
		{
			condition ? $metaBox.hide() : $metaBox.show();
		}
	}

	/**
	 * Check all conditions
	 * @param conditions Array of all conditions
	 *
	 * @return bool
	 */
	function checkAllConditions( conditions )
	{
		// Don't change "global" conditions
		var localConditions = $.extend( {}, conditions );

		var relation = localConditions.hasOwnProperty( 'relation' ) ? localConditions['relation'].toUpperCase() : 'OR',
			value;

		// For better loop of checking terms
		if ( localConditions.hasOwnProperty( 'relation' ) )
			delete localConditions['relation'];

		var checkBy = ['template', 'post_format', 'input_value', 'is_child'],
			by, condition;

		for ( var i = 0, l = checkBy.length; i < l; i++ )
		{
			by = checkBy[i];

			if ( !localConditions.hasOwnProperty( by ) )
				continue;

			// Call callback function to check for each condition
			condition = checkCallbacks[by]( localConditions[by], relation );

			if ( 'OR' == relation )
			{
				value = typeof value == 'undefined' ? condition : value || condition;
				if ( value )
					return value;
			}
			else
			{
				value = typeof value == 'undefined' ? condition : value && condition;
				if ( !value )
					return value;
			}

			delete localConditions[by];
		}

		// By taxonomy, including category and post format
		// Note that we unset all other parameters, so we can safely loop in the localConditions array
		if ( !localConditions.length )
		{
			for ( var taxonomy in localConditions )
			{
				if ( !localConditions.hasOwnProperty( taxonomy ) )
					continue;

				// Call callback function to check for each condition
				condition = checkCallbacks['taxonomy']( taxonomy, localConditions[taxonomy] );

				if ( 'OR' == relation )
				{
					value = typeof value == 'undefined' ? condition : value || condition;
					if ( value )
						return value;
				}
				else
				{
					value = typeof value == 'undefined' ? condition : value && condition;
					if ( !value )
						return value;
				}
			}
		}

		return value;
	}

	/**
	 * Add event addEventListenersers for "change" event
	 * This will re-check all conditions to show/hide meta box
	 * @param type
	 * @param conditions
	 * @param $metaBox
	 */
	function addEventListeners( type, conditions, $metaBox )
	{
		// Don't change "global" conditions
		var localConditions = $.extend( {}, conditions );

		// For better loop of checking terms
		if ( localConditions.hasOwnProperty( 'relation' ) )
			delete localConditions['relation'];

		var checkBy = ['template', 'post_format', 'input_value', 'is_child'], by;
		for ( var i = 0, l = checkBy.length; i < l; i++ )
		{
			by = checkBy[i];

			if ( !localConditions.hasOwnProperty( by ) )
				continue;

			if ( 'input_value' != by )
			{
				// Call callback function to check for each condition
				addEventListenersCallbacks[by]( function ()
				{
					maybeShowHide( type, conditions, $metaBox );
				} );
				delete localConditions[by];
				continue;
			}

			// Input values
			for ( var selector in localConditions[by] )
			{
				// Call callback function to check for each condition
				addEventListenersCallbacks[by]( function ()
				{
					maybeShowHide( type, conditions, $metaBox );
				}, selector );
			}
			delete localConditions[by];

		}

		// By taxonomy, including category and post format
		// Note that we unset all other parameters, so we can safely loop in the localConditions array
		if ( !localConditions.length )
		{
			for ( var taxonomy in localConditions )
			{
				if ( !localConditions.hasOwnProperty( taxonomy ) )
					continue;

				// Call callback function to check for each condition
				addEventListenersCallbacks['taxonomy']( taxonomy, function ()
				{
					maybeShowHide( type, conditions, $metaBox );
				} );
			}
		}
	}

	// Show/hide check for each meta box
	$( '.mb-show-hide' ).each( function ()
	{
		var $this = $( this ),
			$metaBox = $this.closest( '.postbox' ),
			conditions;

		// Check for show rules
		if ( $this.data( 'show' ) )
		{
			conditions = $this.data( 'show' );
			maybeShowHide( 'show', conditions, $metaBox );
			addEventListeners( 'show', conditions, $metaBox );
		}

		// Check for hide rules
		if ( $this.data( 'hide' ) )
		{
			conditions = $this.data( 'hide' );
			maybeShowHide( 'hide', conditions, $metaBox );
			addEventListeners( 'hide', conditions, $metaBox );
		}
	} );
} );
