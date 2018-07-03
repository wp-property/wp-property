/* global jQuery, _, google */
( function( $, _, document ) {
	'use strict';

	var $wrapper,
		group = {
			toggle: {}, // Toggle module for handling collapsible/expandable groups.
			clone: {}   // Clone module for handling clone groups.
		},
		inputSelectors = 'input[class*="rwmb"], textarea[class*="rwmb"], select[class*="rwmb"], button[class*="rwmb"]';

	/**
	 * Handles a click on either the group title or the group collapsible/expandable icon.
	 * Expects `this` to equal the clicked element.
	 *
	 * @param event Click event.
	 */
	group.toggle.handle = function( event ) {
		event.preventDefault();
		event.stopPropagation();

		var $group = $( this ).closest( '.rwmb-group-clone, .rwmb-group-non-cloneable' ),
			state = $group.hasClass( 'rwmb-group-collapsed' ) ? 'expanded' : 'collapsed';

		group.toggle.updateState( $group, state );

		// Refresh maps to make them visible.
		$( window ).trigger( 'rwmb_map_refresh' );
	};

	/**
	 * Update the group expanded/collapsed state.
	 *
	 * @param $group Group element.
	 * @param state  Force group to have a state.
	 */
	group.toggle.updateState = function( $group, state ) {
		var $input = $group.find( '.rwmb-group-state' ).last().find( 'input' );
		if ( state ) {
			$input.val( state );
		} else {
			state = $input.val();
		}
		// Store current state. Will be preserved when cloning.
		$input.attr( 'data-current', state );

		$group.toggleClass( 'rwmb-group-collapsed', 'collapsed' === state )
		      .find( '.rwmb-group-toggle-handle' ).first().attr( 'aria-expanded', 'collapsed' !== state );
	};

	/**
	 * Update group title.
	 *
	 * @param index   Group clone index.
	 * @param element Group element.
	 */
	group.toggle.updateTitle = function ( index, element ) {
		var $group = $( element ),
			$title = $group.find( '> .rwmb-group-title, > .rwmb-input > .rwmb-group-title' ),
			options = $title.data( 'options' ),
			content = '';

		function processField( fieldId, separator ) {
			separator = separator || '';

			var selectors = 'input[name*="[' + fieldId + ']"], textarea[name*="[' + fieldId + ']"], select[name*="[' + fieldId + ']"], button[name*="[' + fieldId + ']"]',
				$field = $group.find( selectors ),
				fieldValue = $field.val();

			if ( $field.is( 'select' ) ) {
				fieldValue = $field.find( 'option:selected' ).text();
			}

			if ( fieldValue ) {
				content += ( content ? separator : '' ) + fieldValue;
			}

			// Update title when field's value is changed.
			if ( ! $field.data( 'update-group-title' ) ) {
				$field.on( 'keyup change', _.debounce( function () {
					group.toggle.updateTitle( 0, element );
				}, 250 ) ).data( 'update-group-title', true );
			}
		}

		if ( 'undefined' === typeof options || 'undefined' === typeof options.type ) {
			return;
		}

		if ( 'text' === options.type ) {
			content = options.content.replace( '{#}', index );
		}
		if ( 'field' === options.type ) {
			var fieldId = options.field;

			// Multiple fields.
			if ( -1 !== fieldId.indexOf( ',' ) ) {
				options.separator = options.separator || ' ';
				var fieldIds = fieldId.split( ',' );
				fieldIds.forEach( function ( value ) {
					processField( value.trim(), options.separator );
				} );
			} else {
				processField( fieldId );
			}
		}
		$title.text( content );
	};

	/**
	 * Initialize the title on load or when new clone is added.
	 *
	 * @param container Wrapper (on load) or group element (when new clone is added)
	 */
	group.toggle.initTitle = function ( container ) {
		$( container ).find( '.rwmb-group-collapsible' ).each( function () {
			// Update group title for non-cloneable groups.
			if ( $( this ).hasClass( 'rwmb-group-non-cloneable' ) ) {
				group.toggle.updateTitle( 1, this );
				group.toggle.updateState( $( this ) );
				return;
			}

			$( this ).children( '.rwmb-input' ).each( function () {
				var $input = $( this );

				// Update group title.
				$input.children( '.rwmb-group-clone' ).each( function ( index, clone ) {
					group.toggle.updateTitle( index + 1, clone );
					group.toggle.updateState( $( clone ) );
				} );

				// Drag and drop clones via group title.
				if ( $input.data( 'ui-sortable' ) ) { // If sortable is initialized.
					$input.sortable( 'option', 'handle', '.rwmb-clone-icon + .rwmb-group-title' );
				} else { // If not.
					$input.on( 'sortcreate', function () {
						$input.sortable( 'option', 'handle', '.rwmb-clone-icon + .rwmb-group-title' );
					} );
				}
			} );
		} );
	};

	/**
	 * Update group index for inputs
	 */
	group.clone.updateGroupIndex = function () {
		var that = this,
			$clones = $( this ).parents( '.rwmb-group-clone' ),
			totalLevel = $clones.length;
		$clones.each( function ( i, clone ) {
			var index = parseInt( $( clone ).parent().data( 'next-index' ) ) - 1,
				level = totalLevel - i;

			group.clone.replaceName.call( that, level, index );

			// Stop each() loop immediately when reach the new clone group.
			if ( $( clone ).data( 'clone-group-new' ) ) {
				return false;
			}
		} );
	};

	group.clone.updateIndex = function() {
		var $this = $( this );

		// Update index only for sub fields in a group
		if ( ! $this.closest( '.rwmb-group-clone' ).length ) {
			return;
		}

		// Do not update index if field is not cloned
		if ( ! $this.closest( '.rwmb-input' ).children( '.rwmb-clone' ).length ) {
			return;
		}

		var index = parseInt( $this.closest( '.rwmb-input' ).data( 'next-index' ) ) - 1,
			level = $this.parents( '.rwmb-clone' ).length;

		group.clone.replaceName.call( this, level, index );

		// Stop propagation.
		return false;
	};

	/**
	 * Helper function to replace the level-nth [\d] with the new index.
	 * @param level
	 * @param index
	 */
	group.clone.replaceName = function ( level, index ) {
		var $input = $( this ),
			name = $input.attr( 'name' );
		if ( ! name ) {
			return;
		}

		var regex = new RegExp( '((?:\\[\\d+\\].*?){' + ( level - 1 ) + '}.*?)(\\[\\d+\\])' ),
			newValue = '$1' + '[' + index + ']';

		name = name.replace( regex, newValue );
		$input.attr( 'name', name );
	};

	/**
	 * Helper function to replace the level-nth [\d] with the new index.
	 * @param level
	 * @param index
	 */
	group.clone.replaceId = function ( level, index ) {
		var $input = $( this ),
			id = $input.attr( 'id' );
		if ( ! id ) {
			return;
		}

		var regex = new RegExp( '_(\\d*)$' ),
			newValue = '_' + Date.now();

		if ( regex.test( id ) ) {
			id = id.replace( regex, newValue );
		} else {
			id += newValue;
		}

		$input.attr( 'id', id );
	};

	/**
	 * When clone a group:
	 * 1) Remove sub fields' clones and keep only their first clone
	 * 2) Reset sub fields' [data-next-index] to 1
	 * 3) Set [name] for sub fields (which is done when 'clone' event is fired
	 * 4) Repeat steps 1)-3) for sub groups
	 * 5) Set the group title
	 *
	 * @param event The clone_instance custom event
	 * @param index The group clone index
	 */
	group.clone.processGroup = function ( event, index ) {
		var $group = $( this );
		if ( ! $group.hasClass( 'rwmb-group-clone' ) ) {
			return false; // Do not bubble up.
		}
		// Do not trigger clone on parents.
		event.stopPropagation();

		$group
			// Add new [data-clone-group-new] to detect which group is cloned. This data is used to update sub inputs' group index
			.data( 'clone-group-new', true )
			// Remove clones, and keep only their first clone. Reset [data-next-index] to 1
			.find( '.rwmb-input' ).each( function () {
				$( this ).data( 'next-index', 1 ).children( '.rwmb-clone:gt(0)' ).remove();
			} );

		// Update [group index] for inputs
		$group.find( inputSelectors ).each( function () {
			group.clone.updateGroupIndex.call( this );
		} );

		// Preserve the state (via [data-current]).
		$group.find( '[name*="[_state]"]' ).each( function() {
			$( this ).val( $( this ).data( 'current' ) );
		} );

		// Update group title for the new clone and set it expanded by default.
		if ( $group.closest( '.rwmb-group-collapsible' ).length ) {
			group.toggle.updateTitle( index + 1, $group );
			group.toggle.updateState( $group );
		}
		// Sub groups: reset titles, but preserve the state.
		group.toggle.initTitle( $group );

		$wrapper.trigger( 'clone_completed' );
	};

	// Run when DOM ready.
	$( function() {
		$wrapper = $( document );

		// Add event handlers to both group title and toggle icon.
		$wrapper.on( 'click', '.rwmb-group-title, .rwmb-group-toggle-handle', group.toggle.handle );
		group.toggle.initTitle( $wrapper );

		// Refresh maps to make them visible.
		$( window ).trigger( 'rwmb_map_refresh' );

		$wrapper.on( 'clone_instance', '.rwmb-clone', group.clone.processGroup );
		$wrapper.on( 'update_index', inputSelectors, group.clone.replaceId );
		$wrapper.on( 'clone', inputSelectors, group.clone.updateIndex );
	} );
} )( jQuery, _, document );
