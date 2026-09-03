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


if __name__ == "__main__":
    unittest.main()
