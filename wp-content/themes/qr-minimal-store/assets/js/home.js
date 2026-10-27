/* Home: click-to-load YouTube, only after marketing consent (qr-analytics). Nothing is requested from YouTube before. */
( function () {
	function allowed() {
		return !! ( window.qrConsent && window.qrConsent.granted( 'marketing' ) );
	}

	function load( box ) {
		var iframe = document.createElement( 'iframe' );
		iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent( box.getAttribute( 'data-qr-youtube' ) ) + '?autoplay=1&rel=0';
		iframe.title = box.getAttribute( 'data-qr-title' ) || 'YouTube video';
		iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
		iframe.allowFullscreen = true;
		iframe.className = 'qr-video__frame';
		box.textContent = '';
		box.appendChild( iframe );
		box.classList.add( 'is-playing' );
		iframe.focus();
	}

	document.querySelectorAll( '[data-qr-youtube]' ).forEach( function ( box ) {
		var play = box.querySelector( '[data-qr-video-play]' );
		var consent = box.querySelector( '.qr-video__consent' );
		var waiting = false;
		if ( ! play ) {
			return;
		}
		play.addEventListener( 'click', function () {
			if ( allowed() ) {
				load( box );
				return;
			}
			waiting = true;
			consent.hidden = false;
			var first = consent.querySelector( 'button, a' );
			if ( first ) {
				first.focus();
			}
		} );
		// Consent given from the settings dialog after pressing play: start the video.
		if ( window.qrConsent && window.qrConsent.onChange ) {
			window.qrConsent.onChange( function () {
				if ( waiting && allowed() && box.isConnected && ! box.classList.contains( 'is-playing' ) ) {
					load( box );
				}
			} );
		}
	} );
} )();
