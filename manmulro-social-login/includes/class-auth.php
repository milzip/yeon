<?php
/**
 * Core Authentication Controller & Provider Registry
 *
 * Implements sections #4, #8, #9, #13, #19, #22 of the MANMULRO SOCIAL LOGIN specification.
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Auth {

	/**
	 * Registered providers keyed by slug.
	 *
	 * @var array<string, MM_SL_Provider_Interface>
	 */
	private $providers = array();

	/**
	 * Account linker.
	 *
	 * @var MM_SL_Account_Linker
	 */
	private $linker;

	/**
	 * Constructor.
	 *
	 * @param MM_SL_Account_Linker $linker Account linker instance.
	 */
	public function __construct( MM_SL_Account_Linker $linker ) {
		$this->linker = $linker;

		add_action( 'template_redirect', array( $this, 'handle_requests' ), 1 );
		add_action( 'delete_user', array( $this->linker, 'delete_all_for_user' ) );
		add_filter( 'get_avatar_url', array( $this, 'filter_avatar_url' ), 10, 3 );
	}

	/**
	 * Register built-in V1 providers (Kakao, Naver, Google) and allow external extension (#22).
	 */
	public function register_default_providers() {
		$this->register_provider( new MM_SL_Kakao_Provider() );
		$this->register_provider( new MM_SL_Naver_Provider() );
		$this->register_provider( new MM_SL_Google_Provider() );

		/**
		 * Allow registering future OAuth/OIDC providers (Apple, LINE, Facebook, etc.)
		 * without modifying Social Login Core (#22).
		 *
		 * @param MM_SL_Auth $auth Auth controller instance.
		 */
		do_action( 'manmulro_register_social_providers', $this );
	}

	/**
	 * Register a provider implementing MM_SL_Provider_Interface.
	 *
	 * @param MM_SL_Provider_Interface $provider Provider instance.
	 */
	public function register_provider( MM_SL_Provider_Interface $provider ) {
		$this->providers[ $provider->get_id() ] = $provider;
	}

	/**
	 * Get a specific provider by ID.
	 *
	 * @param string $provider_id Provider slug.
	 * @return MM_SL_Provider_Interface|null
	 */
	public function get_provider( $provider_id ) {
		$key = sanitize_key( $provider_id );
		return isset( $this->providers[ $key ] ) ? $this->providers[ $key ] : null;
	}

	/**
	 * Return all registered providers.
	 *
	 * @return array<string, MM_SL_Provider_Interface>
	 */
	public function get_providers() {
		return $this->providers;
	}

	/**
	 * Register rewrite rules for clean OAuth URLs (`/mm-auth/{provider}` and `/mm-auth/{provider}/callback`).
	 */
	public function register_routes() {
		add_rewrite_rule(
			'^mm-auth/([a-z0-9_-]+)/callback/?$',
			'index.php?mm_sl_action=callback&provider=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^mm-auth/([a-z0-9_-]+)/?$',
			'index.php?mm_sl_action=authorize&provider=$matches[1]',
			'top'
		);
		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = 'mm_sl_action';
				$vars[] = 'provider';
				return $vars;
			}
		);
	}

	/**
	 * Build URL to start social login or account linking flow.
	 *
	 * @param string $provider_id Provider slug.
	 * @param string $redirect_to Internal URL to return to after login.
	 * @param string $mode        'login' or 'link'.
	 * @return string
	 */
	public function get_start_url( $provider_id, $redirect_to = '', $mode = 'login' ) {
		$args = array(
			'mm_sl_action' => 'authorize',
			'provider'     => sanitize_key( $provider_id ),
			'mode'         => ( 'link' === $mode ) ? 'link' : 'login',
		);

		if ( ! empty( $redirect_to ) ) {
			$args['redirect'] = $redirect_to;
		}

		if ( 'link' === $mode ) {
			$args['_wpnonce'] = wp_create_nonce( 'mm_sl_link_' . $provider_id );
		}

		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * Build URL to unlink a connected social provider.
	 *
	 * @param string $provider_id Provider slug.
	 * @return string
	 */
	public function get_unlink_url( $provider_id ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'mm_sl_action' => 'unlink',
					'provider'     => sanitize_key( $provider_id ),
				),
				home_url( '/my-account/' )
			),
			'mm_sl_unlink_' . $provider_id
		);
	}

	/**
	 * Dispatch incoming OAuth start, callback, unlink, email login, signup, and account actions.
	 */
	public function handle_requests() {
		$action = get_query_var( 'mm_sl_action' );
		if ( empty( $action ) && isset( $_REQUEST['mm_sl_action'] ) ) {
			$action = sanitize_key( wp_unslash( $_REQUEST['mm_sl_action'] ) );
		}

		if ( empty( $action ) ) {
			return;
		}

		switch ( $action ) {
			case 'authorize':
				$this->handle_authorize();
				break;
			case 'callback':
				$this->handle_callback();
				break;
			case 'unlink':
				$this->handle_unlink();
				break;
			case 'email_login':
				$this->handle_email_login();
				break;
			case 'email_signup':
				$this->handle_email_signup();
				break;
			case 'update_account':
				$this->handle_update_account();
				break;
			case 'withdraw_account':
				$this->handle_withdraw_account();
				break;
		}
	}

	/**
	 * Step 1 of OAuth Flow: Redirect user to Provider Authorization URL (#8).
	 */
	private function handle_authorize() {
		$provider_id = get_query_var( 'provider' );
		if ( empty( $provider_id ) && isset( $_GET['provider'] ) ) {
			$provider_id = sanitize_key( wp_unslash( $_GET['provider'] ) );
		}

		$provider = $this->get_provider( $provider_id );
		if ( ! $provider || ! $provider->is_enabled() ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', '선택한 소셜 로그인 수단이 비활성화되어 있습니다.' );
		}

		$mode = isset( $_GET['mode'] ) && 'link' === $_GET['mode'] ? 'link' : 'login';
		if ( 'link' === $mode ) {
			if ( ! is_user_logged_in() ) {
				$this->redirect_with_notice( home_url( '/login/' ), 'error', '계정 연결을 위해 먼저 로그인해주세요.' );
			}
			$settings = MM_SL_Security::get_settings();
			if ( empty( $settings['enable_account_link'] ) ) {
				$this->redirect_with_notice( home_url( '/my-account/' ), 'error', '소셜 계정 연결 기능이 비활성화되어 있습니다.' );
			}
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mm_sl_link_' . $provider_id ) ) {
				$this->redirect_with_notice( home_url( '/my-account/' ), 'error', '보안 토큰이 만료되었습니다. 다시 시도해주세요.' );
			}
		}

		if ( ! $provider->is_configured() ) {
			$target = ( 'link' === $mode ) ? home_url( '/my-account/' ) : home_url( '/login/' );
			$this->redirect_with_notice(
				$target,
				'error',
				sprintf( '%s 로그인 API 설정(Client ID)이 아직 완료되지 않았습니다. 관리자 설정을 확인해주세요.', $provider->get_label() )
			);
		}

		$redirect_to = MM_SL_Redirect::get_requested_redirect();
		$state       = MM_SL_Security::generate_state();

		MM_SL_Session::store_state(
			$state,
			array(
				'provider'    => $provider->get_id(),
				'mode'        => $mode,
				'user_id'     => is_user_logged_in() ? get_current_user_id() : 0,
				'redirect_to' => $redirect_to,
				'created_at'  => time(),
			)
		);

		$auth_url = $provider->get_authorization_url( $state );
		wp_redirect( $auth_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Step 2 of OAuth Flow: Verify callback state, exchange code, link or login WP User (#8).
	 */
	private function handle_callback() {
		$provider_id = get_query_var( 'provider' );
		if ( empty( $provider_id ) && isset( $_GET['provider'] ) ) {
			$provider_id = sanitize_key( wp_unslash( $_GET['provider'] ) );
		}

		$provider = $this->get_provider( $provider_id );
		if ( ! $provider ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', '알 수 없는 소셜 로그인 제공자입니다.' );
		}

		if ( isset( $_GET['error'] ) ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', '소셜 로그인 동의가 취소되었거나 실패했습니다.' );
		}

		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

		$flow = MM_SL_Session::consume_state( $state );
		if ( is_wp_error( $flow ) ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', $flow->get_error_message() );
		}

		if ( empty( $flow['provider'] ) || $flow['provider'] !== $provider->get_id() ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', '인증 제공자 정보가 일치하지 않습니다.' );
		}

		$profile = $provider->authenticate_code( $code, $state );
		if ( is_wp_error( $profile ) ) {
			$target = ( 'link' === $flow['mode'] ) ? home_url( '/my-account/' ) : home_url( '/login/' );
			$this->redirect_with_notice( $target, 'error', $profile->get_error_message() );
		}

		$provider_user_id = isset( $profile['provider_user_id'] ) ? (string) $profile['provider_user_id'] : '';
		if ( '' === $provider_user_id ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', '소셜 계정의 고유 ID를 가져올 수 없습니다.' );
		}

		// Mode A: Logged-in user linking an additional social account (#13).
		if ( 'link' === $flow['mode'] && is_user_logged_in() ) {
			$current_user_id = get_current_user_id();
			$result          = $this->linker->link_account( $current_user_id, $provider->get_id(), $provider_user_id );
			if ( is_wp_error( $result ) ) {
				$this->redirect_with_notice( home_url( '/my-account/' ), 'error', $result->get_error_message() );
			}
			$this->redirect_with_notice(
				home_url( '/my-account/' ),
				'success',
				sprintf( '%s 계정이 성공적으로 연결되었습니다.', $provider->get_label() )
			);
		}

		// Mode B: Login or Signup via Social Account (#8, #12).
		$linked_user_id = $this->linker->get_user_id_by_provider( $provider->get_id(), $provider_user_id );

		if ( $linked_user_id > 0 ) {
			$login_res = MM_SL_User::login_wordpress_user( $linked_user_id );
			if ( is_wp_error( $login_res ) ) {
				$this->redirect_with_notice( home_url( '/login/' ), 'error', $login_res->get_error_message() );
			}

			$redirect_url = MM_SL_Redirect::sanitize_internal_redirect(
				isset( $flow['redirect_to'] ) ? $flow['redirect_to'] : '',
				home_url( '/my-account/' )
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// No linked WP user yet -> create new user (respecting Duplicate Account Prevention #12).
		$new_user_id = MM_SL_User::create_user_from_social( $profile, $this->linker );
		if ( is_wp_error( $new_user_id ) ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', $new_user_id->get_error_message() );
		}

		MM_SL_User::login_wordpress_user( $new_user_id );

		$redirect_url = MM_SL_Redirect::sanitize_internal_redirect(
			isset( $flow['redirect_to'] ) ? $flow['redirect_to'] : '',
			home_url( '/my-account/' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle unlinking a social provider from My Account (#13, #14).
	 */
	private function handle_unlink() {
		if ( ! is_user_logged_in() ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', '로그인이 필요합니다.' );
		}

		$provider_id = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : '';
		$nonce       = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'mm_sl_unlink_' . $provider_id ) ) {
			$this->redirect_with_notice( home_url( '/my-account/' ), 'error', '보안 검증에 실패했습니다.' );
		}

		$result = $this->linker->unlink_account( get_current_user_id(), $provider_id );
		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( home_url( '/my-account/' ), 'error', $result->get_error_message() );
		}

		$this->redirect_with_notice( home_url( '/my-account/' ), 'success', '소셜 계정 연결이 해제되었습니다.' );
	}

	/**
	 * Handle standard Email / Password login (#10).
	 */
	private function handle_email_login() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		$nonce = isset( $_POST['mm_sl_login_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mm_sl_login_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'mm_sl_email_login' ) ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', '보안 토큰이 유효하지 않습니다.' );
		}

		$settings = MM_SL_Security::get_settings();
		if ( empty( $settings['enable_email'] ) ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', '이메일 로그인이 비활성화되어 있습니다.' );
		}

		if ( ! MM_SL_Security::check_rate_limit( 'email_login', 10, 300 ) ) {
			$this->redirect_with_notice( home_url( '/login/' ), 'error', '로그인 시도가 너무 많습니다. 잠시 후 다시 시도해주세요.' );
		}

		$login_input = isset( $_POST['user_email'] ) ? sanitize_text_field( wp_unslash( $_POST['user_email'] ) ) : '';
		$password    = isset( $_POST['user_password'] ) ? wp_unslash( $_POST['user_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$remember    = ! empty( $_POST['remember'] );
		$redirect_to = MM_SL_Redirect::get_requested_redirect();

		$user = wp_signon(
			array(
				'user_login'    => $login_input,
				'user_password' => $password,
				'remember'      => $remember,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			$this->redirect_with_notice(
				add_query_arg( 'redirect', rawurlencode( $redirect_to ), home_url( '/login/' ) ),
				'error',
				'이메일 또는 비밀번호가 올바르지 않습니다.'
			);
		}

		$withdrawn = get_user_meta( $user->ID, '_mm_sl_withdrawal_status', true );
		if ( 'withdrawn' === $withdrawn ) {
			wp_logout();
			$this->redirect_with_notice( home_url( '/login/' ), 'error', '탈퇴 처리가 진행 중이거나 완료된 계정입니다.' );
		}

		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Handle Email / Password registration (#10, #11).
	 */
	private function handle_email_signup() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		$nonce = isset( $_POST['mm_sl_signup_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mm_sl_signup_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'mm_sl_email_signup' ) ) {
			$this->redirect_with_notice( home_url( '/signup/' ), 'error', '보안 토큰이 유효하지 않습니다.' );
		}

		$settings = MM_SL_Security::get_settings();
		if ( empty( $settings['enable_signup'] ) || empty( $settings['enable_email'] ) ) {
			$this->redirect_with_notice( home_url( '/signup/' ), 'error', '현재 이메일 회원가입이 비활성화되어 있습니다.' );
		}

		if ( ! MM_SL_Security::check_rate_limit( 'email_signup', 5, 600 ) ) {
			$this->redirect_with_notice( home_url( '/signup/' ), 'error', '가입 요청이 너무 많습니다. 잠시 후 다시 시도해주세요.' );
		}

		$email        = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
		$password     = isset( $_POST['user_password'] ) ? wp_unslash( $_POST['user_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$redirect_to  = MM_SL_Redirect::get_requested_redirect();

		if ( empty( $email ) || ! is_email( $email ) ) {
			$this->redirect_with_notice( home_url( '/signup/' ), 'error', '유효한 이메일 주소를 입력해주세요.' );
		}

		if ( email_exists( $email ) ) {
			$this->redirect_with_notice( home_url( '/signup/' ), 'error', '이미 가입된 이메일 주소입니다.' );
		}

		if ( strlen( $password ) < 8 ) {
			$this->redirect_with_notice( home_url( '/signup/' ), 'error', '비밀번호는 8자 이상이어야 합니다.' );
		}

		if ( empty( $display_name ) ) {
			$display_name = strtok( $email, '@' );
		}

		$base_login = sanitize_user( strtok( $email, '@' ), true );
		if ( empty( $base_login ) ) {
			$base_login = 'mm_user';
		}
		$user_login = $base_login;
		$suffix     = 1;
		while ( username_exists( $user_login ) ) {
			$user_login = $base_login . '_' . $suffix;
			++$suffix;
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $user_login,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $display_name,
				'nickname'     => $display_name,
				'role'         => get_option( 'default_role', 'subscriber' ),
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$this->redirect_with_notice( home_url( '/signup/' ), 'error', $user_id->get_error_message() );
		}

		update_user_meta( $user_id, '_mm_sl_password_set', '1' );
		MM_SL_User::login_wordpress_user( $user_id );

		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Handle profile & password update on My Account (#15).
	 */
	private function handle_update_account() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! is_user_logged_in() ) {
			return;
		}

		$nonce = isset( $_POST['mm_sl_account_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mm_sl_account_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'mm_sl_update_account' ) ) {
			$this->redirect_with_notice( home_url( '/my-account/' ), 'error', '보안 검증에 실패했습니다.' );
		}

		$user_id      = get_current_user_id();
		$display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
		$user_email   = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$new_password = isset( $_POST['new_password'] ) ? wp_unslash( $_POST['new_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$update_data = array( 'ID' => $user_id );

		if ( ! empty( $display_name ) ) {
			$update_data['display_name'] = $display_name;
			$update_data['nickname']     = $display_name;
		}

		if ( ! empty( $user_email ) && is_email( $user_email ) ) {
			$existing = email_exists( $user_email );
			if ( $existing && (int) $existing !== $user_id ) {
				$this->redirect_with_notice( home_url( '/my-account/' ), 'error', '이미 다른 계정에서 사용 중인 이메일입니다.' );
			}
			$update_data['user_email'] = $user_email;
		}

		if ( '' !== $new_password ) {
			if ( strlen( $new_password ) < 8 ) {
				$this->redirect_with_notice( home_url( '/my-account/' ), 'error', '새 비밀번호는 최소 8자 이상이어야 합니다.' );
			}
			$update_data['user_pass'] = $new_password;
		}

		$res = wp_update_user( $update_data );
		if ( is_wp_error( $res ) ) {
			$this->redirect_with_notice( home_url( '/my-account/' ), 'error', $res->get_error_message() );
		}

		if ( '' !== $new_password ) {
			update_user_meta( $user_id, '_mm_sl_password_set', '1' );
			MM_SL_User::login_wordpress_user( $user_id );
		}

		$this->redirect_with_notice( home_url( '/my-account/' ), 'success', '내 정보가 저장되었습니다.' );
	}

	/**
	 * Handle account withdrawal request (#21).
	 */
	private function handle_withdraw_account() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! is_user_logged_in() ) {
			return;
		}

		$nonce = isset( $_POST['mm_sl_withdraw_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mm_sl_withdraw_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'mm_sl_withdraw_account' ) ) {
			$this->redirect_with_notice( home_url( '/my-account/' ), 'error', '보안 검증에 실패했습니다.' );
		}

		$reason = isset( $_POST['withdraw_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['withdraw_reason'] ) ) : '';
		$res    = MM_SL_User::request_account_withdrawal( get_current_user_id(), $reason );

		if ( is_wp_error( $res ) ) {
			$this->redirect_with_notice( home_url( '/my-account/' ), 'error', $res->get_error_message() );
		}

		$this->redirect_with_notice( home_url( '/login/' ), 'success', '회원 탈퇴 요청이 정상적으로 접수되었습니다.' );
	}

	/**
	 * Filter WordPress avatar URL to use social profile avatar if present (#11, #20).
	 *
	 * @param string $url         Default avatar URL.
	 * @param mixed  $id_or_email User ID, email, or object.
	 * @param array  $args        Avatar arguments.
	 * @return string
	 */
	public function filter_avatar_url( $url, $id_or_email, $args ) {
		$user_id = 0;
		if ( is_numeric( $id_or_email ) ) {
			$user_id = (int) $id_or_email;
		} elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
			$user_id = (int) $id_or_email->user_id;
		} elseif ( $id_or_email instanceof WP_User ) {
			$user_id = $id_or_email->ID;
		}

		if ( $user_id > 0 ) {
			$custom = get_user_meta( $user_id, '_mm_sl_avatar_url', true );
			if ( ! empty( $custom ) ) {
				return esc_url( $custom );
			}
		}

		return $url;
	}

	/**
	 * Redirect with a query-arg status notice.
	 *
	 * @param string $url     Target URL.
	 * @param string $type    'error' or 'success'.
	 * @param string $message Notice message.
	 */
	private function redirect_with_notice( $url, $type, $message ) {
		$target = add_query_arg(
			array(
				'mm_sl_notice' => rawurlencode( $message ),
				'mm_sl_type'   => sanitize_key( $type ),
			),
			$url
		);
		wp_safe_redirect( $target );
		exit;
	}
}
