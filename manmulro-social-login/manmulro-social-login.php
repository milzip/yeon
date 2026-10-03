<?php
/**
 * Plugin Name:       Manmulro Social Login (만물로 통합 소셜 로그인)
 * Plugin URI:        https://manmulro.com
 * Description:       만물로 전체 서비스를 위한 통합 회원 인증 시스템 (Kakao, Naver, Google 및 이메일 로그인, 계정 연결, 마이페이지 제공)
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            MANMULRO
 * Text Domain:       manmulro-social-login
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MM_SL_VERSION', '1.0.0' );
define( 'MM_SL_PLUGIN_FILE', __FILE__ );
define( 'MM_SL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MM_SL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once MM_SL_PLUGIN_DIR . 'includes/class-provider-interface.php';
require_once MM_SL_PLUGIN_DIR . 'includes/class-session.php';
require_once MM_SL_PLUGIN_DIR . 'includes/class-security.php';
require_once MM_SL_PLUGIN_DIR . 'includes/class-redirect.php';
require_once MM_SL_PLUGIN_DIR . 'includes/class-account-linker.php';
require_once MM_SL_PLUGIN_DIR . 'includes/class-user.php';
require_once MM_SL_PLUGIN_DIR . 'includes/class-auth.php';

require_once MM_SL_PLUGIN_DIR . 'providers/kakao/class-kakao-provider.php';
require_once MM_SL_PLUGIN_DIR . 'providers/naver/class-naver-provider.php';
require_once MM_SL_PLUGIN_DIR . 'providers/google/class-google-provider.php';

require_once MM_SL_PLUGIN_DIR . 'public/login.php';
require_once MM_SL_PLUGIN_DIR . 'public/signup.php';
require_once MM_SL_PLUGIN_DIR . 'public/account.php';

if ( is_admin() ) {
	require_once MM_SL_PLUGIN_DIR . 'admin/settings.php';
	require_once MM_SL_PLUGIN_DIR . 'admin/users.php';
}

/**
 * Main plugin bootstrap class.
 */
final class Manmulro_Social_Login {

	/**
	 * Singleton instance.
	 *
	 * @var Manmulro_Social_Login|null
	 */
	private static $instance = null;

	/**
	 * Auth controller.
	 *
	 * @var MM_SL_Auth
	 */
	public $auth;

	/**
	 * Account linker.
	 *
	 * @var MM_SL_Account_Linker
	 */
	public $linker;

	/**
	 * Get singleton instance.
	 *
	 * @return Manmulro_Social_Login
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->linker = new MM_SL_Account_Linker();
		$this->auth   = new MM_SL_Auth( $this->linker );

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'login_form', array( $this, 'render_wp_login_buttons' ) );
	}

	/**
	 * Initialize rewrite rules and components.
	 */
	public function init() {
		$this->auth->register_default_providers();
		$this->auth->register_routes();

		MM_SL_Login_Shortcode::init( $this->auth );
		MM_SL_Signup_Shortcode::init( $this->auth );
		MM_SL_Account_Shortcode::init( $this->auth, $this->linker );

		if ( is_admin() ) {
			MM_SL_Admin_Settings::init( $this->auth );
			MM_SL_Admin_Users::init( $this->linker );
		}
	}

	/**
	 * Enqueue frontend styles and scripts.
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'manmulro-social-login',
			MM_SL_PLUGIN_URL . 'assets/css/social-login.css',
			array(),
			MM_SL_VERSION
		);

		wp_enqueue_script(
			'manmulro-social-login',
			MM_SL_PLUGIN_URL . 'assets/js/social-login.js',
			array(),
			MM_SL_VERSION,
			true
		);

		wp_localize_script(
			'manmulro-social-login',
			'mmSocialLogin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mm_sl_ajax_nonce' ),
				'i18n'    => array(
					'confirmUnlink'  => '해당 소셜 계정 연결을 해제하시겠습니까?',
					'confirmWithdraw'=> '정말로 회원 탈퇴를 요청하시겠습니까?',
					'lastMethodErr'  => '다른 로그인 방법을 먼저 연결해주세요.',
				),
			)
		);
	}

	/**
	 * Render social login buttons on default wp-login.php form.
	 */
	public function render_wp_login_buttons() {
		$redirect = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
		echo MM_SL_Login_Shortcode::render_social_buttons_only( $this->auth, $redirect ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Plugin activation hook: create DB table and default pages.
	 */
	public static function activate() {
		MM_SL_Account_Linker::create_table();

		// Default settings.
		if ( false === get_option( 'mm_sl_settings' ) ) {
			update_option(
				'mm_sl_settings',
				array(
					'enable_kakao'          => 1,
					'enable_naver'          => 1,
					'enable_google'         => 1,
					'enable_email'          => 1,
					'enable_signup'         => 1,
					'enable_account_link'   => 1,
					'enable_return_redirect'=> 1,
					'withdrawal_policy'     => 'retain_service_data',
				)
			);
		}

		// Create standard pages if they do not exist.
		$pages = array(
			'login'      => array(
				'title'   => '만물로 로그인',
				'content' => '[manmulro_login]',
			),
			'signup'     => array(
				'title'   => '만물로 회원가입',
				'content' => '[manmulro_signup]',
			),
			'my-account' => array(
				'title'   => '마이페이지',
				'content' => '[manmulro_my_account]',
			),
		);

		foreach ( $pages as $slug => $page_data ) {
			$existing = get_page_by_path( $slug );
			if ( ! $existing ) {
				wp_insert_post(
					array(
						'post_title'   => $page_data['title'],
						'post_name'    => $slug,
						'post_content' => $page_data['content'],
						'post_status'  => 'publish',
						'post_type'    => 'page',
					)
				);
			}
		}

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}

register_activation_hook( __FILE__, array( 'Manmulro_Social_Login', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Manmulro_Social_Login', 'deactivate' ) );

/**
 * Helper accessor for Manmulro_Social_Login instance.
 *
 * @return Manmulro_Social_Login
 */
function manmulro_social_login() {
	return Manmulro_Social_Login::instance();
}

manmulro_social_login();
