<?php
/**
 * Faker Trait.
 *
 * @package MemberDash\Tests
 */

namespace MemberDash\Tests\Traits;

/**
 * Faker Trait.
 */
trait WithFaker {
	/**
	 * Faker instance.
	 *
	 * @var \Faker\Generator
	 */
	protected $faker;

	/**
	 * Generates unique strings list. Stupid simple for now.
	 *
	 * @param int $number Number of "words".
	 *
	 * @return string[]
	 */
	protected function fake_unique_words( int $number ): array {
		$words = array();

		$vowels     = array( 'a', 'e', 'i', 'o', 'u' );
		$consonants = array( 'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z' );

		// phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found -- I have to.
		while ( count( $words ) < $number ) {
			$word = '';

			// phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed -- I have to.
			for ( $j = 0; $j <= wp_rand( 3, 8 ); $j++ ) {
				$word .= $consonants[ array_rand( $consonants ) ];
				$word .= $vowels[ array_rand( $vowels ) ];
			}

			if ( isset( $words[ $word ] ) ) {
				$word = strrev( $word );
			}

			if ( isset( $words[ $word ] ) ) {
				$word = substr( $word, 0, wp_rand( 2, 5 ) );
			}

			if ( isset( $words[ $word ] ) ) {
				$word = $word . substr( md5( $word ), 0, wp_rand( 1, 5 ) );
			}

			if ( isset( $words[ $word ] ) ) {
				continue;
			}

			$words[ $word ] = null;
		}

		return array_keys( $words );
	}

	/**
	 * Sets up.
	 *
	 * @before
	 *
	 * @return void
	 **/
	protected function set_up_faker_before_test() {
		$this->faker = \Faker\Factory::create();
	}
}
