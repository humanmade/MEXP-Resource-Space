<?php
/*
Plugin Name: Resourcespace Explorer
Version: 0.1-alpha
Description: Resource Space Media Explorer Plugin.
Author: Human Made Limited
Author URI: http://hmn.md
Text Domain: resourcespace
Domain Path: /languages
*/

define( 'PJ_RESOURCESPACE_PLUGIN_VERSION', '0.2' );

defined( 'PJ_RESOURCE_SPACE_PLUGIN_DIR' ) OR define( 'PJ_RESOURCE_SPACE_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
defined( 'PJ_RESOURCE_SPACE_PLUGIN_URL' ) OR define( 'PJ_RESOURCE_SPACE_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
defined( 'PJ_RESOURCE_SPACE_RESULTS_PER_PAGE' ) or define( 'PJ_RESOURCE_SPACE_RESULTS_PER_PAGE', 10 );

add_action( 'init', function() {

	if ( ! class_exists( 'MEXP_Service' ) ) {
		wp_die( esc_html__( 'Media Explorer plugin must be enabled.', 'resourcespace' ) );
	}

	if ( ! defined( 'PJ_RESOURCE_SPACE_DOMAIN' ) ) {
		wp_die( __( 'You must define PJ_RESOURCE_SPACE_DOMAIN', 'resourcespace' ) );
	}

	if ( ! defined( 'PJ_RESOURCE_SPACE_KEY' ) ) {
		wp_die( __( 'You must define PJ_RESOURCE_SPACE_KEY', 'resourcespace' ) );
	}

	require_once( __DIR__ . '/helpers.php' );
	require_once( __DIR__ . '/inc/class-resource-space-loader.php' );
	require_once( __DIR__ . '/inc/class-resource-space-admin.php' );
	require_once( __DIR__ . '/inc/class-mexp-resource-space-service.php' );
	require_once( __DIR__ . '/inc/class-mexp-resource-space-template.php' );

	Resource_Space_Loader::get_instance();
	Resource_Space_Admin::get_instance();

}, 9 );

add_filter( 'mexp_services', function( array $services ) {

	if ( current_user_can( 'insert_from_resourcespace' ) ) {
		$services['resource-space'] = new MEXP_Resource_Space_Service;
	}

	return $services;

} );

/**
 * Enqueue Visual Composer modification scripts if plugin is active.
 * @return null
 */
function resource_space_vc_script() {

	if ( ! ( defined( 'WPB_VC_VERSION' ) && WPB_VC_VERSION ) ) {
		return;
	}

	if ( ! current_user_can( 'insert_from_resourcespace' ) ) {
		return;
	}

	wp_enqueue_script( 'resource-space-vc', plugins_url( 'js/resource-space-vc.js', __FILE__ ), array( 'wpb_jscomposer_media_editor_js' ), null, true );

	wp_localize_script( 'resource-space-vc', 'resourceSpaceVC', array(
		'stockImagesTabText'    => esc_html__( 'Stock Images', 'resourcespace' ),
		'stockImagesInsertText' => esc_html__( 'Import', 'resourcespace' ),
	) );

}

add_action( 'admin_print_scripts-post.php', 'resource_space_vc_script' );
add_action( 'admin_print_scripts-post-new.php', 'resource_space_vc_script' );

/**
 * Resourcespace featured image Script.
 * @return null
 */
function resource_space_featured_image_script() {

	if ( ! current_user_can( 'insert_from_resourcespace' ) ) {
		return;
	}

	wp_enqueue_script( 'resource-space-featured-image', plugins_url( 'js/featured-image.js', __FILE__ ), array( 'backbone' ), null, true );

}

add_action( 'admin_print_scripts-post.php', 'resource_space_featured_image_script' );
add_action( 'admin_print_scripts-post-new.php', 'resource_space_featured_image_script' );

/**
 * Add the resourcespace MEXP capability for required roles.
 * Note - version check to ensure this isn't fired always as it writes to the database.d
 */
add_action( 'admin_init', function() {

	if ( version_compare( PJ_RESOURCESPACE_PLUGIN_VERSION, get_option( 'pj_resourcespace_version' ), '<=' ) ) {
		return;
	}

	update_option( 'pj_resourcespace_version', PJ_RESOURCESPACE_PLUGIN_VERSION );

	$roles         = get_editable_roles();
	$default_roles = array( 'editor', 'administrator' );

    foreach ( $GLOBALS['wp_roles']->role_objects as $key => $role ) {
		if ( in_array( $key, $default_roles ) && isset( $roles[ $key ] ) ) {
            $role->add_cap( 'insert_from_resourcespace' );
		}
    }

} );

add_filter( 'resourcespace_import_complete', function( $attachment_id, $resource ) {

	$post_array = array( 'ID' => $attachment_id );

	if ( isset( $resource->field8 ) ) {
		$post_array['post_title'] = sanitize_text_field( $resource->field8 );
	}

	if ( isset( $resource->field18 ) ) {
		$post_array['post_content'] = wp_kses_post( $resource->field18 );
	}

	// Only update if there is more data than just the ID.
	if ( count( $post_array ) > 1 ) {
		wp_update_post( $post_array );
	}

	if ( isset( $resource->field10 ) ) {
		update_post_meta( $attachment_id, 'aleteia_media_copyright', sanitize_text_field( $resource->field10 ) );
	}

}, 10, 2 );

/**
 * Add custom attachment meta fields
 *
 * @param array $form_fields
 * @param object $post
 * @return array
 */
function aleteia_attachment_fields_to_edit( $form_fields, $post ) {
	// Copyright text
	$form_fields['aleteia_media_copyright']['label'] = __( 'Copyright Text', 'aleteia' );
	$form_fields['aleteia_media_copyright']['input'] = 'textarea';
	$form_fields['aleteia_media_copyright']['value'] = get_post_meta( $post->ID, 'aleteia_media_copyright', true );
	// Copyright Link
	$form_fields['aleteia_media_copyright_link']['label'] = __( 'Copyright Link', 'aleteia' );
	$form_fields['aleteia_media_copyright_link']['input'] = 'text';
	$form_fields['aleteia_media_copyright_link']['value'] = get_post_meta( $post->ID, 'aleteia_media_copyright_link', true );
	return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'aleteia_attachment_fields_to_edit', null, 2 );
/**
 * Save custom attachment meta fields
 *
 * @param array $post
 * @param array $attachment
 * @return array
 */
function aleteia_attachment_fields_to_save( $post, $attachment ) {
	// Copyright text
	if ( isset( $attachment['aleteia_media_copyright'] ) && $attachment['aleteia_media_copyright'] ) {
		update_post_meta( $post['ID'], 'aleteia_media_copyright', sanitize_text_field( $attachment['aleteia_media_copyright'] ) );
	} else if ( isset( $attachment['aleteia_media_copyright'] ) ) {
		delete_post_meta( $post['ID'], 'aleteia_media_copyright' );
	}
	// Copyright Link
	if ( isset( $attachment['aleteia_media_copyright_link'] ) && $attachment['aleteia_media_copyright_link'] ) {
		update_post_meta( $post['ID'], 'aleteia_media_copyright_link', sanitize_text_field( $attachment['aleteia_media_copyright_link'] ) );
	} else if ( isset( $attachment['aleteia_media_copyright_link'] ) ) {
		delete_post_meta( $post['ID'], 'aleteia_media_copyright_link' );
	}
	return $post;
}
add_filter( 'attachment_fields_to_save','aleteia_attachment_fields_to_save', null, 2 );
