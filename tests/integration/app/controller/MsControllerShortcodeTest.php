<?php
/**
 * Test Shortcode controller.
 *
 * @package MemberDash\Tests
 */

use MemberDash\Tests\Base\TestCase;
use MemberDash\Tests\Traits\WithHelper;
use MemberDash\Tests\Traits\WithFaker;

/**
 * Class to test the Shortcode controller class.
 */
class Ms_ControllerShortcodeTest extends TestCase {
	use WithHelper;
	use WithFaker;

	/**
	 * Test the membership thank you page with a non-member.
	 *
	 * @return void
	 */
	public function test_membership_thank_you_page_non_member() {
		// Arrange.

		$controller = MS_Factory::load( 'MS_Controller_Shortcode' );
		$atts       = [
			'fallback_message' => 'You have not subscribed to any memberships yet.',
		];

		// Act.

		$result = $controller->membership_thank_you_page( $atts );

		// Assert.

		$this->assertStringContainsString( $atts['fallback_message'], $result );
	}

	/**
	 * Test the membership thank you page with a paid membership.
	 *
	 * @return void
	 */
	public function test_membership_thank_you_page_paid_membership() {
		// Arrange.

		$controller      = MS_Factory::load( 'MS_Controller_Shortcode' );
		$membership_name = $this->faker->word();

		// Set relationship ID.
		$_REQUEST['ms_relationship_id'] = $this->create_membership_relationship( $membership_name, false );

		$atts = [
			'paid_membership_message' => 'Paid %membership_name%',
		];

		$expected = str_replace( '%membership_name%', $membership_name, $atts['paid_membership_message'] );

		// Act.

		$result = $controller->membership_thank_you_page( $atts );

		// Assert.

		$this->assertStringContainsString( $expected, $result );
	}

	/**
	 * Test the membership thank you page with a free membership.
	 *
	 * @return void
	 */
	public function test_membership_thank_you_page_free_membership() {
		// Arrange.

		$controller      = MS_Factory::load( 'MS_Controller_Shortcode' );
		$membership_name = $this->faker->word();

		// Set relationship ID.
		$_REQUEST['ms_relationship_id'] = $this->create_membership_relationship( $membership_name, true );

		$atts = [
			'free_membership_message' => 'Free %s',
		];

		$expected = str_replace( '%membership_name%', $membership_name, $atts['free_membership_message'] );

		// Act.

		$result = $controller->membership_thank_you_page( $atts );

		// Assert.

		$this->assertStringContainsString( $expected, $result );
	}

	/**
	 * Create membership and return relationship ID.
	 *
	 * @param string $membership_name Membership name.
	 * @param bool   $is_free         Is free membership.
	 *
	 * @return int
	 */
	protected function create_membership_relationship( string $membership_name, bool $is_free = false ): int {
		// Default for paid membership.
		$status = 'paid';
		$price  = '10';

		// Set free membership.
		if ( $is_free ) {
			$status = 'active';
			$price  = '0';
		}

		$membership_id = $this->factory()->post->create(
			[
				'post_type'   => MS_Model_Membership::get_post_type(),
				'post_status' => 'publish',
				'post_title'  => $membership_name,
				'meta_input'  => [
					'active'       => '1',
					'payment_type' => 'recurring',
					'price'        => $price,
					'type'         => MS_Model_Membership::TYPE_STANDARD,
					'is_free'      => $is_free ? '1' : '0',
					'name'         => $membership_name,
				],
			]
		);

		return $this->factory()->post->create(
			[
				'post_type'   => MS_Model_Relationship::get_post_type(),
				'post_status' => 'private',
				'meta_input'  => [
					'amount'        => $price,
					'status'        => $status,
					'membership_id' => $membership_id,
				],
			]
		);
	}
}
