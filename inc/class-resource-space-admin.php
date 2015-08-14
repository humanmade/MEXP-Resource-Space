<?php

class Resource_Space_Admin {

	protected static $instance;

	/**
	 * Create singleton instance.
	 * @return HM_Reviews
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
			self::$instance->setup_actions();
		}

		return self::$instance;
	}

	public function setup_actions() {

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'ajax_query_attachments_args', array( $this, 'ajax_query_attachments_args' ) );

		if ( ! defined( 'PJ_RESOURCE_SPACE_DOMAIN' ) || ! defined( 'PJ_RESOURCE_SPACE_KEY' ) ) {

			add_action( 'admin_notices', function() { ?>
			    <div class="error">
			        <p><?php esc_html_e( 'You must define the resource space domain and API key in your wp-config.php. See readme for more details.', 'resourcespace' ); ?></p>
			    </div>
		    <?php } );

		}

	}

	function admin_menu() {

		add_media_page(
			__( 'Stock Photos', 'resourcespace' ),
			__( 'Stock Photos', 'resourcespace' ),
			'insert_from_resourcespace',
			'resourcespace',
			array( $this, 'render_page' )
		);

	}

	function render_page() {
		include( PJ_RESOURCE_SPACE_PLUGIN_DIR . '/templates/admin-page.php' );
	}

	function admin_enqueue_scripts() {

		global $current_screen;

		if ( $current_screen && 'media_page_resourcespace' === $current_screen->base ) {

			wp_enqueue_media();
			wp_enqueue_script( 'media-grid' );
			wp_enqueue_script( 'media' );

			$deps = array( 'jquery', 'backbone', 'media' );
			wp_enqueue_script( 'resource-space-admin', PJ_RESOURCE_SPACE_PLUGIN_URL . '/js/admin.js', $deps, PJ_RESOURCESPACE_PLUGIN_VERSION, true );

		}

	}

	function ajax_query_attachments_args( $query ) {

		if ( isset( $_POST['query']['meta_query'] ) && 'resource_space' === $_POST['query']['meta_query'] ) {
			$query['meta_query'] = array(
				array(
					'key'     => 'resource_space',
					'compare' => 'EXISTS',
				),
			);
		}

		return $query;

	}

}
