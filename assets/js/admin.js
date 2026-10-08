(function () {
	'use strict';

	const root = document.getElementById('sfj-admin');
	if (!root || typeof shaFactorialAdmin === 'undefined') {
		return;
	}

	const { ajaxUrl, nonce, i18n } = shaFactorialAdmin;

	function qs(sel, ctx) {
		return (ctx || root).querySelector(sel);
	}

	function qsa(sel, ctx) {
		return Array.from((ctx || root).querySelectorAll(sel));
	}

	function setStatus(card, state, text) {
		const badge = qs('[data-status-badge]', card);
		if (!badge) return;
		badge.className = 'sfj-status sfj-status--' + state;
		const label = qs('.sfj-status__text', badge) || badge;
		if (label) label.textContent = text;
	}

	function renderTestResult(card, ok, html) {
		const box = qs('[data-test-result]', card);
		if (!box) return;
		box.hidden = false;
		box.className = 'sfj-test-result ' + (ok ? 'is-success' : 'is-error');
		box.innerHTML = html;
	}

	qsa('.sfj-tab').forEach((tab) => {
		tab.addEventListener('click', () => {
			const target = tab.dataset.tab;
			qsa('.sfj-tab').forEach((t) => {
				t.classList.toggle('is-active', t === tab);
				t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
			});
			qsa('.sfj-panel').forEach((panel) => {
				const active = panel.dataset.panel === target;
				panel.classList.toggle('is-active', active);
				panel.hidden = !active;
			});

			const form = qs('.sfj-form');
			if (form) {
				form.hidden = target === 'about';
			}
		});
	});

	qsa('.sfj-toggle-key').forEach((btn) => {
		btn.addEventListener('click', () => {
			const input = btn.closest('.sfj-input-group')?.querySelector('input');
			const icon = btn.querySelector('.dashicons');
			if (!input) return;
			const show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			if (icon) {
				icon.classList.toggle('dashicons-visibility', !show);
				icon.classList.toggle('dashicons-hidden', show);
			}
		});
	});

	qsa('[data-test-connection]').forEach((btn) => {
		btn.addEventListener('click', async () => {
			const region = btn.dataset.region;
			const card = btn.closest('.sfj-connection');
			const keyInput = qs('#connection_' + region + '_api_key', card);
			const spinner = qs('.sfj-btn__spinner', btn);
			const label = qs('.sfj-btn__label', btn);

			setStatus(card, 'loading', i18n.testing);
			if (spinner) spinner.hidden = false;
			if (label) label.textContent = i18n.testing;
			btn.disabled = true;

			const body = new FormData();
			body.append('action', 'sha_factorial_test_connection');
			body.append('nonce', nonce);
			body.append('region', region);
			if (keyInput && keyInput.value.trim()) {
				body.append('api_key', keyInput.value.trim());
			}

			try {
				const res = await fetch(ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
				const json = await res.json();

				if (!json.success) {
					setStatus(card, 'error', i18n.error);
					renderTestResult(card, false, '<strong>' + (json.data?.message || i18n.error) + '</strong>');
					return;
				}

				const data = json.data;
				const identity = data.identity || {};
				const name = identity.company_name || identity.name || identity.email || region.toUpperCase();
				const count = data.published_count ?? 0;

				setStatus(card, 'success', i18n.success);
				renderTestResult(
					card,
					true,
					'<strong>' + data.message + '</strong><br>' +
						name +
						'<span class="sfj-test-result__metric">' + count + ' ' + i18n.jobsFound + '</span><br>' +
						'<small>' + i18n.apiVersion + ' ' + (data.api_version || '') + '</small>'
				);
			} catch (e) {
				setStatus(card, 'error', i18n.error);
				renderTestResult(card, false, '<strong>' + i18n.error + '</strong>');
			} finally {
				if (spinner) spinner.hidden = true;
				if (label) label.textContent = i18n.test;
				btn.disabled = false;
			}
		});
	});

	const clearBtn = qs('#sfj-clear-cache');
	if (clearBtn) {
		clearBtn.addEventListener('click', async () => {
			clearBtn.disabled = true;
			const body = new FormData();
			body.append('action', 'sha_factorial_clear_cache');
			body.append('nonce', nonce);
			try {
				const res = await fetch(ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
				const json = await res.json();
				alert(json.data?.message || (json.success ? 'OK' : 'Error'));
			} finally {
				clearBtn.disabled = false;
			}
		});
	}

	const stylesPanel = qs('[data-panel="styles"]');
	const stylePreview = stylesPanel ? qs('[data-sfj-style-preview]', stylesPanel) : null;
	if (stylePreview && stylesPanel) {
		function styleInputValue(input) {
			if (input.dataset.sfjStyleType === 'checkbox') {
				return input.checked ? input.dataset.sfjStyleOn : input.dataset.sfjStyleOff;
			}

			if (input.dataset.sfjStyleType === 'range') {
				const unit = input.dataset.sfjStyleUnit || '';
				if (unit === '%') {
					if (input.dataset.sfjStyleScale === '1') {
						return (parseInt(input.value, 10) / 100).toFixed(2);
					}
					return (parseInt(input.value, 10) / 100).toFixed(2) + 'rem';
				}
				return input.value + unit;
			}

			return input.value;
		}

		function syncCardWrapper() {
			const showCardInput = qs('[data-sfj-style-key="show_card"]', stylesPanel);
			if (!showCardInput) return;
			stylePreview.classList.toggle('sfj-jobs--no-card', !showCardInput.checked);
		}

		function syncFilterStyle() {
			const filterButtonInput = qs('[data-sfj-style-key="filter_button_style"]', stylesPanel);
			const filters = qs('.sfj-filters', stylePreview);
			if (!filterButtonInput || !filters) return;
			filters.classList.toggle('sfj-filters--plain', !filterButtonInput.checked);
		}

		function syncStylePreview() {
			syncCardWrapper();
			syncFilterStyle();

			qsa('[data-sfj-style-input]', stylesPanel).forEach((input) => {
				const cssVar = input.dataset.sfjStyleVar;
				if (!cssVar) return;
				stylePreview.style.setProperty(cssVar, styleInputValue(input));

				if (input.dataset.sfjStyleType === 'color' && cssVar === '--sfj-accent') {
					stylePreview.style.setProperty('--sfj-salary-color', input.value);
				}

				const valueLabel = qs('[data-sfj-style-value="' + input.dataset.sfjStyleKey + '"]', stylesPanel);
				if (valueLabel && input.dataset.sfjStyleType === 'range') {
					valueLabel.textContent = input.value + (input.dataset.sfjStyleUnit || '');
				}
			});
		}

		qsa('[data-sfj-style-input]', stylesPanel).forEach((input) => {
			input.addEventListener('input', syncStylePreview);
			input.addEventListener('change', syncStylePreview);
		});

		syncStylePreview();

		const resetStylesBtn = qs('#sfj-reset-styles', stylesPanel);
		if (resetStylesBtn) {
			resetStylesBtn.addEventListener('click', () => {
				qsa('[data-sfj-style-input]', stylesPanel).forEach((input) => {
					const def = input.dataset.sfjStyleDefault ?? '';
					if (input.dataset.sfjStyleType === 'checkbox') {
						input.checked = def === '1';
						return;
					}
					input.value = def;
				});
				syncStylePreview();
				window.alert(i18n.resetDone);
			});
		}
	}

	const displayPanel = qs('[data-panel="display"]');
	const cardPreviewRoots = displayPanel ? qsa('[data-sfj-card-preview]', displayPanel) : [];
	if (cardPreviewRoots.length && displayPanel) {
		function syncCardPreview() {
			qsa('[data-sfj-card-field-toggle]', displayPanel).forEach((input) => {
				const field = input.dataset.sfjCardFieldToggle;
				const enabled = input.checked;
				cardPreviewRoots.forEach((preview) => {
					qsa('[data-sfj-card-field="' + field + '"]', preview).forEach((el) => {
						el.hidden = !enabled;
					});
				});
			});
		}

		qsa('[data-sfj-card-field-toggle]', displayPanel).forEach((input) => {
			input.addEventListener('change', syncCardPreview);
		});

		syncCardPreview();

		const resetFieldsBtn = qs('#sfj-reset-card-fields', displayPanel);
		if (resetFieldsBtn) {
			resetFieldsBtn.addEventListener('click', () => {
				qsa('[data-sfj-card-field-toggle]', displayPanel).forEach((input) => {
					input.checked = (input.dataset.sfjCardFieldDefault ?? '0') === '1';
				});
				syncCardPreview();
				window.alert(i18n.resetDone);
			});
		}
	}

	qsa('.sfj-copy').forEach((btn) => {
		btn.addEventListener('click', async () => {
			const text = btn.dataset.copy || '';
			try {
				await navigator.clipboard.writeText(text);
				btn.textContent = i18n.copied;
				setTimeout(() => { btn.textContent = i18n.copy; }, 1600);
			} catch (e) {
				window.prompt('Copy:', text);
			}
		});
	});
})();
