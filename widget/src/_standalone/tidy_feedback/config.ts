import Tidy_feedback from './index.svelte';

import type { TargetEmbeddedWindow } from 'svelte-standalone';

declare global {
	interface Window extends TargetEmbeddedWindow<typeof Tidy_feedback, 'tidy_feedback'> {}
}
