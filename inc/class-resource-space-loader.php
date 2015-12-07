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

		$args = array_map( 'rawurlencode', array(
			'key'              => PJ_RESOURCE_SPACE_KEY,
			'search'           => $resource_id,
			'prettyfieldnames' => false,
			'original'         => true,
			'previewsize'      => 'scr',
		) );

		$url          = add_query_arg( $args, PJ_RESOURCE_SPACE_DOMAIN . '/plugins/api_search/' );
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

}
