<?php
/**
 * Unified My Account Shortcode (`[manmulro_my_account]`)
 *
 * Implements sections #13, #14, #15, #21 of the MANMULRO SOCIAL LOGIN specification:
 * - 내 정보 (프로필, 이메일, 비밀번호)
 * - 로그인 관리 / 연결된 소셜 계정 (Kakao, Naver, Google 연결 / 연결 해제 + 마지막 로그인 수단 보호)
 * - 내 서비스 (내 초대장 및 향후 만물로 서비스 확장 훅)
 * - 회원 탈퇴 요청
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Account_Shortcode {

	/**
	 * Auth controller.
	 *
	 * @var MM_SL_Auth
	 */
	private static $auth;

	/**
	 * Account linker.
	 *
	 * @var MM_SL_Account_Linker
	 */
	private static $linker;

	/**
	 * Register shortcode.
	 *
	 * @param MM_SL_Auth           $auth   Auth controller.
	 * @param MM_SL_Account_Linker $linker Account linker.
	 */
	public static function init( MM_SL_Auth $auth, MM_SL_Account_Linker $linker ) {
		self::$auth   = $auth;
		self::$linker = $linker;
		add_shortcode( 'manmulro_my_account', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render unified My Account dashboard.
	 *
	 * @return string
	 */
	public static function render() {
		if ( ! is_user_logged_in() ) {
			$login_url = MM_SL_Redirect::get_login_url( home_url( '/my-account/' ) );
			ob_start();
			?>
			<div class="mm-sl-card">
				<h2 class="mm-sl-title">로그인이 필요합니다</h2>
				<p class="mm-sl-subtitle">마이페이지를 이용하시려면 먼저 로그인해주세요.</p>
				<a class="mm-sl-submit-btn" href="<?php echo esc_url( $login_url ); ?>">만물로 로그인하기</a>
			</div>
			<?php
			return ob_get_clean();
		}

		$user         = wp_get_current_user();
		$user_id      = $user->ID;
		$linked       = self::$linker->get_linked_accounts( $user_id );
		$has_password = MM_SL_User::user_has_password_login( $user_id );
		$settings     = MM_SL_Security::get_settings();
		$notice       = isset( $_GET['mm_sl_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['mm_sl_notice'] ) ) : '';
		$notice_type  = isset( $_GET['mm_sl_type'] ) ? sanitize_key( wp_unslash( $_GET['mm_sl_type'] ) ) : 'info';

		// Total login methods count for UI feedback (#14).
		$total_methods = count( $linked ) + ( $has_password ? 1 : 0 );

		ob_start();
		?>
		<div class="mm-sl-account-wrap">
			<div class="mm-sl-account-header">
				<div class="mm-sl-account-user">
					<img class="mm-sl-avatar" src="<?php echo esc_url( get_avatar_url( $user_id, array( 'size' => 96 ) ) ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>" />
					<div>
						<span class="mm-sl-brand-badge">MANMULRO ACCOUNT</span>
						<h2 class="mm-sl-account-name"><?php echo esc_html( $user->display_name ); ?> 님</h2>
						<p class="mm-sl-account-email"><?php echo esc_html( $user->user_email ); ?></p>
					</div>
				</div>
				<a class="mm-sl-logout-link" href="<?php echo esc_url( wp_logout_url( home_url( '/login/' ) ) ); ?>">로그아웃</a>
			</div>

			<?php if ( ! empty( $notice ) ) : ?>
				<div class="mm-sl-notice mm-sl-notice--<?php echo esc_attr( $notice_type ); ?>" role="alert">
					<?php echo esc_html( $notice ); ?>
				</div>
			<?php endif; ?>

			<div class="mm-sl-account-grid">
				<!-- Section 1: 내 정보 (프로필 / 이메일 / 비밀번호) -->
				<section class="mm-sl-panel">
					<h3 class="mm-sl-panel-title">내 정보 · 프로필 및 비밀번호</h3>
					<form method="post" action="<?php echo esc_url( home_url( '/my-account/' ) ); ?>" class="mm-sl-form">
						<input type="hidden" name="mm_sl_action" value="update_account" />
						<?php wp_nonce_field( 'mm_sl_update_account', 'mm_sl_account_nonce' ); ?>

						<div class="mm-sl-field">
							<label for="mm_acc_display_name">표시 이름</label>
							<input type="text" id="mm_acc_display_name" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required />
						</div>

						<div class="mm-sl-field">
							<label for="mm_acc_user_email">이메일</label>
							<input type="email" id="mm_acc_user_email" name="user_email" value="<?php echo esc_attr( $user->user_email ); ?>" required />
						</div>

						<div class="mm-sl-field">
							<label for="mm_acc_new_password">
								비밀번호 <?php echo $has_password ? '변경 (변경 시에만 입력)' : '설정 (이메일 로그인 추가)'; ?>
							</label>
							<input type="password" id="mm_acc_new_password" name="new_password" minlength="8" autocomplete="new-password" placeholder="새 비밀번호 (8자 이상)" />
						</div>

						<button type="submit" class="mm-sl-submit-btn">내 정보 저장</button>
					</form>
				</section>

				<!-- Section 2: 로그인 관리 / 연결된 소셜 계정 (#13, #14) -->
				<section class="mm-sl-panel">
					<h3 class="mm-sl-panel-title">로그인 관리 · 연결된 소셜 계정</h3>
					<p class="mm-sl-panel-desc">하나의 만물로 계정에 여러 소셜 로그인 수단을 연결할 수 있습니다.</p>

					<ul class="mm-sl-provider-list">
						<?php foreach ( self::$auth->get_providers() as $provider_id => $provider ) : ?>
							<?php
							$is_connected = isset( $linked[ $provider_id ] );
							$can_unlink   = $is_connected && ( $total_methods > 1 );
							?>
							<li class="mm-sl-provider-item">
								<div class="mm-sl-provider-info">
									<span class="mm-sl-provider-badge mm-sl-provider-badge--<?php echo esc_attr( $provider_id ); ?>">
										<?php echo esc_html( $provider->get_label() ); ?>
									</span>
									<?php if ( $is_connected ) : ?>
										<span class="mm-sl-status mm-sl-status--connected">연결됨</span>
									<?php else : ?>
										<span class="mm-sl-status mm-sl-status--disconnected">연결 안 됨</span>
									<?php endif; ?>
								</div>

								<div class="mm-sl-provider-action">
									<?php if ( $is_connected ) : ?>
										<?php if ( $can_unlink ) : ?>
											<a href="<?php echo esc_url( self::$auth->get_unlink_url( $provider_id ) ); ?>"
											   class="mm-sl-unlink-btn"
											   data-can-unlink="1">연결 해제</a>
										<?php else : ?>
											<button type="button"
											        class="mm-sl-unlink-btn mm-sl-unlink-btn--disabled"
											        data-can-unlink="0"
											        title="다른 로그인 방법을 먼저 연결해주세요.">연결 해제</button>
										<?php endif; ?>
									<?php elseif ( ! empty( $settings['enable_account_link'] ) && $provider->is_enabled() ) : ?>
										<a href="<?php echo esc_url( self::$auth->get_start_url( $provider_id, home_url( '/my-account/' ), 'link' ) ); ?>"
										   class="mm-sl-connect-btn">연결</a>
									<?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>

						<li class="mm-sl-provider-item">
							<div class="mm-sl-provider-info">
								<span class="mm-sl-provider-badge mm-sl-provider-badge--email">Email</span>
								<span class="mm-sl-status <?php echo $has_password ? 'mm-sl-status--connected' : 'mm-sl-status--disconnected'; ?>">
									<?php echo esc_html( $user->user_email ); ?>
									<?php echo $has_password ? ' (비밀번호 사용 가능)' : ' (비밀번호 미설정)'; ?>
								</span>
							</div>
						</li>
					</ul>

					<?php if ( $total_methods <= 1 ) : ?>
						<p class="mm-sl-hint-box">
							현재 사용 가능한 로그인 수단이 1개뿐입니다. 마지막 로그인 수단은 해제할 수 없으며, 해제하려면 다른 로그인 방법을 먼저 연결해주세요.
						</p>
					<?php endif; ?>
				</section>
			</div>

			<!-- Section 3: 내 서비스 (#15, #16) -->
			<section class="mm-sl-panel mm-sl-panel--full">
				<h3 class="mm-sl-panel-title">내 서비스</h3>
				<div class="mm-sl-services-grid">
					<?php
					/**
					 * Allow independent Manmulro plugins (such as Manmulro Invitation)
					 * to render service cards in My Account without creating a hard dependency (#15, #16).
					 *
					 * @param int $user_id Current WordPress User ID.
					 */
					do_action( 'manmulro_my_account_services', $user_id );

					if ( ! has_action( 'manmulro_my_account_services' ) ) :
						?>
						<div class="mm-sl-service-card">
							<h4>내 초대장</h4>
							<p>세상의 모든 만남을 위한 만물로 초대장을 만들고 관리하세요.</p>
							<a href="<?php echo esc_url( home_url( '/my-invitations/' ) ); ?>" class="mm-sl-secondary-btn">내 초대장 바로가기</a>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<!-- Section 4: 회원 탈퇴 (#21) -->
			<section class="mm-sl-panel mm-sl-panel--danger">
				<details class="mm-sl-withdraw-details">
					<summary>회원 탈퇴 요청</summary>
					<p class="mm-sl-panel-desc">
						탈퇴 요청 시 계정은 즉시 로그아웃되며, 초대장 및 향후 만물로 서비스의 데이터 보존 정책에 따라 안전하게 처리됩니다.
					</p>
					<form method="post" action="<?php echo esc_url( home_url( '/my-account/' ) ); ?>" class="mm-sl-form mm-sl-withdraw-form">
						<input type="hidden" name="mm_sl_action" value="withdraw_account" />
						<?php wp_nonce_field( 'mm_sl_withdraw_account', 'mm_sl_withdraw_nonce' ); ?>
						<div class="mm-sl-field">
							<label for="mm_withdraw_reason">탈퇴 사유 (선택)</label>
							<textarea id="mm_withdraw_reason" name="withdraw_reason" rows="2" placeholder="서비스 개선을 위해 의견을 남겨주세요."></textarea>
						</div>
						<button type="submit" class="mm-sl-danger-btn">회원 탈퇴 신청</button>
					</form>
				</details>
			</section>
		</div>
		<?php
		return ob_get_clean();
	}
}
