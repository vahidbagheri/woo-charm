"""Optimize the existing Dana fonts without dropping characters or shaping features.

Build dependency: fonttools[woff]. Original font files remain untouched.
"""
import hashlib
import json
from pathlib import Path
from fontTools import subset
from fontTools.ttLib import TTFont

theme = Path(__file__).resolve().parents[1]
target = theme / 'assets/fonts/dana/display'
target.mkdir(exist_ok=True)
records = {}
for weight in ('Regular', 'Medium', 'Bold'):
    source = theme / f'assets/fonts/dana/DanaFaNum-{weight}.woff2'
    font = TTFont(source, recalcTimestamp=False)
    characters = set(font.getBestCmap())
    options = subset.Options()
    options.hinting = False
    options.layout_features = ['*']
    options.name_IDs = ['*']
    processor = subset.Subsetter(options=options)
    processor.populate(glyphs=font.getGlyphOrder())
    processor.subset(font)
    assert set(font.getBestCmap()) == characters
    font.flavor = 'woff2'
    output = target / source.name
    font.save(output)
    records[weight] = {'source_sha256': hashlib.sha256(source.read_bytes()).hexdigest(), 'path': output.relative_to(theme).as_posix(), 'before_bytes': source.stat().st_size, 'after_bytes': output.stat().st_size}
(theme / 'config/launch-display-fonts.json').write_text(json.dumps(records, indent=2) + '\n', encoding='utf-8')
print(json.dumps(records))
