<?php
/**
 * Print Menu Module (`modules/print/class-print-module.php`)
 *
 * Implements Section #14:
 * - Uses the exact same Common Menu Data to generate print-ready menus
 * - Paper sizes: A4, A3 (and extensible custom dimensions)
 * - Orientations: 세로 (portrait), 가로 (landscape)
 * - Outputs: PDF (via browser print/PDF engine), JPG, PNG
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Print_Module {

	/**
	 * Supported paper sizes (#14).
	 *
	 * @return array
	 */
	public static function get_paper_sizes() {
		return array(
			'a4' => array(
				'label' => 'A4 (210 × 297mm)',
			),
			'a3' => array(
				'label' => 'A3 (297 × 420mm)',
			),
		);
	}

	/**
	 * Build print URL for a project.
	 *
	 * @param string $public_url  Store public URL.
	 * @param string $paper       'a4' or 'a3'.
	 * @param string $orientation 'portrait' or 'landscape'.
	 * @return string
	 */
	public static function get_print_url( $public_url, $paper = 'a4', $orientation = 'portrait' ) {
		return add_query_arg(
			array(
				'print'       => '1',
				'paper'       => sanitize_key( $paper ),
				'orientation' => sanitize_key( $orientation ),
			),
			$public_url
		);
	}
}
