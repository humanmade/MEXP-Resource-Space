(function ( $ ) {

	var $oldContainer = ('#resource-space-images');
	var $newContainer = $('#resource-space-new-images');

	jQuery('#resource-space-add-new').on( 'click', function( event ) {

		event.preventDefault();

		var insertImages = function() {

			if ( this.attachments.length ) {
				$newContainer.show();
			}

			// Loop through attachments and build image element HTML.
			_.each( this.attachments, function( attachment ) {

				var $img = $('<img />');
				var size = 'medium';

				if ( attachment.sizes[ size ] ) {
					$img.attr( 'src', attachment.sizes[ size ].url );
					$img.attr( 'width', attachment.sizes[ size ].width );
					$img.attr( 'height', attachment.sizes[ size ].height );
				} else {
					$img.attr( 'src', attachment.url );
					$img.attr( 'width', attachment.width );
					$img.attr( 'height', attachment.height );
				}

				$img.attr( 'alt', attachment.title );

				$img.addClass( 'alignnone' );
				$img.addClass( 'size-' + size );
				$img.addClass( 'wp-image-' + attachment.id );

				$newContainer.append( $img );

			});

			this.complete();

		}

		var wp_media_frame = wp.media.frames.wp_media_frame = wp.media({
			frame : "post",
			state : 'mexp-service-resource-space',
			resourceSpaceInsertCallback: insertImages,
		});

		wp_media_frame.open();
		wp_media_frame.$el.addClass( 'hide-menu' );

	});

	window.wp.media({
		frame: 'manage',
		container: $oldContainer,
		library: { 'meta_query': 'resource_space' },
	}).open();

}( jQuery ));
