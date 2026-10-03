<?php
/**
 * WordPress Admin Settings (`설정 -> 만물로 로그인`)
 *
 * Implements sections #17, #18, #19 of the MANMULRO SOCIAL LOGIN specification.
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Admin_Settings {

	/**
	 * Auth controller.
	 *
	 * @var MM_SL_Auth
	 */
	private static $auth;

	/**
	 * Initialize admin menu and settings handler.
	 *
	 * @param MM_SL_Auth $auth Auth controller.
	 */
	public static function init( MM_SL_Auth $auth ) {
		self::$auth = $auth;
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_save' ) );
	}

	/**
	 * Add submenu under Settings (`설정 -> 만물로 로그인`) (#17).
	 */
	public static function register_menu() {
		add_options_page(
			'만물로 로그인 설정',
			'만물로 로그인',
			'manage_options',
			'manmulro-social-login',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Handle settings form submission with capability + nonce verification (#19).
	 */
	public static function handle_save() {
		if ( ! isset( $_POST['mm_sl_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '권한이 없습니다.' );
		}

		check_admin_referer( 'mm_sl_save_settings_action', 'mm_sl_settings_nonce' );

		$current = MM_SL_Security::get_settings();

		$updated = array(
			'enable_kakao'           => ! empty( $_POST['enable_kakao'] ) ? 1 : 0,
			'enable_naver'           => ! empty( $_POST['enable_naver'] ) ? 1 : 0,
			'enable_google'          => ! empty( $_POST['enable_google'] ) ? 1 : 0,
			'enable_email'           => ! empty( $_POST['enable_email'] ) ? 1 : 0,
			'enable_signup'          => ! empty( $_POST['enable_signup'] ) ? 1 : 0,
			'enable_account_link'    => ! empty( $_POST['enable_account_link'] ) ? 1 : 0,
			'enable_return_redirect' => ! empty( $_POST['enable_return_redirect'] ) ? 1 : 0,
			'withdrawal_policy'      => isset( $_POST['withdrawal_policy'] ) ? sanitize_key( wp_unslash( $_POST['withdrawal_policy'] ) ) : 'retain_service_data',
		);

		foreach ( array( 'kakao', 'naver', 'google' ) as $prov ) {
			$id_key     = "{$prov}_client_id";
			$secret_key = "{$prov}_client_secret";
			$cb_key     = "{$prov}_callback_url";

			$updated[ $id_key ] = isset( $_POST[ $id_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $id_key ] ) ) : '';
			$updated[ $cb_key ] = isset( $_POST[ $cb_key ] ) ? esc_url_raw( wp_unslash( $_POST[ $cb_key ] ) ) : '';

			// Keep existing secret if input left blank or masked (#18, #19).
			$raw_secret = isset( $_POST[ $secret_key ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $secret_key ] ) ) ) : '';
			if ( '' === $raw_secret || false !== strpos( $raw_secret, '***' ) ) {
				$updated[ $secret_key ] = isset( $current[ $secret_key ] ) ? $current[ $secret_key ] : '';
			} else {
				$updated[ $secret_key ] = $raw_secret;
			}
		}

		update_option( 'mm_sl_settings', $updated );

		add_settings_error(
			'mm_sl_messages',
			'mm_sl_saved',
			'만물로 로그인 설정이 저장되었습니다.',
			'updated'
		);
	}

	/**
	 * Render Settings Page (#17, #18).
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings  = MM_SL_Security::get_settings();
		$providers = self::$auth->get_providers();
		?>
		<div class="wrap">
			<h1>만물로 통합 소셜 로그인 설정 (Manmulro Social Login)</h1>
			<p class="description">
				만물로 전체 서비스(초대장, 커뮤니티, 모임 등)가 공통으로 사용하는 통합 회원 인증 설정입니다.
			</p>

			<?php settings_errors( 'mm_sl_messages' ); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'mm_sl_save_settings_action', 'mm_sl_settings_nonce' ); ?>

				<h2 class="title">1. 로그인 및 정책 ON / OFF</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Kakao Login</th>
						<td>
							<label><input type="checkbox" name="enable_kakao" value="1" <?php checked( ! empty( $settings['enable_kakao'] ) ); ?> /> 사용 (ON)</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Naver Login</th>
						<td>
							<label><input type="checkbox" name="enable_naver" value="1" <?php checked( ! empty( $settings['enable_naver'] ) ); ?> /> 사용 (ON)</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Google Login</th>
						<td>
							<label><input type="checkbox" name="enable_google" value="1" <?php checked( ! empty( $settings['enable_google'] ) ); ?> /> 사용 (ON)</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Email Login</th>
						<td>
							<label><input type="checkbox" name="enable_email" value="1" <?php checked( ! empty( $settings['enable_email'] ) ); ?> /> 이메일/비밀번호 로그인 허용</label>
						</td>
					</tr>
					<tr>
						<th scope="row">신규 회원가입</th>
						<td>
							<label><input type="checkbox" name="enable_signup" value="1" <?php checked( ! empty( $settings['enable_signup'] ) ); ?> /> 소셜/이메일 신규 회원가입 허용</label>
						</td>
					</tr>
					<tr>
						<th scope="row">계정 연결 (Account Linking)</th>
						<td>
							<label><input type="checkbox" name="enable_account_link" value="1" <?php checked( ! empty( $settings['enable_account_link'] ) ); ?> /> 마이페이지에서 복수 소셜 계정 연결 허용</label>
						</td>
					</tr>
					<tr>
						<th scope="row">로그인 후 원래 페이지 복귀</th>
						<td>
							<label><input type="checkbox" name="enable_return_redirect" value="1" <?php checked( ! empty( $settings['enable_return_redirect'] ) ); ?> /> 허용된 내부 URL(`?redirect=`)로 자동 복귀</label>
						</td>
					</tr>
				</table>

				<hr />

				<h2 class="title">2. OAuth Provider 설정 (Kakao / Naver / Google)</h2>
				<p class="description">
					보안을 위해 운영 환경에서는 <code>wp-config.php</code> 상수 또는 서버 환경변수(예: <code>MM_SL_KAKAO_CLIENT_ID</code>, <code>MM_SL_KAKAO_CLIENT_SECRET</code>) 사용을 권장합니다.
				</p>

				<?php foreach ( array( 'kakao' => 'Kakao (카카오)', 'naver' => 'Naver (네이버)', 'google' => 'Google (구글)' ) as $slug => $title ) : ?>
					<?php
					$prov_obj      = isset( $providers[ $slug ] ) ? $providers[ $slug ] : null;
					$default_cb    = $prov_obj ? $prov_obj->get_callback_url() : home_url( "/?mm_sl_action=callback&provider={$slug}" );
					$id_from_env   = MM_SL_Security::is_credential_from_env( $slug, 'client_id' );
					$sec_from_env  = MM_SL_Security::is_credential_from_env( $slug, 'client_secret' );
					$masked_secret = MM_SL_Security::mask_secret( isset( $settings[ "{$slug}_client_secret" ] ) ? $settings[ "{$slug}_client_secret" ] : '' );
					?>
					<h3><?php echo esc_html( $title ); ?></h3>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $slug ); ?>_client_id">Client ID (REST API Key)</label></th>
							<td>
								<input type="text"
								       id="<?php echo esc_attr( $slug ); ?>_client_id"
								       name="<?php echo esc_attr( $slug ); ?>_client_id"
								       class="regular-text"
								       value="<?php echo esc_attr( $settings[ "{$slug}_client_id" ] ); ?>"
								       <?php disabled( $id_from_env ); ?> />
								<?php if ( $id_from_env ) : ?>
									<p class="description"><code>wp-config.php</code> 또는 환경변수에서 로드 중입니다.</p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $slug ); ?>_client_secret">Client Secret</label></th>
							<td>
								<input type="password"
								       id="<?php echo esc_attr( $slug ); ?>_client_secret"
								       name="<?php echo esc_attr( $slug ); ?>_client_secret"
								       class="regular-text"
								       value=""
								       placeholder="<?php echo ! empty( $masked_secret ) ? esc_attr( '저장됨: ' . $masked_secret . ' (변경 시에만 입력)' ) : 'Client Secret 입력'; ?>"
								       autocomplete="new-password"
								       <?php disabled( $sec_from_env ); ?> />
								<?php if ( $sec_from_env ) : ?>
									<p class="description"><code>wp-config.php</code> 또는 환경변수에서 안전하게 로드 중입니다.</p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $slug ); ?>_callback_url">Callback / Redirect URI</label></th>
							<td>
								<input type="url"
								       id="<?php echo esc_attr( $slug ); ?>_callback_url"
								       name="<?php echo esc_attr( $slug ); ?>_callback_url"
								       class="large-text code"
								       value="<?php echo esc_attr( $settings[ "{$slug}_callback_url" ] ); ?>"
								       placeholder="<?php echo esc_attr( $default_cb ); ?>" />
								<p class="description">
									비워두면 기본 콜백 URL이 사용됩니다: <code><?php echo esc_html( $default_cb ); ?></code>
								</p>
							</td>
						</tr>
					</table>
				<?php endforeach; ?>

				<p class="submit">
					<button type="submit" name="mm_sl_save_settings" value="1" class="button button-primary">설정 저장</button>
				</p>
			</form>
		</div>
		<?php
	}
}
