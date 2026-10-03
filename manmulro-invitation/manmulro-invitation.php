<?php
/**
 * Plugin Name:       Manmulro Invitation (만물로 범용 초대장 플랫폼)
 * Plugin URI:        https://manmulro.com
 * Description:       세상의 모든 만남을 위한 범용 초대장 플랫폼 V1 — 템플릿·자유 항목(Flexible Fields)·실시간 미리보기·고유 단축 URL(/i/code)·QR·인쇄(A4/A5/엽서)·네이버 지도·RSVP·방명록·사진앨범 지원
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            MANMULRO
 * Text Domain:       manmulro-invitation
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MM_INV_VERSION', '1.0.0' );
define( 'MM_INV_PLUGIN_FILE', __FILE__ );
define( 'MM_INV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MM_INV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once MM_INV_PLUGIN_DIR . 'includes/security.php';
require_once MM_INV_PLUGIN_DIR . 'includes/post-types.php';
require_once MM_INV_PLUGIN_DIR . 'includes/taxonomies.php';
require_once MM_INV_PLUGIN_DIR . 'includes/fields.php';
require_once MM_INV_PLUGIN_DIR . 'includes/gallery.php';
require_once MM_INV_PLUGIN_DIR . 'includes/rsvp.php';
require_once MM_INV_PLUGIN_DIR . 'includes/guestbook.php';
require_once MM_INV_PLUGIN_DIR . 'includes/sharing.php';
require_once MM_INV_PLUGIN_DIR . 'includes/qr.php';
require_once MM_INV_PLUGIN_DIR . 'includes/print.php';
require_once MM_INV_PLUGIN_DIR . 'includes/invitation.php';

if ( is_admin() ) {
	require_once MM_INV_PLUGIN_DIR . 'admin/class-admin-menu.php';
}

/**
 * Main Manmulro Invitation plugin bootstrap class.
 */
final class Manmulro_Invitation {

	/**
	 * Singleton instance.
	 *
	 * @var Manmulro_Invitation|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Manmulro_Invitation
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
		add_action( 'init', array( $this, 'init' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'manmulro_my_account_services', array( $this, 'render_my_account_service_card' ) );
		add_action( 'manmulro_user_withdrawal_requested', array( $this, 'handle_user_withdrawal_retention' ), 10, 1 );
	}

	/**
	 * Initialize CPT, taxonomies, routes, shortcodes, and AJAX handlers.
	 */
	public function init() {
		MM_Inv_Post_Types::register();
		MM_Inv_Taxonomies::register();
		MM_Inv_Gallery::register_image_sizes();
		MM_Inv_Invitation::init();
		MM_Inv_RSVP::init();
		MM_Inv_Guestbook::init();
		MM_Inv_Sharing::init();
		MM_Inv_QR::init();
		MM_Inv_Print::init();

		if ( is_admin() ) {
			MM_Inv_Admin_Menu::init();
		}
	}

	/**
	 * Register frontend styles and scripts.
	 */
	public function register_assets() {
		wp_register_style(
			'mm-inv-frontend',
			MM_INV_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			MM_INV_VERSION
		);

		wp_register_style(
			'mm-inv-editor',
			MM_INV_PLUGIN_URL . 'assets/css/editor.css',
			array( 'mm-inv-frontend' ),
			MM_INV_VERSION
		);

		wp_register_style(
			'mm-inv-print',
			MM_INV_PLUGIN_URL . 'assets/css/print.css',
			array( 'mm-inv-frontend' ),
			MM_INV_VERSION
		);

		foreach ( array( 'simple', 'classic', 'modern', 'flower', 'nature' ) as $theme_slug ) {
			wp_register_style(
				'mm-inv-theme-' . $theme_slug,
				MM_INV_PLUGIN_URL . 'templates/themes/' . $theme_slug . '/style.css',
				array( 'mm-inv-frontend' ),
				MM_INV_VERSION
			);
		}

		wp_register_script(
			'mm-inv-qr',
			MM_INV_PLUGIN_URL . 'assets/js/qr.js',
			array(),
			MM_INV_VERSION,
			true
		);

		wp_register_script(
			'mm-inv-frontend',
			MM_INV_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'mm-inv-qr' ),
			MM_INV_VERSION,
			true
		);

		wp_register_script(
			'mm-inv-editor',
			MM_INV_PLUGIN_URL . 'assets/js/editor.js',
			array( 'mm-inv-qr', 'mm-inv-frontend' ),
			MM_INV_VERSION,
			true
		);
	}

	/**
	 * Render "내 초대장" service card inside Manmulro Social Login's `/my-account/` page (#3, #49)
	 * without creating a hard dependency in either direction.
	 *
	 * @param int $user_id WordPress User ID.
	 */
	public function render_my_account_service_card( $user_id ) {
		$count = count(
			get_posts(
				array(
					'post_type'      => 'mm_invitation',
					'author'         => (int) $user_id,
					'post_status'    => array( 'publish', 'draft', 'private', 'mm_archived' ),
					'posts_per_page' => 100,
					'fields'         => 'ids',
				)
			)
		);
		?>
		<div class="mm-sl-service-card">
			<h4>만물로 초대장 (내 초대장 <?php echo (int) $count; ?>건)</h4>
			<p>세상의 모든 만남을 위한 초대장을 제작하고 RSVP·방명록을 관리하세요.</p>
			<div style="display:flex;gap:8px;margin-top:10px;">
				<a href="<?php echo esc_url( home_url( '/my-invitations/' ) ); ?>" class="mm-sl-primary-btn">내 초대장 관리</a>
				<a href="<?php echo esc_url( home_url( '/invitation-editor/' ) ); ?>" class="mm-sl-secondary-btn">+ 새 초대장 만들기</a>
			</div>
		</div>
		<?php
	}

	/**
	 * When a user requests account withdrawal, archive their invitations instead of deleting immediately (#34).
	 *
	 * @param int $user_id WordPress User ID.
	 */
	public function handle_user_withdrawal_retention( $user_id ) {
		$invitations = get_posts(
			array(
				'post_type'      => 'mm_invitation',
				'author'         => (int) $user_id,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $invitations as $inv_id ) {
			wp_update_post(
				array(
					'ID'          => $inv_id,
					'post_status' => 'mm_archived',
				)
			);
			update_post_meta( $inv_id, '_mm_status', 'ARCHIVED' );
		}
	}

	/**
	 * Plugin activation hook: create RSVP & Guestbook tables, seed 18 initial categories, create pages.
	 */
	public static function activate() {
		MM_Inv_Post_Types::register();
		MM_Inv_Taxonomies::register();
		MM_Inv_Taxonomies::seed_initial_categories();
		MM_Inv_RSVP::create_table();
		MM_Inv_Guestbook::create_table();
		MM_Inv_Invitation::register_rewrite_rules();

		if ( false === get_option( 'mm_inv_settings' ) ) {
			update_option(
				'mm_inv_settings',
				array(
					'kakao_js_key'       => '',
					'default_visibility' => 'link_only',
					'default_noindex'    => 1,
					'default_expiration' => 'always',
					'max_gallery_images' => 10,
					'enable_rsvp_spam'   => 1,
				)
			);
		}

		// Create frontend pages for My Invitations and Invitation Editor.
		$pages = array(
			'my-invitations'    => array(
				'title'   => '내 초대장',
				'content' => '[manmulro_my_invitations]',
			),
			'invitation-editor' => array(
				'title'   => '초대장 만들기',
				'content' => '[manmulro_invitation_editor]',
			),
		);

		foreach ( $pages as $slug => $page_data ) {
			if ( ! get_page_by_path( $slug ) ) {
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

register_activation_hook( __FILE__, array( 'Manmulro_Invitation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Manmulro_Invitation', 'deactivate' ) );

manmulro_invitation();

/**
 * Accessor for Manmulro_Invitation singleton.
 *
 * @return Manmulro_Invitation
 */
function manmulro_invitation() {
	return Manmulro_Invitation::instance();
}
