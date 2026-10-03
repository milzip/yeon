<?php
/**
 * Print & PDF Export Template (`/i/{code}?print=1&paper=a4|a5|postcard`) (#19, #38)
 *
 * Supports A4, A5, and Postcard (엽서) formats with Print CSS and embedded QR Code (#19, #38).
 *
 * @package Manmulro_Invitation
 * @var array $inv Loaded invitation array.
 */

if ( ! defined( 'ABSPATH' ) || empty( $inv ) ) {
	exit;
}

$paper         = isset( $_GET['paper'] ) ? sanitize_key( wp_unslash( $_GET['paper'] ) ) : 'a4';
$formats       = MM_Inv_Print::get_paper_formats();
if ( ! isset( $formats[ $paper ] ) ) {
	$paper = 'a4';
}
$template_slug = sanitize_key( $inv['template'] );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="robots" content="noindex, nofollow" />
	<title><?php echo esc_html( $inv['title'] ); ?> - 인쇄 / PDF 저장</title>
	<link rel="stylesheet" href="<?php echo esc_url( MM_INV_PLUGIN_URL . 'assets/css/frontend.css?ver=' . MM_INV_VERSION ); ?>" />
	<link rel="stylesheet" href="<?php echo esc_url( MM_INV_PLUGIN_URL . 'templates/themes/' . $template_slug . '/style.css?ver=' . MM_INV_VERSION ); ?>" />
	<link rel="stylesheet" href="<?php echo esc_url( MM_INV_PLUGIN_URL . 'assets/css/print.css?ver=' . MM_INV_VERSION ); ?>" />
</head>
<body class="mm-inv-print-body mm-inv-paper-<?php echo esc_attr( $paper ); ?> mm-inv-theme-<?php echo esc_attr( $template_slug ); ?>">

	<!-- Screen Toolbar (Hidden when printing via @media print) -->
	<div class="mm-inv-print-toolbar no-print">
		<div class="mm-inv-print-toolbar__left">
			<a href="<?php echo esc_url( $inv['short_url'] ); ?>" class="mm-inv-btn mm-inv-btn--outline">← 모바일 초대장으로 돌아가기</a>
			<span class="mm-inv-print-toolbar__label">용지 규격 선택:</span>
			<?php foreach ( $formats as $slug => $fmt ) : ?>
				<a href="<?php echo esc_url( MM_Inv_Print::get_print_url( $inv['short_url'], $slug ) ); ?>"
				   class="mm-inv-paper-tab <?php echo ( $slug === $paper ) ? 'is-active' : ''; ?>">
					<?php echo esc_html( $fmt['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
		<div class="mm-inv-print-toolbar__right">
			<button type="button" onclick="window.print();" class="mm-inv-btn mm-inv-btn--primary">
				🖨️ 인쇄 / PDF로 저장
			</button>
		</div>
	</div>

	<!-- Printable Sheet (#38) -->
	<article class="mm-inv-print-sheet mm-inv-print-sheet--<?php echo esc_attr( $paper ); ?>">
		<div class="mm-inv-print-sheet__inner">
			<header class="mm-inv-print-header">
				<span class="mm-inv-category-pill"><?php echo esc_html( $inv['category_name'] ); ?></span>
				<h1 class="mm-inv-print-title"><?php echo esc_html( $inv['title'] ); ?></h1>
				<?php if ( ! empty( $inv['summary'] ) ) : ?>
					<p class="mm-inv-print-subtitle"><?php echo nl2br( esc_html( $inv['summary'] ) ); ?></p>
				<?php endif; ?>
			</header>

			<?php if ( ! empty( $inv['cover_image_url'] ) ) : ?>
				<div class="mm-inv-print-cover">
					<img src="<?php echo esc_url( $inv['cover_image_url'] ); ?>" alt="<?php echo esc_attr( $inv['title'] ); ?>" />
				</div>
			<?php endif; ?>

			<section class="mm-inv-print-details">
				<?php if ( ! empty( $inv['event_date'] ) ) : ?>
					<div class="mm-inv-field-row">
						<span class="mm-inv-field-row__label">일시</span>
						<span class="mm-inv-field-row__value">
							<?php echo esc_html( $inv['event_date'] . ( $inv['event_time'] ? ' ' . $inv['event_time'] : '' ) ); ?>
						</span>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $inv['location_name'] ) || ! empty( $inv['address'] ) ) : ?>
					<div class="mm-inv-field-row">
						<span class="mm-inv-field-row__label">장소</span>
						<span class="mm-inv-field-row__value">
							<?php echo esc_html( trim( $inv['location_name'] . ' (' . $inv['address'] . ')' ) ); ?>
						</span>
					</div>
				<?php endif; ?>

				<?php
				if ( ! empty( $inv['fields'] ) ) {
					foreach ( $inv['fields'] as $field ) {
						if ( empty( $field['visible'] ) || in_array( $field['type'], array( 'gallery', 'rsvp' ), true ) ) {
							continue;
						}
						echo MM_Inv_Fields::render_field( $field, $inv ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
				}
				?>
			</section>

			<!-- QR Code for Paper-to-Mobile Bridge (#19, #38) -->
			<footer class="mm-inv-print-qr-footer">
				<div class="mm-inv-print-qr-wrap">
					<?php echo MM_Inv_QR::render_qr_box( $inv['short_url'], 116 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="mm-inv-print-qr-info">
					<strong>모바일 초대장 · 오시는 길 · 참석 여부 응답(RSVP)</strong>
					<p>스마트폰 카메라로 오른쪽 QR 코드를 촬영하시거나 아래 주소로 접속하시면 지도 길찾기 및 참석 여부를 간편하게 전달하실 수 있습니다.</p>
					<code class="mm-inv-print-url"><?php echo esc_html( $inv['short_url'] ); ?></code>
				</div>
			</footer>
		</div>
	</article>

	<script src="<?php echo esc_url( MM_INV_PLUGIN_URL . 'assets/js/qr.js?ver=' . MM_INV_VERSION ); ?>"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			if (window.MMInvQR && typeof window.MMInvQR.renderAll === 'function') {
				window.MMInvQR.renderAll();
			}
		});
	</script>
</body>
</html>
