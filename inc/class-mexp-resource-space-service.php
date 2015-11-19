<?php
/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

defined( 'ABSPATH' ) or die();

class MEXP_Resource_Space_Service extends MEXP_Service {

	/**
	 * Constructor.
	 *
	 * Creates the Backbone view template.
	 */
	public function __construct() {
		$this->set_template( new MEXP_Resource_Space_Template );
	}

	/**
	 * Fired when the service is loaded.
	 *
	 * Allows the service to enqueue JS/CSS only when it's required. Akin to WordPress' load action.
	 */
	public function load() {
		add_filter( 'mexp_tabs',   array( $this, 'tabs' ),   10, 1 );
		add_filter( 'mexp_labels', array( $this, 'labels' ), 10, 1 );
		add_action( 'mexp_enqueue', array( $this, 'enqueue_statics' ) );
	}

	/**
	 * Handles the AJAX request and returns an appropriate response. This should be used, for example, to perform an API request to the service provider and return the results.
	 *
	 * @param array $request The request parameters.
	 * @return MEXP_Response|bool|WP_Error A MEXP_Response object should be returned on success, boolean false should be returned if there are no results to show, and a WP_Error should be returned if there is an error.
	 */
	public function request( array $request ) {

		$response = new MEXP_Response();

		// Ensure that 'page' is never 0. This breaks things.
		$request['page'] = ( $request['page'] < 1 ) ? 1 : $request['page'];

		// Build the request URL.
		$args = array_map( 'rawurlencode', apply_filters( 'resourcespace_request_args', array(
				'search'           => sanitize_text_field( $request['params']['q'] ),
				'key'              => PJ_RESOURCE_SPACE_KEY,
				'previewsize'      => 'pre',
				'prettyfieldnames' => true,
				'original'         => true,
				'results_per_page' => PJ_RESOURCE_SPACE_RESULTS_PER_PAGE,
				'page'             => absint( $request['page'] ),
				'restypes'         => 1, // Restrict to images only.
		) ) );

		$api_url = add_query_arg( $args, sprintf( '%s/plugins/api_search/', PJ_RESOURCE_SPACE_DOMAIN ) );

		$request_args = array(
			'headers' => array()
		);

		// Pass basic auth header if available.
		if ( defined( 'PJ_RESOURCE_SPACE_AUTHL' ) &&  defined( 'PJ_RESOURCE_SPACE_AUTHP' ) ) {
			$request_args['headers']['Authorization'] = 'Basic ' . base64_encode( PJ_RESOURCE_SPACE_AUTHL . ':' . PJ_RESOURCE_SPACE_AUTHP );
		}

		$api_response = wp_remote_get( $api_url, $request_args );

		if ( 200 !== wp_remote_retrieve_response_code( $api_response ) ) {
			return $api_response;
		}

		$response_data = json_decode( wp_remote_retrieve_body( $api_response ) );

		foreach ( (array) $response_data->resources as $resource ) {

			$dirty_data = array(
				'title'       => basename( $resource->file_path ),
				'date'        => strtotime( $resource->creation_date ),
				'id'          => $resource->ref,
				'thumbnail'   => $resource->preview,
				'url'         => null,
			);

			$dirty_data = apply_filters( 'resourcespace_parse_raw_image_data', $dirty_data, $resource );
			$clean_data = array();

			foreach ( $this->get_item_fields() as $field => $args ) {

				$clean_data[ $field ] = '';

				if ( isset( $dirty_data[ $field ] ) ) {
					$clean_data[ $field ] = call_user_func( $args['sanitize_callback'], $dirty_data[ $field ] );
				} elseif ( ! isset( $dirty_data[ $field ] ) && isset( $args['default'] ) ) {
					$clean_data[ $field ] = $args['default'];
				}

			}

			$item = new MEXP_Response_Item();
			$item->set_content( $clean_data['title'] );
			$item->set_date( $clean_data['date'] );
			$item->set_date_format( $clean_data['date_format'] );
			$item->set_id( $clean_data['id'] );
			$item->set_url( $clean_data['url'] );
			$item->set_thumbnail( $clean_data['thumbnail'] );

			$response->add_item( $item );

		}

		$response->add_meta( 'per_page', $response_data->pagination->per_page );
		$response->add_meta( 'page', $response_data->pagination->page );
		$response->add_meta( 'total_pages', $response_data->pagination->total_pages );
		$response->add_meta( 'total_resources', $response_data->pagination->total_resources );

		return $response;
	}

	/**
	 * Returns an array of tabs (routers) for the service's media manager panel.
	 *
	 * @param array $tabs Associative array of default tab items.
	 * @return array Associative array of tabs. The key is the tab ID and the value is an array of tab attributes.
	 */
	public function tabs( array $tabs ) {
		$tabs['resource-space'] = array(
			'all' => array(
				'defaultTab' => true,
				'text'       => _x( 'All', 'Tab title', 'resourcespace' ),
			),
		);

		return $tabs;
	}

	/**
	 * Returns an array of custom text labels for this service.
	 *
	 * @param array $labels Associative array of default labels.
	 * @return array Associative array of labels.
	 */
	public function labels( array $labels ) {
	 	$labels['resource-space'] = array(
			'insert'    => esc_html__( 'Insert Image', 'resourcespace' ),
			'noresults' => esc_html__( 'No images matched your search query.', 'resourcespace' ),
			'title'     => esc_html__( 'Insert Stock Photo', 'resourcespace' ),
		);

	 	return $labels;
	}

	public function get_item_fields() {
		return apply_filters( 'resourcespace_fields', array(
			'title' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'date' => array(
				'sanitize_callback' => 'absint',
			),
			'date_format' => array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'j M y',
			),
			'id' => array(
				'sanitize_callback' => 'absint',
				'default'           => 0,
			),
			'thumbnail' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'url' => array(
				'sanitize_callback' => 'esc_url_raw',
			),
		) );

	}

	public function enqueue_statics() {

		wp_enqueue_script(
			'mexp-service-resourcespace',
			plugins_url( 'js/mexp.js', __DIR__ ),
			array( 'jquery', 'mexp' ),
			'0.1'
		);

		wp_enqueue_style(
			'mexp-service-resourcespace',
			plugins_url( 'css/mexp.css', __DIR__ ),
			array( 'mexp' ),
			'0.1'
		);
	}
}
