// One-command setup for the uikit site. Usage: pnpm exec nx setup @hyper/press-uikit
// Required environment: UIKIT_ADMIN_USER, UIKIT_ADMIN_PASSWORD, UIKIT_ADMIN_EMAIL.
// Optional: UIKIT_BASE_URL (default http://localhost:5080). Idempotent; safe to run again.
// Compose project and ports come from the inherited environment (COMPOSE_PROJECT_NAME, ...).
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../..', import.meta.url));
const composeDir = fileURLToPath(new URL('.', import.meta.url));
const required = ['UIKIT_ADMIN_USER', 'UIKIT_ADMIN_PASSWORD', 'UIKIT_ADMIN_EMAIL'];
const missing = required.filter((name) => !process.env[name]);
if (missing.length > 0) {
  console.error(`Missing environment variable(s): ${missing.join(', ')}`);
  process.exit(1);
}
const { UIKIT_ADMIN_USER: user, UIKIT_ADMIN_EMAIL: email } = process.env;
const baseUrl = (process.env.UIKIT_BASE_URL || 'http://localhost:5080').replace(/\/$/, '');
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// Runs a command without a shell; stdout is captured (and echoed unless quiet), stderr is
// inherited unless quiet. Resolves { code, out }.
function run(command, args, { cwd = root, quiet = false } = {}) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, {
      cwd,
      stdio: ['pipe', 'pipe', quiet ? 'pipe' : 'inherit'],
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
    if (Date.now() >= deadline) {
      console.error('Timed out after 120 s waiting for wp-cli to reach the database.');
      process.exit(1);
    }
    await sleep(2000);
  }
}

async function installIfNeeded() {
  if ((await wp(['core', 'is-installed'], { quiet: true })).code === 0) return;
  // wp-cli's --prompt echoes the typed answer (and the full command) to stdout, so the password
  // travels in the container environment instead: `-e NAME` forwards the value without putting
  // it in the host docker command line. wp-cli still takes it as --admin_password, so it is
  // briefly visible in the wp process arguments inside the container (accepted for a local
  // dev site).
  const script =
    'exec wp core install --url="$1" --title="HyperPress UIkit" --admin_user="$2" ' +
    '--admin_email="$3" --admin_password="$UIKIT_ADMIN_PASSWORD" --skip-email';
  const { code } = await wpcli(['sh', '-c', script, 'sh', baseUrl, user, email], {
    dockerArgs: ['-e', 'UIKIT_ADMIN_PASSWORD'],
  });
  if (code !== 0) process.exit(code ?? 1);
}

async function seed() {
  const steps = [
    ['theme', 'activate', 'hyperpress-uikit'],
    ['plugin', 'activate', 'hyperpress-example'],
  ];
  for (const args of steps) {
    const { code } = await wp(args);
    if (code !== 0) process.exit(code ?? 1);
  }
  const existing = await wp(
    ['post', 'list', '--post_type=post', '--name=hello-uikit-card', '--field=ID'],
    { quiet: true },
  );
  if (existing.code !== 0) {
    console.error('Could not list posts.');
    process.exit(existing.code ?? 1);
  }
  if (existing.out) return;
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
}

async function smoke() {
  const env = { ...process.env, SMOKE_EXPECT: 'hp-uikit-card' };
  const child = spawn('node', ['docker/uikit/smoke.mjs', baseUrl], {
    cwd: root,
    env,
    stdio: 'inherit',
  });
  return new Promise((resolve) => child.on('close', resolve));
}

const up = await run('pnpm', ['exec', 'nx', 'up', '@hyper/press-uikit']);
if (up.code !== 0) process.exit(up.code ?? 1);
await waitForWordPress();
await installIfNeeded();
await seed();
const smokeCode = await smoke();
if (smokeCode === 0) console.log(`Setup complete: ${baseUrl}`);
process.exit(smokeCode ?? 1);
