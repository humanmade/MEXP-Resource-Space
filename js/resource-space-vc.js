(function ( $ ) {
	$(document).ready( function() {
		window.setTimeout( function() {

			var _vcSingleImage = media.VcSingleImage;
			var l10n = i18nLocale;

			media.VcSingleImage = _.extend( _vcSingleImage, {

				// Frame constructor function.
				// This is a copy-paste from VC media-editor.js
				// With the addition of the MEXP tab.
				frame: function ( element ) {

					var self = this;

					this.element = element;

					this.$button       = $( this.element );
					this.$block        = this.$button.closest( '.edit_form_line' );
					this.$hidden_ids   = this.$block.find( '.gallery_widget_attached_images_ids' );
					this.$img_ul       = this.$block.find( '.gallery_widget_attached_images_list' );
					this.$clear_button = this.$img_ul.next();

					if ( this._frame ) {
						return this._frame;
					}

					// Insert images callback.
					// Append attachments to the content region collection.
					var insertIDs = function() {

						var contentRegion = wp.media.frame.views.get( '.media-frame-content' );

						if ( contentRegion[0] ) {
							_.each( this.attachments, function( attachment ) {
								contentRegion[0].collection.add( attachment );
							} );
						}

						this.complete();

					}

					var resourceSpaceFrame = wp.media.frames.resourceSpaceFrame = wp.media({
						frame : "post",
						state : 'mexp-service-resource-space',
						resourceSpaceInsertCallback: insertIDs,
					});

					// Update the MEXP frame insert button text.
					resourceSpaceFrame.on( 'toolbar:render:mexp-service-resource-space-toolbar', function ( toolbar ) {
						toolbar.$el.find( '.media-button-inserter' ).text( resourceSpaceVC.stockImagesInsertText );
					}, resourceSpaceFrame );

					this._frame = wp.media( {
						state: 'vc_single-image',
						states: [ new wp.media.controller.VcSingleImage() ]
					} );

					// Add the stock images tab.
					this._frame.on( 'router:render:browse', function ( routerView ) {

						routerView.set({
							resourceSpace: {
								text:     resourceSpaceVC.stockImagesTabText,
								priority: 60
							}
						});

						routerView.controller.content.mode('browse');

					} );

					// Trigger the MEXP frame to open when the stock images tab is visited.
					this._frame.on( 'content:render:resourceSpace', function() {

						resourceSpaceFrame.open();

						// Switch content view back to browse in the original frame.
						var routerView = wp.media.frame.views.get( '.media-frame-router' )[0];
						routerView.controller.content.mode('browse');

						window.setTimeout( function() {

							// Ensure that the resource space frame is on top.
							resourceSpaceFrame.$el.closest('.media-modal').parent().appendTo( 'body' );

							// Hide all other menu options in the frame.
							resourceSpaceFrame.$el.addClass( 'hide-menu' );

						}, 1 );

						// Slightly hcky workaround because for some reason the load more
						// button doesn't exist when the event callback is attached.
						$('#resource-space-loadmore').on('click', function(e) {
							var view = wp.media.frames.resourceSpaceFrame.views.get('.media-frame-content' );
							if ( view.length ) {
								view[0].paginate(e);
							}
						} );

					}, this );

					// Create the toolbar.
					this._frame.on( 'toolbar:create:vc_single-image', function ( toolbar ) {
						this.createSelectToolbar( toolbar, {
							text: l10n.set_image
						} );
					}, this._frame );

					this._frame.state( 'vc_single-image' ).on( 'select', this.select );

					return this._frame;

				},

			});

		}, 100 );
	} )
}( jQuery ));
