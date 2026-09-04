( function () {
	const section = document.querySelector( '[data-deal-end]' );
	if ( ! section ) {
		return;
	}

	const end = Number( section.dataset.dealEnd ) * 1000;
	if ( ! end ) {
		return;
	}

	const days = section.querySelector( '[data-days]' );
	const hours = section.querySelector( '[data-hours]' );
	const minutes = section.querySelector( '[data-minutes]' );
	const seconds = section.querySelector( '[data-seconds]' );
	if ( ! days || ! hours || ! minutes || ! seconds ) {
		return;
	}

	function pad( n ) {
		return String( n ).padStart( 2, '0' );
	}

	function tick() {
		const diff = Math.max( 0, end - Date.now() );
		const s = Math.floor( diff / 1000 );

		days.textContent = pad( Math.floor( s / 86400 ) );
		hours.textContent = pad( Math.floor( ( s % 86400 ) / 3600 ) );
		minutes.textContent = pad( Math.floor( ( s % 3600 ) / 60 ) );
		seconds.textContent = pad( s % 60 );

		if ( diff <= 0 && timer ) {
			clearInterval( timer );
		}
	}

	tick();
	const timer = setInterval( tick, 1000 );
} )();
