<?php
/**
 * WordPress User Integration, Duplicate Account Protection & Minimal Data Policy
 *
 * Implements sections #6, #11, #12, #20, #21 of the MANMULRO SOCIAL LOGIN specification.
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_User {

	/**
	 * Check whether the user has an explicit email/password login configured.
	 * Users created purely via social login have usermeta `_mm_sl_password_set = 0` until they set a password.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return bool
	 */
	public static function user_has_password_login( $user_id ) {
		$flag = get_user_meta( (int) $user_id, '_mm_sl_password_set', true );
		if ( '0' === (string) $flag ) {
			return false;
		}
		return true;
	}

	/**
	 * Create a new WordPress user from normalized social profile (#11, #12, #20).
	 *
	 * Important Duplicate Account Prevention Policy (#12):
	 * - NEVER automatically merge into an existing account just because the email matches!
	 * - If the email already belongs to an existing WP user, we return a specific WP_Error
	 *   (`email_exists_manual_link_required`) instructing the user to log in with their existing
	 *   account and link the social provider from My Account -> Login Management.
	 *
	 * @param array                $profile Normalized profile from provider.
	 * @param MM_SL_Account_Linker $linker  Account linker instance.
	 * @return int|WP_Error WordPress User ID or WP_Error.
	 */
	public static function create_user_from_social( array $profile, MM_SL_Account_Linker $linker ) {
		$settings = MM_SL_Security::get_settings();
		if ( empty( $settings['enable_signup'] ) ) {
			return new WP_Error( 'signup_disabled', '현재 신규 회원가입이 비활성화되어 있습니다.' );
		}

		$provider         = isset( $profile['provider'] ) ? sanitize_key( $profile['provider'] ) : '';
		$provider_user_id = isset( $profile['provider_user_id'] ) ? trim( (string) $profile['provider_user_id'] ) : '';
		$email            = isset( $profile['email'] ) ? sanitize_email( $profile['email'] ) : '';
		$display_name     = isset( $profile['display_name'] ) ? sanitize_text_field( $profile['display_name'] ) : '';
		$avatar_url       = isset( $profile['avatar_url'] ) ? esc_url_raw( $profile['avatar_url'] ) : '';

		if ( empty( $provider ) || '' === $provider_user_id ) {
			return new WP_Error( 'missing_provider_id', '소셜 계정 고유 식별자(User ID)를 확인할 수 없습니다.' );
		}

		// Principle #12: Do NOT auto-merge accounts solely because email address matches!
		if ( ! empty( $email ) && email_exists( $email ) ) {
			return new WP_Error(
				'email_exists_manual_link_required',
				'동일한 이메일(' . esc_html( $email ) . ')을 사용하는 계정이 이미 존재합니다. 보안을 위해 자동으로 계정을 통합하지 않습니다. 기존 계정으로 먼저 로그인한 후 [마이페이지 → 로그인 관리]에서 소셜 계정을 직접 연결해주세요.'
			);
		}

		// Generate safe unique username based on provider + hash.
		$base_login = 'mm_' . $provider . '_' . substr( md5( $provider . ':' . $provider_user_id ), 0, 10 );
		$user_login = $base_login;
		$suffix     = 1;
		while ( username_exists( $user_login ) ) {
			$user_login = $base_login . '_' . $suffix;
			++$suffix;
		}

		// If provider did not supply an email, use a non-routable placeholder domain per WP requirement.
		if ( empty( $email ) ) {
			$email = $user_login . '@users.manmulro.local';
		}

		if ( empty( $display_name ) ) {
			$display_name = '만물로 회원';
		}

		$random_password = wp_generate_password( 32, true, true );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $user_login,
				'user_pass'    => $random_password,
				'user_email'   => $email,
				'display_name' => $display_name,
				'nickname'     => $display_name,
				'role'         => get_option( 'default_role', 'subscriber' ),
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Mark that the user has not set an explicit password yet (for Unlink Protection #14).
		update_user_meta( $user_id, '_mm_sl_password_set', '0' );
		update_user_meta( $user_id, '_mm_sl_created_via', $provider );

		if ( ! empty( $avatar_url ) ) {
			update_user_meta( $user_id, '_mm_sl_avatar_url', $avatar_url );
		}

		$linked = $linker->link_account( $user_id, $provider, $provider_user_id );
		if ( is_wp_error( $linked ) ) {
			return $linked;
		}

		return (int) $user_id;
	}

	/**
	 * Log in a WordPress User safely (prevents session fixation #19).
	 *
	 * @param int  $user_id  WordPress User ID.
	 * @param bool $remember Remember login cookie.
	 * @return true|WP_Error
	 */
	public static function login_wordpress_user( $user_id, $remember = true ) {
		$user = get_user_by( 'id', (int) $user_id );
		if ( ! $user ) {
			return new WP_Error( 'invalid_user', '존재하지 않는 사용자 계정입니다.' );
		}

		// Check if user requested account withdrawal.
		$withdrawn = get_user_meta( $user->ID, '_mm_sl_withdrawal_status', true );
		if ( 'withdrawn' === $withdrawn ) {
			return new WP_Error( 'account_withdrawn', '탈퇴 처리가 진행 중이거나 완료된 계정입니다.' );
		}

		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID, $remember );
		do_action( 'wp_login', $user->user_login, $user );

		return true;
	}

	/**
	 * Handle account withdrawal request from My Account (#21).
	 *
	 * Does NOT hardcode immediate destructive deletion of all service data;
	 * triggers hooks so Manmulro Invitation and future services can apply their retention policy.
	 *
	 * @param int    $user_id WordPress User ID.
	 * @param string $reason  Optional withdrawal reason.
	 * @return true|WP_Error
	 */
	public static function request_account_withdrawal( $user_id, $reason = '' ) {
		$user_id = (int) $user_id;
		$user    = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error( 'invalid_user', '유효하지 않은 사용자입니다.' );
		}

		if ( user_can( $user, 'manage_options' ) ) {
			return new WP_Error( 'admin_cannot_withdraw', '관리자 계정은 마이페이지에서 바로 탈퇴할 수 없습니다.' );
		}

		update_user_meta( $user_id, '_mm_sl_withdrawal_status', 'withdrawn' );
		update_user_meta( $user_id, '_mm_sl_withdrawal_requested_at', current_time( 'mysql' ) );
		if ( ! empty( $reason ) ) {
			update_user_meta( $user_id, '_mm_sl_withdrawal_reason', sanitize_textarea_field( $reason ) );
		}

		/**
		 * Fires when a user requests Manmulro account withdrawal (#21).
		 *
		 * Connected services (e.g., Manmulro Invitation) can hook into this action
		 * to archive/unpublish invitations according to their data retention policy
		 * without hardcoding immediate data deletion inside Social Login.
		 *
		 * @param int     $user_id WordPress User ID.
		 * @param WP_User $user    WordPress User object.
		 */
		do_action( 'manmulro_user_withdrawal_requested', $user_id, $user );

		wp_logout();

		return true;
	}
}
