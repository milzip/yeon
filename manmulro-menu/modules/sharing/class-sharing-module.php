<?php
/**
 * Sharing Module (`modules/sharing/class-sharing-module.php`)
 *
 * Implements Sections #9.1, #9.2, #13:
 * - Store QR menu URL (`/menu/{store}`)
 * - Individual menu item detail URL (`/menu/{store}/{menu-item}`) direct sharing
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Sharing_Module {

	/**
	 * Build the unique detail URL for a single menu item (`/menu/{store}/{menu-item}` #9.1).
	 *
	 * @param string $store_slug Store slug.
	 * @param string $item_slug  Menu item slug.
	 * @return string
	 */
	public static function get_item_detail_url( $store_slug, $item_slug ) {
		return home_url( '/menu/' . rawurlencode( $store_slug ) . '/' . rawurlencode( $item_slug ) );
	}
}
