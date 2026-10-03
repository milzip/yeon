<?php
/**
 * Naver OAuth2 Provider
 *
 * Implements Naver Login API with minimal personal data collection (#18, #19, #20).
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Naver_Provider extends MM_SL_Abstract_Provider {

	const AUTH_URL  = 'https://nid.naver.com/oauth2.0/authorize';
	const TOKEN_URL = 'https://nid.naver.com/oauth2.0/token';
	const USER_URL  = 'https://openapi.naver.com/v1/nid/me';

	/**
	 * Provider ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'naver';
	}

	/**
	 * Provider label.
	 *
	 * @return string
	 */
	public function get_label() {
		return 'Naver';
	}

	/**
	 * Korean button label (#10).
	 *
	 * @return string
	 */
	public function get_button_text() {
		return '네이버로 시작하기';
	}

	/**
	 * Build Naver OAuth2 authorization URL.
	 *
	 * @param string $state CSRF state token.
	 * @return string
	 */
	public function get_authorization_url( $state ) {
		$args = array(
			'response_type' => 'code',
			'client_id'     => $this->get_client_id(),
			'redirect_uri'  => $this->get_callback_url(),
			'state'         => $state,
		);

		return add_query_arg( $args, self::AUTH_URL );
	}

	/**
	 * Exchange authorization code for Naver user profile (#8, #19, #20).
	 *
	 * @param string $code  Authorization code.
	 * @param string $state State token.
	 * @return array|WP_Error
	 */
	public function authenticate_code( $code, $state = '' ) {
		if ( empty( $code ) ) {
			return new WP_Error( 'naver_missing_code', '네이버 인가 코드가 전달되지 않았습니다.' );
		}

		$token_res = wp_remote_post(
			self::TOKEN_URL,
			array(
				'timeout' => 15,
				'body'    => array(
					'grant_type'    => 'authorization_code',
					'client_id'     => $this->get_client_id(),
					'client_secret' => $this->get_client_secret(),
					'redirect_uri'  => $this->get_callback_url(),
					'code'          => $code,
					'state'         => $state,
				),
			)
		);

		if ( is_wp_error( $token_res ) ) {
			return new WP_Error( 'naver_token_http_error', '네이버 인증 서버와 통신할 수 없습니다.' );
		}

		$token_data   = json_decode( wp_remote_retrieve_body( $token_res ), true );
		$access_token = isset( $token_data['access_token'] ) ? $token_data['access_token'] : '';

		if ( empty( $access_token ) ) {
			return new WP_Error( 'naver_token_failed', '네이버 액세스 토큰 발급에 실패했습니다.' );
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
			return new WP_Error( 'naver_profile_http_error', '네이버 사용자 정보를 가져오지 못했습니다.' );
		}

		$user_data = json_decode( wp_remote_retrieve_body( $user_res ), true );
		$response  = isset( $user_data['response'] ) && is_array( $user_data['response'] ) ? $user_data['response'] : array();

		if ( empty( $response['id'] ) ) {
			return new WP_Error( 'naver_invalid_profile', '네이버 고유 식별자를 확인할 수 없습니다.' );
		}

		$email        = ! empty( $response['email'] ) ? sanitize_email( $response['email'] ) : '';
		$display_name = ! empty( $response['nickname'] ) ? sanitize_text_field( $response['nickname'] ) : ( ! empty( $response['name'] ) ? sanitize_text_field( $response['name'] ) : '네이버 회원' );
		$avatar_url   = ! empty( $response['profile_image'] ) ? esc_url_raw( $response['profile_image'] ) : '';

		return array(
			'provider'         => $this->get_id(),
			'provider_user_id' => (string) $response['id'],
			'email'            => $email,
			'display_name'     => $display_name,
			'avatar_url'       => $avatar_url,
		);
	}
}
