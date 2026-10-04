<?php
/**
 * Menu Projects Module (`modules/projects/class-projects-module.php`)
 *
 * Manages MENU_PROJECT records, business types (#6.1), and auto-save (#15).
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Projects_Module {

	/**
	 * Initialize AJAX endpoints for project creation and auto-save (#15).
	 */
	public static function init() {
		add_action( 'wp_ajax_mm_menu_autosave_project', array( __CLASS__, 'ajax_autosave_project' ) );
	}

	/**
	 * Supported business types per Section #6.1.
	 *
	 * @return array
	 */
	public static function get_business_types() {
		return array(
			'음식점'       => array(
				'label'              => '음식점',
				'icon'               => '🍲',
				'default_categories' => array( '식사', '사이드', '음료', '주류' ),
				'default_template'   => 'korean',
			),
			'카페'         => array(
				'label'              => '카페',
				'icon'               => '☕',
				'default_categories' => array( '커피', '논커피/에이드', '디저트', '베이커리' ),
				'default_template'   => 'cafe',
			),
			'주점'         => array(
				'label'              => '주점',
				'icon'               => '🍺',
				'default_categories' => array( '대표 안주', '탕/구이', '사이드', '주류' ),
				'default_template'   => 'pub',
			),
			'베이커리'     => array(
				'label'              => '베이커리',
				'icon'               => '🥐',
				'default_categories' => array( '식빵/바게트', '구움과자', '케이크', '음료' ),
				'default_template'   => 'bakery',
			),
			'미용/뷰티'    => array(
				'label'              => '미용/뷰티',
				'icon'               => '💇',
				'default_categories' => array( '커트', '펌', '염색', '클리닉' ),
				'default_template'   => 'modern',
			),
			'서비스 가격표'=> array(
				'label'              => '서비스 가격표',
				'icon'               => '📋',
				'default_categories' => array( '기본 서비스', '프리미엄 코스', '부가 옵션' ),
				'default_template'   => 'simple',
			),
			'기타'         => array(
				'label'              => '기타',
				'icon'               => '✨',
				'default_categories' => array( '기본 메뉴', '추가 메뉴' ),
				'default_template'   => 'simple',
			),
		);
	}

	/**
	 * Get all menu projects belonging to a user.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return array
	 */
	public static function get_user_projects( $user_id ) {
		global $wpdb;
		$table = MM_Menu_DB::table( 'projects' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $user_id
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Load full project state (including categories, menu items + details, source images, OCR results).
	 *
	 * @param int $project_id Project ID.
	 * @return array|null
	 */
	public static function get_full_project( $project_id ) {
		global $wpdb;
		$table   = MM_Menu_DB::table( 'projects' );
		$project = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE project_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $project_id
			),
			ARRAY_A
		);

		if ( ! $project ) {
			return null;
		}

		$project['project_id']    = (int) $project['project_id'];
		$project['user_id']       = (int) $project['user_id'];
		$project['public_url']    = home_url( '/menu/' . $project['store_slug'] );
		$project['design_config'] = ! empty( $project['design_config'] )
			? json_decode( $project['design_config'], true )
			: MM_Menu_Design_Module::get_default_design_config();

		$project['categories']    = MM_Menu_Categories_Module::get_categories( $project['project_id'] );
		$project['items']         = MM_Menu_Items_Module::get_items( $project['project_id'], true );
		$project['source_images'] = MM_Menu_Images_Module::get_source_images( $project['project_id'] );
		$project['ocr_results']   = MM_Menu_OCR_Importer::get_project_ocr_results( $project['project_id'] );

		return $project;
	}

	/**
	 * Find project by permanent `store_slug` (#13.1).
	 *
	 * @param string $store_slug Store slug.
	 * @return array|null
	 */
	public static function get_project_by_slug( $store_slug ) {
		global $wpdb;
		$table = MM_Menu_DB::table( 'projects' );
		$pid   = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT project_id FROM {$table} WHERE store_slug = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				sanitize_text_field( $store_slug )
			)
		);

		return $pid > 0 ? self::get_full_project( $pid ) : null;
	}

	/**
	 * Create or auto-save a project with all categories, items, details, and design (#15).
	 *
	 *Key rule (#13.1, #28.5): `store_slug` and `public_url` are generated ONCE upon creation
	 * and NEVER altered when menu items or prices are modified, keeping existing printed QR codes valid forever.
	 *
	 * @param array $payload Project payload.
	 * @param int   $user_id User ID.
	 * @return array|WP_Error Full saved project.
	 */
	public static function save_project_bundle( array $payload, $user_id = 0 ) {
		global $wpdb;

		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$project_id    = ! empty( $payload['project_id'] ) ? (int) $payload['project_id'] : 0;
		$business_name = ! empty( $payload['business_name'] ) ? sanitize_text_field( $payload['business_name'] ) : '만물로 식당';
		$business_type = ! empty( $payload['business_type'] ) ? sanitize_text_field( $payload['business_type'] ) : '음식점';
		$status        = ! empty( $payload['status'] ) && in_array( $payload['status'], array( 'DRAFT', 'PUBLISHED', 'ARCHIVED' ), true ) ? $payload['status'] : 'DRAFT';
		$template      = ! empty( $payload['design_template'] ) ? sanitize_key( $payload['design_template'] ) : 'korean';
		$design_cfg    = isset( $payload['design_config'] ) && is_array( $payload['design_config'] )
			? MM_Menu_Design_Module::sanitize_design_config( $payload['design_config'] )
			: MM_Menu_Design_Module::get_default_design_config();

		$t_projects = MM_Menu_DB::table( 'projects' );
		$now        = current_time( 'mysql' );

		if ( $project_id > 0 ) {
			if ( ! MM_Menu_Security::can_manage_project( $project_id, $user_id ) ) {
				return new WP_Error( 'forbidden', '이 메뉴판을 수정할 권한이 없습니다.' );
			}

			// Note: store_slug and public_url are intentionally NOT overwritten (#13.1, #28.5).
			$wpdb->update(
				$t_projects,
				array(
					'business_name'   => $business_name,
					'business_type'   => $business_type,
					'status'          => $status,
					'design_template' => $template,
					'design_config'   => wp_json_encode( $design_cfg, JSON_UNESCAPED_UNICODE ),
					'updated_at'      => $now,
				),
				array( 'project_id' => $project_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$store_slug = MM_Menu_Security::generate_unique_store_slug( $business_name );
			$public_url = home_url( '/menu/' . $store_slug );

			$wpdb->insert(
				$t_projects,
				array(
					'user_id'         => $user_id,
					'business_name'   => $business_name,
					'business_type'   => $business_type,
					'store_slug'      => $store_slug,
					'status'          => $status,
					'public_url'      => $public_url,
					'design_template' => $template,
					'design_config'   => wp_json_encode( $design_cfg, JSON_UNESCAPED_UNICODE ),
					'created_at'      => $now,
					'updated_at'      => $now,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
			$project_id = (int) $wpdb->insert_id;
		}

		if ( isset( $payload['categories'] ) && is_array( $payload['categories'] ) ) {
			MM_Menu_Categories_Module::sync_categories( $project_id, $payload['categories'] );
		}

		if ( isset( $payload['items'] ) && is_array( $payload['items'] ) ) {
			MM_Menu_Items_Module::sync_items( $project_id, $payload['items'] );
		}

		return self::get_full_project( $project_id );
	}

	/**
	 * AJAX handler for real-time Auto-Save (#15, #26).
	 */
	public static function ajax_autosave_project() {
		check_ajax_referer( 'mm_menu_builder_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => '로그인이 필요합니다.' ) );
		}

		$raw_payload = isset( $_POST['project_json'] ) ? wp_unslash( $_POST['project_json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$decoded     = is_string( $raw_payload ) ? json_decode( $raw_payload, true ) : array();

		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( array( 'message' => '잘못된 메뉴판 데이터 형식입니다.' ) );
		}

		$saved = self::save_project_bundle( $decoded, get_current_user_id() );
		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( array( 'message' => $saved->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'  => '자동 저장됨 (' . current_time( 'H:i:s' ) . ')',
				'project'  => $saved,
			)
		);
	}
}
