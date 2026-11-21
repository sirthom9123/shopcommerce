=== QR Store — Meta Pixel ===
Contributors: yehudasolutions
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPLv2 or later

Facebook / Meta Pixel for WooCommerce standard events.

== Changelog ==

= 1.2.0 =
* content_ids now use the Facebook catalog feed id (wc_post_id_<ID>, filter qr_meta_pixel_content_id) so ViewContent, AddToCart, InitiateCheckout and Purchase match catalog items.

= 1.1.0 =
* Waits for QR Analytics marketing consent (loader.js).
