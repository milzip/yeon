<?php
/**
 * Public URL Router for Mobile QR Menu (`/menu/{store}`) & Menu Detail Page (`/menu/{store}/{menu-item}`)
 *
 * Implements sections #9.1, #13.1, #14, #28.5, #28.6:
 * - `/menu/{store}` -> Mobile QR Menu Board (URL & QR never change when menu/price changes)
 * - `/menu/{store}/{menu-item}` -> Individual Menu Item Detail Page
 * - `/menu/{store}?print=1&paper=a4|a3&orientation=portrait|landscape` -> Print Menu Board
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Router {

	/**
	 * Initialize rewrite rules and template dispatcher.
	 */
	public static function init() {
		self::register_rewrite_rules();
		add_action( 'template_redirect', array( __CLASS__, 'dispatch_public_routes' ), 1 );
	}

	/**
	 * Register rewrite rules for `/menu/{store}` and `/menu/{store}/{menu-item}`.
	 */
	public static function register_rewrite_rules() {
		add_rewrite_rule(
			'^menu/([A-Za-z0-9_-]+)/([A-Za-z0-9_-]+)/?$',
			'index.php?mm_menu_store=$matches[1]&mm_menu_item=$matches[2]',
			'top'
		);

		add_rewrite_rule(
			'^menu/([A-Za-z0-9_-]+)/?$',
			'index.php?mm_menu_store=$matches[1]',
			'top'
		);

		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = 'mm_menu_store';
				$vars[] = 'mm_menu_item';
				return $vars;
			}
		);
	}

	/**
	 * Dispatch public requests for `/menu/{store}` and `/menu/{store}/{menu-item}`.
	 */
	public static function dispatch_public_routes() {
		$store_slug = get_query_var( 'mm_menu_store' );
		if ( empty( $store_slug ) && isset( $_GET['mm_menu_store'] ) ) {
			$store_slug = sanitize_text_field( wp_unslash( $_GET['mm_menu_store'] ) );
		}

		if ( empty( $store_slug ) ) {
			return;
		}

		$project = MM_Menu_Projects_Module::get_project_by_slug( $store_slug );
		if ( ! $project ) {
			status_header( 404 );
			wp_die( '요청하신 메뉴판을 찾을 수 없습니다.', '메뉴판 없음', array( 'response' => 404 ) );
		}

		$item_slug = get_query_var( 'mm_menu_item' );
		if ( empty( $item_slug ) && isset( $_GET['mm_menu_item'] ) ) {
			$item_slug = sanitize_text_field( wp_unslash( $_GET['mm_menu_item'] ) );
		}

		// Route 1: Individual Menu Item Detail Page (`/menu/{store}/{menu-item}` #9).
		if ( ! empty( $item_slug ) ) {
			$menu_item = MM_Menu_Items_Module::get_item_by_slug( (int) $project['project_id'], $item_slug );
			if ( ! $menu_item ) {
				status_header( 404 );
				wp_die( '요청하신 메뉴 상세 정보를 찾을 수 없습니다.', '메뉴 없음', array( 'response' => 404 ) );
			}
			include MM_MENU_PLUGIN_DIR . 'templates/menu-item-detail.php';
			exit;
		}

		// Route 2: Print Menu Board (`/menu/{store}?print=1` #14).
		if ( ! empty( $_GET['print'] ) ) {
			include MM_MENU_PLUGIN_DIR . 'templates/print-menu.php';
			exit;
		}

		// Route 3: Mobile QR Menu Board (`/menu/{store}` #13).
		include MM_MENU_PLUGIN_DIR . 'templates/mobile-menu.php';
		exit;
	}
}
