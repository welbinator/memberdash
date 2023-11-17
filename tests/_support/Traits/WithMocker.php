<?php
/**
 * Mocker Trait.
 *
 * @package MemberDash\Tests
 */

namespace MemberDash\Tests\Traits;

use Mockery;

/**
 * Mocker Trait.
 */
trait WithMocker {
	/**
	 * Mockery instance.
	 *
	 * @var Mockery
	 */
	protected $mocker;

	/**
	 * Sets up.
	 *
	 * @before
	 *
	 * @return void
	 **/
	public function set_up_mocker_before_test() {
		$this->mocker = new Mockery();
	}
}
