/**
 * Wedding Widget - Elementor editor integration.
 *
 * Adds a "Wedding Widget" launcher (heart icon) into the canvas add-section row
 * and opens a custom template library modal (search + thumbnails) that inserts
 * the selected template into the current document.
 *
 * Targets Elementor 3.x editor internals ($e.run, document containers).
 *
 * @package WeddingWidget
 */
(function ($) {
	'use strict';

	var cfg = window.WWEditor || {};
	var i18n = cfg.i18n || {};
	var modalEl = null;
	var cache = null;

	/* ----- AJAX helpers --------------------------------------------------- */
	function ajax(action, params) {
		var body = new FormData();
		body.append('action', action);
		body.append('nonce', cfg.nonce || '');
		Object.keys(params || {}).forEach(function (k) { body.append(k, params[k]); });
		return fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); });
	}

	/* ----- Insert into the document -------------------------------------- */
	function getRootContainer() {
		try {
			if (window.elementor && elementor.documents && elementor.documents.getCurrent()) {
				var c = elementor.documents.getCurrent().container;
				if (c) { return c; }
			}
			if (window.elementor && typeof elementor.getPreviewContainer === 'function') {
				return elementor.getPreviewContainer();
			}
		} catch (e) {}
		return null;
	}

	function insertContent(content) {
		if (!window.$e || !Array.isArray(content) || !content.length) {
			return false;
		}
		var container = getRootContainer();
		if (!container) { return false; }

		content.forEach(function (model) {
			try {
				$e.run('document/elements/create', {
					container: container,
					model: model,
					options: { edit: false }
				});
			} catch (err) {
				if (window.console) { console.error('[Wedding Widget] insert failed', err); }
			}
		});
		return true;
	}

	function insertTemplate(id, btn) {
		var original = btn ? btn.textContent : '';
		if (btn) { btn.disabled = true; btn.textContent = i18n.inserting || 'Inserting...'; }

		ajax('ww_template_content', { id: id }).then(function (json) {
			if (json && json.success && json.data && insertContent(json.data.content)) {
				closeModal();
			} else {
				window.alert((json && json.data && json.data.message) || i18n.error || 'Error');
			}
		}).catch(function () {
			window.alert(i18n.error || 'Error');
		}).finally(function () {
			if (btn) { btn.disabled = false; btn.textContent = original; }
		});
	}

	/* ----- Modal ---------------------------------------------------------- */
	var activeCat = '';

	function buildModal() {
		var overlay = document.createElement('div');
		overlay.className = 'ww-modal';
		overlay.innerHTML =
			'<div class="ww-modal__dialog" role="dialog" aria-modal="true">' +
				'<div class="ww-modal__header">' +
					'<h2 class="ww-modal__title"><i class="eicon-heart"></i> ' + escapeHtml(i18n.title || 'Templates') + '</h2>' +
					'<button type="button" class="ww-modal__close" aria-label="Close">&times;</button>' +
				'</div>' +
				'<div class="ww-modal__toolbar">' +
					'<input type="search" class="ww-modal__search" placeholder="' + escapeAttr(i18n.search || 'Search...') + '">' +
				'</div>' +
				'<div class="ww-modal__tabs" data-tabs></div>' +
				'<div class="ww-modal__body"><div class="ww-modal__grid" data-grid></div></div>' +
			'</div>';

		overlay.addEventListener('click', function (e) {
			if (e.target === overlay) { closeModal(); }
		});
		overlay.querySelector('.ww-modal__close').addEventListener('click', closeModal);
		overlay.querySelector('.ww-modal__search').addEventListener('input', function () {
			applyFilter(overlay);
		});

		document.body.appendChild(overlay);
		return overlay;
	}

	function applyFilter(overlay) {
		var term = (overlay.querySelector('.ww-modal__search').value || '').toLowerCase();
		overlay.querySelectorAll('.ww-card').forEach(function (card) {
			var title = (card.getAttribute('data-title') || '').toLowerCase();
			var cats = (card.getAttribute('data-cats') || '').split(' ');
			var matchTerm = (term === '' || title.indexOf(term) > -1);
			var matchCat = (activeCat === '' || cats.indexOf(activeCat) > -1);
			card.style.display = (matchTerm && matchCat) ? '' : 'none';
		});
	}

	function renderTabs(overlay, templates) {
		var tabsEl = overlay.querySelector('[data-tabs]');
		tabsEl.innerHTML = '';

		// Collect unique categories.
		var seen = {};
		var cats = [];
		templates.forEach(function (tpl) {
			(tpl.categories || []).forEach(function (c) {
				if (!seen[c.slug]) {
					seen[c.slug] = true;
					cats.push(c);
				}
			});
		});

		if (!cats.length) { return; } // No categories: hide the tab row entirely.

		function makeTab(slug, label) {
			var tab = document.createElement('button');
			tab.type = 'button';
			tab.className = 'ww-tab' + (slug === activeCat ? ' is-active' : '');
			tab.textContent = label;
			tab.addEventListener('click', function () {
				activeCat = slug;
				tabsEl.querySelectorAll('.ww-tab').forEach(function (t) { t.classList.remove('is-active'); });
				tab.classList.add('is-active');
				applyFilter(overlay);
			});
			return tab;
		}

		tabsEl.appendChild(makeTab('', i18n.all || 'All'));
		cats.forEach(function (c) { tabsEl.appendChild(makeTab(c.slug, c.name)); });
	}

	function renderGrid(overlay, templates) {
		var grid = overlay.querySelector('[data-grid]');
		grid.innerHTML = '';

		if (!templates || !templates.length) {
			grid.innerHTML = '<p class="ww-modal__empty">' + escapeHtml(i18n.empty || 'No templates.') + '</p>';
			renderTabs(overlay, []);
			return;
		}

		templates.forEach(function (tpl) {
			var slugs = (tpl.categories || []).map(function (c) { return c.slug; }).join(' ');
			var card = document.createElement('div');
			card.className = 'ww-card';
			card.setAttribute('data-title', tpl.title || '');
			card.setAttribute('data-cats', slugs);
			var thumb = tpl.thumbnail
				? '<div class="ww-card__thumb" style="background-image:url(\'' + encodeURI(tpl.thumbnail) + '\')"></div>'
				: '<div class="ww-card__thumb ww-card__thumb--empty"><i class="eicon-heart"></i></div>';
			card.innerHTML =
				thumb +
				'<div class="ww-card__foot">' +
					'<span class="ww-card__title"></span>' +
					'<button type="button" class="ww-card__insert">' + escapeHtml(i18n.insert || 'Insert') + '</button>' +
				'</div>';
			card.querySelector('.ww-card__title').textContent = tpl.title || '';
			card.querySelector('.ww-card__insert').addEventListener('click', function () {
				insertTemplate(tpl.id, this);
			});
			grid.appendChild(card);
		});

		renderTabs(overlay, templates);
		applyFilter(overlay);
	}

	function openModal() {
		if (!modalEl) { modalEl = buildModal(); }
		modalEl.classList.add('is-open');
		activeCat = '';
		var search = modalEl.querySelector('.ww-modal__search');
		if (search) { search.value = ''; }

		var grid = modalEl.querySelector('[data-grid]');
		if (cache) {
			renderGrid(modalEl, cache);
		} else {
			grid.innerHTML = '<p class="ww-modal__empty">…</p>';
			ajax('ww_list_templates', {}).then(function (json) {
				cache = (json && json.success && json.data) ? json.data.templates : [];
				renderGrid(modalEl, cache);
			}).catch(function () {
				renderGrid(modalEl, []);
			});
		}
		if (search) { search.focus(); }
	}

	function closeModal() {
		if (modalEl) { modalEl.classList.remove('is-open'); }
	}

	/* ----- Launcher button in the preview iframe ------------------------- */
	function setupLauncher() {
		if (!window.elementor || !elementor.$preview || !elementor.$preview[0]) { return; }
		var doc = elementor.$preview[0].contentDocument;
		if (!doc || !doc.body) { return; }

		function addButtons() {
			var areas = doc.querySelectorAll('.elementor-add-new-section');
			areas.forEach(function (area) {
				if (area.querySelector('.ww-add-template-button')) { return; }
				var btn = doc.createElement('div');
				btn.className = 'elementor-add-section-area-button ww-add-template-button';
				btn.setAttribute('title', i18n.library || 'Wedding Widget');
				btn.innerHTML = '<i class="eicon-heart" aria-hidden="true"></i>';
				btn.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					openModal();
				});
				area.appendChild(btn);
			});
		}

		addButtons();
		try {
			var obs = new MutationObserver(addButtons);
			obs.observe(doc.body, { childList: true, subtree: true });
		} catch (e) {}
	}

	/* ----- Utils ---------------------------------------------------------- */
	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	function escapeAttr(s) { return escapeHtml(s); }

	/* ----- Boot ----------------------------------------------------------- */
	$(window).on('elementor:init', function () {
		elementor.on('preview:loaded', setupLauncher);
	});

	// Close on Escape.
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') { closeModal(); }
	});
})(jQuery);
