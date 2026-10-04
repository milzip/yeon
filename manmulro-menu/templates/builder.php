<?php
/**
 * 5-Step Menu Builder Template (`templates/builder.php`)
 *
 * Implements Sections #3 ~ #17:
 * - STEP 1: 시작 방법 선택 ([사진으로 시작하기] / [직접 만들기])
 * - STEP 2: 메뉴 구성 (OCR 원본 비교 화면 + 공통 메뉴 관리 + 일괄 관리 + [원본 메뉴판 보기])
 * - STEP 3: 메뉴 상세정보 (상세 설명, 재료, 맛 특징 ●●●○○, 추천 대상, 알레르기, 원산지, 확장 옵션)
 * - STEP 4: 메뉴판 디자인 (8종 템플릿 + 색상/글꼴/가격 스타일 + 실시간 미리보기)
 * - STEP 5: 완성 및 배포 (QR 모바일 메뉴판, 메뉴 상세페이지 URL, A4/A3 인쇄용 PDF/이미지 메뉴판)
 *
 * @package Manmulro_Menu
 * @var array|null $project Loaded project data or null.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$business_types = MM_Menu_Projects_Module::get_business_types();
$templates      = MM_Menu_Design_Module::get_templates();
$flavor_types   = MM_Menu_Detail_Module::get_flavor_types();
$allergen_opts  = MM_Menu_Detail_Module::get_allergen_options();
$default_img    = MM_MENU_PLUGIN_URL . 'assets/images/sample-source-menu.svg';
?>
<div class="mm-menu-builder-wrap"
     id="mm-menu-builder-app"
     data-project="<?php echo esc_attr( wp_json_encode( $project ) ); ?>"
     data-default-source-img="<?php echo esc_attr( $default_img ); ?>">

	<!-- Top Step Progress Bar (#3) + Auto-Save Status (#15) -->
	<header class="mm-menu-stepper">
		<div class="mm-menu-stepper__brand">
			<strong>만물로 메뉴판 만들기</strong>
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
			<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-open-source-modal">
				🖼️ [원본 메뉴판 보기]
			</button>
			<button type="button" class="mm-menu-btn mm-menu-btn--primary" id="mm-btn-save-project">
				💾 저장하기
			</button>
		</div>
	</header>

	<!-- ===================================================================
	     STEP 1: 시작 방법 선택 (#4)
	     =================================================================== -->
	<section class="mm-step-panel is-active" data-step-panel="1">
		<div class="mm-start-hero">
			<h2>어떻게 메뉴판을 시작하시겠어요?</h2>
			<p>"메뉴는 한 번만 입력하세요." 기존 메뉴판이 있으면 사진으로, 없으면 직접 만들어보세요.</p>

			<div class="mm-start-setup-bar">
				<label>
					<span>상호명</span>
					<input type="text" id="mm-init-business-name" value="<?php echo esc_attr( $project ? $project['business_name'] : '만물로 한식당' ); ?>" placeholder="예: 만물로 한식당" />
				</label>
				<label>
					<span>업종 선택 (#6.1)</span>
					<select id="mm-init-business-type">
						<?php foreach ( $business_types as $business_type_key => $business_type_data ) : ?>
							<option value="<?php echo esc_attr( $business_type_key ); ?>" <?php selected( $project ? $project['business_type'] : '음식점', $business_type_key ); ?>>
								<?php echo esc_html( $business_type_data['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>

			<div class="mm-start-cards">
				<article class="mm-start-card">
					<div class="mm-start-card__icon">📸</div>
					<h3>[사진으로 시작하기]</h3>
					<p>기존 종이/벽 메뉴판 사진을 촬영하거나 업로드하면 OCR로 메뉴명과 가격을 추출하여 원본과 나란히 비교·확인할 수 있습니다.</p>
					<button type="button" class="mm-menu-btn mm-menu-btn--primary" id="mm-btn-start-ocr">
						사진으로 시작하기 (OCR 비교)
					</button>
				</article>

				<article class="mm-start-card">
					<div class="mm-start-card__icon">✍️</div>
					<h3>[직접 만들기]</h3>
					<p>업종별 기본 카테고리(식사·사이드·음료·주류)로 바로 시작하여 메뉴명과 가격을 빠르게 입력합니다.</p>
					<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-start-direct">
						직접 만들기
					</button>
				</article>
			</div>
		</div>
	</section>

	<!-- ===================================================================
	     STEP 2: 메뉴 구성 — OCR 원본 비교 (#5, #11) + 직접 메뉴 관리 (#6, #7, #16)
	     =================================================================== -->
	<section class="mm-step-panel" data-step-panel="2">

		<!-- Mode Switcher Bar (#2.4 Seamless transition between OCR & Direct Input) -->
		<div class="mm-step2-toolbar">
			<div class="mm-step2-toolbar__left">
				<button type="button" class="mm-submode-btn" id="mm-btn-toggle-ocr-panel">
					📷 기존 메뉴판에서 가져오기 (OCR 원본 비교)
				</button>
				<button type="button" class="mm-submode-btn is-active" id="mm-btn-show-menu-manager">
					📋 공통 메뉴 목록 관리
				</button>
			</div>
			<div class="mm-step2-toolbar__right">
				<button type="button" class="mm-menu-btn mm-menu-btn--primary" id="mm-btn-add-menu-item">
					+ 메뉴 추가
				</button>
				<button type="button" class="mm-menu-btn mm-menu-btn--outline" data-goto-step="3">
					다음: 메뉴 상세정보 (STEP 3) →
				</button>
			</div>
		</div>

		<!-- 2A. OCR Split Verification Panel (#5.1 ~ #5.9, #11) -->
		<div class="mm-ocr-split-panel" id="mm-ocr-split-panel" hidden>
			<div class="mm-ocr-split-header">
				<div>
					<h3>원본 메뉴판 비교 & OCR 결과 검증 (#5.5 ~ #5.9)</h3>
					<p>원본 메뉴판의 파란 박스를 클릭하면 오른쪽 메뉴가 강조되고, 오른쪽 메뉴를 클릭하면 원본 위치가 표시됩니다. 확인 후 <strong>[확인하고 메뉴로 가져오기]</strong>를 눌러주세요.</p>
				</div>
				<div class="mm-ocr-preprocess-controls">
					<label class="mm-menu-btn mm-menu-btn--outline">
						📂 메뉴판 사진 업로드 (JPG/PNG/WEBP)
						<input type="file" id="mm-ocr-file-input" accept=".jpg,.jpeg,.png,.webp,image/*" multiple hidden />
					</label>
					<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-ocr-rotate">↻ 회전</button>
					<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-ocr-zoom-in">＋ 확대</button>
					<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-ocr-contrast">◐ 대비 보정</button>
					<button type="button" class="mm-menu-btn mm-menu-btn--primary" id="mm-btn-run-ocr">🔍 OCR 메뉴 추출 실행</button>
				</div>
			</div>

			<div class="mm-ocr-split-grid">
				<!-- Left: Original Menu Image (`SOURCE_IMAGE`) + Bounding Boxes (#5.5, #5.6) -->
				<div class="mm-ocr-source-pane">
					<div class="mm-ocr-canvas-wrap" id="mm-ocr-canvas-wrap">
						<img src="<?php echo esc_url( $default_img ); ?>" alt="원본 메뉴판 (SOURCE_IMAGE)" id="mm-ocr-source-img" />
						<div class="mm-ocr-bbox-layer" id="mm-ocr-bbox-layer"></div>
					</div>
				</div>

				<!-- Right: Recognized OCR Candidates List (#5.7, #5.8, #5.9) -->
				<div class="mm-ocr-results-pane">
					<div class="mm-ocr-results-toolbar">
						<span>인식된 후보 항목 (확률 85% 미만은 <strong>"확인 필요"</strong> 표시)</span>
						<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" id="mm-btn-add-ocr-row">+ 행 추가</button>
					</div>

					<div class="mm-ocr-rows-list" id="mm-ocr-rows-list"></div>

					<div class="mm-ocr-confirm-footer">
						<button type="button" class="mm-menu-btn mm-menu-btn--primary mm-menu-btn--full" id="mm-btn-confirm-ocr-import">
							✅ [확인하고 메뉴로 가져오기] (#5.9)
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- 2B. Unified Common Menu Manager (#6, #7, #16) -->
		<div class="mm-common-menu-manager" id="mm-common-menu-manager">
			<!-- Category Management Bar (#6.2, #7.1) -->
			<div class="mm-category-bar">
				<div class="mm-category-tabs" id="mm-builder-category-tabs"></div>
				<div class="mm-category-add">
					<input type="text" id="mm-new-category-name" placeholder="새 카테고리명 (예: 시즌특선)" />
					<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" id="mm-btn-add-category">+ 카테고리 추가</button>
				</div>
			</div>

			<!-- Bulk Management Bar (#16) -->
			<div class="mm-bulk-bar" id="mm-bulk-bar">
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
					<span>카테고리 이동 (#16.3):</span>
					<select id="mm-bulk-target-category">
						<option value="">카테고리 선택...</option>
					</select>
				</div>
			</div>

			<!-- Menu Items Table / Drag & Drop Cards (#7.2 ~ #7.8) -->
			<div class="mm-builder-items-list" id="mm-builder-items-list"></div>
		</div>
	</section>

	<!-- ===================================================================
	     STEP 3: 메뉴 상세정보 (#8 — 필요한 메뉴에만 선택 입력)
	     =================================================================== -->
	<section class="mm-step-panel" data-step-panel="3">
		<div class="mm-step3-layout">
			<aside class="mm-step3-sidebar">
				<h3>상세정보를 입력할 메뉴 선택</h3>
				<p class="mm-muted">필요한 메뉴에만 선택적으로 상세 설명·재료·맛 특징·알레르기·원산지를 입력하세요.</p>
				<div class="mm-step3-item-picker" id="mm-step3-item-picker"></div>
			</aside>

			<div class="mm-step3-editor" id="mm-step3-editor">
				<div class="mm-step3-header">
					<h3 id="mm-detail-editing-title">메뉴를 선택해주세요</h3>
					<a href="#" target="_blank" id="mm-detail-preview-link" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm">
						🔗 개별 상세페이지 미리보기
					</a>
				</div>

				<input type="hidden" id="mm-detail-menu-id" value="0" />

				<div class="mm-form-group">
					<label>상세 설명 — 이 음식은 어떤 음식인가요? (#8.1)</label>
					<textarea id="mm-detail-description" rows="3" placeholder="예: 직접 담근 숙성 김치와 국내산 한돈 삼겹살을 넣고 깊게 끓여낸 대표 식사 메뉴입니다."></textarea>
				</div>

				<div class="mm-form-group">
					<label>주요 재료 및 재료 설명 (#8.2, #8.3)</label>
					<div id="mm-detail-ingredients-rows"></div>
					<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" id="mm-btn-add-ingredient">+ 주요 재료 추가</button>
				</div>

				<div class="mm-form-group">
					<label>맛 특징 (0~5점, 고객 화면에 ●●●○○ 형태로 표시 #8.4)</label>
					<div class="mm-flavor-grid">
						<?php foreach ( $flavor_types as $f_type ) : ?>
							<label class="mm-flavor-control">
								<span><?php echo esc_html( $f_type ); ?></span>
								<input type="range" min="0" max="5" value="0" class="mm-flavor-slider" data-flavor="<?php echo esc_attr( $f_type ); ?>" />
								<output class="mm-flavor-output">○○○○○</output>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="mm-form-group">
					<label>추천 대상 (#8.5)</label>
					<input type="text" id="mm-detail-recommended" placeholder="예: 얼큰한 국물을 좋아하시는 분 / 든든한 점심 식사를 찾으시는 분" />
				</div>

				<div class="mm-form-group">
					<label>알레르기 정보 (체크한 항목만 표시되며 추측하지 않습니다 #8.6, #28.7)</label>
					<div class="mm-allergen-checks">
						<?php foreach ( $allergen_opts as $alg ) : ?>
							<label class="mm-check-pill">
								<input type="checkbox" class="mm-allergen-cb" value="<?php echo esc_attr( $alg ); ?>" />
								<span><?php echo esc_html( $alg ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="mm-form-group">
					<label>원산지 정보 (직접 입력한 정보만 표시됩니다 #8.7, #28.7)</label>
					<div id="mm-detail-origins-rows"></div>
					<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" id="mm-btn-add-origin">+ 원산지 항목 추가</button>
				</div>

				<div class="mm-step3-save-bar">
					<button type="button" class="mm-menu-btn mm-menu-btn--primary" id="mm-btn-save-item-detail">
						💾 이 메뉴의 상세정보 저장
					</button>
					<button type="button" class="mm-menu-btn mm-menu-btn--outline" data-goto-step="4">
						다음: 메뉴판 디자인 (STEP 4) →
					</button>
				</div>
			</div>
		</div>
	</section>

	<!-- ===================================================================
	     STEP 4: 메뉴판 디자인 (#12 — 8종 템플릿 & 실시간 미리보기)
	     =================================================================== -->
	<section class="mm-step-panel" data-step-panel="4">
		<div class="mm-step4-layout">
			<div class="mm-step4-controls">
				<h3>기본 템플릿 선택 (8종 #12.1)</h3>
				<div class="mm-template-grid">
					<?php foreach ( $templates as $tpl_slug => $tpl ) : ?>
						<button type="button"
						        class="mm-template-card"
						        data-template="<?php echo esc_attr( $tpl_slug ); ?>"
						        data-primary="<?php echo esc_attr( $tpl['primary_color'] ); ?>"
						        data-bg="<?php echo esc_attr( $tpl['bg_color'] ); ?>"
						        data-text="<?php echo esc_attr( $tpl['text_color'] ); ?>">
							<span class="mm-template-swatch" style="background:<?php echo esc_attr( $tpl['bg_color'] ); ?>;border-color:<?php echo esc_attr( $tpl['primary_color'] ); ?>;color:<?php echo esc_attr( $tpl['primary_color'] ); ?>;">Aa</span>
							<strong><?php echo esc_html( $tpl['name'] ); ?></strong>
							<small><?php echo esc_html( $tpl['description'] ); ?></small>
						</button>
					<?php endforeach; ?>
				</div>

				<h3>세부 디자인 설정 (#12.2)</h3>
				<div class="mm-design-options-grid">
					<label>
						<span>대표 색상</span>
						<input type="color" id="mm-design-primary-color" value="#9a3412" />
					</label>
					<label>
						<span>배경 색상</span>
						<input type="color" id="mm-design-bg-color" value="#fffbeb" />
					</label>
					<label>
						<span>글자 색상</span>
						<input type="color" id="mm-design-text-color" value="#1c1917" />
					</label>
					<label>
						<span>가격 표시 스타일</span>
						<select id="mm-design-price-style">
							<option value="won">9,000원 (기본)</option>
							<option value="comma">₩9,000</option>
							<option value="dots">···· 9,000원</option>
						</select>
					</label>
					<label class="mm-design-toggle">
						<input type="checkbox" id="mm-design-show-images" checked />
						<span>메뉴 사진 표시 (`MENU_IMAGE` #10)</span>
					</label>
				</div>

				<div style="margin-top:20px;">
					<button type="button" class="mm-menu-btn mm-menu-btn--primary" data-goto-step="5">
						다음: 완성 및 배포 (STEP 5) →
					</button>
				</div>
			</div>

			<!-- Real-Time Mobile Menu Preview (#12.3) -->
			<div class="mm-step4-preview">
				<div class="mm-phone-mockup">
					<div class="mm-phone-mockup__notch">실시간 모바일 메뉴판 미리보기 (#12.3)</div>
					<div class="mm-phone-mockup__screen" id="mm-live-design-preview"></div>
				</div>
			</div>
		</div>
	</section>

	<!-- ===================================================================
	     STEP 5: 완성 및 배포 (#13 QR 모바일 메뉴판, #9 상세페이지, #14 인쇄용 메뉴판)
	     =================================================================== -->
	<section class="mm-step-panel" data-step-panel="5">
		<div class="mm-publish-grid">
			<article class="mm-publish-card">
				<span class="mm-publish-badge">1. 모바일 QR 메뉴판 (#13)</span>
				<h3>QR 코드 및 전용 메뉴판 주소</h3>
				<p>메뉴나 가격을 수정해도 QR 코드와 메뉴판 주소는 절대 변경되지 않습니다 (#13.1, #28.5).</p>
				<div class="mm-publish-url-box">
					<input type="text" id="mm-publish-public-url" readonly value="" />
					<button type="button" class="mm-menu-btn mm-menu-btn--outline" id="mm-btn-copy-public-url">주소 복사</button>
					<a href="#" target="_blank" class="mm-menu-btn mm-menu-btn--primary" id="mm-link-open-public-url">모바일 메뉴판 열기 ↗</a>
				</div>
				<div id="mm-publish-qr-holder" class="mm-publish-qr-holder"></div>
			</article>

			<article class="mm-publish-card">
				<span class="mm-publish-badge">2. 인쇄용 메뉴판 & PDF/이미지 다운로드 (#14)</span>
				<h3>A4 / A3 세로·가로 인쇄 메뉴판</h3>
				<p>동일한 공통 메뉴 데이터를 사용해 매장 비치용 인쇄 메뉴판(PDF / JPG / PNG)을 즉시 생성합니다.</p>
				<div class="mm-print-links-grid" id="mm-print-links-grid">
					<a href="#" target="_blank" class="mm-menu-btn mm-menu-btn--primary" data-print-paper="a4" data-print-orient="portrait">🖨️ A4 세로 인쇄/PDF</a>
					<a href="#" target="_blank" class="mm-menu-btn mm-menu-btn--outline" data-print-paper="a4" data-print-orient="landscape">🖨️ A4 가로 인쇄/PDF</a>
					<a href="#" target="_blank" class="mm-menu-btn mm-menu-btn--outline" data-print-paper="a3" data-print-orient="portrait">🖨️ A3 세로 인쇄/PDF</a>
					<a href="#" target="_blank" class="mm-menu-btn mm-menu-btn--outline" data-print-paper="a3" data-print-orient="landscape">🖨️ A3 가로 인쇄/PDF</a>
				</div>
			</article>

			<article class="mm-publish-card">
				<span class="mm-publish-badge">3. 개별 메뉴 상세페이지 고유 URL (#9.1, #9.2)</span>
				<h3>메뉴별 상세페이지 바로가기 & 공유</h3>
				<p>각 메뉴는 <code>/menu/{store}/{menu-item}</code> 형식의 고유 주소를 가지며, SNS나 메신저로 직접 공유할 수 있습니다.</p>
				<ul class="mm-publish-item-urls" id="mm-publish-item-urls"></ul>
			</article>
		</div>
	</section>

	<!-- Modal: [원본 메뉴판 보기] & [원본에서 보기] (#11) -->
	<div class="mm-source-modal" id="mm-source-modal" hidden>
		<div class="mm-source-modal__backdrop" id="mm-source-modal-close"></div>
		<div class="mm-source-modal__dialog">
			<div class="mm-source-modal__header">
				<h3>원본 메뉴판 보기 (`SOURCE_IMAGE` 보관 및 위치 확인 #11)</h3>
				<button type="button" class="mm-menu-btn mm-menu-btn--outline mm-menu-btn--sm" id="mm-source-modal-close-btn">닫기 ✕</button>
			</div>
			<div class="mm-source-modal__body">
				<div class="mm-ocr-canvas-wrap">
					<img src="<?php echo esc_url( $default_img ); ?>" alt="원본 메뉴판" id="mm-modal-source-img" />
					<div class="mm-ocr-bbox-layer" id="mm-modal-bbox-layer"></div>
				</div>
			</div>
		</div>
	</div>

</div>
