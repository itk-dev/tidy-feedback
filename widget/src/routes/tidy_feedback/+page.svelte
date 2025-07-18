<script lang="ts">
	import { onMount } from 'svelte';

	onMount(() => {
		const script = document.createElement('script');
		script.src = '/src/_standalone/tidy_feedback/embed.ts';
		script.async = true;
		script.type = 'module';

		document.head.appendChild(script);

		return () => {
			document.head.removeChild(script);
			window.tidy_feedback.stop();
		};
	});
</script>

<template id="tidy_feedback_form" data-tidy-feedback-config="{ JSON.stringify({"messageHideDelay": 0}) }">
	<form class="x-tidy-feedback-form" action="/tidy-feedback/" method="post">
		<h1 class="tidy-feedback-form-title">Din feedback</h1>
		<p class="tidy-feedback-form-lead">
			Fremhæv den relevante del af siden og udfyld formularen for at sende din feedback
		</p>

		<div class="form-row mb-3 required">
			<label for="subject" class="form-label">Emne</label>
			<input
				id="subject"
				class="form-control"
				name="subject"
				required
				placeholder="Emne"
				value=""
			/>
		</div>

		<div class="form-row mb-3 required">
			<label for="created_by" class="form-label">Din e-mailadresse</label>
			<input
				id="created_by"
				class="form-control"
				name="created_by"
				required
				type="email"
				placeholder="Din e-mailadresse"
				value=""
			/>
		</div>

		<div class="form-row mb-3 required">
			<label for="description" class="form-label">Beskrivelse</label>
			<textarea
				id="description"
				class="form-control"
				name="description"
				required
				placeholder="Beskrivelse"
			></textarea>
		</div>

		<div class="form-row mb-3">
			<label for="what_did_you_do" class="form-label">Hvad gjorde du?</label>
			<textarea
				id="what_did_you_do"
				class="form-control"
				name="what_did_you_do"
				placeholder="Hvad gjorde du?"
			></textarea>
		</div>
	</form>
</template>

<div id="tidy_feedback"></div>

<h1>This is a test</h1>
