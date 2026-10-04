<?php
/**
 * OCR Importer (`modules/ocr/class-ocr-importer.php`)
 *
 * Implements Sections #2.1 ~ #2.5, #5.5 ~ #5.9, #11, #19:
 * - Persists OCR bounding boxes & confidence scores into `wp_mm_menu_ocr_results`
 * - NEVER auto-confirms OCR results (#2.2, #5.8)
 * - Saves into Common Menu Data (`wp_mm_menu_items`) ONLY when user clicks `[확인하고 메뉴로 가져오기]` (#5.9)
 * - Links `linked_menu_id` and `source_ocr_id` so the user can later click `[원본에서 보기]` (#11)
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_OCR_Importer {

	/**
	 * Initialize AJAX endpoints for OCR run and confirmed import.
	 */
	public static function init() {
		add_action( 'wp_ajax_mm_menu_run_ocr', array( __CLASS__, 'ajax_run_ocr' ) );
		add_action( 'wp_ajax_mm_menu_confirm_ocr_import', array( __CLASS__, 'ajax_confirm_ocr_import' ) );
	}

	/**
	 * Get all stored OCR results for a project (#5.4, #11).
	 *
	 * @param int $project_id Project ID.
	 * @return array
	 */
	public static function get_project_ocr_results( $project_id ) {
		global $wpdb;
		$table = MM_Menu_DB::table( 'ocr_results' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE project_id = %d ORDER BY ocr_id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $project_id
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		foreach ( $rows as &$row ) {
			$row['ocr_id']          = (int) $row['ocr_id'];
			$row['source_image_id'] = (int) $row['source_image_id'];
			$row['project_id']      = (int) $row['project_id'];
			$row['parsed_price']    = (int) $row['parsed_price'];
			$row['confidence']      = (float) $row['confidence'];
			$row['needs_review']    = (int) $row['needs_review'];
			$row['linked_menu_id']  = (int) $row['linked_menu_id'];
			$row['bounding_box']    = ! empty( $row['bounding_box'] ) ? json_decode( $row['bounding_box'], true ) : array( 'x' => 0, 'y' => 0, 'width' => 180, 'height' => 44 );
		}

		return $rows;
	}

	/**
	 * Run OCR on a source image and store unconfirmed candidate rows (#5.3, #5.4, #5.8).
	 *
	 * @param int    $project_id      Project ID.
	 * @param string $image_url       Source image URL.
	 * @param array  $preprocess_meta Preprocessing settings (#5.2).
	 * @return array{source_image_id:int, ocr_results:array}
	 */
	public static function run_and_store_ocr( $project_id, $image_url, array $preprocess_meta = array() ) {
		global $wpdb;

		$project_id      = (int) $project_id;
		$existing_imgs   = MM_Menu_Images_Module::get_source_images( $project_id );
		$next_page       = count( $existing_imgs ) + 1;
		$source_image_id = MM_Menu_Images_Module::add_source_image( $project_id, $image_url, $next_page, $preprocess_meta );

		$adapter    = new MM_Menu_OCR_Adapter();
		$normalized = $adapter->process_image( $image_url, $source_image_id, $preprocess_meta );

		$t_ocr = MM_Menu_DB::table( 'ocr_results' );
		$saved = array();

		foreach ( $normalized as $item ) {
			$wpdb->insert(
				$t_ocr,
				array(
					'source_image_id' => $source_image_id,
					'project_id'      => $project_id,
					'text'            => $item['text'],
					'parsed_name'     => $item['parsed_name'],
					'parsed_price'    => $item['parsed_price'],
					'parsed_category' => $item['parsed_category'],
					'confidence'      => $item['confidence'],
					'bounding_box'    => wp_json_encode( $item['bounding_box'] ),
					'needs_review'    => $item['needs_review'],
					'linked_menu_id'  => 0,
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%f', '%s', '%d', '%d' )
			);

			$item['ocr_id'] = (int) $wpdb->insert_id;
			$saved[]        = $item;
		}

		return array(
			'source_image_id' => $source_image_id,
			'ocr_results'     => $saved,
		);
	}

	/**
	 * Import user-verified OCR items into the Common Menu Data tables (`CATEGORY` + `MENU_ITEM`) (#2.1, #2.2, #5.9).
	 * Called ONLY when the user explicitly clicks `[확인하고 메뉴로 가져오기]`.
	 *
	 * @param int   $project_id     Project ID.
	 * @param array $confirmed_rows Verified OCR rows from the user.
	 * @return array Updated full project.
	 */
	public static function import_confirmed_ocr_to_menu( $project_id, array $confirmed_rows ) {
		global $wpdb;

		$project_id = (int) $project_id;
		$t_cats     = MM_Menu_DB::table( 'categories' );
		$t_items    = MM_Menu_DB::table( 'items' );
		$t_ocr      = MM_Menu_DB::table( 'ocr_results' );

		$existing_cats = MM_Menu_Categories_Module::get_categories( $project_id );
		$cat_map       = array();
		foreach ( $existing_cats as $c ) {
			$cat_map[ $c['name'] ] = (int) $c['category_id'];
		}

		$now   = current_time( 'mysql' );
		$order = count( MM_Menu_Items_Module::get_items( $project_id, false ) ) + 1;

		foreach ( $confirmed_rows as $row ) {
			$name     = ! empty( $row['parsed_name'] ) ? sanitize_text_field( $row['parsed_name'] ) : '';
			$price    = isset( $row['parsed_price'] ) ? max( 0, (int) preg_replace( '/[^0-9]/', '', (string) $row['parsed_price'] ) ) : 0;
			$cat_name = ! empty( $row['parsed_category'] ) ? sanitize_text_field( $row['parsed_category'] ) : '식사';
			$ocr_id   = ! empty( $row['ocr_id'] ) ? (int) $row['ocr_id'] : 0;

			if ( '' === $name ) {
				continue;
			}

			if ( ! isset( $cat_map[ $cat_name ] ) ) {
				$wpdb->insert(
					$t_cats,
					array(
						'project_id' => $project_id,
						'name'       => $cat_name,
						'sort_order' => count( $cat_map ) + 1,
					),
					array( '%d', '%s', '%d' )
				);
				$cat_map[ $cat_name ] = (int) $wpdb->insert_id;
			}

			$item_slug = sanitize_title( $name );
			if ( empty( $item_slug ) ) {
				$item_slug = 'menu-' . $order;
			}

			$wpdb->insert(
				$t_items,
				array(
					'project_id'        => $project_id,
					'category_id'       => $cat_map[ $cat_name ],
					'item_slug'         => $item_slug,
					'name'              => $name,
					'price'             => $price,
					'short_description' => '',
					'status'            => 'ACTIVE',
					'tags'              => '[]',
					'source_ocr_id'     => $ocr_id,
					'sort_order'        => $order++,
					'created_at'        => $now,
					'updated_at'        => $now,
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
			);

			$new_menu_id = (int) $wpdb->insert_id;

			if ( $ocr_id > 0 && $new_menu_id > 0 ) {
				$wpdb->update(
					$t_ocr,
					array(
						'parsed_name'     => $name,
						'parsed_price'    => $price,
						'parsed_category' => $cat_name,
						'needs_review'    => 0,
						'linked_menu_id'  => $new_menu_id,
					),
					array( 'ocr_id' => $ocr_id ),
					array( '%s', '%d', '%s', '%d', '%d' ),
					array( '%d' )
				);
			}
		}

		return MM_Menu_Projects_Module::get_full_project( $project_id );
	}

	/**
	 * AJAX: Run OCR on uploaded source menu image (#5.1 ~ #5.4).
	 */
	public static function ajax_run_ocr() {
		check_ajax_referer( 'mm_menu_builder_nonce', 'nonce' );

		$project_id = isset( $_POST['project_id'] ) ? (int) $_POST['project_id'] : 0;
		if ( ! MM_Menu_Security::can_manage_project( $project_id ) ) {
			wp_send_json_error( array( 'message' => '권한이 없습니다.' ) );
		}

		$image_url  = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		$preprocess = isset( $_POST['preprocess'] ) && is_array( $_POST['preprocess'] ) ? $_POST['preprocess'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $image_url ) ) {
			$image_url = MM_MENU_PLUGIN_URL . 'assets/images/sample-source-menu.svg';
		}

		$result = self::run_and_store_ocr( $project_id, $image_url, $preprocess );

		wp_send_json_success(
			array(
				'message'         => 'OCR 인식이 완료되었습니다. 원본 메뉴판과 비교하여 확인해주세요.',
				'source_image_id' => $result['source_image_id'],
				'ocr_results'     => $result['ocr_results'],
			)
		);
	}

	/**
	 * AJAX: Confirm OCR items and import into Common Menu Data (#5.9).
	 */
	public static function ajax_confirm_ocr_import() {
		check_ajax_referer( 'mm_menu_builder_nonce', 'nonce' );

		$project_id = isset( $_POST['project_id'] ) ? (int) $_POST['project_id'] : 0;
		if ( ! MM_Menu_Security::can_manage_project( $project_id ) ) {
			wp_send_json_error( array( 'message' => '권한이 없습니다.' ) );
		}

		$rows_json = isset( $_POST['ocr_rows'] ) ? wp_unslash( $_POST['ocr_rows'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$rows      = is_string( $rows_json ) ? json_decode( $rows_json, true ) : array();

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			wp_send_json_error( array( 'message' => '가져올 OCR 메뉴 항목이 없습니다.' ) );
		}

		$project = self::import_confirmed_ocr_to_menu( $project_id, $rows );

		wp_send_json_success(
			array(
				'message' => '확인하신 메뉴가 공통 메뉴 데이터로 저장되었습니다!',
				'project' => $project,
			)
		);
	}
}
