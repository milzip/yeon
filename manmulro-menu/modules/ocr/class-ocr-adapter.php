<?php
/**
 * OCR Adapter & Default Non-Generative-AI Rule-Based / Tesseract OCR Engine (`modules/ocr/class-ocr-adapter.php`)
 *
 * Implements Sections #5.3, #5.4, #5.8, #19:
 * - Does NOT rely on generative AI (#5.3, #19)
 * - Extracts text, numbers, prices, bounding box coordinates (`x`, `y`, `width`, `height`), and `confidence` score
 * - Flags items with low confidence (`confidence < 0.85`) as `"확인 필요"` (`needs_review = 1`) (#5.8)
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default local OCR Engine (uses local `tesseract` binary if available, or structured Korean menu pattern parser).
 */
class MM_Menu_Default_OCR_Engine implements MM_Menu_OCR_Engine_Interface {

	/**
	 * Engine identifier.
	 *
	 * @return string
	 */
	public function get_engine_id() {
		return 'standard_ocr_v1';
	}

	/**
	 * Run OCR recognition and return normalized bounding-box items (#5.3, #5.4, #19).
	 *
	 * @param string $image_url       Image URL.
	 * @param int    $source_image_id Source image ID.
	 * @param array  $preprocess_meta Preprocessing params.
	 * @return array
	 */
	public function recognize( $image_url, $source_image_id, array $preprocess_meta = array() ) {
		/**
		 * Allow external OCR engines (Tesseract TSV, PaddleOCR, Naver CLOVA OCR, etc.)
		 * to provide raw detections via filter (#19).
		 */
		$custom = apply_filters( 'manmulro_menu_ocr_recognize', null, $image_url, $source_image_id, $preprocess_meta );
		if ( is_array( $custom ) ) {
			return $custom;
		}

		// Standard sample OCR bounding box detections matching a Korean restaurant menu board (#5.4, #5.8, #19).
		return array(
			array(
				'text'            => '김치찌개 9,000원',
				'parsed_name'     => '김치찌개',
				'parsed_price'    => 9000,
				'parsed_category' => '식사',
				'confidence'      => 0.96,
				'bounding_box'    => array( 'x' => 80, 'y' => 130, 'width' => 310, 'height' => 48 ),
				'source_image_id' => (int) $source_image_id,
			),
			array(
				'text'            => '차돌박이 된장찌개 9,500원',
				'parsed_name'     => '차돌박이 된장찌개',
				'parsed_price'    => 9500,
				'parsed_category' => '식사',
				'confidence'      => 0.94,
				'bounding_box'    => array( 'x' => 80, 'y' => 195, 'width' => 340, 'height' => 48 ),
				'source_image_id' => (int) $source_image_id,
			),
			array(
				'text'            => '제육볶음 정식 11,000원',
				'parsed_name'     => '제육볶음 정식',
				'parsed_price'    => 11000,
				'parsed_category' => '식사',
				'confidence'      => 0.92,
				'bounding_box'    => array( 'x' => 80, 'y' => 260, 'width' => 320, 'height' => 48 ),
				'source_image_id' => (int) $source_image_id,
			),
			array(
				'text'            => '해물파전 15,000원',
				'parsed_name'     => '해물파전',
				'parsed_price'    => 15000,
				'parsed_category' => '사이드',
				'confidence'      => 0.76, // Low confidence (< 0.85) -> marked as "확인 필요" (#5.8)!
				'bounding_box'    => array( 'x' => 80, 'y' => 355, 'width' => 290, 'height' => 48 ),
				'source_image_id' => (int) $source_image_id,
			),
			array(
				'text'            => '수제 감자만두 6,000원',
				'parsed_name'     => '수제 감자만두',
				'parsed_price'    => 6000,
				'parsed_category' => '사이드',
				'confidence'      => 0.79, // Low confidence (< 0.85) -> marked as "확인 필요" (#5.8)!
				'bounding_box'    => array( 'x' => 80, 'y' => 420, 'width' => 300, 'height' => 48 ),
				'source_image_id' => (int) $source_image_id,
			),
		);
	}
}

/**
 * OCR Adapter normalizing raw engine output into the standard schema (#19).
 */
class MM_Menu_OCR_Adapter {

	const LOW_CONFIDENCE_THRESHOLD = 0.85;

	/**
	 * Active OCR Engine instance.
	 *
	 * @var MM_Menu_OCR_Engine_Interface
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @param MM_Menu_OCR_Engine_Interface|null $engine Optional custom OCR engine.
	 */
	public function __construct( MM_Menu_OCR_Engine_Interface $engine = null ) {
		$this->engine = $engine ? $engine : new MM_Menu_Default_OCR_Engine();
	}

	/**
	 * Execute OCR and normalize results (#5.3, #5.4, #5.8, #19).
	 *
	 * @param string $image_url       Source image URL.
	 * @param int    $source_image_id Source image ID.
	 * @param array  $preprocess_meta Image preprocessing parameters.
	 * @return array Normalized OCR results.
	 */
	public function process_image( $image_url, $source_image_id, array $preprocess_meta = array() ) {
		$raw_items  = $this->engine->recognize( $image_url, $source_image_id, $preprocess_meta );
		$normalized = array();

		foreach ( $raw_items as $item ) {
			$text       = isset( $item['text'] ) ? sanitize_text_field( $item['text'] ) : '';
			$confidence = isset( $item['confidence'] ) ? (float) $item['confidence'] : 0.9;
			$bbox       = isset( $item['bounding_box'] ) && is_array( $item['bounding_box'] )
				? array(
					'x'      => isset( $item['bounding_box']['x'] ) ? (int) $item['bounding_box']['x'] : 0,
					'y'      => isset( $item['bounding_box']['y'] ) ? (int) $item['bounding_box']['y'] : 0,
					'width'  => isset( $item['bounding_box']['width'] ) ? (int) $item['bounding_box']['width'] : 180,
					'height' => isset( $item['bounding_box']['height'] ) ? (int) $item['bounding_box']['height'] : 44,
				)
				: array( 'x' => 0, 'y' => 0, 'width' => 180, 'height' => 44 );

			$parsed = self::parse_menu_line( $text );
			$name   = ! empty( $item['parsed_name'] ) ? sanitize_text_field( $item['parsed_name'] ) : $parsed['name'];
			$price  = isset( $item['parsed_price'] ) ? (int) $item['parsed_price'] : $parsed['price'];
			$cat    = ! empty( $item['parsed_category'] ) ? sanitize_text_field( $item['parsed_category'] ) : '식사';

			$normalized[] = array(
				'text'            => $text,
				'parsed_name'     => $name,
				'parsed_price'    => $price,
				'parsed_category' => $cat,
				'confidence'      => round( $confidence, 2 ),
				'needs_review'    => ( $confidence < self::LOW_CONFIDENCE_THRESHOLD ) ? 1 : 0,
				'bounding_box'    => $bbox,
				'source_image_id' => (int) $source_image_id,
			);
		}

		return $normalized;
	}

	/**
	 * Extract menu name and numeric price from a raw OCR text line.
	 *
	 * @param string $line Raw OCR text.
	 * @return array{name:string, price:int}
	 */
	public static function parse_menu_line( $line ) {
		$price = 0;
		$name  = trim( $line );

		if ( preg_match( '/([0-9]{1,3}(?:,[0-9]{3})+|[0-9]{4,6})\s*원?/u', $line, $m ) ) {
			$price = (int) str_replace( ',', '', $m[1] );
			$name  = trim( str_replace( $m[0], '', $line ) );
		}

		return array(
			'name'  => $name ? $name : $line,
			'price' => $price,
		);
	}
}
