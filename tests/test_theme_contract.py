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


if __name__ == "__main__":
    unittest.main()
