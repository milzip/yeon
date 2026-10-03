<?php
/**
 * Google OAuth2 / OIDC Provider
 *
 * Implements Google Sign-In with minimal personal data collection (#18, #19, #20).
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Google_Provider extends MM_SL_Abstract_Provider {

	const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
	const TOKEN_URL = 'https://oauth2.googleapis.com/token';
	const USER_URL  = 'https://openidconnect.googleapis.com/v1/userinfo';

	/**
	 * Provider ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'google';
	}

	/**
	 * Provider label.
	 *
	 * @return string
	 */
	public function get_label() {
		return 'Google';
	}

	/**
	 * Korean button label (#10).
	 *
	 * @return string
	 */
	public function get_button_text() {
		return 'Google로 계속하기';
	}

	/**
	 * Build Google OAuth2 authorization URL (#20 minimal scopes: openid email profile).
	 *
	 * @param string $state CSRF state token.
	 * @return string
	 */
	public function get_authorization_url( $state ) {
		$args = array(
			'client_id'     => $this->get_client_id(),
			'redirect_uri'  => $this->get_callback_url(),
			'response_type' => 'code',
			'scope'         => 'openid email profile',
			'state'         => $state,
			'prompt'        => 'select_account',
		);

		return add_query_arg( $args, self::AUTH_URL );
	}

	/**
	 * Exchange authorization code for Google OIDC user profile (#8, #19, #20).
	 *
	 * @param string $code  Authorization code.
	 * @param string $state State token.
	 * @return array|WP_Error
	 */
	public function authenticate_code( $code, $state = '' ) {
		if ( empty( $code ) ) {
			return new WP_Error( 'google_missing_code', 'Google 인가 코드가 전달되지 않았습니다.' );
		}

		$token_res = wp_remote_post(
			self::TOKEN_URL,
			array(
				'timeout' => 15,
				'body'    => array(
					'code'          => $code,
					'client_id'     => $this->get_client_id(),
					'client_secret' => $this->get_client_secret(),
					'redirect_uri'  => $this->get_callback_url(),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		if ( is_wp_error( $token_res ) ) {
			return new WP_Error( 'google_token_http_error', 'Google 인증 서버와 통신할 수 없습니다.' );
		}

		$token_data   = json_decode( wp_remote_retrieve_body( $token_res ), true );
		$access_token = isset( $token_data['access_token'] ) ? $token_data['access_token'] : '';

		if ( empty( $access_token ) ) {
			return new WP_Error( 'google_token_failed', 'Google 액세스 토큰 발급에 실패했습니다.' );
		}

		$user_res = wp_remote_get(
			self::USER_URL,
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
			)
		);

		unset( $access_token );

		if ( is_wp_error( $user_res ) ) {
			return new WP_Error( 'google_profile_http_error', 'Google 사용자 정보를 가져오지 못했습니다.' );
		}

		$user_data = json_decode( wp_remote_retrieve_body( $user_res ), true );
		$sub       = ! empty( $user_data['sub'] ) ? (string) $user_data['sub'] : '';

		if ( '' === $sub ) {
			return new WP_Error( 'google_invalid_profile', 'Google 고유 식별자(sub)를 확인할 수 없습니다.' );
		}

		$email        = ! empty( $user_data['email'] ) ? sanitize_email( $user_data['email'] ) : '';
		$display_name = ! empty( $user_data['name'] ) ? sanitize_text_field( $user_data['name'] ) : 'Google 회원';
		$avatar_url   = ! empty( $user_data['picture'] ) ? esc_url_raw( $user_data['picture'] ) : '';

		return array(
			'provider'         => $this->get_id(),
			'provider_user_id' => $sub,
			'email'            => $email,
			'display_name'     => $display_name,
			'avatar_url'       => $avatar_url,
		);
	}
}
