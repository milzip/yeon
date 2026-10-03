<?php
/**
 * Security Helpers, Credential Resolver & Rate Limiting
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Security {

	/**
	 * Generate a cryptographically secure state token.
	 *
	 * @return string
	 */
	public static function generate_state() {
		try {
			return bin2hex( random_bytes( 24 ) );
		} catch ( Exception $e ) {
			return wp_generate_password( 48, false, false );
		}
	}

	/**
	 * Retrieve plugin settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'enable_kakao'           => 1,
			'enable_naver'           => 1,
			'enable_google'          => 1,
			'enable_email'           => 1,
			'enable_signup'          => 1,
			'enable_account_link'    => 1,
			'enable_return_redirect' => 1,
			'withdrawal_policy'      => 'retain_service_data',
			'kakao_client_id'        => '',
			'kakao_client_secret'    => '',
			'kakao_callback_url'     => '',
			'naver_client_id'        => '',
			'naver_client_secret'    => '',
			'naver_callback_url'     => '',
			'google_client_id'       => '',
			'google_client_secret'   => '',
			'google_callback_url'    => '',
		);

		$saved = get_option( 'mm_sl_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Resolve provider credential supporting wp-config.php constants & environment variables first (#18).
	 *
	 * Constant format: MM_SL_{PROVIDER}_{KEY}, e.g., MM_SL_KAKAO_CLIENT_ID, MM_SL_KAKAO_CLIENT_SECRET
	 *
	 * @param string $provider Provider slug (kakao, naver, google).
	 * @param string $field    Field key (client_id, client_secret, callback_url).
	 * @return string
	 */
	public static function get_provider_credential( $provider, $field ) {
		$provider_upper = strtoupper( sanitize_key( $provider ) );
		$field_upper    = strtoupper( sanitize_key( $field ) );
		$const_name     = "MM_SL_{$provider_upper}_{$field_upper}";

		if ( defined( $const_name ) && constant( $const_name ) ) {
			return (string) constant( $const_name );
		}

		$env_val = getenv( $const_name );
		if ( false !== $env_val && '' !== $env_val ) {
			return (string) $env_val;
		}

		$settings = self::get_settings();
		$opt_key  = sanitize_key( $provider ) . '_' . sanitize_key( $field );

		return isset( $settings[ $opt_key ] ) ? (string) $settings[ $opt_key ] : '';
	}

	/**
	 * Check if a credential is defined via constant or environment variable.
	 *
	 * @param string $provider Provider slug.
	 * @param string $field    Field key.
	 * @return bool
	 */
	public static function is_credential_from_env( $provider, $field ) {
		$const_name = 'MM_SL_' . strtoupper( sanitize_key( $provider ) ) . '_' . strtoupper( sanitize_key( $field ) );
		return ( defined( $const_name ) && constant( $const_name ) ) || ( false !== getenv( $const_name ) && '' !== getenv( $const_name ) );
	}

	/**
	 * Mask a secret for safe display in admin inputs.
	 *
	 * @param string $secret Raw secret value.
	 * @return string
	 */
	public static function mask_secret( $secret ) {
		if ( empty( $secret ) ) {
			return '';
		}
		$len = strlen( $secret );
		if ( $len <= 6 ) {
			return str_repeat( '*', $len );
		}
		return substr( $secret, 0, 3 ) . str_repeat( '*', max( 6, $len - 6 ) ) . substr( $secret, -3 );
	}

	/**
	 * Simple IP-based rate limiter using transients to prevent brute force.
	 *
	 * @param string $action_key Action identifier.
	 * @param int    $limit      Max attempts within window.
	 * @param int    $window     Window in seconds.
	 * @return bool True if allowed, false if rate-limited.
	 */
	public static function check_rate_limit( $action_key, $limit = 10, $window = 300 ) {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		$key = 'mm_sl_rl_' . md5( $action_key . '_' . $ip );
		$cnt = (int) get_transient( $key );
		if ( $cnt >= $limit ) {
			return false;
		}
		set_transient( $key, $cnt + 1, $window );
		return true;
	}
}
