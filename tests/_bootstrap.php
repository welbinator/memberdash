<?php
/**
 * Bootstrap file for tests.
 *
 * @package MemberDash\Tests
 */

// This is global bootstrap for autoloading.
Codeception\Util\Autoload::addNamespace( 'MemberDash\Tests', __DIR__ . '/_support' );

// A small fix for PhpStorm to recognize the Codeception classes. @see https://github.com/lucatume/wp-browser/issues/513.
if ( (int) getenv( 'USING_CONTAINERS' ) !== 1 ) {
	require_once __DIR__ . '/phpstorm-alias-fix.php';
}

// Patchwork needs to be loaded first. See https://brain-wp.github.io/BrainMonkey/docs/functions-setup.html.
require_once dirname( __DIR__ ) . '/vendor/antecedent/patchwork/Patchwork.php';

/**
 * Load the Composer autoloader.
 */
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
