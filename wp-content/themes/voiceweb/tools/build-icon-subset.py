"""Build a small first-load Iconly font from a saved public HTML page.

Requires fonttools[woff] as a build tool only. Original font remains the fallback
for every other icon, including future selections in ACF.
"""
import hashlib
import html
import json
import re
import sys
from pathlib import Path
from fontTools import subset
from fontTools.ttLib import TTFont

theme = Path(__file__).resolve().parents[1]
source = theme.parents[1] / "plugins/acf-iconly-picker/assets/fonts/iconly.woff2"
page = html.unescape(Path(sys.argv[1]).read_text(encoding="utf-8"))
points = {ord(char) for char in page if 0xE000 <= ord(char) <= 0xF8FF}
points.update(int(code, 16) for code in re.findall(r"\\u([eEfF][0-9a-fA-F]{3})", page))
font = TTFont(source)
points.intersection_update(font.getBestCmap())
assert points, "No real Iconly glyphs found in the supplied HTML."
options = subset.Options()
options.flavor = "woff2"
options.layout_features = ["*"]
subsetter = subset.Subsetter(options=options)
subsetter.populate(unicodes=points)
subsetter.subset(font)
font.flavor = "woff2"
target = theme / "assets/fonts/iconly"
target.mkdir(exist_ok=True)
font.save(target / "storefront.woff2")
metadata = {"source_sha256": hashlib.sha256(source.read_bytes()).hexdigest(), "unicode_range": ",".join(f"U+{code:X}" for code in sorted(points))}
(target / "storefront.json").write_text(json.dumps(metadata, indent=2) + "\n", encoding="utf-8")
print(json.dumps({"glyphs": len(points), "before_bytes": source.stat().st_size, "after_bytes": (target / "storefront.woff2").stat().st_size}))
