(function () {
	'use strict';

	document.querySelectorAll('[data-sfj-jobs]').forEach((root) => {
		const filters = root.querySelector('[data-sfj-filters]');
		const cards = root.querySelectorAll('[data-sfj-job-card]');
		if (!filters || !cards.length) return;

		const selects = Array.from(filters.querySelectorAll('select[data-sfj-filter]'));
		const clearButton = filters.querySelector('[data-sfj-filter-clear]');

		function hasUserFilters() {
			return selects.some(
				(select) => select.dataset.sfjFilterLocked !== 'true' && select.value !== ''
			);
		}

		function syncClearButton() {
			if (!clearButton) {
				return;
			}

			const visible = hasUserFilters();
			clearButton.hidden = !visible;
			clearButton.disabled = !visible;
		}

		function clearFilters() {
			selects.forEach((select) => {
				if (select.dataset.sfjFilterLocked === 'true') {
					return;
				}

				select.value = '';
			});

			applyFilters();
		}

		function getAllActiveFilters() {
			const active = {};

			selects.forEach((select) => {
				if (select.value) {
					active[select.dataset.sfjFilter] = select.value;
				}
			});

			return active;
		}

		/** Active filters except one axis (for facet option availability). */
		function getActiveFiltersExcept(excludedField) {
			const active = {};

			selects.forEach((select) => {
				const field = select.dataset.sfjFilter;

				if (select.value && field !== excludedField) {
					active[field] = select.value;
				}
			});

			return active;
		}

		function cardMatches(card, active) {
			return Object.keys(active).every((field) => {
				const attr = field.replace(/_/g, '-');
				return (card.getAttribute('data-' + attr) || '') === active[field];
			});
		}

		function getSelectableOptions(select) {
			return Array.from(select.options).filter(
				(option) => option.value && !option.disabled && !option.hidden
			);
		}

		function getDefinedOptionCount(select) {
			return Array.from(select.options).filter((option) => option.value).length;
		}

		function syncFilterFieldVisibility() {
			let changed = false;

			selects.forEach((select) => {
				const field = select.closest('.sfj-filters__field');
				if (!field) {
					return;
				}

				if (select.dataset.sfjFilterLocked === 'true') {
					field.hidden = true;
					return;
				}

				if (select.value) {
					field.hidden = false;
					return;
				}

				const definedOptions = getDefinedOptionCount(select);

				if (definedOptions < 2) {
					field.hidden = true;

					const available = getSelectableOptions(select);
					if (available.length === 1 && select.value !== available[0].value) {
						select.value = available[0].value;
						changed = true;
					}

					return;
				}

				field.hidden = false;
			});

			return changed;
		}

		function syncFilterOptions() {
			let changed = false;

			selects.forEach((select) => {
				if (select.dataset.sfjFilterLocked === 'true') {
					return;
				}

				const field = select.dataset.sfjFilter;
				const context = getActiveFiltersExcept(field);

				Array.from(select.options).forEach((option) => {
					if (!option.value) {
						option.hidden = false;
						option.disabled = false;
						return;
					}

					const attr = field.replace(/_/g, '-');
					const available = Array.from(cards).some((card) => {
						const cardValue = card.getAttribute('data-' + attr) || '';
						return cardValue === option.value && cardMatches(card, context);
					});

					option.hidden = !available;
					option.disabled = !available;
				});

				if (select.value && select.selectedOptions[0]?.disabled) {
					select.value = '';
					changed = true;
				}
			});

			return changed;
		}

		function applyFilters() {
			let attempts = selects.length;

			while (attempts > 0) {
				const optionsChanged = syncFilterOptions();
				const visibilityChanged = syncFilterFieldVisibility();

				if (!optionsChanged && !visibilityChanged) {
					break;
				}

				attempts -= 1;
			}

			const active = getAllActiveFilters();

			cards.forEach((card) => {
				card.hidden = !cardMatches(card, active);
			});

			syncClearButton();
		}

		selects.forEach((select) => select.addEventListener('change', applyFilters));

		if (clearButton) {
			clearButton.addEventListener('click', clearFilters);
		}

		applyFilters();
	});

	const MOBILE_LAYOUT_MAX = 767;
	const layoutStorageKey = 'sfj_jobs_layout';

	function isMobileLayout() {
		return window.matchMedia('(max-width: ' + MOBILE_LAYOUT_MAX + 'px)').matches;
	}

	function setLayoutMode(root, mode) {
		const effectiveMode = isMobileLayout() ? 'list' : mode;

		root.classList.remove('sfj-jobs--list', 'sfj-jobs--grid', 'sfj-jobs--no-card');

		if (effectiveMode === 'grid') {
			root.classList.add('sfj-jobs--grid');
		} else {
			root.classList.add('sfj-jobs--list', 'sfj-jobs--no-card');
		}

		const toggle = root.querySelector('[data-sfj-layout-toggle]');
		if (!toggle) {
			return;
		}

		toggle.querySelectorAll('[data-sfj-layout-btn]').forEach((button) => {
			const active = button.dataset.sfjLayoutBtn === effectiveMode;
			button.setAttribute('aria-pressed', active ? 'true' : 'false');
			button.classList.toggle('is-active', active);
		});
	}

	document.querySelectorAll('[data-sfj-jobs][data-sfj-initial-layout]').forEach((root) => {
		const toggle = root.querySelector('[data-sfj-layout-toggle]');
		if (!toggle) {
			return;
		}

		let mode = root.dataset.sfjInitialLayout === 'grid' ? 'grid' : 'list';

		if (!isMobileLayout()) {
			try {
				const stored = sessionStorage.getItem(layoutStorageKey);
				if (stored === 'grid' || stored === 'list') {
					mode = stored;
				}
			} catch (error) {
				// sessionStorage may be unavailable.
			}
		}

		setLayoutMode(root, mode);

		toggle.addEventListener('click', (event) => {
			const button = event.target.closest('[data-sfj-layout-btn]');
			if (!button || isMobileLayout()) {
				return;
			}

			const nextMode = button.dataset.sfjLayoutBtn === 'grid' ? 'grid' : 'list';
			setLayoutMode(root, nextMode);

			try {
				sessionStorage.setItem(layoutStorageKey, nextMode);
			} catch (error) {
				// sessionStorage may be unavailable.
			}
		});

		window.addEventListener('resize', () => {
			if (isMobileLayout()) {
				setLayoutMode(root, 'list');
				return;
			}

			let restored = root.dataset.sfjInitialLayout === 'grid' ? 'grid' : 'list';
			try {
				const stored = sessionStorage.getItem(layoutStorageKey);
				if (stored === 'grid' || stored === 'list') {
					restored = stored;
				}
			} catch (error) {
				// sessionStorage may be unavailable.
			}

			setLayoutMode(root, restored);
		});
	});
})();
