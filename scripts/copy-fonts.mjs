// Copies self-hosted IBM Plex Sans Arabic woff2 subsets from @fontsource into assets/fonts.
import { copyFileSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const src = join(root, 'node_modules/@fontsource/ibm-plex-sans-arabic/files');
const out = join(root, 'assets/fonts');
mkdirSync(out, { recursive: true });

const wanted = [
  ['arabic', 400], ['arabic', 500], ['arabic', 600], ['arabic', 700],
  ['latin', 400], ['latin', 700],
];

for (const [subset, weight] of wanted) {
  const from = join(src, `ibm-plex-sans-arabic-${subset}-${weight}-normal.woff2`);
  const to = join(out, `ibm-plex-sans-arabic-${subset}-${weight}.woff2`);
  if (!existsSync(from)) {
    console.error(`Missing font file: ${from}`);
    process.exitCode = 1;
    continue;
  }
  copyFileSync(from, to);
  console.log(`✓ ${subset} ${weight}`);
}
