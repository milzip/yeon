<?php
/**
 * QR Code System (#19)
 *
 * Generates QR codes for `/i/{code}` URLs used in online sharing and printed invitations.
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_QR {

	/**
	 * Initialize QR hooks.
	 */
	public static function init() {
		// QR rendering is handled client-side via `assets/js/qr.js` (zero external network dependency)
		// with an inline container helper below.
	}

	/**
	 * Render a QR container element that `assets/js/qr.js` populates with a crisp SVG QR code.
	 *
	 * @param string $url  Invitation short URL (e.g. `https://example.com/i/a7Fk32`).
	 * @param int    $size Size in pixels.
	 * @return string HTML markup.
	 */
	public static function render_qr_box( $url, $size = 160 ) {
		ob_start();
		?>
		<div class="mm-inv-qr-box"
		     data-qr-url="<?php echo esc_attr( esc_url_raw( $url ) ); ?>"
		     data-qr-size="<?php echo (int) $size; ?>"
		     aria-label="초대장 QR 코드">
			<div class="mm-inv-qr-canvas"></div>
			<p class="mm-inv-qr-caption">스마트폰 카메라로 QR을 스캔하면 모바일 초대장·지도·참석응답(RSVP)으로 연결됩니다.</p>
		</div>
		<?php
		return ob_get_clean();
	}
}
