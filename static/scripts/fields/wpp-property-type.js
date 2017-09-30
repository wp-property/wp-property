// Feature Flag: WPP_FEATURE_FLAG_WPP_LISTING_TYPE
jQuery(document).ready(function($){
    var wraper = $('.wpp-property-type');
    wraper.each(function(){
        var $this = $(this);
        var terms = $this.data('terms');
        var input_terms = $this.find('.wpp-terms-input');
        var input_terms_id = $this.find('.wpp-terms-term-id');

        var btntoggle  = $this.find('.select-combobox-toggle');

        var autocomplete = input_terms.autocomplete({
            minLength: 0,
            source: terms,
            focus: function (event, ui) {
                this.value = ui.item.label;
                event.preventDefault();
            },
            select: function (event, ui) {
                this.value = ui.item.label;
                input_terms_id.val(ui.item.value);
                event.preventDefault();
            }
        });

        input_terms.autocomplete( "instance" )._renderItem = function( ul, item ) {
          var exist = ( item.value == input_terms.val());
          var selected = exist?'ui-state-selected':'';
          return $( "<li>" )
            .append( "<a class='"+selected+"' data-value='" + item.value + "'>" + item.label + "</a>" )
            .appendTo( ul );
        };

        input_terms.autocomplete( "widget" ).on('click', 'li', function( event) {
            var _this = jQuery(this);
            input_terms_id.val(_this.find('a').data('value'));
            input_terms.val(_this.find('a').text());
            input_terms.autocomplete( "close" );
        });

        input_terms.autocomplete( "instance" )._resizeMenu = function () {
            console.log('_resizeMenu event');
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

        input_terms.on('focusout', function(){
            var input = $(this);
            var value = input.val().trim();
            var exist = false;
            jQuery.each( terms, function( k, i ) {
                if( (typeof i == 'string' && value == i) || value == i.label ) {
                    exist = true;
                }
            } );
            if(!exist){
                input.val('');
            }
        });

        if(input_terms.is(':focus')) {
            input_terms.autocomplete( "search", '');
        }

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