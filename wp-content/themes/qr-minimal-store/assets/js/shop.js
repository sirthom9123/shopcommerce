/* Shop listing: filters (rail on desktop, bottom sheet on phones), sort and "Show more". */
( function () {
	document.documentElement.classList.add( 'qr-js' );
	var i18n = window.qrShop || {};
	var wide = window.matchMedia( '(min-width: 901px)' );
	var panel = document.querySelector( '[data-qr-filters]' );
	var form = panel ? panel.querySelector( 'form' ) : null;
	var openBtn = document.querySelector( '[data-qr-filters-open]' );
	var backdrop = document.querySelector( '.qr-filters-backdrop' );
	var applyBtn = panel ? panel.querySelector( '[data-qr-filters-apply]' ) : null;
	var lastFocus = null;

	function format( template, n ) {
		return String( template || '%d' ).replace( '%d', n );
	}

	function values( name ) {
		return Array.prototype.map.call( form.querySelectorAll( 'input[name="' + name + '"]:checked' ), function ( input ) {
			return input.value;
		} );
	}

	// Clean URLs: price=a,b instead of price[]=a&price[]=b.
	function filterUrl() {
		var params = new URLSearchParams();
		form.querySelectorAll( 'input[type="hidden"]' ).forEach( function ( input ) {
			params.set( input.name, input.value );
		} );
		if ( form.querySelector( 'input[name="stock"]:checked' ) ) {
			params.set( 'stock', 'all' );
		}
		var price = values( 'price[]' );
		var brand = values( 'brand[]' );
		if ( price.length ) {
			params.set( 'price', price.join( ',' ) );
		}
		if ( brand.length ) {
			params.set( 'brand', brand.join( ',' ) );
		}
		var query = params.toString().replace( /%2C/g, ',' );
		return form.getAttribute( 'action' ).split( '?' )[ 0 ] + ( query ? '?' + query : '' );
	}

	// Counts for the current ticks (each group counted with the other groups applied).
	function recount() {
		var data;
		try {
			data = JSON.parse( form.getAttribute( 'data-qr-facets' ) || '{}' );
		} catch ( e ) {
			return;
		}
		var rows = data.rows || [];
		var bands = data.bands || {};
		var all = !! form.querySelector( 'input[name="stock"]:checked' );
		var price = values( 'price[]' );
		var brand = values( 'brand[]' );
		function inBand( p, key ) {
			var b = bands[ key ];
			return b && p >= b[ 0 ] && ( b[ 1 ] <= 0 || p < b[ 1 ] );
		}
		function priceOk( r ) {
			return ! price.length || price.some( function ( key ) {
				return inBand( r[ 1 ], key );
			} );
		}
		function brandOk( r ) {
			return ! brand.length || r[ 2 ].some( function ( slug ) {
				return brand.indexOf( slug ) !== -1;
			} );
		}
		function stockOk( r ) {
			return all || ! r[ 0 ];
		}
		var counts = { in_stock: 0, sold_out: 0 };
		var total = 0;
		rows.forEach( function ( r ) {
			if ( priceOk( r ) && brandOk( r ) ) {
				counts[ r[ 0 ] ? 'sold_out' : 'in_stock' ]++;
			}
			if ( stockOk( r ) && brandOk( r ) ) {
				Object.keys( bands ).forEach( function ( key ) {
					if ( inBand( r[ 1 ], key ) ) {
						counts[ 'price:' + key ] = ( counts[ 'price:' + key ] || 0 ) + 1;
					}
				} );
			}
			if ( stockOk( r ) && priceOk( r ) ) {
				r[ 2 ].forEach( function ( slug ) {
					counts[ 'brand:' + slug ] = ( counts[ 'brand:' + slug ] || 0 ) + 1;
				} );
			}
			if ( stockOk( r ) && priceOk( r ) && brandOk( r ) ) {
				total++;
			}
		} );
		form.querySelectorAll( '[data-qr-count]' ).forEach( function ( el ) {
			el.textContent = '(' + ( counts[ el.getAttribute( 'data-qr-count' ) ] || 0 ) + ')';
		} );
		if ( applyBtn ) {
			applyBtn.textContent = format( 1 === total ? i18n.showOne : i18n.showMany, total );
		}
	}

	function focusables() {
		return Array.prototype.filter.call( panel.querySelectorAll( 'a[href], button, input, select' ), function ( el ) {
			return ! el.disabled && el.offsetParent !== null;
		} );
	}

	function openSheet() {
		lastFocus = document.activeElement;
		panel.classList.add( 'is-open' );
		document.body.classList.add( 'qr-sheet-open' );
		if ( backdrop ) {
			backdrop.hidden = false;
		}
		openBtn.setAttribute( 'aria-expanded', 'true' );
		var first = panel.querySelector( '[data-qr-filters-close]' );
		if ( first ) {
			first.focus();
		}
	}

	function closeSheet() {
		panel.classList.remove( 'is-open' );
		document.body.classList.remove( 'qr-sheet-open' );
		if ( backdrop ) {
			backdrop.hidden = true;
		}
		openBtn.setAttribute( 'aria-expanded', 'false' );
		if ( lastFocus ) {
			lastFocus.focus();
		}
	}

	if ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			window.location.href = filterUrl();
		} );
		form.addEventListener( 'change', function () {
			if ( wide.matches ) {
				window.location.href = filterUrl();
				return;
			}
			recount();
		} );
	}

	if ( panel && openBtn ) {
		openBtn.addEventListener( 'click', openSheet );
		document.querySelectorAll( '[data-qr-filters-close]' ).forEach( function ( el ) {
			el.addEventListener( 'click', closeSheet );
		} );
		document.addEventListener( 'keydown', function ( event ) {
			if ( ! panel.classList.contains( 'is-open' ) ) {
				return;
			}
			if ( 'Escape' === event.key ) {
				closeSheet();
			} else if ( 'Tab' === event.key ) {
				var items = focusables();
				if ( ! items.length ) {
					return;
				}
				var first = items[ 0 ];
				var last = items[ items.length - 1 ];
				if ( event.shiftKey && document.activeElement === first ) {
					event.preventDefault();
					last.focus();
				} else if ( ! event.shiftKey && document.activeElement === last ) {
					event.preventDefault();
					first.focus();
				}
			}
		} );
		wide.addEventListener( 'change', function () {
			if ( wide.matches && panel.classList.contains( 'is-open' ) ) {
				closeSheet();
			}
		} );
	}

	// Sort: apply on change.
	var sort = document.querySelector( '[data-qr-sort]' );
	if ( sort ) {
		sort.addEventListener( 'change', function () {
			sort.form.submit();
		} );
	}

	// "Show more": load the next page into the grid.
	var more = document.querySelector( '[data-qr-more-link]' );
	var grid = document.querySelector( '[data-qr-grid]' );
	if ( more && grid && window.fetch && window.DOMParser ) {
		var total = parseInt( more.getAttribute( 'data-total' ), 10 ) || 0;
		var shown = parseInt( more.getAttribute( 'data-shown' ), 10 ) || 0;
		var text = document.querySelector( '[data-qr-progress-text]' );
		var bar = document.querySelector( '[data-qr-progress]' );
		var label = more.textContent;
		more.setAttribute( 'role', 'button' );
		more.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			if ( more.getAttribute( 'aria-busy' ) === 'true' ) {
				return;
			}
			more.setAttribute( 'aria-busy', 'true' );
			more.textContent = i18n.loading || label;
			fetch( more.href, { credentials: 'same-origin' } )
				.then( function ( response ) {
					if ( ! response.ok ) {
						throw new Error( response.status );
					}
					return response.text();
				} )
				.then( function ( html ) {
					var doc = new DOMParser().parseFromString( html, 'text/html' );
					var cards = doc.querySelectorAll( '[data-qr-grid] > li.qr-card' );
					var firstNew = null;
					cards.forEach( function ( card ) {
						var node = document.importNode( card, true );
						grid.appendChild( node );
						firstNew = firstNew || node;
					} );
					shown = Math.min( total, shown + cards.length );
					if ( text ) {
						text.textContent = text.textContent.replace( /\d+(\D+)\d+/, shown + '$1' + total );
					}
					if ( bar && total ) {
						bar.style.width = Math.round( ( 100 * shown ) / total ) + '%';
					}
					var next = doc.querySelector( '[data-qr-more-link]' );
					if ( next ) {
						more.href = next.href;
						more.textContent = label;
						more.removeAttribute( 'aria-busy' );
					} else {
						more.remove();
					}
					// Keep keyboard users in place: move focus to the first new product.
					var link = firstNew ? firstNew.querySelector( '.qr-card__name a' ) : null;
					if ( link ) {
						link.focus();
					}
				} )
				.catch( function () {
					more.textContent = label;
					more.removeAttribute( 'aria-busy' );
					if ( text ) {
						text.textContent = i18n.error || text.textContent;
					}
				} );
		} );
	}
} )();
