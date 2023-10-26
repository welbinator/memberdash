/*! MemberDash - v1.0.0
 * Copyright (c) 2019; * Licensed GPLv2+ */

jQuery( function() {
	const ms_ajax = window.ms_ajax_login,
		frm_login = jQuery( 'form[action="login"]' ),
		frm_lost = jQuery( 'form[action="lostpassword"]' ),
		sts_login = frm_login.find( '.status' ),
		sts_lost = frm_lost.find( '.status' ),
		show_login = jQuery( 'a.login', 'form' ),
		show_lost = jQuery( 'a.lost', 'form' );

	// Auto-Focus on the user-name field.
	function set_focus() {
		let form = false;

		if ( frm_login.is( ':visible' ) ) {
			form = frm_login;
		} else if ( frm_lost.is( ':visible' ) ) {
			form = frm_lost;
		}

		if ( form ) {
			form.find( 'input.focus' ).focus();
		}
	}

	// Disable all fields inside the form.
	function disable_form( form ) {
		form.addClass( 'progress' );
		form.find( 'input, textarea, select, button' ).each( function() {
			jQuery( this ).data( 'ms-disabled', jQuery( this ).prop( 'disabled' ) );
			jQuery( this ).prop( 'disabled', true ).addClass( 'disabled' );
		} );
	}

	// Re-Enable all fields inside the form.
	function enable_form( form ) {
		form.removeClass( 'progress' ).prop( 'disabled', false );
		form.find( 'input, textarea, select, button' ).each( function() {
			if ( jQuery( this ).data( 'ms-disabled' ) ) {
				return;
			}
			jQuery( this ).prop( 'disabled', false ).removeClass( 'disabled' );
		} );
	}

	// Display the Ajax response message.
	function show_message( label, data ) {
		if ( undefined !== data.error ) {
			label.addClass( 'error' ).text( data.error );
		} else if ( undefined !== data.success ) {
			label.removeClass( 'error' ).text( data.success );
		}
	}

	// Switch between the forms.
	show_lost.on( 'click', function() {
		frm_login.hide();
		frm_lost.show();
		jQuery( '.ms-auth-header' ).html( ms_ajax.resetmessage );
		sts_lost.removeClass( 'error' ).text( '' );
		set_focus();
	} );

	show_login.on( 'click', function() {
		frm_lost.hide();
		frm_login.show();
		jQuery( '.ms-auth-header' ).html( ms_ajax.loginmessage );
		sts_login.removeClass( 'error' ).text( '' );
		set_focus();
	} );

	// Login Handler
	frm_login.on( 'submit', function( ev ) {
		let key,
			data = {},
			frm_current = jQuery( this ),
			fields = frm_current.serializeArray(),
			redirect = frm_current.find( 'input[name="redirect_to"]' );

		sts_login.removeClass( 'error' ).show().text( ms_ajax.loadingmessage );
		disable_form( frm_current );

		// Very simple serialization. Since the form is simple it will work...
		for ( key in fields ) {
			// eslint-disable-next-line no-prototype-builtins
			if ( fields.hasOwnProperty( key ) ) {
				data[ fields[ key ].name ] = fields[ key ].value;
			}
		}
		data.action = 'ms_login'; // calls wp_ajax_nopriv_ms_login

		jQuery.ajax( {
			type: 'POST',
			dataType: 'json',
			url: ms_ajax.ajaxurl + '?ms_ajax=1',
			data,
			success( dataA ) {
				enable_form( frm_current );
				show_message( sts_login, dataA );

				if ( dataA.loggedin ) {
					if ( undefined !== dataA.redirect && dataA.redirect.length > 5 ) {
						document.location.href = dataA.redirect;
					} else {
						document.location.href = redirect.val();
					}
				}
			},
			error() {
				const dataE = { error: ms_ajax.errormessage };
				enable_form( frm_current );
				show_message( sts_login, dataE );
			},
		} );

		ev.preventDefault();
		return false;
	} );

	// Lost-Pass Handler
	frm_lost.on( 'submit', function( ev ) {
		let key,
			data = {},
			fields = frm_lost.serializeArray();

		sts_lost.removeClass( 'error' ).show().text( ms_ajax.loadingmessage );
		disable_form( frm_lost );

		// Very simple serialization. Since the form is simple it will work...
		for ( key in fields ) {
			// eslint-disable-next-line no-prototype-builtins
			if ( fields.hasOwnProperty( key ) ) {
				data[ fields[ key ].name ] = fields[ key ].value;
			}
		}
		data.action = 'ms_lostpass'; // calls wp_ajax_nopriv_ms_login

		jQuery.ajax( {
			type: 'POST',
			dataType: 'json',
			url: ms_ajax.ajaxurl,
			data,
			success( dataS ) {
				enable_form( frm_lost );
				show_message( sts_lost, dataS );
			},
			error() {
				const dataE = { error: ms_ajax.errormessage };
				enable_form( frm_lost );
				show_message( sts_lost, dataE );
			},
		} );

		ev.preventDefault();
		return false;
	} );

	if ( frm_login.hasClass( 'autofocus' ) ) {
		set_focus();
	}
} );
