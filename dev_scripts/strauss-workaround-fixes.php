<?php
if ( php_sapi_name() !== 'cli' ) {
	die( 'This script can only be run from the command line.' );
}

function replace_in_directory( $dir, $search, $replace ) {
	$files = scandir( $dir );

	foreach ( $files as $file ) {
		$path = $dir . DIRECTORY_SEPARATOR . $file;

		// Skip . and .. directories
		if ( $file === '.' || $file === '..' ) {
			continue;
		}

		// If $path is a directory, recursively call this function
		if ( is_dir( $path ) ) {
			replace_in_directory( $path, $search, $replace );
		} else {
			// If $path is a file, replace the search string with the replace string
			if ( strpos( $file, '.php' ) !== false ) {
				$content     = file_get_contents( $path );
				$new_content = str_replace( $search, $replace, $content );

				if ( $new_content !== $content ) {
					file_put_contents( $path, $new_content );
				}
			}
		}
	}
}

// Stripe fixes

$dir = 'vendor-prefixed/stripe/stripe-php/lib';

// Call the function with your directory path and the search/replace strings
replace_in_directory( $dir, 'instanceof \Stripe\Collection', 'instanceof \StellarWP\Memberdash\Stripe\Collection' );
replace_in_directory( $dir, 'instanceof \Stripe\ApiResource', 'instanceof \StellarWP\Memberdash\Stripe\ApiResource' );
replace_in_directory( $dir, 'instanceof \Stripe\SearchResult', 'instanceof \StellarWP\Memberdash\Stripe\SearchResult' );
replace_in_directory( $dir, '<\Stripe\InvoiceLineItem>', '<\StellarWP\Memberdash\Stripe\InvoiceLineItem>' );
replace_in_directory( $dir, '<\Stripe\SubscriptionItem>', '<\StellarWP\Memberdash\Stripe\SubscriptionItem>' );
