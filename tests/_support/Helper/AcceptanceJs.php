<?php
/**
 * AcceptanceJs helper
 *
 * @package MemberDash\Tests
 */

// cSpell:ignore optin .

namespace Helper; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound

use Codeception\Module\WPDb;
use Codeception\Module\WPWebDriver;
use Facebook\WebDriver\WebDriverKeys;
use Exception;

/**
 * Class AcceptanceJs
 *
 * Here you can define custom actions
 * All public methods declared in helper class will be available in $I
 */
class AcceptanceJs extends \Codeception\Module {
	/**
	 * Disable the telemetry modal.
	 *
	 * @return void
	 */
	public function disableTelemetry() {
		/**
		 * WPDb module.
		 *
		 * @var WPDb $wpdb_module
		 */
		$wpdb_module = $this->getModule( 'WPDb' );

		$wpdb_module->haveOptionInDatabase(
			'stellarwp_telemetry',
			serialize(
				[
					'plugins' =>
					[
						'learndash' =>
						[
							'wp_slug' => 'memberdash/memberdash.php',
							'optin'   => false,
						],
					],
					'token'   => '',
				]
			)
		);
	}

	/**
	 * Wait until the select2 component is loaded
	 *
	 * @param mixed $selector The select2 ID selector.
	 * @param int   $timeout  The timeout in seconds. Default to 30.
	 *
	 * @return void
	 */
	public function waitForSelect2( $selector, $timeout = 30 ) {
		$i = $this->getWebDriverModule();
		$i->waitForJS( 'return !!jQuery("' . $selector . '").data("select2");', $timeout );
	}

	/**
	 * Selects an option in a select2 dropdown.
	 *
	 * @param string $select2 The select2 ID selector.
	 * @param mixed  $option  The option value.
	 *
	 * @return void
	 */
	public function selectOptionInSelect2( $select2, $option ) {
		// Wait for the select2 to be loaded.
		$this->waitForSelect2( $select2 );

		$i = $this->getWebDriverModule();

		// Defining the element and search field selectors.
		$select2_id = $i->grabAttributeFrom( $select2, 'id' );

		$element      = '#select2-' . $select2_id . '-container';
		$search_field = '.select2-search__field';

		$i->click( $element );
		$i->waitForElementVisible( $search_field );
		$i->fillField( $search_field, $option );
		$i->pressKey( $search_field, WebDriverKeys::ENTER );
	}

	/**
	 * Returns the acceptance WebDriver module.
	 *
	 * @throws Exception If the module is not enabled.
	 *
	 * @return WPWebDriver
	 */
	private function getWebDriverModule() {
		if ( ! $this->hasModule( 'WPWebDriver' ) ) {
			throw new Exception( 'You must enable the WPWebDriver module' );
		}

		/**
		 * WPWebDriver module.
		 *
		 * @var WPWebDriver $wp_web_driver_module
		 */
		$wp_web_driver_module = $this->getModule( 'WPWebDriver' );

		return $wp_web_driver_module;
	}
}
