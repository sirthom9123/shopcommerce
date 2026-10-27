/* Product page: colour swatches, per-option stock, mobile panels, review form and the add-to-cart drawer (Store API). */
( function () {
	var cfg = window.qrProductPage || {};
	var i18n = cfg.i18n || {};
	var $ = window.jQuery;

	function format( template ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var next = 0;
		return String( template ).replace( /%(?:(\d+)\$)?[sd]/g, function ( match, position ) {
			var value = position ? args[ parseInt( position, 10 ) - 1 ] : args[ next++ ];
			return value === undefined ? '' : String( value );
		} );
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

	/* ---------- Sections: open on desktop, delivery and reviews collapsed on phones ---------- */

	var mobile = window.matchMedia( '(max-width: 768px)' );
	document.querySelectorAll( '.qr-pdp-panel' ).forEach( function ( panel ) {
		if ( mobile.matches && panel.hasAttribute( 'data-qr-mobile-closed' ) ) {
			panel.open = false;
		}
		panel.querySelector( 'summary' ).addEventListener( 'click', function ( event ) {
			if ( ! mobile.matches ) {
				event.preventDefault();
			}
		} );
	} );

	// In-page nav opens a collapsed panel it points to.
	document.querySelectorAll( '.qr-pdp-nav a' ).forEach( function ( link ) {
		link.addEventListener( 'click', function () {
			var target = document.querySelector( link.getAttribute( 'href' ) );
			if ( target && 'DETAILS' === target.tagName ) {
				target.open = true;
			}
		} );
	} );

	var writeReview = document.querySelector( '[data-qr-write-review]' );
	if ( writeReview ) {
		writeReview.addEventListener( 'click', function () {
			var form = document.getElementById( 'qr-review-form' );
			if ( ! form ) {
				return;
			}
			form.hidden = false;
			writeReview.setAttribute( 'aria-expanded', 'true' );
			var field = form.querySelector( 'select, textarea, input:not([type=hidden])' );
			if ( field ) {
				field.focus();
			}
		} );
	}

	/* ---------- Variable products: swatches and stock per option ---------- */

	var variationForm = document.querySelector( 'form.variations_form' );
	var variations = [];
	if ( variationForm ) {
		try {
			variations = JSON.parse( variationForm.getAttribute( 'data-product_variations' ) || '[]' ) || [];
		} catch ( e ) {
			variations = [];
		}
	}

	function optionState( attributeName, value ) {
		var states = variations
			.filter( function ( v ) {
				var current = v.attributes[ attributeName ];
				return current === value || '' === current;
			} )
			.map( function ( v ) {
				return v.qr_stock;
			} );
		if ( ! states.length ) {
			return 'unknown';
		}
		if ( states.indexOf( 'in-stock' ) !== -1 || states.indexOf( 'low-stock' ) !== -1 ) {
			return 'in-stock';
		}
		return states.indexOf( 'backorder' ) !== -1 ? 'backorder' : 'out-of-stock';
	}

	function buildSwatches( select ) {
		var attributeName = select.getAttribute( 'data-attribute_name' ) || select.name;
		var row = select.closest( 'tr' );
		var label = row ? row.querySelector( 'th label' ) : null;
		var labelText = label ? label.textContent.trim() : '';
		if ( ! /colou?r/i.test( labelText + ' ' + attributeName ) ) {
			return;
		}

		var group = el( 'div', 'qr-swatches' );
		group.setAttribute( 'role', 'radiogroup' );
		group.setAttribute( 'aria-label', labelText );
		var hasFaded = false;

		Array.prototype.forEach.call( select.options, function ( option ) {
			if ( ! option.value ) {
				return;
			}
			var name = option.textContent.trim();
			var state = optionState( attributeName, option.value );
			var hex = ( cfg.swatches || {} )[ name.toLowerCase() ];
			var button = el( 'button', 'qr-swatch' + ( hex ? '' : ' qr-swatch--text' ) );
			button.type = 'button';
			button.setAttribute( 'role', 'radio' );
			button.setAttribute( 'aria-checked', 'false' );
			button.setAttribute( 'data-value', option.value );
			button.setAttribute( 'aria-label', 'backorder' === state ? name + ', ' + i18n.backorder : name );
			button.title = name;
			if ( hex ) {
				button.style.setProperty( '--qr-swatch', hex );
			} else {
				button.textContent = name;
			}
			if ( 'backorder' === state ) {
				button.classList.add( 'is-backorder' );
				hasFaded = true;
			}
			if ( 'out-of-stock' === state ) {
				button.disabled = true;
			}
			button.addEventListener( 'click', function () {
				select.value = option.value;
				if ( $ ) {
					$( select ).trigger( 'change' );
				} else {
					select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				}
			} );
			group.appendChild( button );
		} );

		var chosen = el( 'span', 'qr-swatches__chosen' );
		if ( label ) {
			label.appendChild( chosen );
		}

		function sync() {
			var current = select.options[ select.selectedIndex ];
			chosen.textContent = current && current.value ? ': ' + current.textContent.trim() : '';
			group.querySelectorAll( '.qr-swatch' ).forEach( function ( button ) {
				var on = button.getAttribute( 'data-value' ) === select.value;
				button.setAttribute( 'aria-checked', on ? 'true' : 'false' );
				button.tabIndex = on || ( ! select.value && button === group.firstElementChild ) ? 0 : -1;
			} );
		}

		// Arrow keys move between options (radio group pattern).
		group.addEventListener( 'keydown', function ( event ) {
			var keys = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };
			if ( ! keys[ event.key ] ) {
				return;
			}
			event.preventDefault();
			var buttons = Array.prototype.filter.call( group.querySelectorAll( '.qr-swatch' ), function ( b ) {
				return ! b.disabled;
			} );
			var index = buttons.indexOf( document.activeElement );
			var next = buttons[ ( index + keys[ event.key ] + buttons.length ) % buttons.length ];
			if ( next ) {
				next.focus();
				next.click();
			}
		} );

		select.classList.add( 'qr-visually-hidden' );
		select.setAttribute( 'tabindex', '-1' );
		select.setAttribute( 'aria-hidden', 'true' );
		select.parentNode.insertBefore( group, select );
		if ( hasFaded ) {
			group.parentNode.insertBefore( el( 'p', 'qr-swatches__note', i18n.fadedNote ), group.nextSibling );
		}
		select.addEventListener( 'change', sync );
		if ( $ ) {
			$( select ).on( 'change', sync );
		}
		sync();
	}

	if ( variationForm ) {
		variationForm.querySelectorAll( 'select[name^="attribute_"]' ).forEach( buildSwatches );
	}

	var stockBox = document.querySelector( '[data-qr-variation-stock]' );
	var addButton = document.querySelector( 'form.cart .single_add_to_cart_button' );
	var addLabel = addButton ? addButton.textContent : '';

	function chosenOptionText() {
		var names = [];
		if ( variationForm ) {
			variationForm.querySelectorAll( 'select[name^="attribute_"]' ).forEach( function ( select ) {
				var option = select.options[ select.selectedIndex ];
				if ( option && option.value ) {
					names.push( option.textContent.trim() );
				}
			} );
		}
		return names.join( ', ' );
	}

	function showVariationStock( variation ) {
		if ( ! stockBox ) {
			return;
		}
		stockBox.innerHTML = '';
		if ( addButton ) {
			addButton.textContent = variation && 'backorder' === variation.qr_stock ? i18n.backorderNow : addLabel;
		}
		if ( ! variation ) {
			return;
		}
		if ( 'backorder' === variation.qr_stock ) {
			// Server-rendered and escaped (qr_minimal_store_backorder_panel_html).
			stockBox.innerHTML = variation.qr_panel;
			return;
		}
		var line = el( 'p', 'qr-stock' );
		var text = el( 'span' );
		if ( 'out-of-stock' === variation.qr_stock ) {
			line.classList.add( 'qr-stock--out' );
			text.appendChild( el( 'strong', '', i18n.outOfStock ) );
		} else {
			var option = chosenOptionText();
			line.classList.add( 'qr-stock--in' );
			text.appendChild( el( 'strong', '', option ? format( i18n.inStockIn, option ) : i18n.inStock ) );
			if ( 'low-stock' === variation.qr_stock && variation.qr_qty ) {
				text.appendChild( document.createTextNode( ', ' + format( i18n.onlyLeft, variation.qr_qty ) ) );
			}
		}
		line.appendChild( text );
		stockBox.appendChild( line );
	}

	if ( variationForm && $ ) {
		$( variationForm )
			.on( 'found_variation', function ( event, variation ) {
				showVariationStock( variation );
			} )
			.on( 'reset_data hide_variation', function () {
				showVariationStock( null );
			} );
	}

	/* ---------- Add to cart through the Store API, then the drawer ---------- */

	var drawer = document.getElementById( 'qr-cart-drawer' );
	var form = document.querySelector( 'form.cart' );
	if ( ! drawer || ! form || ! window.fetch || ! cfg.storeApi ) {
		return;
	}
	var drawerBody = drawer.querySelector( '[data-qr-drawer-body]' );
	var drawerTotal = drawer.querySelector( '[data-qr-drawer-total]' );
	var lastFocus = null;
	var nonce = null;

	function money( minorAmount, totals ) {
		var minor = parseInt( totals.currency_minor_unit, 10 );
		minor = isNaN( minor ) ? 2 : minor;
		var amount = ( parseInt( minorAmount, 10 ) || 0 ) / Math.pow( 10, minor );
		var parts = amount.toFixed( minor ).split( '.' );
		var whole = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, totals.currency_thousand_separator || '' );
		var text = ( totals.currency_prefix || '' ) + whole + ( minor > 0 ? ( totals.currency_decimal_separator || '.' ) + parts[ 1 ] : '' ) + ( totals.currency_suffix || '' );
		return text.replace( / /g, ' ' );
	}

	function wholeMoney( amount, totals ) {
		var minor = parseInt( totals.currency_minor_unit, 10 );
		var text = money( Math.round( amount * Math.pow( 10, isNaN( minor ) ? 2 : minor ) ), totals );
		return Math.round( amount ) === amount ? text.replace( /[.,]0+(?=\D*$)/, '' ) : text;
	}

	function itemsSubtotal( cart ) {
		var value = parseInt( cart.totals.total_items, 10 ) || 0;
		if ( cfg.pricesIncludeTax ) {
			value += parseInt( cart.totals.total_items_tax, 10 ) || 0;
		}
		return value;
	}

	function api( path, options ) {
		options = options || {};
		var headers = { 'Content-Type': 'application/json' };
		if ( nonce ) {
			headers.Nonce = nonce;
		}
		return fetch( cfg.storeApi + path, {
			method: options.method || 'GET',
			credentials: 'same-origin',
			headers: headers,
			body: options.body ? JSON.stringify( options.body ) : undefined,
		} ).then( function ( res ) {
			nonce = res.headers.get( 'Nonce' ) || nonce;
			return res.json().then( function ( json ) {
				if ( ! res.ok ) {
					throw new Error( ( json && json.message ) || i18n.error );
				}
				return json;
			} );
		} );
	}

	function addItem( body ) {
		var ready = nonce ? Promise.resolve() : api( 'cart' );
		return ready.then( function () {
			return api( 'cart/add-item', { method: 'POST', body: body } );
		} );
	}

	function updateCartCount( cart ) {
		document.querySelectorAll( '.cart-count' ).forEach( function ( node ) {
			node.textContent = String( cart.items_count );
		} );
	}

	function freeDeliveryNote( cart ) {
		var rule = cfg.freeDelivery;
		if ( ! rule ) {
			return null;
		}
		var minor = parseInt( cart.totals.currency_minor_unit, 10 );
		var subtotal = itemsSubtotal( cart ) / Math.pow( 10, isNaN( minor ) ? 2 : minor );
		var left = Math.round( ( rule.threshold - subtotal ) * 100 ) / 100;
		var note = el( 'p', 'qr-drawer__delivery' );
		if ( left <= 0 ) {
			note.classList.add( 'is-unlocked' );
			note.appendChild( el( 'strong', '', i18n.unlocked ) );
			note.appendChild( document.createTextNode( ' ' + format( i18n.unlockedNote, wholeMoney( rule.threshold, cart.totals ) ) ) );
		} else {
			note.appendChild( el( 'strong', '', format( i18n.addMore, money( Math.round( left * 100 ), cart.totals ) ) ) );
		}
		return note;
	}

	function suggestionData( cart ) {
		var node = document.getElementById( 'qr-upsell-suggestion' );
		if ( ! node ) {
			return null;
		}
		var data;
		try {
			data = JSON.parse( node.textContent );
		} catch ( e ) {
			return null;
		}
		var inCart = cart.items.some( function ( item ) {
			return item.id === data.id;
		} );
		return inCart ? null : data;
	}

	function renderSuggestion( cart ) {
		var data = suggestionData( cart );
		if ( ! data ) {
			return null;
		}
		var wrap = el( 'div', 'qr-drawer__suggest' );
		wrap.appendChild( el( 'p', 'qr-drawer__eyebrow', data.heading || i18n.goesWith ) );
		var card = el( 'div', 'qr-drawer__card' );
		var img = el( 'img' );
		img.src = data.image;
		img.alt = data.name;
		img.width = 60;
		img.height = 60;
		card.appendChild( img );
		var text = el( 'div', 'qr-drawer__card-body' );
		var link = el( 'a', 'qr-drawer__card-name', data.name );
		link.href = data.url;
		text.appendChild( link );
		text.appendChild( el( 'span', 'qr-drawer__card-meta', data.meta ) );
		card.appendChild( text );
		var add = el( 'button', 'qr-drawer__card-add', i18n.add );
		add.type = 'button';
		add.setAttribute( 'aria-label', i18n.add + ' ' + data.name );
		add.addEventListener( 'click', function () {
			add.disabled = true;
			addItem( { id: data.id, quantity: 1 } )
				.then( function ( next ) {
					add.textContent = i18n.added;
					renderDrawer( next, null, true );
				} )
				.catch( function ( error ) {
					add.disabled = false;
					window.alert( error.message || i18n.error );
				} );
		} );
		card.appendChild( add );
		wrap.appendChild( card );
		return wrap;
	}

	var lastAdded = null;

	function renderDrawer( cart, addedId, keepSuggestion ) {
		if ( addedId ) {
			lastAdded = addedId;
		}
		var item = cart.items.filter( function ( row ) {
			return row.id === lastAdded;
		} )[ 0 ];
		var suggestion = keepSuggestion ? drawerBody.querySelector( '.qr-drawer__suggest' ) : null;
		drawerBody.textContent = '';

		if ( item ) {
			var row = el( 'div', 'qr-drawer__item' );
			if ( item.images && item.images[ 0 ] ) {
				var img = el( 'img' );
				img.src = item.images[ 0 ].thumbnail;
				img.alt = item.images[ 0 ].alt || item.name;
				img.width = 72;
				img.height = 72;
				row.appendChild( img );
			}
			var text = el( 'div', 'qr-drawer__item-body' );
			text.appendChild( el( 'p', 'qr-drawer__item-name', item.name ) );
			var variation = ( item.variation || [] ).map( function ( v ) {
				return v.value;
			} ).join( ', ' );
			if ( variation ) {
				text.appendChild( el( 'p', 'qr-drawer__item-meta', variation ) );
			}
			var line = parseInt( item.totals.line_subtotal, 10 ) || 0;
			if ( cfg.pricesIncludeTax ) {
				line += parseInt( item.totals.line_subtotal_tax, 10 ) || 0;
			}
			text.appendChild( el( 'p', 'qr-drawer__item-meta', format( i18n.qty, item.quantity, money( line, item.totals ) ) ) );
			row.appendChild( text );
			drawerBody.appendChild( row );
		}

		var note = freeDeliveryNote( cart );
		if ( note ) {
			drawerBody.appendChild( note );
		}
		var next = suggestion || renderSuggestion( cart );
		if ( next ) {
			drawerBody.appendChild( next );
		}
		drawerTotal.textContent = money( itemsSubtotal( cart ), cart.totals );
		updateCartCount( cart );
	}

	function openDrawer() {
		lastFocus = document.activeElement;
		drawer.hidden = false;
		document.documentElement.classList.add( 'qr-drawer-open' );
		drawer.querySelector( '.qr-drawer__panel' ).focus();
	}

	function closeDrawer() {
		drawer.hidden = true;
		document.documentElement.classList.remove( 'qr-drawer-open' );
		if ( lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}
	}

	drawer.querySelectorAll( '[data-qr-drawer-close]' ).forEach( function ( node ) {
		node.addEventListener( 'click', closeDrawer );
	} );
	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && ! drawer.hidden ) {
			closeDrawer();
		}
		// Keep Tab inside the open drawer.
		if ( 'Tab' === event.key && ! drawer.hidden ) {
			var focusable = drawer.querySelectorAll( 'a[href], button:not([disabled])' );
			var first = focusable[ 0 ];
			var last = focusable[ focusable.length - 1 ];
			if ( event.shiftKey && ( document.activeElement === first || ! drawer.contains( document.activeElement ) ) ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		}
	} );

	function requestFromForm() {
		var qtyInput = form.querySelector( 'input.qty' );
		var quantity = qtyInput ? Math.max( 1, parseInt( qtyInput.value, 10 ) || 1 ) : 1;
		if ( form.classList.contains( 'variations_form' ) ) {
			var variationInput = form.querySelector( 'input[name="variation_id"]' );
			var variationId = variationInput ? parseInt( variationInput.value, 10 ) : 0;
			if ( ! variationId ) {
				return null;
			}
			var attributes = [];
			form.querySelectorAll( 'select[name^="attribute_"]' ).forEach( function ( select ) {
				attributes.push( { attribute: ( select.getAttribute( 'data-attribute_name' ) || select.name ).replace( /^attribute_/, '' ), value: select.value } );
			} );
			return { id: variationId, quantity: quantity, variation: attributes };
		}
		var button = form.querySelector( '[name="add-to-cart"]' );
		if ( ! button || form.querySelector( '.group_table' ) ) {
			return null;
		}
		return { id: parseInt( button.value, 10 ), quantity: quantity };
	}

	form.addEventListener( 'submit', function ( event ) {
		var request = requestFromForm();
		// Grouped products, missing options etc. keep the normal WooCommerce flow.
		if ( ! request || ! request.id || ( addButton && addButton.classList.contains( 'disabled' ) ) ) {
			return;
		}
		event.preventDefault();
		var errorNode = form.querySelector( '.qr-atc-error' );
		if ( errorNode ) {
			errorNode.remove();
		}
		if ( addButton ) {
			addButton.disabled = true;
			addButton.classList.add( 'loading' );
		}

		var extras = Array.prototype.map.call( document.querySelectorAll( '[data-qr-goes-with]:checked' ), function ( box ) {
			return parseInt( box.value, 10 );
		} );

		addItem( request )
			.then( function ( cart ) {
				return extras.reduce( function ( chain, id ) {
					return chain.then( function ( current ) {
						return addItem( { id: id, quantity: 1 } ).catch( function () {
							return current;
						} );
					} );
				}, Promise.resolve( cart ) );
			} )
			.then( function ( cart ) {
				document.querySelectorAll( '[data-qr-goes-with]:checked' ).forEach( function ( box ) {
					box.checked = false;
				} );
				renderDrawer( cart, request.id );
				openDrawer();
			} )
			.catch( function ( error ) {
				var message = el( 'p', 'qr-atc-error', error.message || i18n.error );
				message.setAttribute( 'role', 'alert' );
				form.appendChild( message );
			} )
			.then( function () {
				if ( addButton ) {
					addButton.disabled = false;
					addButton.classList.remove( 'loading' );
				}
			} );
	} );
} )();
