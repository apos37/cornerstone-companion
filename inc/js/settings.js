( function( $ ) {
	'use strict';

	$( document ).on( 'change', '.cscompanion-toggle input[type="checkbox"]', function() {
		const labelEl = $( this ).siblings( 'span' ).find( '.label' );
		labelEl.text( this.checked ? cscompanion_settings.on : cscompanion_settings.off );
	} );

	$( document ).ready( function() {
		const options = typeof cscompanion_settings !== 'undefined' ? cscompanion_settings.options : [];
		console.log( options );

		function updateVisibility() {
			const values = {};
			$( 'input[id], select[id], textarea[id]' ).each( function() {
				const id = $( this ).attr( 'id' );
				if ( id ) {
					if ( this.type === 'checkbox' ) {
						values[ id ] = $( this ).is( ':checked' );
					} else {
						values[ id ] = $( this ).val();
					}
				}
			} );

			$( '.cscompanion-box-content' ).each( function() {
				const fieldEl = $( this );
				const inputEl = fieldEl.find( 'input[id], select[id], textarea[id]' ).first();
				const optionKey = inputEl.attr( 'id' );

				const optionData = options.find( function( o ) { return o.key === optionKey; } );

				if ( !optionData || !optionData.conditions ) return;

				const visible = optionData.conditions.every( function( dep ) {
					return values[ dep ];
				} );

				fieldEl.toggleClass( 'not-applicable', !visible );
			} );
		}

		updateVisibility();

		$( document ).on( 'change input', 'input, select, textarea', function() {
			updateVisibility();
		} );
	} );

} )( jQuery );
