<?php
/**
 * Public Login Shortcode (`[manmulro_login]`)
 *
 * Implements section #10 of the MANMULRO SOCIAL LOGIN specification:
 * - 만물로 로그인
 * - [ 카카오로 시작하기 ]
 * - [ 네이버로 시작하기 ]
 * - [ Google로 계속하기 ]
 * - ──────── 또는 ────────
 * - 이메일 / 비밀번호 로그인 폼
 * - 회원가입 / 비밀번호 찾기 링크
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Login_Shortcode {

	/**
	 * Auth controller.
	 *
	 * @var MM_SL_Auth
	 */
	private static $auth;

	/**
	 * Register shortcode.
	 *
	 * @param MM_SL_Auth $auth Auth controller.
	 */
	public static function init( MM_SL_Auth $auth ) {
		self::$auth = $auth;
		add_shortcode( 'manmulro_login', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render only social login buttons (used on wp-login.php and embedded contexts).
	 *
	 * @param MM_SL_Auth $auth        Auth controller.
	 * @param string     $redirect_to Redirect URL.
	 * @return string
	 */
	public static function render_social_buttons_only( MM_SL_Auth $auth, $redirect_to = '' ) {
		if ( empty( $redirect_to ) ) {
			$redirect_to = MM_SL_Redirect::get_requested_redirect();
		}

		ob_start();
		?>
		<div class="mm-sl-social-buttons">
			<?php foreach ( $auth->get_providers() as $provider ) : ?>
				<?php if ( $provider->is_enabled() ) : ?>
					<a href="<?php echo esc_url( $auth->get_start_url( $provider->get_id(), $redirect_to, 'login' ) ); ?>"
					   class="mm-sl-btn mm-sl-btn--<?php echo esc_attr( $provider->get_id() ); ?>">
						<span class="mm-sl-btn__icon" aria-hidden="true"><?php echo self::get_provider_icon_svg( $provider->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="mm-sl-btn__label"><?php echo esc_html( $provider->get_button_text() ); ?></span>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render full login box (`[manmulro_login]`).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'redirect' => '',
			),
			$atts,
			'manmulro_login'
		);

		$redirect_to = ! empty( $atts['redirect'] )
			? MM_SL_Redirect::sanitize_internal_redirect( $atts['redirect'] )
			: MM_SL_Redirect::get_requested_redirect();

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			ob_start();
			?>
			<div class="mm-sl-card mm-sl-logged-in">
				<h2 class="mm-sl-title">이미 로그인되어 있습니다</h2>
				<p class="mm-sl-subtitle"><strong><?php echo esc_html( $user->display_name ); ?></strong>님으로 로그인된 상태입니다.</p>
				<div class="mm-sl-actions">
					<a class="mm-sl-primary-btn" href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>">마이페이지로 이동</a>
					<a class="mm-sl-secondary-btn" href="<?php echo esc_url( wp_logout_url( home_url( '/login/' ) ) ); ?>">로그아웃</a>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}

		$settings    = MM_SL_Security::get_settings();
		$notice      = isset( $_GET['mm_sl_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['mm_sl_notice'] ) ) : '';
		$notice_type = isset( $_GET['mm_sl_type'] ) ? sanitize_key( wp_unslash( $_GET['mm_sl_type'] ) ) : 'info';

		ob_start();
		?>
		<div class="mm-sl-card">
			<div class="mm-sl-header">
				<span class="mm-sl-brand-badge">MANMULRO</span>
				<h2 class="mm-sl-title">만물로 로그인</h2>
				<p class="mm-sl-subtitle">하나의 만물로 계정으로 초대장 및 모든 서비스를 이용하세요.</p>
			</div>

			<?php if ( ! empty( $notice ) ) : ?>
				<div class="mm-sl-notice mm-sl-notice--<?php echo esc_attr( $notice_type ); ?>" role="alert">
					<?php echo esc_html( $notice ); ?>
				</div>
			<?php endif; ?>

			<?php echo self::render_social_buttons_only( self::$auth, $redirect_to ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( ! empty( $settings['enable_email'] ) ) : ?>
				<div class="mm-sl-divider">
					<span>또는</span>
				</div>

				<form method="post" action="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="mm-sl-form">
					<input type="hidden" name="mm_sl_action" value="email_login" />
					<input type="hidden" name="redirect" value="<?php echo esc_attr( $redirect_to ); ?>" />
					<?php wp_nonce_field( 'mm_sl_email_login', 'mm_sl_login_nonce' ); ?>

					<div class="mm-sl-field">
						<label for="mm_sl_user_email">이메일</label>
						<input type="text" id="mm_sl_user_email" name="user_email" required autocomplete="username" placeholder="user@example.com" />
					</div>

					<div class="mm-sl-field">
						<label for="mm_sl_user_password">비밀번호</label>
						<input type="password" id="mm_sl_user_password" name="user_password" required autocomplete="current-password" placeholder="비밀번호를 입력하세요" />
					</div>

					<div class="mm-sl-field mm-sl-field--inline">
						<label class="mm-sl-checkbox">
							<input type="checkbox" name="remember" value="1" checked />
							<span>로그인 상태 유지</span>
						</label>
					</div>

					<button type="submit" class="mm-sl-submit-btn">로그인</button>
				</form>
			<?php endif; ?>

			<div class="mm-sl-footer-links">
				<?php if ( ! empty( $settings['enable_signup'] ) ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'redirect', rawurlencode( $redirect_to ), home_url( '/signup/' ) ) ); ?>">회원가입</a>
					<span class="mm-sl-dot">·</span>
				<?php endif; ?>
				<a href="<?php echo esc_url( wp_lostpassword_url( $redirect_to ) ); ?>">비밀번호 찾기</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Inline SVG icons for Kakao, Naver, Google.
	 *
	 * @param string $provider_id Provider slug.
	 * @return string
	 */
	public static function get_provider_icon_svg( $provider_id ) {
		switch ( $provider_id ) {
			case 'kakao':
				return '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3C6.477 3 2 6.477 2 10.765c0 2.775 1.86 5.21 4.66 6.58-.15.54-.96 3.48-1 3.64-.05.2.07.2.15.15.06-.04 2.52-1.71 3.54-2.4.86.13 1.74.2 2.65.2 5.523 0 10-3.477 10-7.765S17.523 3 12 3z"/></svg>';
			case 'naver':
				return '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16.273 12.845 7.376 0H0v24h7.727V11.155L16.624 24H24V0h-7.727v12.845z"/></svg>';
			case 'google':
				return '<svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/><path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62H1.29C.47 8.24 0 10.06 0 12s.47 3.76 1.29 5.38l3.98-3.09z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09c.95-2.85 3.6-4.96 6.73-4.96z"/></svg>';
			default:
				return '';
		}
	}
}
