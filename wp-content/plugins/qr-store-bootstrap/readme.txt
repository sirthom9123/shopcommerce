=== QR Store Bootstrap ===
Stable tag: 1.2.2
License: GPL-2.0-or-later

Foundation bootstrap for the store: core pages, WooCommerce options, legal pages, and Google Merchant Center feed fixes.

== Changelog ==

= 1.2.2 =
* Store, orders and returns contact addresses now use y-store.co.za. Legal pages rebuild (legal version 1.0.12).

= 1.2.1 =
* Google for WooCommerce: clears the cached Merchant Center claimed-URL hash when it no longer matches the site URL (left over from the old shop.yehudasolutions.com domain). It made Google for WooCommerce skip every product sync since the domain change.

= 1.2.0 =
* Google for WooCommerce: backorder products now send availabilityDate (supplier ETA `_supplier_eta` + 3 days, or today + 14 days when there is no future ETA). Fixes "Availability date is required".
* New uploads (including images sideloaded by the WooCommerce REST API / store_sync) get plain-ASCII file names (en dash, degree sign etc. removed). Fixes "Invalid image file name".
* Tools > Media file names: lists existing non-ASCII media files and renames them (all sizes), then re-saves the products that use them so Google re-syncs.

= 1.1.2 =
* Legal pages content.
