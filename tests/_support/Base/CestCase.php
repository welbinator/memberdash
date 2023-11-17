<?php
/**
 * Cest Case Base Class.
 *
 * @package MemberDash\Tests
 */

namespace MemberDash\Tests\Base;

use ReflectionClass;
use AcceptanceTester;
use AcceptanceJsTester;

/**
 * LearnDash Cest Case.
 */
class CestCase {
	/**
	 * Test setup.
	 *
	 * @param AcceptanceTester|AcceptanceJsTester $i The actor.
	 *
	 * @return void
	 */
	public function _before( $i ) {
		// Call all methods with @before phpdoc tag.
		$reflection_class = new ReflectionClass( static::class );
		foreach ( $reflection_class->getMethods() as $method ) {
			$doc_comment = $method->getDocComment();

			if ( strpos( (string) $doc_comment, "* @before\n" ) === false ) {
				continue;
			}

			$this->{$method->getName()}();
		}

		global $wp_db_version;

		// Fake the WP DB Version. Necessary to prevent upgrade notices when running acceptance tests in the backend.
		$i->haveOptionInDatabase(
			'db_version',
			$wp_db_version
		);
	}
}
