// Copies the self-hosted woff2 subsets used by the "ضوء" design system into assets/fonts:
//   IBM Plex Sans Arabic (body, 400/500/700) · Alexandria (headings, 600/800).
import { copyFileSync, mkdirSync, existsSync, readdirSync, unlinkSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const out = join(root, 'assets/fonts');
mkdirSync(out, { recursive: true });

const wanted = [
  ['ibm-plex-sans-arabic', 'arabic', 400], ['ibm-plex-sans-arabic', 'arabic', 500], ['ibm-plex-sans-arabic', 'arabic', 700],
  ['ibm-plex-sans-arabic', 'latin', 400], ['ibm-plex-sans-arabic', 'latin', 700],
  ['alexandria', 'arabic', 600], ['alexandria', 'arabic', 800],
  ['alexandria', 'latin', 600], ['alexandria', 'latin', 800],
];
const keep = new Set(wanted.map(([f, s, w]) => `${f}-${s}-${w}.woff2`));

// Remove fonts the design no longer uses so they don't ship in the theme zip.
for (const file of readdirSync(out)) {
  if (file.endsWith('.woff2') && !keep.has(file)) unlinkSync(join(out, file));
}

for (const [family, subset, weight] of wanted) {
  const from = join(root, `node_modules/@fontsource/${family}/files/${family}-${subset}-${weight}-normal.woff2`);
  const to = join(out, `${family}-${subset}-${weight}.woff2`);
  if (!existsSync(from)) {
    console.error(`Missing font file: ${from}`);
    process.exitCode = 1;
    continue;
  }
  copyFileSync(from, to);
  console.log(`✓ ${family} ${subset} ${weight}`);
}
