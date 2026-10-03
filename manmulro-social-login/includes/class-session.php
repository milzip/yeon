<?php
/**
 * OAuth State & Session Manager (Transient + HttpOnly Cookie based)
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Session {

	const COOKIE_NAME = 'mm_sl_session_id';
	const TTL         = 900; // 15 minutes

	/**
	 * Store OAuth flow context keyed by state token.
	 *
	 * @param string $state    Cryptographically random state token.
	 * @param array  $payload  Flow metadata (provider, mode, redirect_to, user_id).
	 */
	public static function store_state( $state, array $payload ) {
		$key = 'mm_sl_state_' . md5( $state );
		set_transient( $key, $payload, self::TTL );

		if ( ! headers_sent() ) {
			setcookie(
				self::COOKIE_NAME,
				$state,
				time() + self::TTL,
				COOKIEPATH ? COOKIEPATH : '/',
				COOKIE_DOMAIN,
				is_ssl(),
				true
			);
		}
	}

	/**
	 * Verify and consume OAuth state token (one-time use to prevent replay / CSRF).
	 *
	 * @param string $state State token from callback request.
	 * @return array|WP_Error
	 */
	public static function consume_state( $state ) {
		if ( empty( $state ) || ! is_string( $state ) ) {
			return new WP_Error( 'invalid_state', '유효하지 않은 인증 상태(state)입니다.' );
		}

		$key     = 'mm_sl_state_' . md5( $state );
		$payload = get_transient( $key );

		// One-time consumption.
		delete_transient( $key );

		if ( ! is_array( $payload ) ) {
			return new WP_Error( 'expired_state', '인증 세션이 만료되었거나 유효하지 않습니다. 다시 시도해주세요.' );
		}

		// If cookie is present, verify it matches state for session fixation / CSRF defense.
		if ( ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$cookie_state = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
			if ( ! hash_equals( $cookie_state, $state ) ) {
				return new WP_Error( 'state_mismatch', '보안 검증(CSRF State)에 실패했습니다.' );
			}
		}

		if ( ! headers_sent() ) {
			setcookie( self::COOKIE_NAME, '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}

		return $payload;
	}
}
