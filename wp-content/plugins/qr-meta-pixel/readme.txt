=== QR Store — Meta Pixel ===
Contributors: yehudasolutions
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.3.0
License: GPLv2 or later

Facebook / Meta Pixel for WooCommerce standard events.

== Changelog ==

= 1.3.0 =
* Meta Conversions API: AddToCart (after redirect), InitiateCheckout and Purchase are also sent server-to-server when QR_META_ACCESS_TOKEN is defined and the visitor accepted marketing. The browser event carries the same eventID so Meta deduplicates. Buyer details are SHA-256 hashed; _fbp/_fbc, IP and user agent are passed for matching and not stored. Optional QR_META_TEST_EVENT_CODE sends to Events Manager Test events.

= 1.2.0 =
* content_ids now use the Facebook catalog feed id (wc_post_id_<ID>, filter qr_meta_pixel_content_id) so ViewContent, AddToCart, InitiateCheckout and Purchase match catalog items.

= 1.1.0 =
* Waits for QR Analytics marketing consent (loader.js).
