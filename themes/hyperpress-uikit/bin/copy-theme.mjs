import { cp, mkdir, rm } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const themeDir = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const outDir = resolve(themeDir, '../../dist/themes/hyperpress-uikit');
const entries = ['style.css', 'functions.php', 'index.php', 'inc', 'views', 'composer.json', 'composer.lock'];

await mkdir(outDir, { recursive: true });
for (const entry of entries) {
	const target = join(outDir, entry);
	await rm(target, { recursive: true, force: true });
	await cp(join(themeDir, entry), target, { recursive: true });
}
