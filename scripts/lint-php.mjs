// PHP 8.1 syntax check without a PHP binary (CI-friendly): parses every theme
// PHP file with glayzzle/php-parser and fails on the first syntax error.
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import PhpParser from 'php-parser';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const parser = new PhpParser.Engine({
  parser: { php8: true, version: '8.1', suppressErrors: false, extractDoc: false },
  ast: { withPositions: false },
});

const files = [];
(function walk(dir) {
  for (const name of readdirSync(dir)) {
    if (name === 'node_modules' || name.startsWith('.')) continue;
    const path = join(dir, name);
    if (statSync(path).isDirectory()) walk(path);
    else if (name.endsWith('.php')) files.push(path);
  }
})(root);

let failed = 0;
for (const file of files) {
  try {
    parser.parseCode(readFileSync(file, 'utf8'), file);
  } catch (error) {
    failed++;
    console.error(`✗ ${relative(root, file)}: ${error.message}`);
  }
}
console.log(`${files.length - failed}/${files.length} PHP files parsed cleanly.`);
process.exit(failed ? 1 : 0);
