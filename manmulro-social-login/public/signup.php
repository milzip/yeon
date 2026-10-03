<?php
/**
 * Public Signup Shortcode (`[manmulro_signup]`)
 *
 * Implements minimal personal information signup (#11, #20).
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Signup_Shortcode {

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
		add_shortcode( 'manmulro_signup', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render signup box.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		if ( is_user_logged_in() ) {
			return MM_SL_Login_Shortcode::render( $atts );
		}

		$settings    = MM_SL_Security::get_settings();
		$redirect_to = MM_SL_Redirect::get_requested_redirect();
		$notice      = isset( $_GET['mm_sl_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['mm_sl_notice'] ) ) : '';
		$notice_type = isset( $_GET['mm_sl_type'] ) ? sanitize_key( wp_unslash( $_GET['mm_sl_type'] ) ) : 'info';

		ob_start();
		?>
		<div class="mm-sl-card">
			<div class="mm-sl-header">
				<span class="mm-sl-brand-badge">MANMULRO</span>
				<h2 class="mm-sl-title">만물로 회원가입</h2>
				<p class="mm-sl-subtitle">소셜 계정으로 3초 만에 시작하거나 이메일로 가입하세요.</p>
			</div>

			<?php if ( ! empty( $notice ) ) : ?>
				<div class="mm-sl-notice mm-sl-notice--<?php echo esc_attr( $notice_type ); ?>" role="alert">
					<?php echo esc_html( $notice ); ?>
				</div>
			<?php endif; ?>

			<?php echo MM_SL_Login_Shortcode::render_social_buttons_only( self::$auth, $redirect_to ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( ! empty( $settings['enable_email'] ) && ! empty( $settings['enable_signup'] ) ) : ?>
				<div class="mm-sl-divider">
					<span>또는 이메일로 가입</span>
				</div>

				<form method="post" action="<?php echo esc_url( home_url( '/signup/' ) ); ?>" class="mm-sl-form">
					<input type="hidden" name="mm_sl_action" value="email_signup" />
					<input type="hidden" name="redirect" value="<?php echo esc_attr( $redirect_to ); ?>" />
					<?php wp_nonce_field( 'mm_sl_email_signup', 'mm_sl_signup_nonce' ); ?>

					<div class="mm-sl-field">
						<label for="mm_sl_signup_name">표시 이름 (닉네임)</label>
						<input type="text" id="mm_sl_signup_name" name="display_name" required placeholder="홍길동" />
					</div>

					<div class="mm-sl-field">
						<label for="mm_sl_signup_email">이메일</label>
						<input type="email" id="mm_sl_signup_email" name="user_email" required autocomplete="email" placeholder="user@example.com" />
					</div>

					<div class="mm-sl-field">
						<label for="mm_sl_signup_password">비밀번호 (8자 이상)</label>
						<input type="password" id="mm_sl_signup_password" name="user_password" required minlength="8" autocomplete="new-password" placeholder="8자 이상 입력해주세요" />
					</div>

					<p class="mm-sl-privacy-note">
						만물로는 서비스 제공에 필요한 최소한의 정보(이름, 이메일)만 수집하며 생년월일·성별 등 불필요한 개인정보를 요구하지 않습니다.
					</p>

					<button type="submit" class="mm-sl-submit-btn">회원가입 완료</button>
				</form>
			<?php endif; ?>

			<div class="mm-sl-footer-links">
				<span>이미 계정이 있으신가요?</span>
				<a href="<?php echo esc_url( add_query_arg( 'redirect', rawurlencode( $redirect_to ), home_url( '/login/' ) ) ); ?>">로그인</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
