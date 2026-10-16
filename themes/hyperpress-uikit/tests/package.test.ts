import { mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { unzipSync } from 'fflate';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';
import { collectFiles, createZip, zipName } from '../bin/package.mjs';

const manifest = {
  'src/main.ts': {
    file: 'assets/main-AAA.js',
    css: ['assets/main-AAA.css'],
    imports: ['_chunk-BBB.js'],
  },
  '_chunk-BBB.js': {
    file: 'assets/chunk-BBB.js',
    css: ['assets/chunk-BBB.css'],
  },
};

const fixtureFiles: Record<string, string> = {
  'style.css': '/*\nTheme Name: Fixture\nVersion: 1.2.3\n*/\n',
  'functions.php': '<?php\n',
  'vendor/autoload.php': '<?php\n',
  '.vite/manifest.json': JSON.stringify(manifest),
  'assets/main-AAA.js': 'console.log("main");\n',
  'assets/main-AAA.css': 'body{}\n',
  'assets/chunk-BBB.js': 'console.log("chunk");\n',
  'assets/chunk-BBB.css': 'p{}\n',
  'assets/old-123.js': 'stale\n',
  '.vite-dev': '',
  'tests/x.php': '<?php\n',
  'phpunit.xml.dist': '<phpunit/>\n',
};

let root: string;
let themeDir: string;

async function writeFixture(dir: string, files: Record<string, string>) {
  for (const [path, content] of Object.entries(files)) {
    const target = join(dir, path);
    await mkdir(dirname(target), { recursive: true });
    await writeFile(target, content);
  }
}

beforeAll(async () => {
  root = await mkdtemp(join(tmpdir(), 'uikit-package-'));
  themeDir = join(root, 'theme');
  await writeFixture(themeDir, fixtureFiles);
});

afterAll(async () => {
  await rm(root, { recursive: true, force: true });
});

describe('collectFiles', () => {
  it('keeps the theme and the manifest-referenced assets only', async () => {
    expect(await collectFiles(themeDir)).toEqual([
      '.vite/manifest.json',
      'assets/chunk-BBB.css',
      'assets/chunk-BBB.js',
      'assets/main-AAA.css',
      'assets/main-AAA.js',
      'functions.php',
      'style.css',
      'vendor/autoload.php',
    ]);
  });

  it('fails when the manifest is missing', async () => {
    const broken = join(root, 'no-manifest');
    await writeFixture(broken, { 'style.css': 'Version: 1.0.0\n' });
    await expect(collectFiles(broken)).rejects.toThrow(/manifest/);
  });

  it('fails when the manifest is not valid JSON', async () => {
    const broken = join(root, 'bad-manifest');
    await writeFixture(broken, { 'style.css': 'Version: 1.0.0\n', '.vite/manifest.json': '{' });
    await expect(collectFiles(broken)).rejects.toThrow(/manifest/);
  });
});

describe('collectFiles with missing assets', () => {
  it('throws naming every manifest-referenced file that is missing on disk', async () => {
    const broken = join(root, 'missing-asset');
    const { 'assets/main-AAA.css': _css, 'assets/chunk-BBB.js': _js, ...present } = fixtureFiles;
    await writeFixture(broken, present);
    const error = await collectFiles(broken).then(
      () => null,
      (caught: Error) => caught,
    );
    expect(error).toBeInstanceOf(Error);
    expect(error?.message).toContain('assets/main-AAA.css');
    expect(error?.message).toContain('assets/chunk-BBB.js');
    expect(error?.message).not.toContain('assets/main-AAA.js');
  });

  it('also checks the assets list of a manifest entry', async () => {
    const broken = join(root, 'missing-font');
    const extended = {
      ...manifest,
      'src/font.ts': { file: 'assets/font-CCC.js', assets: ['assets/font-CCC.woff2'] },
    };
    await writeFixture(broken, {
      ...fixtureFiles,
      '.vite/manifest.json': JSON.stringify(extended),
      'assets/font-CCC.js': 'x\n',
    });
    await expect(collectFiles(broken)).rejects.toThrow('assets/font-CCC.woff2');
  });
});

describe('createZip', () => {
  it('writes one top-level folder with byte-identical files, deterministically', async () => {
    const first = join(root, 'out/one/theme.zip');
    const second = join(root, 'out/two/theme.zip');
    await createZip(themeDir, first);
    await createZip(themeDir, second);

    const bytes = await readFile(first);
    expect(bytes.equals(await readFile(second))).toBe(true);

    const entries = unzipSync(new Uint8Array(bytes));
    const names = Object.keys(entries).toSorted();
    expect(names).toEqual(
      (await collectFiles(themeDir)).map((path: string) => `hyperpress-uikit/${path}`),
    );
    for (const path of await collectFiles(themeDir)) {
      const source = await readFile(join(themeDir, path));
      expect(Buffer.from(entries[`hyperpress-uikit/${path}`]).equals(source)).toBe(true);
    }
  });
});

describe('zipName', () => {
  it('uses the version from the style.css header', async () => {
    expect(await zipName(themeDir)).toBe('hyperpress-uikit-1.2.3.zip');
  });
});
