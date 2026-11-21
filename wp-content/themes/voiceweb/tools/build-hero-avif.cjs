/* Build responsive AVIF derivatives; keep the original banner and WebP fallback. */
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const sharp = require(process.env.VOICEWEB_SHARP_PATH || 'sharp');
const theme = path.resolve(__dirname, '..');
(async () => {
  const source = path.join(theme, 'assets/images/avan-catalog-hero.webp');
  const hash = crypto.createHash('sha256').update(fs.readFileSync(source)).digest('hex');
  const record = { source_name: path.basename(source), source_sha256: hash, images: [] };
  for (const width of [480, 576, 672, 768, 864]) {
    const relative = `assets/images/avan-hero-${hash.slice(0, 8)}-${width}.avif`;
    const info = await sharp(source).resize({ width, withoutEnlargement: true }).avif({ quality: 45, effort: 6 }).toFile(path.join(theme, relative));
    record.images.push({ width: info.width, height: info.height, path: relative, bytes: info.size });
  }
  fs.writeFileSync(path.join(theme, 'config/launch-hero-media.json'), JSON.stringify(record, null, 2) + '\n');
  console.log(JSON.stringify(record));
})().catch(error => { console.error(error); process.exitCode = 1; });
