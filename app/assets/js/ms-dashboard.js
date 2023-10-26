jQuery( function() {
	function ms_toggle_widget_ajax_processing( daterangeId, isError = false ) {
		let ajaxBlockId = '#' + jQuery( '#' + daterangeId ).data( 'ajaxBlockId' );
		let spinnerId = '#spinner-' + daterangeId;
		let errorId = '#error-' + daterangeId;
		if ( ! jQuery( spinnerId ).hasClass( 'is-active' ) ) {
			jQuery( errorId ).hide( );
			jQuery( ajaxBlockId ).hide( );
			jQuery( spinnerId ).addClass( 'is-active' );
		} else {
			jQuery( spinnerId ).removeClass( 'is-active' );
			if ( isError ) {
				jQuery( errorId ).show( );
			} else {
				jQuery( ajaxBlockId ).show( );
			}
		}
	}

	function create_ms_daterange( objId ) {
		let start = moment().subtract( jQuery( '#' + objId ).data( 'startDaysBefore' ), 'days' );
		let end = moment();

		function cb( startDate, endDate ) {
			jQuery( '#' + objId + ' span' ).html(
				startDate.format( 'MMMM D, YYYY' ) + ' - ' + endDate.format( 'MMMM D, YYYY' ),
			);
		}

		jQuery( '#' + objId ).daterangepicker(
			{
				startDate: start,
				endDate: end,
				opens: 'left',
				ranges: {
					Today: [ moment(), moment() ],
					Yesterday: [
						moment().subtract( 1, 'days' ),
						moment().subtract( 1, 'days' ),
					],
					'Last 7 Days': [ moment().subtract( 6, 'days' ), moment() ],
					'Last 30 Days': [ moment().subtract( 29, 'days' ), moment() ],
					'This Month': [ moment().startOf( 'month' ), moment().endOf( 'month' ) ],
					'Last Month': [
						moment().subtract( 1, 'month' ).startOf( 'month' ),
						moment().subtract( 1, 'month' ).endOf( 'month' ),
					],
				},
			},
			cb,
		);

		jQuery( '#' + objId ).on( 'apply.daterangepicker', function( ev, picker ) {
			let startDate = picker.startDate.format( 'YYYY-MM-DD' );
			let endDate = picker.endDate.format( 'YYYY-MM-DD' );
			let ajaxAction = jQuery( '#' + ev.currentTarget.id ).data( 'ajaxAction' );
			let ajaxBlockId = '#' + jQuery( '#' + ev.currentTarget.id ).data( 'ajaxBlockId' );

			if ( ! ajaxAction ) {
				return; // no action defined
			}

			ms_toggle_widget_ajax_processing( ev.currentTarget.id );

			jQuery.ajax( {
				type: 'POST',
				dataType: 'html',
				url: window.ajaxurl,
				data: { action: ajaxAction, start_date: startDate, end_date: endDate },
				success( response ) {
					jQuery( ajaxBlockId ).html( response );
					ms_toggle_widget_ajax_processing( ev.currentTarget.id );
				},
				error() {
					ms_toggle_widget_ajax_processing( ev.currentTarget.id, true );
				},
			} );
		} );

		cb( start, end );
	}

	// creating date ranges objects
	jQuery.each( jQuery( '.ms-daterange' ), function( _, obj ) {
		create_ms_daterange( jQuery( obj ).attr( 'id' ) );
	} );

	function manageReportFilters( reportType ) {
		// hide all report filters
		jQuery( '#ms-daterange-wrapper' ).hide();
		jQuery( '#select2-ms-reporting-membership-container,#select2-ms-reporting-gateway-container' ).parent().parent().parent().hide();
		if ( reportType == 'new_users' ) {
			jQuery( '#ms-daterange-wrapper' ).show();
		} else if ( reportType == 'new_paying_users' ) {
			jQuery( '#ms-daterange-wrapper' ).show();
			jQuery( '#select2-ms-reporting-membership-container,#select2-ms-reporting-gateway-container' ).parent().parent().parent().show();
		}
	}

	// define report filter accordingly report type
	jQuery( '#ms-reporting-type' ).change( function() {
		manageReportFilters( jQuery( this ).val() );
	} );
	manageReportFilters( jQuery( '#ms-reporting-type' ).val() );

	// download csv action
	jQuery( '#ms-download-csv' ).on( 'click', function( ) {
		let url = jQuery( this ).data( 'url' );

		// validate params
		let reportType = jQuery( '#ms-reporting-type' ).val();
		if ( ! reportType ) {
			alert( window.ms_text.select_report_type_warning ); // eslint-disable-line no-alert
			return;
		}
		let start_date = jQuery( '#ms-reporting-daterange' ).data( 'daterangepicker' ).startDate.format( 'YYYY-MM-DD' );
		let end_date = jQuery( '#ms-reporting-daterange' ).data( 'daterangepicker' ).endDate.format( 'YYYY-MM-DD' );
		let membership = jQuery( '#ms-reporting-membership' ).val();
		let gateway = jQuery( '#ms-reporting-gateway' ).val();

		window.open( url + '&report_type=' + reportType + '&start_date=' + start_date + '&end_date=' + end_date + '&membership=' + membership + '&gateway=' + gateway );
	} );
} );
