<?php
/**
 * Customer Mobile QR Menu Template (`/menu/{store}`) (#13, #29)
 *
 * Features:
 * - Mobile-first responsive design (#13.2)
 * - Fast category filter bar: [전체] [식사] [사이드] [음료] [주류] (#13.3)
 * - Displays menu photo, name, short description, price, status (ACTIVE / SOLD_OUT), tags (#13.4)
 * - Hides HIDDEN items (#7.7)
 * - Clicking any menu name or photo navigates to `/menu/{store}/{menu-item}` detail page (#9, #13.5)
 *
 * @package Manmulro_Menu
 * @var array $project Full loaded project array from MM_Menu_Projects_Module.
 */

if ( ! defined( 'ABSPATH' ) || empty( $project ) ) {
	exit;
}

$cfg         = $project['design_config'];
$show_images = ! isset( $cfg['show_images'] ) || ! empty( $cfg['show_images'] );
$price_style = ! empty( $cfg['price_style'] ) ? $cfg['price_style'] : 'won';
$categories  = $project['categories'];
$visible_items = array_filter(
	$project['items'],
	function ( $item ) {
		return 'HIDDEN' !== $item['status'];
	}
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
	<title><?php echo esc_html( $project['business_name'] ); ?> | 모바일 메뉴판</title>
	<link rel="stylesheet" href="<?php echo esc_url( MM_MENU_PLUGIN_URL . 'assets/css/mobile-menu.css?ver=' . MM_MENU_VERSION ); ?>" />
	<style>
		:root {
			--mm-menu-primary: <?php echo esc_attr( ! empty( $cfg['primary_color'] ) ? $cfg['primary_color'] : '#9a3412' ); ?>;
			--mm-menu-bg: <?php echo esc_attr( ! empty( $cfg['bg_color'] ) ? $cfg['bg_color'] : '#fffbeb' ); ?>;
			--mm-menu-text: <?php echo esc_attr( ! empty( $cfg['text_color'] ) ? $cfg['text_color'] : '#1c1917' ); ?>;
		}
	</style>
</head>
<body class="mm-menu-mobile-body mm-menu-tpl-<?php echo esc_attr( $project['design_template'] ); ?>">

	<main class="mm-menu-mobile-shell">
		<!-- Store Header (#12.2) -->
		<header class="mm-menu-store-header">
			<?php if ( ! empty( $cfg['logo_url'] ) ) : ?>
				<img src="<?php echo esc_url( $cfg['logo_url'] ); ?>" alt="<?php echo esc_attr( $project['business_name'] ); ?>" class="mm-menu-store-logo" />
			<?php endif; ?>
			<span class="mm-menu-store-type"><?php echo esc_html( $project['business_type'] ); ?></span>
			<h1 class="mm-menu-store-title"><?php echo esc_html( $project['business_name'] ); ?></h1>
		</header>

		<!-- Fast Category Navigation Bar (#13.3) -->
		<nav class="mm-menu-cat-nav" aria-label="메뉴 카테고리 선택">
			<button type="button" class="mm-menu-cat-pill is-active" data-filter-cat="all">[전체]</button>
			<?php foreach ( $categories as $cat ) : ?>
				<button type="button" class="mm-menu-cat-pill" data-filter-cat="<?php echo (int) $cat['category_id']; ?>">
					[<?php echo esc_html( $cat['name'] ); ?>]
				</button>
			<?php endforeach; ?>
		</nav>

		<!-- Menu Items List (#13.4, #13.5) -->
		<section class="mm-menu-list-section">
			<?php foreach ( $categories as $cat ) : ?>
				<?php
				$cat_items = array_filter(
					$visible_items,
					function ( $it ) use ( $cat ) {
						return (int) $it['category_id'] === (int) $cat['category_id'];
					}
				);
				if ( empty( $cat_items ) ) {
					continue;
				}
				?>
				<div class="mm-menu-category-group" data-category-group="<?php echo (int) $cat['category_id']; ?>">
					<h2 class="mm-menu-category-heading"><?php echo esc_html( $cat['name'] ); ?></h2>
					<div class="mm-menu-items-stack">
						<?php foreach ( $cat_items as $item ) : ?>
							<?php
							$detail_url  = MM_Menu_Sharing_Module::get_item_detail_url( $project['store_slug'], $item['item_slug'] );
							$is_sold_out = ( 'SOLD_OUT' === $item['status'] );
							?>
							<a href="<?php echo esc_url( $detail_url ); ?>"
							   class="mm-menu-item-card <?php echo $is_sold_out ? 'is-sold-out' : ''; ?>">
								<div class="mm-menu-item-card__info">
									<?php if ( ! empty( $item['tags'] ) || $is_sold_out ) : ?>
										<div class="mm-menu-item-card__tags">
											<?php if ( $is_sold_out ) : ?>
												<span class="mm-menu-tag mm-menu-tag--soldout">품절 (SOLD OUT)</span>
											<?php endif; ?>
											<?php foreach ( $item['tags'] as $tag ) : ?>
												<span class="mm-menu-tag"><?php echo esc_html( $tag ); ?></span>
											<?php endforeach; ?>
										</div>
									<?php endif; ?>

									<h3 class="mm-menu-item-card__name"><?php echo esc_html( $item['name'] ); ?></h3>

									<?php if ( ! empty( $item['short_description'] ) ) : ?>
										<p class="mm-menu-item-card__desc"><?php echo esc_html( $item['short_description'] ); ?></p>
									<?php endif; ?>

									<div class="mm-menu-item-card__price">
										<?php echo esc_html( MM_Menu_Design_Module::format_price( $item['price'], $price_style ) ); ?>
									</div>
								</div>

								<?php if ( $show_images && ! empty( $item['image_url'] ) ) : ?>
									<div class="mm-menu-item-card__thumb">
										<img src="<?php echo esc_url( $item['image_url'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" loading="lazy" />
									</div>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</section>

		<footer class="mm-menu-mobile-footer">
			<p>메뉴를 터치하면 사진·재료·맛 특징·알레르기·원산지 상세 정보를 확인할 수 있습니다.</p>
			<p class="mm-menu-credit">Powered by <strong>MANMULRO MENU</strong></p>
		</footer>
	</main>

	<script src="<?php echo esc_url( MM_MENU_PLUGIN_URL . 'assets/js/mobile-menu.js?ver=' . MM_MENU_VERSION ); ?>"></script>
</body>
</html>
