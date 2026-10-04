<?php
/**
 * Menu Categories Module (`modules/categories/class-categories-module.php`)
 *
 * Implements Section #6.2:
 * - Category creation, editing, deletion, and sort_order reordering.
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Categories_Module {

	/**
	 * Initialize hooks.
	 */
	public static function init() {}

	/**
	 * Get categories for a project ordered by `sort_order`.
	 *
	 * @param int $project_id Project ID.
	 * @return array
	 */
	public static function get_categories( $project_id ) {
		global $wpdb;
		$table = MM_Menu_DB::table( 'categories' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE project_id = %d ORDER BY sort_order ASC, category_id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $project_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Synchronize categories list for a project (#6.2, #15).
	 *
	 * @param int   $project_id Project ID.
	 * @param array $categories Array of category objects.
	 */
	public static function sync_categories( $project_id, array $categories ) {
		global $wpdb;
		$table      = MM_Menu_DB::table( 'categories' );
		$project_id = (int) $project_id;

		$kept_ids = array();
		$order    = 1;

		foreach ( $categories as $cat ) {
			$cat_id = ! empty( $cat['category_id'] ) && is_numeric( $cat['category_id'] ) ? (int) $cat['category_id'] : 0;
			$name   = ! empty( $cat['name'] ) ? sanitize_text_field( $cat['name'] ) : '분류';

			if ( $cat_id > 0 ) {
				$wpdb->update(
					$table,
					array(
						'name'       => $name,
						'sort_order' => $order,
					),
					array(
						'category_id' => $cat_id,
						'project_id'  => $project_id,
					),
					array( '%s', '%d' ),
					array( '%d', '%d' )
				);
				$kept_ids[] = $cat_id;
			} else {
				$wpdb->insert(
					$table,
					array(
						'project_id' => $project_id,
						'name'       => $name,
						'sort_order' => $order,
					),
					array( '%d', '%s', '%d' )
				);
				$kept_ids[] = (int) $wpdb->insert_id;
			}
			++$order;
		}

		if ( ! empty( $kept_ids ) ) {
			$ids_sql = implode( ',', array_map( 'intval', $kept_ids ) );
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE project_id = %d AND category_id NOT IN ({$ids_sql})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$project_id
				)
			);
		}
	}
}
