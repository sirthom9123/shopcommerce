/* Click-to-load YouTube (home feature, product pages, 3D Printing page), only after marketing consent (qr-analytics). Nothing is requested from YouTube before. */
( function () {
	function allowed() {
		return !! ( window.qrConsent && window.qrConsent.granted( 'marketing' ) );
	}

	var still = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	// Ambient videos (3D Printing hero): muted, no controls, looping; our own Pause button puts the poster back.
	function load( box, focus ) {
		var id = encodeURIComponent( box.getAttribute( 'data-qr-youtube' ) );
		var ambient = box.hasAttribute( 'data-qr-ambient' );
		var iframe = document.createElement( 'iframe' );
		iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + ( ambient ? '?autoplay=1&mute=1&controls=0&loop=1&playlist=' + id + '&playsinline=1&rel=0&disablekb=1' : '?autoplay=1&rel=0' );
		iframe.title = box.getAttribute( 'data-qr-title' ) || 'YouTube video';
		iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
		iframe.allowFullscreen = true;
		iframe.className = 'qr-video__frame';
		if ( ambient ) {
			iframe.tabIndex = -1;
			box.insertBefore( iframe, box.firstChild );
			box.querySelector( '.qr-video__overlay' ).hidden = true;
			box.querySelector( '.qr-video__consent' ).hidden = true;
			var stop = box.querySelector( '[data-qr-video-stop]' );
			stop.hidden = false;
			if ( focus ) {
				stop.focus();
			}
		} else {
			box.textContent = '';
			box.appendChild( iframe );
			iframe.focus();
		}
		box.classList.add( 'is-playing' );
	}

	function unload( box ) {
		var frame = box.querySelector( '.qr-video__frame' );
		if ( frame ) {
			frame.remove();
		}
		box.classList.remove( 'is-playing' );
		box.querySelector( '[data-qr-video-stop]' ).hidden = true;
		box.querySelector( '.qr-video__overlay' ).hidden = false;
		box.querySelector( '[data-qr-video-play]' ).focus();
	}

	document.querySelectorAll( '[data-qr-youtube]' ).forEach( function ( box ) {
		var play = box.querySelector( '[data-qr-video-play]' );
		var consent = box.querySelector( '.qr-video__consent' );
		var stop = box.querySelector( '[data-qr-video-stop]' );
		var ambient = box.hasAttribute( 'data-qr-ambient' ) && ! still;
		var waiting = false;
		if ( ! play ) {
			return;
		}
		if ( stop ) {
			stop.addEventListener( 'click', function () {
				unload( box );
			} );
		}
		// Muted hero: starts by itself once marketing cookies are allowed (never before).
		if ( ambient && allowed() ) {
			load( box, false );
		}
		play.addEventListener( 'click', function () {
			if ( allowed() ) {
				load( box, true );
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
				if ( ( waiting || ambient ) && allowed() && box.isConnected && ! box.classList.contains( 'is-playing' ) ) {
					load( box, waiting );
				}
			} );
		}
	} );
} )();
