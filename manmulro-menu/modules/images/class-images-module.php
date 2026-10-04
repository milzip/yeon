<?php
/**
 * Images Module (`modules/images/class-images-module.php`)
 *
 * Implements Sections #2.5, #5.1, #5.2, #10, #11, #28.4:
 * - Strict separation between:
 *   1. `SOURCE_IMAGE` (원본 메뉴판 사진 — stored in `wp_mm_menu_source_images` for OCR & original comparison)
 *   2. `MENU_IMAGE`   (실제 메뉴 사진 — displayed to customers on mobile/print menus)
 * - Automatic Web/Mobile image size optimization & WebP support (#10.3)
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Images_Module {

	/**
	 * Register optimized image sizes for menu items (#10.3).
	 */
	public static function register_image_sizes() {
		add_image_size( 'mm_menu_item_thumb', 400, 400, true );
		add_image_size( 'mm_menu_item_detail', 960, 720, true );
		add_image_size( 'mm_menu_source_board', 1600, 2200, false );
	}

	/**
	 * Get all stored original source menu board images (`SOURCE_IMAGE`) for a project (#11).
	 *
	 * @param int $project_id Project ID.
	 * @return array
	 */
	public static function get_source_images( $project_id ) {
		global $wpdb;
		$table = MM_Menu_DB::table( 'source_images' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE project_id = %d ORDER BY page_number ASC, source_image_id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $project_id
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		foreach ( $rows as &$row ) {
			$row['source_image_id'] = (int) $row['source_image_id'];
			$row['project_id']      = (int) $row['project_id'];
			$row['page_number']     = (int) $row['page_number'];
			$row['preprocess_meta'] = ! empty( $row['preprocess_meta'] ) ? json_decode( $row['preprocess_meta'], true ) : array();
		}

		return $rows;
	}

	/**
	 * Save a new `SOURCE_IMAGE` record (#5.1, #11).
	 *
	 * @param int    $project_id      Project ID.
	 * @param string $image_url       Uploaded source image URL.
	 * @param int    $page_number     Page number.
	 * @param array  $preprocess_meta Preprocessing settings (rotation, scale, skew, brightness, contrast, crop #5.2).
	 * @return int Inserted source_image_id.
	 */
	public static function add_source_image( $project_id, $image_url, $page_number = 1, array $preprocess_meta = array() ) {
		global $wpdb;
		$table = MM_Menu_DB::table( 'source_images' );

		$wpdb->insert(
			$table,
			array(
				'project_id'      => (int) $project_id,
				'image_url'       => esc_url_raw( $image_url ),
				'page_number'     => max( 1, (int) $page_number ),
				'preprocess_meta' => wp_json_encode( $preprocess_meta, JSON_UNESCAPED_UNICODE ),
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}
}
