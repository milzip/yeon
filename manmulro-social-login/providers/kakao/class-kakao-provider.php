<?php
/**
 * Kakao OAuth2 Provider
 *
 * Implements Kakao Login API (v2) with minimal personal data collection (#18, #19, #20).
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Kakao_Provider extends MM_SL_Abstract_Provider {

	const AUTH_URL  = 'https://kauth.kakao.com/oauth/authorize';
	const TOKEN_URL = 'https://kauth.kakao.com/oauth/token';
	const USER_URL  = 'https://kapi.kakao.com/v2/user/me';

	/**
	 * Provider ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'kakao';
	}

	/**
	 * Provider label.
	 *
	 * @return string
	 */
	public function get_label() {
		return 'Kakao';
	}

	/**
	 * Korean button label (#10).
	 *
	 * @return string
	 */
	public function get_button_text() {
		return '카카오로 시작하기';
	}

	/**
	 * Build Kakao OAuth2 authorization URL.
	 *
	 * @param string $state CSRF state token.
	 * @return string
	 */
	public function get_authorization_url( $state ) {
		$args = array(
			'client_id'     => $this->get_client_id(),
			'redirect_uri'  => $this->get_callback_url(),
			'response_type' => 'code',
			'state'         => $state,
		);

		return add_query_arg( $args, self::AUTH_URL );
	}

	/**
	 * Exchange authorization code for Kakao user profile (#8, #19, #20).
	 *
	 * @param string $code  Authorization code.
	 * @param string $state State token.
	 * @return array|WP_Error
	 */
	public function authenticate_code( $code, $state = '' ) {
		if ( empty( $code ) ) {
			return new WP_Error( 'kakao_missing_code', '카카오 인가 코드가 전달되지 않았습니다.' );
		}

		$body = array(
			'grant_type'   => 'authorization_code',
			'client_id'    => $this->get_client_id(),
			'redirect_uri' => $this->get_callback_url(),
			'code'         => $code,
		);

		$secret = $this->get_client_secret();
		if ( ! empty( $secret ) ) {
			$body['client_secret'] = $secret;
		}

		$token_res = wp_remote_post(
			self::TOKEN_URL,
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $token_res ) ) {
			return new WP_Error( 'kakao_token_http_error', '카카오 인증 서버와 통신할 수 없습니다.' );
		}

		$token_data   = json_decode( wp_remote_retrieve_body( $token_res ), true );
		$access_token = isset( $token_data['access_token'] ) ? $token_data['access_token'] : '';

		if ( empty( $access_token ) ) {
			return new WP_Error( 'kakao_token_failed', '카카오 액세스 토큰 발급에 실패했습니다.' );
		}

		$user_res = wp_remote_get(
			self::USER_URL,
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/x-www-form-urlencoded;charset=utf-8',
				),
			)
		);

		// Access token is discarded immediately after profile fetch (#19).
		unset( $access_token );

		if ( is_wp_error( $user_res ) ) {
			return new WP_Error( 'kakao_profile_http_error', '카카오 사용자 정보를 가져오지 못했습니다.' );
		}

		$user_data = json_decode( wp_remote_retrieve_body( $user_res ), true );
		if ( empty( $user_data['id'] ) ) {
			return new WP_Error( 'kakao_invalid_profile', '카카오 고유 회원번호를 확인할 수 없습니다.' );
		}

		$kakao_account = isset( $user_data['kakao_account'] ) && is_array( $user_data['kakao_account'] ) ? $user_data['kakao_account'] : array();
		$profile       = isset( $kakao_account['profile'] ) && is_array( $kakao_account['profile'] ) ? $kakao_account['profile'] : array();

		$email        = ! empty( $kakao_account['email'] ) ? sanitize_email( $kakao_account['email'] ) : '';
		$display_name = ! empty( $profile['nickname'] ) ? sanitize_text_field( $profile['nickname'] ) : '카카오 회원';
		$avatar_url   = ! empty( $profile['profile_image_url'] ) ? esc_url_raw( $profile['profile_image_url'] ) : '';

		return array(
			'provider'         => $this->get_id(),
			'provider_user_id' => (string) $user_data['id'],
			'email'            => $email,
			'display_name'     => $display_name,
			'avatar_url'       => $avatar_url,
		);
	}
}
