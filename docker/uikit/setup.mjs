// One-command setup for the uikit site. Usage: pnpm exec nx setup @hyper/press-uikit
// Required environment: UIKIT_ADMIN_USER, UIKIT_ADMIN_PASSWORD, UIKIT_ADMIN_EMAIL.
// Optional: UIKIT_BASE_URL (default http://localhost:<WORDPRESS_EXTERNAL_PORT>, else :5080).
// Idempotent; safe to run again. Compose project and ports come from the inherited
// environment (COMPOSE_PROJECT_NAME, WORDPRESS_EXTERNAL_PORT, ...).
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const composeDir = fileURLToPath(new URL('.', import.meta.url));
const abort = (message) => {
  console.error(message);
  process.exit(1);
};
const required = ['UIKIT_ADMIN_USER', 'UIKIT_ADMIN_PASSWORD', 'UIKIT_ADMIN_EMAIL'];
const missing = required.filter((name) => !process.env[name]);
if (missing.length > 0) abort(`Missing environment variable(s): ${missing.join(', ')}`);
const { UIKIT_ADMIN_USER: user, UIKIT_ADMIN_EMAIL: email } = process.env;
const { WORDPRESS_EXTERNAL_PORT: composePort, UIKIT_BASE_URL: urlSetting } = process.env;
const baseUrl = (urlSetting || `http://localhost:${composePort || 5080}`).replace(/\/$/, '');
if (composePort && urlSetting) {
  let urlPort = '';
  try {
    const url = new URL(baseUrl);
    urlPort = url.port || (url.protocol === 'https:' ? '443' : '80');
  } catch {
    abort('UIKIT_BASE_URL is not a valid URL.');
  }
  if (urlPort !== composePort) {
    abort(
      `UIKIT_BASE_URL (port ${urlPort}) does not match WORDPRESS_EXTERNAL_PORT (${composePort}).`,
    );
  }
}
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// Runs a command without a shell; stdout is captured (and echoed unless quiet), stderr is
// inherited unless quiet. Resolves { code, out }.
function run(command, args, { cwd = root, quiet = false } = {}) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, {
      cwd,
      stdio: ['pipe', 'pipe', quiet ? 'ignore' : 'inherit'],
    });
    let out = '';
    child.stdout.on('data', (chunk) => {
      out += chunk;
      if (!quiet) process.stdout.write(chunk);
    });
    child.on('error', reject);
    child.on('close', (code) => resolve({ code, out: out.trim() }));
    child.stdin.end();
  });
}

// Runs a command in the wpcli tooling container (-T: no TTY).
const wpcli = (command, { dockerArgs = [], ...options } = {}) =>
  run(
    'docker',
    ['compose', '--profile', 'local', 'run', '--rm', '-T', ...dockerArgs, 'wpcli', ...command],
    {
      cwd: composeDir,
      ...options,
    },
  );
const wp = (args, options) => wpcli(['wp', ...args], options);

async function waitForWordPress() {
  const deadline = Date.now() + 120_000;
  for (;;) {
    const { code } = await wp(['db', 'check'], { quiet: true });
    if (code === 0) return;
    if (Date.now() >= deadline)
      abort('Timed out after 120 s waiting for wp-cli to reach the database.');
    await sleep(2000);
  }
}

async function installIfNeeded() {
  if ((await wp(['core', 'is-installed'], { quiet: true })).code === 0) return;
  // wp-cli's --prompt echoes the typed answer (and the full command) to stdout, so the password
  // travels in the container environment instead: `-e NAME` forwards the value without putting
  // it in the host docker command line. wp-cli still takes it as --admin_password, so it is
  // briefly visible in the wp process arguments inside the container and, as an environment
  // variable, in `docker inspect` of that short-lived wpcli container (accepted for a local
  // dev site).
  const script =
    'exec wp core install --url="$1" --title="HyperPress UIkit" --admin_user="$2" ' +
    '--admin_email="$3" --admin_password="$UIKIT_ADMIN_PASSWORD" --skip-email';
  const { code } = await wpcli(['sh', '-c', script, 'sh', baseUrl, user, email], {
    dockerArgs: ['-e', 'UIKIT_ADMIN_PASSWORD'],
  });
  if (code !== 0) process.exit(code ?? 1);
}

const SEED = ['post', 'list', '--post_type=post', '--name=hello-uikit-card'];
// Every status but trash: `--post_status=any` did not match a draft with `--name` in testing, and
// WordPress renames a trashed post's slug (hello-uikit-card__trashed), so a trashed seed is not
// found and a fresh one is created without a -2 suffix.
const ANY_STATUS = '--post_status=publish,future,draft,pending,private';

// Creates the demo post if no post with the slug exists in any status (trash aside), publishes
// it if it exists in another status, and returns its permalink.
async function seed() {
  for (const args of [
    ['theme', 'activate', 'hyperpress-uikit'],
    ['plugin', 'activate', 'hyperpress-example'],
  ]) {
    const { code } = await wp(args);
    if (code !== 0) process.exit(code ?? 1);
  }
  const found = await wp([...SEED, ANY_STATUS, '--field=ID'], { quiet: true });
  if (found.code !== 0) abort('Could not list posts.');
  if (!found.out) {
    const { code } = await wp([
      'post',
      'create',
      '--post_status=publish',
      '--post_name=hello-uikit-card',
      '--post_title=Hello UIkit card',
      '--post_content=[hp_uikit_card title="Hello"]World[/hp_uikit_card]',
      '--porcelain',
    ]);
    if (code !== 0) process.exit(code ?? 1);
  } else {
    const id = found.out.split('\n')[0];
    const status = (await wp(['post', 'get', id, '--field=post_status'], { quiet: true })).out;
    if (status !== 'publish') {
      console.log(`Seed post ${id} was "${status}"; publishing it.`);
      const { code } = await wp(['post', 'update', id, '--post_status=publish']);
      if (code !== 0) process.exit(code ?? 1);
    }
  }
  const url = (await wp([...SEED, '--post_status=publish', '--field=url'], { quiet: true })).out;
  if (!url) abort('The seed post has no permalink.');
  return url.split('\n')[0];
}

// The seed post's own page must contain the rendered shortcode (retried for up to 30 s).
async function verifyPermalink(url) {
  const deadline = Date.now() + 30_000;
  for (;;) {
    try {
      const response = await fetch(url, { redirect: 'follow', signal: AbortSignal.timeout(5000) });
      if (response.status === 200 && (await response.text()).includes('hp-uikit-card')) {
        console.log(`ok ${url} contains hp-uikit-card`);
        return;
      }
    } catch {
      // retried until the deadline
    }
    if (Date.now() >= deadline) abort(`FAIL ${url} does not serve hp-uikit-card`);
    await sleep(1000);
  }
}

// The generic smoke check (no SMOKE_EXPECT: the homepage need not list the seed post).
function smoke() {
  const childEnv = { ...process.env };
  delete childEnv.SMOKE_EXPECT;
  return new Promise((resolve) => {
    const child = spawn('node', ['docker/uikit/smoke.mjs', baseUrl], {
      cwd: root,
      env: childEnv,
      stdio: 'inherit',
    });
    child.on('error', (error) => {
      console.error(`Could not start the smoke check: ${error.message}`);
      resolve(1);
    });
    child.on('close', resolve);
  });
}

const up = await run('pnpm', ['exec', 'nx', 'up', '@hyper/press-uikit']);
if (up.code !== 0) process.exit(up.code ?? 1);
await waitForWordPress();
await installIfNeeded();
await verifyPermalink(await seed());
const smokeCode = await smoke();
if (smokeCode === 0) console.log(`Setup complete: ${baseUrl}`);
process.exit(smokeCode ?? 1);
