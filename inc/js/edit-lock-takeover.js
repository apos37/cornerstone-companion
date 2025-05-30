( function( $ ) {
	'use strict';

	console.log( 'Stop! Cornerstone is already being edited.' );

	if ( typeof cscompanion_edit_lock_takeover === 'undefined' ) return;

	/**
	 * Timers
	 */

	// How long to wait for the other person to get out
	const pleaseWaitTime = 30000;

	
	/**
	 * The someone is editing modal
	 */
	const modal = $( `
		<div id="cscompanion-lock-overlay">
			<div id="cscompanion-lock-modal">
				<div class="cscompanion-lock-content">
					<h2>${ cscompanion_edit_lock_takeover.text.title }</h2>
					<p>${ cscompanion_edit_lock_takeover.text.message }</p>
					<div class="cscompanion-lock-buttons">
						<button id="cscompanion-go-back">${ cscompanion_edit_lock_takeover.text.go_back }</button>
						<button id="cscompanion-take-over">${ cscompanion_edit_lock_takeover.text.take_over }</button>
					</div>
				</div>
			</div>
		</div>
	`);

	$( 'body' ).append( modal );

	const parent$ = window.parent.jQuery;
	parent$( 'a, button' ).not( '#cscompanion-go-back, #cscompanion-take-over' ).on( 'click.cscompanion-lock', function( e ) {
		e.preventDefault();
		e.stopImmediatePropagation();
		return false;
	} );

	$( '#cscompanion-go-back' ).on( 'click', function() {
		window.history.back();
	} );

	$( '#cscompanion-take-over' ).on( 'click', function() {
		if ( !confirm( cscompanion_edit_lock_takeover.text.confirm_takeover ) ) {
			return;
		}

		const button = this;
		const start = Date.now();
		const end = start + pleaseWaitTime;

		$( button ).prop( 'disabled', true );
		$( '#cscompanion-lock-modal p' ).text( cscompanion_edit_lock_takeover.text.taking_over_msg );

		function updateCountdown() {
			const now = Date.now();
			const remaining = Math.max( 0, Math.round( ( end - now ) / 1000 ) );
			button.textContent = cscompanion_edit_lock_takeover.text.taking_over_btn + ' (' + remaining + ')';

			if ( remaining > 0 ) {
				setTimeout( updateCountdown, 1000 );
			}
		}

		updateCountdown();

		$.post( cscompanion_edit_lock_takeover.ajax_url, {
			action: cscompanion_edit_lock_takeover.action,
			nonce: cscompanion_edit_lock_takeover.nonce
		} ).done( function( response ) {
			setTimeout( function() {
				window.top.location.reload();
			}, pleaseWaitTime );
		} );
	} );

} )( jQuery );