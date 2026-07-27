( function( $ ) {
	'use strict';

    function updateSyncButtonState() {
		const urlField = $( '#cscompanion_icons_remote_url' );
		const keyField = $( '#cscompanion_icons_remote_key' );
		const syncButton = $( '#cscompanion-sync-icons' );

		if ( !urlField.length || !keyField.length || !syncButton.length ) {
			return;
		}

		const currentUrl = urlField.val().trim();
		const currentKey = keyField.val().trim();
		const hasBoth = currentUrl !== '' && currentKey !== '';
		const matchesSaved = currentUrl === cscompanion_icons_settings.saved_remote_url && currentKey === cscompanion_icons_settings.saved_remote_key;

		if ( !hasBoth ) {
			syncButton.prop( 'disabled', true ).text( cscompanion_icons_settings.text.check_icons );
			return;
		}

		if ( !matchesSaved ) {
			syncButton.prop( 'disabled', true ).text( cscompanion_icons_settings.text.save_first );
			return;
		}

		syncButton.prop( 'disabled', false ).text( cscompanion_icons_settings.text.check_icons );
	}

	$( document ).on( 'input', '#cscompanion_icons_remote_url, #cscompanion_icons_remote_key', updateSyncButtonState );
	$( updateSyncButtonState );

    function showNotice( status, message, isDismissible ) {
		const noticeClass = 'notice notice-' + status + ( isDismissible ? ' is-dismissible' : '' );
		const dismissButton = isDismissible
			? '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
			: '';

		$( '#cscompanion-sync-status' ).html(
			'<div class="' + noticeClass + '" style="margin: 1em 0 0;"><p>' + message + '</p>' + dismissButton + '</div>'
		);
	}

	$( document ).on( 'click', '#cscompanion-sync-status .notice-dismiss', function() {
		$( this ).closest( '.notice' ).fadeOut( 200, function() {
			$( this ).remove();
		} );
	} );

	$( document ).on( 'click', '#cscompanion-sync-icons', function() {
		const button = $( this );

		button.prop( 'disabled', true );
		showNotice( 'info', cscompanion_icons_settings.text.checking, false );

		$.post( cscompanion_icons_settings.ajax_url, {
			action: cscompanion_icons_settings.action_check,
			nonce: cscompanion_icons_settings.nonce
		} ).done( function( response ) {
			button.prop( 'disabled', false );

			if ( !response.success ) {
				showNotice( 'error', response.data || cscompanion_icons_settings.text.error, true );
				return;
			}

			const icons = response.data.icons;
            console.log(icons);

			if ( !icons.length ) {
				showNotice( 'success', cscompanion_icons_settings.text.none_found, true );
				return;
			}

			const prompt = cscompanion_icons_settings.text.found_prompt.replace( '%d', icons.length );

			$( '#cscompanion-sync-status' ).html(
				'<div class="notice notice-warning" style="margin: 1em 0 0;"><p>' + prompt + '</p>' +
				'<p><button type="button" class="button button-primary" id="cscompanion-sync-yes">' + cscompanion_icons_settings.text.yes + '</button> ' +
				'<button type="button" class="button" id="cscompanion-sync-no">' + cscompanion_icons_settings.text.no + '</button></p></div>'
			);

			$( '#cscompanion-sync-no' ).on( 'click', function() {
				$( '#cscompanion-sync-status' ).empty();
			} );

			$( '#cscompanion-sync-yes' ).on( 'click', function() {
				importIcons( icons );
			} );
		} ).fail( function() {
			button.prop( 'disabled', false );
			showNotice( 'error', cscompanion_icons_settings.text.error, true );
		} );
	} );


	function importIcons( icons ) {
		const total = icons.length;
		const chunkSize = 10;
		let processed = 0;
		let failures = [];

		showNotice( 'info', '<span class="spinner is-active" style="float:none;"></span> ' + formatImporting( 0, total ), false );

		function importNextChunk() {
			if ( processed >= total ) {
				if ( failures.length ) {
					showNotice( 'warning', cscompanion_icons_settings.text.done_with_errors.replace( '%1$d', total - failures.length ).replace( '%2$d', failures.length ) + '<br>' + failures.join( '<br>' ), true );
				} else {
					showNotice( 'success', cscompanion_icons_settings.text.done.replace( '%d', total ), true );
				}
				return;
			}

			const chunk = icons.slice( processed, processed + chunkSize );

			$.post( cscompanion_icons_settings.ajax_url, {
				action: cscompanion_icons_settings.action_import,
				nonce: cscompanion_icons_settings.nonce,
				icons: JSON.stringify( chunk )
			} ).done( function( response ) {
				if ( !response.success ) {
					showNotice( 'error', response.data || cscompanion_icons_settings.text.error, true );
					return;
				}

				response.data.results.forEach( function( result ) {
					if ( !result.success ) {
						failures.push( result.slug + ': ' + result.message );
					}
				} );

				processed += chunk.length;
				showNotice( 'info', '<span class="spinner is-active" style="float:none;"></span> ' + formatImporting( processed, total ), false );

				importNextChunk();
			} ).fail( function() {
				showNotice( 'error', cscompanion_icons_settings.text.error, true );
			} );
		}

		importNextChunk();
	}


	function formatImporting( current, total ) {
		return cscompanion_icons_settings.text.importing_batch
			.replace( '%1$d', current )
			.replace( '%2$d', total );
	}

	$( document ).on( 'click', '.cscompanion-regenerate-key', function() {
		const button = $( this );
		const target = button.data( 'target' );
		const field = $( '#' + target );
		const spinner = $( '.cscompanion-key-spinner' );
		const hasKey = button.data( 'has-key' ) === true || button.data( 'has-key' ) === 'true';

		if ( hasKey && !confirm( cscompanion_icons_settings.text.confirm_regenerate ) ) {
			return;
		}

		button.prop( 'disabled', true );
		spinner.addClass( 'is-active' );

		$.post( cscompanion_icons_settings.ajax_url, {
			action: cscompanion_icons_settings.action_regenerate,
			nonce: cscompanion_icons_settings.nonce
		} ).done( function( response ) {
			button.prop( 'disabled', false );
			spinner.removeClass( 'is-active' );

			if ( !response.success ) {
				alert( response.data || cscompanion_icons_settings.text.error );
				return;
			}

			field.val( response.data.key );
			button.text( cscompanion_icons_settings.text.regenerate_label );
			button.data( 'has-key', true );
			$( '.cscompanion-clear-key, .cscompanion-copy-key' ).prop( 'disabled', false );
		} ).fail( function() {
			button.prop( 'disabled', false );
			spinner.removeClass( 'is-active' );
			alert( cscompanion_icons_settings.text.error );
		} );
	} );


	$( document ).on( 'click', '.cscompanion-clear-key', function() {
		const button = $( this );
		const target = button.data( 'target' );
		const field = $( '#' + target );
		const spinner = $( '.cscompanion-key-spinner' );

		if ( !confirm( cscompanion_icons_settings.text.confirm_clear ) ) {
			return;
		}

		button.prop( 'disabled', true );
		spinner.addClass( 'is-active' );

		$.post( cscompanion_icons_settings.ajax_url, {
			action: cscompanion_icons_settings.action_clear,
			nonce: cscompanion_icons_settings.nonce
		} ).done( function( response ) {
			spinner.removeClass( 'is-active' );

			if ( !response.success ) {
				alert( cscompanion_icons_settings.text.error );
				button.prop( 'disabled', false );
				return;
			}

			field.val( '' );
			$( '.cscompanion-regenerate-key' ).text( cscompanion_icons_settings.text.generate_label ).data( 'has-key', false );
			$( '.cscompanion-clear-key, .cscompanion-copy-key' ).prop( 'disabled', true );
		} ).fail( function() {
			spinner.removeClass( 'is-active' );
			button.prop( 'disabled', false );
			alert( cscompanion_icons_settings.text.error );
		} );
	} );


	$( document ).on( 'click', '.cscompanion-copy-key', function() {
		const target = $( this ).data( 'target' );
		const field = $( '#' + target );
		const button = $( this );

		navigator.clipboard.writeText( field.val() ).then( function() {
			const original = button.text();
			button.text( cscompanion_icons_settings.text.copied );
			setTimeout( function() {
				button.text( original );
			}, 1500 );
		} );
	} );

} )( jQuery );