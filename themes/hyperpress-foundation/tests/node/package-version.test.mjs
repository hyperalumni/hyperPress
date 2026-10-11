import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {mkdtempSync, rmSync, writeFileSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {test} from 'node:test';

import {
  normalizeTag,
  resolveVersion,
  setStyleVersion,
  stampStyleVersion,
} from '../../gulp/package-version.mjs';

const STYLE = '/**\n * Theme Name:         hyperPress\n * Version:            2026.0.0\n * Requires at least:  6.9\n*/\n';

function git(cwd, ...args) {
  return execFileSync('git', ['-c', 'user.name=t', '-c', 'user.email=t@example.com', '-c', 'commit.gpgSign=false', '-c', 'tag.gpgSign=false', '-c', 'tag.forceSignAnnotated=false', ...args], {cwd, encoding: 'utf8'});
}

// A throwaway repository with one commit; tags are added by the individual tests.
function repo() {
  const dir = mkdtempSync(join(tmpdir(), 'hp-version-'));
  git(dir, 'init', '-q');
  writeFileSync(join(dir, 'style.css'), STYLE);
  git(dir, 'add', '.');
  git(dir, 'commit', '-q', '-m', 'one');
  return dir;
}

function resolve(dir) {
  const warnings = [];
  const version = resolveVersion({cwd: dir, styleCss: STYLE, warn: (message) => warnings.push(message)});
  return {version, warnings};
}

test('normalizeTag strips one leading v', () => {
  assert.equal(normalizeTag('v2027.1.0'), '2027.1.0');
  assert.equal(normalizeTag('2027.1.0'), '2027.1.0');
});

test('setStyleVersion rewrites only the Version header line', () => {
  const out = setStyleVersion(STYLE, '2027.1.0');
  assert.equal(out, STYLE.replace('2026.0.0', '2027.1.0'));
  assert.throws(() => setStyleVersion('/* Theme Name: x */', '1.0.0'), /Version/);
});

test('stampStyleVersion changes style.css only', async () => {
  const stream = stampStyleVersion('2027.1.0');
  const seen = [];
  stream.on('data', (file) => seen.push(file));
  // Minimal stand-in for a vinyl file (vinyl itself is only a transitive dependency).
  const make = (relative, text) => ({relative, contents: Buffer.from(text), isBuffer: () => true});
  stream.write(make('style.css', STYLE));
  stream.write(make('languages/style.css', STYLE));
  stream.end();
  await new Promise((done) => stream.on('end', done));
  assert.match(seen[0].contents.toString(), /Version: +2027\.1\.0/);
  assert.match(seen[1].contents.toString(), /Version: +2026\.0\.0/);
});

test('no tag: falls back to style.css and warns', () => {
  const dir = repo();
  try {
    const {version, warnings} = resolve(dir);
    assert.equal(version, '2026.0.0');
    assert.equal(warnings.length, 1);
    assert.match(warnings[0], /warning: no version tag found; using 2026\.0\.0 from style\.css/);
  } finally {
    rmSync(dir, {recursive: true, force: true});
  }
});

test('not a git repository: falls back too', () => {
  const dir = mkdtempSync(join(tmpdir(), 'hp-nogit-'));
  try {
    const {version, warnings} = resolve(dir);
    assert.equal(version, '2026.0.0');
    assert.equal(warnings.length, 1);
  } finally {
    rmSync(dir, {recursive: true, force: true});
  }
});

test('nearest ancestor tag wins, with no suffix after extra commits', () => {
  const dir = repo();
  try {
    git(dir, 'tag', '2026.0.0');
    writeFileSync(join(dir, 'a.txt'), 'a');
    git(dir, 'add', '.');
    git(dir, 'commit', '-q', '-m', 'two');
    git(dir, 'tag', '2027.1.0');
    writeFileSync(join(dir, 'b.txt'), 'b');
    git(dir, 'add', '.');
    git(dir, 'commit', '-q', '-m', 'three');
    assert.deepEqual(resolve(dir), {version: '2027.1.0', warnings: []});
  } finally {
    rmSync(dir, {recursive: true, force: true});
  }
});

test('a leading v is stripped and non-version tags are ignored', () => {
  const dir = repo();
  try {
    git(dir, 'tag', 'v2027.1.0');
    git(dir, 'tag', 'backup-1');
    assert.deepEqual(resolve(dir), {version: '2027.1.0', warnings: []});
  } finally {
    rmSync(dir, {recursive: true, force: true});
  }
});

test('only a non-version tag: falls back', () => {
  const dir = repo();
  try {
    git(dir, 'tag', 'backup-1');
    const {version, warnings} = resolve(dir);
    assert.equal(version, '2026.0.0');
    assert.equal(warnings.length, 1);
  } finally {
    rmSync(dir, {recursive: true, force: true});
  }
});
