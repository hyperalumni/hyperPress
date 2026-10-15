// Smoke test for the uikit site. Usage: node docker/uikit/smoke.mjs [base-url]
// SMOKE_RETRY_SECONDS (default 30) bounds how long connection errors are retried.
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const base = (process.argv[2] ?? 'http://localhost:5080').replace(/\/$/, '');
const retrySeconds = Number(process.env.SMOKE_RETRY_SECONDS ?? 30);
const manifestPath = fileURLToPath(
  new URL('../../dist/themes/hyperpress-uikit/.vite/manifest.json', import.meta.url),
);
const themeUrl = `${base}/wp-content/themes/hyperpress-uikit`;

const fail = (url, reason) => {
  console.error(`FAIL ${url} ${reason}`);
  process.exit(1);
};

async function get(url) {
  for (let attempt = 0; ; attempt++) {
    try {
      return await fetch(url, { redirect: 'manual' });
    } catch (error) {
      if (attempt >= retrySeconds) fail(url, `connection error: ${error.cause?.code ?? error.message}`);
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
  const response = await get(url);
  if (response.status !== 200) fail(url, `status ${response.status}`);
  console.log(`ok ${url}`);
}

if (!(await home.text()).includes('uk-')) fail(`${base}/`, 'body contains no uk- classes');
console.log(`ok ${base}/ contains uk-`);
