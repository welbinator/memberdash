/*! MemberDash - v1.0.0
 * Copyright (c) 2019; * Licensed GPLv2+ */
/*global ms_data:false */

window.ms_init = window.ms_init || {};

jQuery( function() {
	let i;

	window.ms_init._done = window.ms_init._done || {};

	function initialize( callback ) {
		if ( undefined !== callback && undefined !== window.ms_init[ callback ] ) {
			// Prevent multiple calls to init functions...
			if ( true === window.ms_init._done[ callback ] ) {
				return false;
			}

			window.ms_init._done[ callback ] = true;
			window.ms_init[ callback ]();
		}
	}

	if ( undefined === window.ms_data ) {
		return;
	}

	if ( undefined !== ms_data.ms_init ) {
		if ( ms_data.ms_init instanceof Array ) {
			for ( i = 0; i < ms_data.ms_init.length; i += 1 ) {
				initialize( ms_data.ms_init[ i ] );
			}
		} else {
			initialize( ms_data.ms_init );
		}

		// Prevent multiple calls to init functions...
		ms_data.ms_init = [];
	}
} );

window.ms_init.shortcode = function init() {
	jQuery( '.ms-membership-form .membership_cancel' ).click( function() {
		// eslint-disable-next-line no-alert
		if ( window.confirm( ms_data.cancel_msg ) ) {
			return true;
		}
		return false;
	} );
};

window.ms_init.frontend_profile = function init() {
	const args = {
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			email: {
				required: true,
				email: true,
			},
			password: {
				minlength: 5,
			},
			password2: {
				equalTo: '.ms-form-element #password',
			},
		},
	};

	jQuery( '#ms-view-frontend-profile-form' ).validate( args );
};

window.ms_init.frontend_register = function init() {
	let $ = jQuery,
		first_last_name;

	if ( $( '#ms-shortcode-register-user-form #display_name' ).length ) {
		$( document ).on( 'blur', '#username', function() {
			const username = $( this ).val();
			if ( username.trim() === '' ) {
				$( '#display_username_option' ).remove();
			} else if ( $( '#display_username_option' ).length ) {
				$( '#display_username_option' ).attr( 'value', username ).text( username );
			} else {
				$( '#display_name' ).append( '<option id="display_username_option" value="' + username + '">' + username + '</option>' );
			}
		} );

		if ( $( '#first_name' ).length ) {
			$( document ).on( 'blur', '#first_name', function() {
				const first_name = $( this ).val();
				if ( first_name.trim() === '' ) {
					$( '#display_first_name_option' ).remove();
				} else if ( $( '#display_first_name_option' ).length ) {
					$( '#display_first_name_option' ).attr( 'value', first_name ).text( first_name );
				} else {
					$( '#display_name' ).append( '<option id="display_first_name_option" value="' + first_name + '">' + first_name + '</option>' );
				}
				first_last_name();
			} );
		}

		if ( $( '#last_name' ).length ) {
			$( document ).on( 'blur', '#last_name', function() {
				const last_name = $( this ).val();
				if ( last_name.trim() === '' ) {
					$( '#display_last_name_option' ).remove();
				} else if ( $( '#display_last_name_option' ).length ) {
					$( '#display_last_name_option' ).attr( 'value', last_name ).text( last_name );
				} else {
					$( '#display_name' ).append( '<option id="display_last_name_option" value="' + last_name + '">' + last_name + '</option>' );
				}
				first_last_name();
			} );
		}

		first_last_name = function() {
			const fname = $( '#first_name' ).val(),
				lname = $( '#last_name' ).val();

			if ( fname.trim() === '' || lname.trim() === '' ) {
				$( '#display_first_last_option' ).remove();
				return;
			}

			const name = fname + ' ' + lname,
				rname = lname + ' ' + fname;

			$( '#display_name' ).append( '<option id="display_first_last_option" value="' + name + '">' + name + '</option>' );
			$( '#display_name' ).append( '<option id="display_first_last_option" value="' + rname + '">' + rname + '</option>' );
		};
	}
};
