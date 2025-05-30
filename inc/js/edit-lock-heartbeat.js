( function( $ ) {
	'use strict';

	$( document ).on( 'heartbeat-tick', function( e, data ) {
		let notice = $( '#cscompanion-lock-notice' );

		if ( data.cscompanion_lock_notice ) {
			if ( !notice.length ) {
				notice = $( '<div id="cscompanion-lock-notice" class="notice notice-warning"><p></p></div>' );

				const marker = $( '.wrap > .wp-header-end' );
				if ( marker.length ) {
					marker.after( notice );
				} else {
					$( '.wrap' ).append( notice );
				}
			}

			notice.find( 'p' ).html(
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:1.5em;height:1.5em;vertical-align:middle;margin-right:0.3em;fill:#DBA617;"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>' +
				data.cscompanion_lock_notice +
				' <button id="cscompanion-force-exit" class="button button-secondary" style="float:right;margin-left:1em;">' + cscompanion_edit_lock_heartbeat.text.button + '</button>'
			);

			$( '#cscompanion-force-exit' ).off( 'click' ).on( 'click', function( e ) {
				e.preventDefault();

				if ( !confirm( cscompanion_edit_lock_heartbeat.text.confirm ) ) {
					return;
				}

				$.post( cscompanion_edit_lock_heartbeat.ajax_url, {
					action: cscompanion_edit_lock_heartbeat.action,
					nonce: cscompanion_edit_lock_heartbeat.nonce
				} ).done( function( response ) {
					if ( response && response.success ) {
						location.reload();
					} else {
						alert( 'Something went wrong. The editor may not have been released.' );
					}
				} ).fail( function() {
					alert( 'Request failed. Please check your connection or try again.' );
				} );
			} );
		} else if ( notice.length ) {
			notice.remove();
		}
	} );
} )( jQuery );
