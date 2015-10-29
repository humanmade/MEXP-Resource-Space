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
	}

	/**
	 * Ajax handler to retrieve content from Resource space and add as attachment.
	 */
	function ajax_get_image() {

		$resource_id    = intval( $_POST['resource_id'] );
		$parent_post_id = isset( $_POST['post'] ) ? intval( $_POST['post'] ) : 0;

		if ( empty( $resource_id ) ) {
			wp_send_json_error( esc_html__( 'Empty resource id', 'resourcespace' ) );
		}

		$url = PJ_RESOURCE_SPACE_DOMAIN . '/plugins/api_search/';
		$key = PJ_RESOURCE_SPACE_KEY;

		$url = add_query_arg( array(
			'key'              => $key,
			'search'           => $resource_id,
			'prettyfieldnames' => false,
			'original'         => true,
			'previewsize'      => 'scr',
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
			wp_send_json_error( esc_html__( 'Unable to query API', 'resourcespace' ) );
		}

		if ( count( $data ) < 1 ) {
			wp_send_json_error( esc_html__( 'Resource not found', 'resourcespace' ) );
		}

		// Request original URL.
		$attachment_id = wpcom_vip_download_image( $data[0]->preview );

		// Update Metadata.
		update_post_meta( $attachment_id, 'resource_space', 1 );

		// Allow plugins to hook in here.
		do_action( 'resourcespace_import_complete', $attachment_id, $data[0] );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id->get_error_message() );
		} else {
			wp_send_json_success( wp_prepare_attachment_for_js( $attachment_id ) );
		}

		exit();

	}

	private function sideload_image( $url ) {

		$request_args = array( 'headers' => array() );

		// Pass basic auth header if available.
		if ( defined( 'PJ_RESOURCE_SPACE_AUTHL' ) &&  defined( 'PJ_RESOURCE_SPACE_AUTHP' ) ) {
			$request_args['headers']['Authorization'] = 'Basic ' . base64_encode( PJ_RESOURCE_SPACE_AUTHL . ':' . PJ_RESOURCE_SPACE_AUTHP );
		}

		// TODO test. Advice from Kirill was to use the users cookie.
		// Hopefully it isn't required as this isn't as robust as using basic auth.
		$response = wp_remote_get( $url, $request_args );

		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {

			$file = get_temp_dir() . sanitize_file_name( $data[0]->Original_filename );
			file_put_contents( $file, wp_remote_retrieve_body( $response ) );

			$filename = basename( $file );

			$upload_file = wp_upload_bits( $filename, null, file_get_contents( $file ) );

			if ( ! $upload_file['error'] ) {

				$wp_filetype = wp_check_filetype( $filename, null );

				$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_parent'    => 0,
					'post_title'     => $data[0]->{'Légende'},
					'post_content'   => 'Downloaded ' . current_time( 'd/m/Y \a\t H:i:s' ),
					'post_status'    => 'inherit',
				);

				$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $parent_post_id );

				if ( ! is_wp_error( $attachment_id ) ) {

					require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/image.php' );

					$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
					$attachment_data['image_meta']['created_timestamp'] = current_time( 'Y-m-d H:i:s', true );

					wp_update_attachment_metadata( $attachment_id, $attachment_data );

					add_post_meta( $attachment_id, 'resource_space', true, true );

					return $attachment_id;

				} else {
					unlink( $file );
					return new WP_Error( 'broke', esc_html__( 'Could not create attachment', 'resourcespace' ) );
				}
			} else {
				unlink( $file );
				return new WP_Error( 'broke', esc_html__( 'Upload error', 'resourcespace' ) );
			}

			unlink( $file );

		} else {
			return new WP_Error( 'broke', esc_html__( 'Unable to retrieve image', 'resourcespace' ) );
		}

	}

}
