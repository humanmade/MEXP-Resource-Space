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
			wp_send_json_error( __( 'Empty resource id', 'resourcespace' ) );
		}

		$url = PJ_RESOURCE_SPACE_DOMAIN . '/plugins/api_search/';
		$key = PJ_RESOURCE_SPACE_KEY;

		$url = add_query_arg( array(
			'key'              => $key,
			'search'           => $resource_id,
			'prettyfieldnames' => 1,
			'previewsize'      => 'pre',
			'original'         => true,
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

		if ( count( $data ) < 1 ) {
			wp_send_json_error( __( 'Resource not found', 'resourcespace' ) );
		}

		// Request original URL.
		// $attachment_id = wpcom_vip_download_image( $data[0]->original );

		// Request preview size.
		$attachment_id = wpcom_vip_download_image( $data[0]->preview, $parent_post_id );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id->get_error_message() );
		} else {
			wp_send_json_success( wp_prepare_attachment_for_js( $attachment_id ) );
		}

		exit();

	}

}
