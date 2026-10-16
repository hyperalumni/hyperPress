import { spawn } from 'node:child_process';
import { rmSync, writeFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createServer } from 'vite';

const themeDir = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const marker = resolve(themeDir, '../../dist/themes/hyperpress-uikit/.vite-dev');
const urls = { browser: 'http://localhost:5173', internal: 'http://host.docker.internal:5173' };

// Only remove a marker this process wrote: a failed second start must not clobber the running server's.
let markerWritten = false;
const removeMarker = () => {
  if (markerWritten) rmSync(marker, { force: true });
  markerWritten = false;
};

// Resolves true when copy-theme exits 0; logs and resolves false otherwise.
const copyTheme = () =>
  new Promise((done) => {
    const child = spawn(process.execPath, [join(themeDir, 'bin/copy-theme.mjs')], {
      stdio: 'inherit',
    });
    child.on('error', (error) => {
      console.error(`copy-theme failed to start: ${error.message}`);
      done(false);
    });
    child.on('close', (code) => {
      if (code !== 0) console.error(`copy-theme exited with code ${code}`);
      done(code === 0);
    });
  });

// Serialized, coalesced: while a copy runs, further changes schedule at most one trailing run.
let running = false;
let queued = false;
const syncTheme = async () => {
  if (running) {
    queued = true;
    return;
  }
  running = true;
  do {
    queued = false;
    if (await copyTheme()) server.ws.send({ type: 'full-reload' });
  } while (queued);
  running = false;
};

let server;
const stop = async (code) => {
  removeMarker();
  await server?.close();
  process.exit(code);
};
process.on('exit', removeMarker);
for (const signal of ['SIGINT', 'SIGTERM', 'SIGHUP']) process.on(signal, () => stop(0));

try {
  server = await createServer({ configFile: join(themeDir, 'vite.config.ts') });
  await server.listen();
  writeFileSync(marker, JSON.stringify(urls));
  markerWritten = true;
  server.printUrls();

  const watched = /^(functions\.php|index\.php|style\.css|inc\/|views\/)/;
  server.watcher.add(
    ['functions.php', 'index.php', 'style.css', 'inc', 'views'].map((entry) =>
      join(themeDir, entry),
    ),
  );
  server.watcher.on('all', (_event, file) => {
    if (watched.test(relative(themeDir, file))) syncTheme();
  });
} catch (error) {
  removeMarker();
  console.error(error);
  process.exit(1);
}
