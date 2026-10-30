// Points playground/blueprint.json at the theme zip of the current commit.
// raw.githubusercontent.com caches branch URLs (and ignores query strings), so a
// commit-SHA URL is the only way to guarantee visitors get the latest zip.
// Usage: commit dist/bayt-kahraba.zip first, then `npm run pin`, commit + push.
import { execSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const file = join(root, 'playground/blueprint.json');
const sha = execSync('git log -n 1 --format=%H -- dist/bayt-kahraba.zip', { cwd: root }).toString().trim();
if (!sha) {
  console.error('dist/bayt-kahraba.zip is not committed yet — run `npm run zip` and commit it first.');
  process.exit(1);
}
const blueprint = readFileSync(file, 'utf8');
const updated = blueprint.replace(/(raw\.githubusercontent\.com\/[^/]+\/[^/]+\/)[^/]+(\/dist\/bayt-kahraba\.zip)[^"]*/, `$1${sha}$2`);
writeFileSync(file, updated);
console.log(updated === blueprint ? `Already pinned to ${sha}` : `Blueprint pinned to ${sha}`);
