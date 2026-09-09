(function ($) {
	function trackAddToCart(params) {
		if (typeof fbq !== 'function') {
			return;
		}
		fbq('track', 'AddToCart', params || {});
	}

	function payloadFromButton($button) {
		var id = '';
		var qty = 1;
		if ($button && $button.length) {
			id = String($button.data('product_id') || $button.attr('data-product_id') || '');
			qty = parseFloat($button.data('quantity') || $button.attr('data-quantity') || 1);
		}
		if (!isFinite(qty) || qty < 1) {
			qty = 1;
		}
		var params = {
			content_type: 'product',
			currency: (window.qrMetaPixel && qrMetaPixel.currency) || 'ZAR'
		};
		if (id) {
			params.content_ids = [id];
			params.contents = [{ id: id, quantity: qty }];
		}
		return params;
	}

	$(document.body).on('added_to_cart', function (e, fragments, hash, $button) {
		trackAddToCart(payloadFromButton($button));
	});

	document.body.addEventListener('wc-blocks_added_to_cart', function () {
		trackAddToCart({
			content_type: 'product',
			currency: (window.qrMetaPixel && qrMetaPixel.currency) || 'ZAR'
		});
	});
})(jQuery);
