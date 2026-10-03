<?php
/**
 * Print & PDF Support (#38)
 *
 * Supports A4, A5, and Postcard (엽서) print layouts via Print CSS and includes QR code (#19, #38).
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Print {

	/**
	 * Initialize print mode hooks.
	 */
	public static function init() {
		// Handled by `MM_Inv_Invitation` when `?print=1` is present on `/i/{code}`.
	}

	/**
	 * Supported paper formats (#38).
	 *
	 * @return array
	 */
	public static function get_paper_formats() {
		return array(
			'a4'       => array(
				'label'      => 'A4 (210 × 297mm)',
				'dimensions' => '210mm × 297mm',
			),
			'a5'       => array(
				'label'      => 'A5 (148 × 210mm)',
				'dimensions' => '148mm × 210mm',
			),
			'postcard' => array(
				'label'      => '엽서 / Postcard (100 × 148mm)',
				'dimensions' => '100mm × 148mm',
			),
		);
	}

	/**
	 * Build print URL for an invitation.
	 *
	 * @param string $short_url Invitation short URL.
	 * @param string $format    'a4', 'a5', or 'postcard'.
	 * @return string
	 */
	public static function get_print_url( $short_url, $format = 'a4' ) {
		return add_query_arg(
			array(
				'print' => '1',
				'paper' => sanitize_key( $format ),
			),
			$short_url
		);
	}
}
