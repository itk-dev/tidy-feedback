<script lang="ts">
	import { snapdom } from '@zumer/snapdom';
	import { onMount } from 'svelte';

	// https://coreui.io/bootstrap/docs/getting-started/vite/#import-coreui

	import { makeResizableDiv } from './component/region';
	import { makeDraggable } from './component/draggable';
	import Alert from '@coreui/coreui/js/src/alert';

	const config = (() => {
		const el = document.querySelector('[data-tidy-feedback-config]');
		if (el) {
			try {
				return JSON.parse(el.dataset.tidyFeedbackConfig);
			} catch (error) {
				// Ignore all errors.
			}
		}
		return {};
	})();

	const MESSAGE_TYPE_INFO = 'info';
	const MESSAGE_TYPE_DANGER = 'danger';
	const MESSAGE_TYPE_ERROR = 'error';
	const MESSAGE_TYPE_SUCCESS = 'success';

	let formContainer;
	let region;
	let formHidden = $state(true);
	let regionHidden = $state(true);
	let form;

	let messageTimeout = null;
	let message = $state('');
	let messageType = $state(MESSAGE_TYPE_INFO);
	const messageHideDelay = config.messageHideDelay ?? 0;

	const t = (text) => config.messages?.[text] ?? text + ' (missing translation)';

	const showMessage = (msg, type = MESSAGE_TYPE_INFO) => {
		if (messageTimeout) {
			clearTimeout(messageTimeout);
		}
		message = msg;
		messageType = type;
		if (messageHideDelay > 0) {
			messageTimeout = setTimeout(() => (message = ''), messageHideDelay);
		}
	};

	const showForm = () => {
		showMessage(t('Highlight and fill'));
		formHidden = false;
		regionHidden = false;
	};

	const hideForm = (reset = false) => {
		if (reset) {
			form.reset();
		}
		formHidden = true;
		regionHidden = true;
	};

	const cancelForm = () => {
		const isEmpty = (() => {
			for (const el of form.elements) {
				if (el.value || el.checked) {
					return false;
				}
			}
			return true;
		})();
		if (!isEmpty && !confirm(t('Confirm cancellation'))) {
			return;
		}
		showMessage(t('Cancelled'));
		hideForm();
	};

	const submitForm = async (target) => {
		// https://developer.mozilla.org/en-US/docs/Web/API/HTMLFormElement#instance_methods
		const isValid = form.reportValidity();
		if (isValid) {
			const data = {};

			showMessage('Taking screenshot …');
			try {
				const el = document.body;
				const result = await snapdom(el, { scale: 1 });

				let image = result.toRaw();
				// Check if some other image formats generate smaller payload
				for (const method of ['toWebp', 'toPng', 'toJpg']) {
					const img = await result[method]();
					if (img.src.length < image.length) {
						image = img.src;
					}
				}

				data.image = image;
			} catch (error) {
				console.error(error);
				showMessage('Error taking screenshot', MESSAGE_TYPE_DANGER);
			}

			const formData = new FormData(form);
			// https://stackoverflow.com/a/46774073
			formData.forEach((value, key) => (data[key] = value));

			// Add some useful context
			data.context = {
				url: document.location.href,
				referrer: document.referrer,
				document: document.documentElement.outerHTML,
				navigator: {
					userAgent: navigator.userAgent
				},
				window: {
					innerWidth: window.innerWidth,
					innerHeight: window.innerHeight
				},
				region: {
					left: region.style.left,
					top: region.style.top,
					width: region.style.width,
					height: region.style.height
				}
			};

			showMessage('Sending feedback …');

			fetch(form.action, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(data)
			})
				.then((response) => {
					if (201 === response.status) {
						hideForm(true);
						showMessage(t('Feedback created'), MESSAGE_TYPE_SUCCESS);
					} else {
						response
							.json()
							.then((data) =>
								showMessage('Error creating feedback: ' + JSON.stringify(data), MESSAGE_TYPE_DANGER)
							)
							.catch((error) =>
								showMessage('Error creating feedback: ' + error, MESSAGE_TYPE_DANGER)
							);
					}
				})
				.catch((reason) => showMessage(reason, MESSAGE_TYPE_ERROR));
		}
	};

	$effect(() => {
		form = document.getElementById('the_tidy_feedback_form');
		if (form) {
			makeDraggable(formContainer, '.tidy-feedback-draggable-handle', {
				constrainToViewport: true
			});

			region.style.left = '300px';
			region.style.top = '300px';
			region.style.width = '300px';
			region.style.height = '200px';

			makeResizableDiv(region);

			const params = new URLSearchParams(document.location.search);
			const regexp = /tidy-feedback\[(.+)\]/;
			for (const [key, value] of params.entries()) {
				const match = regexp.exec(key);
				if (match) {
					const el = form.elements[match[1]] ?? null;
					if (el) {
						// @todo Handle non-text elements
						el.value = value;
					}
				}
			}

			switch (params.get('tidy-feedback-show')) {
				case 'form':
					showForm();
					break;
			}
		}
	});
</script>

<div data-capture="exclude" class="tidy-feedback-container">
	{#if message}
		<div class="tidy-feedback-message-wrapper container position-fixed top-0">
			<row class="row">
				<div class="col-md-6 offset-md-3 mt-3">
					<div
						class={[
							'tidy-feedback-message',
							'alert',
							'alert-dismissible',
							{
								'alert-info': MESSAGE_TYPE_INFO === messageType,
								'alert-danger': MESSAGE_TYPE_DANGER === messageType,
								'alert-error': MESSAGE_TYPE_ERROR === messageType,
								'alert-success': MESSAGE_TYPE_SUCCESS === messageType
							}
						]}
						role="alert"
					>
						{message}
						<button class="btn-close" onclick={() => (message = null)} aria-label="Close"></button>
					</div>
				</div>
			</row>
		</div>
	{/if}

	{#if formHidden}
		<button class="btn btn-submit tidy-feedback-start" onclick={showForm}
			>{t('Send feedback')}</button
		>
	{/if}

	<div class="form-container tidy-feedback-form" bind:this={formContainer} hidden={formHidden}>
		<div class="tidy-feedback-draggable-handle">
			<span></span>
			<span></span>
			<span></span>
		</div>

		<form
			class="x-tidy-feedback-form"
			action={config.form.action ?? '/tidy_feedback'}
			method="post"
			id="the_tidy_feedback_form"
		>
			{#if config.form}
				{#if config.form.title}
					<h1 class="tidy-feedback-form-title">{config.form.title}</h1>
				{/if}
				{#if config.form.lead}
					<p class="tidy-feedback-form-lead">{config.form.lead}</p>
				{/if}
				{#if config.form.fields}
					{#each config.form.fields as field}
						{#if field.type == 'textarea'}
							<div class={['form-row', 'mb-3', { required: field.required }]}>
								<label for={field.name} class="form-label">{field.label}</label>
								<textarea
									name={field.name}
									id={field.name}
									class="form-control"
									required={field.required}
									placeholder={field.placeholder}>{field.value ?? ''}</textarea
								>
							</div>
						{:else}
							<div class={['form-row', 'mb-3', { required: field.required }]}>
								<label for={field.name} class="form-label">{field.label}</label>
								<input
									type={field.type ?? 'text'}
									name={field.name}
									id={field.name}
									class="form-control"
									required={field.required}
									placeholder={field.placeholder}
									value={field.value ?? ''}
								/>
							</div>
						{/if}
					{/each}
				{/if}

				<button class="btn btn-submit" onclick={submitForm}>{t('Send feedback')}</button>
				<button class="btn btn-cancel" onclick={cancelForm} type="button">{t('Cancel')}</button>
			{/if}
		</form>
	</div>

	<div id="tidy-feedback-region" hidden={regionHidden} bind:this={region}>
		<div class="overlays">
			{#each ['top', 'left', 'right', 'bottom'] as position}
				<div class={position}></div>
			{/each}
		</div>

		<div class="resizable">
			<div class="resizers">
				{#each ['top-left', 'top-right', 'bottom-left', 'bottom-right'] as position}
					<div class="resizer {position}"></div>
				{/each}
			</div>
		</div>
	</div>
</div>

<style>
	@import './styles/widget.scss';
	@import './styles/widget-region.scss';
</style>
