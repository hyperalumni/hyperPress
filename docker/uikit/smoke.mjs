// Smoke test for the uikit site. Usage: node docker/uikit/smoke.mjs [base-url]
// SMOKE_RETRY_SECONDS (default 30) bounds how long connection errors are retried.
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const base = (process.argv[2] ?? 'http://localhost:5080').replace(/\/$/, '');
const REQUEST_TIMEOUT_MS = 3000;
const requested = parseFloat(process.env.SMOKE_RETRY_SECONDS ?? '');
const retrySeconds = Number.isFinite(requested) && requested >= 0 ? requested : 30;
const manifestPath = fileURLToPath(
  new URL('../../dist/themes/hyperpress-uikit/.vite/manifest.json', import.meta.url),
);
const themeUrl = `${base}/wp-content/themes/hyperpress-uikit`;

const fail = (url, reason) => {
  console.error(`FAIL ${url} ${reason}`);
  process.exit(1);
};

// Every request (headers and body) is bounded by REQUEST_TIMEOUT_MS; failed attempts are
// retried until the deadline. Returns { status, body }.
async function get(url) {
  const deadline = Date.now() + retrySeconds * 1000;
  for (;;) {
    try {
      const response = await fetch(url, {
        redirect: 'manual',
        signal: AbortSignal.timeout(REQUEST_TIMEOUT_MS),
      });
      return { status: response.status, body: await response.text() };
    } catch (error) {
      const reason =
        error.name === 'TimeoutError'
          ? 'timeout'
          : `connection error: ${error.cause?.code ?? error.message}`;
      if (Date.now() >= deadline) fail(url, reason);
      await new Promise((resolve) => setTimeout(resolve, 1000));
    }
  }
}

const home = await get(`${base}/`);
if (home.status !== 200) {
  fail(`${base}/`, `status ${home.status} (WordPress is not installed or the theme is not active)`);
}
console.log(`ok ${base}/`);

let entry;
try {
  entry = JSON.parse(readFileSync(manifestPath, 'utf8'))['src/main.ts'];
} catch (error) {
  fail(manifestPath, `cannot read manifest: ${error.message}`);
}
if (!entry) fail(manifestPath, 'no src/main.ts entry');

for (const path of [entry.file, ...(entry.css ?? [])]) {
  const url = `${themeUrl}/${path}`;
  const { status } = await get(url);
  if (status !== 200) fail(url, `status ${status}`);
  console.log(`ok ${url}`);
  if (!home.body.includes(url)) fail(`${base}/`, `page does not reference ${url}`);
  console.log(`ok ${base}/ references ${path}`);
}

if (!home.body.includes('type="module"')) fail(`${base}/`, 'body contains no type="module" script');
console.log(`ok ${base}/ has a module script`);

if (!home.body.includes('uk-')) fail(`${base}/`, 'body contains no uk- classes');
console.log(`ok ${base}/ contains uk-`);
