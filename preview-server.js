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
			cover_image_url: '/assets/inv/images/invitation-samples/hiking.svg',
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
	simple: { slug: 'simple', name: 'Simple (심플)', description: '종이 질감과 절제된 패턴이 화면 전체를 감싸는 미니멀 디자인', tier: 'FREE', accent: '#111827', bg: '#ffffff' },
	classic: { slug: 'classic', name: 'Classic (클래식)', description: '한지 질감과 은은한 緣 모티프 배경을 더한 우아한 세리프 스타일', tier: 'FREE', accent: '#7c5a3a', bg: '#fdfaf6' },
	modern: { slug: 'modern', name: 'Modern (모던)', description: '현대적인 기하학 배경과 선명한 포인트의 세련된 디자인', tier: 'FREE', accent: '#2563eb', bg: '#f8fafc' },
	flower: { slug: 'flower', name: 'Flower (플라워)', description: '화사한 파스텔 꽃무늬가 화면 전체에 번지는 로맨틱 디자인', tier: 'PREMIUM', accent: '#db2777', bg: '#fff7f9' },
	nature: { slug: 'nature', name: 'Nature (네이처)', description: '은은한 山 글자와 산 능선 배경을 담은 싱그러운 아웃도어 디자인', tier: 'FREE', accent: '#15803d', bg: '#f4fbf7' }
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
		<div style="max-width:1360px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
			<div style="display:flex;align-items:center;gap:10px;">
				<span style="background:#9a3412;color:#fff;font-weight:800;font-size:12px;padding:4px 10px;border-radius:999px;">MANMULRO WP PLUGINS V1 (3종)</span>
				<strong style="font-size:14px;">메뉴판 만들기 · 범용 초대장 · 통합 소셜 로그인</strong>
			</div>
			<nav style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
				<a href="/menu-builder" style="padding:7px 11px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;color:${activePage === 'menu-builder' ? '#fff' : '#fde68a'};background:${activePage === 'menu-builder' ? '#9a3412' : 'rgba(154,52,18,0.35)'};">🍽️ 메뉴판 5단계 빌더</a>
				<a href="/menu/manmulro-hansik" style="padding:7px 11px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;color:${activePage === 'menu-mobile' ? '#fff' : '#fde68a'};background:${activePage === 'menu-mobile' ? '#9a3412' : 'transparent'};">📱 QR 모바일 메뉴판</a>
				<a href="/menu/manmulro-hansik/kimchi-jjigae" style="padding:7px 11px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;color:${activePage === 'menu-detail' ? '#fff' : '#fde68a'};background:${activePage === 'menu-detail' ? '#9a3412' : 'transparent'};">🥘 메뉴 상세페이지 (#9)</a>
				<a href="/menu/manmulro-hansik?print=1&paper=a4&orientation=portrait" style="padding:7px 11px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;color:${activePage === 'menu-print' ? '#fff' : '#fde68a'};background:${activePage === 'menu-print' ? '#9a3412' : 'transparent'};">🖨️ 메뉴판 인쇄 (#14)</a>
				<a href="/invitation-editor" style="padding:7px 11px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;color:${activePage === 'editor' ? '#fff' : '#cbd5e1'};background:${activePage === 'editor' ? '#2563eb' : 'transparent'};">✏️ 초대장 편집기</a>
				<a href="/i/a7Fk32" style="padding:7px 11px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;color:${activePage === 'single' ? '#fff' : '#cbd5e1'};background:${activePage === 'single' ? '#2563eb' : 'transparent'};">💌 공개 초대장</a>
				<a href="/login" style="padding:7px 11px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;color:${activePage === 'login' ? '#fff' : '#cbd5e1'};background:${activePage === 'login' ? '#2563eb' : 'transparent'};">🔐 소셜 로그인</a>
				<a href="/download/manmulro-menu.zip" style="padding:6px 10px;border-radius:8px;font-size:12px;font-weight:800;text-decoration:none;color:#fff;background:#ea580c;">📦 menu.zip</a>
				<a href="/download/manmulro-invitation.zip" style="padding:6px 10px;border-radius:8px;font-size:12px;font-weight:800;text-decoration:none;color:#111827;background:#FEE500;">📦 invitation.zip</a>
				<a href="/download/manmulro-social-login.zip" style="padding:6px 10px;border-radius:8px;font-size:12px;font-weight:800;text-decoration:none;color:#fff;background:#03C75A;">📦 social-login.zip</a>
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
			<span class="mm-inv-template-swatch" style="background-color:${esc(t.bg)};background-image:url('/assets/inv/themes/${esc(slug)}/background.svg');background-size:cover;border-color:${esc(t.accent)};">
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
					<div class="mm-inv-category-grid" id="mm-editor-category-grid">${catChips}</div>
					<p class="mm-inv-editor-hint mm-inv-category-note">종류를 선택하면 제목·인사말·추천 이미지가 해당 행사에 맞게 바뀝니다. 직접 수정한 내용은 덮어쓰지 않습니다.</p>
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
						<div class="mm-inv-form-field mm-inv-form-field--full mm-inv-cover-field">
							<div class="mm-inv-cover-field__heading">
								<label>초대장 대표 이미지 <span>16:9</span></label>
								<p>선택한 종류의 예시 이미지가 표시됩니다. 이미지를 누르면 추천 이미지와 내 앨범을 둘러볼 수 있어요.</p>
							</div>
							<button type="button" class="mm-inv-cover-stage" id="mm-btn-open-cover-gallery" aria-label="초대장 이미지 갤러리 열기">
								<img id="mm_ed_cover_preview" src="${esc(inv.cover_image_url)}" alt="${esc(inv.category_name)} 초대장 예시" />
								<span class="mm-inv-cover-stage__shade"></span>
								<span class="mm-inv-cover-stage__badge" id="mm-cover-category-badge">${esc(inv.category_name)} 추천 이미지</span>
								<span class="mm-inv-cover-stage__edit">🖼️ 갤러리에서 이미지 바꾸기</span>
							</button>
							<div class="mm-inv-cover-actions">
								<button type="button" class="mm-inv-btn mm-inv-btn--outline" data-trigger-cover-upload>내 이미지 올리기</button>
								<button type="button" class="mm-inv-btn mm-inv-btn--ghost" id="mm-btn-reset-cover-sample">이 종류의 예시 이미지로</button>
								<input type="file" id="mm_ed_cover_file" accept="image/*" hidden />
							</div>
							<details class="mm-inv-cover-url-details">
								<summary>이미지 주소(URL)를 직접 입력</summary>
								<input type="url" id="mm_ed_cover_url" value="${esc(inv.cover_image_url)}" placeholder="https://example.com/invitation-cover.jpg" />
							</details>
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
					<h2>5. 사진앨범 (최대 10장)</h2>
					<div class="mm-inv-media-block">
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
		<div class="mm-inv-cover-picker" id="mm-cover-gallery-modal" hidden>
			<button type="button" class="mm-inv-cover-picker__backdrop" data-cover-picker-close aria-label="이미지 갤러리 닫기"></button>
			<section class="mm-inv-cover-picker__dialog" role="dialog" aria-modal="true" aria-labelledby="mm-cover-picker-title" tabindex="-1">
				<header class="mm-inv-cover-picker__header">
					<div><span class="mm-inv-badge">16:9 COVER GALLERY</span><h2 id="mm-cover-picker-title">초대장 이미지 갤러리</h2><p>종류별 예시를 고르거나, 내가 올린 사진앨범에서 대표 이미지를 선택하세요.</p></div>
					<button type="button" class="mm-inv-cover-picker__close" data-cover-picker-close aria-label="닫기">&times;</button>
				</header>
				<div class="mm-inv-cover-picker__body">
					<section class="mm-inv-cover-picker__section"><div class="mm-inv-cover-picker__section-head"><div><h3>종류별 추천 이미지</h3><p>현재 선택한 초대장 종류와 어울리는 이미지입니다.</p></div></div><div class="mm-inv-cover-gallery-grid" id="mm-cover-sample-grid"></div></section>
					<section class="mm-inv-cover-picker__section"><div class="mm-inv-cover-picker__section-head"><div><h3>내 사진앨범</h3><p>이 초대장에 추가한 사진을 대표 이미지로 사용할 수 있어요.</p></div><button type="button" class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm" data-trigger-cover-upload>새 사진 올리기</button></div><div class="mm-inv-cover-gallery-grid" id="mm-cover-user-grid"></div><p class="mm-inv-cover-picker__empty" id="mm-cover-user-empty" hidden>아직 내 앨범에 사진이 없습니다. 새 사진을 올리면 여기에서 대표 이미지로 선택할 수 있어요.</p></section>
				</div>
				<footer class="mm-inv-cover-picker__footer"><button type="button" class="mm-inv-btn mm-inv-btn--outline" data-trigger-cover-upload>📤 내 컴퓨터에서 이미지 업로드</button><button type="button" class="mm-inv-btn mm-inv-btn--ghost" data-cover-picker-close>닫기</button></footer>
			</section>
		</div>
	</div>

	<script>
		window.mmInvEditorConfig = {
			ajaxUrl: '/wp-admin/admin-ajax.php',
			nonce: 'demo_nonce',
			homeUrl: '/',
			sampleImageBase: '/assets/inv/images/invitation-samples/',
			sampleMode: true,
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
					${inv.cover_image_url ? `<div class="mm-inv-print-cover"><img src="${esc(inv.cover_image_url)}" alt="Cover" /></div>` : ''}
					<p class="mm-inv-print-subtitle">${esc(inv.summary)}</p>
				</header>
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
				${inv.cover_image_url ? `<figure class="mm-inv-hero__cover"><img src="${esc(inv.cover_image_url)}" alt="${esc(inv.title)}" /></figure>` : ''}
				<p class="mm-inv-hero__subtitle">${esc(inv.summary)}</p>
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

function renderAdminDemoPage(fishingAdded) {
	return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>WordPress 관리자 — 만물로 초대장 (#41, #42, #50)</title>
	<link rel="stylesheet" href="/assets/inv/css/frontend.css" />
	<link rel="stylesheet" href="/assets/inv/css/editor.css" />
</head>
<body style="margin:0;background:#f0f0f1;font-family:-apple-system,BlinkMacSystemFont,'Pretendard',sans-serif;">
	${renderTopNav('admin')}
	<div style="display:grid;grid-template-columns:220px 1fr;min-height:calc(100vh - 58px);">
		<aside style="background:#1d2327;color:#f0f0f1;padding:20px 0;">
			<div style="padding:0 16px 14px;font-weight:800;font-size:15px;color:#72aee6;border-bottom:1px solid #2c3338;">📧 초대장 (WP Admin #41)</div>
			<ul style="list-style:none;padding:10px 0;margin:0;font-size:13px;line-height:2.2;">
				<li style="padding:0 16px;background:#2271b1;color:#fff;font-weight:700;">├── 대시보드 (#42)</li>
				<li style="padding:0 16px;color:#c3c4c7;">├── 전체 초대장</li>
				<li style="padding:0 16px;color:#c3c4c7;">├── 카테고리 (#6, #50)</li>
				<li style="padding:0 16px;color:#c3c4c7;">├── 템플릿 (#39, #40)</li>
				<li style="padding:0 16px;color:#c3c4c7;">├── RSVP (#26, #27)</li>
				<li style="padding:0 16px;color:#c3c4c7;">├── 방명록 (#28)</li>
				<li style="padding:0 16px;color:#c3c4c7;">└── 설정 (#41)</li>
			</ul>
		</aside>
		<main style="padding:28px;max-width:1080px;">
			<h1 style="margin:0 0 8px;font-size:24px;">만물로 초대장 관리자 대시보드 (#41, #42, #50)</h1>
			<p style="margin:0 0 20px;color:#50575e;font-size:14px;">전체 초대장, 이번 달 생성, 공개 중, 전체 RSVP, 활성 회원, 인기 카테고리/템플릿 및 무코드 카테고리 확장(#50)을 관리합니다.</p>

			${fishingAdded ? `<div style="padding:14px 18px;background:#ecfdf5;border:1px solid #10b981;color:#065f46;border-radius:10px;margin-bottom:20px;font-weight:700;">✅ 코드 수정 없이 '🎣 낚시모임' 카테고리(출조일, 집결시간, 집결장소, 선박, 출조비, 준비물)가 등록되었습니다! 이제 [초대장 편집기]에서 '낚시모임'을 선택해보세요.</div>` : ''}

			<div class="mm-inv-stats-grid">
				<div class="mm-inv-stat-box"><span>전체 초대장</span><strong>${store.invitations.length}건</strong></div>
				<div class="mm-inv-stat-box"><span>이번 달 생성</span><strong>${store.invitations.length}건</strong></div>
				<div class="mm-inv-stat-box"><span>공개 중 (PUBLISHED)</span><strong>${store.invitations.filter(x => x.status === 'PUBLISHED').length}건</strong></div>
				<div class="mm-inv-stat-box"><span>전체 RSVP 응답</span><strong>${store.rsvps.length}건</strong></div>
				<div class="mm-inv-stat-box mm-inv-stat-box--highlight"><span>활성 회원</span><strong>1명</strong></div>
			</div>

			<section class="mm-inv-editor-card" style="margin-bottom:20px;">
				<div class="mm-inv-editor-card__head">
					<div>
						<h2>#50 최종 제품 철학: 코드 수정 없이 새 초대장 종류 등록 (Category + Template + Flexible Fields)</h2>
						<p class="mm-inv-editor-hint">명세서 #50 예시: '낚시모임'과 기본 항목(출조일, 집결시간, 집결장소, 선박, 출조비, 준비물)을 원클릭으로 등록해보세요.</p>
					</div>
					<a href="/wp-admin-demo?add_fishing=1" class="mm-inv-btn mm-inv-btn--primary">🎣 '낚시모임' 카테고리 + 기본 항목 즉시 등록 (#50)</a>
				</div>
				<p style="font-size:13px;color:#475569;margin:0;">현재 등록된 카테고리 (${Object.keys(CATEGORIES).length}종): ${Object.values(CATEGORIES).map(c => `${c.icon} ${c.name}`).join(', ')}</p>
			</section>

			<section class="mm-inv-editor-card">
				<h2>#39 & #40 초대장 템플릿 목록 (FREE / PREMIUM 구분 준비 완료)</h2>
				<table class="mm-inv-table">
					<thead>
						<tr><th>Slug</th><th>템플릿 이름</th><th>설명</th><th>구분 (#40)</th></tr>
					</thead>
					<tbody>
						${Object.values(TEMPLATES).map(t => `<tr><td><code>${esc(t.slug)}</code></td><td><strong>${esc(t.name)}</strong></td><td>${esc(t.description)}</td><td><span class="mm-inv-tier-badge mm-inv-tier-badge--${t.tier.toLowerCase()}">${t.tier}</span></td></tr>`).join('')}
					</tbody>
				</table>
			</section>
		</main>
	</div>
</body>
</html>`;
}

function renderMenuBuilderPage() {
	return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>만물로 메뉴판 만들기 5단계 빌더 | MANMULRO MENU V1</title>
	<link rel="stylesheet" href="/assets/menu/css/mobile-menu.css" />
	<link rel="stylesheet" href="/assets/menu/css/builder.css" />
</head>
<body style="margin:0;background:#f1f5f9;">
	${renderTopNav('menu-builder')}
	<div class="mm-menu-builder-wrap" id="mm-menu-builder-app" data-default-source-img="/assets/menu/images/sample-source-menu.svg">
		<header class="mm-menu-stepper">
			<div class="mm-menu-stepper__brand">
				<strong>만물로 메뉴판 만들기 V1</strong>
				<span class="mm-menu-autosave-badge" id="mm-menu-autosave-badge">자동 저장됨 (#15)</span>
			</div>
			<ol class="mm-menu-stepper__steps" role="tablist">
				<li><button type="button" class="mm-step-btn is-active" data-step="1">STEP 1. 시작 방법</button></li>
				<li><button type="button" class="mm-step-btn" data-step="2">STEP 2. 메뉴 구성</button></li>
				<li><button type="button" class="mm-step-btn" data-step="3">STEP 3. 상세정보 (선택)</button></li>
				<li><button type="button" class="mm-step-btn" data-step="4">STEP 4. 디자인</button></li>
				<li><button type="button" class="mm-step-btn" data-step="5">STEP 5. 완성 및 배포</button></li>
			</ol>
			<div class="mm-menu-stepper__actions">
				<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-open-source-modal">🖼️ [원본 메뉴판 보기]</button>
				<button type="button" class="mm-menu-btn mm-menu-btn--primary" onclick="alert('공통 메뉴 데이터가 저장되었습니다!');">💾 저장하기</button>
			</div>
		</header>

		<!-- STEP 1 -->
		<section class="mm-step-panel is-active" data-step-panel="1">
			<div class="mm-start-hero">
				<h2>어떻게 메뉴판을 시작하시겠어요?</h2>
				<p>"메뉴는 한 번만 입력하세요." 기존 메뉴판이 있으면 사진으로, 없으면 직접 만들어보세요.</p>
				<div class="mm-start-setup-bar">
					<label><span>상호명</span><input type="text" id="mm-init-business-name" value="만물로 한식당" /></label>
					<label>
						<span>업종 선택 (#6.1)</span>
						<select id="mm-init-business-type">
							<option value="음식점" selected>음식점</option>
							<option value="카페">카페</option>
							<option value="주점">주점</option>
							<option value="베이커리">베이커리</option>
							<option value="미용/뷰티">미용/뷰티</option>
							<option value="서비스 가격표">서비스 가격표</option>
							<option value="기타">기타</option>
						</select>
					</label>
				</div>
				<div class="mm-start-cards">
					<article class="mm-start-card">
						<div class="mm-start-card__icon">📸</div>
						<h3>[사진으로 시작하기]</h3>
						<p>기존 메뉴판 사진을 업로드하여 OCR로 텍스트·가격·위치 좌표를 추출하고 원본과 나란히 비교·확인합니다 (#5).</p>
						<button type="button" class="mm-menu-btn mm-menu-btn--primary" id="mm-btn-start-ocr">사진으로 시작하기 (OCR 비교)</button>
					</article>
					<article class="mm-start-card">
						<div class="mm-start-card__icon">✍️</div>
						<h3>[직접 만들기]</h3>
						<p>업종별 기본 카테고리(식사·사이드·음료·주류)로 시작해 메뉴명과 가격을 직접 입력합니다 (#6).</p>
						<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-start-direct">직접 만들기</button>
					</article>
				</div>
			</div>
		</section>

		<!-- STEP 2 -->
		<section class="mm-step-panel" data-step-panel="2">
			<div class="mm-step2-toolbar">
				<div class="mm-step2-toolbar__left">
					<button type="button" class="mm-submode-btn" id="mm-btn-toggle-ocr-panel">📷 기존 메뉴판에서 가져오기 (OCR 원본 비교 #2.4)</button>
					<button type="button" class="mm-submode-btn is-active" id="mm-btn-show-menu-manager">📋 공통 메뉴 목록 관리</button>
				</div>
				<div class="mm-step2-toolbar__right">
					<button type="button" class="mm-menu-btn mm-menu-btn--primary" id="mm-btn-add-menu-item">+ 메뉴 추가</button>
					<button type="button" class="mm-menu-btn mm-menu-btn--outline" data-goto-step="3">다음: 메뉴 상세정보 (STEP 3) →</button>
				</div>
			</div>

			<div class="mm-ocr-split-panel" id="mm-ocr-split-panel" hidden>
				<div class="mm-ocr-split-header">
					<div>
						<h3>원본 메뉴판 비교 & OCR 결과 검증 (#5.5 ~ #5.9)</h3>
						<p>좌측 원본 메뉴판의 파란/주황 박스를 클릭하면 우측 항목이 강조됩니다. 신뢰도 낮은 항목은 <strong>⚠️ 확인 필요</strong>로 표시되며, <strong>[확인하고 메뉴로 가져오기]</strong> 클릭 시에만 공통 메뉴 데이터로 확정됩니다.</p>
					</div>
					<div class="mm-ocr-preprocess-controls">
						<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-ocr-rotate">↻ 회전 (#5.2)</button>
						<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-ocr-zoom-in">＋ 확대</button>
						<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-ocr-contrast">◐ 대비 보정</button>
					</div>
				</div>
				<div class="mm-ocr-split-grid">
					<div class="mm-ocr-source-pane">
						<div class="mm-ocr-canvas-wrap">
							<img src="/assets/menu/images/sample-source-menu.svg" alt="원본 메뉴판 (SOURCE_IMAGE)" id="mm-ocr-source-img" />
							<div class="mm-ocr-bbox-layer" id="mm-ocr-bbox-layer"></div>
						</div>
					</div>
					<div class="mm-ocr-results-pane">
						<div class="mm-ocr-results-toolbar">
							<span>인식된 후보 항목 (자동 확정 금지 원칙 #2.2, #5.8)</span>
							<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" id="mm-btn-add-ocr-row">+ 행 추가</button>
						</div>
						<div class="mm-ocr-rows-list" id="mm-ocr-rows-list"></div>
						<div class="mm-ocr-confirm-footer">
							<button type="button" class="mm-menu-btn mm-menu-btn--primary mm-menu-btn--full" id="mm-btn-confirm-ocr-import">✅ [확인하고 메뉴로 가져오기] (#5.9)</button>
						</div>
					</div>
				</div>
			</div>

			<div class="mm-common-menu-manager" id="mm-common-menu-manager">
				<div class="mm-category-bar">
					<div class="mm-category-tabs" id="mm-builder-category-tabs"></div>
					<div class="mm-category-add">
						<input type="text" id="mm-new-category-name" placeholder="새 카테고리명" />
						<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" id="mm-btn-add-category">+ 카테고리 추가</button>
					</div>
				</div>
				<div class="mm-bulk-bar">
					<label class="mm-bulk-check-all">
						<input type="checkbox" id="mm-bulk-select-all" />
						<span>전체 선택 (<strong id="mm-bulk-selected-count">0</strong>개)</span>
					</label>
					<div class="mm-bulk-actions">
						<span>일괄 가격 조정 (#16.1):</span>
						<button type="button" class="mm-bulk-chip" data-bulk-type="price_delta" data-delta="500">+500원</button>
						<button type="button" class="mm-bulk-chip" data-bulk-type="price_delta" data-delta="1000">+1,000원</button>
						<button type="button" class="mm-bulk-chip" data-bulk-type="price_delta" data-delta="-500">-500원</button>
						<span>일괄 상태 변경 (#16.2):</span>
						<button type="button" class="mm-bulk-chip" data-bulk-type="status" data-status="ACTIVE">판매중</button>
						<button type="button" class="mm-bulk-chip" data-bulk-type="status" data-status="SOLD_OUT">품절</button>
						<button type="button" class="mm-bulk-chip" data-bulk-type="status" data-status="HIDDEN">숨김</button>
						<select id="mm-bulk-target-category"></select>
					</div>
				</div>
				<div class="mm-builder-items-list" id="mm-builder-items-list"></div>
			</div>
		</section>

		<!-- STEP 3 -->
		<section class="mm-step-panel" data-step-panel="3">
			<div class="mm-step3-layout">
				<aside class="mm-step3-sidebar">
					<h3>상세정보를 입력할 메뉴 선택</h3>
					<p style="font-size:12px;color:#64748b;">필요한 메뉴에만 선택적으로 상세 설명·재료·맛 특징·알레르기·원산지를 입력하세요 (#8).</p>
					<div class="mm-step3-item-picker" id="mm-step3-item-picker"></div>
				</aside>
				<div class="mm-step3-editor">
					<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
						<h3 id="mm-detail-editing-title" style="margin:0;">메뉴 상세정보</h3>
						<a href="/menu/manmulro-hansik/kimchi-jjigae" target="_blank" id="mm-detail-preview-link" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm">🔗 상세페이지 열기 ↗</a>
					</div>
					<div class="mm-form-group">
						<label>상세 설명 — 이 음식은 어떤 음식인가요? (#8.1)</label>
						<textarea id="mm-detail-description" rows="3"></textarea>
					</div>
					<div class="mm-form-group">
						<label>주요 재료 및 재료 설명 (#8.2, #8.3)</label>
						<div id="mm-detail-ingredients-rows"></div>
						<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" id="mm-btn-add-ingredient">+ 주요 재료 추가</button>
					</div>
					<div class="mm-form-group">
						<label>맛 특징 (0~5점 → ●●●○○ #8.4)</label>
						<div class="mm-flavor-grid">
							${['매운맛', '단맛', '짠맛', '신맛', '고소함', '담백함']
								.map(
									(f) => `<label class="mm-flavor-control"><span>${f}</span><input type="range" min="0" max="5" value="0" class="mm-flavor-slider" data-flavor="${f}" /><output class="mm-flavor-output">○○○○○</output></label>`
								)
								.join('')}
						</div>
					</div>
					<div class="mm-form-group">
						<label>추천 대상 (#8.5)</label>
						<input type="text" id="mm-detail-recommended" />
					</div>
					<div class="mm-form-group">
						<label>알레르기 정보 (체크한 항목만 표시 — 절대 추측 금지 #8.6, #28.7)</label>
						<div class="mm-allergen-checks">
							${['우유', '계란', '대두', '밀', '땅콩', '견과류', '갑각류', '생선', '기타']
								.map((a) => `<label class="mm-check-pill"><input type="checkbox" class="mm-allergen-cb" value="${a}" /><span>${a}</span></label>`)
								.join('')}
						</div>
					</div>
					<div class="mm-form-group">
						<label>원산지 정보 (직접 입력한 정보만 표시 #8.7, #28.7)</label>
						<div id="mm-detail-origins-rows"></div>
						<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" id="mm-btn-add-origin">+ 원산지 항목 추가</button>
					</div>
					<div style="display:flex;gap:10px;">
						<button type="button" class="mm-menu-btn mm-menu-btn--primary" id="mm-btn-save-item-detail">💾 이 메뉴의 상세정보 저장</button>
						<button type="button" class="mm-menu-btn mm-menu-btn--outline" data-goto-step="4">다음: 메뉴판 디자인 (STEP 4) →</button>
					</div>
				</div>
			</div>
		</section>

		<!-- STEP 4 -->
		<section class="mm-step-panel" data-step-panel="4">
			<div class="mm-step4-layout">
				<div class="mm-step4-controls">
					<h3>기본 템플릿 선택 (8종 #12.1)</h3>
					<div class="mm-template-grid">
						${[
							{ slug: 'korean', name: '한식', desc: '정갈한 한식당 디자인', p: '#9a3412', bg: '#fffbeb', t: '#1c1917' },
							{ slug: 'cafe', name: '카페', desc: '에스프레소 & 크림 톤', p: '#78350f', bg: '#faf8f5', t: '#292524' },
							{ slug: 'fine_dining', name: '고급 레스토랑', desc: '다크 차콜 & 골드', p: '#b45309', bg: '#18181b', t: '#f4f4f5' },
							{ slug: 'simple', name: '심플', desc: '화이트 & 블랙', p: '#111827', bg: '#ffffff', t: '#111827' },
							{ slug: 'modern', name: '모던', desc: '세련된 블루 포인트', p: '#2563eb', bg: '#f8fafc', t: '#0f172a' },
							{ slug: 'traditional', name: '전통', desc: '한지 질감과 먹색', p: '#57534e', bg: '#f5f0e6', t: '#1c1917' },
							{ slug: 'bakery', name: '베이커리', desc: '따뜻한 버터 옐로우', p: '#d97706', bg: '#fffdf7', t: '#451a03' },
							{ slug: 'pub', name: '주점', desc: '다크 네이비 & 앰버', p: '#f59e0b', bg: '#0f172a', t: '#f8fafc' }
						]
							.map(
								(tpl, i) => `<button type="button" class="mm-template-card ${i === 0 ? 'is-active' : ''}" data-template="${tpl.slug}" data-primary="${tpl.p}" data-bg="${tpl.bg}" data-text="${tpl.t}"><span class="mm-template-swatch" style="background:${tpl.bg};border-color:${tpl.p};color:${tpl.p};">Aa</span><strong>${tpl.name}</strong><small>${tpl.desc}</small></button>`
							)
							.join('')}
					</div>
					<h3>세부 디자인 설정 (#12.2)</h3>
					<div style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
						<label>대표 색상 <input type="color" id="mm-design-primary-color" value="#9a3412" /></label>
						<label>배경 색상 <input type="color" id="mm-design-bg-color" value="#fffbeb" /></label>
						<label>글자 색상 <input type="color" id="mm-design-text-color" value="#1c1917" /></label>
						<label>가격 스타일 <select id="mm-design-price-style"><option value="won">9,000원</option><option value="comma">₩9,000</option><option value="dots">···· 9,000원</option></select></label>
						<label><input type="checkbox" id="mm-design-show-images" checked /> 메뉴 사진 표시</label>
					</div>
					<div style="margin-top:20px;">
						<button type="button" class="mm-menu-btn mm-menu-btn--primary" data-goto-step="5">다음: 완성 및 배포 (STEP 5) →</button>
					</div>
				</div>
				<div class="mm-step4-preview">
					<div class="mm-phone-mockup">
						<div class="mm-phone-mockup__notch">실시간 모바일 메뉴판 미리보기 (#12.3)</div>
						<div class="mm-phone-mockup__screen" id="mm-live-design-preview"></div>
					</div>
				</div>
			</div>
		</section>

		<!-- STEP 5 -->
		<section class="mm-step-panel" data-step-panel="5">
			<div class="mm-publish-grid">
				<article class="mm-publish-card">
					<h3>1. 모바일 QR 메뉴판 (#13)</h3>
					<p>메뉴나 가격을 수정해도 QR 코드와 메뉴판 주소는 절대 변경되지 않습니다 (#13.1, #28.5).</p>
					<div class="mm-publish-url-box">
						<input type="text" id="mm-publish-public-url" readonly value="/menu/manmulro-hansik" />
						<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-copy-public-url">주소 복사</button>
						<a href="/menu/manmulro-hansik" target="_blank" class="mm-menu-btn mm-menu-btn--primary" id="mm-link-open-public-url">모바일 메뉴판 열기 ↗</a>
					</div>
					<div id="mm-publish-qr-holder"></div>
				</article>
				<article class="mm-publish-card">
					<h3>2. A4 / A3 세로·가로 인쇄용 메뉴판 (#14)</h3>
					<p>동일한 공통 메뉴 데이터로 인쇄용 메뉴판(PDF / JPG / PNG)을 생성합니다.</p>
					<div class="mm-print-links-grid">
						<a href="/menu/manmulro-hansik?print=1&paper=a4&orientation=portrait" target="_blank" class="mm-menu-btn mm-menu-btn--primary" data-print-paper="a4" data-print-orient="portrait">🖨️ A4 세로 인쇄/PDF</a>
						<a href="/menu/manmulro-hansik?print=1&paper=a4&orientation=landscape" target="_blank" class="mm-menu-btn mm-menu-btn--outline" data-print-paper="a4" data-print-orient="landscape">🖨️ A4 가로 인쇄/PDF</a>
						<a href="/menu/manmulro-hansik?print=1&paper=a3&orientation=portrait" target="_blank" class="mm-menu-btn mm-menu-btn--outline" data-print-paper="a3" data-print-orient="portrait">🖨️ A3 세로 인쇄/PDF</a>
						<a href="/menu/manmulro-hansik?print=1&paper=a3&orientation=landscape" target="_blank" class="mm-menu-btn mm-menu-btn--outline" data-print-paper="a3" data-print-orient="landscape">🖨️ A3 가로 인쇄/PDF</a>
					</div>
				</article>
				<article class="mm-publish-card">
					<h3>3. 개별 메뉴 상세페이지 고유 URL (#9.1, #9.2)</h3>
					<ul id="mm-publish-item-urls" style="list-style:none;padding:0;margin:0;"></ul>
				</article>
			</div>
		</section>

		<!-- Source Modal (#11) -->
		<div class="mm-source-modal" id="mm-source-modal" hidden>
			<div class="mm-source-modal__backdrop" id="mm-source-modal-close"></div>
			<div class="mm-source-modal__dialog">
				<div class="mm-source-modal__header">
					<h3>원본 메뉴판 보기 (SOURCE_IMAGE 보관 및 위치 확인 #11)</h3>
					<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" id="mm-source-modal-close-btn">닫기 ✕</button>
				</div>
				<div class="mm-ocr-canvas-wrap">
					<img src="/assets/menu/images/sample-source-menu.svg" alt="원본 메뉴판" id="mm-modal-source-img" />
					<div class="mm-ocr-bbox-layer" id="mm-modal-bbox-layer"></div>
				</div>
			</div>
		</div>
	</div>
	<script src="/assets/menu/js/ocr-viewer.js"></script>
	<script src="/assets/menu/js/mobile-menu.js"></script>
	<script src="/assets/menu/js/builder.js"></script>
</body>
</html>`;
}

function renderMobileMenuPage(isPrint, paper, orientation) {
	if (isPrint) {
		const p = paper === 'a3' ? 'a3' : 'a4';
		const o = orientation === 'landscape' ? 'landscape' : 'portrait';
		return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>만물로 한식당 - 인쇄용 메뉴판 (#14)</title>
	<link rel="stylesheet" href="/assets/menu/css/mobile-menu.css" />
	<link rel="stylesheet" href="/assets/menu/css/print-menu.css" />
</head>
<body class="mm-menu-print-body mm-paper-${p} mm-orient-${o}">
	<div class="no-print" style="margin:-24px -24px 20px;">${renderTopNav('menu-print')}</div>
	<div class="mm-menu-print-toolbar no-print">
		<div class="mm-menu-print-toolbar__group">
			<a href="/menu/manmulro-hansik" class="mm-menu-btn mm-menu-btn--outline">← 모바일 메뉴판</a>
			<span>용지:</span>
			<a href="/menu/manmulro-hansik?print=1&paper=a4&orientation=${o}" class="mm-print-chip ${p === 'a4' ? 'is-active' : ''}">A4</a>
			<a href="/menu/manmulro-hansik?print=1&paper=a3&orientation=${o}" class="mm-print-chip ${p === 'a3' ? 'is-active' : ''}">A3</a>
			<span>방향:</span>
			<a href="/menu/manmulro-hansik?print=1&paper=${p}&orientation=portrait" class="mm-print-chip ${o === 'portrait' ? 'is-active' : ''}">세로</a>
			<a href="/menu/manmulro-hansik?print=1&paper=${p}&orientation=landscape" class="mm-print-chip ${o === 'landscape' ? 'is-active' : ''}">가로</a>
		</div>
		<div class="mm-menu-print-toolbar__group">
			<button type="button" onclick="window.print();" class="mm-menu-btn mm-menu-btn--primary">🖨️ PDF 인쇄 / 저장</button>
		</div>
	</div>
	<article class="mm-menu-print-sheet">
		<header class="mm-menu-print-header">
			<div>
				<span class="mm-menu-store-type">음식점 MENU</span>
				<h1>만물로 한식당</h1>
			</div>
			<div class="mm-menu-qr-box" data-qr-url="https://manmulro.com/menu/manmulro-hansik" data-qr-size="96">
				<div class="mm-menu-qr-canvas"></div>
			</div>
		</header>
		<div class="mm-menu-print-columns">
			<section class="mm-menu-print-cat">
				<h2>식사</h2>
				<div class="mm-menu-print-row"><div class="mm-menu-print-row__main"><strong>김치찌개</strong><span class="mm-menu-print-row__price">9,000원</span></div><p class="mm-menu-print-row__desc">국내산 숙성 김치와 한돈으로 깊게 끓인 대표 찌개</p></div>
				<div class="mm-menu-print-row"><div class="mm-menu-print-row__main"><strong>차돌박이 된장찌개</strong><span class="mm-menu-print-row__price">9,500원</span></div><p class="mm-menu-print-row__desc">고소한 차돌박이와 전통 집된장의 구수한 조화</p></div>
				<div class="mm-menu-print-row"><div class="mm-menu-print-row__main"><strong>제육볶음 정식</strong><span class="mm-menu-print-row__price">11,000원</span></div><p class="mm-menu-print-row__desc">매콤달콤 불향 가득한 한돈 제육볶음과 쌈채소</p></div>
			</section>
			<section class="mm-menu-print-cat">
				<h2>사이드 · 별미</h2>
				<div class="mm-menu-print-row"><div class="mm-menu-print-row__main"><strong>해물파전</strong><span class="mm-menu-print-row__price">15,000원</span></div><p class="mm-menu-print-row__desc">오징어·새우와 향긋한 쪽파를 바삭하게 부쳐낸 별미</p></div>
				<div class="mm-menu-print-row"><div class="mm-menu-print-row__main"><strong>수제 감자만두</strong><span class="mm-menu-print-row__price">6,000원</span></div><p class="mm-menu-print-row__desc">쫄깃한 감자피에 속이 꽉 찬 수제 찐만두</p></div>
			</section>
		</div>
		<footer class="mm-menu-print-footer">
			<span>상단 QR 코드를 스캔하시면 각 메뉴의 상세 사진·재료·맛 특징·알레르기·원산지 정보를 확인하실 수 있습니다.</span>
			<code>/menu/manmulro-hansik</code>
		</footer>
	</article>
	<script src="/assets/menu/js/mobile-menu.js"></script>
</body>
</html>`;
	}

	return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>만물로 한식당 | 모바일 QR 메뉴판 (#13)</title>
	<link rel="stylesheet" href="/assets/menu/css/mobile-menu.css" />
</head>
<body class="mm-menu-mobile-body">
	${renderTopNav('menu-mobile')}
	<main class="mm-menu-mobile-shell">
		<header class="mm-menu-store-header">
			<span class="mm-menu-store-type">음식점</span>
			<h1 class="mm-menu-store-title">만물로 한식당</h1>
		</header>
		<nav class="mm-menu-cat-nav">
			<button type="button" class="mm-menu-cat-pill is-active" data-filter-cat="all">[전체]</button>
			<button type="button" class="mm-menu-cat-pill" data-filter-cat="1">[식사]</button>
			<button type="button" class="mm-menu-cat-pill" data-filter-cat="2">[사이드]</button>
		</nav>
		<section class="mm-menu-list-section">
			<div class="mm-menu-category-group" data-category-group="1">
				<h2 class="mm-menu-category-heading">식사</h2>
				<div class="mm-menu-items-stack">
					<a href="/menu/manmulro-hansik/kimchi-jjigae" class="mm-menu-item-card">
						<div class="mm-menu-item-card__info">
							<div class="mm-menu-item-card__tags"><span class="mm-menu-tag">대표 메뉴</span><span class="mm-menu-tag">인기 메뉴</span></div>
							<h3 class="mm-menu-item-card__name">김치찌개</h3>
							<p class="mm-menu-item-card__desc">국내산 숙성 김치와 한돈으로 깊게 끓인 대표 찌개</p>
							<div class="mm-menu-item-card__price">9,000원</div>
						</div>
					</a>
					<a href="/menu/manmulro-hansik/chadol-doenjang" class="mm-menu-item-card">
						<div class="mm-menu-item-card__info">
							<div class="mm-menu-item-card__tags"><span class="mm-menu-tag">추천 메뉴</span></div>
							<h3 class="mm-menu-item-card__name">차돌박이 된장찌개</h3>
							<p class="mm-menu-item-card__desc">고소한 차돌박이와 전통 집된장의 구수한 조화</p>
							<div class="mm-menu-item-card__price">9,500원</div>
						</div>
					</a>
				</div>
			</div>
			<div class="mm-menu-category-group" data-category-group="2">
				<h2 class="mm-menu-category-heading">사이드</h2>
				<div class="mm-menu-items-stack">
					<a href="/menu/manmulro-hansik/haemul-pajeon" class="mm-menu-item-card">
						<div class="mm-menu-item-card__info">
							<div class="mm-menu-item-card__tags"><span class="mm-menu-tag">인기 메뉴</span></div>
							<h3 class="mm-menu-item-card__name">해물파전</h3>
							<p class="mm-menu-item-card__desc">오징어·새우와 향긋한 쪽파를 바삭하게 부쳐낸 별미</p>
							<div class="mm-menu-item-card__price">15,000원</div>
						</div>
					</a>
				</div>
			</div>
		</section>
		<footer class="mm-menu-mobile-footer">
			<p>메뉴를 터치하면 사진·재료·맛 특징·알레르기·원산지 상세 정보를 확인할 수 있습니다 (#9, #13.5).</p>
		</footer>
	</main>
	<script src="/assets/menu/js/mobile-menu.js"></script>
</body>
</html>`;
}

function renderMenuItemDetailPage(itemSlug) {
	return `<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>김치찌개 - 만물로 한식당 메뉴 상세페이지 (#9)</title>
	<link rel="stylesheet" href="/assets/menu/css/mobile-menu.css" />
</head>
<body class="mm-menu-mobile-body">
	${renderTopNav('menu-detail')}
	<main class="mm-menu-mobile-shell mm-menu-detail-shell">
		<div class="mm-menu-detail-topbar">
			<a href="/menu/manmulro-hansik" class="mm-menu-back-link">← 메뉴판으로 돌아가기</a>
			<button type="button" class="mm-menu-share-btn" data-share-url="https://manmulro.com/menu/manmulro-hansik/${esc(itemSlug)}" data-share-title="김치찌개 | 만물로 한식당">🔗 공유하기</button>
		</div>
		<header class="mm-menu-detail-header">
			<div class="mm-menu-item-card__tags"><span class="mm-menu-tag">대표 메뉴</span><span class="mm-menu-tag">인기 메뉴</span></div>
			<h1 class="mm-menu-detail-title">김치찌개</h1>
			<div class="mm-menu-detail-price">9,000원</div>
			<p class="mm-menu-detail-short">국내산 숙성 김치와 한돈으로 깊게 끓인 대표 찌개</p>
		</header>
		<section class="mm-menu-detail-card">
			<h2>이 음식은 어떤 음식인가요?</h2>
			<p>직접 담근 1년 숙성 김치와 국내산 생삼겹살, 국산 대두 두부를 듬뿍 넣어 얼큰하고 깊게 끓여낸 만물로 한식당의 대표 식사 메뉴입니다.</p>
		</section>
		<section class="mm-menu-detail-card">
			<h2>주요 재료</h2>
			<ul class="mm-menu-ingredient-chips">
				<li><strong>숙성 배추김치</strong> <span>— 직접 담근 1년 해남 배추김치</span></li>
				<li><strong>국내산 한돈</strong> <span>— 신선한 생삼겹살</span></li>
				<li><strong>국산 두부</strong> <span>— 매일 아침 만든 손두부</span></li>
			</ul>
		</section>
		<section class="mm-menu-detail-card">
			<h2>맛 특징</h2>
			<div class="mm-menu-flavor-list">
				<div class="mm-menu-flavor-row"><span>매운맛</span><span class="mm-menu-flavor-row__dots">●●●○○</span></div>
				<div class="mm-menu-flavor-row"><span>고소함</span><span class="mm-menu-flavor-row__dots">●●●●○</span></div>
				<div class="mm-menu-flavor-row"><span>담백함</span><span class="mm-menu-flavor-row__dots">●●●●○</span></div>
			</div>
		</section>
		<section class="mm-menu-detail-card">
			<h2>추천 대상</h2>
			<p>얼큰하고 깊은 국물을 좋아하시는 분 / 든든한 한 끼 식사를 찾으시는 분</p>
		</section>
		<section class="mm-menu-detail-card">
			<h2>알레르기 정보</h2>
			<p>대두 / 돼지고기</p>
		</section>
		<section class="mm-menu-detail-card">
			<h2>원산지</h2>
			<ul class="mm-menu-origin-list">
				<li><strong>돼지고기:</strong> 국내산 한돈</li>
				<li><strong>배추김치:</strong> 국내산 (배추·고춧가루 국내산)</li>
				<li><strong>두부:</strong> 국내산 대두</li>
			</ul>
		</section>
		<div class="mm-menu-detail-bottom-actions">
			<a href="/menu/manmulro-hansik" class="mm-menu-btn mm-menu-btn--outline">[메뉴판으로 돌아가기]</a>
			<button type="button" class="mm-menu-btn mm-menu-btn--primary mm-menu-share-btn" data-share-url="https://manmulro.com/menu/manmulro-hansik/${esc(itemSlug)}">[공유하기]</button>
		</div>
	</main>
	<script src="/assets/menu/js/mobile-menu.js"></script>
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
			const ext = path.extname(mapped).toLowerCase();
			const mime = {
				'.css': 'text/css; charset=utf-8',
				'.js': 'application/javascript; charset=utf-8',
				'.svg': 'image/svg+xml',
				'.png': 'image/png',
				'.jpg': 'image/jpeg',
				'.jpeg': 'image/jpeg',
				'.webp': 'image/webp',
				'.gif': 'image/gif'
			}[ext] || 'application/octet-stream';
			res.writeHead(200, { 'Content-Type': mime });
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

	if (pathname.startsWith('/assets/menu/')) {
		const rel = pathname.replace('/assets/menu/', '');
		const mapped = path.join(__dirname, 'manmulro-menu', 'assets', rel);
		if (fs.existsSync(mapped)) {
			const ext = path.extname(mapped);
			const mime =
				ext === '.css'
					? 'text/css; charset=utf-8'
					: ext === '.svg'
					? 'image/svg+xml'
					: 'application/javascript; charset=utf-8';
			res.writeHead(200, { 'Content-Type': mime });
			fs.createReadStream(mapped).pipe(res);
			return;
		}
	}

	// Serve downloadable WordPress Plugin ZIP files
	if (
		pathname === '/download/manmulro-menu.zip' ||
		pathname === '/download/manmulro-invitation.zip' ||
		pathname === '/download/manmulro-social-login.zip'
	) {
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

	if (pathname === '/wp-admin-demo') {
		if (parsed.query && parsed.query.add_fishing === '1') {
			CATEGORIES.fishing = {
				name: '낚시모임',
				icon: '🎣',
				presets: [
					{ type: 'custom', label: '출조일', value: '2026-11-01' },
					{ type: 'custom', label: '집결시간', value: '새벽 04:30' },
					{ type: 'custom', label: '집결장소', value: '부산 다대포항 1부두' },
					{ type: 'custom', label: '선박', value: '만물로피싱호 (22인승)' },
					{ type: 'custom', label: '출조비', value: '110,000원 (중식·미끼 포함)' },
					{ type: 'custom', label: '준비물', value: '구명조끼, 아이스박스, 개인 낚싯대' }
				]
			};
		}
		res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
		res.end(renderAdminDemoPage(parsed.query && parsed.query.add_fishing === '1'));
		return;
	}

	// Manmulro Menu Builder (`/menu-builder`)
	if (pathname === '/menu-builder' || pathname === '/menu-builder/') {
		res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
		res.end(renderMenuBuilderPage());
		return;
	}

	// Manmulro Menu Public Store & Item Detail (`/menu/{store}` and `/menu/{store}/{item}`)
	if (pathname.startsWith('/menu/')) {
		const parts = pathname.replace(/^\/menu\//, '').replace(/\/$/, '').split('/');
		if (parts.length >= 2 && parts[1]) {
			res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
			res.end(renderMenuItemDetailPage(parts[1]));
			return;
		}
		const isPrint = parsed.query && parsed.query.print === '1';
		res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
		res.end(renderMobileMenuPage(isPrint, parsed.query && parsed.query.paper, parsed.query && parsed.query.orientation));
		return;
	}

	// Default `/` -> Menu Builder (or Invitation Editor if `/invitation-editor` or `?id=` is passed)
	if (pathname !== '/invitation-editor' && (!parsed.query || !parsed.query.id)) {
		res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
		res.end(renderMenuBuilderPage());
		return;
	}

	const editId = parsed.query && parsed.query.id ? Number(parsed.query.id) : 101;
	const inv = store.invitations.find((x) => x.id === editId) || store.invitations[0];
	res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
	res.end(renderEditorPage(inv));
});

server.listen(PORT, '0.0.0.0', () => {
	console.log(`Manmulro Plugins Live Preview listening on http://0.0.0.0:${PORT}`);
});
