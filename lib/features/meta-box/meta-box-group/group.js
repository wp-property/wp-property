jQuery( function ( $ ) {
	'use strict';

	var $wrapper = $( '#wpbody' );

	/**
	 * Functions to handle input's name.
	 */
	var input = {
		updateGroupIndex: function () {
			var that = this,
				$clones = $( this ).parents( '.rwmb-group-clone' ),
				totalLevel = $clones.length;
			$clones.each( function ( i, clone ) {
				var index = parseInt( $( clone ).parent().data( 'next-index' ) ) - 1,
					level = totalLevel - i;
				input.replaceName.call( that, level, index );

				// Stop each() loop immediately when reach the new clone group.
				if ( $( clone ).data( 'clone-group-new' ) ) {
					return false;
				}
			} );
		},
		updateIndex: function () {
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
			input.replaceName.call( this, level, index );

			// Stop propagation.
			return false;
		},
		// Replace the level-nth [\d] with new index
		replaceName: function ( level, index ) {
			var $input = $( this ),
				name = $input.attr( 'name' );
			if ( ! name ) {
				return;
			}

			var regex = new RegExp( '((?:\\[\\d+\\].*?){' + ( level - 1 ) + '}.*?)(\\[\\d+\\])' ),
				newValue = '$1' + '[' + index + ']';

			name = name.replace( regex, newValue );
			$input.attr( 'name', name );
		}
	};

	$wrapper.on( 'clone', ':input[class|="rwmb"]', input.updateIndex );

	/**
	 * When clone a group:
	 * 1) Remove sub fields' clones and keep only their first clone
	 * 2) Reset sub fields' [data-next-index] to 1
	 * 3) Set [name] for sub fields (which is done when 'clone' event is fired
	 * 4) Repeat steps 1)-3) for sub groups
	 */
	$wrapper.on( 'clone_instance', '.rwmb-clone', function () {
		if ( ! $( this ).hasClass( 'rwmb-group-clone' ) ) {
			return false;
		}

		$( this )
			// Add new [data-clone-group-new] to detect which group is cloned. This data is used to update sub inputs' group index
			.data( 'clone-group-new', true )
			// Remove clones, and keep only their first clone. Reset [data-next-index] to 1
			.find( '.rwmb-input' ).each( function () {
				$( this ).data( 'next-index', 1 ).children( '.rwmb-clone:gt(0)' ).remove();
			} )
			.end()
			// Update [group index] for inputs
			.find( ':input[class|="rwmb"]' ).each( function () {
				input.updateGroupIndex.call( this );
			} );

		$wrapper.trigger( 'clone_completed' );

		// Stop propagation to not trigger the same event on parent's clone.
		return false;
	} );
} );
