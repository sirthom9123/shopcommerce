# Spring Bonanza Landing Page — Cursor Build Prompt

Paste this entire file into Cursor as your prompt. It contains everything needed to build the landing page as a WordPress template in your existing `qr-minimal-store` theme.

---

## What to Build

A **Spring Bonanza Sale landing page** for `shop.yehudasolutions.com/spring-bonanza/`. This is a WordPress/WooCommerce page template in the existing `qr-minimal-store` theme (located at `C:\xampp\htdocs\store\wp-content\themes\qr-minimal-store\`).

The page is the destination for Meta Ads driving traffic to a sitewide 5% off sale running Sep 7–30, 2026. It must match the existing site's design language exactly — clean, minimal, Apple-inspired.

**Create these files:**
1. `page-spring-bonanza.php` — WordPress page template
2. `assets/css/spring-bonanza.css` — Dedicated stylesheet (enqueue conditionally)
3. Update `functions.php` — Add the CSS enqueue for this template

**Then create a WordPress page** titled "Spring Bonanza" with slug `spring-bonanza` and assign this template.

---

## Design System (from theme.json — match exactly)

```css
/* Colors — use these CSS custom properties */
--wp--preset--color--background: #ffffff;
--wp--preset--color--surface: #f5f5f7;
--wp--preset--color--foreground: #1d1d1f;
--wp--preset--color--muted: #6e6e73;
--wp--preset--color--primary: #0855a1;
--wp--preset--color--primary-hover: #0077ed;
--wp--preset--color--primary-soft: #e8f0fe;

/* Additional brand colors for this campaign */
--bonanza-accent: #0071e3;        /* Campaign blue — use for CTAs and price highlights */
--bonanza-badge: #34c759;         /* Green for trust badges */

/* Typography */
Font: System sans-serif stack (-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif)
Line height: 1.6
Buttons: font-weight 600, border-radius 8px

/* Layout */
Content width: 760px
Wide width: 1200px
Use .container class for max-width centering (already exists in theme)
```

---

## Page Structure (sections top to bottom)

### 1. Hero Banner
- **Background:** Gradient from `#0855a1` to `#0071e3` (brand blue tones)
- **Layout:** Two columns — left: text content, right: decorative (product collage or abstract orbs like existing hero)
- **Eyebrow:** "Limited Time — Sep 7–30"
- **Headline:** "Spring Bonanza — 5% Off Everything"
- **Subheadline:** "Smart gadgets, kitchen appliances, and lifestyle essentials from trusted brands. Authorized Xiaomi dealer."
- **CTA button:** "Shop the Sale" → links to `/shop/` with sale filter or coupon auto-apply
- **Secondary link:** "See featured products ↓" → anchor to featured section
- **Countdown timer:** Days / Hours / Min / Sec until Sep 30 (use same countdown pattern as the existing `deal-of-day` section in `front-page.php` — the `data-deal-end` timestamp approach)
- **Trust bar below hero:** 3 inline badges — "Authorized Xiaomi Dealer" | "Free Delivery Over R2,000" | "Local Warranty"

### 2. Featured Sale Products (4 products)
- **Heading:** "Top Picks This Spring"
- **Layout:** 4-column product grid (2 columns on mobile)
- Use WooCommerce shortcode: `[products ids="SCALE_ID,STEAMER_ID,DRYER_ID,MASSAGE_GUN_ID" columns="4"]`
- Or use `[products limit="4" columns="4" on_sale="true" orderby="popularity"]` if products are set to on-sale in WooCommerce
- **Note:** The store owner will need to replace the product IDs with actual WooCommerce product IDs or ensure the 5% coupon/sale is applied to products in WooCommerce admin

### 3. Value Props / Why Shop With Us
- **Background:** `#f5f5f7` (surface color)
- **Layout:** 4-column icon grid (same style as existing `.benefits` section)
- **Items:**
  - 🚚 **Free Delivery** — "On orders over R2,000"
  - 🛡️ **Local Warranty** — "SA-based support & returns"
  - ✅ **Authorized Dealer** — "Genuine Xiaomi products"
  - 💳 **Secure Checkout** — "Trusted payment gateways"

### 4. Product Category Cards
- **Heading:** "Shop by Category"
- **Layout:** 3 cards in a row (stack on mobile)
- **Cards:** Link to category pages
  - **Smart Home** → `/product-category/smart-home/` — "Scales, kettles, air purifiers"
  - **Personal Care** → `/product-category/personal-care/` — "Hair dryers, steamers, grooming"
  - **Health & Fitness** → `/product-category/health-fitness/` — "Massage guns, smart watches"
- **Style:** Each card has a subtle hover lift (match existing `.card` hover pattern — `translateY(-4px)` + shadow)

### 5. All Sale Products
- **Heading:** "Everything 5% Off"
- **Subheading:** "Browse the full range — sale prices applied at checkout"
- Use WooCommerce shortcode: `[products limit="12" columns="4" orderby="popularity"]`
- **"View All" link** below → `/shop/`

### 6. Trust Strip
- **Background:** `#1d1d1f` (dark foreground color)
- **Text:** White
- **Layout:** 3 columns (same as existing `.trust-strip` in front-page.php)
- **Items:**
  - **"Authorized Xiaomi Dealer"** — "Every product is genuine with full manufacturer warranty"
  - **"South African Business"** — "Local support, local shipping, local returns"
  - **"Spring Bonanza Guarantee"** — "5% off everything, no minimum order required"

### 7. Newsletter + CTA
- **Reuse** the existing `.newsletter-bar` component from footer.php
- **Below it:** A final CTA banner — "Don't miss out — Spring Bonanza ends Sep 30" with "Shop Now" button

### 8. Footer
- Use standard `get_footer()` — the existing footer handles itself

---

## Technical Requirements

### WordPress Template File (`page-spring-bonanza.php`)

```php
<?php
/**
 * Template Name: Spring Bonanza
 * Description: Spring Bonanza 2026 sale landing page
 */

get_header();
// ... page content sections here ...
get_footer();
```

### CSS Enqueue (add to `functions.php`)

```php
// In the existing enqueue function or as a new one:
function qr_spring_bonanza_assets() {
    if ( is_page_template( 'page-spring-bonanza.php' ) ) {
        wp_enqueue_style(
            'qr-spring-bonanza',
            get_theme_file_uri( 'assets/css/spring-bonanza.css' ),
            array(),
            '1.0.0'
        );
    }
}
add_action( 'wp_enqueue_scripts', 'qr_spring_bonanza_assets' );
```

### Countdown Timer JavaScript

Reuse the existing countdown pattern from `front-page.php`. The deal-of-day section uses `data-deal-end` with a Unix timestamp. Do the same for the Spring Bonanza end date:

```php
<section class="bonanza-hero" data-bonanza-end="<?php echo esc_attr( (string) strtotime( '2026-09-30 23:59:59' ) ); ?>">
```

```javascript
// Countdown JS — inline at bottom of template or in a separate enqueued file
(function() {
    const hero = document.querySelector('[data-bonanza-end]');
    if (!hero) return;
    const end = parseInt(hero.dataset.bonanzaEnd, 10) * 1000;
    
    function update() {
        const now = Date.now();
        const diff = Math.max(0, end - now);
        const d = Math.floor(diff / 86400000);
        const h = Math.floor((diff % 86400000) / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        
        const el = hero.querySelector('.bonanza-countdown');
        if (el) {
            el.querySelector('[data-days]').textContent = String(d).padStart(2, '0');
            el.querySelector('[data-hours]').textContent = String(h).padStart(2, '0');
            el.querySelector('[data-minutes]').textContent = String(m).padStart(2, '0');
            el.querySelector('[data-seconds]').textContent = String(s).padStart(2, '0');
        }
        if (diff > 0) requestAnimationFrame(update);
    }
    update();
})();
```

### Responsive Breakpoints

Match the existing theme:
- **Desktop:** 4-column grids, side-by-side hero
- **Tablet (≤1024px):** 2-column grids, stacked hero
- **Mobile (≤768px):** 1-column stacked, full-width CTAs, smaller text

### Accessibility

- All images need `alt` text
- Countdown needs `aria-label="Time remaining"`
- CTAs need clear focus states (`:focus-visible` outline)
- Color contrast: all text meets WCAG AA (the existing theme colors already pass)

---

## Copy (exact text to use)

### Hero
- Eyebrow: "Limited Time — Sep 7–30"
- H1: "Spring Bonanza — 5% Off Everything"
- Body: "Smart gadgets, kitchen appliances, and lifestyle essentials from trusted brands. Authorized Xiaomi dealer with local warranty."
- CTA: "Shop the Sale"
- Secondary: "See featured products"

### Trust Badges (below hero)
- "Authorized Xiaomi Dealer"
- "Free Delivery Over R2,000"
- "Local Warranty & Support"

### Section Headings
- "Top Picks This Spring"
- "Shop by Category"
- "Everything 5% Off"
- "Why Shop With Us"

### Final CTA Banner
- "Don't miss out — Spring Bonanza ends Sep 30"
- Button: "Shop Now"

---

## UTM / Tracking

The Meta Ads will send traffic with this UTM: `?utm_source=meta&utm_medium=paid&utm_campaign=spring-bonanza-2026`

Make sure the page works correctly with these query parameters (WordPress handles this by default, just don't break it).

Ensure the Meta Pixel fires `ViewContent` on this page. If the pixel is installed site-wide via a plugin (like PixelYourSite or Meta's official plugin), this happens automatically. If not, add the pixel base code to `header.php` if it's not already there.

---

## What NOT to Do

- Don't create a standalone HTML file — this must be a WordPress template that uses `get_header()` and `get_footer()` so it inherits the site nav, cart, and footer
- Don't use external CSS frameworks (no Tailwind, no Bootstrap) — match the existing theme's vanilla CSS approach
- Don't hardcode product IDs — use WooCommerce shortcodes with dynamic parameters where possible, and leave comments where the store owner needs to insert specific IDs
- Don't use JavaScript frameworks — vanilla JS only, matching the theme's existing patterns
- Don't change the existing theme files except for adding the CSS enqueue to `functions.php`

---

## File Checklist

After building, verify:
- [ ] `page-spring-bonanza.php` exists in theme root
- [ ] `assets/css/spring-bonanza.css` exists with all page styles
- [ ] `functions.php` updated with conditional CSS enqueue
- [ ] WordPress page created with slug `spring-bonanza` and template assigned
- [ ] Countdown timer works (shows days/hours/min/sec until Sep 30)
- [ ] All WooCommerce shortcodes render products
- [ ] Page is responsive (test at 375px, 768px, 1200px)
- [ ] Page matches the rest of the site visually (same header, footer, fonts, colors)
- [ ] "Shop the Sale" CTA links to `/shop/` or the appropriate sale collection
