<?php
/**
 * WordPress Admin Meta Boxes for `mm_invitation` (`admin/meta-boxes.php`)
 *
 * Allows administrators and authors to inspect and edit invitation metadata,
 * flexible fields, short URL (`/i/{code}`), QR code, and publication policies
 * directly inside WP Admin -> 초대장 -> 수정.
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Admin_Meta_Boxes {

	/**
	 * Initialize meta box hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_mm_invitation', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin stylesheet and QR script on `mm_invitation` screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || ( 'mm_invitation' !== $screen->post_type && false === strpos( $hook, 'mm-inv' ) && false === strpos( $hook, 'manmulro-invitation' ) ) ) {
			return;
		}

		wp_enqueue_style(
			'mm-inv-admin',
			MM_INV_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			MM_INV_VERSION
		);

		wp_enqueue_script(
			'mm-inv-qr',
			MM_INV_PLUGIN_URL . 'assets/js/qr.js',
			array(),
			MM_INV_VERSION,
			true
		);
	}

	/**
	 * Register meta boxes on `mm_invitation` edit screen.
	 */
	public static function register_meta_boxes() {
		add_meta_box(
			'mm_inv_event_details',
			'만물로 초대장 기본 설정 · 템플릿 · 일정 · 장소',
			array( __CLASS__, 'render_event_details_box' ),
			'mm_invitation',
			'normal',
			'high'
		);

		add_meta_box(
			'mm_inv_flexible_fields_box',
			'자유 항목 (Flexible Fields / 사용자 정의 항목 #8, #9, #10)',
			array( __CLASS__, 'render_flexible_fields_box' ),
			'mm_invitation',
			'normal',
			'default'
		);

		add_meta_box(
			'mm_inv_short_url_qr',
			'고유 URL (/i/code) & QR 코드 (#17, #19)',
			array( __CLASS__, 'render_short_url_box' ),
			'mm_invitation',
			'side',
			'high'
		);
	}

	/**
	 * Render Event Details & Policies Meta Box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public static function render_event_details_box( $post ) {
		wp_nonce_field( 'mm_inv_admin_save_meta', 'mm_inv_admin_meta_nonce' );

		$inv       = MM_Inv_Invitation::get_invitation( $post->ID );
		$templates = MM_Inv_Invitation::get_templates();
		?>
		<table class="form-table">
			<tr>
				<th><label for="mm_adm_template">템플릿 디자인 (#11, #39)</label></th>
				<td>
					<select id="mm_adm_template" name="mm_template">
						<?php foreach ( $templates as $slug => $tpl ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $inv['template'], $slug ); ?>>
								<?php echo esc_html( $tpl['name'] . ' [' . $tpl['tier'] . ']' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">템플릿을 변경해도 입력된 행사 데이터와 자유 항목은 그대로 유지됩니다 (#11).</p>
				</td>
			</tr>
			<tr>
				<th><label for="mm_adm_summary">한 줄 요약 / 초대 문구</label></th>
				<td>
					<textarea id="mm_adm_summary" name="mm_summary" rows="2" class="large-text"><?php echo esc_textarea( $inv['summary'] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="mm_adm_event_date">행사 날짜 / 시간</label></th>
				<td>
					<input type="date" id="mm_adm_event_date" name="mm_event_date" value="<?php echo esc_attr( $inv['event_date'] ); ?>" />
					<input type="time" name="mm_event_time" value="<?php echo esc_attr( $inv['event_time'] ); ?>" />
					~
					<input type="time" name="mm_event_end_time" value="<?php echo esc_attr( $inv['event_end_time'] ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="mm_adm_location_name">장소명 (#20)</label></th>
				<td>
					<input type="text" id="mm_adm_location_name" name="mm_location_name" class="regular-text" value="<?php echo esc_attr( $inv['location_name'] ); ?>" placeholder="OO 컨벤션센터" />
				</td>
			</tr>
			<tr>
				<th><label for="mm_adm_address">주소 (#21 네이버 지도 연동)</label></th>
				<td>
					<input type="text" id="mm_adm_address" name="mm_address" class="large-text" value="<?php echo esc_attr( $inv['address'] ); ?>" placeholder="부산광역시 OO구 OO로 123" />
				</td>
			</tr>
			<tr>
				<th>기능 ON/OFF (#23, #28, #30)</th>
				<td>
					<label style="margin-right:16px;">
						<input type="checkbox" name="mm_rsvp_enabled" value="1" <?php checked( $inv['rsvp_enabled'] ); ?> /> RSVP 참석 여부 사용
					</label>
					<label style="margin-right:16px;">
						<input type="checkbox" name="mm_guestbook_enabled" value="1" <?php checked( $inv['guestbook_enabled'] ); ?> /> 방명록 사용
					</label>
					<label>
						<input type="checkbox" name="mm_dday_enabled" value="1" <?php checked( $inv['dday_enabled'] ); ?> /> D-Day 표시
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="mm_adm_rsvp_deadline">RSVP 마감일 (#24)</label></th>
				<td>
					<input type="date" id="mm_adm_rsvp_deadline" name="mm_rsvp_deadline" value="<?php echo esc_attr( $inv['rsvp_deadline'] ); ?>" />
				</td>
			</tr>
			<tr>
				<th>공개 범위 & 공개기간 (#32, #34)</th>
				<td>
					<select name="mm_visibility">
						<option value="link_only" <?php selected( $inv['visibility'], 'link_only' ); ?>>링크를 아는 사람만</option>
						<option value="password" <?php selected( $inv['visibility'], 'password' ); ?>>비밀번호 보호</option>
						<option value="private" <?php selected( $inv['visibility'], 'private' ); ?>>비공개</option>
					</select>
					<select name="mm_expiration_mode">
						<option value="always" <?php selected( $inv['expiration_mode'], 'always' ); ?>>계속 공개</option>
						<option value="after_30" <?php selected( $inv['expiration_mode'], 'after_30' ); ?>>행사 후 30일</option>
						<option value="after_90" <?php selected( $inv['expiration_mode'], 'after_90' ); ?>>행사 후 90일</option>
						<option value="custom" <?php selected( $inv['expiration_mode'], 'custom' ); ?>>직접 설정</option>
					</select>
					<label style="margin-left:12px;">
						<input type="checkbox" name="mm_noindex" value="1" <?php checked( $inv['noindex'] ); ?> /> 검색엔진 NOINDEX (#33)
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Flexible Fields JSON/Table Editor in WP Admin.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public static function render_flexible_fields_box( $post ) {
		$inv = MM_Inv_Invitation::get_invitation( $post->ID );
		?>
		<p class="description">
			프론트엔드 편집기(<code><?php echo esc_html( add_query_arg( 'id', $post->ID, home_url( '/invitation-editor/' ) ) ); ?></code>)에서 실시간 모바일 미리보기와 함께 Drag &amp; Drop으로 편집할 수 있습니다.
		</p>
		<textarea name="mm_fields_json" rows="8" class="large-text code"><?php echo esc_textarea( wp_json_encode( $inv['fields'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></textarea>
		<?php
	}

	/**
	 * Render Short URL & QR Box in WP Admin sidebar (#17, #19).
	 *
	 * @param WP_Post $post Current post object.
	 */
	public static function render_short_url_box( $post ) {
		$inv = MM_Inv_Invitation::get_invitation( $post->ID );
		?>
		<p><strong>고유 단축 URL (#17):</strong></p>
		<p><a href="<?php echo esc_url( $inv['short_url'] ); ?>" target="_blank"><code><?php echo esc_html( $inv['short_url'] ); ?></code></a></p>
		<div class="mm-inv-qr-box" data-qr-url="<?php echo esc_attr( $inv['short_url'] ); ?>" data-qr-size="140">
			<div class="mm-inv-qr-canvas"></div>
		</div>
		<p style="margin-top:10px;">
			<a href="<?php echo esc_url( MM_Inv_Print::get_print_url( $inv['short_url'], 'a4' ) ); ?>" target="_blank" class="button">🖨️ 인쇄 / PDF 화면 열기</a>
		</p>
		<?php
	}

	/**
	 * Save meta box fields when saving `mm_invitation` in WP Admin.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save_meta_boxes( $post_id, $post ) {
		if ( ! isset( $_POST['mm_inv_admin_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mm_inv_admin_meta_nonce'] ) ), 'mm_inv_admin_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! MM_Inv_Security::can_manage_invitation( $post_id ) ) {
			return;
		}

		$code = get_post_meta( $post_id, '_mm_invitation_code', true );
		if ( empty( $code ) ) {
			update_post_meta( $post_id, '_mm_invitation_code', MM_Inv_Security::generate_unique_code() );
		}

		if ( isset( $_POST['mm_template'] ) ) {
			update_post_meta( $post_id, '_mm_template', sanitize_key( wp_unslash( $_POST['mm_template'] ) ) );
		}
		if ( isset( $_POST['mm_summary'] ) ) {
			update_post_meta( $post_id, '_mm_summary', sanitize_textarea_field( wp_unslash( $_POST['mm_summary'] ) ) );
		}
		if ( isset( $_POST['mm_event_date'] ) ) {
			update_post_meta( $post_id, '_mm_event_date', sanitize_text_field( wp_unslash( $_POST['mm_event_date'] ) ) );
		}
		if ( isset( $_POST['mm_event_time'] ) ) {
			update_post_meta( $post_id, '_mm_event_time', sanitize_text_field( wp_unslash( $_POST['mm_event_time'] ) ) );
		}
		if ( isset( $_POST['mm_event_end_time'] ) ) {
			update_post_meta( $post_id, '_mm_event_end_time', sanitize_text_field( wp_unslash( $_POST['mm_event_end_time'] ) ) );
		}
		if ( isset( $_POST['mm_location_name'] ) ) {
			update_post_meta( $post_id, '_mm_location_name', sanitize_text_field( wp_unslash( $_POST['mm_location_name'] ) ) );
		}
		if ( isset( $_POST['mm_address'] ) ) {
			update_post_meta( $post_id, '_mm_address', sanitize_text_field( wp_unslash( $_POST['mm_address'] ) ) );
		}

		update_post_meta( $post_id, '_mm_rsvp_enabled', ! empty( $_POST['mm_rsvp_enabled'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_mm_guestbook_enabled', ! empty( $_POST['mm_guestbook_enabled'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_mm_dday_enabled', ! empty( $_POST['mm_dday_enabled'] ) ? '1' : '0' );

		if ( isset( $_POST['mm_rsvp_deadline'] ) ) {
			update_post_meta( $post_id, '_mm_rsvp_deadline', sanitize_text_field( wp_unslash( $_POST['mm_rsvp_deadline'] ) ) );
		}
		if ( isset( $_POST['mm_visibility'] ) ) {
			update_post_meta( $post_id, '_mm_visibility', sanitize_key( wp_unslash( $_POST['mm_visibility'] ) ) );
		}
		if ( isset( $_POST['mm_expiration_mode'] ) ) {
			update_post_meta( $post_id, '_mm_expiration_mode', sanitize_key( wp_unslash( $_POST['mm_expiration_mode'] ) ) );
		}
		update_post_meta( $post_id, '_mm_noindex', ! empty( $_POST['mm_noindex'] ) ? '1' : '0' );

		if ( ! empty( $_POST['mm_fields_json'] ) ) {
			$sanitized = MM_Inv_Fields::sanitize_fields( $_POST['mm_fields_json'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! empty( $sanitized ) ) {
				update_post_meta( $post_id, '_mm_fields', $sanitized );
			}
		}
	}
}
