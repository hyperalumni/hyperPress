import { resolve } from 'node:path';
import { defineConfig } from 'vite';

const root = import.meta.dirname;
const wpOrigin = process.env.UIKIT_WP_ORIGIN ?? 'http://localhost:5080';

export default defineConfig({
	root,
	build: {
		outDir: resolve(root, '../../dist/themes/hyperpress-uikit'),
		emptyOutDir: false,
		assetsDir: 'assets',
		manifest: true,
		rollupOptions: {
			input: 'src/main.ts',
		},
	},
	server: {
		port: 5173,
		strictPort: true,
		host: true,
		origin: 'http://localhost:5173',
		cors: { origin: [wpOrigin] },
		allowedHosts: ['localhost', '127.0.0.1', 'host.docker.internal'],
	},
	css: {
		preprocessorOptions: {
			scss: {
				loadPaths: [resolve(root, 'node_modules'), resolve(root, '../../node_modules')],
				quietDeps: true,
				silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
			},
		},
	},
});
