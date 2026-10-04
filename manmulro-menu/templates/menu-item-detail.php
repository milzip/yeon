<?php
/**
 * Individual Menu Item Detail Page (`/menu/{store}/{menu-item}`) (#8, #9, #28.6, #28.7)
 *
 * Displays:
 * - Menu Name, Representative Photo, Price, Short Description
 * - [이 음식은 어떤 음식인가요?] Detailed Description (#8.1)
 * - [주요 재료] Ingredients & descriptions (#8.2, #8.3)
 * - [맛 특징] Flavor dots e.g. 매운맛 ●●●○○ (#8.4)
 * - [추천 대상] Recommended for (#8.5)
 * - [알레르기 정보] User-confirmed allergens ONLY (#8.6, #28.7)
 * - [원산지] User-entered origin info ONLY (#8.7, #28.7)
 * - [기타 정보] Serving size, weight, cook time, HOT/ICE, options (#8.8)
 * - [메뉴판으로 돌아가기] & [공유하기] (#9, #9.2)
 *
 * @package Manmulro_Menu
 * @var array $project   Full store project.
 * @var array $menu_item Loaded menu item with details.
 */

if ( ! defined( 'ABSPATH' ) || empty( $project ) || empty( $menu_item ) ) {
	exit;
}

$cfg        = $project['design_config'];
$board_url  = $project['public_url'];
$detail_url = MM_Menu_Sharing_Module::get_item_detail_url( $project['store_slug'], $menu_item['item_slug'] );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo esc_html( $menu_item['name'] . ' - ' . $project['business_name'] ); ?></title>
	<meta property="og:title" content="<?php echo esc_attr( $menu_item['name'] . ' | ' . $project['business_name'] ); ?>" />
	<meta property="og:description" content="<?php echo esc_attr( $menu_item['short_description'] ); ?>" />
	<meta property="og:url" content="<?php echo esc_url( $detail_url ); ?>" />
	<?php if ( ! empty( $menu_item['image_url'] ) ) : ?>
		<meta property="og:image" content="<?php echo esc_url( $menu_item['image_url'] ); ?>" />
	<?php endif; ?>
	<link rel="stylesheet" href="<?php echo esc_url( MM_MENU_PLUGIN_URL . 'assets/css/mobile-menu.css?ver=' . MM_MENU_VERSION ); ?>" />
</head>
<body class="mm-menu-mobile-body">

	<main class="mm-menu-mobile-shell mm-menu-detail-shell">
		<div class="mm-menu-detail-topbar">
			<a href="<?php echo esc_url( $board_url ); ?>" class="mm-menu-back-link">← 메뉴판으로 돌아가기</a>
			<button type="button" class="mm-menu-share-btn" data-share-url="<?php echo esc_attr( $detail_url ); ?>" data-share-title="<?php echo esc_attr( $menu_item['name'] ); ?>">
				🔗 공유하기
			</button>
		</div>

		<?php if ( ! empty( $menu_item['image_url'] ) ) : ?>
			<figure class="mm-menu-detail-hero">
				<img src="<?php echo esc_url( $menu_item['image_url'] ); ?>" alt="<?php echo esc_attr( $menu_item['name'] ); ?>" />
			</figure>
		<?php endif; ?>

		<header class="mm-menu-detail-header">
			<?php if ( ! empty( $menu_item['tags'] ) ) : ?>
				<div class="mm-menu-item-card__tags">
					<?php foreach ( $menu_item['tags'] as $tag ) : ?>
						<span class="mm-menu-tag"><?php echo esc_html( $tag ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<h1 class="mm-menu-detail-title"><?php echo esc_html( $menu_item['name'] ); ?></h1>
			<div class="mm-menu-detail-price">
				<?php echo esc_html( MM_Menu_Design_Module::format_price( $menu_item['price'], ! empty( $cfg['price_style'] ) ? $cfg['price_style'] : 'won' ) ); ?>
			</div>

			<?php if ( ! empty( $menu_item['short_description'] ) ) : ?>
				<p class="mm-menu-detail-short"><?php echo nl2br( esc_html( $menu_item['short_description'] ) ); ?></p>
			<?php endif; ?>
		</header>

		<!-- [이 음식은 어떤 음식인가요?] Detailed Description (#8.1, #9) -->
		<?php if ( ! empty( $menu_item['detailed_description'] ) ) : ?>
			<section class="mm-menu-detail-card">
				<h2>이 음식은 어떤 음식인가요?</h2>
				<p><?php echo nl2br( esc_html( $menu_item['detailed_description'] ) ); ?></p>
			</section>
		<?php endif; ?>

		<!-- [주요 재료] (#8.2, #8.3, #9) -->
		<?php if ( ! empty( $menu_item['ingredients'] ) ) : ?>
			<section class="mm-menu-detail-card">
				<h2>주요 재료</h2>
				<ul class="mm-menu-ingredient-chips">
					<?php foreach ( $menu_item['ingredients'] as $ing ) : ?>
						<li>
							<strong><?php echo esc_html( $ing['name'] ); ?></strong>
							<?php if ( ! empty( $ing['description'] ) ) : ?>
								<span>— <?php echo esc_html( $ing['description'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<!-- [맛 특징] (#8.4, #9) -->
		<?php if ( ! empty( $menu_item['flavors'] ) ) : ?>
			<section class="mm-menu-detail-card">
				<h2>맛 특징</h2>
				<div class="mm-menu-flavor-list">
					<?php foreach ( $menu_item['flavors'] as $flv ) : ?>
						<div class="mm-menu-flavor-row">
							<span class="mm-menu-flavor-row__label"><?php echo esc_html( $flv['flavor_type'] ); ?></span>
							<span class="mm-menu-flavor-row__dots" aria-label="<?php echo (int) $flv['intensity']; ?>점">
								<?php echo esc_html( MM_Menu_Detail_Module::format_flavor_dots( (int) $flv['intensity'] ) ); ?>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- [추천 대상] (#8.5) -->
		<?php if ( ! empty( $menu_item['recommended_for'] ) ) : ?>
			<section class="mm-menu-detail-card">
				<h2>추천 대상</h2>
				<p><?php echo nl2br( esc_html( $menu_item['recommended_for'] ) ); ?></p>
			</section>
		<?php endif; ?>

		<!-- [알레르기 정보] — Strictly user-selected only (#8.6, #28.7) -->
		<?php if ( ! empty( $menu_item['allergens'] ) ) : ?>
			<section class="mm-menu-detail-card">
				<h2>알레르기 정보</h2>
				<p class="mm-menu-allergen-text"><?php echo esc_html( implode( ' / ', $menu_item['allergens'] ) ); ?></p>
			</section>
		<?php endif; ?>

		<!-- [원산지] — Strictly user-entered only (#8.7, #28.7) -->
		<?php if ( ! empty( $menu_item['origins'] ) ) : ?>
			<section class="mm-menu-detail-card">
				<h2>원산지</h2>
				<ul class="mm-menu-origin-list">
					<?php foreach ( $menu_item['origins'] as $ori ) : ?>
						<li><strong><?php echo esc_html( $ori['ingredient_name'] ); ?>:</strong> <?php echo esc_html( $ori['origin'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<div class="mm-menu-detail-bottom-actions">
			<a href="<?php echo esc_url( $board_url ); ?>" class="mm-menu-btn mm-menu-btn--outline">
				[메뉴판으로 돌아가기]
			</a>
			<button type="button" class="mm-menu-btn mm-menu-btn--primary mm-menu-share-btn" data-share-url="<?php echo esc_attr( $detail_url ); ?>" data-share-title="<?php echo esc_attr( $menu_item['name'] ); ?>">
				[공유하기]
			</button>
		</div>
	</main>

	<script src="<?php echo esc_url( MM_MENU_PLUGIN_URL . 'assets/js/mobile-menu.js?ver=' . MM_MENU_VERSION ); ?>"></script>
</body>
</html>
