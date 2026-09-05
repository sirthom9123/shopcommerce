# QR Minimal Store — Light Theme Polishing Guide

> **Theme:** `qr-minimal-store` · **Repo:** `C:\xampp\htdocs\store` · **Reference:** [Electro Home v12](https://electro.madrasthemes.com/home-v12/)
>
> This guide maps every visual and structural gap between your current custom theme and the Electro v12 reference, organized into phases you can tackle in Cursor. Each phase is self-contained — complete one, test locally on `localhost/store/`, then move to the next.

---

## Current State Summary

Your theme already has solid structural bones:

- **Header:** utility bar → brand + nav + search + actions → category strip (3-row layout matching Electro's pattern)
- **Front page sections:** hero → category rail → benefits → hot products → featured products → tabbed shop highlights → trust strip → brands → footer
- **WooCommerce integration:** custom `content-product.php` card, sidebar removed, archive template, product gallery support
- **Responsive breakpoints:** 992px / 768px / 640px / 420px
- **CSS architecture:** BEM-style classes, CSS custom properties via `:root`, clean specificity

The visual layer has been updated to a clean white theme with blue accents. This guide now reflects that palette. Remaining work is structural additions and polish.

---

## Phase 1 — Light Colour Palette & Typography

**Goal:** Apply a clean white background with blue accent — easy on the eyes, modern feel.

**Priority:** Do this first — it changes the entire feel instantly.

### 1.1 Replace CSS Custom Properties

**File:** `assets/css/site.css` — `:root` block (lines 1–16)

Replace the current variables with the clean white palette:

```css
:root {
  /* Clean white palette */
  --qr-bg:            #ffffff;        /* clean white background */
  --qr-surface:       #f5f5f7;        /* light grey surface */
  --qr-surface-muted: #eeeef0;        /* subtle light variant */
  --qr-text:          #1d1d1f;        /* primary text — near black */
  --qr-muted:         #6e6e73;        /* secondary/muted text */
  --qr-border:        #d2d2d7;        /* light borders */
  --qr-primary:       #0855a1;        /* primary blue accent */
  --qr-primary-hover: #0077ed;        /* hover blue */
  --qr-primary-soft:  rgba(0, 113, 227, 0.08); /* subtle blue tint */
  --qr-danger:        #ef4444;        /* error/sale red */
  --qr-price:         #0855a1;        /* price matches primary blue */
  --qr-radius:        8px;            /* slightly tighter corners */
  --qr-radius-pill:   999px;
  --qr-gap:           1.25rem;
  --qr-shadow:        0 4px 12px rgba(0, 0, 0, 0.08);
}
```

**New variable added:** `--qr-price` — Prices use the same primary blue as the accent, keeping the palette unified.

### 1.2 Update Global Styles

**File:** `assets/css/site.css`

```css
body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  background: var(--qr-bg);
  color: var(--qr-text);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;  /* ADD: smooth text rendering */
}

a {
  color: var(--qr-primary);  /* blue links */
}

a:hover {
  color: var(--qr-primary-hover);
}
```

### 1.3 Update theme.json Palette

**File:** `theme.json` — replace the `palette` array:

```json
"palette": [
  { "slug": "background",    "name": "Background",    "color": "#ffffff" },
  { "slug": "surface",       "name": "Surface",       "color": "#f5f5f7" },
  { "slug": "foreground",    "name": "Foreground",    "color": "#1d1d1f" },
  { "slug": "muted",         "name": "Muted",         "color": "#6e6e73" },
  { "slug": "primary",       "name": "Electro Yellow","color": "#0855a1" },
  { "slug": "primary-hover", "name": "Light Blue",  "color": "#0077ed" },
  { "slug": "primary-soft",  "name": "Soft Blue",   "color": "#e8f0fe" }
]
```

### 1.4 Button Styling

Filled blue buttons with white text for clean CTAs:

```css
button,
.button,
.wp-element-button,
.woocommerce a.button,
.woocommerce button.button,
.woocommerce input.button {
  border: 1px solid var(--qr-primary);
  border-radius: var(--qr-radius);  /* squared-off, not pill */
  padding: 0.6rem 1.2rem;
  min-height: 40px;
  background: var(--qr-primary);
  color: #1d1d1f;                    /* white text on blue */
  font-weight: 700;
  text-transform: uppercase;         /* Electro uses uppercase CTAs */
  font-size: 0.85rem;
  letter-spacing: 0.03em;
  transition: background-color 0.15s ease, border-color 0.15s ease;
}

button:hover,
.button:hover,
.wp-element-button:hover,
.woocommerce a.button:hover,
.woocommerce button.button:hover,
.woocommerce input.button:hover {
  background: var(--qr-primary-hover);
  border-color: var(--qr-primary-hover);
  color: #1d1d1f;
}
```

Also update `.button--ghost` for outline style:

```css
.button--ghost {
  background: transparent;
  color: var(--qr-primary);
  border-color: var(--qr-primary);
}

.button--ghost:hover {
  background: var(--qr-primary);
  color: #1d1d1f;
}
```

### 1.5 Form Inputs on Dark Background

```css
input[type="search"],
input[type="text"],
input[type="email"],
input[type="tel"],
input[type="password"],
input[type="number"],
textarea,
select {
  background: var(--qr-surface-muted);
  color: var(--qr-text);
  border: 1px solid var(--qr-border);
}

input::placeholder,
textarea::placeholder {
  color: var(--qr-muted);
}
```

---

## Phase 2 — Header Transformation

**Goal:** Match a clean light header with blue accents.

### 2.1 Utility Bar

```css
.site-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: var(--qr-surface);
  border-bottom: 1px solid var(--qr-border);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

.site-header__utility {
  border-bottom: 1px solid var(--qr-border);
  background: #f0f0f2;  /* slightly darker than white surface */
}

.utility-row {
  color: var(--qr-muted);
  font-size: 0.78rem;
}

.utility-nav a {
  color: var(--qr-muted);
}

.utility-nav a:hover {
  color: var(--qr-primary);
}
```

### 2.2 Brand / Logo

The logo uses dark text on the light background. Update `.brand`:

```css
.brand {
  color: var(--qr-text);  /* dark on light */
  font-size: 1.75rem;
  font-weight: 800;
}

.brand:hover {
  color: var(--qr-text);
}

.brand__dot {
  color: var(--qr-primary);  /* blue dot accent */
}
```

### 2.3 Navigation Links

```css
.site-nav__menu a {
  color: var(--qr-text);
  font-weight: 600;
}

.site-nav__menu a:hover {
  color: var(--qr-primary);
}
```

### 2.4 Search Bar

Electro's search bar has a blue submit button and a category dropdown. Your structure already supports this — just update colours:

```css
.product-search-bar {
  border: 2px solid var(--qr-primary);
  border-radius: var(--qr-radius);  /* less rounded than pill */
  background: var(--qr-surface-muted);
}

.product-search-bar input[type="search"],
.product-search-bar select {
  background: var(--qr-surface-muted);
  color: var(--qr-text);
}

.product-search-bar button {
  background: var(--qr-primary);
  border-radius: var(--qr-radius);
}

/* Update the pseudo-element magnifying glass colour */
.product-search-bar button::before {
  border-color: #1d1d1f;
}

.product-search-bar button::after {
  background: #ffffff;
}
```

### 2.5 Header Action Buttons (Account / Cart)

```css
.header-action {
  border-color: var(--qr-border);
  background: var(--qr-surface-muted);
  color: var(--qr-text);
}

.header-action:hover {
  border-color: var(--qr-primary);
  color: var(--qr-primary);
}

.cart-count {
  background: var(--qr-primary);
  color: #1d1d1f;  /* white text on blue badge */
}
```

### 2.6 Category Strip (below header)

```css
.site-header__category-strip {
  border-top: 1px solid var(--qr-border);
  background: var(--qr-surface);
}

.category-navigation strong {
  color: var(--qr-primary);  /* "Browse Categories" in blue */
}

.category-strip__list a {
  color: var(--qr-muted);
}

.category-strip__list a:hover {
  color: var(--qr-primary);
}
```

---

## Phase 3 — Hero Section

**Goal:** Light gradient hero with bold headline, clean and modern product showcase.

### 3.1 Hero Background

```css
.hero-campaign {
  position: relative;
  overflow: hidden;
  padding: 5rem 0 6.5rem;
  background: linear-gradient(135deg, #ffffff 0%, #e8eeff 50%, #dce6ff 100%);
  border-bottom: 1px solid var(--qr-border);
}
```

### 3.2 Hero Typography

```css
.hero-campaign .eyebrow {
  color: var(--qr-primary);          /* blue eyebrow */
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.8rem;
}

.hero-campaign h1 {
  color: #1d1d1f;
  font-size: clamp(2.3rem, 5vw, 4.6rem);
  line-height: 1.02;
  letter-spacing: -0.045em;
}

.hero-campaign__copy {
  color: #6e6e73;                    /* muted grey */
}

.hero-campaign__offer {
  color: var(--qr-primary);          /* blue highlight */
  font-weight: 700;
}
```

### 3.3 Decorative Orbs

Update the orbs from green to a soft blue-lavender gradient:

```css
.hero-campaign__orb {
  background: linear-gradient(145deg, #dce6ff, #e8d5f5);
  box-shadow: 0 12px 30px rgba(0, 113, 227, 0.1);
}

.hero-campaign__orb::after {
  border-color: rgba(0, 113, 227, 0.12);  /* subtle blue ring */
}

.hero-campaign__orb--small {
  background: rgba(0, 113, 227, 0.04);
  border: 2px solid rgba(0, 113, 227, 0.08);
  box-shadow: none;
}
```

### 3.4 Hero Visual — Add a Product Image (Optional Enhancement)

Electro v12 shows an actual product image in the hero. To replicate this, add a theme Customizer field or hard-code an image:

**File:** `front-page.php` — replace the orb `<div>` content:

```php
<div class="hero-campaign__visual" aria-hidden="true">
    <?php
    $hero_image_id = get_theme_mod( 'qr_hero_image', 0 );
    if ( $hero_image_id ) {
        echo wp_get_attachment_image( $hero_image_id, 'large', false, array(
            'class'   => 'hero-campaign__product-img',
            'loading' => 'eager',
        ) );
    } else {
        // Fallback to decorative orbs
        ?>
        <span class="hero-campaign__orb hero-campaign__orb--large"></span>
        <span class="hero-campaign__orb hero-campaign__orb--small"></span>
        <?php
    }
    ?>
</div>
```

**File:** `functions.php` — add Customizer support:

```php
add_action( 'customize_register', 'qr_minimal_store_customizer' );
function qr_minimal_store_customizer( $wp_customize ) {
    $wp_customize->add_section( 'qr_hero_section', array(
        'title'    => __( 'Hero Banner', 'qr-minimal-store' ),
        'priority' => 30,
    ) );

    $wp_customize->add_setting( 'qr_hero_image', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ) );

    $wp_customize->add_control( new WP_Customize_Media_Control(
        $wp_customize,
        'qr_hero_image',
        array(
            'label'     => __( 'Hero Product Image', 'qr-minimal-store' ),
            'section'   => 'qr_hero_section',
            'mime_type' => 'image',
        )
    ) );
}
```

Add CSS for the hero product image:

```css
.hero-campaign__product-img {
  position: absolute;
  right: 0;
  bottom: 0;
  max-width: 90%;
  max-height: 95%;
  object-fit: contain;
  filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.4));
}
```

---

## Phase 4 — Category Rail

**Goal:** Dark cards with hover glow, matching Electro's category browsing bar.

```css
.category-rail .container {
  margin-top: -2.5rem;
  padding: 1rem;
  background: var(--qr-surface);
  border: 1px solid var(--qr-border);
  border-radius: var(--qr-radius);
  box-shadow: var(--qr-shadow);
}

.category-card a {
  color: var(--qr-text);
  border-color: transparent;
  border-radius: var(--qr-radius);
  transition: border-color 0.2s, box-shadow 0.2s;
}

.category-card a:hover {
  color: var(--qr-primary);
  border-color: var(--qr-primary);
  box-shadow: 0 0 12px rgba(0, 113, 227, 0.12);
}

.category-card__media {
  background: var(--qr-surface-muted);
  border-radius: var(--qr-radius);
}

.category-card__placeholder {
  background: var(--qr-border);
}

.category-card a > span:last-child {
  color: var(--qr-muted);
}
```

---

## Phase 5 — Product Cards

**Goal:** Dark product cards with cyan prices, yellow add-to-cart buttons, and hover lift effect.

### 5.1 Product Grid

```css
.woocommerce ul.products {
  background: var(--qr-border);   /* grid gap colour */
  border-color: var(--qr-border);
}

.woocommerce ul.products li.product.store-product-card {
  background: var(--qr-surface);
  border-right-color: var(--qr-border);
}

.woocommerce ul.products li.product.store-product-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
}
```

### 5.2 Product Image Area

```css
.store-product-card__media {
  background: var(--qr-surface-muted);
}
```

### 5.3 Product Title & Price

```css
.woocommerce ul.products li.product .woocommerce-loop-product__title {
  color: var(--qr-text);
  font-size: 0.95rem;
}

/* Cyan-blue price — the Electro signature */
.woocommerce ul.products li.product .price {
  color: var(--qr-price);       /* #38bdf8 cyan */
  font-weight: 700;
}

.woocommerce ul.products li.product .price del {
  color: var(--qr-muted);
}

.woocommerce ul.products li.product .star-rating {
  color: var(--qr-primary);     /* yellow stars */
}
```

### 5.4 Sale Badge

```css
.woocommerce span.onsale {
  background: var(--qr-danger);   /* red badge */
  color: #fff;
  border-radius: var(--qr-radius);
  font-weight: 700;
  text-transform: uppercase;
  font-size: 0.72rem;
}
```

---

## Phase 6 — Section Styling

### 6.1 Benefits Strip

```css
.benefits {
  background: var(--qr-surface);
  border-bottom-color: var(--qr-border);
}

.benefit-grid div {
  border-left-color: var(--qr-border);
}

.benefit-grid strong {
  color: var(--qr-text);
}

.benefit-grid span {
  color: var(--qr-muted);
}
```

Consider adding icons to each benefit. Electro uses small SVG icons next to each one:

**File:** `front-page.php` — enhance the benefit items:

```php
<div class="container benefit-grid">
    <div>
        <span class="benefit-icon" aria-hidden="true">🚚</span>
        <strong><?php esc_html_e( 'Fast Dispatch', 'qr-minimal-store' ); ?></strong>
        <span><?php esc_html_e( 'Orders processed quickly on business days.', 'qr-minimal-store' ); ?></span>
    </div>
    <!-- repeat for other benefits with 🔒 💬 🛡️ -->
</div>
```

```css
.benefit-icon {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}

.benefit-grid div {
  display: grid;
  gap: 0.25rem;
  padding: 0.6rem 1rem;
  border-left: 1px solid var(--qr-border);
  text-align: center;     /* Electro centers benefit items */
}
```

### 6.2 Section Headers

```css
.section-head {
  border-bottom-color: var(--qr-border);
}

.section-head h2 {
  color: var(--qr-text);
}

.section-head--split a,
.section-head--split span {
  color: var(--qr-primary);  /* yellow "See all" links */
}
```

### 6.3 Alt Section (Featured Products)

```css
.section--alt {
  background: var(--qr-surface-muted);
  border-color: var(--qr-border);
}
```

### 6.4 Product Tabs Section

```css
.product-tabs-section {
  background: var(--qr-surface);
  border-color: var(--qr-border);
}

.product-tabs__tab {
  border-color: var(--qr-border);
  background: transparent;
  color: var(--qr-muted);
}

.product-tabs__tab:hover {
  color: var(--qr-primary);
  border-color: var(--qr-primary);
}

.product-tabs__tab.is-active {
  background: var(--qr-primary);
  border-color: var(--qr-primary);
  color: #1d1d1f;           /* white text on blue tab */
}
```

### 6.5 Trust Strip

```css
.trust-strip {
  background: var(--qr-surface);
}

.trust-strip h3 {
  color: var(--qr-text);
}

.trust-strip p {
  color: var(--qr-muted);
}
```

### 6.6 Brand Strip

```css
.brand-strip-wrap {
  background: var(--qr-surface);
  border-color: var(--qr-border);
}

.brand-strip li {
  border-color: var(--qr-border);
  background: var(--qr-surface-muted);
  color: var(--qr-muted);
  min-height: 56px;           /* slightly taller */
  font-size: 0.95rem;
  transition: border-color 0.2s;
}

.brand-strip li:hover {
  border-color: var(--qr-primary);
  color: var(--qr-primary);
}
```

**Enhancement:** Replace text brand names with logo images. Add brand logos as media in WP and create a simple repeater or hardcode `<img>` tags. For now, the text-based approach is fine — just make them look polished on the light background.

---

## Phase 7 — Footer

**Goal:** Dark footer with clear hierarchy, matching Electro's structured footer.

### 7.1 Add a Newsletter Bar (Missing Section)

Electro has a prominent newsletter signup bar above the footer. Add this to `footer.php` before the `<footer>` tag:

**File:** `footer.php` — insert before `</main>`:

```php
<section class="newsletter-bar">
    <div class="container newsletter-bar__grid">
        <div class="newsletter-bar__content">
            <h3><?php esc_html_e( 'Sign up for our newsletter', 'qr-minimal-store' ); ?></h3>
            <p><?php esc_html_e( 'Get deals, new arrivals, and tech tips delivered to your inbox.', 'qr-minimal-store' ); ?></p>
        </div>
        <form class="newsletter-bar__form" action="#" method="post">
            <input type="email" name="newsletter_email" placeholder="<?php esc_attr_e( 'Your email address', 'qr-minimal-store' ); ?>" required>
            <button type="submit"><?php esc_html_e( 'Subscribe', 'qr-minimal-store' ); ?></button>
        </form>
    </div>
</section>
```

**CSS:**

```css
.newsletter-bar {
  padding: 2rem 0;
  background: var(--qr-primary);
  color: #1d1d1f;
}

.newsletter-bar__grid {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 2rem;
  align-items: center;
}

.newsletter-bar h3 {
  margin: 0;
  font-size: 1.35rem;
  color: #1d1d1f;
}

.newsletter-bar p {
  margin: 0.3rem 0 0;
  color: #e8f0fe;
}

.newsletter-bar__form {
  display: flex;
  gap: 0.5rem;
}

.newsletter-bar__form input[type="email"] {
  min-width: 280px;
  background: rgba(255, 255, 255, 0.9);
  color: #1d1d1f;
  border: 2px solid transparent;
  border-radius: var(--qr-radius);
}

.newsletter-bar__form input[type="email"]:focus {
  border-color: #1d1d1f;
  box-shadow: none;
}

.newsletter-bar__form button {
  background: #ffffff;
  color: var(--qr-primary);
  border-color: #1d1d1f;
}

.newsletter-bar__form button:hover {
  background: #f5f5f7;
  border-color: #f5f5f7;
  color: var(--qr-primary);
}

@media (max-width: 768px) {
  .newsletter-bar__grid {
    grid-template-columns: 1fr;
  }

  .newsletter-bar__form {
    flex-direction: column;
  }

  .newsletter-bar__form input[type="email"] {
    min-width: 0;
    width: 100%;
  }
}
```

> **Note:** For actual newsletter functionality, integrate with a plugin like Mailchimp for WooCommerce or use a simple custom handler. The HTML/CSS here gives you the visual structure.

### 7.2 Footer Colours

```css
.site-footer {
  padding: 2.5rem 0;
  background: #f5f5f7;         /* footer shade */
  border-top: 1px solid var(--qr-border);
  color: var(--qr-muted);
}

.footer-contact-bar {
  background: var(--qr-surface);
  border-color: var(--qr-border);
}

.footer-call {
  color: var(--qr-text);
}

.footer-contact-meta {
  color: var(--qr-muted);
}

.footer-brand h2 {
  color: var(--qr-text);
}

.footer-column h3 {
  color: var(--qr-text);
}

.footer-brand p,
.footer-column p {
  color: var(--qr-muted);
}

.footer-nav a {
  color: var(--qr-muted);
}

.footer-nav a:hover {
  color: var(--qr-primary);
}

.site-footer .category-strip__list a {
  color: var(--qr-muted);
}

.site-footer .category-strip__list a:hover {
  color: var(--qr-primary);
}
```

### 7.3 Update Placeholder Contact Info

**File:** `footer.php` — update the placeholder address and phone:

```php
<p class="footer-call"><?php esc_html_e( 'Contact us: hello@yehudasolutions.com', 'qr-minimal-store' ); ?></p>
```

```php
<p><?php esc_html_e( 'South Africa', 'qr-minimal-store' ); ?></p>
```

---

## Phase 8 — Single Product Page

Your `woocommerce div.product` styles already exist. Update colours for the light theme:

```css
.woocommerce div.product {
  background: var(--qr-surface);
  border-color: var(--qr-border);
}

.woocommerce div.product .product_title {
  color: var(--qr-text);
}

.woocommerce div.product p.price,
.woocommerce div.product span.price {
  color: var(--qr-price);       /* cyan price */
  font-weight: 700;
}

.woocommerce div.product p.price del,
.woocommerce div.product span.price del {
  color: var(--qr-muted);
}

.woocommerce div.product div.images .woocommerce-product-gallery__wrapper {
  border-color: var(--qr-border);
  background: var(--qr-surface-muted);
}

.woocommerce div.product .product_meta {
  border-top-color: var(--qr-border);
  color: var(--qr-muted);
}

.woocommerce div.product .stock {
  color: #22c55e;              /* green "in stock" */
}

.woocommerce div.product .stock.out-of-stock {
  color: var(--qr-danger);
}

/* Tab panels */
.woocommerce div.product .woocommerce-tabs ul.tabs::before {
  border-color: var(--qr-border);
}

.woocommerce div.product .woocommerce-tabs ul.tabs li {
  border-color: var(--qr-border);
  background: var(--qr-surface-muted);
}

.woocommerce div.product .woocommerce-tabs ul.tabs li a {
  color: var(--qr-muted);
}

.woocommerce div.product .woocommerce-tabs ul.tabs li.active {
  border-color: var(--qr-primary);
  background: var(--qr-primary);
}

.woocommerce div.product .woocommerce-tabs ul.tabs li.active a {
  color: #1d1d1f;
}

.woocommerce div.product .woocommerce-tabs .panel {
  background: var(--qr-surface-muted);
  color: var(--qr-text);
}
```

---

## Phase 9 — Cart & Checkout

Update the transactional pages for the light theme:

```css
.woocommerce-cart .woocommerce-cart-form,
.woocommerce-cart .cart-collaterals .cart_totals,
.woocommerce-checkout #customer_details,
.woocommerce-checkout #order_review {
  background: var(--qr-surface);
  border-color: var(--qr-border);
}

.woocommerce-cart table.shop_table,
.woocommerce-checkout table.shop_table {
  background: var(--qr-surface);
  color: var(--qr-text);
}

.woocommerce-cart table.shop_table th,
.woocommerce-cart table.shop_table td,
.woocommerce-checkout table.shop_table th,
.woocommerce-checkout table.shop_table td {
  border-bottom-color: var(--qr-border);
  color: var(--qr-text);
}

.woocommerce-cart table.cart td.product-name a {
  color: var(--qr-text);
}

.woocommerce-checkout .woocommerce-checkout-payment {
  border-color: var(--qr-border);
  background: var(--qr-surface-muted);
}

.woocommerce-checkout #payment ul.payment_methods {
  border-bottom-color: var(--qr-border);
}

.woocommerce-checkout #payment div.payment_box {
  background: var(--qr-surface-muted);
  color: var(--qr-text);
}

.woocommerce-checkout #payment div.payment_box::before {
  border-bottom-color: var(--qr-surface-muted);
}

.woocommerce-checkout .form-row label {
  color: var(--qr-text);
}

.woocommerce-checkout .select2-container .select2-selection--single {
  background: var(--qr-surface-muted);
  border-color: var(--qr-border);
  color: var(--qr-text);
}

/* My Account */
.woocommerce-account .woocommerce-MyAccount-navigation {
  background: var(--qr-surface);
  border-color: var(--qr-border);
}

.woocommerce-account .woocommerce-MyAccount-navigation a {
  color: var(--qr-text);
}

.woocommerce-account .woocommerce-MyAccount-navigation li.is-active a,
.woocommerce-account .woocommerce-MyAccount-navigation a:hover {
  background: var(--qr-primary);
  color: #1d1d1f;
}

.woocommerce-account .woocommerce-MyAccount-content {
  background: var(--qr-surface);
  border-color: var(--qr-border);
  color: var(--qr-text);
}

.woocommerce-account .woocommerce-Address {
  background: var(--qr-surface-muted);
  border-color: var(--qr-border);
}

/* Notices */
.woocommerce-message {
  background: rgba(254, 215, 0, 0.08);
  border-color: var(--qr-primary);
  border-top-color: var(--qr-primary);
  color: var(--qr-text);
}

.woocommerce-error {
  background: rgba(239, 68, 68, 0.08);
  border-color: var(--qr-danger);
  border-top-color: var(--qr-danger);
  color: #fca5a5;
}

.woocommerce-info {
  background: rgba(56, 189, 248, 0.08);
  border-color: var(--qr-price);
  border-top-color: var(--qr-price);
  color: var(--qr-text);
}
```

---

## Phase 10 — Missing Features & Enhancements

These are structural features Electro v12 has that your theme doesn't yet.

### 10.1 "Deal of the Day" Countdown Section

Electro shows a featured deal with a countdown timer. Add after the "Hot Products Today" section:

**File:** `front-page.php` — add after the hot-products section:

```php
<?php
$deal_product_id = get_theme_mod( 'qr_deal_product', 0 );
if ( $deal_product_id && function_exists( 'wc_get_product' ) ) :
    $deal = wc_get_product( $deal_product_id );
    if ( $deal && $deal->is_on_sale() ) :
        $sale_end = $deal->get_date_on_sale_to();
        $end_timestamp = $sale_end ? $sale_end->getTimestamp() : 0;
        ?>
        <section class="section deal-of-day" data-deal-end="<?php echo esc_attr( $end_timestamp ); ?>">
            <div class="container">
                <div class="section-head">
                    <h2><?php esc_html_e( 'Deal of the Day', 'qr-minimal-store' ); ?></h2>
                </div>
                <div class="deal-of-day__grid">
                    <div class="deal-of-day__media">
                        <?php echo $deal->get_image( 'woocommerce_single' ); ?>
                    </div>
                    <div class="deal-of-day__info">
                        <h3 class="deal-of-day__title"><?php echo esc_html( $deal->get_name() ); ?></h3>
                        <p class="deal-of-day__price"><?php echo $deal->get_price_html(); ?></p>
                        <?php if ( $end_timestamp ) : ?>
                            <div class="deal-countdown" aria-label="<?php esc_attr_e( 'Time remaining', 'qr-minimal-store' ); ?>">
                                <div class="deal-countdown__unit"><span data-days>00</span><small><?php esc_html_e( 'Days', 'qr-minimal-store' ); ?></small></div>
                                <div class="deal-countdown__unit"><span data-hours>00</span><small><?php esc_html_e( 'Hours', 'qr-minimal-store' ); ?></small></div>
                                <div class="deal-countdown__unit"><span data-minutes>00</span><small><?php esc_html_e( 'Min', 'qr-minimal-store' ); ?></small></div>
                                <div class="deal-countdown__unit"><span data-seconds>00</span><small><?php esc_html_e( 'Sec', 'qr-minimal-store' ); ?></small></div>
                            </div>
                        <?php endif; ?>
                        <a class="button" href="<?php echo esc_url( $deal->get_permalink() ); ?>"><?php esc_html_e( 'Shop this deal', 'qr-minimal-store' ); ?></a>
                    </div>
                </div>
            </div>
        </section>
        <?php
    endif;
endif;
?>
```

**CSS:**

```css
.deal-of-day {
  background: var(--qr-surface);
  border-block: 1px solid var(--qr-border);
}

.deal-of-day__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  align-items: center;
}

.deal-of-day__media img {
  width: 100%;
  border-radius: var(--qr-radius);
  background: var(--qr-surface-muted);
}

.deal-of-day__title {
  font-size: 1.5rem;
  color: var(--qr-text);
  margin: 0 0 0.75rem;
}

.deal-of-day__price {
  font-size: 1.75rem;
  color: var(--qr-price);
  font-weight: 700;
}

.deal-countdown {
  display: flex;
  gap: 0.75rem;
  margin: 1.25rem 0;
}

.deal-countdown__unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 60px;
  padding: 0.75rem;
  border-radius: var(--qr-radius);
  background: var(--qr-surface-muted);
  border: 1px solid var(--qr-border);
}

.deal-countdown__unit span {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--qr-primary);
}

.deal-countdown__unit small {
  font-size: 0.72rem;
  text-transform: uppercase;
  color: var(--qr-muted);
  letter-spacing: 0.05em;
}

@media (max-width: 768px) {
  .deal-of-day__grid {
    grid-template-columns: 1fr;
  }
}
```

**JS file:** `assets/js/deal-countdown.js`

```js
( function () {
  const section = document.querySelector( '[data-deal-end]' );
  if ( ! section ) return;

  const end = Number( section.dataset.dealEnd ) * 1000;
  if ( ! end ) return;

  const days    = section.querySelector( '[data-days]' );
  const hours   = section.querySelector( '[data-hours]' );
  const minutes = section.querySelector( '[data-minutes]' );
  const seconds = section.querySelector( '[data-seconds]' );
  if ( ! days || ! hours || ! minutes || ! seconds ) return;

  function pad( n ) { return String( n ).padStart( 2, '0' ); }

  function tick() {
    const diff = Math.max( 0, end - Date.now() );
    const s    = Math.floor( diff / 1000 );

    days.textContent    = pad( Math.floor( s / 86400 ) );
    hours.textContent   = pad( Math.floor( ( s % 86400 ) / 3600 ) );
    minutes.textContent = pad( Math.floor( ( s % 3600 ) / 60 ) );
    seconds.textContent = pad( s % 60 );

    if ( diff > 0 ) requestAnimationFrame( tick );
  }

  tick();
  setInterval( tick, 1000 );
} )();
```

**Enqueue the script** in `functions.php` inside the `is_front_page()` block:

```php
if ( is_front_page() ) {
    // existing product-tabs enqueue...

    wp_enqueue_script(
        'qr-minimal-store-deal-countdown',
        get_template_directory_uri() . '/assets/js/deal-countdown.js',
        array(),
        $js_ver,
        true
    );
}
```

**Customizer setting** (add inside `qr_minimal_store_customizer`):

```php
$wp_customize->add_setting( 'qr_deal_product', array(
    'default'           => 0,
    'sanitize_callback' => 'absint',
) );

$wp_customize->add_control( 'qr_deal_product', array(
    'label'   => __( 'Deal of the Day — Product ID', 'qr-minimal-store' ),
    'section' => 'qr_hero_section',
    'type'    => 'number',
) );
```

### 10.2 "Recently Viewed" Section

Electro shows recently viewed products at the bottom. WooCommerce tracks these in cookies — add after the brand strip:

**File:** `front-page.php` — add before `get_footer()`:

```php
<?php if ( function_exists( 'wc_get_products' ) ) : ?>
    <?php
    $viewed = array_filter( array_map( 'absint', explode( '|', wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ?? '' ) ) ) );
    if ( ! empty( $viewed ) ) :
        ?>
        <section class="section section--tight recently-viewed">
            <div class="container">
                <div class="section-head section-head--split">
                    <h2><?php esc_html_e( 'Recently Viewed', 'qr-minimal-store' ); ?></h2>
                </div>
                <?php echo do_shortcode( '[products ids="' . implode( ',', array_slice( $viewed, 0, 4 ) ) . '" columns="4"]' ); ?>
            </div>
        </section>
        <?php
    endif;
    ?>
<?php endif; ?>
```

### 10.3 Sticky "Add to Cart" on Mobile (Single Product)

Electro shows a sticky bottom bar on mobile product pages. Add this CSS + a small PHP snippet:

**File:** `functions.php`:

```php
add_action( 'wp_footer', 'qr_minimal_store_sticky_add_to_cart' );
function qr_minimal_store_sticky_add_to_cart() {
    if ( ! is_product() ) return;
    global $product;
    if ( ! $product ) return;
    ?>
    <div class="sticky-add-to-cart" id="sticky-atc" hidden>
        <div class="container sticky-add-to-cart__inner">
            <div class="sticky-add-to-cart__info">
                <strong><?php echo esc_html( $product->get_name() ); ?></strong>
                <span><?php echo $product->get_price_html(); ?></span>
            </div>
            <a class="button" href="#product-<?php echo esc_attr( $product->get_id() ); ?>">
                <?php esc_html_e( 'Add to Cart', 'qr-minimal-store' ); ?>
            </a>
        </div>
    </div>
    <script>
    (function(){
      var bar = document.getElementById('sticky-atc');
      var form = document.querySelector('form.cart');
      if (!bar || !form) return;
      var io = new IntersectionObserver(function(entries){
        bar.hidden = entries[0].isIntersecting;
      }, { threshold: 0 });
      io.observe(form);
    })();
    </script>
    <?php
}
```

**CSS:**

```css
.sticky-add-to-cart {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 90;
  padding: 0.75rem 0;
  background: var(--qr-surface);
  border-top: 1px solid var(--qr-border);
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.3);
}

.sticky-add-to-cart__inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.sticky-add-to-cart__info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.sticky-add-to-cart__info strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--qr-text);
  font-size: 0.92rem;
}

.sticky-add-to-cart__info span {
  color: var(--qr-price);
  font-weight: 700;
}

@media (min-width: 769px) {
  .sticky-add-to-cart {
    display: none;
  }
}
```

---

## Phase 11 — Performance & Polish

### 11.1 Preload Critical Font (Optional)

If you switch from system fonts to a custom font like Inter or Poppins:

```php
add_action( 'wp_head', 'qr_minimal_store_preload_font', 1 );
function qr_minimal_store_preload_font() {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" as="style">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap">
    <?php
}
```

Then update CSS: `font-family: 'Inter', -apple-system, ...sans-serif;`

### 11.2 Smooth Scroll for Anchor Links

```css
html {
  scroll-behavior: smooth;
  scroll-padding-top: 160px;  /* account for sticky header */
}
```

### 11.3 Loading Skeleton States

Add subtle shimmer placeholders for async content:

```css
@keyframes shimmer {
  0%   { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.category-card__placeholder,
.woocommerce ul.products li.product img[src=""] {
  background: linear-gradient(
    90deg,
    var(--qr-surface-muted) 25%,
    var(--qr-border) 50%,
    var(--qr-surface-muted) 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}
```

### 11.4 Focus Visible for Dark Mode

```css
:focus-visible {
  outline: 3px solid rgba(0, 113, 227, 0.4);
  outline-offset: 3px;
}
```

---

## Phase 12 — Mobile Hamburger Menu

Your current header puts the navigation inline, which collapses awkwardly on mobile. Add a hamburger toggle:

**File:** `header.php` — add before the `<nav class="header-main__navigation">` element:

```php
<button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="<?php esc_attr_e( 'Toggle menu', 'qr-minimal-store' ); ?>">
    <span class="nav-toggle__bar"></span>
    <span class="nav-toggle__bar"></span>
    <span class="nav-toggle__bar"></span>
</button>
```

Add `id="primary-nav"` to the `<nav class="header-main__navigation">` tag.

**CSS:**

```css
.nav-toggle {
  display: none;     /* hidden on desktop */
  flex-direction: column;
  gap: 4px;
  padding: 8px;
  background: none;
  border: 1px solid var(--qr-border);
  border-radius: var(--qr-radius);
  min-height: 44px;
  min-width: 44px;
  justify-content: center;
  align-items: center;
  cursor: pointer;
}

.nav-toggle__bar {
  display: block;
  width: 20px;
  height: 2px;
  background: var(--qr-text);
  border-radius: 2px;
  transition: transform 0.2s, opacity 0.2s;
}

.nav-toggle[aria-expanded="true"] .nav-toggle__bar:nth-child(1) {
  transform: rotate(45deg) translate(4px, 4px);
}

.nav-toggle[aria-expanded="true"] .nav-toggle__bar:nth-child(2) {
  opacity: 0;
}

.nav-toggle[aria-expanded="true"] .nav-toggle__bar:nth-child(3) {
  transform: rotate(-45deg) translate(4px, -4px);
}

@media (max-width: 992px) {
  .nav-toggle {
    display: flex;
  }

  #primary-nav {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--qr-surface);
    border-bottom: 1px solid var(--qr-border);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    z-index: 50;
    padding: 1rem;
  }

  #primary-nav.is-open {
    display: block;
  }

  #primary-nav .site-nav__menu {
    flex-direction: column;
    gap: 0;
  }

  #primary-nav .site-nav__menu a {
    display: block;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--qr-border);
  }
}
```

**JS:** Add to `functions.php` or create `assets/js/nav-toggle.js`:

```js
( function () {
  const toggle = document.querySelector( '.nav-toggle' );
  const nav    = document.getElementById( 'primary-nav' );
  if ( ! toggle || ! nav ) return;

  toggle.addEventListener( 'click', function () {
    const expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
    toggle.setAttribute( 'aria-expanded', String( ! expanded ) );
    nav.classList.toggle( 'is-open' );
  } );
} )();
```

Enqueue on all pages in `functions.php`:

```php
wp_enqueue_script(
    'qr-minimal-store-nav-toggle',
    get_template_directory_uri() . '/assets/js/nav-toggle.js',
    array(),
    wp_get_theme()->get( 'Version' ),
    true
);
```

---

## Quick-Reference Checklist

Use this to track your progress in Cursor:

| #  | Phase | Key File(s) | Status |
|----|-------|-------------|--------|
| 1  | Dark palette & typography | `site.css` `:root`, `theme.json` | ▢ |
| 2  | Header transformation | `site.css` header rules | ▢ |
| 3  | Hero section | `site.css` hero rules, `front-page.php` | ▢ |
| 4  | Category rail | `site.css` category rules | ▢ |
| 5  | Product cards | `site.css` WooCommerce product rules | ▢ |
| 6  | Section styling | `site.css` sections, `front-page.php` | ▢ |
| 7  | Footer + newsletter | `footer.php`, `site.css` | ▢ |
| 8  | Single product page | `site.css` product detail rules | ▢ |
| 9  | Cart & checkout | `site.css` transactional rules | ▢ |
| 10 | New features (deal, sticky ATC, recently viewed) | `front-page.php`, `functions.php`, new JS | ▢ |
| 11 | Performance & polish | `site.css`, `functions.php` | ▢ |
| 12 | Mobile hamburger menu | `header.php`, `site.css`, new JS | ▢ |

---

## File Summary — What Gets Changed

| File | Type of Change |
|------|---------------|
| `assets/css/site.css` | Colour variables, all component colours, new component styles |
| `theme.json` | Palette values |
| `front-page.php` | Hero image option, benefit icons, deal section, recently viewed |
| `footer.php` | Newsletter bar, contact info update |
| `header.php` | Hamburger toggle button, nav id |
| `functions.php` | Customizer settings, deal countdown enqueue, nav toggle enqueue, sticky ATC |
| `assets/js/deal-countdown.js` | **New file** — countdown timer |
| `assets/js/nav-toggle.js` | **New file** — mobile menu toggle |

---

## Development Tips for Cursor

1. **Work phase by phase.** Each phase is testable independently — refresh `localhost/store/` after each one.
2. **Use Cursor's multi-file editing.** When changing `:root` variables, the cascade handles most colour changes automatically.
3. **Test with products.** Import WooCommerce sample data (`WooCommerce → Status → Tools → Import sample products`) to see product cards, prices, and sale badges.
4. **Check responsive.** Use Chrome DevTools device toolbar at 1200px → 992px → 768px → 640px → 420px.
5. **Hard refresh.** CSS is versioned by `filemtime()` in functions.php, but still Ctrl+Shift+R after CSS edits.
6. **Don't touch `style.css`.** Keep it as metadata only — all styles live in `assets/css/site.css`.
