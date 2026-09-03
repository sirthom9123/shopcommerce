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
