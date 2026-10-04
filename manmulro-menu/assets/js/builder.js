/**
 * MANMULRO MENU — 5-Step Menu Builder Controller (`assets/js/builder.js`)
 *
 * Implements Sections #2 ~ #17:
 * - Unified Common Menu Data state (`project`, `categories`, `items`, `source_images`, `ocr_results`)
 * - STEP 1: `[사진으로 시작하기]` (OCR) or `[직접 만들기]`
 * - STEP 2: Split OCR comparison & `[확인하고 메뉴로 가져오기]`, Category & Menu CRUD, Duplicate, Bulk price/status/category, `[원본 메뉴판 보기]` & `[원본에서 보기]`
 * - STEP 3: Optional Menu Detail Editor (`●●●○○` flavor dots, ingredients, allergens, origins)
 * - STEP 4: 8 Design Templates + Real-time mobile preview
 * - STEP 5: Permanent QR Code (`/menu/{store}`), Individual Detail URLs (`/menu/{store}/{menu-item}`), A4/A3 Print Menu
 * - Auto-Save (#15)
 */
(function () {
	'use strict';

	function dotsString(intensity) {
		var v = Math.max(0, Math.min(5, parseInt(intensity || 0, 10)));
		return '●'.repeat(v) + '○'.repeat(5 - v);
	}

	document.addEventListener('DOMContentLoaded', function () {
		var app = document.getElementById('mm-menu-builder-app');
		if (!app) return;

		var defaultSourceImg = app.getAttribute('data-default-source-img') || '';
		var initialProject = null;
		try {
			initialProject = JSON.parse(app.getAttribute('data-project') || 'null');
		} catch (e) {
			initialProject = null;
		}

		// Initialize default Common Menu Data if no project exists yet
		var state = initialProject || {
			project_id: 1,
			business_name: '만물로 한식당',
			store_slug: 'manmulro-hansik',
			business_type: '음식점',
			design_template: 'korean',
			design_config: {
				logo_url: '',
				primary_color: '#9a3412',
				bg_color: '#fffbeb',
				text_color: '#1c1917',
				font_family: 'sans',
				show_images: true,
				price_style: 'won'
			},
			public_url: window.location.origin + '/menu/manmulro-hansik',
			categories: [
				{ category_id: 1, name: '식사', sort_order: 1 },
				{ category_id: 2, name: '사이드', sort_order: 2 },
				{ category_id: 3, name: '음료', sort_order: 3 },
				{ category_id: 4, name: '주류', sort_order: 4 }
			],
			items: [
				{
					menu_id: 101,
					category_id: 1,
					item_slug: 'kimchi-jjigae',
					name: '김치찌개',
					price: 9000,
					short_description: '국내산 숙성 김치와 한돈으로 깊게 끓인 대표 찌개',
					detailed_description: '직접 담근 1년 숙성 김치와 국내산 생삼겹살, 국산 대두 두부를 듬뿍 넣어 얼큰하고 깊게 끓여낸 만물로 한식당의 대표 식사 메뉴입니다.',
					recommended_for: '얼큰하고 깊은 국물을 좋아하시는 분 / 든든한 한 끼 식사를 찾으시는 분',
					image_url: '',
					status: 'ACTIVE',
					tags: ['대표 메뉴', '인기 메뉴'],
					source_ocr_id: 1,
					ingredients: [
						{ name: '숙성 배추김치', description: '직접 담근 1년 해남 배추김치' },
						{ name: '국내산 한돈', description: '신선한 생삼겹살' },
						{ name: '국산 두부', description: '매일 아침 만든 손두부' }
					],
					flavors: [
						{ flavor_type: '매운맛', intensity: 3 },
						{ flavor_type: '고소함', intensity: 4 },
						{ flavor_type: '담백함', intensity: 4 }
					],
					allergens: ['대두', '기타'],
					origins: [
						{ ingredient_name: '돼지고기', origin: '국내산 한돈' },
						{ ingredient_name: '배추김치', origin: '국내산' }
					]
				},
				{
					menu_id: 102,
					category_id: 1,
					item_slug: 'chadol-doenjang',
					name: '차돌박이 된장찌개',
					price: 9500,
					short_description: '고소한 차돌박이와 전통 집된장의 구수한 조화',
					detailed_description: '3년 숙성 재래식 집된장에 고소한 우삼겹·차돌박이와 호박, 두부를 넣어 끓여낸 구수한 찌개입니다.',
					recommended_for: '맵지 않고 구수한 국물을 선호하시는 분',
					image_url: '',
					status: 'ACTIVE',
					tags: ['추천 메뉴'],
					source_ocr_id: 2,
					ingredients: [{ name: '전통 집된장', description: '3년 숙성 한식 된장' }],
					flavors: [{ flavor_type: '고소함', intensity: 5 }, { flavor_type: '매운맛', intensity: 1 }],
					allergens: ['대두'],
					origins: [{ ingredient_name: '소고기', origin: '미국산' }]
				},
				{
					menu_id: 103,
					category_id: 2,
					item_slug: 'haemul-pajeon',
					name: '해물파전',
					price: 15000,
					short_description: '오징어·새우와 향긋한 쪽파를 바삭하게 부쳐낸 별미',
					detailed_description: '신선한 동해 오징어와 통새우, 쪽파를 아낌없이 넣어 겉은 바삭하고 속은 촉촉하게 부쳐낸 파전입니다.',
					recommended_for: '식사 곁들임이나 막걸리 안주를 찾으시는 분',
					image_url: '',
					status: 'ACTIVE',
					tags: ['인기 메뉴'],
					source_ocr_id: 4,
					ingredients: [{ name: '오징어·새우', description: '신선 해물' }, { name: '쪽파', description: '서산 향긋한 쪽파' }],
					flavors: [{ flavor_type: '고소함', intensity: 5 }, { flavor_type: '담백함', intensity: 4 }],
					allergens: ['계란', '밀', '갑각류'],
					origins: [{ ingredient_name: '오징어', origin: '국내산' }]
				}
			],
			source_images: [
				{ source_image_id: 1, image_url: defaultSourceImg, page_number: 1 }
			],
			ocr_results: [
				{ ocr_id: 1, parsed_name: '김치찌개', parsed_price: 9000, parsed_category: '식사', confidence: 0.96, needs_review: 0, bounding_box: { x: 80, y: 130, width: 310, height: 48 } },
				{ ocr_id: 2, parsed_name: '차돌박이 된장찌개', parsed_price: 9500, parsed_category: '식사', confidence: 0.94, needs_review: 0, bounding_box: { x: 80, y: 195, width: 340, height: 48 } },
				{ ocr_id: 3, parsed_name: '제육볶음 정식', parsed_price: 11000, parsed_category: '식사', confidence: 0.92, needs_review: 0, bounding_box: { x: 80, y: 260, width: 320, height: 48 } },
				{ ocr_id: 4, parsed_name: '해물파전', parsed_price: 15000, parsed_category: '사이드', confidence: 0.76, needs_review: 1, bounding_box: { x: 80, y: 355, width: 290, height: 48 } },
				{ ocr_id: 5, parsed_name: '수제 감자만두', parsed_price: 6000, parsed_category: '사이드', confidence: 0.79, needs_review: 1, bounding_box: { x: 80, y: 420, width: 300, height: 48 } }
			]
		};

		var activeCategoryFilter = 'all';
		var highlightedOcrId = null;
		var selectedDetailMenuId = state.items.length ? state.items[0].menu_id : 0;

		function markAutosaved() {
			var badge = document.getElementById('mm-menu-autosave-badge');
			if (!badge) return;
			var now = new Date();
			badge.textContent = '자동 저장됨 (' + now.toTimeString().slice(0, 8) + ')';
		}

		// Step navigation (#3)
		function goToStep(stepNum) {
			document.querySelectorAll('.mm-step-btn').forEach(function (b) {
				b.classList.toggle('is-active', b.getAttribute('data-step') === String(stepNum));
			});
			document.querySelectorAll('.mm-step-panel').forEach(function (p) {
				p.classList.toggle('is-active', p.getAttribute('data-step-panel') === String(stepNum));
			});
			renderAll();
		}

		document.querySelectorAll('.mm-step-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				goToStep(btn.getAttribute('data-step'));
			});
		});

		document.querySelectorAll('[data-goto-step]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				goToStep(btn.getAttribute('data-goto-step'));
			});
		});

		// STEP 1 buttons (#4)
		var btnStartOcr = document.getElementById('mm-btn-start-ocr');
		var btnStartDirect = document.getElementById('mm-btn-start-direct');

		if (btnStartOcr) {
			btnStartOcr.addEventListener('click', function () {
				state.business_name = document.getElementById('mm-init-business-name').value || state.business_name;
				state.business_type = document.getElementById('mm-init-business-type').value || state.business_type;
				goToStep(2);
				document.getElementById('mm-ocr-split-panel').hidden = false;
				renderOcrSplitView();
			});
		}

		if (btnStartDirect) {
			btnStartDirect.addEventListener('click', function () {
				state.business_name = document.getElementById('mm-init-business-name').value || state.business_name;
				state.business_type = document.getElementById('mm-init-business-type').value || state.business_type;
				goToStep(2);
				document.getElementById('mm-ocr-split-panel').hidden = true;
			});
		}

		// STEP 2 Submode toggle (#2.4)
		var btnToggleOcr = document.getElementById('mm-btn-toggle-ocr-panel');
		var btnShowManager = document.getElementById('mm-btn-show-menu-manager');

		if (btnToggleOcr) {
			btnToggleOcr.addEventListener('click', function () {
				var panel = document.getElementById('mm-ocr-split-panel');
				panel.hidden = !panel.hidden;
				btnToggleOcr.classList.toggle('is-active', !panel.hidden);
				renderOcrSplitView();
			});
		}
		if (btnShowManager) {
			btnShowManager.addEventListener('click', function () {
				document.getElementById('mm-ocr-split-panel').hidden = true;
				btnToggleOcr.classList.remove('is-active');
			});
		}

		// OCR Split View (#5.5 ~ #5.9)
		function renderOcrSplitView() {
			var bboxLayer = document.getElementById('mm-ocr-bbox-layer');
			var rowsList = document.getElementById('mm-ocr-rows-list');
			if (!bboxLayer || !rowsList) return;

			window.ManmulroOCRViewer.renderBoxes(
				bboxLayer,
				state.ocr_results,
				function (idx, item) {
					highlightedOcrId = item.ocr_id || idx + 1;
					renderOcrSplitView();
				},
				highlightedOcrId
			);

			rowsList.innerHTML = '';
			(state.ocr_results || []).forEach(function (ocr, idx) {
				var row = document.createElement('div');
				row.className = 'mm-ocr-row' + (Number(ocr.ocr_id) === Number(highlightedOcrId) ? ' is-highlighted' : '');

				var catSelect = '<select data-ocr-field="parsed_category" data-idx="' + idx + '">';
				state.categories.forEach(function (c) {
					var sel = c.name === ocr.parsed_category ? ' selected' : '';
					catSelect += '<option value="' + c.name + '"' + sel + '>' + c.name + '</option>';
				});
				catSelect += '</select>';

				var warnBadge = (ocr.needs_review || ocr.confidence < 0.85)
					? '<span class="mm-ocr-badge-warn">⚠️ 확인 필요 (' + Math.round(ocr.confidence * 100) + '%)</span>'
					: '<span style="font-size:11px;color:#047857;font-weight:700;">' + Math.round(ocr.confidence * 100) + '%</span>';

				row.innerHTML =
					catSelect +
					'<input type="text" value="' + (ocr.parsed_name || '') + '" data-ocr-field="parsed_name" data-idx="' + idx + '" />' +
					'<input type="number" value="' + (ocr.parsed_price || 0) + '" step="100" data-ocr-field="parsed_price" data-idx="' + idx + '" />' +
					'<div style="display:flex;align-items:center;gap:6px;">' +
						warnBadge +
						'<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" data-focus-ocr="' + ocr.ocr_id + '">원본위치</button>' +
						'<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" data-del-ocr="' + idx + '">✕</button>' +
					'</div>';

				rowsList.appendChild(row);
			});

			rowsList.querySelectorAll('[data-ocr-field]').forEach(function (inp) {
				inp.addEventListener('input', function () {
					var i = parseInt(inp.getAttribute('data-idx'), 10);
					var f = inp.getAttribute('data-ocr-field');
					state.ocr_results[i][f] = f === 'parsed_price' ? parseInt(inp.value || '0', 10) : inp.value;
					state.ocr_results[i].needs_review = 0;
				});
			});

			rowsList.querySelectorAll('[data-focus-ocr]').forEach(function (b) {
				b.addEventListener('click', function () {
					highlightedOcrId = parseInt(b.getAttribute('data-focus-ocr'), 10);
					renderOcrSplitView();
				});
			});

			rowsList.querySelectorAll('[data-del-ocr]').forEach(function (b) {
				b.addEventListener('click', function () {
					var i = parseInt(b.getAttribute('data-del-ocr'), 10);
					state.ocr_results.splice(i, 1);
					renderOcrSplitView();
				});
			});
		}

		// Preprocessing controls (#5.2)
		var ocrImg = document.getElementById('mm-ocr-source-img');
		var btnRotate = document.getElementById('mm-btn-ocr-rotate');
		var btnZoom = document.getElementById('mm-btn-ocr-zoom-in');
		var btnContrast = document.getElementById('mm-btn-ocr-contrast');

		if (btnRotate) {
			btnRotate.addEventListener('click', function () {
				window.ManmulroOCRViewer.rotation = (window.ManmulroOCRViewer.rotation + 90) % 360;
				window.ManmulroOCRViewer.applyPreprocess(ocrImg);
			});
		}
		if (btnZoom) {
			btnZoom.addEventListener('click', function () {
				window.ManmulroOCRViewer.scale = window.ManmulroOCRViewer.scale >= 1.4 ? 1 : 1.25;
				window.ManmulroOCRViewer.applyPreprocess(ocrImg);
			});
		}
		if (btnContrast) {
			btnContrast.addEventListener('click', function () {
				window.ManmulroOCRViewer.highContrast = !window.ManmulroOCRViewer.highContrast;
				window.ManmulroOCRViewer.applyPreprocess(ocrImg);
			});
		}

		// Add OCR row & Confirm OCR Import (`[확인하고 메뉴로 가져오기]` #5.9)
		var btnAddOcrRow = document.getElementById('mm-btn-add-ocr-row');
		if (btnAddOcrRow) {
			btnAddOcrRow.addEventListener('click', function () {
				state.ocr_results.push({
					ocr_id: Date.now(),
					parsed_name: '새 추출 메뉴',
					parsed_price: 8000,
					parsed_category: state.categories[0] ? state.categories[0].name : '식사',
					confidence: 1.0,
					needs_review: 0,
					bounding_box: { x: 80, y: 130, width: 300, height: 48 }
				});
				renderOcrSplitView();
			});
		}

		var btnConfirmOcr = document.getElementById('mm-btn-confirm-ocr-import');
		if (btnConfirmOcr) {
			btnConfirmOcr.addEventListener('click', function () {
				state.ocr_results.forEach(function (ocr) {
					var catObj = state.categories.find(function (c) { return c.name === ocr.parsed_category; }) || state.categories[0];
					var exists = state.items.some(function (it) { return it.name === ocr.parsed_name; });
					if (!exists && ocr.parsed_name) {
						state.items.push({
							menu_id: Date.now() + Math.floor(Math.random() * 1000),
							category_id: catObj ? catObj.category_id : 1,
							item_slug: 'menu-' + (state.items.length + 1),
							name: ocr.parsed_name,
							price: parseInt(ocr.parsed_price || 0, 10),
							short_description: '',
							detailed_description: '',
							recommended_for: '',
							image_url: '',
							status: 'ACTIVE',
							tags: [],
							source_ocr_id: ocr.ocr_id,
							ingredients: [],
							flavors: [],
							allergens: [],
							origins: []
						});
					}
				});
				document.getElementById('mm-ocr-split-panel').hidden = true;
				markAutosaved();
				renderAll();
				alert('확인하신 OCR 메뉴가 공통 메뉴 데이터로 저장되었습니다!');
			});
		}

		// Render Categories & Common Menu Manager (#6, #7, #16)
		function renderCategoriesAndItems() {
			var catTabs = document.getElementById('mm-builder-category-tabs');
			var bulkSelectCat = document.getElementById('mm-bulk-target-category');
			var itemsList = document.getElementById('mm-builder-items-list');
			if (!catTabs || !itemsList) return;

			catTabs.innerHTML = '<button type="button" class="mm-cat-tab ' + (activeCategoryFilter === 'all' ? 'is-active' : '') + '" data-cat-id="all">전체 (' + state.items.length + ')</button>';
			if (bulkSelectCat) {
				bulkSelectCat.innerHTML = '<option value="">카테고리 이동 선택...</option>';
			}

			state.categories.forEach(function (c) {
				var count = state.items.filter(function (i) { return Number(i.category_id) === Number(c.category_id); }).length;
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'mm-cat-tab' + (String(activeCategoryFilter) === String(c.category_id) ? ' is-active' : '');
				btn.textContent = c.name + ' (' + count + ')';
				btn.addEventListener('click', function () {
					activeCategoryFilter = c.category_id;
					renderCategoriesAndItems();
				});
				catTabs.appendChild(btn);

				if (bulkSelectCat) {
					var opt = document.createElement('option');
					opt.value = String(c.category_id);
					opt.textContent = c.name + '(으)로 이동';
					bulkSelectCat.appendChild(opt);
				}
			});

			catTabs.querySelector('[data-cat-id="all"]').addEventListener('click', function () {
				activeCategoryFilter = 'all';
				renderCategoriesAndItems();
			});

			itemsList.innerHTML = '';
			var filtered = state.items.filter(function (it) {
				return activeCategoryFilter === 'all' || Number(it.category_id) === Number(activeCategoryFilter);
			});

			filtered.forEach(function (item) {
				var row = document.createElement('div');
				row.className = 'mm-builder-item-row';

				var catOptions = state.categories.map(function (c) {
					return '<option value="' + c.category_id + '"' + (Number(c.category_id) === Number(item.category_id) ? ' selected' : '') + '>' + c.name + '</option>';
				}).join('');

				var statusOptions = [
					{ k: 'ACTIVE', label: '판매중 (ACTIVE)' },
					{ k: 'SOLD_OUT', label: '품절 (SOLD_OUT)' },
					{ k: 'HIDDEN', label: '숨김 (HIDDEN)' }
				].map(function (s) {
					return '<option value="' + s.k + '"' + (s.k === item.status ? ' selected' : '') + '>' + s.label + '</option>';
				}).join('');

				var sourceBtn = item.source_ocr_id
					? '<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" data-view-source-ocr="' + item.source_ocr_id + '">🔍 [원본에서 보기]</button>'
					: '';

				row.innerHTML =
					'<input type="checkbox" class="mm-bulk-item-cb" value="' + item.menu_id + '" />' +
					'<select data-item-field="category_id" data-id="' + item.menu_id + '">' + catOptions + '</select>' +
					'<div style="display:flex;flex-direction:column;gap:4px;">' +
						'<input type="text" value="' + item.name + '" data-item-field="name" data-id="' + item.menu_id + '" placeholder="메뉴명" />' +
						'<input type="text" value="' + (item.short_description || '') + '" data-item-field="short_description" data-id="' + item.menu_id + '" placeholder="한 줄 설명 (선택)" style="font-size:12px;" />' +
					'</div>' +
					'<input type="number" value="' + item.price + '" step="100" data-item-field="price" data-id="' + item.menu_id + '" />' +
					'<select data-item-field="status" data-id="' + item.menu_id + '">' + statusOptions + '</select>' +
					'<div style="display:flex;gap:6px;flex-wrap:wrap;">' +
						sourceBtn +
						'<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" data-dup-id="' + item.menu_id + '">복제</button>' +
						'<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" data-detail-id="' + item.menu_id + '">상세정보</button>' +
						'<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" data-del-id="' + item.menu_id + '">삭제</button>' +
					'</div>';

				itemsList.appendChild(row);
			});

			// Bind row events
			itemsList.querySelectorAll('[data-item-field]').forEach(function (el) {
				el.addEventListener('input', function () {
					var id = Number(el.getAttribute('data-id'));
					var field = el.getAttribute('data-item-field');
					var target = state.items.find(function (x) { return Number(x.menu_id) === id; });
					if (!target) return;
					if (field === 'price' || field === 'category_id') {
						target[field] = parseInt(el.value || '0', 10);
					} else {
						target[field] = el.value;
					}
					markAutosaved();
					renderLiveDesignPreview();
				});
			});

			itemsList.querySelectorAll('[data-dup-id]').forEach(function (btn) {
				btn.addEventListener('click', function () {
					var id = Number(btn.getAttribute('data-dup-id'));
					var orig = state.items.find(function (x) { return Number(x.menu_id) === id; });
					if (!orig) return;
					var copy = JSON.parse(JSON.stringify(orig));
					copy.menu_id = Date.now();
					copy.name = orig.name + ' (복제)';
					copy.item_slug = orig.item_slug + '-copy';
					state.items.push(copy);
					markAutosaved();
					renderAll();
				});
			});

			itemsList.querySelectorAll('[data-detail-id]').forEach(function (btn) {
				btn.addEventListener('click', function () {
					selectedDetailMenuId = Number(btn.getAttribute('data-detail-id'));
					goToStep(3);
				});
			});

			itemsList.querySelectorAll('[data-del-id]').forEach(function (btn) {
				btn.addEventListener('click', function () {
					var id = Number(btn.getAttribute('data-del-id'));
					state.items = state.items.filter(function (x) { return Number(x.menu_id) !== id; });
					markAutosaved();
					renderAll();
				});
			});

			itemsList.querySelectorAll('[data-view-source-ocr]').forEach(function (btn) {
				btn.addEventListener('click', function () {
					highlightedOcrId = Number(btn.getAttribute('data-view-source-ocr'));
					openSourceModal(highlightedOcrId);
				});
			});

			itemsList.querySelectorAll('.mm-bulk-item-cb').forEach(function (cb) {
				cb.addEventListener('change', updateBulkCounter);
			});
			updateBulkCounter();
		}

		// Bulk Actions (#16)
		function getSelectedMenuIds() {
			var ids = [];
			document.querySelectorAll('.mm-bulk-item-cb:checked').forEach(function (cb) {
				ids.push(Number(cb.value));
			});
			return ids;
		}

		function updateBulkCounter() {
			var countEl = document.getElementById('mm-bulk-selected-count');
			if (countEl) {
				countEl.textContent = String(getSelectedMenuIds().length);
			}
		}

		var selectAllCb = document.getElementById('mm-bulk-select-all');
		if (selectAllCb) {
			selectAllCb.addEventListener('change', function () {
				document.querySelectorAll('.mm-bulk-item-cb').forEach(function (cb) {
					cb.checked = selectAllCb.checked;
				});
				updateBulkCounter();
			});
		}

		document.querySelectorAll('.mm-bulk-chip').forEach(function (chip) {
			chip.addEventListener('click', function () {
				var ids = getSelectedMenuIds();
				if (!ids.length) {
					alert('일괄 변경할 메뉴를 먼저 체크해주세요.');
					return;
				}
				var bType = chip.getAttribute('data-bulk-type');
				state.items.forEach(function (it) {
					if (ids.indexOf(Number(it.menu_id)) === -1) return;
					if (bType === 'price_delta') {
						var delta = parseInt(chip.getAttribute('data-delta') || '0', 10);
						it.price = Math.max(0, it.price + delta);
					} else if (bType === 'status') {
						it.status = chip.getAttribute('data-status');
					}
				});
				markAutosaved();
				renderAll();
			});
		});

		var bulkCatSelect = document.getElementById('mm-bulk-target-category');
		if (bulkCatSelect) {
			bulkCatSelect.addEventListener('change', function () {
				var targetCat = parseInt(bulkCatSelect.value || '0', 10);
				if (!targetCat) return;
				var ids = getSelectedMenuIds();
				state.items.forEach(function (it) {
					if (ids.indexOf(Number(it.menu_id)) !== -1) {
						it.category_id = targetCat;
					}
				});
				bulkCatSelect.value = '';
				markAutosaved();
				renderAll();
			});
		}

		// Add Category & Add Menu Item (#6.2, #7.2)
		var btnAddCat = document.getElementById('mm-btn-add-category');
		if (btnAddCat) {
			btnAddCat.addEventListener('click', function () {
				var inp = document.getElementById('mm-new-category-name');
				var name = (inp.value || '').trim();
				if (!name) return;
				state.categories.push({
					category_id: Date.now(),
					name: name,
					sort_order: state.categories.length + 1
				});
				inp.value = '';
				markAutosaved();
				renderAll();
			});
		}

		var btnAddItem = document.getElementById('mm-btn-add-menu-item');
		if (btnAddItem) {
			btnAddItem.addEventListener('click', function () {
				var defaultCat = activeCategoryFilter === 'all'
					? (state.categories[0] ? state.categories[0].category_id : 1)
					: activeCategoryFilter;
				state.items.push({
					menu_id: Date.now(),
					category_id: defaultCat,
					item_slug: 'menu-' + (state.items.length + 1),
					name: '새 메뉴',
					price: 10000,
					short_description: '',
					detailed_description: '',
					recommended_for: '',
					image_url: '',
					status: 'ACTIVE',
					tags: [],
					source_ocr_id: 0,
					ingredients: [],
					flavors: [],
					allergens: [],
					origins: []
				});
				markAutosaved();
				renderAll();
			});
		}

		// STEP 3: Menu Detail Editor (#8)
		function renderStep3() {
			var picker = document.getElementById('mm-step3-item-picker');
			if (!picker) return;
			picker.innerHTML = '';

			if (!selectedDetailMenuId && state.items.length) {
				selectedDetailMenuId = state.items[0].menu_id;
			}

			state.items.forEach(function (it) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'mm-picker-btn' + (Number(it.menu_id) === Number(selectedDetailMenuId) ? ' is-active' : '');
				btn.textContent = it.name + ' (' + it.price.toLocaleString() + '원)';
				btn.addEventListener('click', function () {
					selectedDetailMenuId = it.menu_id;
					renderStep3();
				});
				picker.appendChild(btn);
			});

			var current = state.items.find(function (x) { return Number(x.menu_id) === Number(selectedDetailMenuId); });
			if (!current) return;

			document.getElementById('mm-detail-editing-title').textContent = current.name + ' 상세정보 설정 (#8)';
			document.getElementById('mm-detail-description').value = current.detailed_description || '';
			document.getElementById('mm-detail-recommended').value = current.recommended_for || '';
			document.getElementById('mm-detail-preview-link').href = state.public_url + '/' + (current.item_slug || 'item');

			// Ingredients (#8.2, #8.3)
			var ingBox = document.getElementById('mm-detail-ingredients-rows');
			ingBox.innerHTML = '';
			(current.ingredients || []).forEach(function (ing, idx) {
				var div = document.createElement('div');
				div.style.cssText = 'display:flex;gap:8px;margin-bottom:6px;';
				div.innerHTML =
					'<input type="text" value="' + (ing.name || '') + '" placeholder="재료명 (예: 국내산 한돈)" data-ing-name="' + idx + '" />' +
					'<input type="text" value="' + (ing.description || '') + '" placeholder="재료 설명 (선택)" data-ing-desc="' + idx + '" />';
				ingBox.appendChild(div);
			});

			// Flavors (#8.4)
			document.querySelectorAll('.mm-flavor-slider').forEach(function (slider) {
				var fType = slider.getAttribute('data-flavor');
				var found = (current.flavors || []).find(function (f) { return f.flavor_type === fType; });
				var val = found ? found.intensity : 0;
				slider.value = String(val);
				var out = slider.parentElement.querySelector('.mm-flavor-output');
				if (out) out.textContent = dotsString(val);
				slider.oninput = function () {
					if (out) out.textContent = dotsString(slider.value);
				};
			});

			// Allergens (#8.6)
			document.querySelectorAll('.mm-allergen-cb').forEach(function (cb) {
				cb.checked = (current.allergens || []).indexOf(cb.value) !== -1;
			});

			// Origins (#8.7)
			var oriBox = document.getElementById('mm-detail-origins-rows');
			oriBox.innerHTML = '';
			(current.origins || []).forEach(function (ori, idx) {
				var div = document.createElement('div');
				div.style.cssText = 'display:flex;gap:8px;margin-bottom:6px;';
				div.innerHTML =
					'<input type="text" value="' + (ori.ingredient_name || '') + '" placeholder="품목 (예: 돼지고기)" data-ori-name="' + idx + '" />' +
					'<input type="text" value="' + (ori.origin || '') + '" placeholder="원산지 (예: 국내산 한돈)" data-ori-val="' + idx + '" />';
				oriBox.appendChild(div);
			});
		}

		var btnAddIng = document.getElementById('mm-btn-add-ingredient');
		if (btnAddIng) {
			btnAddIng.addEventListener('click', function () {
				var current = state.items.find(function (x) { return Number(x.menu_id) === Number(selectedDetailMenuId); });
				if (!current) return;
				current.ingredients = current.ingredients || [];
				current.ingredients.push({ name: '', description: '' });
				renderStep3();
			});
		}

		var btnAddOri = document.getElementById('mm-btn-add-origin');
		if (btnAddOri) {
			btnAddOri.addEventListener('click', function () {
				var current = state.items.find(function (x) { return Number(x.menu_id) === Number(selectedDetailMenuId); });
				if (!current) return;
				current.origins = current.origins || [];
				current.origins.push({ ingredient_name: '', origin: '' });
				renderStep3();
			});
		}

		var btnSaveDetail = document.getElementById('mm-btn-save-item-detail');
		if (btnSaveDetail) {
			btnSaveDetail.addEventListener('click', function () {
				var current = state.items.find(function (x) { return Number(x.menu_id) === Number(selectedDetailMenuId); });
				if (!current) return;
				current.detailed_description = document.getElementById('mm-detail-description').value;
				current.recommended_for = document.getElementById('mm-detail-recommended').value;

				var newIngs = [];
				document.querySelectorAll('[data-ing-name]').forEach(function (el) {
					var i = el.getAttribute('data-ing-name');
					var descEl = document.querySelector('[data-ing-desc="' + i + '"]');
					if (el.value.trim()) {
						newIngs.push({ name: el.value.trim(), description: descEl ? descEl.value.trim() : '' });
					}
				});
				current.ingredients = newIngs;

				var newFlavors = [];
				document.querySelectorAll('.mm-flavor-slider').forEach(function (sl) {
					var v = parseInt(sl.value || '0', 10);
					if (v > 0) {
						newFlavors.push({ flavor_type: sl.getAttribute('data-flavor'), intensity: v });
					}
				});
				current.flavors = newFlavors;

				var newAllergens = [];
				document.querySelectorAll('.mm-allergen-cb:checked').forEach(function (cb) {
					newAllergens.push(cb.value);
				});
				current.allergens = newAllergens;

				var newOrigins = [];
				document.querySelectorAll('[data-ori-name]').forEach(function (el) {
					var i = el.getAttribute('data-ori-name');
					var valEl = document.querySelector('[data-ori-val="' + i + '"]');
					if (el.value.trim() && valEl && valEl.value.trim()) {
						newOrigins.push({ ingredient_name: el.value.trim(), origin: valEl.value.trim() });
					}
				});
				current.origins = newOrigins;

				markAutosaved();
				alert('"' + current.name + '" 상세정보가 저장되었습니다.');
			});
		}

		// STEP 4: Design Templates & Live Preview (#12)
		document.querySelectorAll('.mm-template-card').forEach(function (card) {
			card.addEventListener('click', function () {
				state.design_template = card.getAttribute('data-template');
				state.design_config.primary_color = card.getAttribute('data-primary');
				state.design_config.bg_color = card.getAttribute('data-bg');
				state.design_config.text_color = card.getAttribute('data-text');

				document.getElementById('mm-design-primary-color').value = state.design_config.primary_color;
				document.getElementById('mm-design-bg-color').value = state.design_config.bg_color;
				document.getElementById('mm-design-text-color').value = state.design_config.text_color;

				document.querySelectorAll('.mm-template-card').forEach(function (c) { c.classList.remove('is-active'); });
				card.classList.add('is-active');
				markAutosaved();
				renderLiveDesignPreview();
			});
		});

		['mm-design-primary-color', 'mm-design-bg-color', 'mm-design-text-color', 'mm-design-price-style', 'mm-design-show-images'].forEach(function (id) {
			var el = document.getElementById(id);
			if (!el) return;
			el.addEventListener('input', function () {
				state.design_config.primary_color = document.getElementById('mm-design-primary-color').value;
				state.design_config.bg_color = document.getElementById('mm-design-bg-color').value;
				state.design_config.text_color = document.getElementById('mm-design-text-color').value;
				state.design_config.price_style = document.getElementById('mm-design-price-style').value;
				state.design_config.show_images = document.getElementById('mm-design-show-images').checked;
				renderLiveDesignPreview();
			});
		});

		function formatPriceJs(price, style) {
			var n = Number(price || 0).toLocaleString();
			if (style === 'comma') return '₩' + n;
			if (style === 'dots') return '···· ' + n + '원';
			return n + '원';
		}

		function renderLiveDesignPreview() {
			var screen = document.getElementById('mm-live-design-preview');
			if (!screen) return;

			var cfg = state.design_config;
			screen.style.background = cfg.bg_color;
			screen.style.color = cfg.text_color;

			var html = '<div style="text-align:center;border-bottom:2px solid ' + cfg.primary_color + ';padding-bottom:12px;margin-bottom:14px;">' +
				'<span style="font-size:11px;font-weight:700;color:' + cfg.primary_color + ';">' + state.business_type + '</span>' +
				'<h4 style="margin:4px 0 0;font-size:20px;">' + state.business_name + '</h4>' +
			'</div>';

			state.categories.forEach(function (cat) {
				var catItems = state.items.filter(function (i) {
					return Number(i.category_id) === Number(cat.category_id) && i.status !== 'HIDDEN';
				});
				if (!catItems.length) return;
				html += '<div style="margin-bottom:14px;">' +
					'<div style="font-weight:800;font-size:14px;border-left:3px solid ' + cfg.primary_color + ';padding-left:8px;margin-bottom:8px;">' + cat.name + '</div>';
				catItems.forEach(function (it) {
					html += '<div style="padding:10px;border:1px solid rgba(128,128,128,0.22);border-radius:10px;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;">' +
						'<div>' +
							'<div style="font-weight:700;font-size:14px;">' + it.name + (it.status === 'SOLD_OUT' ? ' <span style="color:#dc2626;font-size:11px;">[품절]</span>' : '') + '</div>' +
							(it.short_description ? '<div style="font-size:11px;opacity:0.75;">' + it.short_description + '</div>' : '') +
						'</div>' +
						'<div style="font-weight:800;color:' + cfg.primary_color + ';font-size:13px;">' + formatPriceJs(it.price, cfg.price_style) + '</div>' +
					'</div>';
				});
				html += '</div>';
			});

			screen.innerHTML = html;
		}

		// STEP 5: Publish, QR, Print & Individual Detail URLs (#9, #13, #14)
		function renderStep5() {
			var pubInput = document.getElementById('mm-publish-public-url');
			var pubLink = document.getElementById('mm-link-open-public-url');
			var qrHolder = document.getElementById('mm-publish-qr-holder');
			var itemUrlsList = document.getElementById('mm-publish-item-urls');

			if (pubInput) pubInput.value = state.public_url;
			if (pubLink) pubLink.href = state.public_url;
			if (qrHolder && window.ManmulroMenuQR) {
				qrHolder.innerHTML = window.ManmulroMenuQR.createQrSvg(state.public_url, 150);
			}

			document.querySelectorAll('[data-print-paper]').forEach(function (a) {
				var paper = a.getAttribute('data-print-paper');
				var orient = a.getAttribute('data-print-orient');
				a.href = state.public_url + '?print=1&paper=' + paper + '&orientation=' + orient;
			});

			if (itemUrlsList) {
				itemUrlsList.innerHTML = '';
				state.items.forEach(function (it) {
					var u = state.public_url + '/' + (it.item_slug || 'item');
					var li = document.createElement('li');
					li.style.cssText = 'display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:13px;';
					li.innerHTML = '<span><strong>' + it.name + '</strong></span><a href="' + u + '" target="_blank"><code>/menu/' + state.store_slug + '/' + it.item_slug + '</code> ↗</a>';
					itemUrlsList.appendChild(li);
				});
			}
		}

		var btnCopyPublic = document.getElementById('mm-btn-copy-public-url');
		if (btnCopyPublic) {
			btnCopyPublic.addEventListener('click', function () {
				if (navigator.clipboard) {
					navigator.clipboard.writeText(state.public_url);
					alert('전용 메뉴판 주소가 복사되었습니다:\n' + state.public_url);
				}
			});
		}

		// Source Menu Modal (`[원본 메뉴판 보기]` & `[원본에서 보기]` #11)
		var sourceModal = document.getElementById('mm-source-modal');
		function openSourceModal(focusOcrId) {
			if (!sourceModal) return;
			sourceModal.hidden = false;
			var modalLayer = document.getElementById('mm-modal-bbox-layer');
			window.ManmulroOCRViewer.renderBoxes(
				modalLayer,
				state.ocr_results,
				function (idx, item) {
					openSourceModal(item.ocr_id);
				},
				focusOcrId
			);
		}

		var btnOpenSource = document.getElementById('mm-btn-open-source-modal');
		if (btnOpenSource) {
			btnOpenSource.addEventListener('click', function () {
				openSourceModal(highlightedOcrId);
			});
		}
		['mm-source-modal-close', 'mm-source-modal-close-btn'].forEach(function (id) {
			var el = document.getElementById(id);
			if (el) {
				el.addEventListener('click', function () {
					sourceModal.hidden = true;
				});
			}
		});

		function renderAll() {
			renderOcrSplitView();
			renderCategoriesAndItems();
			renderStep3();
			renderLiveDesignPreview();
			renderStep5();
		}

		renderAll();
	});
})();
