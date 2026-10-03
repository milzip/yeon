/**
 * Interactive Preview Server for Manmulro WordPress Plugins
 * (`manmulro-social-login` & `manmulro-invitation`)
 *
 * Serves the real plugin CSS/JS assets and simulates the WordPress AJAX endpoints
 * so the user can test every V1 feature live in the browser and download the .zip plugins.
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = process.env.PORT || 3000;

// In-memory store for live preview demonstration
const store = {
	invitations: [
		{
			id: 101,
			code: 'a7Fk32',
			short_url: '/i/a7Fk32',
			title: '2026 가을 금정산 정기 산행 초대',
			summary: '청명한 가을 하늘 아래 함께 걸으며 소중한 추억을 나누는 자리에 초대합니다.',
			category_slug: 'hiking',
			category_name: '등산',
			template: 'nature',
			status: 'PUBLISHED',
			event_date: '2026-10-18',
			event_time: '09:00',
			event_end_time: '15:00',
			location_name: '범어사 매표소 입구 광장',
			address: '부산광역시 금정구 범어사로 250',
			cover_image_url: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=960&q=80',
			gallery_items: [
				{
					id: 1,
					url: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=600&q=80',
					thumb_url: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=420&q=80',
					full_url: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=80'
				},
				{
					id: 2,
					url: 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=600&q=80',
					thumb_url: 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=420&q=80',
					full_url: 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=1200&q=80'
				},
				{
					id: 3,
					url: 'https://images.unsplash.com/photo-1454496522488-7a8e488e8606?auto=format&fit=crop&w=600&q=80',
					thumb_url: 'https://images.unsplash.com/photo-1454496522488-7a8e488e8606?auto=format&fit=crop&w=420&q=80',
					full_url: 'https://images.unsplash.com/photo-1454496522488-7a8e488e8606?auto=format&fit=crop&w=1200&q=80'
				}
			],
			fields: [
				{
					id: 'fld_1',
					type: 'textarea',
					label: '초대 인사말',
					value: '깊어가는 가을, 만물로 산악회 정기 산행에 회원 여러분을 초대합니다.\n가벼운 발걸음으로 오셔서 함께 담소 나누어요.',
					visible: true,
					order: 1
				},
				{
					id: 'fld_2',
					type: 'custom',
					label: '산행코스',
					value: '범어사 → 북문 → 고당봉 (약 4시간 소요)',
					visible: true,
					order: 2
				},
				{
					id: 'fld_3',
					type: 'custom',
					label: '회비',
					value: '25,000원 (하산 후 뒤풀이 식사 포함)',
					visible: true,
					order: 3
				},
				{
					id: 'fld_4',
					type: 'custom',
					label: '준비물',
					value: '등산화, 스틱, 식수 1L, 방풍 자켓',
					visible: true,
					order: 4
				},
				{
					id: 'fld_5',
					type: 'phone',
					label: '산행대장 연락처',
					value: '010-2345-6789',
					visible: true,
					order: 5
				}
			],
			rsvp_enabled: true,
			rsvp_deadline: '2026-10-15',
			guestbook_enabled: true,
			dday_enabled: true,
			visibility: 'link_only',
			expiration_mode: 'always',
			expiration_date: '',
			noindex: true
		},
		{
			id: 102,
			code: 'g9Lp48',
			short_url: '/i/g9Lp48',
			title: '만물로 골프 동호회 10월 정기 라운딩',
			summary: '가을 필드 위에서 함께 즐기는 만물로 골프 모임에 초대합니다.',
			category_slug: 'golf',
			category_name: '골프',
			template: 'classic',
			status: 'PUBLISHED',
			event_date: '2026-10-25',
			event_time: '07:30',
			event_end_time: '14:00',
			location_name: '해운대 컨트리클럽 클럽하우스',
			address: '부산광역시 기장군 정관읍 병산로 320',
			cover_image_url: 'https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?auto=format&fit=crop&w=960&q=80',
			gallery_items: [],
			fields: [
				{ id: 'g1', type: 'custom', label: '티오프 / 코스', value: '07:30 IN / OUT 동시 티오프', visible: true, order: 1 },
				{ id: 'g2', type: 'custom', label: '카트비', value: '25,000원', visible: true, order: 2 },
				{ id: 'g3', type: 'custom', label: '그린피', value: '160,000원', visible: true, order: 3 },
				{ id: 'g4', type: 'phone', label: '총무 연락처', value: '010-9876-5432', visible: true, order: 4 }
			],
			rsvp_enabled: true,
			rsvp_deadline: '2026-10-20',
			guestbook_enabled: true,
			dday_enabled: true,
			visibility: 'link_only',
			expiration_mode: 'after_30',
			expiration_date: '',
			noindex: true
		}
	],
	rsvps: [
		{ id: 1, invitation_id: 101, name: '김철수', status: 'attending', guest_count: 2, message: '축하합니다! 꼭 참석하겠습니다.', created_at: '10/03 09:30' },
		{ id: 2, invitation_id: 101, name: '박영희', status: 'attending', guest_count: 3, message: '꼭 갈게요! 기대됩니다.', created_at: '10/03 10:15' },
		{ id: 3, invitation_id: 101, name: '이민수', status: 'declined', guest_count: 0, message: '선약이 있어 죄송합니다.', created_at: '10/02 18:40' },
		{ id: 4, invitation_id: 101, name: '최지훈', status: 'maybe', guest_count: 1, message: '일정 조율 후 다시 연락드리겠습니다.', created_at: '10/03 11:00' }
	],
	guestbook: [
		{ id: 1, invitation_id: 101, name: '정수진', message: '가을 단풍 산행 너무 기대되네요! 안전 산행 기원합니다.', status: 'approved', created_at: '10/03 10:05' },
		{ id: 2, invitation_id: 101, name: '강현우', message: '멋진 모임 준비해주신 운영진분들 감사합니다.', status: 'approved', created_at: '10/03 11:20' }
	]
};

const CATEGORIES = {
	wedding: { name: '결혼', icon: '💍', presets: [{ type: 'custom', label: '신랑 · 신부', value: '' }, { type: 'custom', label: '혼주 안내', value: '' }, { type: 'custom', label: '식사 및 주차 안내', value: '' }] },
	birthday: { name: '생일', icon: '🎂', presets: [{ type: 'custom', label: '주인공', value: '' }, { type: 'custom', label: '드레스코드', value: '' }] },
	baby: { name: '돌·백일', icon: '👶', presets: [{ type: 'custom', label: '아가 이름', value: '' }, { type: 'custom', label: '아빠 · 엄마', value: '' }] },
	longevity: { name: '회갑·칠순·팔순', icon: '🌺', presets: [{ type: 'custom', label: '주인공 어르신', value: '' }, { type: 'custom', label: '가족 대표 연락처', value: '' }] },
	reunion: { name: '동창회', icon: '🎓', presets: [{ type: 'custom', label: '기수 / 졸업연도', value: '' }, { type: 'custom', label: '회비', value: '' }] },
	gathering: { name: '친목모임', icon: '🥂', presets: [{ type: 'custom', label: '회비', value: '' }, { type: 'custom', label: '모임 안내', value: '' }] },
	hiking: { name: '등산', icon: '⛰️', presets: [{ type: 'custom', label: '산행코스', value: '범어사 → 북문 → 고당봉' }, { type: 'custom', label: '집결장소 및 시간', value: '' }, { type: 'custom', label: '준비물', value: '등산화, 스틱, 식수, 행동식' }, { type: 'custom', label: '회비', value: '25,000원' }] },
	golf: { name: '골프', icon: '⛳', presets: [{ type: 'custom', label: '티오프 시간 / 코스', value: '' }, { type: 'custom', label: '그린피', value: '' }, { type: 'custom', label: '카트비', value: '25,000원' }, { type: 'custom', label: '캐디피', value: '' }] },
	cycling: { name: '사이클', icon: '🚴', presets: [{ type: 'custom', label: '라이딩 코스 / 거리', value: '' }, { type: 'custom', label: '필수 장비', value: '헬멧, 전조등, 후미등' }] },
	running: { name: '러닝', icon: '🏃', presets: [{ type: 'custom', label: '러닝 코스 / 거리', value: '' }, { type: 'custom', label: '페이스 그룹', value: '' }] },
	corporate: { name: '회사행사', icon: '🏢', presets: [{ type: 'custom', label: '주최 부서 / 담당자', value: '' }, { type: 'custom', label: '주요 식순', value: '' }] },
	school: { name: '학교행사', icon: '🏫', presets: [{ type: 'custom', label: '대상 학년 / 학과', value: '' }, { type: 'custom', label: '집결 장소', value: '' }] },
	opening: { name: '개업', icon: '🎊', presets: [{ type: 'custom', label: '상호명 / 대표', value: '' }, { type: 'custom', label: '오픈 이벤트 안내', value: '' }] },
	housewarming: { name: '집들이', icon: '🏠', presets: [{ type: 'custom', label: '출입문 / 주차 안내', value: '' }, { type: 'custom', label: '준비된 음식', value: '' }] },
	exhibition: { name: '전시·공연', icon: '🎨', presets: [{ type: 'custom', label: '참여 작가 / 출연진', value: '' }, { type: 'custom', label: '관람 시간 / 입장료', value: '' }] },
	religious: { name: '종교행사', icon: '🕊️', presets: [{ type: 'custom', label: '집례 / 인도자', value: '' }, { type: 'custom', label: '예배 · 법회 안내', value: '' }] },
	travel: { name: '여행', icon: '✈️', presets: [{ type: 'custom', label: '여행 일정표', value: '' }, { type: 'custom', label: '숙소 안내', value: '' }] },
	other: { name: '기타', icon: '✨', presets: [{ type: 'custom', label: '안내 사항', value: '' }] }
};

const TEMPLATES = {
	simple: { slug: 'simple', name: 'Simple (심플)', description: '깔끔하고 정돈된 타이포그래피 중심의 미니멀 디자인', tier: 'FREE', accent: '#111827', bg: '#ffffff' },
	classic: { slug: 'classic', name: 'Classic (클래식)', description: '격식 있는 모임·결혼·기념일에 어울리는 우아한 세리프 스타일', tier: 'FREE', accent: '#7c5a3a', bg: '#fdfaf6' },
	modern: { slug: 'modern', name: 'Modern (모던)', description: '기업 행사·전시·동창회에 어울리는 세련된 다크 포인트 스타일', tier: 'FREE', accent: '#2563eb', bg: '#f8fafc' },
	flower: { slug: 'flower', name: 'Flower (플라워)', description: '따뜻한 파스텔 플로럴 감성의 화사한 초대장 디자인', tier: 'PREMIUM', accent: '#db2777', bg: '#fff7f9' },
	nature: { slug: 'nature', name: 'Nature (네이처)', description: '등산·골프·사이클·러닝·야외 모임에 어울리는 싱그러운 그린 스타일', tier: 'FREE', accent: '#15803d', bg: '#f4fbf7' }
};

const FIELD_TYPES = {
	title: { label: '제목', icon: '🔤' },
	text: { label: '한 줄 텍스트', icon: '✏️' },
	textarea: { label: '여러 줄 설명', icon: '📝' },
	date: { label: '날짜', icon: '📅' },
	time: { label: '시간', icon: '⏰' },
	location: { label: '장소', icon: '📍' },
	address: { label: '주소', icon: '🗺️' },
	phone: { label: '전화번호', icon: '📞' },
	link: { label: '링크', icon: '🔗' },
	photo: { label: '사진', icon: '🖼️' },
	gallery: { label: '사진앨범', icon: '📸' },
	divider: { label: '구분선', icon: '➖' },
	rsvp: { label: '참석여부', icon: '✅' },
	custom: { label: '사용자 정의 항목', icon: '➕' }
};

function esc(s) {
	return String(s == null ? '' : s)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}

function renderTopNav(activePage) {
	return `
	<header style="background:#0f172a;color:#fff;padding:12px 20px;font-family:-apple-system,BlinkMacSystemFont,'Pretendard',sans-serif;border-bottom:1px solid #1e293b;">
		<div style="max-width:1320px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
			<div style="display:flex;align-items:center;gap:10px;">
				<span style="background:#2563eb;color:#fff;font-weight:800;font-size:12px;padding:4px 10px;border-radius:999px;">MANMULRO WP PLUGINS V1</span>
				<strong style="font-size:15px;">만물로 범용 초대장 & 통합 소셜 로그인 라이브 프리뷰</strong>
			</div>
			<nav style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
				<a href="/" style="padding:7px 12px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:${activePage === 'editor' ? '#fff' : '#cbd5e1'};background:${activePage === 'editor' ? '#2563eb' : 'transparent'};">✏️ 초대장 편집기 (#15)</a>
				<a href="/i/a7Fk32" style="padding:7px 12px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:${activePage === 'single' ? '#fff' : '#cbd5e1'};background:${activePage === 'single' ? '#2563eb' : 'transparent'};">📱 공개 초대장 (/i/a7Fk32)</a>
				<a href="/i/a7Fk32?print=1&paper=a4" style="padding:7px 12px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:${activePage === 'print' ? '#fff' : '#cbd5e1'};background:${activePage === 'print' ? '#2563eb' : 'transparent'};">🖨️ 인쇄/QR 뷰 (#38)</a>
				<a href="/my-invitations" style="padding:7px 12px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:${activePage === 'my' ? '#fff' : '#cbd5e1'};background:${activePage === 'my' ? '#2563eb' : 'transparent'};">📂 내 초대장·RSVP 관리 (#35)</a>
				<a href="/login" style="padding:7px 12px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:${activePage === 'login' ? '#fff' : '#cbd5e1'};background:${activePage === 'login' ? '#2563eb' : 'transparent'};">🔐 만물로 로그인 (#10)</a>
				<a href="/my-account" style="padding:7px 12px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:${activePage === 'account' ? '#fff' : '#cbd5e1'};background:${activePage === 'account' ? '#2563eb' : 'transparent'};">👤 통합 마이페이지 (#15)</a>
				<a href="/download/manmulro-invitation.zip" style="padding:7px 12px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;color:#111827;background:#FEE500;">📦 invitation.zip</a>
				<a href="/download/manmulro-social-login.zip" style="padding:7px 12px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;color:#fff;background:#03C75A;">📦 social-login.zip</a>
			</nav>
		</div>
	</header>`;
}

function renderEditorPage(inv) {
	const catChips = Object.entries(CATEGORIES)
		.map(
			([slug, c]) => `
		<button type="button" class="mm-inv-category-chip ${slug === inv.category_slug ? 'is-selected' : ''}" data-category-slug="${esc(slug)}" data-category-name="${esc(c.name)}">
			<span class="mm-inv-category-chip__icon">${esc(c.icon)}</span>
			<span class="mm-inv-category-chip__name">${esc(c.name)}</span>
		</button>`
		)
		.join('');

	const tplCards = Object.entries(TEMPLATES)
		.map(
			([slug, t]) => `
		<button type="button" class="mm-inv-template-card ${slug === inv.template ? 'is-selected' : ''}" data-template-slug="${esc(slug)}">
			<span class="mm-inv-template-swatch" style="background:${esc(t.bg)};border-color:${esc(t.accent)};">
				<span style="background:${esc(t.accent)};"></span>
			</span>
			<div class="mm-inv-template-card__info">
				<strong>${esc(t.name)}</strong>
				<span class="mm-inv-tier-badge mm-inv-tier-badge--${esc(t.tier.toLowerCase())}">${esc(t.tier)}</span>
			</div>
			<p>${esc(t.description)}</p>
		</button>`
		)
		.join('');

	const typeOptions = Object.entries(FIELD_TYPES)
		.map(([slug, m]) => `<option value="${esc(slug)}">${esc(m.icon)} ${esc(m.label)}</option>`)
		.join('');

	return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>만물로 초대장 편집기 | MANMULRO INVITATION</title>
	<link rel="stylesheet" href="/assets/inv/css/frontend.css" />
	<link rel="stylesheet" href="/assets/inv/css/editor.css" />
	<link rel="stylesheet" href="/assets/inv/themes/simple/style.css" />
	<link rel="stylesheet" href="/assets/inv/themes/classic/style.css" />
	<link rel="stylesheet" href="/assets/inv/themes/modern/style.css" />
	<link rel="stylesheet" href="/assets/inv/themes/flower/style.css" />
	<link rel="stylesheet" href="/assets/inv/themes/nature/style.css" />
</head>
<body style="margin:0;background:#f1f5f9;">
	${renderTopNav('editor')}
	<div class="mm-inv-editor-app" id="mm-inv-editor-app">
		<div class="mm-inv-editor-topbar">
			<div class="mm-inv-editor-topbar__left">
				<a href="/my-invitations" class="mm-inv-btn mm-inv-btn--outline">← 내 초대장 목록</a>
				<span class="mm-inv-editor-status-badge" id="mm-editor-status-badge">${esc(inv.status)}</span>
			</div>
			<div class="mm-inv-mobile-tabs" role="tablist">
				<button type="button" class="mm-inv-mobile-tab is-active" data-editor-tab="edit">[편집]</button>
				<button type="button" class="mm-inv-mobile-tab" data-editor-tab="preview">[미리보기]</button>
			</div>
			<div class="mm-inv-editor-topbar__right">
				<button type="button" class="mm-inv-btn mm-inv-btn--outline" id="mm-btn-save-draft">임시저장</button>
				<button type="button" class="mm-inv-btn mm-inv-btn--primary" id="mm-btn-publish">공개하기</button>
			</div>
		</div>

		<div class="mm-inv-editor-workspace" data-active-tab="edit">
			<div class="mm-inv-editor-pane">
				<section class="mm-inv-editor-card">
					<div class="mm-inv-editor-card__head">
						<h2>1. 초대장 종류 선택 (18개 카테고리 #6)</h2>
						<button type="button" class="mm-inv-btn mm-inv-btn--sm mm-inv-btn--outline" id="mm-btn-apply-category-presets">+ 선택 종류 추천 항목 추가 (#50)</button>
					</div>
					<div class="mm-inv-category-grid">${catChips}</div>
				</section>

				<section class="mm-inv-editor-card">
					<div class="mm-inv-editor-card__head">
						<h2>2. 템플릿 디자인 선택 (#11, #39, #40)</h2>
						<span class="mm-inv-editor-hint">템플릿을 변경해도 입력한 데이터와 항목은 그대로 유지됩니다 (#11)</span>
					</div>
					<div class="mm-inv-template-grid">${tplCards}</div>
				</section>

				<section class="mm-inv-editor-card">
					<h2>3. 기본 내용 및 장소 입력 (#5, #20, #21)</h2>
					<div class="mm-inv-editor-fields-grid">
						<div class="mm-inv-form-field mm-inv-form-field--full">
							<label for="mm_ed_title">초대장 제목 *</label>
							<input type="text" id="mm_ed_title" value="${esc(inv.title)}" />
						</div>
						<div class="mm-inv-form-field mm-inv-form-field--full">
							<label for="mm_ed_summary">한 줄 요약 / 부제</label>
							<textarea id="mm_ed_summary" rows="2">${esc(inv.summary)}</textarea>
						</div>
						<div class="mm-inv-form-field">
							<label for="mm_ed_event_date">행사 날짜</label>
							<input type="date" id="mm_ed_event_date" value="${esc(inv.event_date)}" />
						</div>
						<div class="mm-inv-form-field">
							<label for="mm_ed_event_time">시작 시간 / 종료 시간</label>
							<div style="display:flex;gap:8px;">
								<input type="time" id="mm_ed_event_time" value="${esc(inv.event_time)}" />
								<input type="time" id="mm_ed_event_end_time" value="${esc(inv.event_end_time)}" />
							</div>
						</div>
						<div class="mm-inv-form-field">
							<label for="mm_ed_location_name">장소명 (#20)</label>
							<input type="text" id="mm_ed_location_name" value="${esc(inv.location_name)}" />
						</div>
						<div class="mm-inv-form-field">
							<label for="mm_ed_address">주소 (#21 네이버 지도·길찾기 연동)</label>
							<input type="text" id="mm_ed_address" value="${esc(inv.address)}" />
						</div>
					</div>
				</section>

				<section class="mm-inv-editor-card">
					<div class="mm-inv-editor-card__head">
						<div>
							<h2>4. 자유 항목 시스템 (수정 · 표시/숨김 · 삭제 · Drag & Drop 순서 변경 #8, #9, #10)</h2>
							<p class="mm-inv-editor-hint">☰ 아이콘을 드래그하여 순서를 바꾸거나 사용자 정의 항목(예: 카트비 25,000원, 산행코스 등)을 자유롭게 추가하세요.</p>
						</div>
					</div>
					<div class="mm-inv-flexible-list" id="mm-editor-fields-list"></div>
					<div class="mm-inv-add-field-bar">
						<select id="mm-add-field-type" class="mm-inv-select">${typeOptions}</select>
						<input type="text" id="mm-add-field-label" placeholder="항목명 (예: 카트비, 산행코스, 준비물)" class="mm-inv-input" />
						<button type="button" class="mm-inv-btn mm-inv-btn--primary" id="mm-btn-add-field">[ + 항목 추가 ]</button>
					</div>
				</section>

				<section class="mm-inv-editor-card">
					<h2>5. 대표 이미지 1장 & 사진앨범 최대 10장 (#12, #13, #14)</h2>
					<div class="mm-inv-media-block">
						<label class="mm-inv-media-label">대표 이미지 (Cover · 공유 썸네일 · 내 초대장 카드 #12)</label>
						<div class="mm-inv-cover-control">
							<input type="url" id="mm_ed_cover_url" value="${esc(inv.cover_image_url)}" placeholder="대표 이미지 URL 또는 로컬 이미지 선택" />
							<label class="mm-inv-btn mm-inv-btn--outline">
								📷 이미지 선택
								<input type="file" id="mm_ed_cover_file" accept="image/*" hidden />
							</label>
						</div>
					</div>
					<div class="mm-inv-media-block" style="margin-top:18px;">
						<div class="mm-inv-editor-card__head">
							<label class="mm-inv-media-label">사진앨범 (<span id="mm-gallery-counter">0</span> / 10) (#13)</label>
							<div style="display:flex;gap:8px;flex-wrap:wrap;">
								<input type="url" id="mm_ed_gallery_url_input" placeholder="사진 URL 입력" class="mm-inv-input-sm" />
								<button type="button" class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm" id="mm-btn-add-gallery-url">+ URL 추가</button>
								<label class="mm-inv-btn mm-inv-btn--primary mm-inv-btn--sm">
									[+ 사진 추가]
									<input type="file" id="mm_ed_gallery_file" accept="image/*" multiple hidden />
								</label>
							</div>
						</div>
						<div class="mm-inv-editor-gallery-grid" id="mm-editor-gallery-grid"></div>
					</div>
				</section>

				<section class="mm-inv-editor-card">
					<h2>6. RSVP · 방명록 · D-Day · 공개범위 · 공개기간 · NOINDEX (#23~#34)</h2>
					<div class="mm-inv-editor-fields-grid">
						<div class="mm-inv-form-field">
							<label><input type="checkbox" id="mm_ed_rsvp_enabled" checked /> <strong>참석 여부 (RSVP) 사용</strong> (#23)</label>
						</div>
						<div class="mm-inv-form-field">
							<label for="mm_ed_rsvp_deadline">RSVP 응답 마감일 (#24)</label>
							<input type="date" id="mm_ed_rsvp_deadline" value="${esc(inv.rsvp_deadline)}" />
						</div>
						<div class="mm-inv-form-field">
							<label><input type="checkbox" id="mm_ed_guestbook_enabled" checked /> <strong>방명록 사용</strong> (#28)</label>
						</div>
						<div class="mm-inv-form-field">
							<label><input type="checkbox" id="mm_ed_dday_enabled" checked /> <strong>D-Day 표시</strong> (#30)</label>
						</div>
						<div class="mm-inv-form-field">
							<label for="mm_ed_visibility">공개 범위 (#32)</label>
							<select id="mm_ed_visibility" class="mm-inv-select">
								<option value="link_only">링크를 아는 사람만 (기본 권장)</option>
								<option value="password">비밀번호 보호</option>
								<option value="private">비공개</option>
							</select>
						</div>
						<div class="mm-inv-form-field" id="mm_ed_password_wrap" style="display:none;">
							<label for="mm_ed_password">열람 비밀번호</label>
							<input type="password" id="mm_ed_password" placeholder="방문자 열람 비밀번호" />
						</div>
						<div class="mm-inv-form-field">
							<label for="mm_ed_expiration_mode">공개기간 (#34)</label>
							<select id="mm_ed_expiration_mode" class="mm-inv-select">
								<option value="always">계속 공개</option>
								<option value="after_30">행사 후 30일</option>
								<option value="after_90">행사 후 90일</option>
								<option value="custom">직접 설정</option>
							</select>
						</div>
						<div class="mm-inv-form-field" id="mm_ed_expiration_date_wrap" style="display:none;">
							<label for="mm_ed_expiration_date">공개 종료일</label>
							<input type="date" id="mm_ed_expiration_date" value="" />
						</div>
						<div class="mm-inv-form-field mm-inv-form-field--full">
							<label><input type="checkbox" id="mm_ed_noindex" checked /> <strong>검색엔진 노출 차단 (NOINDEX 기본 권장 #33)</strong></label>
						</div>
					</div>
				</section>

				<section class="mm-inv-editor-card mm-inv-publish-result" id="mm-editor-share-panel">
					<h2>🎉 고유 단축 URL · QR · 인쇄 (#17, #18, #19, #38)</h2>
					<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
						<code id="mm-editor-short-url" style="padding:8px 12px;background:#f1f5f9;border-radius:8px;font-weight:700;">${esc(inv.short_url)}</code>
						<button type="button" class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm" id="mm-editor-copy-url">링크 복사</button>
						<a href="${esc(inv.short_url)}" id="mm-editor-open-url" class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm">공개 초대장 열기 ↗</a>
						<a href="${esc(inv.short_url)}?print=1&paper=a4" id="mm-editor-print-url" class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm">🖨️ 인쇄 / PDF</a>
					</div>
					<div id="mm-editor-qr-container" class="mm-inv-qr-box" data-qr-url="${esc(inv.short_url)}" data-qr-size="140">
						<div class="mm-inv-qr-canvas"></div>
					</div>
				</section>
			</div>

			<aside class="mm-inv-preview-pane">
				<div class="mm-inv-phone-mockup">
					<div class="mm-inv-phone-mockup__notch"><span>모바일 실시간 미리보기 (#15)</span></div>
					<div class="mm-inv-phone-mockup__screen" id="mm-live-preview-screen"></div>
				</div>
			</aside>
		</div>
	</div>

	<script>
		window.mmInvEditorConfig = {
			ajaxUrl: '/wp-admin/admin-ajax.php',
			nonce: 'demo_nonce',
			homeUrl: '/',
			categories: ${JSON.stringify(CATEGORIES)},
			templates: ${JSON.stringify(TEMPLATES)},
			fieldTypes: ${JSON.stringify(FIELD_TYPES)},
			initialInvitation: ${JSON.stringify(inv)}
		};
	</script>
	<script src="/assets/inv/js/qr.js"></script>
	<script src="/assets/inv/js/frontend.js"></script>
	<script src="/assets/inv/js/editor.js"></script>
</body>
</html>`;
}

function renderSingleInvitationPage(inv, isPrint, paper) {
	const guestbookRows = store.guestbook.filter((g) => g.invitation_id === inv.id && g.status === 'approved');
	const mapQuery = encodeURIComponent(inv.address || inv.location_name || '');
	const mapUrl = `https://map.naver.com/p/search/${mapQuery}`;
	const dirUrl = `https://map.naver.com/index.nhn?etext=${encodeURIComponent(inv.location_name || inv.address)}&menu=route&pathType=1`;

	const fieldsHtml = (inv.fields || [])
		.filter((f) => f.visible)
		.map((f) => {
			if (f.type === 'divider') return '<hr class="mm-inv-divider" />';
			if (!f.value) return '';
			if (f.type === 'textarea') {
				return `<div class="mm-inv-block"><div class="mm-inv-block__label">${esc(f.label)}</div><div class="mm-inv-block__multiline">${esc(f.value).replace(/\n/g, '<br/>')}</div></div>`;
			}
			if (f.type === 'phone') {
				const tel = f.value.replace(/[^0-9+]/g, '');
				return `<div class="mm-inv-block"><div class="mm-inv-field-row"><span class="mm-inv-field-row__label">${esc(f.label)}</span><span class="mm-inv-field-row__value">${esc(f.value)}</span></div><div class="mm-inv-contact-actions"><a href="tel:${esc(tel)}" class="mm-inv-action-chip">📞 전화하기</a><a href="sms:${esc(tel)}" class="mm-inv-action-chip">💬 문자 보내기</a></div></div>`;
			}
			return `<div class="mm-inv-block"><div class="mm-inv-field-row"><span class="mm-inv-field-row__label">${esc(f.label)}</span><span class="mm-inv-field-row__value">${esc(f.value)}</span></div></div>`;
		})
		.join('');

	if (isPrint) {
		const validPaper = ['a4', 'a5', 'postcard'].includes(paper) ? paper : 'a4';
		return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="robots" content="noindex, nofollow" />
	<title>${esc(inv.title)} - 인쇄 / PDF 저장 (#38)</title>
	<link rel="stylesheet" href="/assets/inv/css/frontend.css" />
	<link rel="stylesheet" href="/assets/inv/themes/${esc(inv.template)}/style.css" />
	<link rel="stylesheet" href="/assets/inv/css/print.css" />
</head>
<body class="mm-inv-print-body mm-inv-paper-${esc(validPaper)} mm-inv-theme-${esc(inv.template)}">
	<div class="no-print">${renderTopNav('print')}</div>
	<div class="mm-inv-print-toolbar no-print">
		<div class="mm-inv-print-toolbar__left">
			<a href="${esc(inv.short_url)}" class="mm-inv-btn mm-inv-btn--outline">← 모바일 초대장으로 돌아가기</a>
			<span>용지 규격 선택 (#38):</span>
			<a href="${esc(inv.short_url)}?print=1&paper=a4" class="mm-inv-paper-tab ${validPaper === 'a4' ? 'is-active' : ''}">A4 (210×297mm)</a>
			<a href="${esc(inv.short_url)}?print=1&paper=a5" class="mm-inv-paper-tab ${validPaper === 'a5' ? 'is-active' : ''}">A5 (148×210mm)</a>
			<a href="${esc(inv.short_url)}?print=1&paper=postcard" class="mm-inv-paper-tab ${validPaper === 'postcard' ? 'is-active' : ''}">엽서 / Postcard (100×148mm)</a>
		</div>
		<div>
			<button type="button" onclick="window.print();" class="mm-inv-btn mm-inv-btn--primary">🖨️ 인쇄 / PDF로 저장</button>
		</div>
	</div>
	<article class="mm-inv-print-sheet mm-inv-print-sheet--${esc(validPaper)}">
		<div class="mm-inv-print-sheet__inner">
			<header class="mm-inv-print-header">
				<span class="mm-inv-category-pill">${esc(inv.category_name)}</span>
				<h1 class="mm-inv-print-title">${esc(inv.title)}</h1>
				<p class="mm-inv-print-subtitle">${esc(inv.summary)}</p>
			</header>
			${inv.cover_image_url ? `<div class="mm-inv-print-cover"><img src="${esc(inv.cover_image_url)}" alt="Cover" /></div>` : ''}
			<section class="mm-inv-print-details">
				<div class="mm-inv-field-row"><span class="mm-inv-field-row__label">일시</span><span class="mm-inv-field-row__value">${esc(inv.event_date)} ${esc(inv.event_time)}</span></div>
				<div class="mm-inv-field-row"><span class="mm-inv-field-row__label">장소</span><span class="mm-inv-field-row__value">${esc(inv.location_name)} (${esc(inv.address)})</span></div>
				${fieldsHtml}
			</section>
			<footer class="mm-inv-print-qr-footer">
				<div class="mm-inv-qr-box" data-qr-url="${esc(inv.short_url)}" data-qr-size="116"><div class="mm-inv-qr-canvas"></div></div>
				<div class="mm-inv-print-qr-info">
					<strong>모바일 초대장 · 오시는 길 · 참석 여부 응답(RSVP) (#19)</strong>
					<p>스마트폰 카메라로 QR 코드를 스캔하시면 모바일 초대장·지도·길찾기 및 참석 여부 응답(RSVP)으로 연결됩니다.</p>
					<code class="mm-inv-print-url">${esc(inv.short_url)}</code>
				</div>
			</footer>
		</div>
	</article>
	<script src="/assets/inv/js/qr.js"></script>
</body>
</html>`;
	}

	const galleryHtml =
		inv.gallery_items && inv.gallery_items.length
			? `<section class="mm-inv-section mm-inv-gallery-section">
				<div class="mm-inv-section-header"><h3 class="mm-inv-section-title">사진앨범 (#13)</h3><span class="mm-inv-gallery-count">${inv.gallery_items.length} / 10</span></div>
				<div class="mm-inv-gallery-grid">
					${inv.gallery_items
						.map(
							(g, i) => `<button type="button" class="mm-inv-gallery-item" data-lightbox-src="${esc(g.full_url || g.url)}"><img src="${esc(g.thumb_url || g.url)}" alt="사진 ${i + 1}" loading="lazy" /></button>`
						)
						.join('')}
				</div>
			</section>`
			: '';

	return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="robots" content="noindex, nofollow, noarchive" />
	<title>${esc(inv.title)} | 만물로 초대장</title>
	<link rel="stylesheet" href="/assets/inv/css/frontend.css" />
	<link rel="stylesheet" href="/assets/inv/themes/${esc(inv.template)}/style.css" />
</head>
<body class="mm-inv-body mm-inv-theme-${esc(inv.template)}" style="padding-top:0;">
	${renderTopNav('single')}
	<div style="padding:24px 12px 64px;">
		<main class="mm-inv-container" id="mm-invitation-card">
			<header class="mm-inv-hero">
				<div class="mm-inv-hero__top-badges">
					<span class="mm-inv-category-pill">${esc(inv.category_name)}</span>
					<span class="mm-inv-dday-pill">D-15</span>
				</div>
				<h1 class="mm-inv-hero__title">${esc(inv.title)}</h1>
				<p class="mm-inv-hero__subtitle">${esc(inv.summary)}</p>
				${inv.cover_image_url ? `<figure class="mm-inv-hero__cover"><img src="${esc(inv.cover_image_url)}" alt="${esc(inv.title)}" /></figure>` : ''}
				<div class="mm-inv-hero__meta">
					<div class="mm-inv-hero__meta-item"><strong>일시</strong><span>${esc(inv.event_date)} ${esc(inv.event_time)} ~ ${esc(inv.event_end_time)}</span></div>
					<div class="mm-inv-hero__meta-item"><strong>장소</strong><span>${esc(inv.location_name)}</span></div>
				</div>
				<div class="mm-inv-calendar-bar">
					<a href="/?mm_inv_ics=${esc(inv.code)}" class="mm-inv-btn mm-inv-btn--outline">📅 내 일정에 저장 (.ics #31)</a>
				</div>
			</header>

			<section class="mm-inv-section">${fieldsHtml}</section>
			${galleryHtml}

			<section class="mm-inv-section">
				<h3 class="mm-inv-section-title">오시는 길 · 장소 안내 (#20, #21)</h3>
				<div class="mm-inv-location-card">
					<p class="mm-inv-location-name">${esc(inv.location_name)}</p>
					<p class="mm-inv-location-address">${esc(inv.address)}</p>
					<div class="mm-inv-location-buttons">
						<a href="${esc(mapUrl)}" target="_blank" rel="noopener" class="mm-inv-btn mm-inv-btn--naver">🗺️ 네이버 지도</a>
						<a href="${esc(dirUrl)}" target="_blank" rel="noopener" class="mm-inv-btn mm-inv-btn--outline">🧭 길찾기</a>
						<button type="button" class="mm-inv-btn mm-inv-btn--outline" data-copy-text="${esc(inv.address)}">📋 주소 복사</button>
					</div>
				</div>
			</section>

			<section class="mm-inv-section" id="mm-rsvp">
				<div class="mm-inv-section-header">
					<h3 class="mm-inv-section-title">참석 여부 (RSVP #23, #24)</h3>
					<span class="mm-inv-deadline-tag">응답 마감: ${esc(inv.rsvp_deadline)}</span>
				</div>
				<form id="mm-inv-rsvp-form" class="mm-inv-form">
					<input type="hidden" name="invitation_id" value="${inv.id}" />
					<div class="mm-inv-form-field">
						<label>성함 *</label>
						<input type="text" name="name" required placeholder="성함을 입력해주세요" />
					</div>
					<div class="mm-inv-form-field">
						<label>참석 여부 *</label>
						<div class="mm-inv-radio-group">
							<label class="mm-inv-radio-pill"><input type="radio" name="status" value="attending" checked /><span>참석</span></label>
							<label class="mm-inv-radio-pill"><input type="radio" name="status" value="declined" /><span>불참</span></label>
							<label class="mm-inv-radio-pill"><input type="radio" name="status" value="maybe" /><span>미정</span></label>
						</div>
					</div>
					<div class="mm-inv-form-field" id="mm_rsvp_count_wrap">
						<label>참석 인원</label>
						<input type="number" name="guest_count" min="1" max="20" value="1" />
					</div>
					<div class="mm-inv-form-field">
						<label>남기실 말씀</label>
						<textarea name="message" rows="2" placeholder="축하 또는 안부 인사를 남겨주세요"></textarea>
					</div>
					<button type="submit" class="mm-inv-btn mm-inv-btn--primary mm-inv-btn--block">응답하기</button>
					<div class="mm-inv-form-feedback" id="mm-rsvp-feedback"></div>
				</form>
			</section>

			<section class="mm-inv-section" id="mm-guestbook">
				<h3 class="mm-inv-section-title">방명록 (#28)</h3>
				<form id="mm-inv-guestbook-form" class="mm-inv-form">
					<input type="hidden" name="invitation_id" value="${inv.id}" />
					<div class="mm-inv-form-row">
						<input type="text" name="name" required placeholder="이름" class="mm-inv-input-sm" />
						<input type="text" name="message" required placeholder="따뜻한 메시지를 남겨주세요" class="mm-inv-input-lg" />
						<button type="submit" class="mm-inv-btn mm-inv-btn--primary">남기기</button>
					</div>
					<div class="mm-inv-form-feedback" id="mm-guestbook-feedback"></div>
				</form>
				<ul class="mm-inv-guestbook-list" id="mm-guestbook-list">
					${guestbookRows
						.map(
							(g) => `<li class="mm-inv-guestbook-item"><div class="mm-inv-guestbook-item__head"><strong>${esc(g.name)}</strong><span>${esc(g.created_at)}</span></div><p class="mm-inv-guestbook-item__msg">${esc(g.message)}</p></li>`
						)
						.join('')}
				</ul>
			</section>

			<footer class="mm-inv-footer">
				<h3 class="mm-inv-section-title">초대장 공유하기 (#18, #19, #38)</h3>
				<div class="mm-inv-share-buttons">
					<button type="button" class="mm-inv-btn mm-inv-btn--kakao" id="mm-btn-kakao-share" data-url="${esc(inv.short_url)}" data-title="${esc(inv.title)}">💬 카카오톡 공유</button>
					<button type="button" class="mm-inv-btn mm-inv-btn--outline" data-copy-text="${esc(inv.short_url)}">🔗 링크 복사</button>
					<button type="button" class="mm-inv-btn mm-inv-btn--outline" id="mm-btn-toggle-qr">📱 QR 코드</button>
					<a href="${esc(inv.short_url)}?print=1&paper=a4" class="mm-inv-btn mm-inv-btn--outline">🖨️ 인쇄 / PDF</a>
				</div>
				<div id="mm-inv-qr-drawer" class="mm-inv-qr-drawer" hidden>
					<div class="mm-inv-qr-box" data-qr-url="${esc(inv.short_url)}" data-qr-size="168"><div class="mm-inv-qr-canvas"></div></div>
				</div>
				<p class="mm-inv-brand-credit">Powered by <strong>MANMULRO INVITATION</strong></p>
			</footer>
		</main>
	</div>

	<div id="mm-inv-lightbox" class="mm-inv-lightbox" hidden>
		<button type="button" class="mm-inv-lightbox__close">&times;</button>
		<img src="" alt="확대 이미지" id="mm-inv-lightbox-img" />
	</div>

	<script>
		window.mmInvPublic = { ajaxUrl: '/wp-admin/admin-ajax.php', nonce: 'demo_nonce', shortUrl: ${JSON.stringify(inv.short_url)} };
	</script>
	<script src="/assets/inv/js/qr.js"></script>
	<script src="/assets/inv/js/frontend.js"></script>
</body>
</html>`;
}

function renderMyInvitationsPage(query) {
	const statusFilter = query.rsvp_status || '';
	const searchQ = (query.rsvp_s || '').toLowerCase();
	const allRsvps = store.rsvps.filter((r) => r.invitation_id === 101);
	const filteredRsvps = allRsvps.filter((r) => {
		if (statusFilter && r.status !== statusFilter) return false;
		if (searchQ && !r.name.toLowerCase().includes(searchQ) && !r.message.toLowerCase().includes(searchQ)) return false;
		return true;
	});

	const attending = allRsvps.filter((r) => r.status === 'attending');
	const declined = allRsvps.filter((r) => r.status === 'declined');
	const maybe = allRsvps.filter((r) => r.status === 'maybe');
	const expectedHeads = attending.reduce((acc, r) => acc + Number(r.guest_count || 0), 0);

	return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>내 초대장 & RSVP 관리 | MANMULRO INVITATION</title>
	<link rel="stylesheet" href="/assets/inv/css/frontend.css" />
	<link rel="stylesheet" href="/assets/inv/css/editor.css" />
</head>
<body style="margin:0;background:#f8fafc;">
	${renderTopNav('my')}
	<div class="mm-inv-my-wrap">
		<div class="mm-inv-my-header">
			<div>
				<span class="mm-inv-category-pill">MANMULRO INVITATION</span>
				<h2 style="margin:6px 0 4px;">내 초대장 관리 (#35, #36, #37)</h2>
				<p style="margin:0;color:#64748b;font-size:14px;">보기 · 관리(RSVP/방명록) · 공유 · 수정 · 복제 · 인쇄 · QR · 보관 · 삭제 기능을 제공합니다.</p>
			</div>
			<a href="/" class="mm-inv-btn mm-inv-btn--primary">[+ 새 초대장 만들기]</a>
		</div>

		<nav class="mm-inv-my-tabs">
			<a href="/my-invitations" class="mm-inv-my-tab is-active"><span>진행 예정</span> <strong>${store.invitations.length}</strong></a>
			<a href="/my-invitations" class="mm-inv-my-tab"><span>지난 행사</span> <strong>0</strong></a>
			<a href="/my-invitations" class="mm-inv-my-tab"><span>임시저장</span> <strong>0</strong></a>
			<a href="/my-invitations" class="mm-inv-my-tab"><span>보관</span> <strong>0</strong></a>
		</nav>

		<section class="mm-inv-manage-panel">
			<div class="mm-inv-manage-panel__head">
				<div>
					<span class="mm-inv-category-pill">실시간 참석 현황 & 참석자 목록 (#26, #27)</span>
					<h3 style="margin:6px 0 0;">2026 가을 금정산 정기 산행 초대</h3>
				</div>
				<a href="/export-rsvp.csv" class="mm-inv-btn mm-inv-btn--primary">📥 CSV 다운로드 (#27)</a>
			</div>

			<div class="mm-inv-stats-grid">
				<div class="mm-inv-stat-box"><span>전체 응답</span><strong>${allRsvps.length}건</strong></div>
				<div class="mm-inv-stat-box"><span>참석</span><strong>${attending.length}건</strong></div>
				<div class="mm-inv-stat-box"><span>불참</span><strong>${declined.length}건</strong></div>
				<div class="mm-inv-stat-box"><span>미정</span><strong>${maybe.length}건</strong></div>
				<div class="mm-inv-stat-box mm-inv-stat-box--highlight"><span>예상 참석 인원</span><strong>${expectedHeads}명</strong></div>
			</div>

			<form method="get" action="/my-invitations" class="mm-inv-rsvp-toolbar">
				<select name="rsvp_status" class="mm-inv-select" style="max-width:160px;">
					<option value="">전체 필터</option>
					<option value="attending" ${statusFilter === 'attending' ? 'selected' : ''}>참석 필터</option>
					<option value="declined" ${statusFilter === 'declined' ? 'selected' : ''}>불참 필터</option>
					<option value="maybe" ${statusFilter === 'maybe' ? 'selected' : ''}>미정 필터</option>
				</select>
				<input type="search" name="rsvp_s" value="${esc(query.rsvp_s || '')}" placeholder="이름 또는 메시지 검색 (#27)" class="mm-inv-input" style="max-width:260px;" />
				<button type="submit" class="mm-inv-btn mm-inv-btn--outline">검색 / 필터 적용</button>
			</form>

			<table class="mm-inv-table">
				<thead>
					<tr><th>이름</th><th>상태</th><th>인원</th><th>메시지</th><th>응답일</th></tr>
				</thead>
				<tbody>
					${filteredRsvps
						.map(
							(r) => `<tr>
						<td><strong>${esc(r.name)}</strong></td>
						<td>${r.status === 'attending' ? '참석' : r.status === 'declined' ? '불참' : '미정'}</td>
						<td>${r.guest_count}명</td>
						<td>${esc(r.message)}</td>
						<td>${esc(r.created_at)}</td>
					</tr>`
						)
						.join('')}
				</tbody>
			</table>
		</section>

		<div class="mm-inv-cards-grid">
			${store.invitations
				.map(
					(item) => `<article class="mm-inv-card-item">
				<div class="mm-inv-card-item__media">
					${item.cover_image_url ? `<img src="${esc(item.cover_image_url)}" alt="${esc(item.title)}" />` : `<div class="mm-inv-card-item__placeholder">${esc(item.category_name)}</div>`}
					<span class="mm-inv-card-item__status">${esc(item.status)}</span>
				</div>
				<div class="mm-inv-card-item__body">
					<div class="mm-inv-card-item__meta">
						<span>${esc(item.category_name)}</span><span>·</span><span>행사일: ${esc(item.event_date)}</span>
					</div>
					<h3 class="mm-inv-card-item__title">${esc(item.title)}</h3>
					<div class="mm-inv-card-item__rsvp">고유 URL: <code>${esc(item.short_url)}</code> · 템플릿: <strong>${esc(item.template.toUpperCase())}</strong></div>
					<div class="mm-inv-card-item__actions">
						<a href="${esc(item.short_url)}" class="mm-inv-action-btn">보기</a>
						<a href="/my-invitations" class="mm-inv-action-btn">관리</a>
						<button type="button" class="mm-inv-action-btn" data-copy-text="${esc(item.short_url)}">공유</button>
						<a href="/?id=${item.id}" class="mm-inv-action-btn">수정</a>
						<button type="button" class="mm-inv-action-btn mm-js-duplicate-inv" data-invitation-id="${item.id}">복제</button>
						<a href="${esc(item.short_url)}?print=1&paper=a4" class="mm-inv-action-btn">인쇄</a>
						<button type="button" class="mm-inv-action-btn mm-js-show-card-qr" data-short-url="${esc(item.short_url)}">QR</button>
						<button type="button" class="mm-inv-action-btn mm-js-archive-inv" data-invitation-id="${item.id}" data-status="ARCHIVED">보관</button>
						<button type="button" class="mm-inv-action-btn mm-inv-action-btn--danger mm-js-delete-inv" data-invitation-id="${item.id}">삭제</button>
					</div>
				</div>
			</article>`
				)
				.join('')}
		</div>

		<div id="mm-my-qr-modal" class="mm-inv-lightbox" hidden>
			<div class="mm-inv-qr-modal-card">
				<button type="button" class="mm-inv-lightbox__close" id="mm-close-qr-modal">&times;</button>
				<h3>초대장 QR 코드 (#19)</h3>
				<div id="mm-my-qr-target" class="mm-inv-qr-box" data-qr-url="/i/a7Fk32" data-qr-size="180"><div class="mm-inv-qr-canvas"></div></div>
				<p class="mm-inv-qr-caption" id="mm-my-qr-url-label"></p>
			</div>
		</div>
	</div>
	<script>window.mmInvMyConfig = { ajaxUrl: '/wp-admin/admin-ajax.php', nonce: 'demo_nonce' };</script>
	<script src="/assets/inv/js/qr.js"></script>
	<script src="/assets/inv/js/frontend.js"></script>
	<script src="/assets/inv/js/editor.js"></script>
</body>
</html>`;
}

function renderLoginPage() {
	return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>만물로 로그인 | MANMULRO SOCIAL LOGIN</title>
	<link rel="stylesheet" href="/assets/sl/css/social-login.css" />
</head>
<body style="margin:0;background:#f8fafc;">
	${renderTopNav('login')}
	<div class="mm-sl-card">
		<div class="mm-sl-header">
			<span class="mm-sl-brand-badge">MANMULRO SOCIAL LOGIN V1</span>
			<h2 class="mm-sl-title">만물로 로그인</h2>
			<p class="mm-sl-subtitle">초대장·커뮤니티·모임·쇼핑·예약 등 만물로 전체 서비스를 하나의 계정으로 이용하세요.</p>
		</div>

		<div class="mm-sl-social-buttons">
			<a href="/my-account?connected=kakao" class="mm-sl-btn mm-sl-btn--kakao">
				<span class="mm-sl-btn__label">카카오로 시작하기</span>
			</a>
			<a href="/my-account?connected=naver" class="mm-sl-btn mm-sl-btn--naver">
				<span class="mm-sl-btn__label">네이버로 시작하기</span>
			</a>
			<a href="/my-account?connected=google" class="mm-sl-btn mm-sl-btn--google">
				<span class="mm-sl-btn__label">Google로 계속하기</span>
			</a>
		</div>

		<div class="mm-sl-divider"><span>또는</span></div>

		<form method="get" action="/my-account" class="mm-sl-form">
			<div class="mm-sl-field">
				<label>이메일</label>
				<input type="text" name="email" placeholder="user@example.com" required />
			</div>
			<div class="mm-sl-field">
				<label>비밀번호</label>
				<input type="password" name="pwd" placeholder="비밀번호를 입력하세요" required />
			</div>
			<button type="submit" class="mm-sl-submit-btn">로그인</button>
		</form>

		<div class="mm-sl-footer-links">
			<a href="/my-account">회원가입</a>
			<span class="mm-sl-dot">·</span>
			<a href="/my-account">비밀번호 찾기</a>
		</div>
	</div>
	<script src="/assets/sl/js/social-login.js"></script>
</body>
</html>`;
}

function renderMyAccountPage() {
	return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>통합 마이페이지 | MANMULRO SOCIAL LOGIN</title>
	<link rel="stylesheet" href="/assets/sl/css/social-login.css" />
</head>
<body style="margin:0;background:#f8fafc;">
	${renderTopNav('account')}
	<div class="mm-sl-account-wrap" style="padding:0 16px;">
		<div class="mm-sl-account-header">
			<div class="mm-sl-account-user">
				<div style="width:56px;height:56px;border-radius:50%;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px;">만</div>
				<div>
					<span class="mm-sl-brand-badge">WORDPRESS USER #328 (#6)</span>
					<h2 class="mm-sl-account-name">홍길동 님</h2>
					<p class="mm-sl-account-email">user@example.com</p>
				</div>
			</div>
			<a class="mm-sl-logout-link" href="/login">로그아웃</a>
		</div>

		<div class="mm-sl-account-grid">
			<section class="mm-sl-panel">
				<h3 class="mm-sl-panel-title">내 정보 · 프로필 및 비밀번호 (#15)</h3>
				<form class="mm-sl-form" onsubmit="event.preventDefault();alert('내 정보가 저장되었습니다.');">
					<div class="mm-sl-field">
						<label>표시 이름</label>
						<input type="text" value="홍길동" />
					</div>
					<div class="mm-sl-field">
						<label>이메일</label>
						<input type="email" value="user@example.com" />
					</div>
					<div class="mm-sl-field">
						<label>새 비밀번호 설정</label>
						<input type="password" placeholder="새 비밀번호 (8자 이상)" />
					</div>
					<button type="submit" class="mm-sl-submit-btn">내 정보 저장</button>
				</form>
			</section>

			<section class="mm-sl-panel">
				<h3 class="mm-sl-panel-title">로그인 관리 · 연결된 소셜 계정 (#12, #13, #14)</h3>
				<p class="mm-sl-panel-desc">이메일만 같다고 자동 통합하지 않으며, 로그인 상태에서 안전하게 계정을 연결·해제합니다.</p>
				<ul class="mm-sl-provider-list">
					<li class="mm-sl-provider-item">
						<div class="mm-sl-provider-info">
							<span class="mm-sl-provider-badge mm-sl-provider-badge--kakao">Kakao</span>
							<span class="mm-sl-status mm-sl-status--connected">연결됨 (단독 연결 시 해제 보호 테스트)</span>
						</div>
						<div class="mm-sl-provider-action">
							<button type="button" class="mm-sl-unlink-btn mm-sl-unlink-btn--disabled" data-can-unlink="0">연결 해제</button>
						</div>
					</li>
					<li class="mm-sl-provider-item">
						<div class="mm-sl-provider-info">
							<span class="mm-sl-provider-badge mm-sl-provider-badge--naver">Naver</span>
							<span class="mm-sl-status mm-sl-status--connected">연결됨</span>
						</div>
						<div class="mm-sl-provider-action">
							<a href="/my-account" class="mm-sl-unlink-btn" data-can-unlink="1">연결 해제</a>
						</div>
					</li>
					<li class="mm-sl-provider-item">
						<div class="mm-sl-provider-info">
							<span class="mm-sl-provider-badge mm-sl-provider-badge--google">Google</span>
							<span class="mm-sl-status mm-sl-status--disconnected">연결 안 됨</span>
						</div>
						<div class="mm-sl-provider-action">
							<a href="/my-account" class="mm-sl-connect-btn" onclick="alert('Google OAuth 계정 연결 화면으로 이동합니다.');">연결</a>
						</div>
					</li>
					<li class="mm-sl-provider-item">
						<div class="mm-sl-provider-info">
							<span class="mm-sl-provider-badge mm-sl-provider-badge--email">Email</span>
							<span class="mm-sl-status mm-sl-status--connected">user@example.com</span>
						</div>
					</li>
				</ul>
				<p class="mm-sl-hint-box">
					💡 위 <strong>Kakao [연결 해제]</strong> 버튼을 클릭해보세요: 마지막 로그인 수단 보호(#14) 동작으로 <code>"다른 로그인 방법을 먼저 연결해주세요."</code> 안내가 표시됩니다.
				</p>
			</section>
		</div>

		<section class="mm-sl-panel mm-sl-panel--full">
			<h3 class="mm-sl-panel-title">내 서비스 (#15, #16 — 독립 아키텍처 훅 연동)</h3>
			<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
				<div>
					<h4 style="margin:0 0 4px;">만물로 초대장 (내 초대장 ${store.invitations.length}건)</h4>
					<p style="margin:0;font-size:13px;color:#6b7280;">세상의 모든 만남을 위한 범용 초대장을 제작하고 RSVP·방명록을 관리하세요.</p>
				</div>
				<div style="display:flex;gap:8px;">
					<a href="/my-invitations" class="mm-sl-primary-btn">내 초대장 관리</a>
					<a href="/" class="mm-sl-secondary-btn">+ 새 초대장 만들기</a>
				</div>
			</div>
		</section>
	</div>
	<script>
		window.mmSocialLogin = {
			i18n: {
				confirmUnlink: '해당 소셜 계정 연결을 해제하시겠습니까?',
				confirmWithdraw: '정말로 회원 탈퇴를 요청하시겠습니까?',
				lastMethodErr: '다른 로그인 방법을 먼저 연결해주세요.'
			}
		};
	</script>
	<script src="/assets/sl/js/social-login.js"></script>
</body>
</html>`;
}

const server = http.createServer((req, res) => {
	const parsed = url.parse(req.url, true);
	const pathname = parsed.pathname;

	// Serve static plugin assets
	if (pathname.startsWith('/assets/inv/')) {
		const rel = pathname.replace('/assets/inv/', '');
		const mapped = rel.startsWith('themes/')
			? path.join(__dirname, 'manmulro-invitation', 'templates', rel)
			: path.join(__dirname, 'manmulro-invitation', 'assets', rel);
		if (fs.existsSync(mapped)) {
			const ext = path.extname(mapped);
			res.writeHead(200, {
				'Content-Type': ext === '.css' ? 'text/css; charset=utf-8' : 'application/javascript; charset=utf-8'
			});
			fs.createReadStream(mapped).pipe(res);
			return;
		}
	}

	if (pathname.startsWith('/assets/sl/')) {
		const rel = pathname.replace('/assets/sl/', '');
		const mapped = path.join(__dirname, 'manmulro-social-login', 'assets', rel);
		if (fs.existsSync(mapped)) {
			const ext = path.extname(mapped);
			res.writeHead(200, {
				'Content-Type': ext === '.css' ? 'text/css; charset=utf-8' : 'application/javascript; charset=utf-8'
			});
			fs.createReadStream(mapped).pipe(res);
			return;
		}
	}

	// Serve downloadable WordPress Plugin ZIP files
	if (pathname === '/download/manmulro-invitation.zip' || pathname === '/download/manmulro-social-login.zip') {
		const zipFile = path.basename(pathname);
		const fullPath = path.join(__dirname, zipFile);
		if (fs.existsSync(fullPath)) {
			res.writeHead(200, {
				'Content-Type': 'application/zip',
				'Content-Disposition': `attachment; filename="${zipFile}"`
			});
			fs.createReadStream(fullPath).pipe(res);
			return;
		}
	}

	// Serve CSV export (#27)
	if (pathname === '/export-rsvp.csv') {
		res.writeHead(200, {
			'Content-Type': 'text/csv; charset=utf-8',
			'Content-Disposition': 'attachment; filename="manmulro-rsvp-101.csv"'
		});
		const lines = ['\uFEFF이름,상태,인원,메시지,응답일'];
		store.rsvps.forEach((r) => {
			const st = r.status === 'attending' ? '참석' : r.status === 'declined' ? '불참' : '미정';
			lines.push(`"${r.name}","${st}",${r.guest_count},"${r.message}","${r.created_at}"`);
		});
		res.end(lines.join('\r\n'));
		return;
	}

	// Serve ICS calendar export (#31)
	if (parsed.query && parsed.query.mm_inv_ics) {
		const inv = store.invitations[0];
		res.writeHead(200, {
			'Content-Type': 'text/calendar; charset=utf-8',
			'Content-Disposition': `attachment; filename="manmulro-${inv.code}.ics"`
		});
		res.end(
			[
				'BEGIN:VCALENDAR',
				'VERSION:2.0',
				'PRODID:-//MANMULRO//Manmulro Invitation V1//KO',
				'BEGIN:VEVENT',
				`SUMMARY:${inv.title}`,
				`LOCATION:${inv.location_name} ${inv.address}`,
				`DESCRIPTION:${inv.summary}`,
				'END:VEVENT',
				'END:VCALENDAR'
			].join('\r\n')
		);
		return;
	}

	// Simulate WordPress AJAX endpoints for Editor, RSVP, Guestbook, Duplicate
	if (pathname === '/wp-admin/admin-ajax.php' && req.method === 'POST') {
		let body = '';
		req.on('data', (chunk) => {
			body += chunk.toString();
		});
		req.on('end', () => {
			res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
			if (body.includes('mm_inv_submit_rsvp')) {
				res.end(JSON.stringify({ success: true, data: { message: '참석 여부 응답이 전달되었습니다. 감사합니다!' } }));
				return;
			}
			if (body.includes('mm_inv_submit_guestbook')) {
				res.end(
					JSON.stringify({
						success: true,
						data: {
							message: '방명록 메시지가 등록되었습니다.',
							entry: { name: '방문객', message: '축하드립니다! 즐거운 모임 되세요.', created_at: '방금 전' }
						}
					})
				);
				return;
			}
			if (body.includes('mm_inv_duplicate')) {
				const src = store.invitations[0];
				const copy = JSON.parse(JSON.stringify(src));
				copy.id = Date.now();
				copy.code = 'c8Kp91';
				copy.short_url = '/i/c8Kp91';
				copy.title = src.title + ' - 복사본';
				copy.status = 'DRAFT';
				store.invitations.unshift(copy);
				res.end(
					JSON.stringify({
						success: true,
						data: { message: '초대장이 복제되었습니다.', new_id: copy.id, editor_url: '/?id=' + copy.id }
					})
				);
				return;
			}
			// Default save response
			const inv = store.invitations[0];
			res.end(
				JSON.stringify({
					success: true,
					data: {
						message: '초대장이 성공적으로 저장 및 공개되었습니다! (/i/' + inv.code + ')',
						invitation: inv
					}
				})
			);
		});
		return;
	}

	// Short URL `/i/{code}` (#17)
	if (pathname.startsWith('/i/')) {
		const code = pathname.replace('/i/', '').replace(/\/$/, '');
		const inv = store.invitations.find((x) => x.code === code) || store.invitations[0];
		const isPrint = parsed.query && parsed.query.print === '1';
		const paper = (parsed.query && parsed.query.paper) || 'a4';
		res.writeHead(200, {
			'Content-Type': 'text/html; charset=utf-8',
			'X-Robots-Tag': 'noindex, nofollow'
		});
		res.end(renderSingleInvitationPage(inv, isPrint, paper));
		return;
	}

	if (pathname === '/my-invitations') {
		res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
		res.end(renderMyInvitationsPage(parsed.query || {}));
		return;
	}

	if (pathname === '/login') {
		res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
		res.end(renderLoginPage());
		return;
	}

	if (pathname === '/my-account') {
		res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
		res.end(renderMyAccountPage());
		return;
	}

	// Default `/` -> Invitation Editor
	const editId = parsed.query && parsed.query.id ? Number(parsed.query.id) : 101;
	const inv = store.invitations.find((x) => x.id === editId) || store.invitations[0];
	res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
	res.end(renderEditorPage(inv));
});

server.listen(PORT, '0.0.0.0', () => {
	console.log(`Manmulro Plugins Live Preview listening on http://0.0.0.0:${PORT}`);
});
