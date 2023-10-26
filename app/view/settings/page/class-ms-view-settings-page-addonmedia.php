<?php

/**
 * Advanced Media Settings
 *
 * @since 1.0.0
 */
class MS_View_Settings_Page_Addonmedia extends MS_View_Settings_Edit {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * HTML contains the list of advanced media settings
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$settings = $this->data['settings'];

		// get add-on configuration
		$model        = MS_Factory::load( 'MS_Model_Addon' );
		$addon_config = MS_Model_Addon::get_addons()[ MS_Model_Addon::ADDON_MEDIA ];

		$fields = $addon_config->details;

		ob_start();
		?>
		<div class="cf">
			<?php
			MS_Helper_Html::settings_tab_header(
				array(
					'title' => $addon_config->name,
					'desc'  => $addon_config->description,
				)
			);
			MS_Helper_Html::settings_box( $fields );
			?>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

}
