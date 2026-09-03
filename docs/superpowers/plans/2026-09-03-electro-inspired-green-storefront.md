# Electro-Inspired Green Storefront Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restyle the complete QR Minimal Store WooCommerce storefront with an Electro-inspired retail layout and a grass green `#457d53` / `#5f966d` accent system.

**Architecture:** Evolve the existing classic PHP theme without changing WooCommerce business behavior. Keep dynamic data retrieval in thin prefixed theme helpers, semantic page structure in theme templates, and the complete responsive design system in the theme stylesheet and `theme.json`.

**Tech Stack:** WordPress classic theme templates, PHP, WooCommerce hooks and shortcodes, CSS custom properties, vanilla JavaScript, Python `unittest` contract tests.

## Global Constraints

- Modify only `wp-content/themes/qr-minimal-store`, plus project-owned tests and documentation.
- Do not edit WordPress core, WooCommerce core, or third-party themes and plugins.
- Primary grass green is exactly `#457d53`; supporting and hover green is exactly `#5f966d`.
- Retain the current store name, identity, catalog, pricing, search, cart, checkout, and account behavior.
- Do not copy the Electro wordmark, copyrighted promotional imagery, or third-party product content.
- Do not add a page builder or runtime dependency.
- Preserve WooCommerce hooks, forms, notices, fragments, and native validation.
- Keep all output escaped and all query input sanitized.
- The current workspace is not a Git repository. Commit commands must not be run unless repository initialization is separately authorized.

## File Map

- Create `tests/test_theme_contract.py`: dependency-free structural regression tests for tokens, templates, helpers, accessibility, and forbidden core changes.
- Modify `wp-content/themes/qr-minimal-store/theme.json`: editor-visible colors, typography, layout widths, and button defaults.
- Modify `wp-content/themes/qr-minimal-store/assets/css/site.css`: shared tokens and all responsive storefront components.
- Modify `wp-content/themes/qr-minimal-store/header.php`: utility bar, brand/navigation row, search, account, and cart structure.
- Modify `wp-content/themes/qr-minimal-store/functions.php`: presentation-only category card and safe URL helpers.
- Modify `wp-content/themes/qr-minimal-store/front-page.php`: promotional hero, category rail, benefits, and product merchandising hierarchy.
- Modify `wp-content/themes/qr-minimal-store/footer.php`: dynamic category links and cohesive footer structure.
- Modify `wp-content/themes/qr-minimal-store/woocommerce/content-product.php`: stable product-card structure while preserving WooCommerce hooks.
- Keep `woocommerce/archive-product.php`, `woocommerce/single-product.php`, and `woocommerce.php` behavior intact; style their existing semantic output through CSS.

---

### Task 1: Contract Test Harness and Green Design Tokens

**Files:**
- Create: `tests/test_theme_contract.py`
- Modify: `wp-content/themes/qr-minimal-store/theme.json`
- Modify: `wp-content/themes/qr-minimal-store/assets/css/site.css:1-179`

**Interfaces:**
- Consumes: theme files relative to the project root.
- Produces: CSS custom properties `--qr-primary`, `--qr-primary-hover`, `--qr-primary-soft`, `--qr-bg`, `--qr-surface`, `--qr-text`, `--qr-muted`, `--qr-border`, `--qr-radius`, and `--qr-shadow`.

- [ ] **Step 1: Write the failing design-token contract tests**

Create `tests/test_theme_contract.py`:

```python
import json
import pathlib
import re
import unittest


ROOT = pathlib.Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content" / "themes" / "qr-minimal-store"


class ThemeContractTests(unittest.TestCase):
    def read(self, relative_path):
        return (THEME / relative_path).read_text(encoding="utf-8")

    def test_css_uses_approved_grass_green_tokens(self):
        css = self.read("assets/css/site.css").lower()
        self.assertRegex(css, r"--qr-primary:\s*#457d53")
        self.assertRegex(css, r"--qr-primary-hover:\s*#5f966d")
        self.assertNotIn("#fed700", css)

    def test_theme_json_exposes_matching_palette(self):
        data = json.loads(self.read("theme.json"))
        palette = {
            item["slug"]: item["color"].lower()
            for item in data["settings"]["color"]["palette"]
        }
        self.assertEqual("#457d53", palette["primary"])
        self.assertEqual("#5f966d", palette["primary-hover"])


if __name__ == "__main__":
    unittest.main()
```

- [ ] **Step 2: Run the token tests and verify RED**

Run:

```powershell
python -m unittest tests.test_theme_contract.ThemeContractTests.test_css_uses_approved_grass_green_tokens tests.test_theme_contract.ThemeContractTests.test_theme_json_exposes_matching_palette -v
```

Expected: both tests fail because the existing theme uses `#101828` as primary and has no `primary-hover` token.

- [ ] **Step 3: Replace the global CSS token block and base controls**

At the top of `assets/css/site.css`, define:

```css
:root {
  --qr-bg: #f4f2ec;
  --qr-surface: #ffffff;
  --qr-surface-muted: #f8f7f3;
  --qr-text: #25312a;
  --qr-muted: #69736c;
  --qr-border: #e3e5e2;
  --qr-primary: #457d53;
  --qr-primary-hover: #5f966d;
  --qr-primary-soft: #e8f0ea;
  --qr-danger: #b42318;
  --qr-radius: 10px;
  --qr-radius-pill: 999px;
  --qr-gap: 1.25rem;
  --qr-shadow: 0 12px 32px rgba(37, 49, 42, 0.08);
}
```

Update the body, links, controls, buttons, focus styles, and container width so primary buttons use `var(--qr-primary)` with white text, hover uses `var(--qr-primary-hover)`, focus uses a visible `3px` `var(--qr-primary-soft)` ring, and `.container` remains capped at `1200px`.

Add:

```css
:focus-visible {
  outline: 3px solid rgba(69, 125, 83, 0.35);
  outline-offset: 3px;
}

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    scroll-behavior: auto !important;
    transition-duration: 0.01ms !important;
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
  }
}
```

- [ ] **Step 4: Update `theme.json` to match the CSS system**

Replace palette entries with:

```json
[
  { "slug": "background", "name": "Background", "color": "#f4f2ec" },
  { "slug": "surface", "name": "Surface", "color": "#ffffff" },
  { "slug": "foreground", "name": "Foreground", "color": "#25312a" },
  { "slug": "muted", "name": "Muted", "color": "#69736c" },
  { "slug": "primary", "name": "Grass Green", "color": "#457d53" },
  { "slug": "primary-hover", "name": "Light Grass Green", "color": "#5f966d" },
  { "slug": "primary-soft", "name": "Soft Grass Green", "color": "#e8f0ea" }
]
```

Set button background to `var(--wp--preset--color--primary)`, text to `var(--wp--preset--color--surface)`, radius to `999px`, and weight to `600`.

- [ ] **Step 5: Run the token tests and verify GREEN**

Run:

```powershell
python -m unittest tests.test_theme_contract.ThemeContractTests.test_css_uses_approved_grass_green_tokens tests.test_theme_contract.ThemeContractTests.test_theme_json_exposes_matching_palette -v
```

Expected: `Ran 2 tests` and `OK`.

- [ ] **Step 6: Version-control checkpoint**

Do not run Git commands in the current workspace. Record Task 1 as complete in the execution checklist. If the owner later initializes Git, commit these files with message `style: add green storefront design tokens`.

---

### Task 2: Electro-Inspired Shared Header

**Files:**
- Modify: `tests/test_theme_contract.py`
- Modify: `wp-content/themes/qr-minimal-store/header.php`
- Modify: `wp-content/themes/qr-minimal-store/assets/css/site.css`

**Interfaces:**
- Consumes: `qr_minimal_store_product_search_bar()`, `qr_minimal_store_render_category_links(int $limit)`, WooCommerce cart count, registered `primary` menu.
- Produces: `.utility-nav`, `.header-main`, `.header-main__navigation`, `.header-action`, `.header-action__label`, and `.category-navigation`.

- [ ] **Step 1: Add failing header contract tests**

Add to `ThemeContractTests`:

```python
    def test_header_contains_dynamic_store_actions(self):
        php = self.read("header.php")
        self.assertIn('class="header-main"', php)
        self.assertIn('class="header-main__navigation"', php)
        self.assertIn("qr_minimal_store_product_search_bar();", php)
        self.assertIn("wc_get_cart_url()", php)
        self.assertIn("WC()->cart->get_cart_contents_count()", php)
        self.assertIn("wp_nav_menu(", php)
        self.assertIn("qr_minimal_store_render_category_links( 8 );", php)

    def test_header_has_accessible_navigation_labels(self):
        php = self.read("header.php")
        self.assertIn("Utility navigation", php)
        self.assertIn("Primary navigation", php)
        self.assertIn("Product categories", php)
```

- [ ] **Step 2: Run the header tests and verify RED**

Run:

```powershell
python -m unittest tests.test_theme_contract.ThemeContractTests.test_header_contains_dynamic_store_actions tests.test_theme_contract.ThemeContractTests.test_header_has_accessible_navigation_labels -v
```

Expected: failures for missing `header-main`, navigation classes, and eight-category rendering.

- [ ] **Step 3: Rebuild the header semantic structure**

In `header.php`, preserve the document head and WordPress body hooks. Replace only the `<header>` content with:

```php
<header class="site-header">
	<div class="site-header__utility">
		<div class="container utility-row">
			<p><?php esc_html_e( 'Welcome to our electronics store', 'qr-minimal-store' ); ?></p>
			<nav class="utility-nav" aria-label="<?php esc_attr_e( 'Utility navigation', 'qr-minimal-store' ); ?>">
				<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop', 'qr-minimal-store' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>"><?php esc_html_e( 'My Account', 'qr-minimal-store' ); ?></a>
			</nav>
		</div>
	</div>
	<div class="container header-main">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<span class="brand__name"><?php bloginfo( 'name' ); ?></span><span class="brand__dot" aria-hidden="true">.</span>
		</a>
		<nav class="header-main__navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'qr-minimal-store' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => 'wp_page_menu',
					'menu_class'     => 'site-nav__menu',
				)
			);
			?>
		</nav>
		<div class="header-search"><?php qr_minimal_store_product_search_bar(); ?></div>
		<div class="header-actions">
			<a class="header-action" href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>">
				<span aria-hidden="true">♡</span><span class="header-action__label"><?php esc_html_e( 'Account', 'qr-minimal-store' ); ?></span>
			</a>
			<a class="header-action cart-link" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>">
				<span aria-hidden="true">🛒</span><span class="header-action__label"><?php esc_html_e( 'Cart', 'qr-minimal-store' ); ?></span>
				<?php if ( function_exists( 'WC' ) && WC()->cart ) : ?>
					<span class="cart-count"><?php echo esc_html( (string) WC()->cart->get_cart_contents_count() ); ?></span>
				<?php endif; ?>
			</a>
		</div>
	</div>
	<nav class="site-header__category-strip" aria-label="<?php esc_attr_e( 'Product categories', 'qr-minimal-store' ); ?>">
		<div class="container category-navigation">
			<strong><?php esc_html_e( 'Browse Categories', 'qr-minimal-store' ); ?></strong>
			<?php qr_minimal_store_render_category_links( 8 ); ?>
		</div>
	</nav>
</header>
```

- [ ] **Step 4: Add complete header component styles**

In `site.css`, replace old header selectors with a sticky white header; `32px` utility row; four-column desktop `.header-main`; `1.75rem` bold brand with green dot; compact horizontal menu; search form with a `2px` green pill border; green circular submit control; action links with count badge; and a `48px` category row.

At `max-width: 992px`, use:

```css
.header-main {
  grid-template-columns: auto 1fr;
}

.header-main__navigation,
.header-search {
  grid-column: 1 / -1;
}

.header-actions {
  justify-self: end;
}

.category-navigation {
  align-items: flex-start;
  flex-direction: column;
  padding-block: 0.75rem;
}
```

At `max-width: 640px`, hide `.utility-row p` and `.header-action__label`, allow the primary menu and category list to scroll horizontally, and keep every action at least `44px` square.

- [ ] **Step 5: Run header tests and PHP syntax check**

Run:

```powershell
python -m unittest tests.test_theme_contract -v
php -l wp-content/themes/qr-minimal-store/header.php
```

Expected: all tests pass and PHP reports `No syntax errors detected`.

- [ ] **Step 6: Version-control checkpoint**

Do not run Git commands in the current workspace. Record Task 2 as complete. Future commit message: `style: rebuild storefront header`.

---

### Task 3: Dynamic Homepage Hero and Category Rail

**Files:**
- Modify: `tests/test_theme_contract.py`
- Modify: `wp-content/themes/qr-minimal-store/functions.php`
- Modify: `wp-content/themes/qr-minimal-store/front-page.php`
- Modify: `wp-content/themes/qr-minimal-store/assets/css/site.css`

**Interfaces:**
- Consumes: `qr_minimal_store_get_top_product_categories(int $limit): array`.
- Produces: `qr_minimal_store_render_category_cards(int $limit): void`.

- [ ] **Step 1: Add failing homepage and helper contract tests**

Add:

```python
    def test_category_card_helper_uses_safe_dynamic_term_data(self):
        php = self.read("functions.php")
        self.assertIn("function qr_minimal_store_render_category_cards( $limit = 5 )", php)
        self.assertIn("get_term_link( $term )", php)
        self.assertIn("get_term_meta( $term->term_id, 'thumbnail_id', true )", php)
        self.assertIn("wp_get_attachment_image(", php)
        self.assertIn("esc_html( $term->name )", php)

    def test_homepage_has_electronics_merchandising_hierarchy(self):
        php = self.read("front-page.php")
        for class_name in (
            "hero-campaign",
            "hero-campaign__content",
            "category-rail",
            "benefit-grid",
            "product-tabs-section",
            "brand-strip",
        ):
            self.assertIn(class_name, php)
        self.assertIn("qr_minimal_store_render_category_cards( 5 );", php)
```

- [ ] **Step 2: Run homepage tests and verify RED**

Run:

```powershell
python -m unittest tests.test_theme_contract.ThemeContractTests.test_category_card_helper_uses_safe_dynamic_term_data tests.test_theme_contract.ThemeContractTests.test_homepage_has_electronics_merchandising_hierarchy -v
```

Expected: failures because the category-card helper and new hero classes do not exist.

- [ ] **Step 3: Add the presentation-only category card helper**

Append to `functions.php`:

```php
function qr_minimal_store_render_category_cards( $limit = 5 ) {
	$terms = qr_minimal_store_get_top_product_categories( $limit );
	if ( empty( $terms ) ) {
		return;
	}
	?>
	<ul class="category-rail__list">
		<?php foreach ( $terms as $term ) : ?>
			<?php
			$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
			$term_link    = get_term_link( $term );
			if ( is_wp_error( $term_link ) ) {
				continue;
			}
			?>
			<li class="category-card">
				<a href="<?php echo esc_url( $term_link ); ?>">
					<span class="category-card__media">
						<?php
						if ( $thumbnail_id ) {
							echo wp_get_attachment_image( $thumbnail_id, 'woocommerce_thumbnail', false, array( 'loading' => 'lazy' ) );
						} else {
							echo '<span class="category-card__placeholder" aria-hidden="true"></span>';
						}
						?>
					</span>
					<strong><?php echo esc_html( $term->name ); ?></strong>
					<span>
						<?php
						printf(
							esc_html( _n( '%s product', '%s products', $term->count, 'qr-minimal-store' ) ),
							esc_html( number_format_i18n( $term->count ) )
						);
						?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}
```

- [ ] **Step 4: Restructure the homepage above the existing product sections**

Replace the current hero and benefits opening with a `.hero-campaign` section containing:

- Eyebrow: `Power, security, and smart essentials`.
- Heading: `Technology that keeps your world moving.`
- Supporting copy adapted to the current catalog.
- Primary `Shop now` link using `wc_get_page_permalink( 'shop' )` with the existing fallback.
- Secondary link to `#featured-products`.
- Two CSS-only decorative green product-orb shapes marked `aria-hidden="true"`; no copied image.

Immediately after the hero, render:

```php
<section class="category-rail" aria-labelledby="category-rail-title">
	<div class="container">
		<h2 id="category-rail-title" class="screen-reader-text"><?php esc_html_e( 'Popular categories', 'qr-minimal-store' ); ?></h2>
		<?php qr_minimal_store_render_category_cards( 5 ); ?>
	</div>
</section>
```

Retain the current benefits, hot products, featured products, product tabs, trust strip, and brand strip. Remove the separate `[product_categories]` section because the category rail supersedes it.

- [ ] **Step 5: Add homepage styles**

Implement a wide off-white `.hero-campaign` with a two-column grid, `clamp(2.3rem, 5vw, 4.6rem)` heading, green offer emphasis, pill actions, decorative circles, and a white category rail overlapping the hero by `2.5rem`.

Use five equal category cards on desktop, three on tablet, and a horizontally scrollable snap rail on mobile. Category image areas must use `aspect-ratio: 4 / 3`, `object-fit: contain`, and a soft neutral placeholder.

Restyle benefits and product sections with compact section headings, thin borders, white surfaces, and consistent vertical rhythm. Keep tab JavaScript interfaces unchanged: `[data-product-tabs]`, `[data-tab]`, and `[data-panel]`.

- [ ] **Step 6: Run tests and syntax checks**

Run:

```powershell
python -m unittest tests.test_theme_contract -v
php -l wp-content/themes/qr-minimal-store/functions.php
php -l wp-content/themes/qr-minimal-store/front-page.php
```

Expected: tests pass and both files report no syntax errors.

- [ ] **Step 7: Version-control checkpoint**

Record Task 3 as complete. Future commit message: `style: add dynamic storefront hero and categories`.

---

### Task 4: Product Cards, Shop Archives, and Product Detail

**Files:**
- Modify: `tests/test_theme_contract.py`
- Modify: `wp-content/themes/qr-minimal-store/woocommerce/content-product.php`
- Modify: `wp-content/themes/qr-minimal-store/assets/css/site.css`
- Verify unchanged behavior: `wp-content/themes/qr-minimal-store/woocommerce/archive-product.php`
- Verify unchanged behavior: `wp-content/themes/qr-minimal-store/woocommerce/single-product.php`

**Interfaces:**
- Consumes: standard WooCommerce loop hooks.
- Produces: `.store-product-card`, `.store-product-card__media`, and `.store-product-card__body`.

- [ ] **Step 1: Add failing WooCommerce card contract tests**

Add:

```python
    def test_product_card_preserves_required_woocommerce_hooks(self):
        php = self.read("woocommerce/content-product.php")
        hooks = (
            "woocommerce_before_shop_loop_item",
            "woocommerce_before_shop_loop_item_title",
            "woocommerce_shop_loop_item_title",
            "woocommerce_after_shop_loop_item_title",
            "woocommerce_after_shop_loop_item",
        )
        for hook in hooks:
            self.assertIn(hook, php)
        self.assertIn("store-product-card", php)
        self.assertIn("store-product-card__media", php)
        self.assertIn("store-product-card__body", php)

    def test_css_styles_core_woocommerce_surfaces(self):
        css = self.read("assets/css/site.css")
        selectors = (
            ".woocommerce ul.products",
            ".woocommerce div.product",
            ".woocommerce .woocommerce-ordering",
            ".woocommerce nav.woocommerce-pagination",
            ".woocommerce span.onsale",
        )
        for selector in selectors:
            self.assertIn(selector, css)
```

- [ ] **Step 2: Run the product tests and verify RED**

Run:

```powershell
python -m unittest tests.test_theme_contract.ThemeContractTests.test_product_card_preserves_required_woocommerce_hooks tests.test_theme_contract.ThemeContractTests.test_css_styles_core_woocommerce_surfaces -v
```

Expected: card class and one or more WooCommerce surface assertions fail.

- [ ] **Step 3: Rename only theme-owned card wrappers**

In `woocommerce/content-product.php`, change:

```php
<li <?php wc_product_class( 'store-product-card', $product ); ?>>
	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>
	<div class="store-product-card__media">
		<?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?>
	</div>
	<div class="store-product-card__body">
		<?php do_action( 'woocommerce_shop_loop_item_title' ); ?>
		<?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>
		<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
	</div>
</li>
```

Do not reorder or remove hooks.

- [ ] **Step 4: Implement archive and card styling**

In `site.css`:

- Reset WooCommerce product grid floats and widths with a CSS grid while respecting WooCommerce column classes.
- Use four columns above `992px`, three from `769px` to `992px`, two below `768px`, and one below `420px`.
- Give `.store-product-card` a white surface, subtle right separator, stable flex column, `aspect-ratio: 1 / 1` image region, and hover shadow.
- Clamp product titles to two lines.
- Use green prices, rounded sale badges, and full-width green add-to-cart buttons.
- Style result count, ordering control, breadcrumbs, pagination, and notices.

- [ ] **Step 5: Implement product-detail styling**

Style standard WooCommerce single-product selectors without template replacement:

```css
.woocommerce div.product {
  display: flow-root;
  background: var(--qr-surface);
  border: 1px solid var(--qr-border);
  border-radius: calc(var(--qr-radius) + 4px);
  padding: clamp(1rem, 3vw, 2rem);
}

.woocommerce div.product .product_title {
  color: var(--qr-text);
  font-size: clamp(1.75rem, 3vw, 2.65rem);
  line-height: 1.15;
}

.woocommerce div.product p.price,
.woocommerce div.product span.price {
  color: var(--qr-primary);
  font-size: 1.5rem;
  font-weight: 700;
}

.woocommerce div.product form.cart {
  align-items: stretch;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}
```

Add compatible styles for gallery thumbnails, quantity controls, variation tables, tabs, metadata, related products, stock states, and mobile stacking. Do not remove WooCommerce gallery classes or scripts.

- [ ] **Step 6: Run tests and PHP syntax checks**

Run:

```powershell
python -m unittest tests.test_theme_contract -v
php -l wp-content/themes/qr-minimal-store/woocommerce/content-product.php
php -l wp-content/themes/qr-minimal-store/woocommerce/archive-product.php
php -l wp-content/themes/qr-minimal-store/woocommerce/single-product.php
```

Expected: all tests pass and all templates report no syntax errors.

- [ ] **Step 7: Version-control checkpoint**

Record Task 4 as complete. Future commit message: `style: redesign WooCommerce catalog and products`.

---

### Task 5: Cart, Checkout, Account, and Footer

**Files:**
- Modify: `tests/test_theme_contract.py`
- Modify: `wp-content/themes/qr-minimal-store/footer.php`
- Modify: `wp-content/themes/qr-minimal-store/assets/css/site.css`

**Interfaces:**
- Consumes: WooCommerce-generated cart, checkout, notices, account navigation, and registered footer menu.
- Produces: `.footer-brand`, `.footer-column`, and dynamic footer category output using `qr_minimal_store_render_category_links(3)`.

- [ ] **Step 1: Add failing commerce-surface and footer tests**

Add:

```python
    def test_css_styles_transactional_woocommerce_pages(self):
        css = self.read("assets/css/site.css")
        selectors = (
            ".woocommerce-cart",
            ".woocommerce-checkout",
            ".woocommerce-account",
            ".woocommerce-error",
            ".woocommerce-message",
            "#order_review",
            ".woocommerce-MyAccount-navigation",
        )
        for selector in selectors:
            self.assertIn(selector, css)

    def test_footer_uses_dynamic_categories_and_registered_menu(self):
        php = self.read("footer.php")
        self.assertIn("'theme_location' => 'footer'", php)
        self.assertIn("qr_minimal_store_render_category_links( 3 );", php)
        self.assertIn('class="footer-brand"', php)
        self.assertNotIn("/product-category/ups/", php)
```

- [ ] **Step 2: Run tests and verify RED**

Run:

```powershell
python -m unittest tests.test_theme_contract.ThemeContractTests.test_css_styles_transactional_woocommerce_pages tests.test_theme_contract.ThemeContractTests.test_footer_uses_dynamic_categories_and_registered_menu -v
```

Expected: missing commerce selectors and hardcoded footer category paths cause failures.

- [ ] **Step 3: Make footer categories dynamic**

Retain the footer contact bar and footer menu. Replace the hardcoded category links with:

```php
<div class="footer-column">
	<h3><?php esc_html_e( 'Shop Categories', 'qr-minimal-store' ); ?></h3>
	<?php qr_minimal_store_render_category_links( 3 ); ?>
</div>
```

Apply `class="footer-brand"` to the identity column and `class="footer-column"` to each remaining column. Keep all existing text escaped and URLs generated through WordPress helpers.

- [ ] **Step 4: Style cart and checkout without overriding templates**

Add CSS for:

- White cart table and totals panels with visible borders.
- Responsive cart product rows and horizontally scrollable fallback below `768px`.
- Green coupon and update controls with a clear disabled state.
- Two-column checkout layout above `992px`, single-column below.
- Consistent labels, inputs, selects, checkboxes, payment methods, privacy text, and order-review totals.
- Green success notices, red error notices, and neutral info notices with sufficient contrast.
- No `display: none` rules targeting WooCommerce notices, validation, payment boxes, or place-order controls.

- [ ] **Step 5: Style customer account and footer**

Use a `260px / 1fr` account grid on desktop and stacked navigation on mobile. Make account navigation a bordered white panel with green current-item state. Style orders, addresses, and forms consistently.

Make the footer white with a soft green contact bar, four columns on desktop, two on tablet, and one on mobile. Reuse `.category-strip__list` in the footer with vertical alignment scoped under `.site-footer`.

- [ ] **Step 6: Run tests and syntax check**

Run:

```powershell
python -m unittest tests.test_theme_contract -v
php -l wp-content/themes/qr-minimal-store/footer.php
```

Expected: all tests pass and footer PHP has no syntax errors.

- [ ] **Step 7: Version-control checkpoint**

Record Task 5 as complete. Future commit message: `style: finish transactional pages and footer`.

---

### Task 6: Responsive, Accessibility, and Full Store Verification

**Files:**
- Modify: `tests/test_theme_contract.py`
- Modify if verification exposes defects: `wp-content/themes/qr-minimal-store/assets/css/site.css`
- Modify if verification exposes defects: theme-owned PHP templates only.

**Interfaces:**
- Consumes: all previous task outputs.
- Produces: a verified responsive theme with no syntax errors or structural regressions.

- [ ] **Step 1: Add final accessibility and boundary tests**

Add:

```python
    def test_styles_include_mobile_and_reduced_motion_contracts(self):
        css = self.read("assets/css/site.css")
        self.assertIn("@media (max-width: 992px)", css)
        self.assertIn("@media (max-width: 640px)", css)
        self.assertIn("@media (prefers-reduced-motion: reduce)", css)
        self.assertIn(":focus-visible", css)

    def test_product_tabs_keep_accessibility_contract(self):
        php = self.read("front-page.php")
        self.assertIn('role="tablist"', php)
        self.assertIn('role="tab"', php)
        self.assertIn('role="tabpanel"', php)
        self.assertIn('aria-selected="true"', php)
        self.assertIn('aria-controls="panel-new"', php)

    def test_theme_does_not_reference_third_party_brand_assets(self):
        combined = "\n".join(
            self.read(path)
            for path in (
                "header.php",
                "front-page.php",
                "footer.php",
                "assets/css/site.css",
            )
        ).lower()
        self.assertNotIn("electro.", combined)
        self.assertNotIn("#fed700", combined)
```

- [ ] **Step 2: Run final contract tests and verify RED only if a requirement is missing**

Run:

```powershell
python -m unittest tests.test_theme_contract -v
```

Expected before final fixes: any missing mobile, focus, reduced-motion, or accessibility requirement fails with a specific assertion. If all pass because earlier tasks already supplied the behavior, document that these are regression assertions over completed functionality.

- [ ] **Step 3: Apply minimal fixes for any failing final contract**

Change only the selector or semantic attribute named by each failure. Do not add new homepage sections, business logic, dependencies, or template overrides.

- [ ] **Step 4: Run PHP syntax verification across project-owned theme files**

Run:

```powershell
Get-ChildItem wp-content/themes/qr-minimal-store -Recurse -Filter *.php |
  ForEach-Object { php -l $_.FullName }
```

Expected: every line reports `No syntax errors detected`; command exits successfully.

- [ ] **Step 5: Run JSON and test verification**

Run:

```powershell
python -m json.tool wp-content/themes/qr-minimal-store/theme.json *> $null
python -m unittest tests.test_theme_contract -v
```

Expected: JSON command exits `0`; all contract tests pass.

- [ ] **Step 6: Smoke-test dynamic storefront routes**

With Apache and MySQL running in XAMPP, open and verify:

- `/store/`: hero, category rail, benefits, product sections, product tabs, and footer render without overflow.
- `/store/shop/`: sorting, result count, product cards, pagination, and add-to-cart work.
- One category archive: title, description, product grid, and empty state remain usable.
- One simple product: gallery, price, stock, quantity, add-to-cart, tabs, and related products work.
- One variable product if available: selectors update price/availability and add-to-cart remains disabled until valid choices are made.
- `/store/cart/`: quantity update, remove, coupon, totals, and empty-cart state work.
- `/store/checkout/`: labels, validation errors, payment method area, order review, and place-order control remain visible.
- `/store/my-account/`: logged-out form and logged-in navigation/content render responsively.

Expected: no PHP warnings/notices in the page, no browser console errors introduced by the theme, and no hidden WooCommerce controls.

- [ ] **Step 7: Check responsive behavior**

At viewport widths `1440`, `1024`, `768`, `390`, and `320` pixels, verify:

- No horizontal page overflow.
- Header search and actions remain reachable.
- Category rail scrolls or wraps as designed.
- Product grid follows `4 / 3 / 2 / 1` density.
- Product detail, cart, checkout, account, and footer stack cleanly.
- Keyboard focus remains visible on links, tabs, controls, and buttons.

- [ ] **Step 8: Confirm modification boundaries**

List changed files and verify each is under:

- `wp-content/themes/qr-minimal-store/`
- `tests/`
- `docs/superpowers/`

Expected: no changed file belongs to WordPress core, `wp-content/plugins/woocommerce`, or another third-party package.

- [ ] **Step 9: Version-control checkpoint**

Record Task 6 as complete. If Git is later initialized, commit remaining verification fixes with message `test: verify green WooCommerce storefront`.
