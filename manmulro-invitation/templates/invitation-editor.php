<?php
/**
 * Invitation Editor Template (`[manmulro_invitation_editor]`)
 *
 * Implements sections #7, #8, #9, #10, #11, #12, #13, #15, #16, #23, #24, #28, #30, #32, #33, #34, #39, #40, #50:
 * - PC: Left side Invitation Editor / Right side Real-Time Mobile Preview (#15)
 * - Mobile: [편집] / [미리보기] Tabs (#15)
 * - Complete separation of Event Data and Template Design (#11)
 *
 * @package Manmulro_Invitation
 * @var array|null $inv Existing invitation array when editing, or null when creating new.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$categories         = MM_Inv_Taxonomies::get_default_categories();
$templates          = MM_Inv_Invitation::get_templates();
$field_types        = MM_Inv_Fields::get_supported_types();
$sample_image_base  = MM_INV_PLUGIN_URL . 'assets/images/invitation-samples/';
$initial_data       = $inv ? $inv : array(
	'id'                => 0,
	'code'              => '',
	'short_url'         => '',
	'title'             => '2026 가을 금정산 정기 산행 초대',
	'summary'           => '청명한 가을 하늘 아래 함께 걸으며 소중한 추억을 나누는 자리에 초대합니다.',
	'category_slug'     => 'hiking',
	'category_name'     => '등산',
	'template'          => 'nature',
	'status'            => 'DRAFT',
	'event_date'        => gmdate( 'Y-m-d', strtotime( '+14 days' ) ),
	'event_time'        => '09:00',
	'event_end_time'    => '15:00',
	'location_name'     => '범어사 매표소 입구 광장',
	'address'           => '부산광역시 금정구 범어사로 250',
	'cover_image_url'   => $sample_image_base . 'hiking.svg',
	'cover_attachment_id' => 0,
	'gallery_items'     => array(),
	'fields'            => array(
		array(
			'id'      => 'sample_hiking_greeting',
			'type'    => 'textarea',
			'label'   => '초대 인사말',
			'value'   => "깊어가는 가을, 만물로 산악회 정기 산행에 회원 여러분을 초대합니다.\n가벼운 발걸음으로 오셔서 함께 담소 나누어요.",
			'visible' => true,
			'order'   => 1,
		),
		array(
			'id'      => 'sample_hiking_1',
			'type'    => 'custom',
			'label'   => '산행코스',
			'value'   => '범어사 → 북문 → 고당봉 (약 4시간)',
			'visible' => true,
			'order'   => 2,
		),
		array(
			'id'      => 'sample_hiking_2',
			'type'    => 'custom',
			'label'   => '집결장소 및 시간',
			'value'   => '범어사 매표소 앞 오전 9시',
			'visible' => true,
			'order'   => 3,
		),
		array(
			'id'      => 'sample_hiking_3',
			'type'    => 'custom',
			'label'   => '준비물',
			'value'   => '등산화, 스틱, 식수 1L, 간식',
			'visible' => true,
			'order'   => 4,
		),
		array(
			'id'      => 'sample_hiking_4',
			'type'    => 'custom',
			'label'   => '회비',
			'value'   => '25,000원 (뒤풀이 식사 포함)',
			'visible' => true,
			'order'   => 5,
		),
	),
	'rsvp_enabled'      => true,
	'rsvp_deadline'     => gmdate( 'Y-m-d', strtotime( '+10 days' ) ),
	'guestbook_enabled' => true,
	'dday_enabled'      => true,
	'visibility'        => 'link_only',
	'expiration_mode'   => 'always',
	'expiration_date'   => '',
	'noindex'           => true,
);
?>
<div class="mm-inv-editor-app" id="mm-inv-editor-app">

	<!-- Top Action Bar -->
	<div class="mm-inv-editor-topbar">
		<div class="mm-inv-editor-topbar__left">
			<a href="<?php echo esc_url( home_url( '/my-invitations/' ) ); ?>" class="mm-inv-btn mm-inv-btn--outline">← 내 초대장 목록</a>
			<span class="mm-inv-editor-status-badge" id="mm-editor-status-badge">
				<?php echo esc_html( $initial_data['status'] ); ?>
			</span>
		</div>

		<!-- Mobile View Switcher Tabs (#15) -->
		<div class="mm-inv-mobile-tabs" role="tablist" aria-label="편집기 화면 전환">
			<button type="button" class="mm-inv-mobile-tab is-active" data-editor-tab="edit">[편집]</button>
			<button type="button" class="mm-inv-mobile-tab" data-editor-tab="preview">[미리보기]</button>
		</div>

		<div class="mm-inv-editor-topbar__right">
			<button type="button" class="mm-inv-btn mm-inv-btn--outline" id="mm-btn-save-draft">임시저장</button>
			<button type="button" class="mm-inv-btn mm-inv-btn--primary" id="mm-btn-publish">공개하기</button>
		</div>
	</div>

	<div class="mm-inv-editor-workspace" data-active-tab="edit">

		<!-- LEFT PANE: Invitation Editor (#15) -->
		<div class="mm-inv-editor-pane">

			<!-- Step 1: 초대장 종류(Category) 선택 (#6, #7, #50) -->
			<section class="mm-inv-editor-card">
				<div class="mm-inv-editor-card__head">
					<h2>1. 초대장 종류 선택</h2>
					<button type="button" class="mm-inv-btn mm-inv-btn--sm mm-inv-btn--outline" id="mm-btn-apply-category-presets">
						+ 선택 종류 추천 항목 추가
					</button>
				</div>
				<div class="mm-inv-category-grid" id="mm-editor-category-grid">
					<?php foreach ( $categories as $slug => $cat ) : ?>
						<button type="button"
						        class="mm-inv-category-chip <?php echo ( $slug === $initial_data['category_slug'] ) ? 'is-selected' : ''; ?>"
						        data-category-slug="<?php echo esc_attr( $slug ); ?>"
						        data-category-name="<?php echo esc_attr( $cat['name'] ); ?>">
							<span class="mm-inv-category-chip__icon"><?php echo esc_html( $cat['icon'] ); ?></span>
							<span class="mm-inv-category-chip__name"><?php echo esc_html( $cat['name'] ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
				<p class="mm-inv-editor-hint mm-inv-category-note">종류를 선택하면 제목·인사말·추천 이미지가 해당 행사에 맞게 바뀝니다. 직접 수정한 내용은 덮어쓰지 않습니다.</p>
			</section>

			<!-- Step 2: 템플릿 디자인 선택 (#11, #39, #40) -->
			<section class="mm-inv-editor-card">
				<div class="mm-inv-editor-card__head">
					<h2>2. 템플릿 디자인 선택</h2>
					<span class="mm-inv-editor-hint">디자인을 변경해도 입력하신 모든 내용은 그대로 유지됩니다 (#11)</span>
				</div>
				<div class="mm-inv-template-grid" id="mm-editor-template-grid">
						<?php foreach ( $templates as $tpl_slug => $tpl ) : ?>
							<?php
							$tpl_theme_slug       = sanitize_key( $tpl_slug );
							$tpl_background_file  = MM_INV_PLUGIN_DIR . 'templates/themes/' . $tpl_theme_slug . '/background.svg';
							$tpl_background_url   = file_exists( $tpl_background_file ) ? MM_INV_PLUGIN_URL . 'templates/themes/' . $tpl_theme_slug . '/background.svg' : '';
							?>
							<button type="button"
							        class="mm-inv-template-card <?php echo ( $tpl_slug === $initial_data['template'] ) ? 'is-selected' : ''; ?>"
							        data-template-slug="<?php echo esc_attr( $tpl_slug ); ?>">
								<span class="mm-inv-template-swatch" style="background-color:<?php echo esc_attr( $tpl['bg'] ); ?>;<?php if ( $tpl_background_url ) : ?>background-image:url('<?php echo esc_url( $tpl_background_url ); ?>');<?php endif; ?>border-color:<?php echo esc_attr( $tpl['accent'] ); ?>;">
								<span style="background:<?php echo esc_attr( $tpl['accent'] ); ?>;"></span>
							</span>
							<div class="mm-inv-template-card__info">
								<strong><?php echo esc_html( $tpl['name'] ); ?></strong>
								<span class="mm-inv-tier-badge mm-inv-tier-badge--<?php echo esc_attr( strtolower( $tpl['tier'] ) ); ?>">
									<?php echo esc_html( $tpl['tier'] ); ?>
								</span>
							</div>
							<p><?php echo esc_html( $tpl['description'] ); ?></p>
						</button>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Step 3: 기본 행사 정보 및 장소 (#5, #20, #21) -->
			<section class="mm-inv-editor-card">
				<h2>3. 기본 내용 및 장소 입력</h2>
				<div class="mm-inv-editor-fields-grid">
					<div class="mm-inv-form-field mm-inv-form-field--full">
						<label for="mm_ed_title">초대장 제목 *</label>
						<input type="text" id="mm_ed_title" value="<?php echo esc_attr( $initial_data['title'] ); ?>" placeholder="예: 2026 가을 정기 산행 초대 / 김철수·박영희 결혼식에 초대합니다" />
					</div>

					<div class="mm-inv-form-field mm-inv-form-field--full mm-inv-cover-field">
						<div class="mm-inv-cover-field__heading">
							<label>초대장 대표 이미지 <span>16:9</span></label>
							<p>선택한 종류의 예시 이미지가 표시됩니다. 이미지를 누르면 추천 이미지와 내 앨범을 둘러볼 수 있어요.</p>
						</div>
						<button type="button" class="mm-inv-cover-stage" id="mm-btn-open-cover-gallery" aria-label="초대장 이미지 갤러리 열기">
							<img id="mm_ed_cover_preview" src="<?php echo esc_url( $initial_data['cover_image_url'] ); ?>" alt="<?php echo esc_attr( $initial_data['category_name'] ); ?> 초대장 예시" />
							<span class="mm-inv-cover-stage__shade"></span>
							<span class="mm-inv-cover-stage__badge" id="mm-cover-category-badge"><?php echo esc_html( $initial_data['category_name'] ); ?> 추천 이미지</span>
							<span class="mm-inv-cover-stage__edit">🖼️ 갤러리에서 이미지 바꾸기</span>
						</button>
						<div class="mm-inv-cover-actions">
							<button type="button" class="mm-inv-btn mm-inv-btn--outline" data-trigger-cover-upload>내 이미지 올리기</button>
							<button type="button" class="mm-inv-btn mm-inv-btn--ghost" id="mm-btn-reset-cover-sample">이 종류의 예시 이미지로</button>
							<input type="file" id="mm_ed_cover_file" accept="image/*" hidden />
						</div>
						<details class="mm-inv-cover-url-details">
							<summary>이미지 주소(URL)를 직접 입력</summary>
							<input type="url" id="mm_ed_cover_url" value="<?php echo esc_attr( $initial_data['cover_image_url'] ); ?>" placeholder="https://example.com/invitation-cover.jpg" />
						</details>
					</div>

					<div class="mm-inv-form-field mm-inv-form-field--full">
						<label for="mm_ed_summary">한 줄 요약 / 부제</label>
						<textarea id="mm_ed_summary" rows="2" placeholder="초대장 상단 및 카카오톡 공유 시 표시될 소개 문구"><?php echo esc_textarea( $initial_data['summary'] ); ?></textarea>
					</div>

					<div class="mm-inv-form-field">
						<label for="mm_ed_event_date">행사 날짜</label>
						<input type="date" id="mm_ed_event_date" value="<?php echo esc_attr( $initial_data['event_date'] ); ?>" />
					</div>

					<div class="mm-inv-form-field">
						<label for="mm_ed_event_time">시작 시간 / 종료 시간</label>
						<div style="display:flex;gap:8px;">
							<input type="time" id="mm_ed_event_time" value="<?php echo esc_attr( $initial_data['event_time'] ); ?>" />
							<input type="time" id="mm_ed_event_end_time" value="<?php echo esc_attr( $initial_data['event_end_time'] ); ?>" />
						</div>
					</div>

					<div class="mm-inv-form-field">
						<label for="mm_ed_location_name">장소명 (#20)</label>
						<input type="text" id="mm_ed_location_name" value="<?php echo esc_attr( $initial_data['location_name'] ); ?>" placeholder="예: OO 컨벤션센터" />
					</div>

					<div class="mm-inv-form-field">
						<label for="mm_ed_address">주소 (네이버 지도·길찾기 연동 #21)</label>
						<input type="text" id="mm_ed_address" value="<?php echo esc_attr( $initial_data['address'] ); ?>" placeholder="예: 부산광역시 OO구 OO로 123" />
					</div>
				</div>
			</section>

			<!-- Step 4: 자유 항목 시스템 (Flexible Fields + Drag & Drop #8, #9, #10) -->
			<section class="mm-inv-editor-card">
				<div class="mm-inv-editor-card__head">
					<div>
						<h2>4. 자유 항목 관리 (수정 · 표시/숨김 · 삭제 · 순서 변경)</h2>
						<p class="mm-inv-editor-hint">☰ 핸들을 드래그하여 순서를 변경하거나 원하는 항목(카트비, 산행코스, 준비물 등)을 자유롭게 추가하세요.</p>
					</div>
				</div>

				<div class="mm-inv-flexible-list" id="mm-editor-fields-list" aria-label="초대장 항목 목록"></div>

				<div class="mm-inv-add-field-bar">
					<select id="mm-add-field-type" class="mm-inv-select">
						<?php foreach ( $field_types as $type_slug => $meta ) : ?>
							<option value="<?php echo esc_attr( $type_slug ); ?>">
								<?php echo esc_html( $meta['icon'] . ' ' . $meta['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="text" id="mm-add-field-label" placeholder="항목명 (예: 카트비, 산행코스, 준비물)" class="mm-inv-input" />
					<button type="button" class="mm-inv-btn mm-inv-btn--primary" id="mm-btn-add-field">[ + 항목 추가 ]</button>
				</div>
			</section>

			<!-- Step 5: 사진앨범 최대 10장 (#13, #14) -->
			<section class="mm-inv-editor-card">
				<h2>5. 사진앨범 (최대 10장)</h2>

				<div class="mm-inv-media-block">
					<div class="mm-inv-editor-card__head">
						<label class="mm-inv-media-label">사진앨범 (<span id="mm-gallery-counter">0</span> / 10) (#13)</label>
						<div style="display:flex;gap:8px;">
							<input type="url" id="mm_ed_gallery_url_input" placeholder="사진 URL 직접 추가" class="mm-inv-input-sm" />
							<button type="button" class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm" id="mm-btn-add-gallery-url">+ URL 추가</button>
							<label class="mm-inv-btn mm-inv-btn--primary mm-inv-btn--sm mm-inv-upload-btn">
								[+ 사진 추가]
								<input type="file" id="mm_ed_gallery_file" accept="image/*" multiple hidden />
							</label>
						</div>
					</div>
					<div class="mm-inv-editor-gallery-grid" id="mm-editor-gallery-grid"></div>
				</div>
			</section>

			<!-- Step 6: RSVP · 방명록 · D-Day · 공개범위 · 공개기간 · NOINDEX 설정 (#23~#34) -->
			<section class="mm-inv-editor-card">
				<h2>6. 참석응답(RSVP) · 방명록 · 공개 정책 설정</h2>

				<div class="mm-inv-editor-fields-grid">
					<div class="mm-inv-form-field">
						<label class="mm-inv-toggle-row">
							<input type="checkbox" id="mm_ed_rsvp_enabled" <?php checked( ! empty( $initial_data['rsvp_enabled'] ) ); ?> />
							<span><strong>참석 여부 (RSVP) 사용</strong> (#23)</span>
						</label>
					</div>

					<div class="mm-inv-form-field">
						<label for="mm_ed_rsvp_deadline">RSVP 응답 마감일 (#24)</label>
						<input type="date" id="mm_ed_rsvp_deadline" value="<?php echo esc_attr( $initial_data['rsvp_deadline'] ); ?>" />
					</div>

					<div class="mm-inv-form-field">
						<label class="mm-inv-toggle-row">
							<input type="checkbox" id="mm_ed_guestbook_enabled" <?php checked( ! empty( $initial_data['guestbook_enabled'] ) ); ?> />
							<span><strong>방명록 사용</strong> (#28)</span>
						</label>
					</div>

					<div class="mm-inv-form-field">
						<label class="mm-inv-toggle-row">
							<input type="checkbox" id="mm_ed_dday_enabled" <?php checked( ! empty( $initial_data['dday_enabled'] ) ); ?> />
							<span><strong>D-Day 표시</strong> (D-15 / D-DAY / 종료 안내 #30)</span>
						</label>
					</div>

					<div class="mm-inv-form-field">
						<label for="mm_ed_visibility">공개 범위 (#32)</label>
						<select id="mm_ed_visibility" class="mm-inv-select">
							<option value="link_only" <?php selected( $initial_data['visibility'], 'link_only' ); ?>>링크를 아는 사람만 (기본 권장)</option>
							<option value="password" <?php selected( $initial_data['visibility'], 'password' ); ?>>비밀번호 보호</option>
							<option value="private" <?php selected( $initial_data['visibility'], 'private' ); ?>>비공개 (나만 보기)</option>
						</select>
					</div>

					<div class="mm-inv-form-field" id="mm_ed_password_wrap" style="<?php echo ( 'password' === $initial_data['visibility'] ) ? '' : 'display:none;'; ?>">
						<label for="mm_ed_password">열람 비밀번호 설정 (#32)</label>
						<input type="password" id="mm_ed_password" placeholder="방문자 열람용 비밀번호 입력" autocomplete="new-password" />
					</div>

					<div class="mm-inv-form-field">
						<label for="mm_ed_expiration_mode">초대장 공개기간 (#34)</label>
						<select id="mm_ed_expiration_mode" class="mm-inv-select">
							<option value="always" <?php selected( $initial_data['expiration_mode'], 'always' ); ?>>계속 공개</option>
							<option value="after_30" <?php selected( $initial_data['expiration_mode'], 'after_30' ); ?>>행사 후 30일</option>
							<option value="after_90" <?php selected( $initial_data['expiration_mode'], 'after_90' ); ?>>행사 후 90일</option>
							<option value="custom" <?php selected( $initial_data['expiration_mode'], 'custom' ); ?>>직접 설정</option>
						</select>
					</div>

					<div class="mm-inv-form-field" id="mm_ed_expiration_date_wrap" style="<?php echo ( 'custom' === $initial_data['expiration_mode'] ) ? '' : 'display:none;'; ?>">
						<label for="mm_ed_expiration_date">공개 종료일 (#34)</label>
						<input type="date" id="mm_ed_expiration_date" value="<?php echo esc_attr( $initial_data['expiration_date'] ); ?>" />
					</div>

					<div class="mm-inv-form-field mm-inv-form-field--full">
						<label class="mm-inv-toggle-row">
							<input type="checkbox" id="mm_ed_noindex" <?php checked( ! empty( $initial_data['noindex'] ) ); ?> />
							<span><strong>검색엔진 노출 차단 (NOINDEX 기본 권장)</strong> — 개인정보 보호를 위해 검색 로봇 수집을 차단합니다 (#33)</span>
						</label>
					</div>
				</div>
			</section>

			<!-- Published Share & QR Box (#17, #18, #19, #38) -->
			<section class="mm-inv-editor-card mm-inv-publish-result" id="mm-editor-share-panel" <?php echo empty( $initial_data['short_url'] ) ? 'hidden' : ''; ?>>
				<h2>🎉 고유 초대장 URL · QR · 인쇄</h2>
				<div class="mm-inv-short-url-bar">
					<code id="mm-editor-short-url"><?php echo esc_html( $initial_data['short_url'] ); ?></code>
					<button type="button" class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm" id="mm-editor-copy-url">링크 복사</button>
					<a href="<?php echo esc_url( $initial_data['short_url'] ); ?>" target="_blank" id="mm-editor-open-url" class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm">새 창으로 열기 ↗</a>
					<a href="<?php echo esc_url( MM_Inv_Print::get_print_url( $initial_data['short_url'], 'a4' ) ); ?>" target="_blank" id="mm-editor-print-url" class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm">🖨️ 인쇄 / PDF</a>
				</div>
				<div id="mm-editor-qr-container" class="mm-inv-qr-box" data-qr-url="<?php echo esc_attr( $initial_data['short_url'] ); ?>" data-qr-size="140">
					<div class="mm-inv-qr-canvas"></div>
				</div>
			</section>
		</div>

		<!-- RIGHT PANE: Real-Time Mobile Preview (#15) -->
		<aside class="mm-inv-preview-pane" aria-label="모바일 실시간 미리보기">
			<div class="mm-inv-phone-mockup">
				<div class="mm-inv-phone-mockup__notch">
					<span>모바일 실시간 미리보기</span>
				</div>
				<div class="mm-inv-phone-mockup__screen" id="mm-live-preview-screen">
					<!-- Populated dynamically in real time by `assets/js/editor.js` -->
				</div>
			</div>
		</aside>

		</div>

		<div class="mm-inv-cover-picker" id="mm-cover-gallery-modal" hidden>
			<button type="button" class="mm-inv-cover-picker__backdrop" data-cover-picker-close aria-label="이미지 갤러리 닫기"></button>
			<section class="mm-inv-cover-picker__dialog" role="dialog" aria-modal="true" aria-labelledby="mm-cover-picker-title" tabindex="-1">
				<header class="mm-inv-cover-picker__header">
					<div>
						<span class="mm-inv-badge">16:9 COVER GALLERY</span>
						<h2 id="mm-cover-picker-title">초대장 이미지 갤러리</h2>
						<p>종류별 예시를 고르거나, 내가 올린 사진앨범에서 대표 이미지를 선택하세요.</p>
					</div>
					<button type="button" class="mm-inv-cover-picker__close" data-cover-picker-close aria-label="닫기">&times;</button>
				</header>
				<div class="mm-inv-cover-picker__body">
					<section class="mm-inv-cover-picker__section">
						<div class="mm-inv-cover-picker__section-head">
							<div><h3>종류별 추천 이미지</h3><p>현재 선택한 초대장 종류와 어울리는 이미지입니다.</p></div>
						</div>
						<div class="mm-inv-cover-gallery-grid" id="mm-cover-sample-grid"></div>
					</section>
					<section class="mm-inv-cover-picker__section">
						<div class="mm-inv-cover-picker__section-head">
							<div><h3>내 사진앨범</h3><p>이 초대장에 추가한 사진을 대표 이미지로 사용할 수 있어요.</p></div>
							<button type="button" class="mm-inv-btn mm-inv-btn--outline mm-inv-btn--sm" data-trigger-cover-upload>새 사진 올리기</button>
						</div>
						<div class="mm-inv-cover-gallery-grid" id="mm-cover-user-grid"></div>
						<p class="mm-inv-cover-picker__empty" id="mm-cover-user-empty" hidden>아직 내 앨범에 사진이 없습니다. 새 사진을 올리면 여기에서 대표 이미지로 선택할 수 있어요.</p>
					</section>
				</div>
				<footer class="mm-inv-cover-picker__footer">
					<button type="button" class="mm-inv-btn mm-inv-btn--outline" data-trigger-cover-upload>📤 내 컴퓨터에서 이미지 업로드</button>
					<button type="button" class="mm-inv-btn mm-inv-btn--ghost" data-cover-picker-close>닫기</button>
				</footer>
			</section>
		</div>
	</div>

	<script>
		window.mmInvEditorConfig = {
		ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		nonce: <?php echo wp_json_encode( wp_create_nonce( 'mm_inv_editor_nonce' ) ); ?>,
		homeUrl: <?php echo wp_json_encode( home_url( '/' ) ); ?>,
		sampleImageBase: <?php echo wp_json_encode( $sample_image_base ); ?>,
		categories: <?php echo wp_json_encode( $categories ); ?>,
		templates: <?php echo wp_json_encode( $templates ); ?>,
		fieldTypes: <?php echo wp_json_encode( $field_types ); ?>,
		initialInvitation: <?php echo wp_json_encode( $initial_data ); ?>
	};
</script>
