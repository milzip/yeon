<?php
/**
 * Security, Ownership Verification, Nonce, Spam & File Upload Validation (#43, #44)
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Security {

	/**
	 * Verify whether current user owns the given invitation or is an Administrator (#43, #44).
	 *
	 * @param int $invitation_id Invitation post ID.
	 * @param int $user_id       Optional user ID (defaults to current user).
	 * @return bool
	 */
	public static function can_manage_invitation( $invitation_id, $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id <= 0 ) {
			return false;
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$post = get_post( (int) $invitation_id );
		if ( ! $post || 'mm_invitation' !== $post->post_type ) {
			return false;
		}

		return (int) $post->post_author === (int) $user_id;
	}

	/**
	 * Generate a cryptographically random short code for `/i/{code}` URLs (#17).
	 * Avoids exposing database post IDs.
	 *
	 * @param int $length Length of code (default 6, e.g. 'a7Fk32').
	 * @return string
	 */
	public static function generate_unique_code( $length = 6 ) {
		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
		$max      = strlen( $alphabet ) - 1;

		for ( $attempt = 0; $attempt < 20; $attempt++ ) {
			$code = '';
			for ( $i = 0; $i < $length; $i++ ) {
				$code .= $alphabet[ wp_rand( 0, $max ) ];
			}

			$existing = get_posts(
				array(
					'post_type'      => 'mm_invitation',
					'post_status'    => 'any',
					'meta_key'       => '_mm_invitation_code',
					'meta_value'     => $code,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);

			if ( empty( $existing ) ) {
				return $code;
			}
		}

		return wp_generate_password( 8, false, false );
	}

	/**
	 * Hash visitor IP for privacy-preserving spam rate limiting (#25, #28, #44).
	 *
	 * @return string
	 */
	public static function get_visitor_ip_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		return hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
	}

	/**
	 * Check honeypot & rate limit for public RSVP and Guestbook submissions (#25, #28, #44).
	 *
	 * @param string $action_type   'rsvp' or 'guestbook'.
	 * @param int    $invitation_id Invitation ID.
	 * @param string $honeypot      Honeypot field value (must be empty).
	 * @param int    $max_requests  Max requests per window.
	 * @param int    $window_sec    Window in seconds.
	 * @return true|WP_Error
	 */
	public static function verify_public_submission( $action_type, $invitation_id, $honeypot = '', $max_requests = 5, $window_sec = 300 ) {
		if ( ! empty( $honeypot ) ) {
			return new WP_Error( 'spam_detected', '스팸 요청으로 차단되었습니다.' );
		}

		$ip_hash = self::get_visitor_ip_hash();
		$key     = 'mm_inv_rl_' . md5( $action_type . '_' . (int) $invitation_id . '_' . $ip_hash );
		$count   = (int) get_transient( $key );

		if ( $count >= $max_requests ) {
			return new WP_Error( 'rate_limited', '단시간에 너무 많은 요청이 발생했습니다. 잠시 후 다시 시도해주세요.' );
		}

		set_transient( $key, $count + 1, $window_sec );
		return true;
	}

	/**
	 * Validate an uploaded image file for cover or gallery (#13, #14, #44).
	 *
	 * @param array $file Single element from $_FILES.
	 * @return true|WP_Error
	 */
	public static function validate_image_upload( array $file ) {
		if ( empty( $file['tmp_name'] ) || ! empty( $file['error'] ) ) {
			return new WP_Error( 'upload_error', '이미지 업로드 중 오류가 발생했습니다.' );
		}

		$max_bytes = 10 * 1024 * 1024; // 10MB max upload size.
		if ( isset( $file['size'] ) && $file['size'] > $max_bytes ) {
			return new WP_Error( 'file_too_large', '이미지 크기는 10MB 이하여야 합니다.' );
		}

		$allowed_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
		);

		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
		if ( empty( $checked['ext'] ) || empty( $checked['type'] ) ) {
			return new WP_Error( 'invalid_image_type', '허용되지 않는 파일 형식입니다. (JPG, PNG, GIF, WebP만 가능)' );
		}

		return true;
	}

	/**
	 * Get login URL compatible with Manmulro Social Login or standard WordPress login (#3, #4).
	 *
	 * @param string $redirect_to Target internal URL after login.
	 * @return string
	 */
	public static function get_login_url( $redirect_to = '' ) {
		if ( class_exists( 'MM_SL_Redirect' ) ) {
			return MM_SL_Redirect::get_login_url( $redirect_to );
		}
		return wp_login_url( $redirect_to );
	}
}
