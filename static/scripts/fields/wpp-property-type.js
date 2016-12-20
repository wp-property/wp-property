// Feature Flag: WPP_FEATURE_FLAG_WPP_TYPE
jQuery(document).ready(function($){
    var wraper = $('.wpp-property-type');
    wraper.each(function(){
        var $this = $(this);
        var input_terms = $this.find('.wpp-terms-input');
        var btntoggle  = $this.find('.select-combobox-toggle');

        var autocomplete = input_terms.autocomplete({
            minLength: 0,
            source: $this.data('terms'),
        });

        input_terms.autocomplete( "instance" )._renderItem = function( ul, item ) {
          var exist = (item.label == input_terms.val());
          var selected = exist?'ui-state-selected':'';
          return $( "<li>" )
            .append( "<a class='"+selected+"'>" + item.label + "</a>" )
            .appendTo( ul );
        };

        input_terms.autocomplete( "widget" ).on('click', 'li', function( event) {
            var _this = jQuery(this);
            input_terms.val(_this.find('a').text());
            input_terms.autocomplete( "close" );
        });

        input_terms.autocomplete( "instance" )._resizeMenu = function () {
            var ul = this.menu.element;
            ul.outerWidth(input_terms.outerWidth() + btntoggle.outerWidth());
        }

        input_terms.on('focus', function(){
            var input = $(this);
            wasOpen = input.autocomplete( "widget" ).is( ":visible" );
            if ( wasOpen ) {
              return;
            }
 
            // Pass empty string as value to search for, displaying all results
            input.autocomplete( "search", input.val() );
        });

        if(input_terms.is(':focus'))
            input_terms.autocomplete( "search", '');

        var wasOpen;
        btntoggle.on('click', function(e){
            var input = $(this).siblings('input.wpp-terms-input');
            if(!input.hasClass('ui-autocomplete-input'))
                return input.focus();
            if ( wasOpen ) {
                btntoggle.blur();
                return;
            }
            input.focus();
            input.autocomplete( "search", '');
        })
        .mousedown(function() {
            var input = $(this).siblings('input.wpp-terms-input');
            if(input.hasClass('ui-autocomplete-input'))
                wasOpen = input.autocomplete( "widget" ).is( ":visible" );
        });

    });
});