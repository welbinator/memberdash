<?php
/**
 * Bootstrap file for tests.
 *
 * @package MemberDash\Tests
 *
 * cspell:ignore spatie
 */

/**
 * Codeception will regenerate snapshots on `--debug`, while the `spatie/snapshot-assertions`
 * library will do the same on `--update-snapshots`.
 * Since Codeception has strict check on the CLI arguments appending `--update-snapshots` to the
 * `vendor/bin/codecept run` command will throw an error.
 * We handle that intention here.
 */
if ( in_array( '--debug', $_SERVER['argv'], true ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
	$_SERVER['argv'][] = '--update-snapshots';
}

// learndash initialization.
require_once __DIR__ . '/../memberdash-initialization.php';
