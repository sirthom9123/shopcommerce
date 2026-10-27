/* Cart and checkout (Blocks): store wording, cart count and free-delivery progress from wc/store/cart. */
( function () {
	var cfg = window.qrCartCheckout || {};
	var i18n = cfg.i18n || {};
	var labels = cfg.labels || {};
	var wp = window.wp || {};

	// Runs before the Blocks scripts render, so step titles and labels use the store wording.
	if ( wp.hooks ) {
		wp.hooks.addFilter( 'i18n.gettext_woocommerce', 'qr-minimal-store/labels', function ( translation, text ) {
			return Object.prototype.hasOwnProperty.call( labels, text ) ? labels[ text ] : translation;
		} );
	}

	var blocksCheckout = window.wc && window.wc.blocksCheckout;
	if ( blocksCheckout && blocksCheckout.registerCheckoutFilters && cfg.proceedLabel ) {
		blocksCheckout.registerCheckoutFilters( 'qr-minimal-store', {
			proceedToCheckoutButtonLabel: function () {
				return cfg.proceedLabel;
			},
		} );
	}

	if ( ! wp.data ) {
		return;
	}

	function format( template ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var next = 0;
		return String( template ).replace( /%(?:(\d+)\$)?[sd]/g, function ( match, position ) {
			var value = position ? args[ parseInt( position, 10 ) - 1 ] : args[ next++ ];
			return value === undefined ? '' : String( value );
		} );
	}

	function minorUnit( totals ) {
		var minor = parseInt( totals.currency_minor_unit, 10 );
		return isNaN( minor ) ? 2 : minor;
	}

	// Store currency format from the cart totals; non-breaking spaces keep "R2 039,90" on one line.
	function money( amount, totals, trimWhole ) {
		var minor = trimWhole && Math.round( amount ) === amount ? 0 : minorUnit( totals );
		var parts = Math.abs( amount ).toFixed( minor ).split( '.' );
		var whole = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, totals.currency_thousand_separator || '' );
		var text = ( totals.currency_prefix || '' ) + whole + ( minor > 0 ? ( totals.currency_decimal_separator || '.' ) + parts[ 1 ] : '' ) + ( totals.currency_suffix || '' );
		return text.replace( / /g, ' ' );
	}

	// Same base as qr-shipping: displayed item subtotal, before coupons.
	function itemsSubtotal( totals ) {
		var value = parseInt( totals.total_items, 10 ) || 0;
		if ( cfg.pricesIncludeTax ) {
			value += parseInt( totals.total_items_tax, 10 ) || 0;
		}
		return value / Math.pow( 10, minorUnit( totals ) );
	}

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( text ) {
			node.textContent = text;
		}
		return node;
	}

	function progressBar( percent ) {
		var track = el( 'div', 'qr-free-delivery__track' );
		var fill = el( 'div', 'qr-free-delivery__fill' );
		track.setAttribute( 'aria-hidden', 'true' );
		fill.style.width = percent + '%';
		track.appendChild( fill );
		return track;
	}

	function renderDelivery( box, totals, count ) {
		var rule = cfg.freeDelivery;
		var variant = box.getAttribute( 'data-qr-free-delivery' );
		var subtotal = itemsSubtotal( totals );
		var left = Math.round( ( rule.threshold - subtotal ) * 100 ) / 100;
		var signature = count ? variant + '|' + left + '|' + totals.currency_code : 'empty';
		if ( box.getAttribute( 'data-qr-rendered' ) === signature ) {
			return;
		}
		box.setAttribute( 'data-qr-rendered', signature );
		box.textContent = '';
		box.classList.toggle( 'is-unlocked', count > 0 && left <= 0 );
		if ( ! count ) {
			return;
		}

		var threshold = money( rule.threshold, totals, true );
		var line = el( 'p', 'qr-free-delivery__text' );
		if ( left <= 0 ) {
			line.appendChild( el( 'strong', '', i18n.unlocked ) );
			line.appendChild( document.createTextNode( ' ' + format( i18n.unlockedNote, threshold ) ) );
			box.appendChild( line );
			return;
		}

		if ( 'checkout' === variant ) {
			line.appendChild( document.createTextNode( format( i18n.away, threshold, money( left, totals ) ) + ' ' ) );
			var link = el( 'a', '', i18n.addSomething );
			link.href = cfg.shopUrl;
			line.appendChild( link );
			box.appendChild( line );
			return;
		}

		line.appendChild( el( 'strong', '', format( i18n.addMore, money( left, totals ) ) ) );
		line.appendChild( document.createTextNode( ' ' + format( i18n.feeNote, money( rule.fee, totals, true ), threshold ) ) );
		box.appendChild( line );
		box.appendChild( progressBar( Math.max( 0, Math.min( 100, ( subtotal / rule.threshold ) * 100 ) ) ) );
	}

	function update() {
		var store = wp.data.select( 'wc/store/cart' );
		if ( ! store || ! store.getCartData ) {
			return;
		}
		var cart = store.getCartData();
		var totals = store.getCartTotals();
		if ( ! cart || ! totals || totals.total_items === undefined ) {
			return;
		}
		var count = parseInt( cart.itemsCount, 10 ) || 0;

		document.querySelectorAll( '[data-qr-cart-count]' ).forEach( function ( node ) {
			var text = count ? ' · ' + ( 1 === count ? i18n.item : format( i18n.items, count ) ) : '';
			if ( node.textContent !== text ) {
				node.textContent = text;
			}
		} );

		if ( cfg.freeDelivery ) {
			document.querySelectorAll( '[data-qr-free-delivery]' ).forEach( function ( box ) {
				renderDelivery( box, totals, count );
			} );
		}
	}

	// The Blocks cart mounts and re-mounts its markup after this script runs: follow the
	// store and the DOM, batched to one update per frame.
	var queued = false;
	function schedule() {
		if ( queued ) {
			return;
		}
		queued = true;
		window.requestAnimationFrame( function () {
			queued = false;
			update();
		} );
	}

	wp.data.subscribe( schedule );
	if ( window.MutationObserver ) {
		new MutationObserver( schedule ).observe( document.body, { childList: true, subtree: true } );
	}
	schedule();
} )();
