<?php
/**
 * Pluggable OCR Engine Interface (`modules/ocr/interface-ocr-engine.php`)
 *
 * Implements Section #19:
 * OCR ENGINE -> OCR ADAPTER -> NORMALIZED OCR RESULT -> MENU IMPORTER -> COMMON MENU DATA
 * Allows swapping the underlying OCR engine (Tesseract, PaddleOCR, CLOVA OCR, Google Vision, etc.)
 * without modifying the rest of the system.
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface MM_Menu_OCR_Engine_Interface {

	/**
	 * Unique engine identifier.
	 *
	 * @return string
	 */
	public function get_engine_id();

	/**
	 * Extract raw OCR blocks from a source menu image (#5.3, #5.4, #19).
	 *
	 * @param string $image_url        Source image URL or path.
	 * @param int    $source_image_id  Source image DB ID.
	 * @param array  $preprocess_meta  Preprocessing transformations applied (#5.2).
	 * @return array List of normalized OCR items:
	 *   array(
	 *     'text'            => string,
	 *     'parsed_name'     => string,
	 *     'parsed_price'    => int,
	 *     'parsed_category' => string,
	 *     'confidence'      => float,
	 *     'bounding_box'    => array( 'x' => int, 'y' => int, 'width' => int, 'height' => int ),
	 *     'source_image_id' => int,
	 *   )
	 */
	public function recognize( $image_url, $source_image_id, array $preprocess_meta = array() );
}
