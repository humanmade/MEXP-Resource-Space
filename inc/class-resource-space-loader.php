<?php
/*
 *
 * Support class for Resourcespace mexp
*/
class Resource_Space_Loader {

	protected static $instance;

	/**
	 * Create singleton instance.
	 * @return HM_Reviews
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'wp_ajax_pj_rs_get_resource', array( $this, 'ajax_get_image' ) );
		add_action( 'wp_ajax_pj_rs_proxy_resource' , array( $this, 'ajax_proxy_image' ) );
	}

	/**
	 * Ajax handler to retrieve content from Resource space and add as attachment.
	 */
	function ajax_get_image() {

		$resource_id    = intval( $_POST['resource_id'] );
		$parent_post_id = isset( $_POST['post'] ) ? intval( $_POST['post'] ) : 0;

		if ( empty( $resource_id ) ) {
			wp_send_json_error( __( 'Empty resource id', 'resourcespace' ) );
		}

		$url = PJ_RESOURCE_SPACE_DOMAIN . '/plugins/api_search/';
		$key = PJ_RESOURCE_SPACE_KEY;

		$url = add_query_arg( array(
			'key'              => $key,
			'search'           => $resource_id,
			'prettyfieldnames' => 1,
			'previewsize'      => 'sit',
		), $url );

		$request_args = array( 'headers' => array() );

		// Pass basic auth header if available.
		if ( defined( 'PJ_RESOURCE_SPACE_AUTHL' ) &&  defined( 'PJ_RESOURCE_SPACE_AUTHP' ) ) {
			$request_args['headers']['Authorization'] = 'Basic ' . base64_encode( PJ_RESOURCE_SPACE_AUTHL . ':' . PJ_RESOURCE_SPACE_AUTHP );
		}

		$response = wp_remote_get( $url, $request_args );

		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ) );
		} else {
			wp_send_json_error( __( 'Unable to query API', 'resourcespace' ) );
		}

		// All good, continue

		$downloadurl       = $data[0]->preview;
		$file              = get_temp_dir() . sanitize_file_name( $data[0]->Original_filename );

		$response = wp_remote_get( $downloadurl, $request_args );
		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {

			file_put_contents( $file, wp_remote_retrieve_body( $response ) );

			$filename = basename( $file );

			$upload_file = wp_upload_bits( $filename, null, file_get_contents( $file ) );

			if ( ! $upload_file['error'] ) {

				$wp_filetype = wp_check_filetype( $filename, null );

				date_default_timezone_set( 'Europe/London' );
				$d = new DateTime();

				$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_parent'    => 0,
					'post_title'     => $data[0]->{'Légende'},
					'post_content'   => 'Downloaded ' . $d->format( 'd/m/Y \a\t H:i:s' ),
					'post_status'    => 'inherit'
				);

				$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $parent_post_id );

				if ( ! is_wp_error( $attachment_id ) ) {
					require_once( ABSPATH . "wp-admin" . '/includes/image.php' );

					/* Add some attachment data */
					$attachment_data                                    = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
					$attachment_data['image_meta']['created_timestamp'] = $d->format( 'Y-m-d H:i:s' );
					//$attachment_data['image_meta']['copyright'] = 'Yelster ' . $d->format('Y');

					wp_update_attachment_metadata( $attachment_id, $attachment_data );

					add_post_meta( $attachment_id, 'resource_space', true, true );

					wp_send_json_success( wp_prepare_attachment_for_js( $attachment_id ) );

				} else {
					unlink( $file );
					wp_send_json_error( __( 'Could not create attachment', 'resourcespace' ) );
				}
			} else {
				unlink( $file );
				wp_send_json_error( __( 'Upload error', 'resourcespace' ) );
			}

			unlink( $file );

		} else {
			wp_send_json_error( __( 'Unable to retrieve image', 'resourcespace' ) );
		}

		exit();
	}

	function ajax_proxy_image() {

		$src = isset( $_GET['src'] ) ? sanitize_text_field( urldecode( $_GET['src'] ) ) : null;

		if ( ! $src ) {
			die('Please provide an image src.' );
		}

		$bits_request = parse_url( $src );
		$bits_allowed = parse_url( PJ_RESOURCE_SPACE_DOMAIN );

		$allowed = isset( $bits_request['host'] ) && isset( $bits_allowed['host'] ) && ( $bits_request['host'] === $bits_allowed['host'] );

		if ( ! $allowed ) {
			die('Domain not allowed.');
		}

		$request_args = array(
			'headers' => array()
		);

		// Pass basic auth header if available.
		if ( defined( 'PJ_RESOURCE_SPACE_AUTHL' ) &&  defined( 'PJ_RESOURCE_SPACE_AUTHP' ) ) {
			$request_args['headers']['Authorization'] = 'Basic ' . base64_encode( PJ_RESOURCE_SPACE_AUTHL . ':' . PJ_RESOURCE_SPACE_AUTHP );
		}

		$response = wp_remote_get( $src, $request_args );

		if (
		    'OK' !== wp_remote_retrieve_response_message( $response )
		    || 200 !== wp_remote_retrieve_response_code( $response )
		) {
			die( 'request failed' );
		}

		foreach ( wp_remote_retrieve_headers( $response ) as $header => $value ) {
			header( "$header: $value" );
		}

		echo wp_remote_retrieve_body( $response );

	}

}
