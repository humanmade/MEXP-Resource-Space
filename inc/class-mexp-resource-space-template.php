<?php

/**
 * Backbone templates for various views for your new service
 */
class MEXP_Resource_Space_Template extends MEXP_Template {

	/**
	 * Outputs the Backbone template for an item within search results.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 */
	public function item( $id, $tab ) { ?>

		<div id="mexp-item-<?php echo esc_attr( $tab ); ?>-{{ data.id }}" class="mexp-item-area" data-id="{{ data.id }}">
			<div class="mexp-item-container clearfix">
				<div class="mexp-item-thumb">
					<img src="{{ data.thumbnail }}">
				</div>

				<div class="mexp-item-main">
					<div class="mexp-item-content">
						{{ data.content }}
					</div>
					<div class="mexp-item-date">
						{{ data.date }}
					</div>
				</div>

			</div>
		</div>

		<a href="#" id="mexp-check-{{ data.id }}" data-id="{{ data.id }}" class="check" title="<?php esc_attr_e( 'Deselect', 'resourcespace' ); ?>">
			<div class="media-modal-icon"></div>
		</a>
	<?php
	}

	/**
	 * Outputs the Backbone template for a select item's thumbnail in the footer toolbar.
	 *
	 * @param string $id The template ID.
	 */
	public function thumbnail( $id ) {
	}

	/**
	 * Outputs the Backbone template for a tab's search fields.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 */
	public function search( $id, $tab ) { ?>
		<form action="#" class="mexp-toolbar-container clearfix tab-all">

			<input
				type="text"
				name="q"
				value="{{ data.params.q }}"
				class="mexp-input-text mexp-input-search"
				size="40"
				placeholder="<?php esc_attr_e( 'Search photos', 'resourcespace' ); ?>"
			>

			<input class="button button-large" type="submit" value="<?php esc_attr_e( 'Search', 'resourcespace' ); ?>">

			<div class="spinner"></div>

		</form>

	<?php
	}
}
