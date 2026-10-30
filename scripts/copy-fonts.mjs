// Copies the self-hosted woff2 subsets used by the "Volt" design system into assets/fonts:
//   IBM Plex Sans Arabic (body) · Alexandria (display) · IBM Plex Mono (spec readouts).
import { copyFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const out = join(root, 'assets/fonts');
mkdirSync(out, { recursive: true });

const wanted = [
  ['ibm-plex-sans-arabic', 'arabic', 400], ['ibm-plex-sans-arabic', 'arabic', 500], ['ibm-plex-sans-arabic', 'arabic', 600], ['ibm-plex-sans-arabic', 'arabic', 700],
  ['ibm-plex-sans-arabic', 'latin', 400], ['ibm-plex-sans-arabic', 'latin', 700],
  ['alexandria', 'arabic', 600], ['alexandria', 'arabic', 800], ['alexandria', 'arabic', 900],
  ['alexandria', 'latin', 800],
  ['ibm-plex-mono', 'latin', 400], ['ibm-plex-mono', 'latin', 600],
];

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
