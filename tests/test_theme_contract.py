import json
import pathlib
import re
import unittest


ROOT = pathlib.Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content" / "themes" / "qr-minimal-store"


class ThemeContractTests(unittest.TestCase):
    def read(self, relative_path):
        return (THEME / relative_path).read_text(encoding="utf-8")

    def css_rule(self, css, selector):
        rule = re.search(
            rf"{re.escape(selector)}\s*\{{(?P<body>[^}}]*)\}}",
            css,
        )
        self.assertIsNotNone(rule, f"Missing CSS rule for {selector}")
        return rule.group("body")

    def css_declarations(self, css, selector):
        declarations = {}
        css = re.sub(r"/\*.*?\*/", "", css, flags=re.DOTALL)
        for selectors, body in re.findall(r"([^{}]+)\{([^{}]*)\}", css):
            selector_list = [item.strip() for item in selectors.split(",")]
            if selector not in selector_list:
                continue
            for name, value in re.findall(r"([\w-]+)\s*:\s*([^;]+);", body):
                declarations[name.strip()] = value.strip()
        return declarations

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

    def test_mobile_category_list_is_a_constrained_scroller(self):
        css = self.read("assets/css/site.css").lower()
        mobile_css = css.split("@media (max-width: 640px)", 1)[1]
        category_rule = re.search(
            r"\.category-strip__list\s*\{(?P<body>[^}]*)\}",
            mobile_css,
        )

        self.assertIsNotNone(category_rule)
        declarations = category_rule.group("body")
        self.assertRegex(declarations, r"width:\s*100%")
        self.assertRegex(declarations, r"overflow-x:\s*auto")
        self.assertRegex(declarations, r"flex-wrap:\s*nowrap")
        self.assertNotRegex(declarations, r"width:\s*max-content")

    def test_category_card_helper_uses_safe_dynamic_term_data(self):
        php = self.read("functions.php")
        self.assertIn("function qr_minimal_store_render_category_cards( $limit = 5 )", php)
        self.assertIn("get_term_link( $term )", php)
        self.assertIn("is_wp_error( $term_link )", php)
        self.assertIn("esc_url( $term_link )", php)
        self.assertIn("get_term_meta( $term->term_id, 'thumbnail_id', true )", php)
        self.assertIn("wp_get_attachment_image(", php)
        self.assertIn("esc_html( $term->name )", php)
        self.assertIn('class="category-card__placeholder" aria-hidden="true"', php)
        self.assertIn("_n( '%s product', '%s products', $term->count", php)
        self.assertIn("number_format_i18n( $term->count )", php)

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
        self.assertNotIn("[product_categories", php)
        self.assertIn('aria-labelledby="category-rail-title"', php)
        self.assertIn('id="category-rail-title" class="screen-reader-text"', php)
        self.assertIn('class="hero-campaign__visual" aria-hidden="true"', php)
        self.assertIn('role="tablist"', php)
        for tab_name in ("new", "top", "best"):
            self.assertRegex(
                php,
                rf'<button[^>]*role="tab"[^>]*data-tab="{tab_name}"',
            )
            self.assertRegex(
                php,
                rf'<div[^>]*role="tabpanel"[^>]*data-panel="{tab_name}"',
            )

    def test_category_card_hover_uses_primary_hover_token(self):
        css = self.read("assets/css/site.css").lower()
        declarations = self.css_rule(css, ".category-card a:hover")
        self.assertRegex(declarations, r"color:\s*var\(--qr-primary-hover\)")

    def test_category_rail_has_responsive_card_layouts(self):
        css = self.read("assets/css/site.css").lower()
        desktop_css, responsive_css = css.split("@media (max-width: 992px)", 1)
        tablet_css, mobile_css = responsive_css.split("@media (max-width: 640px)", 1)

        desktop_list = self.css_rule(desktop_css, ".category-rail__list")
        self.assertRegex(
            desktop_list,
            r"grid-template-columns:\s*repeat\(5,\s*minmax\(0,\s*1fr\)\)",
        )
        self.assertRegex(
            self.css_rule(desktop_css, ".category-card__media"),
            r"aspect-ratio:\s*4\s*/\s*3",
        )
        self.assertRegex(
            self.css_rule(desktop_css, ".category-card__media img"),
            r"object-fit:\s*contain",
        )

        tablet_list = self.css_rule(tablet_css, ".category-rail__list")
        self.assertRegex(
            tablet_list,
            r"grid-template-columns:\s*repeat\(3,\s*minmax\(0,\s*1fr\)\)",
        )

        mobile_list = self.css_rule(mobile_css, ".category-rail__list")
        self.assertRegex(mobile_list, r"display:\s*flex")
        self.assertRegex(mobile_list, r"width:\s*100%")
        self.assertRegex(mobile_list, r"overflow-x:\s*auto")
        self.assertRegex(mobile_list, r"scroll-snap-type:\s*x\s+mandatory")
        self.assertRegex(
            self.css_rule(mobile_css, ".category-card"),
            r"scroll-snap-align:\s*start",
        )

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
        hook_calls = [f"do_action( '{hook}' )" for hook in hooks]
        hook_positions = [php.index(call) for call in hook_calls]
        self.assertEqual(hook_positions, sorted(hook_positions))
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

    def test_transactional_layouts_honor_responsive_contracts(self):
        css = self.read("assets/css/site.css")
        transactional_css = css.split(
            "/* WooCommerce cart, checkout, and customer account */",
            1,
        )[1]
        desktop_css, tablet_and_below = transactional_css.split(
            "@media (max-width: 992px)",
            1,
        )
        tablet_css, mobile_and_below = tablet_and_below.split(
            "@media (max-width: 768px)",
            1,
        )
        mobile_css, narrow_css = mobile_and_below.split(
            "@media (max-width: 640px)",
            1,
        )

        checkout_selector = ".woocommerce-checkout form.checkout"
        checkout_desktop = self.css_declarations(
            desktop_css,
            checkout_selector,
        )
        checkout_tablet = self.css_declarations(
            tablet_css,
            checkout_selector,
        )
        self.assertEqual("grid", checkout_desktop.get("display"))
        self.assertEqual(
            "minmax(0, 1fr) minmax(320px, 0.8fr)",
            checkout_desktop.get("grid-template-columns"),
        )
        self.assertEqual(
            "1fr",
            checkout_tablet.get("grid-template-columns"),
        )

        account_selector = ".woocommerce-account.logged-in .woocommerce"
        self.assertEqual(
            "260px minmax(0, 1fr)",
            self.css_declarations(
                desktop_css,
                account_selector,
            ).get("grid-template-columns"),
        )
        self.assertEqual(
            "1fr",
            self.css_declarations(
                mobile_css,
                account_selector,
            ).get("grid-template-columns"),
        )

        cart = self.css_declarations(
            desktop_css,
            ".woocommerce-cart .woocommerce-cart-form",
        )
        self.assertEqual("auto", cart.get("overflow-x"))

        disabled_selectors = (
            ".woocommerce-cart table.cart td.actions .button:disabled",
            ".woocommerce-cart table.cart td.actions .button:disabled[disabled]",
            ".woocommerce-cart table.cart td.actions .button.disabled",
        )
        for selector in disabled_selectors:
            declarations = self.css_declarations(desktop_css, selector)
            self.assertEqual(
                "var(--qr-border)",
                declarations.get("background"),
            )
            self.assertEqual(
                "var(--qr-muted)",
                declarations.get("color"),
            )
            self.assertEqual("not-allowed", declarations.get("cursor"))
            self.assertEqual("1", declarations.get("opacity"))

        self.assertEqual(
            "repeat(4, minmax(0, 1fr))",
            self.css_declarations(
                css,
                ".site-footer__grid--four",
            ).get("grid-template-columns"),
        )
        footer_selector = ".site-footer .site-footer__grid--four"
        self.assertEqual(
            "repeat(2, minmax(0, 1fr))",
            self.css_declarations(
                tablet_css,
                footer_selector,
            ).get("grid-template-columns"),
        )
        self.assertEqual(
            "1fr",
            self.css_declarations(
                narrow_css,
                footer_selector,
            ).get("grid-template-columns"),
        )

    def test_transactional_controls_and_feedback_are_not_hidden(self):
        css = re.sub(
            r"/\*.*?\*/",
            "",
            self.read("assets/css/site.css"),
            flags=re.DOTALL,
        )
        protected_targets = (
            ".woocommerce-error",
            ".woocommerce-info",
            ".woocommerce-message",
            ".woocommerce-notice",
            ".woocommerce-notices-wrapper",
            ".payment_box",
            ".place-order",
            "#place_order",
            ".woocommerce-invalid",
        )
        for selectors, body in re.findall(r"([^{}]+)\{([^{}]*)\}", css):
            declarations = {
                name.strip(): value.strip().lower()
                for name, value in re.findall(
                    r"([\w-]+)\s*:\s*([^;]+);",
                    body,
                )
            }
            if declarations.get("display") != "none":
                continue
            normalized_selectors = selectors.lower()
            for target in protected_targets:
                self.assertNotIn(
                    target.lower(),
                    normalized_selectors,
                    f"{target} must not be hidden by {selectors.strip()}",
                )

    def test_footer_uses_dynamic_categories_and_registered_menu(self):
        php = self.read("footer.php")
        self.assertIn("'theme_location' => 'footer'", php)
        self.assertIn("'fallback_cb'    => 'wp_page_menu'", php)
        self.assertIn("qr_minimal_store_render_category_links( 3 );", php)
        self.assertIn('class="footer-brand"', php)
        self.assertEqual(3, php.count('class="footer-column"'))
        self.assertNotIn("/product-category/", php)

    def test_product_grid_honors_column_classes_and_responsive_limits(self):
        css = self.read("assets/css/site.css")
        catalog_css = css.split("/* WooCommerce catalog and product cards */", 1)[1]
        desktop_css, tablet_and_below = catalog_css.split(
            "@media (max-width: 992px)",
            1,
        )
        tablet_css, mobile_and_below = tablet_and_below.split(
            "@media (max-width: 768px)",
            1,
        )
        mobile_css, narrow_css = mobile_and_below.split(
            "@media (max-width: 420px)",
            1,
        )

        desktop_columns = (1, 2, 3, 4, 5, 6)
        tablet_columns = (1, 2, 3, 3, 3, 3)
        mobile_columns = (1, 2, 2, 2, 2, 2)
        for requested, desktop, tablet, mobile in zip(
            range(1, 7),
            desktop_columns,
            tablet_columns,
            mobile_columns,
        ):
            selector = f".woocommerce ul.products.columns-{requested}"
            self.assertEqual(
                f"repeat({desktop}, minmax(0, 1fr))",
                self.css_declarations(desktop_css, selector).get(
                    "grid-template-columns",
                ),
            )
            self.assertEqual(
                f"repeat({tablet}, minmax(0, 1fr))",
                self.css_declarations(tablet_css, selector).get(
                    "grid-template-columns",
                ),
            )
            self.assertEqual(
                f"repeat({mobile}, minmax(0, 1fr))",
                self.css_declarations(mobile_css, selector).get(
                    "grid-template-columns",
                ),
            )
            self.assertEqual(
                "1fr",
                self.css_declarations(narrow_css, selector).get(
                    "grid-template-columns",
                ),
            )

        self.assertEqual(
            "repeat(4, minmax(0, 1fr))",
            self.css_declarations(
                desktop_css,
                ".woocommerce ul.products",
            ).get("grid-template-columns"),
        )
        self.assertEqual(
            "repeat(3, minmax(0, 1fr))",
            self.css_declarations(
                tablet_css,
                ".woocommerce ul.products",
            ).get("grid-template-columns"),
        )
        self.assertEqual(
            "repeat(2, minmax(0, 1fr))",
            self.css_declarations(
                mobile_css,
                ".woocommerce ul.products",
            ).get("grid-template-columns"),
        )
        self.assertEqual(
            "1fr",
            self.css_declarations(
                narrow_css,
                ".woocommerce ul.products",
            ).get("grid-template-columns"),
        )

    def test_product_card_css_has_stable_media_and_title_clamp(self):
        css = self.read("assets/css/site.css")
        media = self.css_declarations(css, ".store-product-card__media")
        title = self.css_declarations(
            css,
            ".woocommerce ul.products li.product .woocommerce-loop-product__title",
        )
        self.assertEqual("1 / 1", media.get("aspect-ratio"))
        self.assertEqual("hidden", media.get("overflow"))
        self.assertEqual("-webkit-box", title.get("display"))
        self.assertEqual("vertical", title.get("-webkit-box-orient"))
        self.assertEqual("2", title.get("-webkit-line-clamp"))
        self.assertEqual("hidden", title.get("overflow"))

    def test_product_detail_css_uses_standard_woocommerce_surfaces(self):
        css = self.read("assets/css/site.css")
        product = self.css_declarations(css, ".woocommerce div.product")
        title = self.css_declarations(
            css,
            ".woocommerce div.product .product_title",
        )
        cart = self.css_declarations(
            css,
            ".woocommerce div.product form.cart",
        )
        gallery = self.css_declarations(
            css,
            ".woocommerce div.product div.images .woocommerce-product-gallery__wrapper",
        )
        tabs = self.css_declarations(
            css,
            ".woocommerce div.product .woocommerce-tabs ul.tabs",
        )
        self.assertEqual("flow-root", product.get("display"))
        self.assertEqual("var(--qr-surface)", product.get("background"))
        self.assertEqual("var(--qr-text)", title.get("color"))
        self.assertEqual("flex", cart.get("display"))
        self.assertEqual("wrap", cart.get("flex-wrap"))
        self.assertEqual("hidden", gallery.get("overflow"))
        self.assertEqual("flex", tabs.get("display"))

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


if __name__ == "__main__":
    unittest.main()
