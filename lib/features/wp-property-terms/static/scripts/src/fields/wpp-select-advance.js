jQuery(document).ready(function($){
    var wraper = $('.rwmb-wpp-taxonomy-wrapper');
    wraper.each(function(){
        var $this = $(this);
        var tagchecklist = $this.find('.tagchecklist');
        var datataxcounter = $this.attr('data-tax-counter');
        var attrName = $this.attr('data-name');
        var taxonomy = $this.attr('data-taxonomy');
        var btnAdd = $this.find('.taxadd');
        var input_terms  = $this.find('.wpp-terms-input');
        var template = $('#wpp-terms-taxnomy-template').html();
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
                    input_terms.removeClass('ui-autocomplete-loading');
                    taxList = data;
                    input_terms.autocomplete({
                        minLength: 0,
                        source: data,
                        focus: function( event, ui ) {
                            var input = $(this);
                            input.val( ui.item.label );
                            return false;
                        },
                        select: function( event, ui ) {
                            var input = $(this);
                            input.val( ui.item.label );
                            return false;
                        }

                    })
                    input_terms.each(function(){
                        var input = $(this);
                        input.autocomplete( "instance" )._renderItem = function( ul, item ) {
                          var exist = is_already_added(item.value, tagchecklist.children());
                          var selected = exist?'ui-state-selected':'';
                          return $( "<li>" )
                            .append( "<a class='"+selected+"'>" + item.label + "</a>" )
                            .appendTo( ul );
                        };
                        input.autocomplete( "widget" ).addClass('wpp-autocomplete');
                    });


                    input_terms.on('focus', function(){
                        var input = $(this);
                        wasOpen = input.autocomplete( "widget" ).is( ":visible" );
                        if ( wasOpen ) {
                          return;
                        }
             
                        // Pass empty string as value to search for, displaying all results
                        input.autocomplete( "search", "" );
                    });

                    if(input.is(':focus'))
                        input.autocomplete( "search", "" );
                }); 
            });
        });

        btnAdd.on('click', function(e){
            var input_term = $(this).siblings('.wpp-terms-term');
            var input_parent = $(this).siblings('.wpp-terms-parent');
            var parent = input_parent.val();
            var tag = input_term.val();
            var taglistChild = tagchecklist.children();
            if(tag == ''){
                input_term.focus();
                return;
            }
            tag = tag.split(",");

            // parent searching if it's available in tax list, if then use tax id.
            $.each(taxList, function(i, tax){
                if(parent == tax.label){
                    parent = 'tID_' + tax.value;
                    return false;
                }
            });

            $.each(tag, function(index, item){
                var item = item.trim();
                var label = item;
                var exist = false;

                // searching if it's available in taxlist, if then use tax id.
                $.each(taxList, function(i, tax){
                    if(item == tax.label){
                        item = 'tID_' + tax.value;
                        label = tax.label;
                        return false;
                    }
                });

                input_name = attrName + "[" + tagchecklist.children().length  + "]";
                // If already added
                exist = is_already_added(item, taglistChild);

                if(exist != true){
                    var tmpl = _.template( template);
                    var rendered = tmpl({label: label, term: item, name: input_name, parent: parent});
                    tagchecklist.append(rendered);
                }
            });
            
            input_terms.val('');
            
        });

        // Hook for enter key.
        input_terms.keypress(function(e){
            if ( e.which == 13 ){
                btnAdd.trigger('click');
                e.preventDefault();
                return false;
            }
        });


    });
    $(document).on('click', '.assign-parent', function(){
        var parent = $(this).siblings('.wpp-terms-parent').toggle();
        if(parent.is(":hidden"))
            parent.val('');

    });
    // Remove tag button.
    $(document).on('click', '.rwmb-wpp-taxonomy-wrapper .tagchecklist .ntdelbutton', function(){
        $(this).parent().remove();
    });

    var is_already_added = function(value, tagList){
        exist = false;
        $.each(tagList, function(i, tag){
            var val = $(tag).find('input').val();
            if (value == val) {
                exist = true;
                return false;
            }
        });
        return exist;
    }
});