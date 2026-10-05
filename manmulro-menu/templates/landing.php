<?php
/**
 * Public Manmulro Menu Builder landing page (`[manmulro_menu_builder]`, logged-out view).
 *
 * The WordPress theme continues to render the site's existing logo, navigation, and footer.
 *
 * @package Manmulro_Menu
 * @var string $login_url WordPress/social-login URL returning to the builder.
 * @var array  $templates Registered menu design templates.
 * @var string $default_img Source-menu sample image URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$landing_features = array(
	array(
		'icon'        => '📷',
		'eyebrow'     => 'PHOTO TO MENU',
		'title'       => '사진으로 메뉴 가져오기',
		'description' => '종이·벽 메뉴판 사진에서 메뉴명과 가격을 읽어 후보로 정리합니다. 원본을 보며 확인하고 필요한 부분을 바로 고칠 수 있습니다.',
	),
	array(
		'icon'        => '✍️',
		'eyebrow'     => 'ONE MENU SOURCE',
		'title'       => '직접 입력도 한곳에서',
		'description' => '사진 인식과 직접 입력이 같은 메뉴 관리 화면으로 이어집니다. 카테고리와 메뉴 정보를 매장에 맞게 정리하세요.',
	),
	array(
		'icon'        => '🎨',
		'eyebrow'     => 'DESIGN TEMPLATES',
		'title'       => '가게에 어울리는 디자인',
		'description' => '한식·카페·베이커리·주점 등 업종에 맞춘 8종의 기본 디자인과 색상·글꼴 설정으로 분위기를 다듬습니다.',
	),
	array(
		'icon'        => '📱',
		'eyebrow'     => 'QR MENU',
		'title'       => '모바일 메뉴판으로 공유',
		'description' => '완성한 메뉴를 모바일 메뉴판과 전용 주소로 안내하고, 테이블에 둘 QR 메뉴판으로 연결할 수 있습니다.',
	),
	array(
		'icon'        => '🖨️',
		'eyebrow'     => 'PRINT READY',
		'title'       => '인쇄용 메뉴판까지',
		'description' => '동일한 메뉴 데이터로 A4·A3 세로 또는 가로 인쇄용 메뉴판을 준비합니다.',
	),
	array(
		'icon'        => 'ℹ️',
		'eyebrow'     => 'MENU DETAILS',
		'title'       => '메뉴 정보를 더 자세히',
		'description' => '설명과 사진, 원산지·알레르기·맛 특징 등 손님에게 필요한 정보를 메뉴별로 정리할 수 있습니다.',
	),
);

$landing_steps = array(
	array(
		'number'      => '01',
		'title'       => '사진 또는 직접 입력으로 시작',
		'description' => '기존 메뉴판 사진을 불러오거나, 새 메뉴를 직접 입력해 시작합니다.',
		'icon'        => '📸',
	),
	array(
		'number'      => '02',
		'title'       => '메뉴와 가격을 확인하고 정리',
		'description' => 'OCR 후보를 원본과 비교해 수정하고, 메뉴·카테고리 정보를 정돈합니다.',
		'icon'        => '✓',
	),
	array(
		'number'      => '03',
		'title'       => '디자인을 고르고 배포',
		'description' => '매장에 어울리는 디자인을 선택해 QR 모바일 메뉴판과 인쇄용 파일을 준비합니다.',
		'icon'        => '↗',
	),
);

$landing_faqs = array(
	array(
		'question' => '사진을 올리면 메뉴가 바로 공개되나요?',
		'answer'   => '아니요. 인식된 메뉴와 가격은 확인용 후보로 표시됩니다. 원본 메뉴판과 비교해 수정한 뒤 직접 확인하고 메뉴로 가져올 수 있습니다.',
	),
	array(
		'question' => '사진 인식 없이 직접 입력해도 되나요?',
		'answer'   => '네. 사진으로 시작하거나 직접 만들기를 선택할 수 있고, 두 방식 모두 같은 메뉴 관리 화면에서 이어서 편집할 수 있습니다.',
	),
	array(
		'question' => '완성한 메뉴를 어떤 방식으로 사용할 수 있나요?',
		'answer'   => '모바일 QR 메뉴판과 전용 메뉴 주소로 공유하고, A4·A3 크기의 인쇄용 메뉴판도 만들 수 있습니다.',
	),
	array(
		'question' => '메뉴나 가격을 수정하면 QR 코드도 바뀌나요?',
		'answer'   => '메뉴나 가격을 수정해도 메뉴판의 기존 QR 코드와 전용 주소는 유지되도록 설계되어 있습니다.',
	),
);

$preview_templates = array_slice( $templates, 0, 4, true );
?>
<main class="mm-menu-landing" id="main">
	<section class="mm-menu-landing__hero" aria-labelledby="mm-menu-landing-title">
		<div class="mm-menu-landing__container mm-menu-landing__hero-grid">
			<div class="mm-menu-landing__hero-copy">
				<p class="mm-menu-landing__eyebrow"><span aria-hidden="true">✳</span> AI 사진 OCR <i></i> 만물로 메뉴판 만들기</p>
				<h1 id="mm-menu-landing-title">손글씨 사진 한 장,<br /><span>우리 가게 메뉴판으로.</span></h1>
				<p class="mm-menu-landing__lead">기존 메뉴판 사진에서 메뉴와 가격을 불러오거나 직접 입력하세요. 내용을 확인해 정리한 뒤, QR 모바일 메뉴판과 인쇄용 메뉴판까지 한 번에 준비할 수 있습니다.</p>
				<div class="mm-menu-landing__actions">
					<a class="mm-menu-landing__button mm-menu-landing__button--primary" href="<?php echo esc_url( $login_url ); ?>">메뉴판 만들기 시작 <span aria-hidden="true">↗</span></a>
					<a class="mm-menu-landing__button mm-menu-landing__button--quiet" href="#mm-menu-landing-templates">템플릿 둘러보기 <span aria-hidden="true">↓</span></a>
				</div>
				<p class="mm-menu-landing__microcopy"><span aria-hidden="true">↳</span> 로그인 후 5단계 편집기에서 이어서 만들 수 있어요.</p>
				<div class="mm-menu-landing__hero-points" aria-label="주요 기능">
					<span><b>01</b> 사진 또는 직접 입력</span>
					<span><b>02</b> QR·인쇄용 결과</span>
				</div>
			</div>

			<div class="mm-menu-landing__hero-art" aria-label="사진에서 메뉴판으로 이어지는 작업 예시">
				<div class="mm-menu-landing__art-orbit mm-menu-landing__art-orbit--one"></div>
				<div class="mm-menu-landing__art-orbit mm-menu-landing__art-orbit--two"></div>
				<div class="mm-menu-landing__source-card">
					<div class="mm-menu-landing__source-head"><span><i></i> 원본 메뉴판</span><small>사진 업로드</small></div>
					<img src="<?php echo esc_url( $default_img ); ?>" alt="메뉴판 사진 예시" />
					<div class="mm-menu-landing__source-caption"><span>PHOTO INPUT</span><b>사진으로 시작하기</b></div>
				</div>
				<div class="mm-menu-landing__art-arrow" aria-hidden="true">↗</div>
				<article class="mm-menu-landing__result-card">
					<header><span class="mm-menu-landing__result-brand">MANMULRO <b>MENU</b></span><span class="mm-menu-landing__result-state"><i></i> 편집 중</span></header>
					<div class="mm-menu-landing__result-title"><small>오늘의 메뉴</small><h2>만물로 한식당</h2></div>
					<div class="mm-menu-landing__result-category">식사 <span>3 ITEMS</span></div>
					<div class="mm-menu-landing__result-row"><span>김치찌개</span><b>9,000원</b></div>
					<div class="mm-menu-landing__result-row"><span>차돌박이 된장찌개</span><b>9,500원</b></div>
					<div class="mm-menu-landing__result-row"><span>제육볶음 정식</span><b>11,000원</b></div>
					<footer><span>QR 모바일 메뉴</span><span>A4 · A3 인쇄</span></footer>
				</article>
				<div class="mm-menu-landing__floating-note mm-menu-landing__floating-note--ocr"><span aria-hidden="true">✓</span><div><b>OCR 후보 확인</b><small>원본과 비교하고 수정</small></div></div>
				<div class="mm-menu-landing__floating-note mm-menu-landing__floating-note--qr"><span class="mm-menu-landing__mini-qr" aria-hidden="true"><i></i></span><div><b>배포할 준비 완료</b><small>QR · 모바일 · 인쇄</small></div></div>
			</div>
		</div>
		<div class="mm-menu-landing__hero-bottom mm-menu-landing__container">
			<span>메뉴는 한 번만 정리하세요.</span><span class="mm-menu-landing__hero-bottom-line"></span><span>손님에게 보여줄 방법은 더 간편하게.</span>
		</div>
	</section>

	<section class="mm-menu-landing__quick-points" aria-label="메뉴판 제작 과정">
		<div class="mm-menu-landing__container mm-menu-landing__quick-grid">
			<div><span class="mm-menu-landing__quick-number">01</span><p><b>사진으로 시작</b><small>기존 메뉴판을 불러오기</small></p></div>
			<div><span class="mm-menu-landing__quick-number">02</span><p><b>내용을 확인</b><small>메뉴와 가격을 직접 정리</small></p></div>
			<div><span class="mm-menu-landing__quick-number">03</span><p><b>QR·인쇄로 완성</b><small>매장에 맞게 공유하기</small></p></div>
		</div>
	</section>

	<section class="mm-menu-landing__section mm-menu-landing__features" id="mm-menu-landing-features" aria-labelledby="mm-menu-landing-features-title">
		<div class="mm-menu-landing__container">
			<div class="mm-menu-landing__section-heading">
				<p class="mm-menu-landing__eyebrow">ONE WORKFLOW, MORE POSSIBILITIES</p>
				<h2 id="mm-menu-landing-features-title">사진 한 장부터 인쇄 파일까지,<br /><span>메뉴판에 필요한 일을 한곳에서.</span></h2>
				<p>만들고, 고치고, 손님에게 보여주는 과정을 하나의 메뉴 데이터로 이어갑니다.</p>
			</div>
			<div class="mm-menu-landing__feature-grid">
				<?php foreach ( $landing_features as $index => $feature ) : ?>
					<article class="mm-menu-landing__feature-card">
						<div class="mm-menu-landing__feature-card-top"><span class="mm-menu-landing__feature-icon" aria-hidden="true"><?php echo esc_html( $feature['icon'] ); ?></span><span class="mm-menu-landing__feature-index">0<?php echo esc_html( (string) ( $index + 1 ) ); ?></span></div>
						<p class="mm-menu-landing__feature-eyebrow"><?php echo esc_html( $feature['eyebrow'] ); ?></p>
						<h3><?php echo esc_html( $feature['title'] ); ?></h3>
						<p class="mm-menu-landing__feature-description"><?php echo esc_html( $feature['description'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="mm-menu-landing__showcase" aria-labelledby="mm-menu-landing-showcase-title">
		<div class="mm-menu-landing__container mm-menu-landing__showcase-grid">
			<div class="mm-menu-landing__showcase-copy">
				<p class="mm-menu-landing__eyebrow">FROM PAPER TO YOUR MENU</p>
				<h2 id="mm-menu-landing-showcase-title">원본을 보며 확인하고,<br /><span>내 가게에 맞게 다듬어요.</span></h2>
				<p>사진에서 읽힌 내용을 그대로 확정하지 않습니다. 원본과 인식 후보를 나란히 보고, 틀린 메뉴명이나 가격을 고친 다음 직접 메뉴로 가져올 수 있습니다.</p>
				<ul class="mm-menu-landing__check-list">
					<li><span>✓</span> OCR 후보를 원본과 나란히 비교</li>
					<li><span>✓</span> 메뉴명·가격을 원하는 대로 수정</li>
					<li><span>✓</span> 직접 입력과 사진 인식을 이어서 사용</li>
				</ul>
				<a class="mm-menu-landing__text-link" href="<?php echo esc_url( $login_url ); ?>">내 메뉴판 만들기 <span aria-hidden="true">↗</span></a>
			</div>
			<div class="mm-menu-landing__comparison" aria-label="원본 메뉴판과 정리된 메뉴 화면 비교 예시">
				<div class="mm-menu-landing__comparison-source">
					<div class="mm-menu-landing__comparison-label"><span>01</span> 원본 사진</div>
					<img src="<?php echo esc_url( $default_img ); ?>" alt="인식 전 원본 메뉴판 예시" loading="lazy" />
					<div class="mm-menu-landing__comparison-caption">촬영한 메뉴판을 기준으로 내용 확인</div>
				</div>
				<div class="mm-menu-landing__comparison-result">
					<div class="mm-menu-landing__comparison-label"><span>02</span> 정리된 메뉴</div>
					<div class="mm-menu-landing__clean-menu">
						<div class="mm-menu-landing__clean-menu-head"><span>MANMULRO HANSIK</span><b>만물로 한식당</b></div>
						<div class="mm-menu-landing__clean-category">식사 <i></i></div>
						<div class="mm-menu-landing__clean-row"><span>김치찌개</span><b>9,000원</b></div>
						<div class="mm-menu-landing__clean-row"><span>차돌박이 된장찌개</span><b>9,500원</b></div>
						<div class="mm-menu-landing__clean-row"><span>제육볶음 정식</span><b>11,000원</b></div>
					<div class="mm-menu-landing__clean-category mm-menu-landing__clean-category--second">사이드 · 별미 <i></i></div>
						<div class="mm-menu-landing__clean-row"><span>해물파전</span><b>15,000원</b></div>
						<div class="mm-menu-landing__clean-row"><span>수제 감자만두</span><b>6,000원</b></div>
						<div class="mm-menu-landing__clean-footer"><span>QR MOBILE</span><span>PRINT READY</span></div>
					</div>
					<div class="mm-menu-landing__comparison-caption">수정 가능한 메뉴 데이터로 이어서 관리</div>
				</div>
				<div class="mm-menu-landing__comparison-mark" aria-hidden="true">→</div>
			</div>
		</div>
	</section>

	<section class="mm-menu-landing__section mm-menu-landing__steps" aria-labelledby="mm-menu-landing-steps-title">
		<div class="mm-menu-landing__container">
			<div class="mm-menu-landing__section-heading mm-menu-landing__section-heading--center">
				<p class="mm-menu-landing__eyebrow">A SIMPLE THREE-STEP FLOW</p>
				<h2 id="mm-menu-landing-steps-title">복잡한 디자인 툴 대신,<br /><span>필요한 순서대로 차근차근.</span></h2>
				<p>만물로 메뉴판 만들기의 5단계 편집기를 따라가며 메뉴판을 완성하세요.</p>
			</div>
			<div class="mm-menu-landing__steps-grid">
				<?php foreach ( $landing_steps as $step ) : ?>
					<article class="mm-menu-landing__step-card">
						<div class="mm-menu-landing__step-top"><span><?php echo esc_html( $step['number'] ); ?></span><i aria-hidden="true"><?php echo esc_html( $step['icon'] ); ?></i></div>
						<h3><?php echo esc_html( $step['title'] ); ?></h3>
						<p><?php echo esc_html( $step['description'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="mm-menu-landing__templates" id="mm-menu-landing-templates" aria-labelledby="mm-menu-landing-templates-title">
		<div class="mm-menu-landing__container">
			<div class="mm-menu-landing__templates-heading">
				<div>
					<p class="mm-menu-landing__eyebrow">BUILT-IN DESIGN LIBRARY</p>
					<h2 id="mm-menu-landing-templates-title">가게 분위기에 맞는<br /><span>메뉴판 디자인을 골라보세요.</span></h2>
				</div>
				<div class="mm-menu-landing__template-count"><b><?php echo esc_html( (string) count( $templates ) ); ?></b><span>기본<br />템플릿</span></div>
			</div>
			<p class="mm-menu-landing__templates-intro">내용은 그대로 두고 디자인만 바꾸거나, 대표 색상·글꼴을 조정해 매장에 맞게 꾸밀 수 있습니다.</p>
			<div class="mm-menu-landing__template-grid">
				<?php foreach ( $preview_templates as $template ) : ?>
					<a class="mm-menu-landing__template-card" href="<?php echo esc_url( $login_url ); ?>">
						<div class="mm-menu-landing__template-swatch" style="--mm-template-accent:<?php echo esc_attr( $template['primary_color'] ); ?>;--mm-template-bg:<?php echo esc_attr( $template['bg_color'] ); ?>;--mm-template-ink:<?php echo esc_attr( $template['text_color'] ); ?>;">
							<span class="mm-menu-landing__template-kicker">MANMULRO MENU</span>
							<strong>오늘의 메뉴</strong>
							<i></i><i></i><i></i>
							<span class="mm-menu-landing__template-price">9,000원</span>
						</div>
						<div class="mm-menu-landing__template-info"><div><h3><?php echo esc_html( $template['name'] ); ?></h3><p><?php echo esc_html( $template['description'] ); ?></p></div><span aria-hidden="true">↗</span></div>
					</a>
				<?php endforeach; ?>
			</div>
			<p class="mm-menu-landing__templates-footnote">한식 · 카페 · 레스토랑 · 심플 · 모던 · 전통 · 베이커리 · 주점 등 업종과 분위기에 맞는 8종</p>
		</div>
	</section>

	<section class="mm-menu-landing__faq" aria-labelledby="mm-menu-landing-faq-title">
		<div class="mm-menu-landing__container mm-menu-landing__faq-grid">
			<div class="mm-menu-landing__faq-intro">
				<p class="mm-menu-landing__eyebrow">GOOD TO KNOW</p>
				<h2 id="mm-menu-landing-faq-title">시작하기 전에<br /><span>궁금한 점을 확인하세요.</span></h2>
				<p>메뉴판 제작 과정과 배포 방식에 대해 자주 묻는 내용을 모았습니다.</p>
				<a class="mm-menu-landing__text-link" href="<?php echo esc_url( $login_url ); ?>">편집기에서 시작하기 <span aria-hidden="true">↗</span></a>
			</div>
			<div class="mm-menu-landing__faq-list">
				<?php foreach ( $landing_faqs as $index => $faq ) : ?>
					<details class="mm-menu-landing__faq-item" <?php echo 0 === $index ? 'open' : ''; ?>>
						<summary><span><?php echo esc_html( $faq['question'] ); ?></span><i aria-hidden="true"></i></summary>
						<p><?php echo esc_html( $faq['answer'] ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="mm-menu-landing__cta" id="mm-menu-landing-cta" aria-labelledby="mm-menu-landing-cta-title">
		<div class="mm-menu-landing__container mm-menu-landing__cta-inner">
			<div class="mm-menu-landing__cta-decoration" aria-hidden="true"><span></span><span></span><span></span></div>
			<p class="mm-menu-landing__eyebrow">YOUR NEXT MENU STARTS HERE</p>
			<h2 id="mm-menu-landing-cta-title">메뉴판을 새로 만들 시간,<br /><span>이제는 더 가볍게.</span></h2>
			<p>사진으로 시작하든, 직접 입력하든. 우리 가게에 필요한 메뉴판을 만물로에서 준비해보세요.</p>
			<a class="mm-menu-landing__button mm-menu-landing__button--light" href="<?php echo esc_url( $login_url ); ?>">만물로 메뉴판 만들기 <span aria-hidden="true">↗</span></a>
			<small>로그인 후 메뉴판 만들기 편집기로 이동합니다.</small>
		</div>
	</section>
</main>
