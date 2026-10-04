<?php
/**
 * Print Menu Template (`/menu/{store}?print=1&paper=a4|a3&orientation=portrait|landscape`) (#14)
 *
 * Uses the exact same Common Menu Data to render a print-ready menu board:
 * - Paper sizes: A4, A3
 * - Orientation: 세로 (portrait), 가로 (landscape)
 * - Outputs: PDF (Print/Save as PDF), JPG, PNG
 * - Includes permanent QR code to the mobile menu (#13, #14)
 *
 * @package Manmulro_Menu
 * @var array $project Full project data.
 */

if ( ! defined( 'ABSPATH' ) || empty( $project ) ) {
	exit;
}

$paper       = isset( $_GET['paper'] ) && 'a3' === $_GET['paper'] ? 'a3' : 'a4';
$orientation = isset( $_GET['orientation'] ) && 'landscape' === $_GET['orientation'] ? 'landscape' : 'portrait';
$cfg         = $project['design_config'];
$price_style = ! empty( $cfg['price_style'] ) ? $cfg['price_style'] : 'won';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo esc_html( $project['business_name'] ); ?> - 인쇄용 메뉴판 (#14)</title>
	<link rel="stylesheet" href="<?php echo esc_url( MM_MENU_PLUGIN_URL . 'assets/css/mobile-menu.css?ver=' . MM_MENU_VERSION ); ?>" />
	<link rel="stylesheet" href="<?php echo esc_url( MM_MENU_PLUGIN_URL . 'assets/css/print-menu.css?ver=' . MM_MENU_VERSION ); ?>" />
	<style>
		@page {
			size: <?php echo esc_attr( strtoupper( $paper ) . ' ' . $orientation ); ?>;
			margin: 10mm;
		}
	</style>
</head>
<body class="mm-menu-print-body mm-paper-<?php echo esc_attr( $paper ); ?> mm-orient-<?php echo esc_attr( $orientation ); ?>">

	<!-- Screen Toolbar (Hidden when printing) -->
	<div class="mm-menu-print-toolbar no-print">
		<div class="mm-menu-print-toolbar__group">
			<a href="<?php echo esc_url( $project['public_url'] ); ?>" class="mm-menu-btn mm-menu-btn--outline">← 모바일 메뉴판</a>
			<span>용지 크기:</span>
			<a href="<?php echo esc_url( MM_Menu_Print_Module::get_print_url( $project['public_url'], 'a4', $orientation ) ); ?>" class="mm-print-chip <?php echo 'a4' === $paper ? 'is-active' : ''; ?>">A4</a>
			<a href="<?php echo esc_url( MM_Menu_Print_Module::get_print_url( $project['public_url'], 'a3', $orientation ) ); ?>" class="mm-print-chip <?php echo 'a3' === $paper ? 'is-active' : ''; ?>">A3</a>
			<span>방향:</span>
			<a href="<?php echo esc_url( MM_Menu_Print_Module::get_print_url( $project['public_url'], $paper, 'portrait' ) ); ?>" class="mm-print-chip <?php echo 'portrait' === $orientation ? 'is-active' : ''; ?>">세로</a>
			<a href="<?php echo esc_url( MM_Menu_Print_Module::get_print_url( $project['public_url'], $paper, 'landscape' ) ); ?>" class="mm-print-chip <?php echo 'landscape' === $orientation ? 'is-active' : ''; ?>">가로</a>
		</div>
		<div class="mm-menu-print-toolbar__group">
			<button type="button" onclick="window.print();" class="mm-menu-btn mm-menu-btn--primary">🖨️ PDF 인쇄 / 저장</button>
			<button type="button" id="mm-btn-export-png" class="mm-menu-btn mm-menu-btn--outline">🖼️ PNG/JPG 이미지 저장</button>
		</div>
	</div>

	<article class="mm-menu-print-sheet" id="mm-menu-print-sheet">
		<header class="mm-menu-print-header">
			<div>
				<span class="mm-menu-store-type"><?php echo esc_html( $project['business_type'] ); ?> MENU</span>
				<h1><?php echo esc_html( $project['business_name'] ); ?></h1>
			</div>
			<div class="mm-menu-print-qr">
				<?php echo MM_Menu_QR_Module::render_qr_box( $project['public_url'], 96 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</header>

		<div class="mm-menu-print-columns">
			<?php foreach ( $project['categories'] as $cat ) : ?>
				<?php
				$cat_items = array_filter(
					$project['items'],
					function ( $it ) use ( $cat ) {
						return (int) $it['category_id'] === (int) $cat['category_id'] && 'HIDDEN' !== $it['status'];
					}
				);
				if ( empty( $cat_items ) ) {
					continue;
				}
				?>
				<section class="mm-menu-print-cat">
					<h2><?php echo esc_html( $cat['name'] ); ?></h2>
					<?php foreach ( $cat_items as $item ) : ?>
						<div class="mm-menu-print-row">
							<div class="mm-menu-print-row__main">
								<strong><?php echo esc_html( $item['name'] ); ?></strong>
								<span class="mm-menu-print-row__price"><?php echo esc_html( MM_Menu_Design_Module::format_price( $item['price'], $price_style ) ); ?></span>
							</div>
							<?php if ( ! empty( $item['short_description'] ) ) : ?>
								<p class="mm-menu-print-row__desc"><?php echo esc_html( $item['short_description'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</section>
			<?php endforeach; ?>
		</div>

		<footer class="mm-menu-print-footer">
			<span>상단 QR 코드를 스마트폰으로 스캔하시면 각 메뉴의 상세 사진·재료·알레르기·원산지 정보를 확인하실 수 있습니다.</span>
			<code><?php echo esc_html( $project['public_url'] ); ?></code>
		</footer>
	</article>

	<script src="<?php echo esc_url( MM_MENU_PLUGIN_URL . 'assets/js/mobile-menu.js?ver=' . MM_MENU_VERSION ); ?>"></script>
</body>
</html>
