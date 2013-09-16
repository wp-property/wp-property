/**
 * WP-Property Global Scripts
 *
 * This file is included on all front-end and specific back-end pages.
 *
*/

/* Determine if page is loaded in frame. */
if ( top === self ) {
  //not in a frame
} else {
  //in a frame
  if( typeof window.wpp === 'object' &&
      typeof window.wpp.instance === 'object' &&
      typeof window.wpp.instance.iframe_enabled !== 'undefined' &&
      window.wpp.instance.iframe_enabled === true ) {
    // ignore. Application allows to be used in iframe
  } else {
    top.location.href = document.location.href;
  }
}

jQuery.extend( wpp = wpp || {}, {

  // Global Settings
  settings: {
    debug: false
  },

  /**
   * Global Debug Logger
   *
   * Accepts any number of arguments and passes them to console.log, if available.
   * Returns the first argument.
   *
   * @usage
   *    wpp.debug( 'debug data', { key: 'some value' } );
   *
   * @return {data} Returns first argument.
   * @method debug
   * @for wpp
   */
  debug: function debug( data ) {

    // Ignore if debugging is not enabled.
    if( !wpp.settings.debug ) {
      return data;
    }

    if( 'function' === typeof console.log ) {
      console.log.apply( console, arguments );
    }

    return data;

  },

  /**
   * Formatting Methods. (todo)
   *
   */
  format: {

    /**
     * Format Currency
     *
     */
    currency: function currency( selector ) {

      jQuery(selector).change(function() {
        this_value = jQuery(this).val();
        var val = jQuery().number_format( this_value.replace(/[^\d|\.]/g,'') );
        jQuery(this).val( val );
      });

    },

    /**
     * Format Number
     *
     */
    number: function number( selector ) {

      jQuery(selector).change(function() {
        this_value = jQuery(this).val();
        var val = jQuery().number_format( this_value.replace(/[^\d|\.]/g,''), {
          numberOfDecimals:0,
          decimalSeparator: '.',
          thousandSeparator: ','
        } );

        if(val == 'NaN') {
          val = '';
        }

        jQuery(this).val( val );
      });

    },

    /**
     * Add Commas
     *
     * @method commas
     */
    commas: function commas( nStr ) {

      nStr += '';
      x = nStr.split('.');
      x1 = x[0];
      x2 = x.length > 1 ? '.' + x[1] : '';
      var rgx = /(\d+)(\d{3})/;
      while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
      }
      return x1 + x2;

    }

  },

  /**
   * http://kevin.vanzonneveld.net
   * +      original by: Philippe Jausions (http://pear.php.net/user/jausions)
   * +      original by: Aidan Lister (http://aidanlister.com/)
   * + reimplemented by: Kankrelune (http://www.webfaktory.info/)
   * +      improved by: Brett Zamir (http://brett-zamir.me)
   * +      improved by: Scott Baker
   * +      improved by: Theriault
   * *        example 1: version_compare('8.2.5rc', '8.2.5a');
   * *        returns 1: 1
   * *        example 2: version_compare('8.2.50', '8.2.52', '<');
   * *        returns 2: true
   * *        example 3: version_compare('5.3.0-dev', '5.3.0');
   * *        returns 3: -1
   * *        example 4: version_compare('4.1.0.52','4.01.0.51');
   * *        returns 4: 1
   */
  version_compare: function(v1, v2, operator) {
    // BEGIN REDUNDANT
    this.php_js = this.php_js || {};
    this.php_js.ENV = this.php_js.ENV || {};
    // END REDUNDANT
    // Important: compare must be initialized at 0.
    var i = 0,
      x = 0,
      compare = 0,
      // vm maps textual PHP versions to negatives so they're less than 0.
      // PHP currently defines these as CASE-SENSITIVE. It is important to
      // leave these as negatives so that they can come before numerical versions
      // and as if no letters were there to begin with.
      // (1alpha is < 1 and < 1.1 but > 1dev1)
      // If a non-numerical value can't be mapped to this table, it receives
      // -7 as its value.
      vm = {
        'dev': -6,
        'alpha': -5,
        'a': -5,
        'beta': -4,
        'b': -4,
        'RC': -3,
        'rc': -3,
        '#': -2,
        'p': 1,
        'pl': 1
      },
      // This function will be called to prepare each version argument.
      // It replaces every _, -, and + with a dot.
      // It surrounds any nonsequence of numbers/dots with dots.
      // It replaces sequences of dots with a single dot.
      //    version_compare('4..0', '4.0') == 0
      // Important: A string of 0 length needs to be converted into a value
      // even less than an unexisting value in vm (-7), hence [-8].
      // It's also important to not strip spaces because of this.
      //   version_compare('', ' ') == 1
      prepVersion = function (v) {
        v = ('' + v).replace(/[_\-+]/g, '.');
        v = v.replace(/([^.\d]+)/g, '.$1.').replace(/\.{2,}/g, '.');
        return (!v.length ? [-8] : v.split('.'));
      },
      // This converts a version component to a number.
      // Empty component becomes 0.
      // Non-numerical component becomes a negative number.
      // Numerical component becomes itself as an integer.
      numVersion = function (v) {
        return !v ? 0 : (isNaN(v) ? vm[v] || -7 : parseInt(v, 10));
      };
    v1 = prepVersion(v1);
    v2 = prepVersion(v2);
    x = Math.max(v1.length, v2.length);
    for (i = 0; i < x; i++) {
      if (v1[i] == v2[i]) {
        continue;
      }
      v1[i] = numVersion(v1[i]);
      v2[i] = numVersion(v2[i]);
      if (v1[i] < v2[i]) {
        compare = -1;
        break;
      } else if (v1[i] > v2[i]) {
        compare = 1;
        break;
      }
    }
    if (!operator) {
      return compare;
    }

    // Important: operator is CASE-SENSITIVE.
    // "No operator" seems to be treated as "<."
    // Any other values seem to make the function return null.
    switch (operator) {
    case '>':
    case 'gt':
      return (compare > 0);
    case '>=':
    case 'ge':
      return (compare >= 0);
    case '<=':
    case 'le':
      return (compare <= 0);
    case '==':
    case '=':
    case 'eq':
      return (compare === 0);
    case '<>':
    case '!=':
    case 'ne':
      return (compare !== 0);
    case '':
    case '<':
    case 'lt':
      return (compare < 0);
    default:
      return null;
    }
  }

});

/**
 * Legacy Support
 *
 * @param selector
 * @returns {*}
 */
function wpp_format_currency( selector ) {
  return wpp.format.currency( selector );
}

/**
 * Legacy Support
 *
 * @param selector
 * @returns {*}
 */
function wpp_format_number( selector ) {
  return wpp.format.number( selector );
}

/**
 * Legacy Support
 *
 * @param data
 * @returns {*}
 */
function wpp_add_commas( data ) {
  return wpp.format.commas( data );
}