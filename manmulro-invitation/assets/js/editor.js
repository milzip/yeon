/**
 * Manmulro Invitation - Interactive Editor & My Invitations Controller (`editor.js`)
 *
 * Implements:
 * - Real-time Mobile Preview (updates immediately on every field edit or template change #11, #15)
 * - Complete separation of Event Data and Template Design (#11)
 * - Flexible Fields add, edit, toggle visibility (표시/숨김), delete, and Drag & Drop reorder (#8, #9, #10)
 * - Category preset fields injection (#6, #50)
 * - Photo Gallery (up to 10 images) with upload, URL add, delete (#12, #13)
 * - Save Draft (DRAFT) / Publish (PUBLISHED) (#16, #17)
 * - My Invitations actions: Duplicate (#37), Archive (#16), Delete (#36), QR popup (#19)
 */
(function () {
	'use strict';

	function escapeHtml(str) {
		return String(str == null ? '' : str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	document.addEventListener('DOMContentLoaded', function () {
		initMyInvitationsActions();
		initEditorApp();
	});

	/**
	 * My Invitations Card Actions: Duplicate, Archive, Delete, QR Modal, Guestbook Delete (#28, #36, #37)
	 */
	function initMyInvitationsActions() {
		var cfg = window.mmInvMyConfig || window.mmInvEditorConfig;
		if (!cfg) return;

		document.addEventListener('click', function (e) {
			// Duplicate (#37)
			var dupBtn = e.target.closest('.mm-js-duplicate-inv');
			if (dupBtn) {
				var id = dupBtn.getAttribute('data-invitation-id');
				var fd = new FormData();
				fd.append('action', 'mm_inv_duplicate');
				fd.append('nonce', cfg.nonce);
				fd.append('invitation_id', id);

				fetch(cfg.ajaxUrl, { method: 'POST', body: fd })
					.then(function (r) {
						return r.json();
					})
					.then(function (res) {
						if (res.success && res.data && res.data.editor_url) {
							window.location.href = res.data.editor_url;
						} else if (res.data && res.data.message) {
							window.alert(res.data.message);
						}
					});
			}

			// Archive (#16, #36)
			var archBtn = e.target.closest('.mm-js-archive-inv');
			if (archBtn) {
				var archId = archBtn.getAttribute('data-invitation-id');
				var status = archBtn.getAttribute('data-status') || 'ARCHIVED';
				var fd2 = new FormData();
				fd2.append('action', 'mm_inv_change_status');
				fd2.append('nonce', cfg.nonce);
				fd2.append('invitation_id', archId);
				fd2.append('status', status);

				fetch(cfg.ajaxUrl, { method: 'POST', body: fd2 })
					.then(function (r) {
						return r.json();
					})
					.then(function () {
						window.location.reload();
					});
			}

			// Delete (#36)
			var delBtn = e.target.closest('.mm-js-delete-inv');
			if (delBtn) {
				if (!window.confirm('정말 이 초대장을 삭제하시겠습니까?')) return;
				var delId = delBtn.getAttribute('data-invitation-id');
				var fd3 = new FormData();
				fd3.append('action', 'mm_inv_delete');
				fd3.append('nonce', cfg.nonce);
				fd3.append('invitation_id', delId);

				fetch(cfg.ajaxUrl, { method: 'POST', body: fd3 })
					.then(function (r) {
						return r.json();
					})
					.then(function () {
						window.location.reload();
					});
			}

			// QR Popup (#19, #36)
			var qrBtn = e.target.closest('.mm-js-show-card-qr');
			var qrModal = document.getElementById('mm-my-qr-modal');
			if (qrBtn && qrModal) {
				var shortUrl = qrBtn.getAttribute('data-short-url');
				var target = document.getElementById('mm-my-qr-target');
				var label = document.getElementById('mm-my-qr-url-label');
				if (target) target.setAttribute('data-qr-url', shortUrl);
				if (label) label.textContent = shortUrl;
				qrModal.hidden = false;
				if (window.MMInvQR) window.MMInvQR.renderAll();
			}

			if (e.target.id === 'mm-close-qr-modal') {
				var modal = document.getElementById('mm-my-qr-modal');
				if (modal) modal.hidden = true;
			}

			// Guestbook Moderation (#28)
			var gbBtn = e.target.closest('.mm-js-gb-manage');
			if (gbBtn) {
				var entryId = gbBtn.getAttribute('data-entry-id');
				var op = gbBtn.getAttribute('data-op') || 'delete';
				var fd4 = new FormData();
				fd4.append('action', 'mm_inv_manage_guestbook');
				fd4.append('nonce', cfg.nonce);
				fd4.append('entry_id', entryId);
				fd4.append('operation', op);

				fetch(cfg.ajaxUrl, { method: 'POST', body: fd4 })
					.then(function (r) {
						return r.json();
					})
					.then(function () {
						window.location.reload();
					});
			}
		});
	}

	/**
	 * Invitation Editor App (#7 ~ #15)
	 */
	function initEditorApp() {
		var appEl = document.getElementById('mm-inv-editor-app');
		var cfg = window.mmInvEditorConfig;
		if (!appEl || !cfg) return;

		var state = JSON.parse(JSON.stringify(cfg.initialInvitation || {}));
		state.fields = Array.isArray(state.fields) ? state.fields : [];
		state.gallery_items = Array.isArray(state.gallery_items) ? state.gallery_items : [];

		var fieldsListEl = document.getElementById('mm-editor-fields-list');
		var galleryGridEl = document.getElementById('mm-editor-gallery-grid');
		var galleryCounterEl = document.getElementById('mm-gallery-counter');
		var previewScreenEl = document.getElementById('mm-live-preview-screen');
		var workspaceEl = appEl.querySelector('.mm-inv-editor-workspace');

		// Mobile Tabs ([편집] / [미리보기] #15)
		appEl.querySelectorAll('[data-editor-tab]').forEach(function (tabBtn) {
			tabBtn.addEventListener('click', function () {
				var targetTab = tabBtn.getAttribute('data-editor-tab');
				appEl.querySelectorAll('[data-editor-tab]').forEach(function (b) {
					b.classList.toggle('is-active', b === tabBtn);
				});
				if (workspaceEl) {
					workspaceEl.setAttribute('data-active-tab', targetTab);
				}
			});
		});

		// Category Selection (#6, #50)
		appEl.querySelectorAll('[data-category-slug]').forEach(function (chip) {
			chip.addEventListener('click', function () {
				state.category_slug = chip.getAttribute('data-category-slug');
				state.category_name = chip.getAttribute('data-category-name');
				appEl.querySelectorAll('[data-category-slug]').forEach(function (c) {
					c.classList.toggle('is-selected', c === chip);
				});
				renderLivePreview();
			});
		});

		// Inject Category Preset Fields (#50)
		var presetBtn = document.getElementById('mm-btn-apply-category-presets');
		if (presetBtn) {
			presetBtn.addEventListener('click', function () {
				var catObj = cfg.categories && cfg.categories[state.category_slug];
				if (!catObj || !Array.isArray(catObj.presets)) return;

				catObj.presets.forEach(function (p) {
					var exists = state.fields.some(function (f) {
						return f.label === p.label;
					});
					if (!exists) {
						state.fields.push({
							id: 'fld_' + Math.random().toString(36).slice(2, 8),
							type: p.type || 'custom',
							label: p.label,
							value: p.value || '',
							visible: true,
							order: state.fields.length + 1
						});
					}
				});
				renderFieldsEditor();
				renderLivePreview();
			});
		}

		// Template Selection (#11, #39, #40) — Switching template NEVER loses event data!
		appEl.querySelectorAll('[data-template-slug]').forEach(function (card) {
			card.addEventListener('click', function () {
				state.template = card.getAttribute('data-template-slug');
				appEl.querySelectorAll('[data-template-slug]').forEach(function (c) {
					c.classList.toggle('is-selected', c === card);
				});
				renderLivePreview();
			});
		});

		// Bind Basic Inputs
		function bindInput(id, key, isCheckbox) {
			var el = document.getElementById(id);
			if (!el) return;
			el.addEventListener(isCheckbox ? 'change' : 'input', function () {
				state[key] = isCheckbox ? el.checked : el.value;
				if (key === 'visibility') {
					var pwdWrap = document.getElementById('mm_ed_password_wrap');
					if (pwdWrap) pwdWrap.style.display = el.value === 'password' ? '' : 'none';
				}
				if (key === 'expiration_mode') {
					var expWrap = document.getElementById('mm_ed_expiration_date_wrap');
					if (expWrap) expWrap.style.display = el.value === 'custom' ? '' : 'none';
				}
				renderLivePreview();
			});
		}

		bindInput('mm_ed_title', 'title', false);
		bindInput('mm_ed_summary', 'summary', false);
		bindInput('mm_ed_event_date', 'event_date', false);
		bindInput('mm_ed_event_time', 'event_time', false);
		bindInput('mm_ed_event_end_time', 'event_end_time', false);
		bindInput('mm_ed_location_name', 'location_name', false);
		bindInput('mm_ed_address', 'address', false);
		bindInput('mm_ed_cover_url', 'cover_image_url', false);
		bindInput('mm_ed_rsvp_enabled', 'rsvp_enabled', true);
		bindInput('mm_ed_rsvp_deadline', 'rsvp_deadline', false);
		bindInput('mm_ed_guestbook_enabled', 'guestbook_enabled', true);
		bindInput('mm_ed_dday_enabled', 'dday_enabled', true);
		bindInput('mm_ed_visibility', 'visibility', false);
		bindInput('mm_ed_expiration_mode', 'expiration_mode', false);
		bindInput('mm_ed_expiration_date', 'expiration_date', false);
		bindInput('mm_ed_noindex', 'noindex', true);

		// Add Flexible Field (#8, #9)
		var addFieldBtn = document.getElementById('mm-btn-add-field');
		if (addFieldBtn) {
			addFieldBtn.addEventListener('click', function () {
				var typeSel = document.getElementById('mm-add-field-type');
				var labelIn = document.getElementById('mm-add-field-label');
				var type = typeSel ? typeSel.value : 'custom';
				var defaultLabel =
					cfg.fieldTypes && cfg.fieldTypes[type] ? cfg.fieldTypes[type].label : '항목';
				var label = labelIn && labelIn.value.trim() ? labelIn.value.trim() : defaultLabel;

				state.fields.push({
					id: 'fld_' + Math.random().toString(36).slice(2, 8),
					type: type,
					label: label,
					value: '',
					visible: true,
					order: state.fields.length + 1
				});

				if (labelIn) labelIn.value = '';
				renderFieldsEditor();
				renderLivePreview();
			});
		}

		// Render Flexible Fields List with Drag & Drop (#10)
		var dragSrcIndex = null;

		function renderFieldsEditor() {
			if (!fieldsListEl) return;
			fieldsListEl.innerHTML = '';

			state.fields.forEach(function (field, idx) {
				var row = document.createElement('div');
				row.className = 'mm-inv-flex-item' + (field.visible ? '' : ' is-hidden-field');
				row.draggable = true;
				row.setAttribute('data-index', idx);

				var isMultiline = field.type === 'textarea';
				var isDivider = field.type === 'divider';

				row.innerHTML =
					'<span class="mm-inv-drag-handle" title="드래그하여 순서 변경">☰</span>' +
					'<input type="text" class="mm-inv-input mm-js-fld-label" value="' +
					escapeHtml(field.label) +
					'" placeholder="항목명" />' +
					(isDivider
						? '<span style="color:#64748b;font-size:12px;">──────── 구분선 ────────</span>'
						: isMultiline
						? '<textarea rows="2" class="mm-inv-input mm-js-fld-value" placeholder="내용 입력">' +
						  escapeHtml(field.value) +
						  '</textarea>'
						: '<input type="text" class="mm-inv-input mm-js-fld-value" value="' +
						  escapeHtml(field.value) +
						  '" placeholder="내용 입력" />') +
					'<div class="mm-inv-flex-item__actions">' +
					'<button type="button" class="mm-inv-action-btn mm-js-fld-toggle">' +
					(field.visible ? '표시됨' : '숨김') +
					'</button>' +
					'<button type="button" class="mm-inv-action-btn mm-inv-action-btn--danger mm-js-fld-delete">삭제</button>' +
					'</div>';

				var labelInput = row.querySelector('.mm-js-fld-label');
				if (labelInput) {
					labelInput.addEventListener('input', function () {
						field.label = labelInput.value;
						renderLivePreview();
					});
				}

				var valInput = row.querySelector('.mm-js-fld-value');
				if (valInput) {
					valInput.addEventListener('input', function () {
						field.value = valInput.value;
						renderLivePreview();
					});
				}

				row.querySelector('.mm-js-fld-toggle').addEventListener('click', function () {
					field.visible = !field.visible;
					renderFieldsEditor();
					renderLivePreview();
				});

				row.querySelector('.mm-js-fld-delete').addEventListener('click', function () {
					state.fields.splice(idx, 1);
					renderFieldsEditor();
					renderLivePreview();
				});

				// Drag & Drop events (#10)
				row.addEventListener('dragstart', function (e) {
					dragSrcIndex = idx;
					row.classList.add('is-dragging');
					e.dataTransfer.effectAllowed = 'move';
				});

				row.addEventListener('dragend', function () {
					row.classList.remove('is-dragging');
				});

				row.addEventListener('dragover', function (e) {
					e.preventDefault();
					e.dataTransfer.dropEffect = 'move';
				});

				row.addEventListener('drop', function (e) {
					e.preventDefault();
					if (dragSrcIndex === null || dragSrcIndex === idx) return;
					var moved = state.fields.splice(dragSrcIndex, 1)[0];
					state.fields.splice(idx, 0, moved);
					dragSrcIndex = null;
					renderFieldsEditor();
					renderLivePreview();
				});

				fieldsListEl.appendChild(row);
			});
		}

		// Gallery Management (Up to 10 photos + Drag & Drop Reorder #13)
		var galleryDragIndex = null;

		function renderGalleryEditor() {
			if (!galleryGridEl) return;
			galleryGridEl.innerHTML = '';
			if (galleryCounterEl) {
				galleryCounterEl.textContent = String(state.gallery_items.length);
			}

			state.gallery_items.forEach(function (item, idx) {
				var url = item.thumb_url || item.url;
				var thumb = document.createElement('div');
				thumb.className = 'mm-inv-editor-gallery-thumb';
				thumb.draggable = true;
				thumb.title = '드래그하여 사진 순서 변경 (#13)';
				thumb.innerHTML =
					'<img src="' +
					escapeHtml(url) +
					'" alt="사진 ' +
					(idx + 1) +
					'" /><button type="button" title="삭제">&times;</button>';
				thumb.querySelector('button').addEventListener('click', function () {
					state.gallery_items.splice(idx, 1);
					renderGalleryEditor();
					renderLivePreview();
				});

				thumb.addEventListener('dragstart', function (e) {
					galleryDragIndex = idx;
					e.dataTransfer.effectAllowed = 'move';
				});
				thumb.addEventListener('dragover', function (e) {
					e.preventDefault();
					e.dataTransfer.dropEffect = 'move';
				});
				thumb.addEventListener('drop', function (e) {
					e.preventDefault();
					if (galleryDragIndex === null || galleryDragIndex === idx) return;
					var movedImg = state.gallery_items.splice(galleryDragIndex, 1)[0];
					state.gallery_items.splice(idx, 0, movedImg);
					galleryDragIndex = null;
					renderGalleryEditor();
					renderLivePreview();
				});

				galleryGridEl.appendChild(thumb);
			});
		}

		var addGalleryUrlBtn = document.getElementById('mm-btn-add-gallery-url');
		if (addGalleryUrlBtn) {
			addGalleryUrlBtn.addEventListener('click', function () {
				if (state.gallery_items.length >= 10) {
					window.alert('사진앨범은 최대 10장까지 등록할 수 있습니다.');
					return;
				}
				var urlIn = document.getElementById('mm_ed_gallery_url_input');
				if (urlIn && urlIn.value.trim()) {
					var u = urlIn.value.trim();
					state.gallery_items.push({ id: 0, url: u, thumb_url: u, full_url: u });
					urlIn.value = '';
					renderGalleryEditor();
					renderLivePreview();
				}
			});
		}

		// Local / AJAX File Upload for Cover & Gallery (#12, #13)
		var coverFileIn = document.getElementById('mm_ed_cover_file');
		if (coverFileIn) {
			coverFileIn.addEventListener('change', function () {
				if (!coverFileIn.files || !coverFileIn.files[0]) return;
				uploadOrPreviewFile(coverFileIn.files[0], function (imgObj) {
					state.cover_image_url = imgObj.cover_url || imgObj.url;
					state.cover_attachment_id = imgObj.id || 0;
					var urlInput = document.getElementById('mm_ed_cover_url');
					if (urlInput) urlInput.value = state.cover_image_url;
					renderLivePreview();
				});
			});
		}

		var galleryFileIn = document.getElementById('mm_ed_gallery_file');
		if (galleryFileIn) {
			galleryFileIn.addEventListener('change', function () {
				if (!galleryFileIn.files) return;
				Array.from(galleryFileIn.files).forEach(function (file) {
					if (state.gallery_items.length >= 10) return;
					uploadOrPreviewFile(file, function (imgObj) {
						if (state.gallery_items.length < 10) {
							state.gallery_items.push(imgObj);
							renderGalleryEditor();
							renderLivePreview();
						}
					});
				});
			});
		}

		function uploadOrPreviewFile(file, callback) {
			var fd = new FormData();
			fd.append('action', 'mm_inv_upload_image');
			fd.append('nonce', cfg.nonce);
			fd.append('image', file);

			fetch(cfg.ajaxUrl, { method: 'POST', body: fd })
				.then(function (r) {
					return r.json();
				})
				.then(function (res) {
					if (res.success && res.data) {
						callback({
							id: res.data.id,
							url: res.data.thumb_url,
							thumb_url: res.data.thumb_url,
							cover_url: res.data.cover_url,
							full_url: res.data.full_url
						});
					}
				})
				.catch(function () {
					var reader = new FileReader();
					reader.onload = function (ev) {
						callback({
							id: 0,
							url: ev.target.result,
							thumb_url: ev.target.result,
							cover_url: ev.target.result,
							full_url: ev.target.result
						});
					};
					reader.readAsDataURL(file);
				});
		}

		// Compute D-Day for Live Preview (#30)
		function computeDdayLabel(dateStr) {
			if (!dateStr) return '';
			var today = new Date();
			today.setHours(0, 0, 0, 0);
			var target = new Date(dateStr + 'T00:00:00');
			if (isNaN(target.getTime())) return '';
			var diff = Math.round((target.getTime() - today.getTime()) / 86400000);
			if (diff > 0) return 'D-' + diff;
			if (diff === 0) return 'D-DAY';
			return '행사가 종료되었습니다.';
		}

		// Render Real-Time Mobile Preview (#11, #15)
		function renderLivePreview() {
			if (!previewScreenEl) return;

			previewScreenEl.className =
				'mm-inv-phone-mockup__screen mm-inv-theme-' + escapeHtml(state.template || 'simple');

			var ddayLabel = state.dday_enabled ? computeDdayLabel(state.event_date) : '';
			var fieldsHtml = '';

			state.fields.forEach(function (f) {
				if (!f.visible) return;
				if (f.type === 'divider') {
					fieldsHtml += '<hr class="mm-inv-divider" />';
					return;
				}
				if (!f.value && f.type !== 'gallery') return;

				if (f.type === 'title') {
					fieldsHtml +=
						'<div class="mm-inv-block"><h3 class="mm-inv-section-heading">' +
						escapeHtml(f.value) +
						'</h3></div>';
				} else if (f.type === 'textarea') {
					fieldsHtml +=
						'<div class="mm-inv-block">' +
						(f.label ? '<div class="mm-inv-block__label">' + escapeHtml(f.label) + '</div>' : '') +
						'<div class="mm-inv-block__multiline">' +
						escapeHtml(f.value).replace(/\n/g, '<br/>') +
						'</div></div>';
				} else if (f.type === 'phone') {
					fieldsHtml +=
						'<div class="mm-inv-block"><div class="mm-inv-field-row"><span class="mm-inv-field-row__label">' +
						escapeHtml(f.label) +
						'</span><span class="mm-inv-field-row__value">' +
						escapeHtml(f.value) +
						'</span></div><div class="mm-inv-contact-actions"><span class="mm-inv-action-chip">📞 전화하기</span><span class="mm-inv-action-chip">💬 문자 보내기</span></div></div>';
				} else {
					fieldsHtml +=
						'<div class="mm-inv-block"><div class="mm-inv-field-row"><span class="mm-inv-field-row__label">' +
						escapeHtml(f.label) +
						'</span><span class="mm-inv-field-row__value">' +
						escapeHtml(f.value) +
						'</span></div></div>';
				}
			});

			var galleryHtml = '';
			if (state.gallery_items.length > 0) {
				galleryHtml =
					'<section class="mm-inv-section"><div class="mm-inv-section-header"><h3 class="mm-inv-section-title">사진앨범</h3><span class="mm-inv-gallery-count">' +
					state.gallery_items.length +
					' / 10</span></div><div class="mm-inv-gallery-grid">' +
					state.gallery_items
						.map(function (g) {
							return (
								'<div class="mm-inv-gallery-item"><img src="' +
								escapeHtml(g.thumb_url || g.url) +
								'" alt="갤러리" /></div>'
							);
						})
						.join('') +
					'</div></section>';
			}

			var locationHtml = '';
			if (state.location_name || state.address) {
				locationHtml =
					'<section class="mm-inv-section"><h3 class="mm-inv-section-title">오시는 길 · 장소 안내</h3><div class="mm-inv-location-card">' +
					(state.location_name
						? '<p class="mm-inv-location-name">' + escapeHtml(state.location_name) + '</p>'
						: '') +
					(state.address
						? '<p class="mm-inv-location-address">' + escapeHtml(state.address) + '</p>'
						: '') +
					'<div class="mm-inv-location-buttons"><span class="mm-inv-btn mm-inv-btn--naver mm-inv-btn--sm">🗺️ 네이버 지도</span><span class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm">🧭 길찾기</span><span class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm">📋 주소 복사</span></div></div></section>';
			}

			var rsvpHtml = '';
			if (state.rsvp_enabled) {
				rsvpHtml =
					'<section class="mm-inv-section"><div class="mm-inv-section-header"><h3 class="mm-inv-section-title">참석 여부 (RSVP)</h3>' +
					(state.rsvp_deadline
						? '<span class="mm-inv-deadline-tag">마감: ' + escapeHtml(state.rsvp_deadline) + '</span>'
						: '') +
					'</div><div class="mm-inv-radio-group"><span class="mm-inv-radio-pill">참석</span><span class="mm-inv-radio-pill">불참</span><span class="mm-inv-radio-pill">미정</span></div></section>';
			}

			previewScreenEl.innerHTML =
				'<div class="mm-inv-container">' +
				'<header class="mm-inv-hero">' +
				'<div class="mm-inv-hero__top-badges">' +
				'<span class="mm-inv-category-pill">' +
				escapeHtml(state.category_name || '기타') +
				'</span>' +
				(ddayLabel ? '<span class="mm-inv-dday-pill">' + escapeHtml(ddayLabel) + '</span>' : '') +
				'</div>' +
				'<h1 class="mm-inv-hero__title">' +
				escapeHtml(state.title || '초대장 제목을 입력하세요') +
				'</h1>' +
				(state.summary
					? '<p class="mm-inv-hero__subtitle">' +
					  escapeHtml(state.summary).replace(/\n/g, '<br/>') +
					  '</p>'
					: '') +
				(state.cover_image_url
					? '<figure class="mm-inv-hero__cover"><img src="' +
					  escapeHtml(state.cover_image_url) +
					  '" alt="대표 이미지" /></figure>'
					: '') +
				'<div class="mm-inv-hero__meta">' +
				(state.event_date
					? '<div class="mm-inv-hero__meta-item"><strong>일시</strong><span>' +
					  escapeHtml(state.event_date + (state.event_time ? ' ' + state.event_time : '')) +
					  '</span></div>'
					: '') +
				(state.location_name
					? '<div class="mm-inv-hero__meta-item"><strong>장소</strong><span>' +
					  escapeHtml(state.location_name) +
					  '</span></div>'
					: '') +
				'</div>' +
				'</header>' +
				'<section class="mm-inv-section">' +
				fieldsHtml +
				'</section>' +
				galleryHtml +
				locationHtml +
				rsvpHtml +
				'</div>';
		}

		// Save Draft / Publish (#16, #17)
		function saveInvitation(targetStatus) {
			var fd = new FormData();
			fd.append('action', 'mm_inv_save');
			fd.append('nonce', cfg.nonce);
			fd.append('invitation_id', state.id || 0);
			fd.append('title', state.title || '새 초대장');
			fd.append('summary', state.summary || '');
			fd.append('category', state.category_slug || 'other');
			fd.append('template', state.template || 'simple');
			fd.append('status', targetStatus);
			fd.append('event_date', state.event_date || '');
			fd.append('event_time', state.event_time || '');
			fd.append('event_end_time', state.event_end_time || '');
			fd.append('location_name', state.location_name || '');
			fd.append('address', state.address || '');
			fd.append('cover_image_url', state.cover_image_url || '');
			fd.append('cover_attachment_id', state.cover_attachment_id || 0);
			fd.append('gallery_items', JSON.stringify(state.gallery_items));
			fd.append('fields', JSON.stringify(state.fields));
			fd.append('rsvp_enabled', state.rsvp_enabled ? '1' : '0');
			fd.append('rsvp_deadline', state.rsvp_deadline || '');
			fd.append('guestbook_enabled', state.guestbook_enabled ? '1' : '0');
			fd.append('dday_enabled', state.dday_enabled ? '1' : '0');
			fd.append('visibility', state.visibility || 'link_only');

			var pwdIn = document.getElementById('mm_ed_password');
			if (pwdIn && pwdIn.value) {
				fd.append('invitation_password', pwdIn.value);
			}
			fd.append('expiration_mode', state.expiration_mode || 'always');
			fd.append('expiration_date', state.expiration_date || '');
			fd.append('noindex', state.noindex ? '1' : '0');

			fetch(cfg.ajaxUrl, { method: 'POST', body: fd })
				.then(function (r) {
					return r.json();
				})
				.then(function (res) {
					if (res.success && res.data && res.data.invitation) {
						state = res.data.invitation;
						var badge = document.getElementById('mm-editor-status-badge');
						if (badge) badge.textContent = state.status;

						var sharePanel = document.getElementById('mm-editor-share-panel');
						var shortUrlEl = document.getElementById('mm-editor-short-url');
						var openUrlEl = document.getElementById('mm-editor-open-url');
						var qrBox = document.getElementById('mm-editor-qr-container');

						if (sharePanel) sharePanel.hidden = false;
						if (shortUrlEl) shortUrlEl.textContent = state.short_url;
						if (openUrlEl) openUrlEl.href = state.short_url;
						if (qrBox) {
							qrBox.setAttribute('data-qr-url', state.short_url);
							if (window.MMInvQR) window.MMInvQR.renderAll();
						}
						window.alert(res.data.message);
					} else if (res.data && res.data.message) {
						window.alert(res.data.message);
					}
				});
		}

		var draftBtn = document.getElementById('mm-btn-save-draft');
		if (draftBtn) {
			draftBtn.addEventListener('click', function () {
				saveInvitation('DRAFT');
			});
		}

		var pubBtn = document.getElementById('mm-btn-publish');
		if (pubBtn) {
			pubBtn.addEventListener('click', function () {
				saveInvitation('PUBLISHED');
			});
		}

		var copyUrlBtn = document.getElementById('mm-editor-copy-url');
		if (copyUrlBtn) {
			copyUrlBtn.addEventListener('click', function () {
				if (state.short_url && navigator.clipboard) {
					navigator.clipboard.writeText(state.short_url).then(function () {
						window.alert('고유 URL이 복사되었습니다: ' + state.short_url);
					});
				}
			});
		}

		renderFieldsEditor();
		renderGalleryEditor();
		renderLivePreview();
	}
})();
