<?php
/**
 * Flexible Fields / Blocks System (#8, #9, #10, #11)
 *
 * Separates Event Data / Flexible Fields from Template Design (#11) so switching
 * templates preserves all entered data and custom fields.
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Fields {

	/**
	 * Supported field types per Section #8.
	 *
	 * @return array
	 */
	public static function get_supported_types() {
		return array(
			'title'    => array(
				'label'       => '제목',
				'icon'        => '🔤',
				'placeholder' => '소제목 또는 섹션 제목을 입력하세요',
			),
			'text'     => array(
				'label'       => '한 줄 텍스트',
				'icon'        => '✏️',
				'placeholder' => '한 줄 안내 문구를 입력하세요',
			),
			'textarea' => array(
				'label'       => '여러 줄 설명',
				'icon'        => '📝',
				'placeholder' => '초대 인사말이나 상세 설명을 입력하세요',
			),
			'date'     => array(
				'label'       => '날짜',
				'icon'        => '📅',
				'placeholder' => '2026-10-24',
			),
			'time'     => array(
				'label'       => '시간',
				'icon'        => '⏰',
				'placeholder' => '오전 11:00',
			),
			'location' => array(
				'label'       => '장소',
				'icon'        => '📍',
				'placeholder' => 'OO 컨벤션센터 3층 그랜드홀',
			),
			'address'  => array(
				'label'       => '주소',
				'icon'        => '🗺️',
				'placeholder' => '부산광역시 해운대구 센텀중앙로 123',
			),
			'phone'    => array(
				'label'       => '전화번호',
				'icon'        => '📞',
				'placeholder' => '010-1234-5678',
			),
			'link'     => array(
				'label'       => '링크',
				'icon'        => '🔗',
				'placeholder' => 'https://example.com',
			),
			'photo'    => array(
				'label'       => '사진',
				'icon'        => '🖼️',
				'placeholder' => '이미지 URL 또는 미디어 선택',
			),
			'gallery'  => array(
				'label'       => '사진앨범',
				'icon'        => '📸',
				'placeholder' => '사진앨범 블록 표시 위치',
			),
			'divider'  => array(
				'label'       => '구분선',
				'icon'        => '➖',
				'placeholder' => '',
			),
			'rsvp'     => array(
				'label'       => '참석여부',
				'icon'        => '✅',
				'placeholder' => '참석 여부(RSVP) 응답 영역 표시',
			),
			'custom'   => array(
				'label'       => '사용자 정의 항목',
				'icon'        => '➕',
				'placeholder' => '예: 카트비 25,000원 / 산행코스: 범어사 → 북문 → 고당봉',
			),
		);
	}

	/**
	 * Default starter fields for a newly created invitation.
	 *
	 * @return array
	 */
	public static function get_default_fields() {
		return array(
			array(
				'id'      => 'fld_greeting',
				'type'    => 'textarea',
				'label'   => '초대 인사말',
				'value'   => "소중한 분들을 모시고 뜻깊은 자리를 함께하고자 합니다.\n바쁘시더라도 부디 참석하시어 자리를 빛내주시면 감사하겠습니다.",
				'visible' => true,
				'order'   => 1,
			),
			array(
				'id'      => 'fld_date',
				'type'    => 'date',
				'label'   => '날짜',
				'value'   => gmdate( 'Y-m-d', strtotime( '+14 days' ) ),
				'visible' => true,
				'order'   => 2,
			),
			array(
				'id'      => 'fld_time',
				'type'    => 'time',
				'label'   => '시간',
				'value'   => '14:00',
				'visible' => true,
				'order'   => 3,
			),
			array(
				'id'      => 'fld_location',
				'type'    => 'location',
				'label'   => '장소',
				'value'   => '만물로 컨벤션센터 2층 라운지',
				'visible' => true,
				'order'   => 4,
			),
			array(
				'id'      => 'fld_address',
				'type'    => 'address',
				'label'   => '주소',
				'value'   => '부산광역시 해운대구 센텀중앙로 123',
				'visible' => true,
				'order'   => 5,
			),
			array(
				'id'      => 'fld_phone',
				'type'    => 'phone',
				'label'   => '문의 연락처',
				'value'   => '010-1234-5678',
				'visible' => true,
				'order'   => 6,
			),
		);
	}

	/**
	 * Sanitize and normalize a list of flexible fields (#10, #44).
	 * Preserves order, visibility (표시/숨김), label, value, and extra meta.
	 *
	 * @param mixed $raw_fields Array or JSON string of field items.
	 * @return array
	 */
	public static function sanitize_fields( $raw_fields ) {
		if ( is_string( $raw_fields ) ) {
			$decoded = json_decode( wp_unslash( $raw_fields ), true );
			if ( ! is_array( $decoded ) ) {
				$decoded = json_decode( $raw_fields, true );
			}
			$raw_fields = is_array( $decoded ) ? $decoded : array();
		}

		if ( ! is_array( $raw_fields ) ) {
			return array();
		}

		$supported = self::get_supported_types();
		$sanitized = array();
		$order     = 1;

		foreach ( $raw_fields as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$type = isset( $item['type'] ) ? sanitize_key( $item['type'] ) : 'custom';
			if ( ! isset( $supported[ $type ] ) ) {
				$type = 'custom';
			}

			$id    = ! empty( $item['id'] ) ? sanitize_key( $item['id'] ) : 'fld_' . wp_generate_password( 6, false, false );
			$label = isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : $supported[ $type ]['label'];

			if ( 'textarea' === $type ) {
				$value = isset( $item['value'] ) ? sanitize_textarea_field( $item['value'] ) : '';
			} elseif ( 'link' === $type || 'photo' === $type ) {
				$value = isset( $item['value'] ) ? esc_url_raw( $item['value'] ) : '';
			} else {
				$value = isset( $item['value'] ) ? sanitize_text_field( $item['value'] ) : '';
			}

			$visible = isset( $item['visible'] ) ? (bool) $item['visible'] : true;

			$sanitized[] = array(
				'id'      => $id,
				'type'    => $type,
				'label'   => $label,
				'value'   => $value,
				'visible' => $visible,
				'order'   => $order++,
			);
		}

		return $sanitized;
	}

	/**
	 * Render a single flexible field on the frontend invitation card (#8, #9, #20, #21, #22).
	 *
	 * @param array $field          Normalized field item.
	 * @param array $invitation_ctx Full invitation context array.
	 * @return string HTML output.
	 */
	public static function render_field( array $field, array $invitation_ctx = array() ) {
		if ( empty( $field['visible'] ) ) {
			return '';
		}

		$type  = isset( $field['type'] ) ? $field['type'] : 'custom';
		$label = isset( $field['label'] ) ? $field['label'] : '';
		$value = isset( $field['value'] ) ? $field['value'] : '';

		if ( 'divider' === $type ) {
			return '<hr class="mm-inv-divider" />';
		}

		if ( 'gallery' === $type ) {
			if ( ! empty( $invitation_ctx['id'] ) ) {
				return MM_Inv_Gallery::render_gallery( (int) $invitation_ctx['id'] );
			}
			return '';
		}

		if ( 'rsvp' === $type ) {
			return ''; // Rendered in dedicated RSVP block when enabled.
		}

		if ( '' === trim( (string) $value ) ) {
			return '';
		}

		ob_start();
		switch ( $type ) {
			case 'title':
				?>
				<div class="mm-inv-block mm-inv-block--title">
					<h3 class="mm-inv-section-heading"><?php echo esc_html( $value ); ?></h3>
				</div>
				<?php
				break;

			case 'textarea':
				?>
				<div class="mm-inv-block mm-inv-block--textarea">
					<?php if ( ! empty( $label ) ) : ?>
						<div class="mm-inv-block__label"><?php echo esc_html( $label ); ?></div>
					<?php endif; ?>
					<div class="mm-inv-block__multiline"><?php echo nl2br( esc_html( $value ) ); ?></div>
				</div>
				<?php
				break;

			case 'phone':
				// Section #22: [전화하기] [문자 보내기]
				$clean_tel = preg_replace( '/[^0-9+]/', '', $value );
				?>
				<div class="mm-inv-block mm-inv-block--phone">
					<div class="mm-inv-field-row">
						<span class="mm-inv-field-row__label"><?php echo esc_html( $label ? $label : '연락처' ); ?></span>
						<span class="mm-inv-field-row__value"><?php echo esc_html( $value ); ?></span>
					</div>
					<?php if ( ! empty( $clean_tel ) ) : ?>
						<div class="mm-inv-contact-actions">
							<a href="<?php echo esc_url( 'tel:' . $clean_tel ); ?>" class="mm-inv-action-chip">📞 전화하기</a>
							<a href="<?php echo esc_url( 'sms:' . $clean_tel ); ?>" class="mm-inv-action-chip">💬 문자 보내기</a>
						</div>
					<?php endif; ?>
				</div>
				<?php
				break;

			case 'link':
				?>
				<div class="mm-inv-block mm-inv-block--link">
					<div class="mm-inv-field-row">
						<span class="mm-inv-field-row__label"><?php echo esc_html( $label ? $label : '링크' ); ?></span>
						<span class="mm-inv-field-row__value">
							<a href="<?php echo esc_url( $value ); ?>" target="_blank" rel="noopener noreferrer" class="mm-inv-external-link">
								<?php echo esc_html( $value ); ?> ↗
							</a>
						</span>
					</div>
				</div>
				<?php
				break;

			case 'photo':
				?>
				<div class="mm-inv-block mm-inv-block--photo">
					<?php if ( ! empty( $label ) ) : ?>
						<div class="mm-inv-block__label"><?php echo esc_html( $label ); ?></div>
					<?php endif; ?>
					<figure class="mm-inv-inline-photo">
						<img src="<?php echo esc_url( $value ); ?>" alt="<?php echo esc_attr( $label ); ?>" loading="lazy" decoding="async" />
					</figure>
				</div>
				<?php
				break;

			case 'location':
			case 'address':
				// Handled in dedicated Location block (#20, #21) if matches main location, or rendered as row.
				?>
				<div class="mm-inv-block mm-inv-block--row">
					<div class="mm-inv-field-row">
						<span class="mm-inv-field-row__label"><?php echo esc_html( $label ); ?></span>
						<span class="mm-inv-field-row__value"><?php echo esc_html( $value ); ?></span>
					</div>
				</div>
				<?php
				break;

			default:
				// Covers `text`, `date`, `time`, `custom` (#9: 카트비 25,000원, 산행코스 등).
				?>
				<div class="mm-inv-block mm-inv-block--row">
					<div class="mm-inv-field-row">
						<span class="mm-inv-field-row__label"><?php echo esc_html( $label ); ?></span>
						<span class="mm-inv-field-row__value"><?php echo esc_html( $value ); ?></span>
					</div>
				</div>
				<?php
				break;
		}
		return ob_get_clean();
	}
}
