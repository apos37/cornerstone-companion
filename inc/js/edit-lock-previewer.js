( function( $ ) {
	'use strict';

	if ( typeof cscompanion_edit_lock_previewer === 'undefined' ) return;

	const parent$ = window.parent.jQuery;

	/**
	 * Timers
	 */

	// The edit lock refresh timer (ie every 15 seconds)
	const intervalTime = cscompanion_edit_lock_previewer.interval;

	// The last rendered time will be checked in intervals of this number of seconds (ie every 60 seconds)
	const updateLastRenderedTime = 60000;

	// How long it takes to show the warning if there is no activity (ie every 30 minutes)
	const autobootShowDialogTime = cscompanion_edit_lock_previewer.autoboot_time * 1000;
	// const autobootShowDialogTime = 10 * 1000; // For testing only

	// Check for inactivity every 60 seconds
	var autobootCheckForInactivityTime = 60000;
	autobootCheckForInactivityTime = autobootShowDialogTime < autobootCheckForInactivityTime ? autobootShowDialogTime : autobootCheckForInactivityTime;

	// How long the warning dialog waits for a response before auto booting (ie 30 seconds)
	const autobootNoResponseTime = cscompanion_edit_lock_previewer.autoboot_no_reponse_time;
	// const autobootNoResponseTime = 5 * 1000; // For testing only

	// console.log( intervalTime, updateLastRenderedTime, autobootShowDialogTime, autobootCheckForInactivityTime, autobootNoResponseTime );


	/**
	 * Refresh edit lock
	 */
	if ( cscompanion_edit_lock_previewer.locked ) {
		console.log( 'Cornerstone locked' );
	}

	const interval = setInterval( function() {
		$.get( cscompanion_edit_lock_previewer.ajax_url, {
			action: cscompanion_edit_lock_previewer.action_previewer,
			nonce: cscompanion_edit_lock_previewer.nonce,
			post_id: cscompanion_edit_lock_previewer.post_id
		} ).done( function( response ) {
			if ( !response.success ) {
				console.warn( 'Edit lock refresh failed:', response );
				return;
			}

			if ( response.data.taken_over ) {
				console.warn( `Edit lock was taken over by ${response.data.name} (User ID: ${response.data.user_id})` );

				// Save our progress if enabled
				saveOnly();

				// Close other modals if they are active
				closeOtherModals();

				// Add our modal
				const modal = createModal(
					cscompanion_edit_lock_previewer.text.title,
					cscompanion_edit_lock_previewer.text.user_taken_over.replace( '%s', response.data.name ),
					[ { id: 'cscompanion-exit', text: cscompanion_edit_lock_previewer.text.exit } ]
				);

				$( 'body' ).append( modal );

				$( '#cscompanion-exit' ).on( 'click', function() {
					$.post( cscompanion_edit_lock_previewer.ajax_url, {
						action: cscompanion_edit_lock_previewer.action_exit,
						nonce: cscompanion_edit_lock_previewer.nonce,
						post_id: cscompanion_edit_lock_previewer.post_id
					} ).always( function() {
						exitOnly();
					} );
				} );
			} else {
				// console.log( 'Edit lock refreshed:', response.data.lock );
			}
		} );
	}, intervalTime );


	/**
	 * Auto Boot
	 */
	let lastRenderUpdate = Date.now();

	// Only if it's enabled
	if ( cscompanion_edit_lock_previewer.autoboot_enabled ) {
		console.log( 'Auto boot enabled' );

		let blinkInterval;
		const originalTitle = parent.document.title;
		
		// Function to blink the browser tab title
		function startTitleBlink( message, interval = 1000 ) {
			if ( blinkInterval ) return;
			let toggle = false;
			blinkInterval = setInterval( function () {
				parent.document.title = toggle ? message : originalTitle;
				toggle = !toggle;
			}, interval );
		}

		let inactivityCheckInterval = null;
		let modalIsOpen = false;

		function startInactivityCheck() {
			inactivityCheckInterval = setInterval( function () {
				if ( modalIsOpen ) return;

				const now = Date.now();
				const since = now - lastRenderUpdate;

				if ( since > autobootShowDialogTime ) {
					modalIsOpen = true;

					$.post( cscompanion_edit_lock_previewer.ajax_url, {
						action: cscompanion_edit_lock_previewer.action_autoboot,
						nonce: cscompanion_edit_lock_previewer.nonce
					} ).done( function () {
						console.log( `You have been inactive for too long!` );
						startTitleBlink( '⚠️ Inactivity Timeout' );

						closeOtherModals();

						disableOtherButtons();

						const modal = createModal(
							cscompanion_edit_lock_previewer.text.autoboot_title,
							cscompanion_edit_lock_previewer.text.autoboot_msg,
							[
								{ id: 'cscompanion-continue', text: cscompanion_edit_lock_previewer.text.autoboot_continue },
								{ id: 'cscompanion-exit', text: cscompanion_edit_lock_previewer.text.autoboot_exit }
							]
						);

						$( 'body' ).append( modal );

						const autoExitTimeout = setTimeout( function() {
							$( '#cscompanion-exit' ).trigger( 'click' );
						}, autobootNoResponseTime );

						$( '#cscompanion-continue' ).on( 'click', function() {
							clearTimeout( autoExitTimeout );

							$.post( cscompanion_edit_lock_previewer.ajax_url, {
								action: cscompanion_edit_lock_previewer.action_since,
								nonce: cscompanion_edit_lock_previewer.nonce
							} ).done( function() {
								lastRenderUpdate = Date.now();
								modal.remove();
								modalIsOpen = false;
								enableOtherButtons();
								clearInterval( blinkInterval );
								parent.document.title = originalTitle;
								blinkInterval = null;

								// restart check
								startInactivityCheck();
							} );
						} );

						$( '#cscompanion-exit' ).on( 'click', function() {
							clearTimeout( autoExitTimeout );

							$.post( cscompanion_edit_lock_previewer.ajax_url, {
								action: cscompanion_edit_lock_previewer.action_exit,
								nonce: cscompanion_edit_lock_previewer.nonce,
								autoboot: true
							} ).always( function() {
								saveAndExit( 'autoboot' );
							} );
						} );

						// Stop checking while modal is open
						clearInterval( inactivityCheckInterval );
					} );
				}
			}, autobootCheckForInactivityTime );
		}

		startInactivityCheck();
	}

	// Create a modal
	function createModal( title, message, buttons ) {
		const buttonsHtml = buttons.map( btn => `<button id="${btn.id}">${btn.text}</button>` ).join( '' );
		const modal = $( `
			<div id="cscompanion-lock-overlay">
				<div id="cscompanion-lock-modal">
					<div class="cscompanion-lock-content">
						<h2>${title}</h2>
						<p>${message}</p>
						<div class="cscompanion-lock-buttons">${buttonsHtml}</div>
					</div>
				</div>
			</div>
		` );

		$( 'body' ).append( modal );
		return modal;
	}

	// Close all the other modals if active
	function closeOtherModals() {
		// Close Content Nav Menu if active
		const activeContentNav = parent$( '.tco-content-nav.is-cs-menu.is-active' );
		if ( activeContentNav.length ) {
			activeContentNav.find( 'button.tco-content-nav-toggle' ).trigger( 'click' );
		}

		// Close Modals if active
		const activeModal = parent$( '.tco-floater.is-active' );
		if ( activeModal.length ) {
			activeModal.find( 'button.tco-floater-header-close' ).trigger( 'click' );
		}
	}

	// Disable all other buttons so they can't save while the modal is up
	function disableOtherButtons() {
		parent$( 'a, button' ).not( '#cscompanion-continue, #cscompanion-exit' ).on( 'click.cscompanion-lock', function( e ) {
			e.preventDefault();
			e.stopImmediatePropagation();
			return false;
		} );
	}

	// Enable all other buttons
	function enableOtherButtons() {
		parent$( 'a, button' ).off( 'click.cscompanion-lock' );
	}

	// Save and exit
	function saveAndExit( reason ) {
		// The save button
		const saveButton = parent.document.querySelector( 'button.tco-bar-button.is-save.has-changed' );

		// Try to save?
		if ( cscompanion_edit_lock_previewer.should_save ) {
			
			// Remove click blocker in parent before attempting save
			enableOtherButtons();

			// Click the save button
			if ( saveButton ) {

				// Stop the preview/lock refresh
				clearInterval( interval );

				// The Exit button
				const exitBtn = $( '#cscompanion-exit' );

				// Change Exit button in modal to italic “Saving…”
				if ( exitBtn.length ) {
					exitBtn.html( `<em>${cscompanion_edit_lock_previewer.text.autoboot_save}…</em>` );
				}
				console.log( 'Saving post…' );

				const buttons = $( '#cscompanion-lock-modal button' );
				if ( buttons.length ) {
					buttons.prop( 'disabled', true );
				}
				
				saveButton.click();

				let reachedStage4 = false;

				const checkProgress = setInterval( function() {
					// re-query each time
					const progressEl = parent.document.querySelector( '.tco-progress' );
					if ( !progressEl ) {
						return;
					}

					const cls = progressEl.className;

					if ( cls.includes( 'is-stage-4' ) ) {
						reachedStage4 = true;
					}

					if ( reachedStage4 && cls.includes( 'is-stage-0' ) ) {
						clearInterval( checkProgress );
						console.log( 'save complete → going back' );
						window.history.back();
					}
				}, 250 );

				return;
			}
		}

		// Update the modal
		if ( saveButton ) {
			const modal = $( '#cscompanion-lock-modal' );
			if ( modal.length ) {
				if ( reason === 'autoboot' ) {
					modal.find( 'h2' ).text( cscompanion_edit_lock_previewer.text.title );
					modal.find( 'p' ).text( cscompanion_edit_lock_previewer.text.autoboot_exit_msg );
				}

				modal.find( '.cscompanion-lock-buttons' ).remove();
			}
		} else {
			const buttons = $( '#cscompanion-lock-modal button' );
			if ( buttons.length ) {
				buttons.prop( 'disabled', true );
			}
		}

		// Go back
		window.history.back();
	}

	// Save and exit
	function saveOnly() {
		// Try to save?
		if ( cscompanion_edit_lock_previewer.should_save ) {
			
			// Remove click blocker in parent before attempting save
			enableOtherButtons();

			// Click the save button
			const saveButton = parent.document.querySelector( 'button.tco-bar-button.is-save.has-changed' );
			if ( saveButton ) {

				// Stop the preview/lock refresh
				clearInterval( interval );

				console.log( 'Saving post…' );
				saveButton.click();

				let reachedStage4 = false;

				const checkProgress = setInterval( function() {
					// re-query each time
					const progressEl = parent.document.querySelector( '.tco-progress' );
					if ( !progressEl ) {
						return;
					}

					const cls = progressEl.className;

					if ( cls.includes( 'is-stage-4' ) ) {
						reachedStage4 = true;
					}

					if ( reachedStage4 && cls.includes( 'is-stage-0' ) ) {
						clearInterval( checkProgress );
						console.log( 'Save complete' );
					}
				}, 250 );
			}

			// Disable the other buttons again
			disableOtherButtons();
		}
		return;
	}

	// Save and exit
	function exitOnly() {
		// Disable the buttons
		const buttons = $( '#cscompanion-lock-modal button' );
		if ( buttons.length ) {
			buttons.prop( 'disabled', true );
		}

		// Go back
		window.history.back();
	}

	// Listen for preview render events and refresh since time
	window.addEventListener( 'cs-preview-render', function () {
		const now = Date.now();
		const since = now - lastRenderUpdate;

		// console.log( 'Cornerstone has rendered' );

		if ( since < updateLastRenderedTime ) {
			return;
		}

		lastRenderUpdate = now;

		$.post( cscompanion_edit_lock_previewer.ajax_url, {
			action: cscompanion_edit_lock_previewer.action_since,
			nonce: cscompanion_edit_lock_previewer.nonce
		} ).done( function () {
			// console.log( 'Logging render...' );
		} );
	} );

} )( jQuery );
