// **************************************************************
// image removal function - fired at load and after AJAX
// **************************************************************

	function imageRemoval() {

		jQuery('span.be-image-remove').on('click', function(event) {
			
			var image	= jQuery(this).attr('rel');
			var parent	= jQuery('form#post input#post_ID').prop('value');
			
			var data = {
				action: 'gallery_remove',
				image: image,
				parent: parent
			};

			jQuery.post(ajaxurl, data, function(response) {
				var obj;
				try {
					obj = jQuery.parseJSON(response);
				}
				catch(e) { // bad JSON catch
					// add some error messaging ?
					}

				if(obj.success === true) { // it worked. AS IT SHOULD.
					jQuery('div#be_gallery_metabox').find('div.be-image-wrapper').replaceWith(obj.gallery);
					imageRemoval();
					// add some success messaging ?
				}
				else { // something else went wrong
					// add some error messaging?
				}
			});

		});

	}

// **************************************************************
// now start the engine
// **************************************************************

jQuery(document).ready( function($) {


// **************************************************************
//  call the image removal function at load
// **************************************************************

	imageRemoval();

// **************************************************************
// AJAX function resetting the gallery with changes
// **************************************************************

	$('div#be_gallery_metabox input#update-gallery').on('click', function(event) {

		var parent	= $('form#post input#post_ID').prop('value');

		var data = {
			action: 'refresh_metabox',
			parent: parent
		};

		jQuery.post(ajaxurl, data, function(response) {
			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {  // bad JSON catch
				// add some error messaging ?
				}

			if(obj.success === true) { // it worked. AS IT SHOULD.
				$('div#be_gallery_metabox').find('div.be-image-wrapper').replaceWith(obj.gallery);
				imageRemoval();
				// add some success messaging ?
			}
			else {  // something else went wrong
				// add some error messaging ?
			}
		});
	});

// **************************************************************
//  what, you're still here? it's over. go home.
// **************************************************************

});