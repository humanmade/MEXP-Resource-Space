(function ( $ ) {

	var $oldContainer = $('#resource-space-images');
	var $newContainer = $('#resource-space-new-images');

	var library = window.wp.media({
		frame: 'manage',
		container: $oldContainer,
		library: _wpMediaGridSettings.queryVars,
	}).open();

	jQuery('#resource-space-add-new').on( 'click', function( event ) {

		event.preventDefault();

		var insertImages = function() {

			if ( this.attachments.length ) {
				$newContainer.show();
			}

			_.each( this.attachments, function( attachment ) {
				library.state().attributes.library.add( attachment );
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

}( jQuery ));
