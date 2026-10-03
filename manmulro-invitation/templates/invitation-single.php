<?php
/**
 * Public Single Invitation Template (`/i/{code}`)
 *
 * Accessible to guests without login (#4):
 * - View invitation & theme design (#11, #39)
 * - D-Day badge (#30)
 * - Flexible fields (#8, #9)
 * - Save to Calendar (.ics / Google Calendar) (#31)
 * - Photo gallery (max 10) with Lightbox (#13)
 * - Location & Naver Map / Directions / Copy Address (#20, #21)
 * - Phone Call / SMS buttons (#22)
 * - RSVP form & deadline enforcement (#23, #24)
 * - Guestbook form & list (#28)
 * - Sharing (KakaoTalk, Link Copy, QR Code, Print) (#18, #19, #38)
 *
 * @package Manmulro_Invitation
 * @var array $inv Loaded invitation array from MM_Inv_Invitation::get_invitation_by_code()
 */

if ( ! defined( 'ABSPATH' ) || empty( $inv ) ) {
	exit;
}

$template_slug = sanitize_key( $inv['template'] );
$dday          = $inv['dday_enabled'] ? MM_Inv_Sharing::get_dday_info( $inv['event_date'] ) : array( 'status' => 'none', 'label' => '' );
$map_links     = MM_Inv_Sharing::get_naver_map_links( $inv['location_name'], $inv['address'] );
$cal_links     = MM_Inv_Sharing::get_calendar_links( $inv );
$rsvp_closed   = MM_Inv_RSVP::is_deadline_passed( $inv['id'] );
$guestbook     = $inv['guestbook_enabled'] ? MM_Inv_Guestbook::get_entries( $inv['id'], false ) : array();
$settings      = get_option( 'mm_inv_settings', array() );
$kakao_js_key  = isset( $settings['kakao_js_key'] ) ? $settings['kakao_js_key'] : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
	<?php if ( ! empty( $inv['noindex'] ) ) : ?>
		<meta name="robots" content="noindex, nofollow, noarchive" />
	<?php endif; ?>
	<title><?php echo esc_html( $inv['title'] ); ?> | 만물로 초대장</title>
	<meta property="og:type" content="website" />
	<meta property="og:title" content="<?php echo esc_attr( $inv['title'] ); ?>" />
	<meta property="og:description" content="<?php echo esc_attr( $inv['summary'] ? $inv['summary'] : '소중한 자리에 초대합니다.' ); ?>" />
	<meta property="og:url" content="<?php echo esc_url( $inv['short_url'] ); ?>" />
	<?php if ( ! empty( $inv['cover_image_url'] ) ) : ?>
		<meta property="og:image" content="<?php echo esc_url( $inv['cover_image_url'] ); ?>" />
	<?php endif; ?>
	<link rel="stylesheet" href="<?php echo esc_url( MM_INV_PLUGIN_URL . 'assets/css/frontend.css?ver=' . MM_INV_VERSION ); ?>" />
	<link rel="stylesheet" href="<?php echo esc_url( MM_INV_PLUGIN_URL . 'templates/themes/' . $template_slug . '/style.css?ver=' . MM_INV_VERSION ); ?>" />
</head>
<body class="mm-inv-body mm-inv-theme-<?php echo esc_attr( $template_slug ); ?>">

	<main class="mm-inv-container" id="mm-invitation-card" data-invitation-id="<?php echo (int) $inv['id']; ?>" data-short-url="<?php echo esc_attr( $inv['short_url'] ); ?>">

		<!-- Header / Cover Section (#12, #30) -->
		<header class="mm-inv-hero">
			<div class="mm-inv-hero__top-badges">
				<span class="mm-inv-category-pill"><?php echo esc_html( $inv['category_name'] ); ?></span>
				<?php if ( ! empty( $dday['label'] ) ) : ?>
					<span class="mm-inv-dday-pill mm-inv-dday-pill--<?php echo esc_attr( $dday['status'] ); ?>">
						<?php echo esc_html( $dday['label'] ); ?>
					</span>
				<?php endif; ?>
			</div>

			<h1 class="mm-inv-hero__title"><?php echo esc_html( $inv['title'] ); ?></h1>

			<?php if ( ! empty( $inv['summary'] ) ) : ?>
				<p class="mm-inv-hero__subtitle"><?php echo nl2br( esc_html( $inv['summary'] ) ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $inv['cover_image_url'] ) ) : ?>
				<figure class="mm-inv-hero__cover">
					<img src="<?php echo esc_url( $inv['cover_image_url'] ); ?>"
					     alt="<?php echo esc_attr( $inv['title'] ); ?>"
					     decoding="async" />
				</figure>
			<?php endif; ?>

			<?php if ( ! empty( $inv['event_date'] ) || ! empty( $inv['location_name'] ) ) : ?>
				<div class="mm-inv-hero__meta">
					<?php if ( ! empty( $inv['event_date'] ) ) : ?>
						<div class="mm-inv-hero__meta-item">
							<strong>일시</strong>
							<span>
								<?php echo esc_html( $inv['event_date'] ); ?>
								<?php if ( ! empty( $inv['event_time'] ) ) : ?>
									<?php echo ' ' . esc_html( $inv['event_time'] ); ?>
								<?php endif; ?>
								<?php if ( ! empty( $inv['event_end_time'] ) ) : ?>
									<?php echo ' ~ ' . esc_html( $inv['event_end_time'] ); ?>
								<?php endif; ?>
							</span>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $inv['location_name'] ) ) : ?>
						<div class="mm-inv-hero__meta-item">
							<strong>장소</strong>
							<span><?php echo esc_html( $inv['location_name'] ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Save to Calendar (#31) -->
			<?php if ( ! empty( $inv['event_date'] ) ) : ?>
				<div class="mm-inv-calendar-bar">
					<a href="<?php echo esc_url( $cal_links['ics_url'] ); ?>" class="mm-inv-btn mm-inv-btn--outline">
						📅 내 일정에 저장 (.ics)
					</a>
					<a href="<?php echo esc_url( $cal_links['google_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="mm-inv-btn mm-inv-btn--ghost">
						Google 캘린더 등록
					</a>
				</div>
			<?php endif; ?>
		</header>

		<!-- Flexible Fields Section (#8, #9, #10, #11, #22) -->
		<?php if ( ! empty( $inv['fields'] ) ) : ?>
			<section class="mm-inv-section mm-inv-fields-section">
				<?php
				foreach ( $inv['fields'] as $field ) {
					echo MM_Inv_Fields::render_field( $field, $inv ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</section>
		<?php endif; ?>

		<!-- Photo Gallery (#13) -->
		<?php echo MM_Inv_Gallery::render_gallery( $inv['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<!-- Location & Naver Map / Directions Section (#20, #21) -->
		<?php if ( ! empty( $inv['location_name'] ) || ! empty( $inv['address'] ) ) : ?>
			<section class="mm-inv-section mm-inv-location-section">
				<h3 class="mm-inv-section-title">오시는 길 · 장소 안내</h3>
				<div class="mm-inv-location-card">
					<?php if ( ! empty( $inv['location_name'] ) ) : ?>
						<p class="mm-inv-location-name"><?php echo esc_html( $inv['location_name'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $inv['address'] ) ) : ?>
						<p class="mm-inv-location-address" id="mm-inv-address-text"><?php echo esc_html( $inv['address'] ); ?></p>
					<?php endif; ?>

					<div class="mm-inv-location-buttons">
						<a href="<?php echo esc_url( $map_links['map_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="mm-inv-btn mm-inv-btn--naver">
							🗺️ 네이버 지도
						</a>
						<a href="<?php echo esc_url( $map_links['directions_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="mm-inv-btn mm-inv-btn--outline">
							🧭 길찾기
						</a>
						<?php if ( ! empty( $inv['address'] ) ) : ?>
							<button type="button" class="mm-inv-btn mm-inv-btn--outline" data-copy-text="<?php echo esc_attr( $inv['address'] ); ?>">
								📋 주소 복사
							</button>
						<?php endif; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- RSVP Section (#23, #24) -->
		<?php if ( ! empty( $inv['rsvp_enabled'] ) ) : ?>
			<section class="mm-inv-section mm-inv-rsvp-section" id="mm-rsvp">
				<div class="mm-inv-section-header">
					<h3 class="mm-inv-section-title">참석 여부 (RSVP)</h3>
					<?php if ( ! empty( $inv['rsvp_deadline'] ) ) : ?>
						<span class="mm-inv-deadline-tag">응답 마감: <?php echo esc_html( $inv['rsvp_deadline'] ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( $rsvp_closed ) : ?>
					<div class="mm-inv-closed-box">
						참석 여부 응답이 마감되었습니다.
					</div>
				<?php else : ?>
					<form id="mm-inv-rsvp-form" class="mm-inv-form">
						<input type="hidden" name="invitation_id" value="<?php echo (int) $inv['id']; ?>" />
						<input type="text" name="mm_hp_website" value="" style="display:none !important;" tabindex="-1" autocomplete="off" />

						<div class="mm-inv-form-field">
							<label for="mm_rsvp_name">성함 *</label>
							<input type="text" id="mm_rsvp_name" name="name" required placeholder="성함을 입력해주세요" />
						</div>

						<div class="mm-inv-form-field">
							<label>참석 여부 *</label>
							<div class="mm-inv-radio-group">
								<label class="mm-inv-radio-pill">
									<input type="radio" name="status" value="attending" checked />
									<span>참석</span>
								</label>
								<label class="mm-inv-radio-pill">
									<input type="radio" name="status" value="declined" />
									<span>불참</span>
								</label>
								<label class="mm-inv-radio-pill">
									<input type="radio" name="status" value="maybe" />
									<span>미정</span>
								</label>
							</div>
						</div>

						<div class="mm-inv-form-field" id="mm_rsvp_count_wrap">
							<label for="mm_rsvp_count">참석 인원 (본인 포함)</label>
							<input type="number" id="mm_rsvp_count" name="guest_count" min="1" max="20" value="1" />
						</div>

						<div class="mm-inv-form-field">
							<label for="mm_rsvp_message">남기실 말씀</label>
							<textarea id="mm_rsvp_message" name="message" rows="2" placeholder="축하 또는 안부 인사를 남겨주세요"></textarea>
						</div>

						<button type="submit" class="mm-inv-btn mm-inv-btn--primary mm-inv-btn--block">응답하기</button>
						<div class="mm-inv-form-feedback" id="mm-rsvp-feedback" aria-live="polite"></div>
					</form>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<!-- Guestbook Section (#28) -->
		<?php if ( ! empty( $inv['guestbook_enabled'] ) ) : ?>
			<section class="mm-inv-section mm-inv-guestbook-section" id="mm-guestbook">
				<h3 class="mm-inv-section-title">방명록</h3>

				<form id="mm-inv-guestbook-form" class="mm-inv-form">
					<input type="hidden" name="invitation_id" value="<?php echo (int) $inv['id']; ?>" />
					<input type="text" name="mm_hp_website" value="" style="display:none !important;" tabindex="-1" autocomplete="off" />

					<div class="mm-inv-form-row">
						<input type="text" name="name" required placeholder="이름" class="mm-inv-input-sm" />
						<input type="text" name="message" required placeholder="따뜻한 메시지를 남겨주세요" class="mm-inv-input-lg" />
						<button type="submit" class="mm-inv-btn mm-inv-btn--primary">남기기</button>
					</div>
					<div class="mm-inv-form-feedback" id="mm-guestbook-feedback" aria-live="polite"></div>
				</form>

				<ul class="mm-inv-guestbook-list" id="mm-guestbook-list">
					<?php if ( empty( $guestbook ) ) : ?>
						<li class="mm-inv-guestbook-empty">첫 번째 방명록 메시지를 남겨보세요.</li>
					<?php else : ?>
						<?php foreach ( $guestbook as $entry ) : ?>
							<li class="mm-inv-guestbook-item">
								<div class="mm-inv-guestbook-item__head">
									<strong><?php echo esc_html( $entry['name'] ); ?></strong>
									<span><?php echo esc_html( gmdate( 'm/d H:i', strtotime( $entry['created_at'] ) ) ); ?></span>
								</div>
								<p class="mm-inv-guestbook-item__msg"><?php echo nl2br( esc_html( $entry['message'] ) ); ?></p>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</section>
		<?php endif; ?>

		<!-- Sharing & QR Section (#18, #19, #38) -->
		<footer class="mm-inv-footer">
			<h3 class="mm-inv-section-title">초대장 공유하기</h3>
			<div class="mm-inv-share-buttons">
				<button type="button"
				        class="mm-inv-btn mm-inv-btn--kakao"
				        id="mm-btn-kakao-share"
				        data-title="<?php echo esc_attr( $inv['title'] ); ?>"
				        data-desc="<?php echo esc_attr( $inv['summary'] ); ?>"
				        data-image="<?php echo esc_attr( $inv['cover_image_url'] ); ?>"
				        data-url="<?php echo esc_attr( $inv['short_url'] ); ?>">
					💬 카카오톡 공유
				</button>
				<button type="button"
				        class="mm-inv-btn mm-inv-btn--outline"
				        data-copy-text="<?php echo esc_attr( $inv['short_url'] ); ?>">
					🔗 링크 복사
				</button>
				<button type="button"
				        class="mm-inv-btn mm-inv-btn--outline"
				        id="mm-btn-toggle-qr">
					📱 QR 코드
				</button>
				<a href="<?php echo esc_url( MM_Inv_Print::get_print_url( $inv['short_url'], 'a4' ) ); ?>"
				   class="mm-inv-btn mm-inv-btn--outline">
					🖨️ 인쇄 / PDF
				</a>
			</div>

			<div id="mm-inv-qr-drawer" class="mm-inv-qr-drawer" hidden>
				<?php echo MM_Inv_QR::render_qr_box( $inv['short_url'], 168 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<p class="mm-inv-brand-credit">Powered by <strong>MANMULRO INVITATION</strong> · 모든 만남을 위한 초대장</p>
		</footer>
	</main>

	<!-- Lightbox Modal for Gallery (#13) -->
	<div id="mm-inv-lightbox" class="mm-inv-lightbox" hidden role="dialog" aria-modal="true" aria-label="사진 확대보기">
		<button type="button" class="mm-inv-lightbox__close" aria-label="닫기">&times;</button>
		<img src="" alt="확대 이미지" id="mm-inv-lightbox-img" />
	</div>

	<script>
		window.mmInvPublic = {
			ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
			nonce: <?php echo wp_json_encode( wp_create_nonce( 'mm_inv_public_nonce' ) ); ?>,
			kakaoJsKey: <?php echo wp_json_encode( $kakao_js_key ); ?>,
			shortUrl: <?php echo wp_json_encode( $inv['short_url'] ); ?>
		};
	</script>
	<script src="<?php echo esc_url( MM_INV_PLUGIN_URL . 'assets/js/qr.js?ver=' . MM_INV_VERSION ); ?>"></script>
	<script src="<?php echo esc_url( MM_INV_PLUGIN_URL . 'assets/js/frontend.js?ver=' . MM_INV_VERSION ); ?>"></script>
</body>
</html>
