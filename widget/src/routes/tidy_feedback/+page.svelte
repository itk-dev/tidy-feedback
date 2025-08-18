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

	const config = {
		messageHideDelay: 0,
		form: {
			action: '/tidy_feedback',
			title: 'Din feedback',
			lead: 'Fremhæv den relevante del af siden og udfyld formularen for at sende din feedback',
			fields: [
				{
					name: 'subject',
					required: true,
					label: 'Emne',
					placeholder: 'Emne',
					value: ''
				},
				{
					name: 'created_by',
					required: true,
					type: 'email',
					label: 'Din e-mailadresse',
					placeholder: 'Din e-mailadresse',
					value: ''
				},
				{
					name: 'description',
					required: true,
					type: 'textarea',
					label: 'Beskrivelse',
					placeholder: 'Beskrivelse',
					value: ''
				},
				{
					name: 'what_did_you_do',
					type: 'textarea',
					label: 'Hvad gjorde du?',
					placeholder: 'Hvad gjorde du?',
					value: ''
				}
			]
		}
	};
</script>

<div data-tidy-feedback-config={JSON.stringify(config)}>
	<h1>This is a Tidy feedback test</h1>

	<button type="button" class="btn btn-primary">This is a button</button>

	<!-- Test with a really long page -->
	{#each { length: 99 } as i}
		<section>
			<header><h2>Section {i}</h2></header>

			<p>
				<a href="https://www.lipsum.com">Lorem ipsum</a> dolor sit amet, consectetur adipiscing elit,
				sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis
				nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure
				dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur
				sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
			</p>
		</section>
	{/each}
</div>

<style>
	p {
		color: red;
	}
</style>
