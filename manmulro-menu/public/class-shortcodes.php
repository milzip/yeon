<?php
/**
 * Public Shortcodes Controller (`public/class-shortcodes.php`)
 *
 * Provides `[manmulro_menu_builder]` for the 5-step Menu Builder application.
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Shortcodes {

	/**
	 * Register shortcodes.
	 */
	public static function init() {
		add_shortcode( 'manmulro_menu_builder', array( __CLASS__, 'render_builder' ) );
	}

	/**
	 * Render the 5-Step Menu Builder (`[manmulro_menu_builder]`).
	 *
	 * @return string
	 */
	public static function render_builder() {
		if ( ! is_user_logged_in() ) {
			$login_url = class_exists( 'MM_SL_Redirect' )
				? MM_SL_Redirect::get_login_url( home_url( '/menu-builder/' ) )
				: wp_login_url( home_url( '/menu-builder/' ) );

			return '<div class="mm-menu-login-box"><h3>만물로 메뉴판 만들기는 로그인 후 이용할 수 있습니다</h3><p>기존 메뉴판이 있으면 사진으로, 없으면 직접 만들어보세요.</p><a class="mm-menu-btn mm-menu-btn--primary" href="' . esc_url( $login_url ) . '">로그인하러 가기</a></div>';
		}

		wp_enqueue_media();
		wp_enqueue_style( 'mm-menu-mobile' );
		wp_enqueue_style( 'mm-menu-builder' );
		wp_enqueue_script( 'mm-menu-ocr-viewer' );
		wp_enqueue_script( 'mm-menu-mobile' );
		wp_enqueue_script( 'mm-menu-builder' );

		$project_id = isset( $_GET['project_id'] ) ? (int) $_GET['project_id'] : 0;
		$project    = null;

		if ( $project_id > 0 && MM_Menu_Security::can_manage_project( $project_id ) ) {
			$project = MM_Menu_Projects_Module::get_full_project( $project_id );
		} else {
			$user_projects = MM_Menu_Projects_Module::get_user_projects( get_current_user_id() );
			if ( ! empty( $user_projects ) ) {
				$project = MM_Menu_Projects_Module::get_full_project( (int) $user_projects[0]['project_id'] );
			}
		}

		ob_start();
		include MM_MENU_PLUGIN_DIR . 'templates/builder.php';
		return ob_get_clean();
	}
}
