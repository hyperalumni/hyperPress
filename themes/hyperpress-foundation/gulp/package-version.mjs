'use strict';

// Version of a packaged theme zip: the nearest version tag reachable from HEAD, without a commit suffix.
// The `Version:` header in the source style.css is only the fallback (and the development value).

import {execFileSync} from 'node:child_process';
import {Transform} from 'node:stream';

// Only tags that start with a digit, optionally after a `v`, count (so `backup-1` is ignored).
const TAG_MATCH = ['--match', '[0-9]*', '--match', 'v[0-9]*'];
const VERSION_LIKE = /^\d+(\.\d+)*([-+.][0-9A-Za-z.-]+)?$/;
const HEADER = /^([ \t]*\*?[ \t]*Version:[ \t]*)(.*?)[ \t]*$/m;

/**
 * @param {string} tag
 * @returns {string} The tag without a leading `v`.
 */
export function normalizeTag(tag) {
  return tag.replace(/^v/, '');
}

/**
 * @param {string} css Contents of style.css.
 * @returns {string} The value of its `Version:` header.
 */
export function readStyleVersion(css) {
  const match = HEADER.exec(css);
  if (!match || match[2] === '') {
    throw new Error('style.css has no Version header');
  }
  return match[2];
}

/**
 * @param {string} css Contents of style.css.
 * @param {string} version
 * @returns {string} The same CSS with only the `Version:` header line rewritten.
 */
export function setStyleVersion(css, version) {
  readStyleVersion(css);
  return css.replace(HEADER, (_line, prefix) => prefix + version);
}

function describeTag(cwd) {
  try {
    return execFileSync('git', ['describe', '--tags', '--abbrev=0', ...TAG_MATCH, 'HEAD'], {
      cwd,
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'ignore'],
    }).trim();
  } catch {
    // No tag, not a git repository, or no git binary.
    return '';
  }
}

/**
 * @param {object}   options
 * @param {string}   options.cwd      Directory inside the git repository.
 * @param {string}   options.styleCss Contents of the source style.css (fallback version).
 * @param {Function} [options.warn]   Receives the fallback warning (default: stderr).
 * @returns {string} The version to package.
 */
export function resolveVersion({cwd, styleCss, warn = (message) => console.error(message)}) {
  const version = normalizeTag(describeTag(cwd));
  if (VERSION_LIKE.test(version)) {
    return version;
  }
  const fallback = readStyleVersion(styleCss);
  warn(`warning: no version tag found; using ${fallback} from style.css`);
  return fallback;
}

/**
 * Gulp transform that writes `version` into the theme's root style.css. Other files pass through untouched.
 * Needs Buffer contents, so read with `{ encoding: false }`.
 *
 * @param {string} version
 * @returns {Transform}
 */
export function stampStyleVersion(version) {
  return new Transform({
    objectMode: true,
    transform(file, _encoding, done) {
      if (file.isBuffer() && file.relative === 'style.css') {
        file.contents = Buffer.from(setStyleVersion(file.contents.toString('utf8'), version));
      }
      done(null, file);
    },
  });
}
