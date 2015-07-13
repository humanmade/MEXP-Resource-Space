(function( window, $ ){

	var controller = media.controller.MEXP;

	media.controller.MEXP = controller.extend({

		mexpInsert: function() {

			var self = this, $button, $spinner, selection, attachments, complete, toggleLoading, insertImages, doItem;

			if ( this.inserting ) {
				return;
			}

			this.inserting = true;

			// Only use this custom insert function for resource space.
			if ( this.id !== "mexp-service-resource-space" ) {
				controller.mexpInsert();
			}

			$button     = $( '#mexp-button' );
			$spinner    = $button.parent().find('.spinner');
			selection   = self.frame.content.get().getSelection();
			attachments = [];

			if ( ! $spinner.length ) {
				$spinner = $( '<span/>', { 'class': 'spinner', 'style': 'margin: 20px 0 20px 10px; float: left;' } );
				$spinner.insertBefore( $button );
			}

			complete = function() {
				toggleLoading( false );
				selection.reset();
				self.frame.close();
				delete this.inserting;
			}

			toggleLoading = function( enable ) {
				$spinner.toggleClass( 'is-active', enable );
				$button.attr( 'disabled', enable );
				$('.mexp-items').toggleClass( 'resourcespace-loading', enable );
			}

			/**
			 * Insert the images.
			 * Create image HTML and insert into currently active editor.
			 *
			 * @return null
			 */
			insertImages = function() {

				// For storing array of image HTML.
				var images = [];

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

					images.push( $img.prop('outerHTML') );

				});

				// Insert all HTML in one go.
				if ( typeof( tinymce ) === 'undefined' || tinymce.activeEditor === null || tinymce.activeEditor.isHidden() ) {
					media.editor.insert( images.join( "\n\n" ) + "\n\n" );
				} else {
					media.editor.insert( "<p>" + images.join( "</p><p>" ) + "</p>" );
				}

				this.complete();

			}

			/**
			 * Proccess a single selection item.
			 * Fetch the
			 * @param
			 * @return {[type]}       [description]
			 */
			doItem = function( model ) {

				$.post( ajaxurl, {
					action:      'pj_rs_get_resource',
					resource_id: model.get( 'id' ),
					post:        parseInt( $('#post_ID').val() ),
				}).done( function( response ) {

					if ( ! response.success ) {
						alert( response.data );
						return;
					}

					attachments.push( response.data );

				}).always( function() {

					if ( attachments.length >= selection.length ) {

						var callback = insertImages;

						// Allow overriding insert callback.
						if ( self.frame.options.resourceSpaceInsertCallback ) {

							callback = self.frame.options.resourceSpaceInsertCallback
						}

						callback = _.bind( callback, { attachments: attachments, complete: complete } );
						callback();

					}

				}).fail( function( response ) {
					alert( 'There was a problem importing your image.' );
				} );

			}

			toggleLoading( true );
			selection.each( doItem );

		},

	});

	var view = media.view.MEXP

	media.view.MEXP = view.extend( {

		fetchedSuccess: function( response ) {

			media.view.MEXP.__super__.fetchedSuccess.apply( this, [response] );

			if ( response.meta.page >= response.meta.total_pages  ) {
				jQuery( '#' + this.service.id + '-loadmore' ).attr( 'disabled', true );
			}

		}

	} );

})( window, this.jQuery );
