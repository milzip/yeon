<?php
/**
 * QR Code Module (`modules/qr/class-qr-module.php`)
 *
 * Implements Section #13 & Principle #28.5:
 * - Generates QR code for the permanent `/menu/{store}` URL
 * - Editing menu items or prices NEVER changes the QR code URL!
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_QR_Module {

	/**
	 * Render QR container for a store's mobile menu URL.
	 *
	 * @param string $public_url Permanent `/menu/{store}` URL.
	 * @param int    $size       Pixel size.
	 * @return string HTML markup.
	 */
	public static function render_qr_box( $public_url, $size = 160 ) {
		ob_start();
		?>
		<div class="mm-menu-qr-box"
		     data-qr-url="<?php echo esc_attr( esc_url_raw( $public_url ) ); ?>"
		     data-qr-size="<?php echo (int) $size; ?>">
			<div class="mm-menu-qr-canvas"></div>
			<p class="mm-menu-qr-note">메뉴나 가격을 수정해도 이 QR 코드는 평생 바뀌지 않습니다 (#13.1).</p>
		</div>
		<?php
		return ob_get_clean();
	}
}
