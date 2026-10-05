<?php
/**
 * Public Shortcodes Controller (`public/class-shortcodes.php`)
 *
 * Provides a public landing page for guests and the existing 5-step Menu Builder for signed-in users.
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
		add_filter( 'body_class', array( __CLASS__, 'filter_brand_body_classes' ) );
	}

	/**
	 * Add a page-scoped design hook without changing the site's header or menu markup.
	 *
	 * @param array $classes Existing body classes.
	 * @return array
	 */
	public static function filter_brand_body_classes( $classes ) {
		$page_id = get_queried_object_id();
		$content = $page_id ? get_post_field( 'post_content', $page_id ) : '';

		if ( is_page( 'menu-builder' ) || ( is_string( $content ) && has_shortcode( $content, 'manmulro_menu_builder' ) ) ) {
			$classes[] = 'mm-manmulo-brand-skin';
			$classes[] = 'mm-menu-builder-page';
		}

		return array_unique( $classes );
	}

	/**
	 * Render the public landing page or the existing 5-step Menu Builder.
	 *
	 * @return string
	 */
	public static function render_builder() {
		wp_enqueue_style( 'mm-menu-mobile' );

		if ( ! is_user_logged_in() ) {
			wp_enqueue_style( 'mm-menu-brand' );
			$login_url = class_exists( 'MM_SL_Redirect' )
				? MM_SL_Redirect::get_login_url( home_url( '/menu-builder/' ) )
				: wp_login_url( home_url( '/menu-builder/' ) );
			$templates  = MM_Menu_Design_Module::get_templates();
			$default_img = MM_MENU_PLUGIN_URL . 'assets/images/sample-source-menu.svg';

			ob_start();
			include MM_MENU_PLUGIN_DIR . 'templates/landing.php';
			return ob_get_clean();
		}

		wp_enqueue_media();
		wp_enqueue_style( 'mm-menu-builder' );
		wp_enqueue_style( 'mm-menu-brand' );
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
