<?php
/**
 * Menu Items & Bulk Management Module (`modules/menu-items/class-menu-items-module.php`)
 *
 * Implements Sections #2.1, #6.3, #7, #9.1, #16:
 * - Common Menu Data structure used identically by OCR and Direct Input (#2.1)
 * - Add, edit, delete, duplicate (`아메리카노 HOT` -> `아메리카노 ICE`), Drag & Drop reorder (#7.1~#7.5)
 * - Category assignment, Status (`ACTIVE`, `SOLD_OUT`, `HIDDEN`), Tags (`대표 메뉴`, `인기 메뉴`, `추천 메뉴`, `신메뉴`, `매운 메뉴`) (#7.6~#7.8)
 * - Bulk Operations (#16): Multi-select price adjustment (+500, +1000, -500, custom), bulk status change, bulk category move.
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Items_Module {

	/**
	 * Initialize AJAX endpoints.
	 */
	public static function init() {
		add_action( 'wp_ajax_mm_menu_bulk_action', array( __CLASS__, 'ajax_bulk_action' ) );
	}

	/**
	 * Supported menu tags per Section #7.8.
	 *
	 * @return array
	 */
	public static function get_supported_tags() {
		return array(
			'대표 메뉴',
			'인기 메뉴',
			'추천 메뉴',
			'신메뉴',
			'매운 메뉴',
		);
	}

	/**
	 * Load all menu items for a project, optionally hydrated with detailed info (#8).
	 *
	 * @param int  $project_id      Project ID.
	 * @param bool $include_details Whether to attach ingredients, allergens, origins, flavors.
	 * @return array
	 */
	public static function get_items( $project_id, $include_details = true ) {
		global $wpdb;
		$table = MM_Menu_DB::table( 'items' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE project_id = %d ORDER BY sort_order ASC, menu_id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $project_id
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		foreach ( $rows as &$row ) {
			$row['menu_id']     = (int) $row['menu_id'];
			$row['project_id']  = (int) $row['project_id'];
			$row['category_id'] = (int) $row['category_id'];
			$row['price']       = (int) $row['price'];
			$row['tags']        = ! empty( $row['tags'] ) ? json_decode( $row['tags'], true ) : array();
			$row['extra_meta']  = ! empty( $row['extra_meta'] ) ? json_decode( $row['extra_meta'], true ) : array();

			if ( $include_details ) {
				$details            = MM_Menu_Detail_Module::get_item_details( $row['menu_id'] );
				$row['ingredients'] = $details['ingredients'];
				$row['allergens']   = $details['allergens'];
				$row['origins']     = $details['origins'];
				$row['flavors']     = $details['flavors'];
			}
		}

		return $rows;
	}

	/**
	 * Load a single menu item by `project_id` and `item_slug` for `/menu/{store}/{menu-item}` (#9.1).
	 *
	 * @param int    $project_id Project ID.
	 * @param string $item_slug  Item slug or numeric ID.
	 * @return array|null
	 */
	public static function get_item_by_slug( $project_id, $item_slug ) {
		global $wpdb;
		$table = MM_Menu_DB::table( 'items' );

		if ( is_numeric( $item_slug ) ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE project_id = %d AND (menu_id = %d OR item_slug = %s) LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					(int) $project_id,
					(int) $item_slug,
					sanitize_text_field( $item_slug )
				),
				ARRAY_A
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE project_id = %d AND item_slug = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					(int) $project_id,
					sanitize_text_field( $item_slug )
				),
				ARRAY_A
			);
		}

		if ( ! $row ) {
			return null;
		}

		$row['menu_id']     = (int) $row['menu_id'];
		$row['price']       = (int) $row['price'];
		$row['tags']        = ! empty( $row['tags'] ) ? json_decode( $row['tags'], true ) : array();
		$row['extra_meta']  = ! empty( $row['extra_meta'] ) ? json_decode( $row['extra_meta'], true ) : array();
		$details            = MM_Menu_Detail_Module::get_item_details( $row['menu_id'] );
		$row['ingredients'] = $details['ingredients'];
		$row['allergens']   = $details['allergens'];
		$row['origins']     = $details['origins'];
		$row['flavors']     = $details['flavors'];

		return $row;
	}

	/**
	 * Synchronize all menu items and their detail records for a project (#2.1, #7, #8, #15).
	 *
	 * @param int   $project_id Project ID.
	 * @param array $items      List of menu item arrays.
	 */
	public static function sync_items( $project_id, array $items ) {
		global $wpdb;
		$table      = MM_Menu_DB::table( 'items' );
		$project_id = (int) $project_id;
		$now        = current_time( 'mysql' );
		$kept_ids   = array();
		$order      = 1;

		foreach ( $items as $item ) {
			$menu_id     = ! empty( $item['menu_id'] ) && is_numeric( $item['menu_id'] ) ? (int) $item['menu_id'] : 0;
			$category_id = ! empty( $item['category_id'] ) ? (int) $item['category_id'] : 0;
			$name        = ! empty( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '새 메뉴';
			$price       = isset( $item['price'] ) ? max( 0, (int) preg_replace( '/[^0-9]/', '', (string) $item['price'] ) ) : 0;
			$short_desc  = isset( $item['short_description'] ) ? sanitize_textarea_field( $item['short_description'] ) : '';
			$detail_desc = isset( $item['detailed_description'] ) ? sanitize_textarea_field( $item['detailed_description'] ) : '';
			$image_id    = ! empty( $item['image_id'] ) ? (int) $item['image_id'] : 0;
			$image_url   = ! empty( $item['image_url'] ) ? esc_url_raw( $item['image_url'] ) : '';
			$status      = ! empty( $item['status'] ) && in_array( $item['status'], array( 'ACTIVE', 'SOLD_OUT', 'HIDDEN' ), true ) ? $item['status'] : 'ACTIVE';
			$tags        = isset( $item['tags'] ) && is_array( $item['tags'] ) ? array_values( array_map( 'sanitize_text_field', $item['tags'] ) ) : array();
			$rec_for     = isset( $item['recommended_for'] ) ? sanitize_text_field( $item['recommended_for'] ) : '';
			$extra_meta  = isset( $item['extra_meta'] ) && is_array( $item['extra_meta'] ) ? $item['extra_meta'] : array();
			$src_ocr_id  = ! empty( $item['source_ocr_id'] ) ? (int) $item['source_ocr_id'] : 0;

			$item_slug = ! empty( $item['item_slug'] ) ? sanitize_title( $item['item_slug'] ) : sanitize_title( $name );
			if ( empty( $item_slug ) ) {
				$item_slug = 'item-' . $order;
			}

			$data = array(
				'project_id'           => $project_id,
				'category_id'          => $category_id,
				'item_slug'            => $item_slug,
				'name'                 => $name,
				'price'                => $price,
				'short_description'    => $short_desc,
				'detailed_description' => $detail_desc,
				'image_id'             => $image_id,
				'image_url'            => $image_url,
				'status'               => $status,
				'tags'                 => wp_json_encode( $tags, JSON_UNESCAPED_UNICODE ),
				'recommended_for'      => $rec_for,
				'extra_meta'           => wp_json_encode( $extra_meta, JSON_UNESCAPED_UNICODE ),
				'source_ocr_id'        => $src_ocr_id,
				'sort_order'           => $order,
				'updated_at'           => $now,
			);

			if ( $menu_id > 0 ) {
				$wpdb->update(
					$table,
					$data,
					array(
						'menu_id'    => $menu_id,
						'project_id' => $project_id,
					)
				);
			} else {
				$data['created_at'] = $now;
				$wpdb->insert( $table, $data );
				$menu_id = (int) $wpdb->insert_id;
			}

			$kept_ids[] = $menu_id;

			// Sync detail tables (#8: ingredients, allergens, origins, flavors).
			MM_Menu_Detail_Module::save_item_details( $menu_id, $item );

			++$order;
		}

		if ( ! empty( $kept_ids ) ) {
			$ids_sql = implode( ',', array_map( 'intval', $kept_ids ) );
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE project_id = %d AND menu_id NOT IN ({$ids_sql})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$project_id
				)
			);
		}
	}

	/**
	 * AJAX handler for Bulk Menu Management (#16):
	 * - Price delta (+500, +1000, -500) or exact price
	 * - Bulk status change (ACTIVE, SOLD_OUT, HIDDEN)
	 * - Bulk category move
	 */
	public static function ajax_bulk_action() {
		check_ajax_referer( 'mm_menu_builder_nonce', 'nonce' );

		$project_id = isset( $_POST['project_id'] ) ? (int) $_POST['project_id'] : 0;
		if ( ! MM_Menu_Security::can_manage_project( $project_id ) ) {
			wp_send_json_error( array( 'message' => '권한이 없습니다.' ) );
		}

		$menu_ids = isset( $_POST['menu_ids'] ) && is_array( $_POST['menu_ids'] ) ? array_map( 'intval', $_POST['menu_ids'] ) : array();
		$bulk_op  = isset( $_POST['bulk_op'] ) ? sanitize_key( wp_unslash( $_POST['bulk_op'] ) ) : '';
		$value    = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

		if ( empty( $menu_ids ) ) {
			wp_send_json_error( array( 'message' => '선택된 메뉴가 없습니다.' ) );
		}

		global $wpdb;
		$table   = MM_Menu_DB::table( 'items' );
		$ids_sql = implode( ',', $menu_ids );

		if ( 'price_delta' === $bulk_op ) {
			$delta = (int) $value;
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET price = GREATEST(0, price + %d) WHERE project_id = %d AND menu_id IN ({$ids_sql})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$delta,
					$project_id
				)
			);
		} elseif ( 'price_set' === $bulk_op ) {
			$exact = max( 0, (int) $value );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET price = %d WHERE project_id = %d AND menu_id IN ({$ids_sql})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$exact,
					$project_id
				)
			);
		} elseif ( 'status' === $bulk_op && in_array( $value, array( 'ACTIVE', 'SOLD_OUT', 'HIDDEN' ), true ) ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET status = %s WHERE project_id = %d AND menu_id IN ({$ids_sql})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$value,
					$project_id
				)
			);
		} elseif ( 'category' === $bulk_op ) {
			$cat_id = (int) $value;
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET category_id = %d WHERE project_id = %d AND menu_id IN ({$ids_sql})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$cat_id,
					$project_id
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => '선택한 메뉴에 일괄 변경이 적용되었습니다.',
				'project' => MM_Menu_Projects_Module::get_full_project( $project_id ),
			)
		);
	}
}
