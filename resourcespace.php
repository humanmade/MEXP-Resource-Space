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

define( 'PJ_RESOURCESPACE_PLUGIN_VERSION', '0.1' );
define( 'PJ_RESOURCE_SPACE_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PJ_RESOURCE_SPACE_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

defined( 'PJ_RESOURCE_SPACE_RESULTS_PER_PAGE' ) or define( 'PJ_RESOURCE_SPACE_RESULTS_PER_PAGE', 10 );

if ( ! class_exists( 'MEXP_Service' ) ) {
	wp_die( esc_html__( 'Media Explorer plugin must be enabled.', 'resourcespace' ) );
}

require_once( __DIR__ . '/inc/class-resource-space-loader.php' );
require_once( __DIR__ . '/inc/class-resource-space-admin.php' );
require_once( __DIR__ . '/inc/class-mexp-resource-space-service.php' );
require_once( __DIR__ . '/inc/class-mexp-resource-space-template.php' );

Resource_Space_Loader::get_instance();
Resource_Space_Admin::get_instance();

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
 * Add the resourcespace MEXP capability for required roles.
 * Note - version check to ensure this isn't fired always as it writes to the database.d
 */
add_action( 'admin_init', function() {

	if ( version_compare( PJ_RESOURCESPACE_PLUGIN_VERSION, get_option( 'pj_resourcespace_version'), '<=' ) ) {
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
