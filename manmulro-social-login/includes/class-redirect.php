<?php
/**
 * Safe Internal Redirect Manager (#9)
 *
 * Prevents Open Redirect vulnerabilities by allowing only internal site URLs.
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Redirect {

	/**
	 * Sanitize and validate a redirect target so only internal URLs are allowed.
	 *
	 * @param string $requested_redirect Relative path or full internal URL.
	 * @param string $fallback           Default fallback URL.
	 * @return string Safe internal URL.
	 */
	public static function sanitize_internal_redirect( $requested_redirect, $fallback = '' ) {
		if ( empty( $fallback ) ) {
			$fallback = home_url( '/' );
		}

		$settings = MM_SL_Security::get_settings();
		if ( empty( $settings['enable_return_redirect'] ) ) {
			return $fallback;
		}

		if ( empty( $requested_redirect ) || ! is_string( $requested_redirect ) ) {
			return $fallback;
		}

		$requested_redirect = trim( wp_unslash( $requested_redirect ) );

		// Reject protocol-relative URLs like //evil.com or javascript:/data: schemes.
		if ( 0 === strpos( $requested_redirect, '//' ) || preg_match( '/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $requested_redirect ) ) {
			$parsed_host = wp_parse_url( $requested_redirect, PHP_URL_HOST );
			$home_host   = wp_parse_url( home_url(), PHP_URL_HOST );
			if ( empty( $parsed_host ) || strtolower( $parsed_host ) !== strtolower( $home_host ) ) {
				return $fallback;
			}
		}

		// Convert relative path (/invitation/create/) into full home_url.
		if ( 0 === strpos( $requested_redirect, '/' ) ) {
			$requested_redirect = home_url( $requested_redirect );
		}

		$validated = wp_validate_redirect( $requested_redirect, $fallback );

		// Prevent redirect loops back to login/signup page.
		$path = wp_parse_url( $validated, PHP_URL_PATH );
		if ( $path && preg_match( '#/(login|signup|wp-login\.php)/?$#i', $path ) ) {
			return $fallback;
		}

		return $validated;
	}

	/**
	 * Get requested redirect URL from current request query args.
	 *
	 * @return string
	 */
	public static function get_requested_redirect() {
		$raw = '';
		if ( ! empty( $_REQUEST['redirect'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_REQUEST['redirect'] ) );
		} elseif ( ! empty( $_REQUEST['redirect_to'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) );
		}
		return self::sanitize_internal_redirect( $raw, home_url( '/my-account/' ) );
	}

	/**
	 * Build login URL preserving the current page as redirect target.
	 *
	 * @param string $target_url Optional explicit target URL.
	 * @return string
	 */
	public static function get_login_url( $target_url = '' ) {
		if ( empty( $target_url ) && isset( $_SERVER['REQUEST_URI'] ) ) {
			$target_url = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
		$login_base = home_url( '/login/' );
		if ( ! empty( $target_url ) ) {
			return add_query_arg( 'redirect', rawurlencode( $target_url ), $login_base );
		}
		return $login_base;
	}
}
