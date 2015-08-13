<?php

?>

<div class="wrap">

	<h2 class="page-title">
		<?php esc_html_e( 'Stock Images', 'resourcespace' ); ?>
		<a href="#" id="resource-space-add-new" class="add-new-h2"><?php esc_html_e( 'Add new', 'resourcespace' ); ?></a>
	</h2>

	<div id="resource-space-new-images" style="display: none;">
		<h3><?php esc_html_e( 'Recently Imported Stock Images', 'resourcespace' ); ?></h3>
	</div>

	<div class="wrap" id="resource-space-images" data-search="<?php _admin_search_query() ?>">

	<style>

		#resource-space-new-images {
			max-width: 1600px;
			padding: 15px 0;
		}

		#resource-space-new-images img {
			width: 23%;
			height: auto;
			margin-right: 2%;
			margin-bottom: 2%;
		}

		#resource-space-images .media-toolbar,
		#resource-space-images .media-sidebar,
		#resource-space-images .uploader-inline {
			display: none;
		}

	</style>

</div>
