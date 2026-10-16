// Copy the built UIkit stylesheet from @hyper/uikit-tokens into the plugin's (git-ignored) dist folder.
import { copyFileSync, existsSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const pluginRoot = join(dirname(fileURLToPath(import.meta.url)), '..');
const repoRoot = join(pluginRoot, '..', '..');
const source = join(repoRoot, 'dist', 'libs', 'uikit-tokens', 'index.css');
const target = join(pluginRoot, 'dist', 'uikit.css');

if (!existsSync(source)) {
  console.error(`Missing ${source}. Run "nx build @hyper/uikit-tokens" first.`);
  process.exit(1);
}

mkdirSync(dirname(target), { recursive: true });
copyFileSync(source, target);
console.log(`Copied ${source} to ${target}`);
