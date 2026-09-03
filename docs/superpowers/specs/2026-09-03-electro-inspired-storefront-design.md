# Electro-Inspired Storefront Design

## Goal

Restyle the existing QR Minimal Store theme with an Electro-inspired electronics retail design while retaining the store's current identity, WooCommerce catalog, and dynamic commerce behavior.

The design applies across the homepage, shared header and footer, shop archive, product pages, cart, checkout, and customer account screens.

## Constraints

- Modify only the custom theme at `wp-content/themes/qr-minimal-store`.
- Do not edit WordPress core, WooCommerce core, or third-party themes and plugins.
- Keep product, category, pricing, account, search, and cart information dynamic.
- Retain the current store name and identity. Do not copy the Electro wordmark or third-party product content.
- Do not add a page builder or new runtime dependency.
- Preserve WooCommerce hooks, forms, notices, and cart behavior.

## Visual System

The storefront will use a retail-focused design derived from the supplied Electro reference:

- Warm off-white page background.
- White content and product surfaces.
- Dark charcoal headings and body text.
- Muted gray secondary information and borders.
- Grass green (`#457d53`) for primary actions, active states, highlights, search controls, and cart badges, with lighter green (`#5f966d`) for hover and supporting accents.
- Rounded search, buttons, cards, and form controls.
- Compact typography and denser product merchandising on wide screens.
- Clear keyboard focus indicators and sufficient text contrast.

These values will be expressed as reusable CSS custom properties and mirrored in `theme.json` where WordPress editor presets are relevant.

## Shared Header and Navigation

The header will be reorganized into:

1. A slim utility row for store messaging and account-related links.
2. A primary row containing the store identity, main navigation, product search, and account/cart actions.
3. A category navigation area populated from WooCommerce product categories.

The product search will retain its current category filtering and query behavior. The desktop presentation will use a rounded search field with a grass green submit control. Tablet and mobile layouts will collapse into readable stacked or wrapped rows without horizontal overflow.

The cart count will continue to come from the WooCommerce cart object. Missing WooCommerce state will fall back to the existing cart URL and omit the count without producing an error.

## Homepage

The homepage will retain its current dynamic product sections while adopting the reference's hierarchy:

- A large promotional hero with campaign copy, one primary action, and supporting promotions.
- A row of product-category cards using WooCommerce category names, links, and available thumbnails.
- A compact benefits strip for dispatch, payment, support, and warranty messaging.
- Product sections for popular, featured, recent, top-rated, and best-selling products.
- Accessible tabs for switching between highlighted product collections.
- A brand strip and trust content near the page footer.

The design will use the store's own categories and products. If category thumbnails or product images are missing, neutral placeholders and stable card dimensions will prevent layout shifts or broken composition.

## WooCommerce Pages

### Shop and Category Archives

- Product grids will use consistent card dimensions, image areas, product titles, prices, sale badges, ratings, and grass green purchase actions.
- Sorting, result counts, pagination, notices, and category context remain functional.
- Grid density will scale from four columns on large screens to fewer columns on tablets and phones.

### Product Detail

- The gallery and product summary will be presented as a balanced two-column layout on desktop and a single column on mobile.
- Price, availability, variation controls, quantity, and add-to-cart actions receive the shared visual styling.
- Tabs, related products, notices, and product metadata remain compatible with WooCommerce output.

### Cart and Checkout

- Tables, totals, coupon controls, address fields, notices, payment methods, and order review use clear bordered sections and consistent spacing.
- Primary checkout actions use the primary grass green with readable white text.
- Existing WooCommerce validation and error messages remain visible and are not replaced by theme logic.

### Customer Account

- Account navigation and content use responsive card-like panels.
- Orders, downloads, addresses, and account forms retain native WooCommerce actions and markup.

## Footer

The footer will keep the existing contact and navigation content while adopting a clearer multi-column electronics-store presentation. It will use the same spacing, borders, typography, and responsive collapse rules as the rest of the theme.

## Data and Empty States

All storefront data continues to come from WordPress and WooCommerce APIs, menus, shortcodes, and template hooks.

- No matching product collections: the corresponding WooCommerce message or an unobtrusive empty state is shown.
- No category image: a neutral category image area is rendered.
- No registered menu: the existing WordPress fallback remains usable.
- WooCommerce unavailable: theme links use existing URL fallbacks and PHP guards prevent fatal errors.

## Accessibility and Responsiveness

- Interactive elements will expose visible hover and keyboard focus states.
- Product tabs retain their tab roles, selected state, panel association, and keyboard-reachable controls.
- Form labels remain available to assistive technology.
- Color will not be the only active-state indicator.
- Layouts will be checked at desktop, tablet, and narrow mobile widths.
- Motion will remain subtle and respect reduced-motion preferences.

## Implementation Boundaries

Expected theme files to change:

- `assets/css/site.css` for the visual system and responsive component styling.
- `theme.json` for matching editor colors, typography, and button defaults.
- `header.php` for the Electro-inspired header structure.
- `front-page.php` for the updated homepage hierarchy and category presentation.
- `footer.php` if footer markup needs small structural changes.
- Theme-owned WooCommerce templates only where CSS cannot provide the required semantic structure.
- `functions.php` only for thin presentation helpers, asset enqueueing, or safe WooCommerce data retrieval.

Business rules and persistent store behavior will not be added to the theme.

## Verification

Verification will include:

- PHP syntax checks for each modified PHP file.
- Theme diagnostics for modified CSS, JSON, JavaScript, and PHP files.
- Homepage checks with populated and sparse catalog content.
- Shop/category grid, sorting, pagination, and add-to-cart smoke tests.
- Simple and variable product page checks where those product types exist.
- Cart update, coupon, totals, and empty-cart checks.
- Checkout field, validation, payment-method, and order-review checks using configured test methods.
- Customer account navigation and form checks.
- Responsive checks at representative desktop, tablet, and mobile widths.
- Confirmation that no WordPress, WooCommerce, or third-party files changed.

## Acceptance Criteria

- The full storefront has a cohesive Electro-inspired grass green, charcoal, off-white, and white presentation.
- The current store name, catalog, prices, categories, and customer workflows remain intact.
- Header, homepage, product cards, forms, and commerce pages share one responsive design system.
- WooCommerce search, cart, product, checkout, and account behavior continues to work.
- Missing images or sparse product data do not break the page layout.
- No core or third-party package files are modified.
