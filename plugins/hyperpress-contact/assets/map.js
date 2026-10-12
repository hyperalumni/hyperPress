/**
 * Draws every [hyperpress_map] placeholder. Google Maps calls this once it has loaded.
 */
window.hyperpressContactInitMaps = function () {
	'use strict';

	if ( typeof google === 'undefined' || ! google.maps ) {
		return;
	}

	var elements = document.querySelectorAll( '.hyperpress-map' );
	Array.prototype.forEach.call( elements, function ( el ) {
		if ( el.getAttribute( 'data-initialised' ) ) {
			return;
		}
		var lat = parseFloat( el.getAttribute( 'data-lat' ) );
		var lng = parseFloat( el.getAttribute( 'data-lng' ) );
		var zoom = parseInt( el.getAttribute( 'data-zoom' ), 10 );
		if ( isNaN( lat ) || isNaN( lng ) ) {
			return;
		}
		var center = { lat: lat, lng: lng };

		var map = new google.maps.Map( el, {
			center: center,
			zoom: isNaN( zoom ) ? 15 : zoom,
			disableDefaultUI: true,
			gestureHandling: 'none',
			keyboardShortcuts: false,
			clickableIcons: false
		} );
		new google.maps.Marker( { position: center, map: map } );
		el.setAttribute( 'data-initialised', '1' );
	} );
};
