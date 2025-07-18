<script lang="ts">
	import { snapdom } from '@zumer/snapdom';
	import { onMount } from 'svelte';
	import './styles/widget.scss';
	import './styles/widget-region.scss';

	import { makeResizableDiv } from './component/region';
	import { makeDraggable } from './component/draggable';

	let formContainer;
	let region;
	let formHidden = $state(true);
	let regionHidden = $state(true);
	let form;

	let messageTimeout = null;
	let message = $state('');
	let messageType = $state('info');
	const messageHideDelay = 0;

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

	const t = (text) => config.messages?.[text] ?? text + ' (missing translation)';

	const showMessage = (msg, type = 'info') => {
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
		// @todo This does not actually validate the form elements!
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
				showMessage('Error taking screenshot', 'danger');
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
						if (region) {
							region.hidden = true;
						}
						hideForm(true);
						showMessage(t('Feedback created'), 'success');
					} else {
						response
							.json()
							.then((data) =>
								showMessage('Error creating feedback: ' + JSON.stringify(data), 'danger')
							)
							.catch((error) => showMessage('Error creating feedback: ' + error, 'danger'));
					}
				})
				.catch((reason) => showMessage(reason, 'error'));
		}
	};

	$effect(() => {
		// Notice: It's important not to clone the form element. If it's cloned form validation (via form.reportValidity) breaks.
		form = document.getElementById('tidy_feedback_form').content?.firstElementChild;
		if (form) {
			const placeholder = formContainer.querySelector('.form-placeholder');
			placeholder.parentNode.replaceChild(form, placeholder);

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
					const el = form.elements[match[1]];
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

<div data-capture="exclude">
	{#if message}
		<div class="tidy-feedback-message-wrapper container position-fixed top">
			<row class="row">
				<div class="col-md-6 offset-md-3 mt-3">
					<div
						class="tidy-feedback-message alert alert-{messageType} alert-dismissible"
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

		<div class="form-placeholder"></div>

		<button class="btn btn-submit" onclick={submitForm}>{t('Send feedback')}</button>
		<button class="btn btn-cancel" onclick={cancelForm}>{t('Cancel')}</button>
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
