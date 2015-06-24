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

define( 'RESOURCESPACE_PLUGIN_VERSION', '0.1' );
define( 'RESOURCE_SPACE_AJAX_ACTION', 'pj_rs_get_resource' );
define( 'RESOURCE_SPACE_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'RESOURCE_SPACE_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

if ( ! class_exists( 'MEXP_Service' ) ) {
	wp_die( __( 'Media Explorer plugin must be enabled.', 'resourcespace' ) );
}

require_once( __DIR__ . '/inc/class-resource-space-loader.php' );
require_once( __DIR__ . '/inc/class-resource-space-admin.php' );
require_once( __DIR__ . '/inc/class-mexp-resource-space-service.php' );
require_once( __DIR__ . '/inc/class-mexp-resource-space-template.php' );

Resource_Space_Loader::get_instance();
Resource_Space_Admin::get_instance();

add_filter( 'mexp_services', function( array $services ) {
	$services['resource-space'] = new MEXP_Resource_Space_Service;
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

	wp_enqueue_script( 'resource-space-vc', plugins_url( 'js/resource-space-vc.js', __FILE__ ), array( 'wpb_jscomposer_media_editor_js' ), null, true );

	wp_localize_script( 'resource-space-vc', 'resourceSpaceVC', array(
		'stockImagesTabText'    => __( 'Stock Images', 'resourcespace' ),
		'stockImagesInsertText' => __( 'Import', 'resourcespace' ),
	) );

}

add_action( 'admin_print_scripts-post.php', 'resource_space_vc_script' );
add_action( 'admin_print_scripts-post-new.php', 'resource_space_vc_script' );
