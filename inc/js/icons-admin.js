( function( $ ) {
	'use strict';

	$( document ).on( 'change', '#cscompanion-select-all', function() {
		$( '.cscompanion-icon-checkbox' ).prop( 'checked', $( this ).is( ':checked' ) );
	} );

} )( jQuery );