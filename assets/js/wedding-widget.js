/**
 * Wedding Widget - frontend behaviour (vanilla JS, no jQuery dependency).
 *
 * @package WeddingWidget
 */
(function () {
	'use strict';

	var data = window.WeddingWidgetData || {};
	var i18n = data.i18n || {};

	/* --------------------------------------------------------------------- */
	/* Ownership tokens (localStorage)                                       */
	/* --------------------------------------------------------------------- */
	function getTokens() {
		try {
			return JSON.parse(localStorage.getItem('wwTokens') || '{}');
		} catch (e) {
			return {};
		}
	}
	function saveToken(id, token) {
		if (!id || !token) { return; }
		var t = getTokens();
		t[id] = token;
		try { localStorage.setItem('wwTokens', JSON.stringify(t)); } catch (e) {}
	}
	function getToken(id) {
		return getTokens()[id] || '';
	}
	function canManage(id) {
		return !!data.canModerate || !!getToken(id);
	}

	function ajax(action, params) {
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', data.nonce || '');
		Object.keys(params).forEach(function (k) { body.append(k, params[k]); });
		return fetch(data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		}).then(function (r) { return r.json(); });
	}

	function mkBtn(label, cls) {
		var b = document.createElement('button');
		b.type = 'button';
		b.className = cls;
		b.textContent = label;
		return b;
	}

	/* --------------------------------------------------------------------- */
	/* Countdown                                                             */
	/* --------------------------------------------------------------------- */
	function initCountdown(el) {
		if (el.dataset.wwInit) { return; }
		el.dataset.wwInit = '1';

		var target = parseInt(el.getAttribute('data-target'), 10) * 1000;
		var digits = {
			days: el.querySelector('[data-unit="days"]'),
			hours: el.querySelector('[data-unit="hours"]'),
			minutes: el.querySelector('[data-unit="minutes"]'),
			seconds: el.querySelector('[data-unit="seconds"]')
		};
		function pad(n) { return (n < 10 ? '0' : '') + n; }
		function tick() {
			var diff = target - Date.now();
			if (diff < 0) { diff = 0; }
			var s = Math.floor(diff / 1000);
			if (digits.days) { digits.days.textContent = pad(Math.floor(s / 86400)); }
			if (digits.hours) { digits.hours.textContent = pad(Math.floor((s % 86400) / 3600)); }
			if (digits.minutes) { digits.minutes.textContent = pad(Math.floor((s % 3600) / 60)); }
			if (digits.seconds) { digits.seconds.textContent = pad(s % 60); }
			if (diff <= 0 && el._wwTimer) { clearInterval(el._wwTimer); }
		}
		tick();
		el._wwTimer = setInterval(tick, 1000);
	}

	/* --------------------------------------------------------------------- */
	/* Cover reveal                                                          */
	/* --------------------------------------------------------------------- */
	function initCover(el) {
		if (el.dataset.wwInit) { return; }
		el.dataset.wwInit = '1';

		var btn = el.querySelector('[data-ww-cover-open]');
		if (!btn) { return; }

		var effect = el.getAttribute('data-effect') || 'slide-up';
		var duration = parseInt(el.getAttribute('data-duration'), 10) || 700;

		btn.addEventListener('click', function () {
			el.style.transition = 'transform ' + duration + 'ms ease, opacity ' + duration + 'ms ease';
			el.classList.add('is-opening', 'ww-cover--' + effect);
			window.setTimeout(function () {
				el.classList.add('is-open');
				el.style.display = 'none';
				document.documentElement.classList.remove('ww-cover-lock');
				document.body.classList.remove('ww-cover-lock');
			}, duration);
		});

		document.documentElement.classList.add('ww-cover-lock');
		document.body.classList.add('ww-cover-lock');
	}

	/* --------------------------------------------------------------------- */
	/* Copy to clipboard                                                     */
	/* --------------------------------------------------------------------- */
	function copyToClipboard(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}
		return new Promise(function (resolve, reject) {
			try {
				var ta = document.createElement('textarea');
				ta.value = text;
				ta.style.position = 'fixed';
				ta.style.opacity = '0';
				document.body.appendChild(ta);
				ta.focus();
				ta.select();
				document.execCommand('copy');
				document.body.removeChild(ta);
				resolve();
			} catch (e) { reject(e); }
		});
	}

	function initCopy(el) {
		if (el.dataset.wwInit) { return; }
		el.dataset.wwInit = '1';
		var btn = el.querySelector('[data-ww-copy-btn]');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			var text = btn.getAttribute('data-copy') || '';
			var label = btn.getAttribute('data-label') || '';
			var copied = btn.getAttribute('data-copied') || (i18n.copied || 'Copied!');
			copyToClipboard(text).then(function () {
				btn.textContent = copied;
				btn.classList.add('is-copied');
				window.setTimeout(function () {
					btn.textContent = label;
					btn.classList.remove('is-copied');
				}, 1500);
			});
		});
	}

	/* --------------------------------------------------------------------- */
	/* RSVP / Wishes                                                         */
	/* --------------------------------------------------------------------- */
	function initRsvp(el) {
		if (el.dataset.wwInit) { return; }
		el.dataset.wwInit = '1';

		var form = el.querySelector('[data-ww-rsvp-form]');
		var feedback = form ? form.querySelector('[data-ww-rsvp-feedback]') : null;
		var list = el.querySelector('[data-ww-rsvp-list]');
		var postId = el.getAttribute('data-post');
		var kind = el.getAttribute('data-kind') || 'ww_rsvp';

		var allowReply = el.getAttribute('data-allow-reply') === '1';
		var allowEdit = el.getAttribute('data-allow-edit') === '1';

		var attLabels = {
			attending: el.getAttribute('data-label-attending') || '',
			not_attending: el.getAttribute('data-label-not-attending') || '',
			maybe: el.getAttribute('data-label-maybe') || ''
		};
		var txt = {
			reply: el.getAttribute('data-txt-reply') || (i18n.reply || 'Reply'),
			edit: el.getAttribute('data-txt-edit') || (i18n.edit || 'Edit'),
			del: el.getAttribute('data-txt-delete') || (i18n.delete || 'Delete'),
			save: el.getAttribute('data-txt-save') || (i18n.save || 'Save'),
			cancel: el.getAttribute('data-txt-cancel') || (i18n.cancel || 'Cancel'),
			confirm: el.getAttribute('data-txt-confirm') || (i18n.confirmDelete || 'Delete this?'),
			phName: el.getAttribute('data-ph-name') || '',
			phReply: el.getAttribute('data-ph-reply') || (i18n.replyPlaceholder || '')
		};

		/* ----- pagination ----- */
		var perPage = parseInt(el.getAttribute('data-per-page'), 10) || 0;
		var pager = el.querySelector('[data-ww-pagination]');
		var prevBtn = el.querySelector('[data-ww-prev]');
		var nextBtn = el.querySelector('[data-ww-next]');
		var pageInfo = el.querySelector('[data-ww-page-info]');
		var currentPage = 1;

		function topItems() {
			return list ? Array.prototype.slice.call(list.children).filter(function (n) {
				return n.classList && n.classList.contains('ww-rsvp__item');
			}) : [];
		}
		function renderPage() {
			if (!list || !perPage) { return; }
			var items = topItems();
			var pages = Math.max(1, Math.ceil(items.length / perPage));
			if (currentPage > pages) { currentPage = pages; }
			if (currentPage < 1) { currentPage = 1; }
			var start = (currentPage - 1) * perPage;
			items.forEach(function (li, i) {
				li.style.display = (i >= start && i < start + perPage) ? '' : 'none';
			});
			if (pager) {
				if (items.length > perPage) {
					pager.hidden = false;
					if (pageInfo) { pageInfo.textContent = currentPage + ' / ' + pages; }
					if (prevBtn) { prevBtn.disabled = currentPage <= 1; }
					if (nextBtn) { nextBtn.disabled = currentPage >= pages; }
				} else {
					pager.hidden = true;
				}
			}
			if (list.scrollTo) { list.scrollTo({ top: 0 }); } else { list.scrollTop = 0; }
		}
		if (perPage && prevBtn) { prevBtn.addEventListener('click', function () { if (currentPage > 1) { currentPage--; renderPage(); } }); }
		if (perPage && nextBtn) { nextBtn.addEventListener('click', function () { currentPage++; renderPage(); }); }

		/* ----- helpers ----- */
		function setFeedback(target, msg, isError) {
			if (!target) { return; }
			target.textContent = msg;
			target.className = 'ww-rsvp__feedback' + (isError ? ' is-error' : ' is-success');
		}
		function badgeClass(att) { return 'ww-rsvp__badge ww-rsvp__badge--' + String(att).replace(/_/g, '-'); }

		function getAttFromNode(node) {
			var badge = node.firstElementChild.querySelector('.ww-rsvp__badge');
			if (!badge) { return ''; }
			var m = badge.className.match(/ww-rsvp__badge--([a-z-]+)/);
			return m ? m[1].replace(/-/g, '_') : '';
		}

		function bumpStat(att, delta) {
			function inc(node) {
				if (!node) { return; }
				var n = parseInt(String(node.textContent).replace(/\D/g, ''), 10) || 0;
				node.textContent = Math.max(0, n + delta);
			}
			inc(el.querySelector('[data-stat="total"]'));
			if (att) { inc(el.querySelector('[data-stat="' + att + '"]')); }
		}

		function buildNode(payload, isReply) {
			var li = document.createElement('li');
			li.className = isReply ? 'ww-rsvp__reply' : 'ww-rsvp__item';
			li.setAttribute('data-comment-id', payload.commentId);
			var hasBadge = !isReply && payload.attendanceText && payload.attendance;
			li.innerHTML =
				'<div class="ww-rsvp__row">' +
					'<span class="ww-rsvp__avatar"></span>' +
					'<div class="ww-rsvp__body">' +
						'<div class="ww-rsvp__item-head"><span class="ww-rsvp__item-name"></span>' +
							(hasBadge ? '<span class="' + badgeClass(payload.attendance) + '"></span>' : '') +
						'</div>' +
						'<div class="ww-rsvp__item-message" data-role="message"></div>' +
						'<div class="ww-rsvp__item-meta"><span class="ww-rsvp__item-date"></span><span class="ww-rsvp__actions" data-role="actions"></span></div>' +
					'</div>' +
				'</div>' +
				(isReply ? '' : '<ul class="ww-rsvp__replies" data-role="replies"></ul>');
			var avatar = li.querySelector('.ww-rsvp__avatar');
			avatar.textContent = payload.initials || '?';
			if (payload.avatarColor) { avatar.style.backgroundColor = payload.avatarColor; }
			li.querySelector('.ww-rsvp__item-name').textContent = payload.name;
			if (hasBadge) {
				li.querySelector('.ww-rsvp__badge').textContent = attLabels[payload.attendance] || payload.attendanceText;
			}
			li.querySelector('[data-role="message"]').textContent = payload.message;
			li.querySelector('.ww-rsvp__item-date').textContent = payload.date;
			return li;
		}

		function repliesOf(node) {
			return node.querySelector(':scope > [data-role="replies"]');
		}

		function startEdit(node) {
			var row = node.firstElementChild;
			var msgEl = row.querySelector('[data-role="message"]');
			if (msgEl.dataset.editing) { return; }
			msgEl.dataset.editing = '1';
			var id = node.getAttribute('data-comment-id');
			var original = msgEl.textContent;

			var box = document.createElement('div');
			box.className = 'ww-rsvp__edit';
			var ta = document.createElement('textarea');
			ta.className = 'ww-rsvp__field';
			ta.rows = 3;
			ta.value = original;
			var bar = document.createElement('div');
			bar.className = 'ww-rsvp__edit-actions';
			var save = mkBtn(txt.save, 'ww-rsvp__action ww-rsvp__action--save');
			var cancel = mkBtn(txt.cancel, 'ww-rsvp__action ww-rsvp__action--cancel');
			bar.appendChild(save);
			bar.appendChild(cancel);
			box.appendChild(ta);
			box.appendChild(bar);
			msgEl.style.display = 'none';
			msgEl.parentNode.insertBefore(box, msgEl.nextSibling);

			function close() {
				box.remove();
				msgEl.style.display = '';
				delete msgEl.dataset.editing;
			}
			cancel.addEventListener('click', close);
			save.addEventListener('click', function () {
				var val = ta.value.trim();
				if (!val) { return; }
				save.disabled = true;
				ajax('ww_wish_edit', { comment_id: id, token: getToken(id), message: val }).then(function (json) {
					if (json && json.success) {
						msgEl.textContent = json.data.message;
						close();
					} else {
						save.disabled = false;
						alert((json && json.data && json.data.message) || (i18n.error || 'Error'));
					}
				}).catch(function () { save.disabled = false; });
			});
		}

		function doDelete(node, isReply) {
			var id = node.getAttribute('data-comment-id');
			if (!window.confirm(txt.confirm)) { return; }
			ajax('ww_wish_delete', { comment_id: id, token: getToken(id) }).then(function (json) {
				if (json && json.success) {
					if (!isReply) {
						bumpStat(getAttFromNode(node), -1);
					}
					node.remove();
					if (!isReply) { renderPage(); }
				} else {
					alert((json && json.data && json.data.message) || (i18n.error || 'Error'));
				}
			});
		}

		function toggleReplyForm(node) {
			var existing = node.querySelector(':scope > .ww-rsvp__reply-form');
			if (existing) { existing.remove(); return; }
			var id = node.getAttribute('data-comment-id');

			var form2 = document.createElement('form');
			form2.className = 'ww-rsvp__reply-form';
			form2.innerHTML =
				'<input type="text" class="ww-rsvp__field" name="name" placeholder="' + txt.phName.replace(/"/g, '&quot;') + '" required>' +
				'<textarea class="ww-rsvp__field" name="message" rows="2" placeholder="' + txt.phReply.replace(/"/g, '&quot;') + '" required></textarea>' +
				'<div class="ww-rsvp__edit-actions">' +
					'<button type="submit" class="ww-rsvp__action ww-rsvp__action--save">' + txt.save + '</button>' +
					'<button type="button" class="ww-rsvp__action ww-rsvp__action--cancel" data-cancel>' + txt.cancel + '</button>' +
				'</div>';
			var replies = repliesOf(node);
			node.insertBefore(form2, replies);

			form2.querySelector('[data-cancel]').addEventListener('click', function () { form2.remove(); });
			form2.addEventListener('submit', function (e) {
				e.preventDefault();
				var name = (form2.elements.name.value || '').trim();
				var message = (form2.elements.message.value || '').trim();
				if (!name || !message) { return; }
				var submit = form2.querySelector('button[type="submit"]');
				submit.disabled = true;
				ajax('ww_rsvp_submit', {
					post_id: postId, name: name, message: message,
					attendance: '', kind: 'ww_wish', parent: id
				}).then(function (json) {
					if (json && json.success) {
						saveToken(json.data.commentId, json.data.token);
						var rnode = buildNode(json.data, true);
						replies.appendChild(rnode);
						decorate(rnode, true);
						form2.remove();
					} else {
						submit.disabled = false;
						alert((json && json.data && json.data.message) || (i18n.error || 'Error'));
					}
				}).catch(function () { submit.disabled = false; });
			});
		}

		function decorate(node, isReply) {
			var row = node.firstElementChild;
			var actions = row.querySelector('[data-role="actions"]');
			if (!actions || actions.dataset.done) { return; }
			actions.dataset.done = '1';
			var id = node.getAttribute('data-comment-id');

			if (!isReply && allowReply) {
				var rb = mkBtn(txt.reply, 'ww-rsvp__action ww-rsvp__action--reply');
				rb.addEventListener('click', function () { toggleReplyForm(node); });
				actions.appendChild(rb);
			}
			if (allowEdit && canManage(id)) {
				var eb = mkBtn(txt.edit, 'ww-rsvp__action ww-rsvp__action--edit');
				eb.addEventListener('click', function () { startEdit(node); });
				var db = mkBtn(txt.del, 'ww-rsvp__action ww-rsvp__action--delete');
				db.addEventListener('click', function () { doDelete(node, isReply); });
				actions.appendChild(eb);
				actions.appendChild(db);
			}
		}

		function decorateAll() {
			if (!list) { return; }
			topItems().forEach(function (item) {
				decorate(item, false);
				var rep = repliesOf(item);
				if (rep) {
					Array.prototype.slice.call(rep.children).forEach(function (r) {
						if (r.classList.contains('ww-rsvp__reply')) { decorate(r, true); }
					});
				}
			});
		}

		decorateAll();
		renderPage();

		/* ----- main submit ----- */
		if (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				var name = (form.elements.name && form.elements.name.value || '').trim();
				var message = (form.elements.message && form.elements.message.value || '').trim();
				var attendance = form.elements.attendance ? form.elements.attendance.value : '';
				if (!name || !message) {
					setFeedback(feedback, i18n.required || 'Please fill in your name and message.', true);
					return;
				}
				var submitBtn = form.querySelector('.ww-rsvp__submit');
				if (submitBtn) { submitBtn.disabled = true; }
				setFeedback(feedback, i18n.sending || 'Sending...', false);

				ajax('ww_rsvp_submit', {
					post_id: postId, name: name, message: message,
					attendance: attendance, kind: kind, parent: 0
				}).then(function (json) {
					if (json && json.success) {
						setFeedback(feedback, i18n.thanks || 'Thank you!', false);
						saveToken(json.data.commentId, json.data.token);
						var empty = list ? list.querySelector('.ww-rsvp__empty') : null;
						if (empty) { empty.remove(); }
						var node = buildNode(json.data, false);
						if (list) { list.insertBefore(node, list.firstChild); }
						decorate(node, false);
						bumpStat(json.data.attendance, 1);
						if (perPage) { currentPage = 1; renderPage(); }
						if (form.elements.message) { form.elements.message.value = ''; }
					} else {
						setFeedback(feedback, (json && json.data && json.data.message) || (i18n.error || 'Error'), true);
					}
				}).catch(function () {
					setFeedback(feedback, i18n.error || 'Something went wrong.', true);
				}).finally(function () {
					if (submitBtn) { submitBtn.disabled = false; }
				});
			});
		}
	}

	/* --------------------------------------------------------------------- */
	/* Music                                                                 */
	/* --------------------------------------------------------------------- */
	var ytApiRequested = false;
	var ytReadyQueue = [];

	function whenYouTubeReady(cb) {
		if (window.YT && typeof window.YT.Player === 'function') { cb(); return; }
		ytReadyQueue.push(cb);
		var prev = window.onYouTubeIframeAPIReady;
		window.onYouTubeIframeAPIReady = function () {
			if (typeof prev === 'function') { prev(); }
			var queue = ytReadyQueue.slice();
			ytReadyQueue = [];
			queue.forEach(function (fn) { fn(); });
		};
		if (!ytApiRequested) {
			ytApiRequested = true;
			var tag = document.createElement('script');
			tag.src = 'https://www.youtube.com/iframe_api';
			var first = document.getElementsByTagName('script')[0];
			first.parentNode.insertBefore(tag, first);
		}
	}

	function extractVideoID(url) {
		if (!url) { return null; }
		var re = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
		var m = url.match(re);
		return m && m[7] && m[7].length === 11 ? m[7] : null;
	}

	function initMusic(el) {
		if (el.dataset.wwInit) { return; }
		el.dataset.wwInit = '1';

		var btn = el.querySelector('[data-ww-music-btn]');
		if (!btn) { return; }

		var autoplay = el.getAttribute('data-autoplay') === '1';
		var loop = el.getAttribute('data-loop') === '1';
		var playing = false;

		function setIcon(isPlaying) {
			el.classList.toggle('is-playing', isPlaying);
			btn.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
		}

		var yt = el.querySelector('.ww-music__yt');
		if (yt) {
			var videoId = extractVideoID(yt.getAttribute('data-video'));
			if (!videoId) { setIcon(false); return; }
			var holderId = 'ww-yt-' + Math.random().toString(36).slice(2);
			yt.innerHTML = '<div id="' + holderId + '"></div>';
			whenYouTubeReady(function () {
				var player = new window.YT.Player(holderId, {
					height: '1', width: '1', videoId: videoId,
					playerVars: { playsinline: 1, loop: loop ? 1 : 0, playlist: loop ? videoId : undefined },
					events: {
						onReady: function (e) { if (autoplay) { e.target.playVideo(); } else { setIcon(false); } },
						onStateChange: function (e) {
							if (e.data === window.YT.PlayerState.ENDED && loop) { player.seekTo(0); player.playVideo(); }
							if (e.data === window.YT.PlayerState.PLAYING) { playing = true; setIcon(true); }
							if (e.data === window.YT.PlayerState.PAUSED) { playing = false; setIcon(false); }
						}
					}
				});
				btn.addEventListener('click', function () {
					var st = player.getPlayerState();
					if (st === 1 || st === 3) { player.pauseVideo(); } else { player.playVideo(); }
				});
			});
			return;
		}

		var audio = el.querySelector('.ww-music__audio');
		if (!audio) { return; }
		audio.loop = loop;
		function play() {
			var p = audio.play();
			if (p && typeof p.catch === 'function') { p.catch(function () { playing = false; setIcon(false); }); }
			playing = true; setIcon(true);
		}
		function pause() { audio.pause(); playing = false; setIcon(false); }
		if (autoplay) { play(); } else { setIcon(false); }
		btn.addEventListener('click', function () { if (playing) { pause(); } else { play(); } });
	}

	/* --------------------------------------------------------------------- */
	/* Boot                                                                  */
	/* --------------------------------------------------------------------- */
	function initAll(root) {
		var scope = root || document;
		scope.querySelectorAll('[data-ww-countdown]').forEach(initCountdown);
		scope.querySelectorAll('[data-ww-cover]').forEach(initCover);
		scope.querySelectorAll('[data-ww-copy]').forEach(initCopy);
		scope.querySelectorAll('[data-ww-rsvp]').forEach(initRsvp);
		scope.querySelectorAll('[data-ww-music]').forEach(initMusic);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initAll(document); });
	} else {
		initAll(document);
	}

	if (window.jQuery) {
		window.jQuery(window).on('elementor/frontend/init', function () {
			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				['ww-countdown', 'ww-cover', 'ww-copy', 'ww-rsvp', 'ww-wishes', 'ww-music'].forEach(function (name) {
					elementorFrontend.hooks.addAction('frontend/element_ready/' + name + '.default', function ($scope) {
						initAll($scope[0]);
					});
				});
			}
		});
	}
})();
