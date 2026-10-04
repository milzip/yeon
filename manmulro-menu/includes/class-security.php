<?php
/**
 * Security, Ownership Verification & File Upload Validation (#21)
 *
 * Ensures:
 * - Only the project owner (or Administrator) can modify a menu project
 * - MIME type, file extension, file size, and malicious file checks on image uploads
 * - WordPress Nonce verification, sanitization, and escaping
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Security {

	/**
	 * Verify if current user owns the menu project or is an Administrator (#21).
	 *
	 * @param int $project_id Menu project ID.
	 * @param int $user_id    Optional user ID (defaults to current user).
	 * @return bool
	 */
	public static function can_manage_project( $project_id, $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id <= 0 ) {
			return false;
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		global $wpdb;
		$table = MM_Menu_DB::table( 'projects' );
		$owner = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$table} WHERE project_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $project_id
			)
		);

		return ( $owner > 0 && $owner === (int) $user_id );
	}

	/**
	 * Validate uploaded image file (JPG, JPEG, PNG, WEBP only #5.1, #21).
	 *
	 * @param array $file Single file entry from $_FILES.
	 * @return true|WP_Error
	 */
	public static function validate_image_file( array $file ) {
		if ( empty( $file['tmp_name'] ) || ! empty( $file['error'] ) ) {
			return new WP_Error( 'upload_error', '이미지 업로드 중 오류가 발생했습니다.' );
		}

		$max_bytes = 12 * 1024 * 1024; // 12MB limit.
		if ( isset( $file['size'] ) && $file['size'] > $max_bytes ) {
			return new WP_Error( 'file_too_large', '파일 크기는 12MB를 초과할 수 없습니다.' );
		}

		// Block executable extensions in filename (#21).
		if ( preg_match( '/\.(php|phtml|phar|pl|py|cgi|sh|exe|js|html|htm|svg)$/i', $file['name'] ) ) {
			return new WP_Error( 'malicious_file_blocked', '허용되지 않는 실행 파일 형식입니다.' );
		}

		$allowed_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
		);

		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
		if ( empty( $checked['ext'] ) || empty( $checked['type'] ) ) {
			return new WP_Error( 'invalid_mime', 'JPG, JPEG, PNG, WEBP 이미지 파일만 업로드할 수 있습니다.' );
		}

		return true;
	}

	/**
	 * Generate a unique store slug for `/menu/{store}` URLs (#13.1, #28.5).
	 * Once created, this slug and QR URL never change when menus or prices are edited!
	 *
	 * @param string $business_name Store name.
	 * @return string
	 */
	public static function generate_unique_store_slug( $business_name = '' ) {
		global $wpdb;
		$table = MM_Menu_DB::table( 'projects' );

		$base = sanitize_title( $business_name );
		if ( empty( $base ) || strlen( $base ) < 2 ) {
			$base = 'store-' . strtolower( wp_generate_password( 6, false, false ) );
		}

		$slug   = $base;
		$suffix = 1;
		while ( $wpdb->get_var( $wpdb->prepare( "SELECT project_id FROM {$table} WHERE store_slug = %s LIMIT 1", $slug ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$slug = $base . '-' . $suffix;
			++$suffix;
		}

		return $slug;
	}
}
