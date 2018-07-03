;(function ($, window, document) {
	'use strict';

	var conditions = window.conditions;

	/**
	 * Selector cache.
	 *
	 * @link https://ttmm.io/tech/selector-caching-jquery/
	 * @param $context The jQuery context. Pass empty string to use global context.
	 */
	function Selector_Cache( $context ) {
		this.collection = {};
		this.$context = $context;
	}
	Selector_Cache.prototype.get = function( selector ) {
		if ( undefined === this.collection[selector] ) {
			this.collection[selector] = this.$context ? this.$context.find( selector ) : $( selector );
		}

		return this.collection[selector];
	};

	/**
	 * Compare two variables
	 *
	 * @param Mixed needle Variable 1
	 * @param Mixed haystack Variable 2
	 * @param String Compare Operator
	 *
	 * @return boolean
	 * @return boolean
	 */
	function checkCompareStatement(needle, haystack, compare) {
		if (needle === null || typeof needle === 'undefined')
			needle = '';

		switch (compare) {
			case '=':
				if ($.isArray(needle) && $.isArray(haystack))
					return ( $(needle).not(haystack).length === 0 && $(haystack).not(needle).length === 0 );
				else
					return needle == haystack;

			case '>=':
				return needle >= haystack;

			case '>':
				return needle > haystack;

			case '<=':
				return needle <= haystack;

			case '<':
				return needle < haystack;

			case 'contains':
				if ($.isArray(needle))
					return $.inArray(haystack, needle) > -1;
				else if ($.type(needle) == 'string')
					return needle.indexOf(haystack) > -1;

			case 'in':
				if ($.isArray(needle)) {
					var found = false;
					$.each(needle, function (index, value) {
						if ($.isNumeric(value))
							value = parseInt(value);

						if (haystack.indexOf(value) > -1)
							found = true;
					});

					return found;
				} else {
					return haystack.indexOf(needle) > -1;
				}

			case 'start_with':
				return needle.indexOf(haystack) === 0;

			case 'starts with':
				return needle.indexOf(haystack) === 0;

			case 'end_with':
				haystack = new RegExp(haystack + '$');
				return haystack.test(needle);

			case 'ends with':
				haystack = new RegExp(haystack + '$');
				return haystack.test(needle);

			case 'match':
				haystack = new RegExp(haystack);
				return haystack.test(needle);

			case 'between':
				if ($.isArray(haystack) && typeof haystack[0] != 'undefined' && typeof haystack[1] != 'undefined')
					return checkCompareStatement(needle, haystack[0], '>=') && checkCompareStatement(needle, haystack[1], '<=');
		}
	}

	/**
	 * Put a selector, then retrieve values
	 *
	 * @param {string} fieldName Field name
	 * @param {string|object} $scope The field scope (jQuery object). Empty string if no scope.
	 */
	function getFieldValue(fieldName, $scope) {
		var selectors = new Selector_Cache( $scope );

		// Allows user define conditional logic by callback
		if ( checkCompareStatement( fieldName, '(', 'contains' ) ) {
			return eval( fieldName );
		}

		var $field = $( '#' + fieldName );

		if ( $field.length && $field.attr( 'type' ) !== 'checkbox' && typeof $field.val() != 'undefined' && $field.val() != null && $scope == '' ) {
			fieldName = '#' + fieldName;
		}

		// If fieldName start with #. Then it's an ID, just return it values
		if ( checkCompareStatement( fieldName, '#', 'start_with' ) && $( fieldName ).attr( 'type' ) !== 'checkbox'
		     && typeof $( fieldName ).val() != 'undefined' && $( fieldName ).val() != null && $scope == '' ) {
			return $( fieldName ).val();
		}

		var selector = null,
			isMultiple = false;

		// Try to find the element via [name] attribute.
		if ( $( '[name="' + fieldName + '"]' ).length ) {
			selector = '[name="' + fieldName + '"]';
		}
		if ( $( '[name*="' + fieldName + '"]' ).length ) {
			selector = '[name*="' + fieldName + '"]';
		}
		if ( $( '[name*="' + fieldName + '[]"]' ).length ) {
			selector = '[name*="' + fieldName + '[]"]';
			isMultiple = true;
		}

		if ( null === selector ) {
			return 0;
		}

		var $selector = $( selector ),
			selectorType = $selector.attr( 'type' );
		selectorType = selectorType ? selectorType : $selector.prop( 'tagName' );

		var isSelectTree = 'SELECT' === selectorType && isMultiple;

		if ( ['checkbox', 'radio', 'hidden'].indexOf( selectorType ) === - 1 && ! isSelectTree ) {
			var $element = selectors.get( selector );
			return $element.val();
		}

		// If user selected a checkbox, radio, or select tree, return array of selected fields, or value of singular field.
		var values = [],
			elements = [];

		if ( selectorType === 'hidden' && fieldName !== 'post_category' && ! checkCompareStatement( selector, 'tax_input', 'contains' ) ) {
			elements = selectors.get( selector );
		} else if ( isSelectTree ) {
			elements = selectors.get( selector );
		} else {
			selector += ':checked';
			elements = selectors.get( selector );
		}

		elements.each( function () {
			values.push( $( this ).val() );
		} );

		return values.length > 1 ? values : values.pop();
	}

	/**
	 * Check if logics attached to fields is correct or not
	 *
	 * If a field is hidden by Conditional Logic, then all dependent fields are hidden also
	 *
	 * @param  Array  logics All logics applied to field
	 *
	 * @return {Boolean}
	 */
	function isLogicCorrect(logics, $field) {
		var relation = ( typeof logics.relation != 'undefined' ) ? logics.relation : 'and',
			success = relation == 'and';

		$.each(logics.when, function (index, logic) {
			// Get scope of current field. Scope is only applied for Group field.
			// A scope is a group or whole meta box which contains event source and current field.
			var $scope = getFieldScope($field, logic[0]);

			var dependentField = guessSelector(logic[0]);
			dependentField = $scope != '' ? $scope.find(dependentField) : $(dependentField);

			var isDependentFieldVisible = dependentField.closest('.rwmb-field').attr('data-visible');

			if ('hidden' == isDependentFieldVisible) {
				success = 'hidden';
			} else {
				var item = getFieldValue(logic[0], $scope),
					compare = logic[1],
					value = logic[2],
					check = false,
					negative = false;

				// Cast to string if array has 1 element and its a string
				if ($.isArray(item) && item.length === 1)
					item = item[0];

				// Allows user using NOT statement.
				if (checkCompareStatement(compare, 'not', 'contains') || checkCompareStatement(compare, '!', 'contains')) {
					negative = true;
					compare = compare.replace('not', '');
					compare = compare.replace('!', '');
				}

				compare = compare.trim();

				if ($.isNumeric(item))
					item = parseInt(item);

				check = checkCompareStatement(item, value, compare);

				if (negative)
					check = !check;

				success = ( relation === 'and' ) ? success && check : success || check;
			}
		});

		return success;
	}

	function getFieldScope($field, eventSource) {
		eventSource = guessSelector(eventSource);

		var $scope = '',
			$wrapper = $(eventSource).closest('.rwmb-clone');

		if (!$wrapper.length || $field == '')
			return $scope;

		$wrapper.addClass('field-scope');

		$scope = $field.closest('.field-scope');

		$wrapper.removeClass('field-scope');

		return $scope;
	}

	/**
	 * Run all conditional logic statements then show / hide fields or meta boxes
	 *
	 * @param  Array conditions All defined conditional
	 *
	 * @return void
	 */
	function runConditionalLogic(conditions) {
		// Store all change selector here
		window.eventSource = [];

		$('.rwmb-conditions').each(function () {
			var field = $(this),
				fieldConditions = field.data('conditions'),
				action = typeof fieldConditions['hidden'] != 'undefined' ? 'hidden' : 'visible',
				logic = fieldConditions[action];

			var logicApply = isLogicCorrect(logic, field);

			var $selector = field.parent().hasClass('rwmb-field') ? field.parent() : field.closest('.postbox');

			if (logicApply === true)
				action == 'visible' ? applyVisible($selector) : applyHidden($selector);
			else if (logicApply === false)
				action == 'visible' ? applyHidden($selector) : applyVisible($selector);
			else if (logicApply === 'hidden')
				applyHidden($selector);

			$.each(logic.when, function (i, single_logic) {
				if (!checkCompareStatement(single_logic[0], '(', 'contains')) {
					var singleLogicSelector = guessSelector(single_logic[0]);
					if (($.inArray(singleLogicSelector, window.eventSource) < 0) && typeof singleLogicSelector != 'undefined')
						window.eventSource.push(singleLogicSelector);
				}
			});
		});

		// Outside Conditions
		$.each(conditions, function (field, logics) {

			$.each(logics, function (action, logic) {

				if (typeof logic.when == 'undefined') return;

				var selector = guessSelector(field),
					logicApply = isLogicCorrect(logic, '');

				if (logicApply === true)
					action == 'visible' ? applyVisible($(selector)) : applyHidden($(selector));
				else if (logicApply === false)
					action == 'visible' ? applyHidden($(selector)) : applyVisible($(selector));
				else if (logicApply === 'hidden')
					applyHidden($(selector));

				// Add Start Point
				$.each(logic.when, function (i, single_logic) {
					if (!checkCompareStatement(single_logic[0], '(', 'contains')) {
						var singleLogicSelector = guessSelector(single_logic[0]);

						if (($.inArray(singleLogicSelector, window.eventSource) < 0) && typeof singleLogicSelector != 'undefined')
							window.eventSource.push(singleLogicSelector);
					}
				});
			});
		});
		window.eventSource.push('.add-clone');
		window.eventSource = window.eventSource.join();
	}

	/**
	 * Guess the selector by field name
	 *
	 * @param  String fieldName Field Name
	 *
	 * @return String selector Field Selector
	 */
	function guessSelector(fieldName) {
		if (checkCompareStatement(fieldName, '(', 'contains'))
			return null;

		if ($(fieldName).length || isUserDefinedSelector(fieldName))
			return fieldName;

		// If field id exists. Then return it values
		try {
			if ($('#' + fieldName).length
				&& $('#' + fieldName).attr('type') !== 'checkbox'
				&& !$('#' + fieldName).attr('name')
				&& !$('#' + fieldName).closest('.rwmb-clone')
			)
				return '#' + fieldName;

			if ($('[name="' + fieldName + '"]').length)
				return '[name="' + fieldName + '"]';

			if ($('[name^="' + fieldName + '"]').length)
				return '[name^="' + fieldName + '"]';

			if ($('[name*="' + fieldName + '"]').length)
				return '[name*="' + fieldName + '"]';
		} catch (e) {
			console.log(e);
		}
	}

	function isUserDefinedSelector(fieldName) {
		return checkCompareStatement(fieldName, '.', 'starts with') ||
			checkCompareStatement(fieldName, '#', 'starts with') ||
			checkCompareStatement(fieldName, '[name', 'contains') ||
			checkCompareStatement(fieldName, '>', 'contains') ||
			checkCompareStatement(fieldName, '*', 'contains') ||
			checkCompareStatement(fieldName, '~', 'contains');
	}

	function getToggleType($element) {
		var toggleType = 'display';
		var hasToggleTypeDefined = $element.closest('.postbox').find('.mbc-toggle-type');
		if (hasToggleTypeDefined.length) {
			toggleType = hasToggleTypeDefined.data('toggle_type');
		}

		return toggleType;
	}

	/**
	 * Visible field or entire meta box
	 *
	 * @param  Element element Element Selector
	 * @param String toggleType 'visibility' or 'display'
	 *
	 * @return void
	 */
	function applyVisible($element) {
		var toggleType = getToggleType($element);

		// Element is a Field. Find the field wrapper and show.
		if ($element.closest('.rwmb-field').length)
			$element = $element.closest('.rwmb-field');

		if (toggleType === 'display') {
			$element.show();
		}
		if (toggleType === 'slide') {
			$element.slideDown();
		}
		if (toggleType === 'fade') {
			$element.fadeIn();
		}
		else {
			$element.css('visibility', 'visible');
		}

		$element.attr('data-visible', 'visible');
	}

	/**
	 * Hide field or entire meta box
	 * @param String toggleType 'visibility' or 'display'
	 *
	 * @return void
	 */
	function applyHidden($element) {
		var toggleType = getToggleType($element);

		// Element is a Field. Find the field wrapper and show.
		if ($element.closest('.rwmb-field').length)
			$element = $element.closest('.rwmb-field');

		if (toggleType === 'display') {
			$element.hide();
		}
		if (toggleType === 'slide') {
			$element.slideUp();
		}
		if (toggleType === 'fade') {
			$element.fadeOut();
		}
		else {
			$element.css('visibility', 'hidden');
		}

		$element.attr('data-visible', 'hidden');
	}

	/**
	 * Initialize.
	 */
	function init() {
		runConditionalLogic(conditions);
	}

	// Run when page finishes loading to improve performance.
	// https://github.com/wpmetabox/meta-box/issues/1195.
	$(window).on('load', function(){
		// Load conditional logic by default.
		init();

		var $document = $(document);

		// Listening eventSource apply conditional logic when eventSource is change.
		if (window.eventSource.length > 1) {
			$document.on('change keyup click', window.eventSource, init);
		}

		// Featured image replaces HTML, thus the event listening above doesn't work.
		// We have to detect DOM change.
		if (-1 !== window.eventSource.indexOf('_thumbnail_id')) {
			$('#postimagediv').on('DOMSubtreeModified', init);
		}

		// For groups.
		$document.on('clone_completed', init);
	});
})(jQuery, window, document);
