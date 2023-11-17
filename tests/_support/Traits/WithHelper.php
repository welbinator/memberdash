<?php
/**
 * Helper trait.
 *
 * @package MemberDash\Tests
 */

namespace MemberDash\Tests\Traits;

use MemberDash\Tests\Base\Helper;

/**
 * LearnDash test util trait.
 */
trait WithHelper {
	/**
	 * Helper instance.
	 *
	 * @var Helper
	 */
	protected $helper;

	/**
	 * Sets up.
	 *
	 * @before
	 *
	 * @return void
	 **/
	public function set_up_helper_before_test() {
		$this->helper = new Helper();
	}
}

