import { resolve } from 'node:path';
import { defineConfig } from 'vite';

const root = import.meta.dirname;

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
