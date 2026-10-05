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

	var CATEGORY_EXAMPLES = {
		wedding: { title: '두 사람의 새로운 시작에 초대합니다', summary: '사랑과 믿음으로 한 길을 걷게 된 두 사람의 예식에 함께해 주세요.', greeting: '서로에게 가장 좋은 친구가 되어 새로운 길을 걷습니다.\n소중한 분들을 모시고 기쁨을 나누고 싶습니다.', template: 'classic', event_time: '12:00', event_end_time: '13:30', location_name: '오션웨딩홀 3층 그랜드볼룸', address: '부산광역시 해운대구 센텀중앙로 90', fieldValues: { '신랑 · 신부': '김민수 · 이서연', '혼주 안내': '양가 가족 일동', '식사 및 주차 안내': '예식 후 2층 연회장 · 무료 주차 2시간' } },
		birthday: { title: '김민수의 서른 번째 생일 파티', summary: '소중한 사람들과 웃음 가득한 시간을 보내고 싶어요. 함께해 주세요.', greeting: '올해는 특별한 생일을 맞아 작은 파티를 준비했어요.\n편한 마음으로 오셔서 즐거운 저녁 함께해요!', template: 'flower', event_time: '18:00', event_end_time: '21:00', location_name: '광안리 라운지 오월', address: '부산광역시 수영구 광안해변로 180', fieldValues: { '주인공': '김민수', '드레스코드': '좋아하는 색 한 가지' } },
		baby: { title: '서윤이의 첫 번째 생일에 초대합니다', summary: '건강하게 자라준 서윤이의 첫돌을 함께 축하해 주세요.', greeting: '작은 손으로 세상을 배워가는 서윤이가 첫 번째 생일을 맞았습니다.\n따뜻한 축하와 웃음으로 자리를 빛내주세요.', template: 'flower', event_time: '11:30', event_end_time: '14:00', location_name: '롯데호텔 부산 에메랄드룸', address: '부산광역시 부산진구 가야대로 772', fieldValues: { '아가 이름': '김서윤', '아빠 · 엄마': '김민수 · 박지은' } },
		longevity: { title: '아버지의 칠순 잔치에 초대합니다', summary: '가족과 함께한 일흔 해를 축하하는 자리에 모십니다.', greeting: '늘 한결같은 사랑으로 가족을 지켜주신 아버지의 칠순을 맞아\n감사의 마음을 나누는 자리를 마련했습니다.', template: 'classic', event_time: '17:30', event_end_time: '20:00', location_name: '호텔 농심 대청홀', address: '부산광역시 동래구 금강공원로 20', fieldValues: { '주인공 어르신': '김영수 아버님', '가족 대표 연락처': '김민수 010-2345-6789' } },
		reunion: { title: '부산고 38회 동문 가을 모임', summary: '졸업의 추억과 반가운 안부를 나누는 자리에 초대합니다.', greeting: '교정에서 함께 웃던 날이 엊그제 같은데 어느덧 가을입니다.\n오랜만에 모여 반가운 얼굴들을 만나고 싶습니다.', template: 'modern', event_time: '18:30', event_end_time: '21:00', location_name: '서면 동문회관', address: '부산광역시 부산진구 중앙대로 700', fieldValues: { '기수 / 졸업연도': '38회 · 1998년 졸업', '회비': '1인 30,000원' } },
		gathering: { title: '오랜만에 함께하는 반가운 저녁', summary: '좋은 사람들과 편안하게 이야기 나누는 자리를 마련했습니다.', greeting: '바쁜 일상 속 잠시 시간을 내어 함께 밥 한 끼 나눠요.\n편안한 마음으로 오셔서 반가운 이야기 들려주세요.', template: 'classic', event_time: '18:00', event_end_time: '20:30', location_name: '전포동 작은식탁', address: '부산광역시 부산진구 전포대로 210', fieldValues: { '회비': '1인 25,000원', '모임 안내': '편한 복장으로 참석해 주세요.' } },
		hiking: { title: '2026 가을 금정산 정기 산행 초대', summary: '청명한 가을 하늘 아래 함께 걸으며 소중한 추억을 나누는 자리에 초대합니다.', greeting: '깊어가는 가을, 만물로 산악회 정기 산행에 회원 여러분을 초대합니다.\n가벼운 발걸음으로 오셔서 함께 담소 나누어요.', template: 'nature', event_time: '09:00', event_end_time: '15:00', location_name: '범어사 매표소 입구 광장', address: '부산광역시 금정구 범어사로 250', fieldValues: { '산행코스': '범어사 → 북문 → 고당봉 (약 4시간)', '집결장소 및 시간': '범어사 매표소 앞 오전 9시', '준비물': '등산화, 스틱, 식수 1L, 간식', '회비': '25,000원 (뒤풀이 식사 포함)' } },
		golf: { title: '2026 동문회 친선 골프대회', summary: '푸른 필드 위에서 반가운 인사와 멋진 라운딩을 함께해요.', greeting: '가을 바람이 좋은 날, 동문들과 함께 친선 라운딩을 준비했습니다.\n좋은 샷과 웃음이 있는 하루에 함께해 주세요.', template: 'nature', event_time: '07:30', event_end_time: '14:00', location_name: '해운대 컨트리클럽 클럽하우스', address: '부산광역시 기장군 정관읍 병산로 320', fieldValues: { '티오프 시간 / 코스': '오전 7:30 · IN / OUT 동시 티오프', '그린피': '160,000원', '카트비': '25,000원', '캐디피': '팀별 별도 정산' } },
		cycling: { title: '낙동강 가을 라이딩에 초대합니다', summary: '강바람을 따라 달리며 계절의 풍경을 함께 즐겨요.', greeting: '낙동강변의 가을 풍경을 따라 천천히 달립니다.\n안전하게 페달을 맞추며 즐거운 하루 보내요.', template: 'nature', event_time: '08:00', event_end_time: '12:00', location_name: '삼락생태공원 자전거 대여소 앞', address: '부산광역시 사상구 낙동대로 1231', fieldValues: { '라이딩 코스 / 거리': '삼락공원 → 을숙도 왕복 · 약 35km', '필수 장비': '헬멧, 전조등, 후미등, 보급식' } },
		running: { title: '2026 바다런 10K 함께 달려요', summary: '완주보다 즐거움이 먼저인 바닷길 러닝 축제에 초대합니다.', greeting: '바다를 옆에 두고 함께 달리는 가을 러닝 데이!\n기록보다 서로의 페이스를 응원하며 즐겨요.', template: 'nature', event_time: '07:00', event_end_time: '10:30', location_name: '광안리 해변 중앙광장', address: '부산광역시 수영구 광안해변로 219', fieldValues: { '러닝 코스 / 거리': '광안리 해변 왕복 10km', '페이스 그룹': '5:30 · 6:30 · 7:30 페이스' } },
		corporate: { title: '2026 하반기 파트너 초청 세미나', summary: '새로운 아이디어와 협력의 가능성을 나누는 자리에 모십니다.', greeting: '함께 만들어온 성과를 돌아보고 다음 도약을 준비하는 시간입니다.\n파트너 여러분을 모시고 인사이트를 나누고자 합니다.', template: 'modern', event_time: '14:00', event_end_time: '17:00', location_name: 'BEXCO 컨벤션홀 2층', address: '부산광역시 해운대구 APEC로 55', fieldValues: { '주최 부서 / 담당자': '만물로 파트너십팀 · 이지훈', '주요 식순': '환영사 · 세션 발표 · 네트워킹' } },
		school: { title: '2026 동아대학교 가을 축제 초대', summary: '캠퍼스의 가을밤을 음악과 웃음으로 함께 채워주세요.', greeting: '캠퍼스에 가을이 찾아왔습니다.\n친구와 선후배가 함께 즐기는 축제의 밤으로 초대합니다!', template: 'modern', event_time: '17:00', event_end_time: '21:00', location_name: '동아대학교 승학캠퍼스 대운동장', address: '부산광역시 사하구 낙동대로 550번길 37', fieldValues: { '대상 학년 / 학과': '동아대학교 재학생·졸업생', '집결 장소': '대운동장 중앙 무대 앞' } },
		opening: { title: '만물로 센텀점 오픈식에 초대합니다', summary: '새로운 공간의 첫 시작을 함께 축하해 주시면 감사하겠습니다.', greeting: '오랜 준비 끝에 새로운 문을 엽니다.\n귀한 걸음으로 자리를 빛내주시면 큰 기쁨이겠습니다.', template: 'flower', event_time: '11:00', event_end_time: '17:00', location_name: '만물로 센텀점', address: '부산광역시 해운대구 센텀중앙로 97', fieldValues: { '상호명 / 대표': '만물로 센텀점 · 대표 김민수', '오픈 이벤트 안내': '방문 고객 음료 1잔 무료' } },
		housewarming: { title: '새 보금자리 집들이에 초대합니다', summary: '정성껏 마련한 새 집에 오셔서 따뜻한 시간 함께 나눠요.', greeting: '새로운 보금자리에 여러분을 초대합니다.\n맛있는 음식 준비해둘게요. 편하게 놀러오세요!', template: 'classic', event_time: '16:00', event_end_time: '20:00', location_name: '해운대 센텀리버파크 104동', address: '부산광역시 해운대구 수영강변대로 120', fieldValues: { '출입문 / 주차 안내': '104동 1202호 · 지하주차장 방문 등록', '준비된 음식': '홈메이드 파스타와 디저트' } },
		exhibition: { title: '가을을 담은 김민지 개인전', summary: '천천히 둘러보며 작품과 이야기를 나누는 시간에 초대합니다.', greeting: '계절의 빛과 마음의 풍경을 담은 작품을 모았습니다.\n전시장에서 만나 작품 이야기를 함께 나누고 싶습니다.', template: 'modern', event_time: '10:00', event_end_time: '18:00', location_name: '갤러리 해운 아트룸', address: '부산광역시 해운대구 달맞이길 65', fieldValues: { '참여 작가 / 출연진': '김민지 · 회화 24점', '관람 시간 / 입장료': '화–일 10:00–18:00 · 무료 관람' } },
		religious: { title: '추수감사 예배에 초대합니다', summary: '감사의 마음을 모아 함께 기도하고 나누는 예배에 초대합니다.', greeting: '올 한 해 받은 은혜와 사랑을 함께 나누고 감사드립니다.\n가족과 이웃을 초대하여 따뜻한 예배의 자리를 마련합니다.', template: 'classic', event_time: '11:00', event_end_time: '12:30', location_name: '부산은혜교회 본당', address: '부산광역시 연제구 중앙대로 1020', fieldValues: { '집례 / 인도자': '이정민 목사', '예배 · 법회 안내': '추수감사 주일 2부 예배' } },
		travel: { title: '2026 단풍길 1박 2일 여행', summary: '좋은 풍경과 맛있는 음식을 함께 즐길 가을 여행을 떠나요.', greeting: '올가을에는 함께 길을 떠나 단풍과 바람을 즐겨요.\n서로의 추억에 오래 남을 여행을 준비했습니다.', template: 'nature', event_time: '08:00', event_end_time: '18:00', location_name: '부산역 1층 2번 출구 앞', address: '부산광역시 동구 중앙대로 206', fieldValues: { '여행 일정표': '1일차: 내장산 단풍길 · 2일차: 전주 한옥마을', '숙소 안내': '정읍 한옥스테이 · 2인 1실' } },
		other: { title: '소중한 모임에 초대합니다', summary: '반가운 분들과 함께할 뜻깊은 자리에 정중히 초대합니다.', greeting: '소중한 인연과 함께 뜻깊은 시간을 나누고자 합니다.\n바쁘시더라도 오셔서 자리를 빛내주시면 감사하겠습니다.', template: 'simple', event_time: '14:00', event_end_time: '16:00', location_name: '부산 시내 모임 장소', address: '부산광역시 부산진구 중앙대로 000', fieldValues: { '안내 사항': '자세한 내용은 초대장 담당자에게 문의해 주세요.' } }
	};

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
		state.cover_attachment_id = Number(state.cover_attachment_id) || 0;

		var sampleImageBase = cfg.sampleImageBase || '/wp-content/plugins/manmulro-invitation/assets/images/invitation-samples/';
		function getCategoryExample(slug) {
			return CATEGORY_EXAMPLES[slug] || CATEGORY_EXAMPLES.other;
		}
		function getCategorySampleImage(slug) {
			var safeSlug = CATEGORY_EXAMPLES[slug] ? slug : 'other';
			return sampleImageBase + safeSlug + '.svg';
		}
		function isBuiltInSampleImage(url) {
			return typeof url === 'string' && url.indexOf(sampleImageBase) === 0;
		}

		var initialCategorySlug = state.category_slug || 'other';
		var initialExample = getCategoryExample(initialCategorySlug);
		if (!state.cover_image_url) {
			state.cover_image_url = getCategorySampleImage(initialCategorySlug);
		}
		var sampleMode = !Number(state.id) || cfg.sampleMode === true;
		var userEdited = {
			title: !sampleMode && !!String(state.title || '').trim() && state.title !== initialExample.title,
			summary: !sampleMode && !!String(state.summary || '').trim() && state.summary !== initialExample.summary,
			template: !sampleMode && !!state.template && state.template !== initialExample.template,
			fields: !sampleMode && state.fields.length > 0 && !fieldsMatchCategoryExample(initialCategorySlug),
			cover: !!state.cover_image_url && !isBuiltInSampleImage(state.cover_image_url),
			event_time: !sampleMode && !!state.event_time && state.event_time !== initialExample.event_time,
			event_end_time: !sampleMode && !!state.event_end_time && state.event_end_time !== initialExample.event_end_time,
			location_name: !sampleMode && !!String(state.location_name || '').trim() && state.location_name !== initialExample.location_name,
			address: !sampleMode && !!String(state.address || '').trim() && state.address !== initialExample.address
		};

		var fieldsListEl = document.getElementById('mm-editor-fields-list');
		var galleryGridEl = document.getElementById('mm-editor-gallery-grid');
		var galleryCounterEl = document.getElementById('mm-gallery-counter');
		var previewScreenEl = document.getElementById('mm-live-preview-screen');
		var coverPreviewEl = document.getElementById('mm_ed_cover_preview');
		var coverBadgeEl = document.getElementById('mm-cover-category-badge');
		var coverPickerEl = document.getElementById('mm-cover-gallery-modal');
		var coverSampleGridEl = document.getElementById('mm-cover-sample-grid');
		var coverUserGridEl = document.getElementById('mm-cover-user-grid');
		var coverUserEmptyEl = document.getElementById('mm-cover-user-empty');
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

		function setInputValue(id, value) {
			var input = document.getElementById(id);
			if (input) input.value = value == null ? '' : value;
		}

		function makeCategorySampleFields(slug) {
			var example = getCategoryExample(slug);
			var category = cfg.categories && cfg.categories[slug] ? cfg.categories[slug] : {};
			var fields = [{
				id: 'sample_' + slug + '_greeting',
				type: 'textarea',
				label: '초대 인사말',
				value: example.greeting,
				visible: true,
				order: 1
			}];
			(category.presets || []).forEach(function (preset, idx) {
				var values = example.fieldValues || {};
				fields.push({
					id: 'sample_' + slug + '_' + (idx + 1),
					type: preset.type || 'custom',
					label: preset.label || '안내 항목',
					value: Object.prototype.hasOwnProperty.call(values, preset.label) ? values[preset.label] : (preset.value || ''),
					visible: true,
					order: idx + 2
				});
			});
			return fields;
		}

		function fieldsMatchCategoryExample(slug) {
			var expected = makeCategorySampleFields(slug);
			if (state.fields.length !== expected.length) return false;
			return expected.every(function (sample, index) {
				var current = state.fields[index] || {};
				return (current.type || 'custom') === (sample.type || 'custom') &&
					current.label === sample.label &&
					String(current.value || '') === String(sample.value || '') &&
					current.visible !== false;
			});
		}

		function syncTemplateSelection() {
			appEl.querySelectorAll('[data-template-slug]').forEach(function (card) {
				card.classList.toggle('is-selected', card.getAttribute('data-template-slug') === state.template);
			});
		}

		// Category Selection (#6, #50): use a different starter example for every type.
		appEl.querySelectorAll('[data-category-slug]').forEach(function (chip) {
			chip.addEventListener('click', function () {
				var previousSlug = state.category_slug || 'other';
				var nextSlug = chip.getAttribute('data-category-slug');
				var nextName = chip.getAttribute('data-category-name');
				if (previousSlug === nextSlug) return;

				var example = getCategoryExample(nextSlug);
				state.category_slug = nextSlug;
				state.category_name = nextName;

				if (!userEdited.title) {
					state.title = example.title;
					setInputValue('mm_ed_title', state.title);
				}
				if (!userEdited.summary) {
					state.summary = example.summary;
					setInputValue('mm_ed_summary', state.summary);
				}
				['event_time', 'event_end_time', 'location_name', 'address'].forEach(function (key) {
					if (!userEdited[key]) {
						state[key] = example[key] || '';
						setInputValue('mm_ed_' + key, state[key]);
					}
				});
				if (!userEdited.template) {
					state.template = example.template || 'simple';
					syncTemplateSelection();
				}
				if (!userEdited.fields) {
					state.fields = makeCategorySampleFields(nextSlug);
					renderFieldsEditor();
				}
				if (!userEdited.cover && isBuiltInSampleImage(state.cover_image_url)) {
					state.cover_image_url = getCategorySampleImage(nextSlug);
					state.cover_attachment_id = 0;
					setInputValue('mm_ed_cover_url', state.cover_image_url);
				}

				appEl.querySelectorAll('[data-category-slug]').forEach(function (categoryChip) {
					categoryChip.classList.toggle('is-selected', categoryChip === chip);
				});
				renderCoverStage();
				renderCoverPicker();
				renderLivePreview();
			});
		});

		// Inject Category Preset Fields (#50)
		var presetBtn = document.getElementById('mm-btn-apply-category-presets');
		if (presetBtn) {
			presetBtn.addEventListener('click', function () {
				var catObj = cfg.categories && cfg.categories[state.category_slug];
				if (!catObj || !Array.isArray(catObj.presets)) return;
				userEdited.fields = true;

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
				userEdited.template = true;
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
				if (Object.prototype.hasOwnProperty.call(userEdited, key)) userEdited[key] = true;
				if (key === 'cover_image_url') {
					if (!String(state.cover_image_url || '').trim()) {
						state.cover_image_url = getCategorySampleImage(state.category_slug);
						el.value = state.cover_image_url;
						userEdited.cover = false;
					} else {
						userEdited.cover = true;
						state.cover_attachment_id = 0;
					}
					renderCoverStage();
				}
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
				userEdited.fields = true;

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
						userEdited.fields = true;
						field.label = labelInput.value;
						renderLivePreview();
					});
				}

				var valInput = row.querySelector('.mm-js-fld-value');
				if (valInput) {
					valInput.addEventListener('input', function () {
						userEdited.fields = true;
						field.value = valInput.value;
						renderLivePreview();
					});
				}

				row.querySelector('.mm-js-fld-toggle').addEventListener('click', function () {
					userEdited.fields = true;
					field.visible = !field.visible;
					renderFieldsEditor();
					renderLivePreview();
				});

				row.querySelector('.mm-js-fld-delete').addEventListener('click', function () {
					userEdited.fields = true;
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
					userEdited.fields = true;
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

		// 16:9 cover image stage and reusable recommendation / photo gallery.
		function renderCoverStage() {
			var currentImage = state.cover_image_url || getCategorySampleImage(state.category_slug);
			if (coverPreviewEl) {
				coverPreviewEl.src = currentImage;
				coverPreviewEl.alt = (state.category_name || '초대장') + ' 대표 이미지';
			}
			if (coverBadgeEl) {
				coverBadgeEl.textContent = (state.category_name || '초대장') + (currentImage === getCategorySampleImage(state.category_slug) ? ' 추천 이미지' : ' 내 이미지');
			}
		}

		function renderCoverPicker() {
			if (coverSampleGridEl) {
				coverSampleGridEl.innerHTML = '';
				var categorySlugs = Object.keys(cfg.categories || {});
				categorySlugs.sort(function (a, b) {
					if (a === state.category_slug) return -1;
					if (b === state.category_slug) return 1;
					return 0;
				});
				categorySlugs.forEach(function (slug) {
					var category = cfg.categories[slug] || {};
					var example = getCategoryExample(slug);
					var imageUrl = getCategorySampleImage(slug);
					var button = document.createElement('button');
					button.type = 'button';
					button.className = 'mm-inv-cover-gallery-item' + (slug === state.category_slug ? ' is-current' : '');
					button.setAttribute('data-cover-choice', 'sample');
					button.setAttribute('data-cover-url', imageUrl);
					button.setAttribute('aria-label', (category.name || slug) + ' 초대장 추천 이미지 선택');
					button.innerHTML = '<img src="' + escapeHtml(imageUrl) + '" alt="" loading="lazy" /><span class="mm-inv-cover-gallery-item__caption"><strong>' + escapeHtml(category.name || slug) + '</strong><small>' + escapeHtml(example.title) + '</small></span>';
					coverSampleGridEl.appendChild(button);
				});
			}

			if (coverUserGridEl) {
				coverUserGridEl.innerHTML = '';
				var userImages = state.gallery_items.filter(function (item) {
					return item && (item.full_url || item.url || item.thumb_url);
				});
				userImages.forEach(function (item, idx) {
					var fullUrl = item.full_url || item.url || item.thumb_url;
					var thumbUrl = item.thumb_url || item.url || item.full_url;
					var button = document.createElement('button');
					button.type = 'button';
					button.className = 'mm-inv-cover-gallery-item';
					button.setAttribute('data-cover-choice', 'user');
					button.setAttribute('data-cover-url', fullUrl);
					button.setAttribute('data-cover-attachment-id', Number(item.id) || 0);
					button.setAttribute('aria-label', '내 사진앨범 사진 ' + (idx + 1) + ' 대표 이미지로 선택');
					button.innerHTML = '<img src="' + escapeHtml(thumbUrl) + '" alt="" loading="lazy" /><span class="mm-inv-cover-gallery-item__caption"><strong>내 사진 ' + (idx + 1) + '</strong><small>이 초대장의 사진앨범</small></span>';
					coverUserGridEl.appendChild(button);
				});
				if (coverUserEmptyEl) coverUserEmptyEl.hidden = userImages.length > 0;
			}
		}

		function openCoverPicker() {
			if (!coverPickerEl) return;
			renderCoverPicker();
			coverPickerEl.hidden = false;
			document.body.classList.add('mm-inv-cover-picker-open');
			var dialog = coverPickerEl.querySelector('.mm-inv-cover-picker__dialog');
			if (dialog) dialog.focus();
		}

		function closeCoverPicker() {
			if (!coverPickerEl) return;
			coverPickerEl.hidden = true;
			document.body.classList.remove('mm-inv-cover-picker-open');
		}

		var coverFileIn = document.getElementById('mm_ed_cover_file');
		var openCoverPickerBtn = document.getElementById('mm-btn-open-cover-gallery');
		if (openCoverPickerBtn) openCoverPickerBtn.addEventListener('click', openCoverPicker);
		var resetCoverBtn = document.getElementById('mm-btn-reset-cover-sample');
		if (resetCoverBtn) {
			resetCoverBtn.addEventListener('click', function () {
				state.cover_image_url = getCategorySampleImage(state.category_slug);
				state.cover_attachment_id = 0;
				userEdited.cover = false;
				setInputValue('mm_ed_cover_url', state.cover_image_url);
				renderCoverStage();
				renderLivePreview();
			});
		}
		appEl.querySelectorAll('[data-trigger-cover-upload]').forEach(function (button) {
			button.addEventListener('click', function () {
				if (coverFileIn) coverFileIn.click();
			});
		});
		if (coverPickerEl) {
			coverPickerEl.addEventListener('click', function (event) {
				var choice = event.target.closest('[data-cover-choice]');
				if (choice) {
					state.cover_image_url = choice.getAttribute('data-cover-url') || '';
					state.cover_attachment_id = Number(choice.getAttribute('data-cover-attachment-id')) || 0;
					userEdited.cover = true;
					setInputValue('mm_ed_cover_url', state.cover_image_url);
					renderCoverStage();
					renderLivePreview();
					closeCoverPicker();
					return;
				}
				if (event.target.closest('[data-cover-picker-close]')) closeCoverPicker();
			});
		}
		if (coverPickerEl) {
			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && !coverPickerEl.hidden) closeCoverPicker();
			});
		}

		// Local / AJAX File Upload for Cover & Gallery (#12, #13)
		if (coverFileIn) {
			coverFileIn.addEventListener('change', function () {
				if (!coverFileIn.files || !coverFileIn.files[0]) return;
				uploadOrPreviewFile(coverFileIn.files[0], function (imgObj) {
					state.cover_image_url = imgObj.cover_url || imgObj.url;
					state.cover_attachment_id = imgObj.id || 0;
					userEdited.cover = true;
					var urlInput = document.getElementById('mm_ed_cover_url');
					if (urlInput) urlInput.value = state.cover_image_url;
					renderCoverStage();
					renderLivePreview();
					closeCoverPicker();
					coverFileIn.value = '';
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
				(state.cover_image_url
					? '<figure class="mm-inv-hero__cover"><img src="' +
					  escapeHtml(state.cover_image_url) +
					  '" alt="대표 이미지" /></figure>'
					: '') +
				(state.summary
					? '<p class="mm-inv-hero__subtitle">' +
					  escapeHtml(state.summary).replace(/\n/g, '<br/>') +
					  '</p>'
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
		renderCoverStage();
		renderCoverPicker();
		renderLivePreview();
	}
})();
