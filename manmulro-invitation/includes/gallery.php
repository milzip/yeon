<?php
/**
 * Featured Image & Photo Gallery Manager (#12, #13, #14)
 *
 * - Cover image: 1 featured image per invitation (#12)
 * - Photo gallery: Up to 10 images with upload, delete, drag & drop reorder, thumbnail & Lightbox (#13)
 * - Mobile image optimization: Responsive image sizes, lazy loading, WebP preference (#14)
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Gallery {

	const MAX_IMAGES = 10;

	/**
	 * Register mobile-optimized image sizes (#14).
	 */
	public static function register_image_sizes() {
		add_image_size( 'mm_inv_cover', 960, 1200, false );
		add_image_size( 'mm_inv_gallery_thumb', 420, 420, true );
		add_image_size( 'mm_inv_gallery_large', 1280, 1280, false );
	}

	/**
	 * Get gallery items for an invitation (up to 10 items).
	 * Each item contains `id` (attachment ID or 0) and `url` (optimized display URL) and `full_url`.
	 *
	 * @param int $invitation_id Invitation post ID.
	 * @return array
	 */
	public static function get_gallery_items( $invitation_id ) {
		$raw = get_post_meta( (int) $invitation_id, '_mm_gallery_items', true );
		if ( is_string( $raw ) && ! empty( $raw ) ) {
			$raw = json_decode( $raw, true );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$items = array();
		foreach ( array_slice( $raw, 0, self::MAX_IMAGES ) as $entry ) {
			if ( is_numeric( $entry ) ) {
				$att_id    = (int) $entry;
				$thumb_url = wp_get_attachment_image_url( $att_id, 'mm_inv_gallery_thumb' );
				$large_url = wp_get_attachment_image_url( $att_id, 'mm_inv_gallery_large' );
				if ( $thumb_url ) {
					$items[] = array(
						'id'        => $att_id,
						'url'       => $thumb_url,
						'thumb_url' => $thumb_url,
						'full_url'  => $large_url ? $large_url : $thumb_url,
					);
				}
			} elseif ( is_array( $entry ) && ! empty( $entry['url'] ) ) {
				$att_id    = ! empty( $entry['id'] ) ? (int) $entry['id'] : 0;
				$thumb_url = $att_id ? wp_get_attachment_image_url( $att_id, 'mm_inv_gallery_thumb' ) : '';
				$large_url = $att_id ? wp_get_attachment_image_url( $att_id, 'mm_inv_gallery_large' ) : '';
				$fallback  = esc_url_raw( $entry['url'] );
				$items[]   = array(
					'id'        => $att_id,
					'url'       => $thumb_url ? $thumb_url : $fallback,
					'thumb_url' => $thumb_url ? $thumb_url : $fallback,
					'full_url'  => $large_url ? $large_url : ( ! empty( $entry['full_url'] ) ? esc_url_raw( $entry['full_url'] ) : $fallback ),
				);
			}
		}

		return $items;
	}

	/**
	 * Sanitize and save up to 10 gallery items (#13).
	 *
	 * @param int   $invitation_id Invitation post ID.
	 * @param mixed $raw_gallery   Array or JSON string of gallery items.
	 */
	public static function save_gallery_items( $invitation_id, $raw_gallery ) {
		if ( is_string( $raw_gallery ) ) {
			$decoded = json_decode( wp_unslash( $raw_gallery ), true );
			if ( ! is_array( $decoded ) ) {
				$decoded = json_decode( $raw_gallery, true );
			}
			$raw_gallery = is_array( $decoded ) ? $decoded : array();
		}

		if ( ! is_array( $raw_gallery ) ) {
			$raw_gallery = array();
		}

		$cleaned = array();
		foreach ( array_slice( $raw_gallery, 0, self::MAX_IMAGES ) as $item ) {
			if ( is_array( $item ) && ! empty( $item['url'] ) ) {
				$cleaned[] = array(
					'id'       => ! empty( $item['id'] ) ? (int) $item['id'] : 0,
					'url'      => esc_url_raw( $item['url'] ),
					'full_url' => ! empty( $item['full_url'] ) ? esc_url_raw( $item['full_url'] ) : esc_url_raw( $item['url'] ),
				);
			} elseif ( is_string( $item ) && '' !== trim( $item ) ) {
				$cleaned[] = array(
					'id'       => 0,
					'url'      => esc_url_raw( $item ),
					'full_url' => esc_url_raw( $item ),
				);
			}
		}

		update_post_meta( (int) $invitation_id, '_mm_gallery_items', wp_json_encode( $cleaned, JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * Get cover image URL (optimized size, not raw original #12, #14).
	 *
	 * @param int $invitation_id Invitation post ID.
	 * @return string
	 */
	public static function get_cover_image_url( $invitation_id ) {
		$thumb_id = get_post_thumbnail_id( (int) $invitation_id );
		if ( $thumb_id ) {
			$url = wp_get_attachment_image_url( $thumb_id, 'mm_inv_cover' );
			if ( $url ) {
				return $url;
			}
		}

		$meta_url = get_post_meta( (int) $invitation_id, '_mm_cover_image_url', true );
		return ! empty( $meta_url ) ? esc_url( $meta_url ) : '';
	}

	/**
	 * Render photo gallery with thumbnails and Lightbox zoom (#13, #14).
	 *
	 * @param int $invitation_id Invitation post ID.
	 * @return string
	 */
	public static function render_gallery( $invitation_id ) {
		$items = self::get_gallery_items( $invitation_id );
		if ( empty( $items ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="mm-inv-section mm-inv-gallery-section" aria-label="사진앨범">
			<div class="mm-inv-section-header">
				<h3 class="mm-inv-section-title">사진앨범</h3>
				<span class="mm-inv-gallery-count"><?php echo count( $items ); ?> / <?php echo (int) self::MAX_IMAGES; ?></span>
			</div>
			<div class="mm-inv-gallery-grid">
				<?php foreach ( $items as $idx => $img ) : ?>
					<button type="button"
					        class="mm-inv-gallery-item"
					        data-lightbox-src="<?php echo esc_url( $img['full_url'] ); ?>"
					        data-lightbox-index="<?php echo (int) $idx; ?>"
					        aria-label="사진 <?php echo (int) ( $idx + 1 ); ?> 확대보기">
						<img src="<?php echo esc_url( $img['thumb_url'] ); ?>"
						     alt="앨범 사진 <?php echo (int) ( $idx + 1 ); ?>"
						     loading="lazy"
						     decoding="async" />
					</button>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}
