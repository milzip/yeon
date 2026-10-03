<?php
/**
 * Provider Interface & Abstract Base Provider
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface that all Manmulro OAuth / OIDC Providers must implement.
 * Allows adding future providers (Apple, LINE, Facebook, etc.) without modifying core.
 */
interface MM_SL_Provider_Interface {

	/**
	 * Unique provider slug (e.g., 'kakao', 'naver', 'google').
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Display label (e.g., 'Kakao', 'Naver', 'Google').
	 *
	 * @return string
	 */
	public function get_label();

	/**
	 * Button call-to-action label in Korean.
	 *
	 * @return string
	 */
	public function get_button_text();

	/**
	 * Check whether provider is enabled and configured.
	 *
	 * @return bool
	 */
	public function is_enabled();

	/**
	 * Build the OAuth2 authorization URL.
	 *
	 * @param string $state OAuth CSRF state parameter.
	 * @return string
	 */
	public function get_authorization_url( $state );

	/**
	 * Exchange authorization code for normalized user profile.
	 * Access tokens are used transiently and NOT persisted long-term (#19).
	 *
	 * @param string $code Authorization code from callback.
	 * @param string $state State parameter from callback.
	 * @return array|WP_Error Normalized profile: array(
	 *   'provider'         => string,
	 *   'provider_user_id' => string,
	 *   'email'            => string,
	 *   'display_name'     => string,
	 *   'avatar_url'       => string,
	 * )
	 */
	public function authenticate_code( $code, $state = '' );

	/**
	 * Return callback URL for this provider.
	 *
	 * @return string
	 */
	public function get_callback_url();
}

/**
 * Base abstract class sharing common configuration helpers across providers.
 */
abstract class MM_SL_Abstract_Provider implements MM_SL_Provider_Interface {

	/**
	 * Get Client ID from constant/env or database settings.
	 *
	 * @return string
	 */
	public function get_client_id() {
		return MM_SL_Security::get_provider_credential( $this->get_id(), 'client_id' );
	}

	/**
	 * Get Client Secret from constant/env or database settings.
	 *
	 * @return string
	 */
	public function get_client_secret() {
		return MM_SL_Security::get_provider_credential( $this->get_id(), 'client_secret' );
	}

	/**
	 * Check if enabled in admin settings.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$settings = MM_SL_Security::get_settings();
		$key      = 'enable_' . $this->get_id();
		return ! empty( $settings[ $key ] );
	}

	/**
	 * Check if credentials exist.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->get_client_id();
	}

	/**
	 * Build callback URL.
	 *
	 * @return string
	 */
	public function get_callback_url() {
		$custom = MM_SL_Security::get_provider_credential( $this->get_id(), 'callback_url' );
		if ( ! empty( $custom ) ) {
			return esc_url_raw( $custom );
		}
		return add_query_arg(
			array(
				'mm_sl_action' => 'callback',
				'provider'     => $this->get_id(),
			),
			home_url( '/' )
		);
	}
}
