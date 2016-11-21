jQuery(document).ready(function($){
    var wraper = $('.wpp-taxonomy-select-combobox');
    wraper.each(function(){
        var $this = $(this);
        var taxonomy = $this.attr('data-taxonomy');
        var input_terms = $this.find('.wpp-terms-input');
        var btntoggle  = $this.find('.select-combobox-toggle');
        var taxList = {};

        var query = {
            action: 'term_autocomplete',
            taxonomy: taxonomy,
        };

        var url = ajaxurl + '?' + jQuery.param(query);
        input_terms.each(function(){
            var input = $(this);
            input.one('focus', function(){
                if(input.hasClass('ui-autocomplete-loading') || input.hasClass('ui-autocomplete-input'))
                    return;
                input_terms.addClass('ui-autocomplete-loading');
                $.ajax(url)
                 .done(function(data){
                    taxList = data;
                    input_terms.removeClass('ui-autocomplete-loading');
                    input_terms.autocomplete({
                        minLength: 0,
                        source: data,
                        focus: function( event, ui ) {
                            var input = $(this);
                            input.val( ui.item.label );
                            onInputChange.apply(input);
                            return false;
                        },
                        select: function( event, ui ) {
                            var input = $(this);
                            input.val( ui.item.label );
                            onInputChange.apply(input);
                            return false;
                        }

                    })
                    input_terms.each(function(){
                        var input = $(this);
                        input.autocomplete( "instance" )._renderItem = function( ul, item ) {
                          var exist = (item.label == input.val());
                          var selected = exist?'ui-state-selected':'';
                          return $( "<li>" )
                            .append( "<a class='"+selected+"'>" + item.label + "</a>" )
                            .appendTo( ul );
                        };
                        input.autocomplete( "instance" )._resizeMenu = function () {
                          var ul = this.menu.element;
                          ul.outerWidth(input.outerWidth() + btntoggle.outerWidth());
                        }
                        input.autocomplete( "widget" ).addClass('wpp-autocomplete');
                    });


                    input_terms.on('focus', function(){
                        var input = $(this);
                        wasOpen = input.autocomplete( "widget" ).is( ":visible" );
                        if ( wasOpen ) {
                          return;
                        }
             
                        // Pass empty string as value to search for, displaying all results
                        input.autocomplete( "search", input.val() );
                    });
                    if(input.is(':focus'))
                        input.autocomplete( "search", '');
                }); 

            });
        });

        input_terms.on('keyup change input', function(){
            onInputChange.call(this);
        });

        var onInputChange = function(e){
            $input = $(this);
            var value = $input.val();
            $term_input = $input.siblings('.wpp-terms-id-input');
            $.each(taxList, function(i, tax){
                if(tax.label == $input.val()){
                    value = 'tID_' + tax.value;
                    return false;
                }
            });
            $term_input.val(value);
        };

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

        $this.find('.term-parent').hide();     

    });

    $(document).on('click', '.assign-parent', function(){
        var parent = $(this).siblings('.term-parent').toggle();
        if(parent.is(":hidden"))
            parent.val('');
    });
});