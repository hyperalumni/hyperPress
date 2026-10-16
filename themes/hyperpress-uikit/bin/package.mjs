import { mkdir, readdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { zipSync } from 'fflate';

const THEME_SLUG = 'hyperpress-uikit';
const MANIFEST = '.vite/manifest.json';
// Local-time 1980-01-01 (the zip epoch), so identical inputs give identical bytes in any timezone.
const ZIP_EPOCH = new Date(1980, 0, 1);
const EXCLUDED_ROOT_ENTRIES = new Set(['.vite-dev', 'tests', 'phpunit.xml.dist', 'phpcs.xml.dist']);

function isExcludedRootEntry(name) {
  return EXCLUDED_ROOT_ENTRIES.has(name) || name.startsWith('.git');
}

async function walk(dir, prefix = '') {
  const files = [];
  for (const entry of await readdir(dir, { withFileTypes: true })) {
    const relative = prefix ? `${prefix}/${entry.name}` : entry.name;
    if (!prefix && isExcludedRootEntry(entry.name)) {
      continue;
    }
    if (entry.isDirectory()) {
      files.push(...(await walk(join(dir, entry.name), relative)));
    } else if (entry.isFile()) {
      files.push(relative);
    }
  }
  return files;
}

async function referencedAssets(themeDir) {
  let manifest;
  try {
    manifest = JSON.parse(await readFile(join(themeDir, MANIFEST), 'utf8'));
  } catch (error) {
    throw new Error(`Cannot read the Vite manifest ${MANIFEST} in ${themeDir}: ${error.message}`, {
      cause: error,
    });
  }
  const referenced = new Set();
  for (const chunk of Object.values(manifest)) {
    for (const path of [chunk.file, ...(chunk.css ?? []), ...(chunk.assets ?? [])]) {
      if (typeof path === 'string') {
        referenced.add(path);
      }
    }
  }
  return referenced;
}

export async function collectFiles(themeDir) {
  const referenced = await referencedAssets(themeDir);
  const files = await walk(themeDir);
  return files.filter((path) => !path.startsWith('assets/') || referenced.has(path)).toSorted();
}

export async function zipName(themeDir) {
  const style = await readFile(join(themeDir, 'style.css'), 'utf8');
  const version = style.match(/^\s*Version:\s*(\S+)/m)?.[1];
  if (!version) {
    throw new Error(`No "Version:" header in ${join(themeDir, 'style.css')}`);
  }
  return `${THEME_SLUG}-${version}.zip`;
}

export async function createZip(themeDir, outFile) {
  const files = await collectFiles(themeDir);
  const entries = {};
  for (const path of files) {
    entries[`${THEME_SLUG}/${path}`] = [
      new Uint8Array(await readFile(join(themeDir, path))),
      { mtime: ZIP_EPOCH },
    ];
  }
  await mkdir(dirname(outFile), { recursive: true });
  await writeFile(outFile, zipSync(entries));
  return files.length;
}

async function main() {
  const repoRoot = resolve(dirname(fileURLToPath(import.meta.url)), '../../..');
  const themeDir = resolve(repoRoot, process.argv[2] ?? 'dist/themes/hyperpress-uikit');
  const outDir = resolve(repoRoot, process.argv[3] ?? 'dist/packages');
  const outFile = join(outDir, await zipName(themeDir));
  const count = await createZip(themeDir, outFile);
  console.log(`${outFile} (${count} files)`);
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  await main();
}
