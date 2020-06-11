jQuery( function ( $ )
{
	'use strict';

	var template = $( '#tmpl-rwmb-file-advanced' ).html();

	$( 'body' ).on( 'click', '.rwmb-file-advanced-upload', function ( e )
	{
		e.preventDefault();

		var $uploadButton = $( this ),
			$fileList = $uploadButton.siblings( '.rwmb-uploaded' ),
			maxFileUploads = $fileList.data( 'max_file_uploads' ),
			mimeType = $fileList.data( 'mime_type' ),
			msg = maxFileUploads > 1 ? rwmbFile.maxFileUploadsPlural : rwmbFile.maxFileUploadsSingle,
			frame,
			frameOptions = {
				className: 'media-frame rwmb-file-frame',
				multiple : true,
				title    : rwmbFileAdvanced.frameTitle
			};

		msg = msg.replace( '%d', maxFileUploads );

		// Create a media frame
		if ( mimeType )
		{
			frameOptions.library = {
				type: mimeType
			};
		}
		frame = wp.media( frameOptions );

		// Open media uploader
		frame.open();

		// Remove all attached 'select' event
		frame.off( 'select' );

		// Handle selection
		frame.on( 'select', function ()
		{
			// Get selections
			var selection = frame.state().get( 'selection' ).toJSON(),
				uploaded = $fileList.children().length,
				ids;

			if ( maxFileUploads > 0 && ( uploaded + selection.length ) > maxFileUploads )
			{
				if ( uploaded < maxFileUploads )
				{
					selection = selection.slice( 0, maxFileUploads - uploaded );
				}
				alert( msg );
			}

			console.log("selection::");
			console.log(selection);
			// Get only files that haven't been added to the list
			// Also prevent duplication when send ajax request
			selection = _.filter( selection, function ( attachment )
			{
				return $fileList.children( 'li#item_' + attachment.id ).length === 0;
			} );
			ids = _.pluck( selection, 'id' );
			if ( ids.length > 0 ){
				$(selection).each(function(index, slec){
					var input = $('<input />', {
						name: $fileList.data( 'field_id' ) + "[]",
						value: ids[index],
						type: 'hidden'
					});
					var tmpl = _.template( template, {
							evaluate   : /<#([\s\S]+?)#>/g,
							interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
							escape     : /\{\{([^\}]+?)\}\}(?!\})/g
						} );
					tmpl = tmpl({ attachments: [slec] });
					tmpl = $(tmpl).append(input);
					$fileList
						.append( tmpl )
						.trigger( 'update.rwmbFile' );
				});
			}
		} );
	} );
} );
