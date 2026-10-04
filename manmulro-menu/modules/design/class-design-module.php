<?php
/**
 * Design System Module (`modules/design/class-design-module.php`)
 *
 * Implements Section #12:
 * - 8 Built-in Templates (#12.1):
 *   1. 한식 (`korean`)
 *   2. 카페 (`cafe`)
 *   3. 고급 레스토랑 (`fine_dining`)
 *   4. 심플 (`simple`)
 *   5. 모던 (`modern`)
 *   6. 전통 (`traditional`)
 *   7. 베이커리 (`bakery`)
 *   8. 주점 (`pub`)
 * - Customizable Design Options (#12.2):
 *   로고, 상호명, 대표 색상, 배경 색상, 글자 색상, 글꼴, 메뉴 이미지 표시 여부, 가격 표시 스타일
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Design_Module {

	/**
	 * Initialize module.
	 */
	public static function init() {}

	/**
	 * Return all 8 design templates per Section #12.1.
	 *
	 * @return array
	 */
	public static function get_templates() {
		return array(
			'korean'      => array(
				'slug'          => 'korean',
				'name'          => '한식 (Korean Dining)',
				'description'   => '정갈하고 따뜻한 한식당·백반·국밥·고깃집 전용 디자인',
				'primary_color' => '#9a3412',
				'bg_color'      => '#fffbeb',
				'text_color'    => '#1c1917',
				'font_family'   => 'serif',
			),
			'cafe'        => array(
				'slug'          => 'cafe',
				'name'          => '카페 (Cafe & Coffee)',
				'description'   => '감성적인 에스프레소 브라운 & 크림 톤의 카페 메뉴판',
				'primary_color' => '#78350f',
				'bg_color'      => '#faf8f5',
				'text_color'    => '#292524',
				'font_family'   => 'sans',
			),
			'fine_dining' => array(
				'slug'          => 'fine_dining',
				'name'          => '고급 레스토랑 (Fine Dining)',
				'description'   => '클래식 골드 & 다크 차콜 포인트의 코스/다이닝 메뉴판',
				'primary_color' => '#b45309',
				'bg_color'      => '#18181b',
				'text_color'    => '#f4f4f5',
				'font_family'   => 'serif',
			),
			'simple'      => array(
				'slug'          => 'simple',
				'name'          => '심플 (Simple)',
				'description'   => '가독성을 극대화한 깔끔한 화이트 & 블랙 메뉴판',
				'primary_color' => '#111827',
				'bg_color'      => '#ffffff',
				'text_color'    => '#111827',
				'font_family'   => 'sans',
			),
			'modern'      => array(
				'slug'          => 'modern',
				'name'          => '모던 (Modern)',
				'description'   => '세련된 블루 포인트의 캐주얼 다이닝·뷰티·서비스 가격표',
				'primary_color' => '#2563eb',
				'bg_color'      => '#f8fafc',
				'text_color'    => '#0f172a',
				'font_family'   => 'sans',
			),
			'traditional' => array(
				'slug'          => 'traditional',
				'name'          => '전통 (Traditional)',
				'description'   => '한지 질감과 먹색 타이포그래피의 전통 한식·전통주 메뉴판',
				'primary_color' => '#57534e',
				'bg_color'      => '#f5f0e6',
				'text_color'    => '#1c1917',
				'font_family'   => 'serif',
			),
			'bakery'      => array(
				'slug'          => 'bakery',
				'name'          => '베이커리 (Bakery & Dessert)',
				'description'   => '버터 옐로우와 따뜻한 코랄 톤의 베이커리·디저트샵 메뉴판',
				'primary_color' => '#d97706',
				'bg_color'      => '#fffdf7',
				'text_color'    => '#451a03',
				'font_family'   => 'round',
			),
			'pub'         => array(
				'slug'          => 'pub',
				'name'          => '주점 (Pub & Izakaya)',
				'description'   => '분위기 있는 다크 네이비 & 앰버 포인트의 주점·포차·이자카야 메뉴판',
				'primary_color' => '#f59e0b',
				'bg_color'      => '#0f172a',
				'text_color'    => '#f8fafc',
				'font_family'   => 'sans',
			),
		);
	}

	/**
	 * Default design configuration (#12.2).
	 *
	 * @return array
	 */
	public static function get_default_design_config() {
		return array(
			'logo_url'      => '',
			'primary_color' => '#9a3412',
			'bg_color'      => '#fffbeb',
			'text_color'    => '#1c1917',
			'font_family'   => 'sans', // sans, serif, round
			'show_images'   => true,
			'price_style'   => 'won',  // 'won' (9,000원), 'comma' (9,000), 'dots' (···· 9,000원)
		);
	}

	/**
	 * Sanitize design configuration (#12.2, #21).
	 *
	 * @param array $raw Raw design config.
	 * @return array
	 */
	public static function sanitize_design_config( array $raw ) {
		$defaults = self::get_default_design_config();

		return array(
			'logo_url'      => ! empty( $raw['logo_url'] ) ? esc_url_raw( $raw['logo_url'] ) : '',
			'primary_color' => ! empty( $raw['primary_color'] ) ? sanitize_text_field( $raw['primary_color'] ) : $defaults['primary_color'],
			'bg_color'      => ! empty( $raw['bg_color'] ) ? sanitize_text_field( $raw['bg_color'] ) : $defaults['bg_color'],
			'text_color'    => ! empty( $raw['text_color'] ) ? sanitize_text_field( $raw['text_color'] ) : $defaults['text_color'],
			'font_family'   => ! empty( $raw['font_family'] ) && in_array( $raw['font_family'], array( 'sans', 'serif', 'round' ), true ) ? $raw['font_family'] : 'sans',
			'show_images'   => isset( $raw['show_images'] ) ? (bool) $raw['show_images'] : true,
			'price_style'   => ! empty( $raw['price_style'] ) && in_array( $raw['price_style'], array( 'won', 'comma', 'dots' ), true ) ? $raw['price_style'] : 'won',
		);
	}

	/**
	 * Format price according to selected `price_style` (#12.2).
	 *
	 * @param int    $price       Numeric price.
	 * @param string $price_style 'won', 'comma', or 'dots'.
	 * @return string
	 */
	public static function format_price( $price, $price_style = 'won' ) {
		$formatted = number_format_i18n( (int) $price );
		if ( 'comma' === $price_style ) {
			return '₩' . $formatted;
		}
		if ( 'dots' === $price_style ) {
			return '···· ' . $formatted . '원';
		}
		return $formatted . '원';
	}
}
