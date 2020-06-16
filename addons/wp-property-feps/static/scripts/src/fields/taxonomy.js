jQuery(document).ready(function($){
	var wraper = $('.rwmb-taxonomy-wrapper');
	wraper.each(function(){
		var $this = $(this);
		var tagchecklist = $this.find('.tagchecklist');
		var datataxcounter = $this.attr('data-tax-counter');
		var slug = $this.attr('data-slug');
		var btnAdd = $this.find('.tagadd');
		var input  = $this.find('.newtag');
		var template = $('#wpp-feps-taxnomy-template').html();

		input.autocomplete({
			source: window["availableTags_" + datataxcounter]
		});
		var autoComplete = input.autocomplete( "instance" );
		$(autoComplete.menu.activeMenu).on('click', '.ui-menu-item', function(){
			input.val($(this).html());
			autoComplete.close();
		})
		console.log(autoComplete.menu.activeMenu);
		btnAdd.on('click', function(e){
			var tag = input.val();
			var taglistChild = tagchecklist.children();
			if(tag == '') return;
			tag = tag.split(",");

			taglistChild.each(function(index, item){
				var val = $(item).find('input').val();
				var index = tag.indexOf(val);
				if (index >= 0) {
					tag.splice( index, 1 );
				}
			});

			$.each(tag, function(index, item){
				item = item.trim();
				var tmpl = _.template( template );
				tmpl = tmpl({
						i: taglistChild.length,
						val: item,
						slug: slug
					});
				tagchecklist.append(tmpl);
				input.val('');
			})
			
			input.trigger('tax-added');
		});
		input.keypress(function(e){
			if ( e.which == 13 ){
				btnAdd.trigger('click');
				e.preventDefault();
				return false;
			}
		});
		tagchecklist.on('click', '.ntdelbutton', function(){
			$(this).parent().parent().remove();
			input.trigger('tax-removed');
		})
	});
})