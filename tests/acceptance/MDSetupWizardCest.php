<?php
/**
 * Tests for the MemberDash Setup Wizard.
 *
 * @package MemberDash\Tests
 */

namespace MemberDash\Tests\Acceptance;

use AcceptanceTester;
use MemberDash\Tests\Base\CestCase;

/**
 * Class to test the MemberDash Setup Wizard.
 */
class MDSetupWizardCest extends CestCase {
	/**
	 * Tests if the Setup Wizard is shown when we visit the plugin's admin page for the first time.
	 *
	 * @param AcceptanceTester $i The actor.
	 *
	 * @return void
	 */
	public function test_setup_wizard_appears_on_first_visit( AcceptanceTester $i ) {
		// Arrange.

		$i->loginAsAdmin();
		$i->amOnAdminPage( 'admin.php?page=membership-setup' );

		// Assert.

		$i->see( 'Create New Membership' );
	}
}
