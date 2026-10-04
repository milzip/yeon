<?php
/**
 * Menu Detail Module (`modules/menu-detail/class-menu-detail-module.php`)
 *
 * Implements Section #8 & Principle #28.7:
 * - Ingredients & ingredient descriptions (#8.2, #8.3)
 * - Flavor profile (매운맛, 단맛, 짠맛, 신맛, 고소함, 담백함 -> ●●●○○ 1~5 scale #8.4)
 * - Allergen info (#8.6: 우유, 계란, 대두, 밀, 땅콩, 견과류, 갑각류, 생선, 기타 — NEVER guessed by system!)
 * - Origin info (#8.7: 소고기: 국내산 등 — NEVER guessed by system!)
 * - Multilingual translation table support (#17)
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Detail_Module {

	/**
	 * Initialize module.
	 */
	public static function init() {}

	/**
	 * Official allergen checklist per Section #8.6.
	 *
	 * @return array
	 */
	public static function get_allergen_options() {
		return array(
			'우유',
			'계란',
			'대두',
			'밀',
			'땅콩',
			'견과류',
			'갑각류',
			'생선',
			'기타',
		);
	}

	/**
	 * Official flavor profile axes per Section #8.4.
	 *
	 * @return array
	 */
	public static function get_flavor_types() {
		return array(
			'매운맛',
			'단맛',
			'짠맛',
			'신맛',
			'고소함',
			'담백함',
		);
	}

	/**
	 * Load detailed records (ingredients, allergens, origins, flavors) for a menu item.
	 *
	 * @param int $menu_id Menu item ID.
	 * @return array
	 */
	public static function get_item_details( $menu_id ) {
		global $wpdb;
		$menu_id = (int) $menu_id;

		$t_ing = MM_Menu_DB::table( 'ingredients' );
		$t_all = MM_Menu_DB::table( 'allergens' );
		$t_ori = MM_Menu_DB::table( 'origins' );
		$t_flv = MM_Menu_DB::table( 'flavors' );

		$ingredients = $wpdb->get_results(
			$wpdb->prepare( "SELECT name, description FROM {$t_ing} WHERE menu_id = %d ORDER BY ingredient_id ASC", $menu_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$allergen_rows = $wpdb->get_col(
			$wpdb->prepare( "SELECT allergen_type FROM {$t_all} WHERE menu_id = %d ORDER BY id ASC", $menu_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$origins = $wpdb->get_results(
			$wpdb->prepare( "SELECT ingredient_name, origin FROM {$t_ori} WHERE menu_id = %d ORDER BY origin_id ASC", $menu_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$flavors = $wpdb->get_results(
			$wpdb->prepare( "SELECT flavor_type, intensity FROM {$t_flv} WHERE menu_id = %d ORDER BY id ASC", $menu_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return array(
			'ingredients' => is_array( $ingredients ) ? $ingredients : array(),
			'allergens'   => is_array( $allergen_rows ) ? $allergen_rows : array(),
			'origins'     => is_array( $origins ) ? $origins : array(),
			'flavors'     => is_array( $flavors ) ? $flavors : array(),
		);
	}

	/**
	 * Save user-confirmed ingredients, allergens, origins, and flavors (#8.2 ~ #8.7).
	 * Strictly persists ONLY what the business owner entered or selected (#8.6, #8.7, #28.7).
	 *
	 * @param int   $menu_id Menu item ID.
	 * @param array $item    Item data array.
	 */
	public static function save_item_details( $menu_id, array $item ) {
		global $wpdb;
		$menu_id = (int) $menu_id;

		$t_ing = MM_Menu_DB::table( 'ingredients' );
		$t_all = MM_Menu_DB::table( 'allergens' );
		$t_ori = MM_Menu_DB::table( 'origins' );
		$t_flv = MM_Menu_DB::table( 'flavors' );

		// 1. Ingredients (#8.2, #8.3).
		if ( isset( $item['ingredients'] ) && is_array( $item['ingredients'] ) ) {
			$wpdb->delete( $t_ing, array( 'menu_id' => $menu_id ), array( '%d' ) );
			foreach ( $item['ingredients'] as $ing ) {
				$name = is_array( $ing ) && ! empty( $ing['name'] ) ? sanitize_text_field( $ing['name'] ) : ( is_string( $ing ) ? sanitize_text_field( $ing ) : '' );
				$desc = is_array( $ing ) && ! empty( $ing['description'] ) ? sanitize_text_field( $ing['description'] ) : '';
				if ( '' !== $name ) {
					$wpdb->insert(
						$t_ing,
						array(
							'menu_id'     => $menu_id,
							'name'        => $name,
							'description' => $desc,
						),
						array( '%d', '%s', '%s' )
					);
				}
			}
		}

		// 2. Allergens (#8.6 — only user-selected allergens).
		if ( isset( $item['allergens'] ) && is_array( $item['allergens'] ) ) {
			$wpdb->delete( $t_all, array( 'menu_id' => $menu_id ), array( '%d' ) );
			foreach ( array_unique( $item['allergens'] ) as $alg ) {
				$alg_clean = sanitize_text_field( $alg );
				if ( '' !== $alg_clean ) {
					$wpdb->insert(
						$t_all,
						array(
							'menu_id'       => $menu_id,
							'allergen_type' => $alg_clean,
						),
						array( '%d', '%s' )
					);
				}
			}
		}

		// 3. Origins (#8.7 — only user-entered origin info).
		if ( isset( $item['origins'] ) && is_array( $item['origins'] ) ) {
			$wpdb->delete( $t_ori, array( 'menu_id' => $menu_id ), array( '%d' ) );
			foreach ( $item['origins'] as $ori ) {
				if ( is_array( $ori ) && ! empty( $ori['ingredient_name'] ) && ! empty( $ori['origin'] ) ) {
					$wpdb->insert(
						$t_ori,
						array(
							'menu_id'         => $menu_id,
							'ingredient_name' => sanitize_text_field( $ori['ingredient_name'] ),
							'origin'          => sanitize_text_field( $ori['origin'] ),
						),
						array( '%d', '%s', '%s' )
					);
				}
			}
		}

		// 4. Flavors (#8.4 — ●●●○○ 1~5 intensity).
		if ( isset( $item['flavors'] ) && is_array( $item['flavors'] ) ) {
			$wpdb->delete( $t_flv, array( 'menu_id' => $menu_id ), array( '%d' ) );
			foreach ( $item['flavors'] as $flv ) {
				if ( is_array( $flv ) && ! empty( $flv['flavor_type'] ) ) {
					$intensity = isset( $flv['intensity'] ) ? max( 0, min( 5, (int) $flv['intensity'] ) ) : 0;
					if ( $intensity > 0 ) {
						$wpdb->insert(
							$t_flv,
							array(
								'menu_id'     => $menu_id,
								'flavor_type' => sanitize_text_field( $flv['flavor_type'] ),
								'intensity'   => $intensity,
							),
							array( '%d', '%s', '%d' )
						);
					}
				}
			}
		}
	}

	/**
	 * Format flavor intensity as `●●●○○` per Section #8.4 & #9.
	 *
	 * @param int $intensity 1..5 level.
	 * @return string
	 */
	public static function format_flavor_dots( $intensity ) {
		$filled = max( 0, min( 5, (int) $intensity ) );
		return str_repeat( '●', $filled ) . str_repeat( '○', 5 - $filled );
	}
}
