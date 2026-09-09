<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public contact mailboxes.
 *
 * @return array<string, string>
 */
function qr_store_contact_emails() {
	return array(
		'store'   => 'store@yehudasolutions.com',
		'orders'  => 'orders@shop.yehudasolutions.com',
		'returns' => 'returns@shop.yehudasolutions.com',
		'officer' => 'tshiamom@yehudasolutions.com',
	);
}

/**
 * @param string $kind store|orders|returns|officer
 * @return string
 */
function qr_store_contact_email( $kind = 'store' ) {
	$emails = qr_store_contact_emails();
	return isset( $emails[ $kind ] ) ? $emails[ $kind ] : $emails['store'];
}

/**
 * @param string $kind store|orders|returns|officer
 * @return string
 */
function qr_store_legal_mailto( $kind = 'store' ) {
	$email = qr_store_contact_email( $kind );
	return '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>';
}

/**
 * Shared tokens for legal HTML.
 *
 * @return array<string, string>
 */
function qr_store_legal_context() {
	$terms_id   = (int) get_option( 'woocommerce_terms_page_id' );
	$returns_id = (int) get_option( 'woocommerce_refund_returns_page_id' );
	$privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );

	$terms_url   = $terms_id ? get_permalink( $terms_id ) : home_url( '/platform-terms/' );
	$returns_url = $returns_id ? get_permalink( $returns_id ) : home_url( '/returns-policy/' );
	$privacy_url = $privacy_id ? get_permalink( $privacy_id ) : home_url( '/privacy-policy/' );

	return array(
		'store'        => esc_html( get_bloginfo( 'name' ) ),
		'operator'     => 'Yehuda Solutions',
		'store_html'   => qr_store_legal_mailto( 'store' ),
		'orders_html'  => qr_store_legal_mailto( 'orders' ),
		'returns_html' => qr_store_legal_mailto( 'returns' ),
		'officer_html' => qr_store_legal_mailto( 'officer' ),
		'region'       => 'Gauteng, South Africa',
		'effective'    => '8 September 2026',
		'terms_url'    => esc_url( $terms_url ),
		'returns_url'  => esc_url( $returns_url ),
		'privacy_url'  => esc_url( $privacy_url ),
	);
}

/**
 * @param array<string, string> $ctx Context tokens.
 */
function qr_store_legal_crosslinks( $ctx, $current ) {
	$links = array(
		'terms'   => array( 'Platform Terms', $ctx['terms_url'] ),
		'returns' => array( 'Returns Policy', $ctx['returns_url'] ),
		'privacy' => array( 'Privacy Policy', $ctx['privacy_url'] ),
	);

	$html = '<nav class="legal-crosslinks" aria-label="Other legal documents"><p>';
	$parts = array();
	foreach ( $links as $key => $item ) {
		if ( $key === $current ) {
			$parts[] = '<strong>' . esc_html( $item[0] ) . '</strong>';
			continue;
		}
		$parts[] = '<a href="' . $item[1] . '">' . esc_html( $item[0] ) . '</a>';
	}
	$html .= implode( ' <span aria-hidden="true">·</span> ', $parts );
	$html .= '</p></nav>';

	return $html;
}

/**
 * @param array<string, string> $ctx Context tokens.
 * @return string
 */
function qr_store_legal_html_terms( $ctx ) {
	$cross = qr_store_legal_crosslinks( $ctx, 'terms' );

	return <<<HTML
<p class="legal-meta">Effective {$ctx['effective']}</p>
<div class="legal-note">
<p>These Platform Terms apply when you use {$ctx['store']}, the online shop operated by {$ctx['operator']}. They do not take away rights you have under South African law, including the Consumer Protection Act 68 of 2008 (CPA), the Electronic Communications and Transactions Act 25 of 2002 (ECTA), and the Protection of Personal Information Act 4 of 2013 (POPIA).</p>
</div>
<h2>1. Who we are</h2>
<p>{$ctx['store']} is an online store for lifestyle gadgets, home electronics, and related accessories. We operate from {$ctx['region']}.</p>
<p>Contact us by topic: general store enquiries {$ctx['store_html']}; orders {$ctx['orders_html']}; returns {$ctx['returns_html']}.</p>
<h2>2. Agreement</h2>
<p>By browsing the Store, creating an account, or placing an order, you agree to these terms and to our <a href="{$ctx['privacy_url']}">Privacy Policy</a> and <a href="{$ctx['returns_url']}">Returns Policy</a>.</p>
<p>We may update these terms from time to time. The effective date above is the current version. Changes apply to orders placed after the updated terms are published, unless the law requires otherwise.</p>
<h2>3. Eligibility</h2>
<p>You should be 18 or older to place an order. If you are under 18, a parent or guardian must place the order and accept these terms on your behalf. You must provide accurate details at checkout and in your account.</p>
<h2>4. Accounts</h2>
<p>You can browse without an account. An account lets you track orders, save details, and manage newsletter preferences. You are responsible for keeping your login details confidential and for activity on your account. Tell us promptly if you think someone else has used it.</p>
<p>We may suspend or close an account where we reasonably suspect fraud, abuse, chargebacks in bad faith, or a legal risk to the Store or other customers.</p>
<h2>5. Products, pricing, and VAT</h2>
<p>Prices are shown in South African Rand (ZAR) and include VAT unless we clearly say otherwise. Product images are illustrative. Specifications come from manufacturers and suppliers; we take care to keep listings accurate, but we may correct obvious errors.</p>
<p>Stock and promotions are limited. If an item cannot be fulfilled after you order — for example because of a pricing error, supplier failure, or stock mismatch — we will let you know and refund any amount already paid for that item.</p>
<h2>6. Orders and when a sale is made</h2>
<p>When you submit an order you offer to buy the goods at the price and on the terms shown at checkout. A binding sale is made when we accept that order, usually by sending an order confirmation and receiving successful payment.</p>
<p>We may decline or cancel an order (and refund you) if payment fails, the goods are unavailable, we cannot deliver to your address, or we reasonably suspect fraud or unauthorised use.</p>
<h2>7. Payment</h2>
<p>You pay through the methods shown at checkout. Card and Instant EFT payments are processed by PayFast, a South African payment service provider. We do not store your full card number on our servers. Until payment clears, we may withhold dispatch.</p>
<h2>8. Delivery</h2>
<p>We deliver within South Africa. Delivery is <strong>R250 including VAT</strong> on orders under R2&nbsp;000, and <strong>free</strong> on orders of R2&nbsp;000 or more (based on the cart subtotal shown at checkout). Typical delivery is <strong>2–3 working days</strong> after dispatch, depending on your area and the courier.</p>
<p>Working days exclude weekends and South African public holidays. Times are estimates, not guarantees. Events outside our reasonable control — including courier disruption, severe weather, or load-shedding affecting depots — can delay delivery.</p>
<p>You must provide a complete, deliverable address. Risk in the goods passes when they are delivered to that address (or collected, if collection is arranged). Please check the parcel on arrival and tell us promptly if it arrived damaged or incomplete.</p>
<h2>9. Returns, refunds, and quality</h2>
<p>Change-of-mind returns, defective goods, and the CPA implied warranty are explained in the <a href="{$ctx['returns_url']}">Returns Policy</a>. Nothing in these terms limits a right you have under the CPA or ECTA.</p>
<h2>10. Acceptable use</h2>
<p>You may not use the Store to:</p>
<ul>
<li>place orders with false identity or payment details;</li>
<li>interfere with the website, other customers, or our systems;</li>
<li>scrape or harvest data except as allowed by law or with our written permission;</li>
<li>post reviews or other content that is unlawful, defamatory, misleading, or infringes someone else’s rights.</li>
</ul>
<h2>11. Reviews and other content you submit</h2>
<p>If you submit a product review or similar content, you grant {$ctx['operator']} a non-exclusive licence to display and use it in connection with the Store. We may refuse or remove content that breaks these terms.</p>
<h2>12. Intellectual property</h2>
<p>The Store’s branding, layout, catalogue copy, and photographs are owned by us or our licensors. You may not copy them for commercial use without permission. Manufacturer names and product marks belong to their owners.</p>
<h2>13. Liability</h2>
<p>We supply consumer goods and must meet the quality standards in the CPA. Nothing in these terms excludes or limits liability where South African law does not allow it — including for gross negligence, fraud, or a CPA quality failure we are required to remedy.</p>
<p>Subject to that: we are not liable for loss of profit, loss of data, or other indirect loss; and we are not responsible for delays or failures of PayFast, couriers, or networks that we do not control. Where we are liable for a product and the law allows a limit, that liability is limited to the amount you paid for the affected product.</p>
<h2>14. Events beyond our control</h2>
<p>We are not in breach of these terms for delay or failure caused by events beyond our reasonable control, provided we take reasonable steps to limit the impact and keep you informed where practical.</p>
<h2>15. Privacy</h2>
<p>How we collect and use personal information is set out in the <a href="{$ctx['privacy_url']}">Privacy Policy</a>.</p>
<h2>16. Governing law and complaints</h2>
<p>These terms are governed by the law of the Republic of South Africa. South African courts have jurisdiction, without limiting your right to use consumer dispute processes that the law makes available, including the Consumer Goods and Services Ombud and the National Consumer Commission where they apply.</p>
<h2>17. Contact</h2>
<p>Questions about these terms or the Store generally: {$ctx['store_html']}.</p>
<p>Questions about an order you have placed: {$ctx['orders_html']}.</p>
<p>Returns and warranty queries: {$ctx['returns_html']}.</p>
{$cross}
HTML;
}

/**
 * @param array<string, string> $ctx Context tokens.
 * @return string
 */
function qr_store_legal_html_returns( $ctx ) {
	$cross = qr_store_legal_crosslinks( $ctx, 'returns' );

	return <<<HTML
<p class="legal-meta">Effective {$ctx['effective']}</p>
<div class="legal-note">
<p>This policy explains how {$ctx['store']} handles change-of-mind returns and goods that are defective or not as described. It does not reduce your rights under the CPA (including the six-month implied warranty of quality) or, where it applies, the ECTA cooling-off right for electronic transactions.</p>
</div>
<h2>1. Summary</h2>
<ul>
<li><strong>Change of mind:</strong> unused items may be returned within <strong>30 calendar days</strong> of delivery, in original condition, as described below.</li>
<li><strong>Faulty, unsafe, or not as described:</strong> we will repair, replace, or refund as the CPA requires. We cover reasonable return of those goods.</li>
<li>Start every return by emailing {$ctx['returns_html']} with your order number. Do not post goods back until we confirm how to send them.</li>
</ul>
<h2>2. Change-of-mind returns (30 days)</h2>
<p>If you simply change your mind, you may return unused goods within 30 calendar days of the delivery date, provided:</p>
<ul>
<li>the item is unused and in sellable condition;</li>
<li>original packaging, manuals, and accessories are included;</li>
<li>factory seals are intact where the product was sold sealed;</li>
<li>you have proof of purchase from {$ctx['store']} (order number or invoice).</li>
</ul>
<p>We inspect returns when they arrive. Once accepted, we refund the product price to the original payment method. Inspection and refund typically complete within 5–10 working days after we receive the parcel, plus any time PayFast needs to reverse a card or Instant EFT payment.</p>
<h3>Return shipping on change of mind</h3>
<p>Unless we agree otherwise, you cover the cost of sending the goods back. The original delivery fee is not refunded on a change-of-mind return, except where the whole order is cancelled before dispatch.</p>
<h3>What we cannot take back as a change of mind</h3>
<ul>
<li>Goods that have been used, installed, or damaged while with you.</li>
<li>Opened grooming, hygiene, or personal-care items.</li>
<li>Software, digital content, or sealed accessories once the seal is broken.</li>
<li>Items made or altered to your specification.</li>
<li>Incomplete returns (missing parts, chargers, or original packaging where it is needed to resell the item).</li>
</ul>
<p>Where ECTA gives you a seven-day cooling-off right for an electronic transaction, you may still use that right even if a product sits in the list above only as a store change-of-mind exception — unless ECTA itself excludes that category. Under ECTA the consumer generally pays the cost of returning the goods.</p>
<h2>3. Defective, damaged, or incorrect goods</h2>
<p>Tell us as soon as you reasonably can if goods arrive damaged, incomplete, different from the listing, or fail in ordinary use.</p>
<p>Under the CPA, goods must be of good quality, durable, and reasonably fit for their intended purpose. If they are not, and the failure is not caused by misuse, you may return them within six months of delivery. We will then, as the CPA requires, <strong>repair or replace</strong> the goods or <strong>refund</strong> you. If a repair fails or is not done in a reasonable time, you may choose a replacement or refund.</p>
<p>For these returns we will arrange collection or refund reasonable return courier costs. Please keep the goods and packaging so we can inspect them. Do not keep using a product that appears unsafe.</p>
<h2>4. How to start a return</h2>
<ol>
<li>Email {$ctx['returns_html']} with your order number, the product, and the reason.</li>
<li>Attach clear photos if the item is damaged, incomplete, or defective.</li>
<li>Wait for our written return instruction (how to pack, where to send, or whether we will collect).</li>
<li>Pack the goods securely. Include a note with your order number.</li>
</ol>
<p>Parcels sent without a return instruction may be delayed or returned to you because we cannot match them to an order.</p>
<h2>5. Refunds and exchanges</h2>
<p>Refunds go back to the original payment method. We do not offer cash refunds. Store credit is only used if you ask for it and we agree in writing.</p>
<p>Exchanges depend on stock. If a replacement is unavailable, we will refund the returned item.</p>
<p>If we originally charged delivery and the return is for our error or a CPA quality failure, we also refund that delivery charge for the affected goods.</p>
<h2>6. Manufacturer warranties</h2>
<p>Many products carry a manufacturer warranty in addition to your CPA rights. Warranty terms are those of the manufacturer. We will help you open a warranty claim where we reasonably can. A manufacturer warranty never replaces the CPA implied warranty.</p>
<h2>7. Delivery times and failed delivery</h2>
<p>Delivery estimates are in the <a href="{$ctx['terms_url']}">Platform Terms</a>. If a courier cannot deliver because of an incomplete address or nobody is available after reasonable attempts, extra delivery attempts or returns to depot may delay the order. If a parcel is marked delivered but you did not receive it, email {$ctx['orders_html']} — we will raise a trace with the courier.</p>
<h2>8. Contact</h2>
<p>Returns and warranty queries: {$ctx['returns_html']}.</p>
<p>Questions about an existing order (status, delivery, missing parcel): {$ctx['orders_html']}.</p>
{$cross}
HTML;
}

/**
 * @param array<string, string> $ctx Context tokens.
 * @return string
 */
function qr_store_legal_html_privacy( $ctx ) {
	$cross = qr_store_legal_crosslinks( $ctx, 'privacy' );

	return <<<HTML
<p class="legal-meta">Effective {$ctx['effective']}</p>
<div class="legal-note">
<p>This Privacy Policy explains how {$ctx['operator']} (trading as {$ctx['store']}) collects, uses, and shares personal information when you use the Store. We process information in line with the Protection of Personal Information Act 4 of 2013 (POPIA).</p>
</div>
<h2>1. Who is responsible</h2>
<p>The responsible party is <strong>{$ctx['operator']}</strong>, trading as <strong>{$ctx['store']}</strong>, operating from {$ctx['region']}.</p>
<p>Information Officer contact: {$ctx['officer_html']}.</p>
<h2>2. Personal information we collect</h2>
<p>Depending on how you use the Store, we may process:</p>
<ul>
<li><strong>Identity and contact:</strong> name, email address, phone number, billing and shipping address.</li>
<li><strong>Account:</strong> login email, password (stored as a hash, not in plain text), order history, newsletter preference.</li>
<li><strong>Orders:</strong> products, quantities, amounts, payment status, delivery notes. We do not store full card numbers.</li>
<li><strong>Support:</strong> messages you send to {$ctx['store_html']}, {$ctx['orders_html']}, or {$ctx['returns_html']}, or via WhatsApp if you use it.</li>
<li><strong>Marketing:</strong> newsletter email address and confirmation status (we use double opt-in).</li>
<li><strong>Shopping behaviour on our site:</strong> cart contents, including snapshots used to remind you about an unfinished checkout if we have your email.</li>
<li><strong>Reviews and alerts:</strong> review text and the email used for back-in-stock notices.</li>
<li><strong>Technical:</strong> IP address, browser type, device, and cookies or similar storage needed for sessions, login, and checkout.</li>
</ul>
<h2>3. How we collect it</h2>
<ul>
<li>Directly from you — account, checkout, newsletter, reviews, support.</li>
<li>Automatically — cookies and server logs when you browse, add to cart, or check out.</li>
<li>From service providers — payment confirmation from PayFast; delivery scans from couriers.</li>
</ul>
<h2>4. Why we use it (and on what basis)</h2>
<table class="legal-table">
<thead>
<tr><th>Purpose</th><th>POPIA / contractual basis</th></tr>
</thead>
<tbody>
<tr><td>Create and manage your account, take orders, deliver goods, handle returns, send order emails</td><td>To perform our contract with you, and as required to run the sale</td></tr>
<tr><td>Take payment and prevent fraud</td><td>Contract, and our legitimate interest in protecting the Store and customers</td></tr>
<tr><td>Tax, accounting, and consumer-law records</td><td>Legal obligation</td></tr>
<tr><td>Abandoned-cart reminders and service messages about an order you started</td><td>Legitimate interest in completing a purchase you began, in a way that is reasonable and not excessive</td></tr>
<tr><td>Newsletter and marketing emails</td><td>Consent (double opt-in). You can unsubscribe at any time</td></tr>
<tr><td>Product reviews and stock alerts you request</td><td>Consent and/or steps you ask us to take</td></tr>
<tr><td>Keep the website secure and working (login, cart, checkout)</td><td>Legitimate interest in operating a secure store</td></tr>
</tbody>
</table>
<p>We do not sell personal information.</p>
<h2>5. Who we share it with</h2>
<p>We share information only as needed to run the Store:</p>
<ul>
<li><strong>PayFast</strong> — to process card and Instant EFT payments. PayFast is the payment operator and has its own privacy notice.</li>
<li><strong>Courier partners</strong> — name, phone, and delivery address so they can deliver your order.</li>
<li><strong>Email delivery provider</strong> — to send account, order, newsletter, and support mail.</li>
<li><strong>Hosting and security providers</strong> — to host the website and protect it.</li>
<li><strong>Meta Platforms (Facebook / Instagram)</strong> — advertising measurement via the Meta Pixel, so we can understand which ads led to visits and purchases. Meta processes this information under its own terms and may process it outside South Africa.</li>
<li><strong>Professional advisers or authorities</strong> — where the law requires it, or to protect our legal rights.</li>
</ul>
<p>These parties act as operators (processors) or, in PayFast’s case for payment data, as a responsible party for the information they collect to complete the payment. We require operators to use the information only for the agreed purpose.</p>
<h2>6. Cookies and similar technologies</h2>
<p>The Store uses cookies and similar storage that are required for it to work:</p>
<ul>
<li>session and cart cookies so items stay in your basket;</li>
<li>account login and security cookies;</li>
<li>checkout cookies so we can complete your order.</li>
</ul>
<p>We also use the <strong>Meta Pixel</strong> (Facebook / Instagram ads). It sets cookies and similar identifiers to measure visits, product views, add-to-cart, checkout starts, and purchases, and to help us show relevant ads. You can block third-party cookies in your browser; essential store functions such as cart and checkout may then fail, and ad measurement may be incomplete.</p>
<h2>7. Marketing</h2>
<p>We only send marketing newsletters after you confirm your email (double opt-in). Every newsletter includes an unsubscribe link. Transactional messages — order confirmation, shipping, password reset, return updates — are not marketing and are sent because they are part of the service.</p>
<h2>8. How long we keep information</h2>
<ul>
<li><strong>Orders, invoices, and tax records:</strong> for as long as South African tax and company law require (generally at least five years).</li>
<li><strong>Accounts:</strong> while the account is open, and for a reasonable period afterwards if needed for records or disputes.</li>
<li><strong>Newsletter:</strong> until you unsubscribe or we delete inactive lists.</li>
<li><strong>Abandoned carts:</strong> for a limited period so we can send a reminder, then they are removed or anonymised.</li>
<li><strong>Support mail:</strong> for as long as needed to resolve the query and keep a record of what was agreed.</li>
</ul>
<h2>9. Security</h2>
<p>We use HTTPS, hashed passwords, role-limited staff access, and a PCI-oriented payment flow through PayFast so that card details are not stored on our servers. No online store can guarantee absolute security; please use a strong unique password.</p>
<h2>10. Cross-border processing</h2>
<p>Some operators (for example email or hosting) may process information outside South Africa. Where that happens we take reasonable steps so the information remains protected, as POPIA requires.</p>
<h2>11. Children</h2>
<p>The Store is intended for adults. We do not knowingly collect personal information from children. If you believe we have, email us and we will delete it where we are not legally required to keep it.</p>
<h2>12. Your rights</h2>
<p>Subject to POPIA and other law, you may:</p>
<ul>
<li>ask whether we hold personal information about you, and request a copy;</li>
<li>ask us to correct or update inaccurate information;</li>
<li>object to processing that is based on legitimate interests, including some marketing;</li>
<li>withdraw consent for the newsletter (unsubscribe);</li>
<li>ask us to delete information we no longer need — we may keep what the law requires, such as invoices.</li>
</ul>
<p>To use these rights, email {$ctx['officer_html']}. We may need to verify that the request comes from you.</p>
<p>You may also lodge a complaint with the <strong>Information Regulator (South Africa)</strong> at <a href="https://inforegulator.org.za/" rel="noopener noreferrer">inforegulator.org.za</a>.</p>
<h2>13. Changes</h2>
<p>We may update this policy. The effective date at the top shows the current version. Material changes will be published on this page.</p>
<h2>14. Related documents</h2>
<p>How we sell and deliver goods is described in the <a href="{$ctx['terms_url']}">Platform Terms</a>. Returns are described in the <a href="{$ctx['returns_url']}">Returns Policy</a>.</p>
<h2>15. Contact</h2>
<p>Privacy questions: {$ctx['officer_html']}.</p>
<p>Order queries: {$ctx['orders_html']}. Returns queries: {$ctx['returns_html']}.</p>
{$cross}
HTML;
}
