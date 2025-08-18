import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
	plugins: [sveltekit()],
	// https://coreui.io/bootstrap/docs/getting-started/vite/#import-coreui
	resolve: {
		alias: {
			'~coreui': resolve(__dirname, 'node_modules/@coreui/coreui')
		}
	}
});
