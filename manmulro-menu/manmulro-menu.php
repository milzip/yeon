<?php
/**
 * Plugin Name:       Manmulro Menu (만물로 메뉴판 만들기)
 * Plugin URI:        https://manmulro.com
 * Description:       음식점·카페·주점·베이커리·서비스업 사업자를 위한 디지털 QR 메뉴판 & 인쇄용 메뉴판 제작 플랫폼 V1 (OCR 원본 비교 입력 + 직접 입력 통합 구조)
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            MANMULRO
 * Text Domain:       manmulro-menu
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MM_MENU_VERSION', '1.0.1' );
define( 'MM_MENU_PLUGIN_FILE', __FILE__ );
define( 'MM_MENU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MM_MENU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Core includes.
require_once MM_MENU_PLUGIN_DIR . 'includes/class-db.php';
require_once MM_MENU_PLUGIN_DIR . 'includes/class-security.php';
require_once MM_MENU_PLUGIN_DIR . 'includes/class-router.php';

// Independent functional modules (#22).
require_once MM_MENU_PLUGIN_DIR . 'modules/projects/class-projects-module.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/categories/class-categories-module.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/menu-items/class-menu-items-module.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/menu-detail/class-menu-detail-module.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/images/class-images-module.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/ocr/interface-ocr-engine.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/ocr/class-ocr-adapter.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/ocr/class-ocr-importer.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/design/class-design-module.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/qr/class-qr-module.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/print/class-print-module.php';
require_once MM_MENU_PLUGIN_DIR . 'modules/sharing/class-sharing-module.php';

// Public & Admin controllers.
require_once MM_MENU_PLUGIN_DIR . 'public/class-shortcodes.php';
if ( is_admin() ) {
	require_once MM_MENU_PLUGIN_DIR . 'admin/class-admin-menu.php';
}

/**
 * Main Manmulro Menu plugin bootstrap class.
 * Operates independently of Manmulro Invitation while sharing WordPress User authentication (#23).
 */
final class Manmulro_Menu {

	/**
	 * Singleton instance.
	 *
	 * @var Manmulro_Menu|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Manmulro_Menu
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
	}

	/**
	 * Initialize modules, routes, shortcodes, and AJAX endpoints.
	 */
	public function init() {
		MM_Menu_Images_Module::register_image_sizes();
		MM_Menu_Router::init();
		MM_Menu_Projects_Module::init();
		MM_Menu_Categories_Module::init();
		MM_Menu_Items_Module::init();
		MM_Menu_Detail_Module::init();
		MM_Menu_OCR_Importer::init();
		MM_Menu_Design_Module::init();
		MM_Menu_Shortcodes::init();

		if ( is_admin() ) {
			MM_Menu_Admin_Menu::init();
		}
	}

	/**
	 * Register frontend styles and scripts.
	 */
	public function register_assets() {
		wp_register_style(
			'mm-menu-mobile',
			MM_MENU_PLUGIN_URL . 'assets/css/mobile-menu.css',
			array(),
			MM_MENU_VERSION
		);

		wp_register_style(
			'mm-menu-builder',
			MM_MENU_PLUGIN_URL . 'assets/css/builder.css',
			array( 'mm-menu-mobile' ),
			MM_MENU_VERSION
		);

		wp_register_style(
			'mm-menu-print',
			MM_MENU_PLUGIN_URL . 'assets/css/print-menu.css',
			array( 'mm-menu-mobile' ),
			MM_MENU_VERSION
		);

		wp_register_script(
			'mm-menu-ocr-viewer',
			MM_MENU_PLUGIN_URL . 'assets/js/ocr-viewer.js',
			array(),
			MM_MENU_VERSION,
			true
		);

		wp_register_script(
			'mm-menu-mobile',
			MM_MENU_PLUGIN_URL . 'assets/js/mobile-menu.js',
			array(),
			MM_MENU_VERSION,
			true
		);

		wp_register_script(
			'mm-menu-builder',
			MM_MENU_PLUGIN_URL . 'assets/js/builder.js',
			array( 'mm-menu-ocr-viewer', 'mm-menu-mobile' ),
			MM_MENU_VERSION,
			true
		);
	}

	/**
	 * Inject service card into Manmulro Social Login `/my-account/` page if present (#23).
	 *
	 * @param int $user_id WordPress User ID.
	 */
	public function render_my_account_service_card( $user_id ) {
		$projects = MM_Menu_Projects_Module::get_user_projects( (int) $user_id );
		?>
		<div class="mm-sl-service-card">
			<h4>만물로 메뉴판 만들기 (내 메뉴판 <?php echo count( $projects ); ?>건)</h4>
			<p>기존 메뉴판 사진(OCR)이나 직접 입력으로 모바일 QR 메뉴판·인쇄 메뉴판을 한 번에 만드세요.</p>
			<div style="display:flex;gap:8px;margin-top:10px;">
				<a href="<?php echo esc_url( home_url( '/menu-builder/' ) ); ?>" class="mm-sl-primary-btn">메뉴판 만들기 / 관리</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Plugin activation hook: create all 10 menu data tables (#20), register routes, and create default builder page.
	 */
	public static function activate() {
		MM_Menu_DB::create_tables();
		MM_Menu_Router::register_rewrite_rules();

		if ( ! get_page_by_path( 'menu-builder' ) ) {
			wp_insert_post(
				array(
					'post_title'   => '만물로 메뉴판 만들기',
					'post_name'    => 'menu-builder',
					'post_content' => '[manmulro_menu_builder]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
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

register_activation_hook( __FILE__, array( 'Manmulro_Menu', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Manmulro_Menu', 'deactivate' ) );

manmulro_menu();

/**
 * Accessor for Manmulro_Menu singleton.
 *
 * @return Manmulro_Menu
 */
function manmulro_menu() {
	return Manmulro_Menu::instance();
}
