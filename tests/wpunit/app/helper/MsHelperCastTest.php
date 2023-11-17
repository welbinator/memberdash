<?php
/**
 * Type casting class test.
 *
 * @package MemberDash\Tests
 */

namespace MemberDash\Tests\Wpunit;

use Generator;
use MemberDash\Tests\Base\TestCase;
use MS_Helper_Cast;
use stdClass;

/**
 * Class to test the Cast class.
 */
class MsHelperCastTest extends TestCase {
	/**
	 * Tests casting to string.
	 *
	 * @dataProvider data_provider_test_to_string
	 *
	 * @param mixed  $value    Value.
	 * @param string $expected Expected.
	 *
	 * @return void
	 */
	public function test_to_string( $value, string $expected ): void {
		// Assert.

		$this->assertEquals( $expected, MS_Helper_Cast::to_string( $value ) );
	}

	/**
	 * Tests casting to int.
	 *
	 * @dataProvider data_provider_test_to_int
	 *
	 * @param mixed $value    Value.
	 * @param int   $expected Expected.
	 *
	 * @return void
	 */
	public function test_to_int( $value, int $expected ): void {
		// Assert.

		$this->assertEquals( $expected, MS_Helper_Cast::to_int( $value ) );
	}

	/**
	 * Tests casting to float.
	 *
	 * @dataProvider data_provider_test_to_float
	 *
	 * @param mixed $value    Value.
	 * @param float $expected Expected.
	 *
	 * @return void
	 */
	public function test_to_float( $value, float $expected ): void {
		// Assert.

		$this->assertEquals( $expected, MS_Helper_Cast::to_float( $value ) );
	}

	/**
	 * Tests casting to bool.
	 *
	 * @dataProvider data_provider_test_to_bool
	 *
	 * @param mixed $value    Value.
	 * @param bool  $expected Expected.
	 *
	 * @return void
	 */
	public function test_to_bool( $value, bool $expected ): void {
		// Assert.

		$this->assertEquals( $expected, MS_Helper_Cast::to_bool( $value ) );
	}

	/**
	 * Test data for the test_to_string.
	 *
	 * @return Generator
	 */
	public function data_provider_test_to_string(): Generator {
		$default = '';

		yield 'string => same string' => [ 'random', 'random' ];
		yield 'empty string => empty string' => [ '', $default ];
		yield 'bool true => empty string' => [ true, '1' ];
		yield 'bool false => empty string' => [ false, $default ];
		yield 'zero => string' => [ 0, '0' ];
		yield 'number => string' => [ 100, '100' ];
		yield 'float => string' => [ 100.2, '100.2' ];
		yield 'array => empty string' => [ [ 'random' ], $default ];
		yield 'object => empty string' => [ new stdClass(), $default ];
		yield 'null => empty string' => [ null, '' ];
		yield 'callable => empty string' => [
			function () {
			},
			$default,
		];
		yield 'closure => empty string' => [
			function () {
				return 0;
			},
			$default,
		];
		yield 'generator => empty string' => [
			( function () {
				yield 0;
			} )(),
			$default,
		];
		yield 'resource => empty string' => [ fopen( 'php://memory', 'r' ), $default ];
		yield 'unknown => empty string' => [ tmpfile(), $default ];
	}

	/**
	 * Test data for the test_to_int.
	 *
	 * @return Generator
	 */
	public function data_provider_test_to_int(): Generator {
		$default = 0;

		yield 'int => same int' => [ 100, 100 ];
		yield 'zero => zero' => [ 0, $default ];
		yield 'float 100.1 => 100' => [ 100.1, 100 ];
		yield 'float 100.9 => 100' => [ 100.9, 100 ];
		yield 'empty string => zero' => [ '', $default ];
		yield 'non empty string => zero' => [ 'random', $default ];
		yield 'bool true => 1' => [ true, 1 ];
		yield 'bool false => 0' => [ false, $default ];
		yield 'string int => number' => [ '100', 100 ];
		yield 'string float => number' => [ '100.1', 100 ];
		yield 'array => zero' => [ [ 'random' ], $default ];
		yield 'object => zero' => [ new stdClass(), $default ];
		yield 'null => zero' => [ null, $default ];
		yield 'callable => zero' => [
			function () {
			},
			$default,
		];
		yield 'closure => zero' => [
			function () {
				return 0;
			},
			$default,
		];
		yield 'generator => zero' => [
			( function () {
				yield 0;
			} )(),
			$default,
		];
		yield 'resource => zero' => [ fopen( 'php://memory', 'r' ), $default ];
		yield 'unknown => zero' => [ tmpfile(), $default ];
	}

	/**
	 * Test data for the test_to_float.
	 *
	 * @return Generator
	 */
	public function data_provider_test_to_float(): Generator {
		$default = 0.0;

		yield 'float => same float' => [ 100.2, 100.2 ];
		yield 'zero => zero' => [ 0, $default ];
		yield 'empty string => zero' => [ '', $default ];
		yield 'non empty string => zero' => [ 'random', $default ];
		yield 'bool true => 1.0' => [ true, 1.0 ];
		yield 'bool false => zero' => [ false, $default ];
		yield 'string int => float' => [ '100', 100.0 ];
		yield 'string float => float' => [ '100.1', 100.1 ];
		yield 'array => zero' => [ [ 'random' ], $default ];
		yield 'object => zero' => [ new stdClass(), $default ];
		yield 'null => zero' => [ null, $default ];
		yield 'callable => zero' => [
			function () {
			},
			$default,
		];
		yield 'closure => zero' => [
			function () {
				return 0;
			},
			$default,
		];
		yield 'generator => zero' => [
			( function () {
				yield 0;
			} )(),
			$default,
		];
		yield 'resource => zero' => [ fopen( 'php://memory', 'r' ), $default ];
		yield 'unknown => zero' => [ tmpfile(), $default ];
	}

	/**
	 * Test data for the test_to_bool.
	 *
	 * @return Generator
	 */
	public function data_provider_test_to_bool(): Generator {
		$default = false;

		yield 'bool true => true' => [ true, true ];
		yield 'bool false => false' => [ false, $default ];
		yield 'float => true' => [ 100.2, true ];
		yield 'int => true' => [ 2, true ];
		yield 'zero => false' => [ 0, $default ];
		yield 'non empty string => true' => [ 'random', true ];
		yield 'empty string => false' => [ '', $default ];
		yield 'string int => true' => [ '100', true ];
		yield 'string float => true' => [ '100.1', true ];
		yield 'array => false' => [ [ 'random' ], $default ];
		yield 'object => false' => [ new stdClass(), $default ];
		yield 'null => false' => [ null, $default ];
		yield 'callable => false' => [
			function () {
			},
			$default,
		];
		yield 'closure => false' => [
			function () {
				return 0;
			},
			$default,
		];
		yield 'generator => false' => [
			( function () {
				yield 0;
			} )(),
			$default,
		];
		yield 'resource => false' => [ fopen( 'php://memory', 'r' ), $default ];
		yield 'unknown => false' => [ tmpfile(), $default ];
	}
}
