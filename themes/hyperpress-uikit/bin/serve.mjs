import { spawn } from 'node:child_process';
import { rmSync, writeFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createServer } from 'vite';

const themeDir = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const marker = resolve(themeDir, '../../dist/themes/hyperpress-uikit/.vite-dev');
const urls = { browser: 'http://localhost:5173', internal: 'http://host.docker.internal:5173' };

const removeMarker = () => rmSync(marker, { force: true });
const copyTheme = () =>
	new Promise((done) => spawn(process.execPath, [join(themeDir, 'bin/copy-theme.mjs')], { stdio: 'inherit' }).on('close', done));

let server;
const stop = async (code) => {
	removeMarker();
	await server?.close();
	process.exit(code);
};
process.on('exit', removeMarker);
process.on('SIGINT', () => stop(0));
process.on('SIGTERM', () => stop(0));

try {
	server = await createServer({ configFile: join(themeDir, 'vite.config.ts') });
	await server.listen();
	writeFileSync(marker, JSON.stringify(urls));
	server.printUrls();

	const watched = /^(functions\.php|index\.php|style\.css|inc\/|views\/)/;
	server.watcher.add(['functions.php', 'index.php', 'style.css', 'inc', 'views'].map((entry) => join(themeDir, entry)));
	server.watcher.on('all', async (_event, file) => {
		if (!watched.test(relative(themeDir, file))) return;
		await copyTheme();
		server.ws.send({ type: 'full-reload' });
	});
} catch (error) {
	removeMarker();
	console.error(error);
	process.exit(1);
}
