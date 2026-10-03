<?php
/**
 * WordPress Admin Menu & Management Pages (#41, #42, #43)
 *
 * Menu structure (#41):
 * 초대장
 * ├── 전체 초대장
 * ├── 카테고리
 * ├── 템플릿
 * ├── RSVP
 * ├── 방명록
 * └── 설정
 * (+ 대시보드 통계 #42)
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Admin_Menu {

	/**
	 * Initialize admin menu and form handlers.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_admin_actions' ) );
		add_filter( 'manage_mm_invitation_posts_columns', array( __CLASS__, 'custom_columns' ) );
		add_action( 'manage_mm_invitation_posts_custom_column', array( __CLASS__, 'render_custom_column' ), 10, 2 );
	}

	/**
	 * Register top-level '초대장' menu and submenus (#41).
	 */
	public static function register_menus() {
		add_menu_page(
			'만물로 초대장 관리',
			'초대장',
			'manage_options',
			'manmulro-invitation',
			array( __CLASS__, 'render_dashboard_page' ),
			'dashicons-email-alt',
			26
		);

		add_submenu_page(
			'manmulro-invitation',
			'초대장 대시보드',
			'대시보드',
			'manage_options',
			'manmulro-invitation',
			array( __CLASS__, 'render_dashboard_page' )
		);

		add_submenu_page(
			'manmulro-invitation',
			'전체 초대장',
			'전체 초대장',
			'manage_options',
			'edit.php?post_type=mm_invitation'
		);

		add_submenu_page(
			'manmulro-invitation',
			'초대장 카테고리',
			'카테고리',
			'manage_options',
			'edit-tags.php?taxonomy=invitation_category&post_type=mm_invitation'
		);

		add_submenu_page(
			'manmulro-invitation',
			'초대장 템플릿 관리',
			'템플릿',
			'manage_options',
			'mm-inv-templates',
			array( __CLASS__, 'render_templates_page' )
		);

		add_submenu_page(
			'manmulro-invitation',
			'RSVP 참석 응답 관리',
			'RSVP',
			'manage_options',
			'mm-inv-rsvp',
			array( __CLASS__, 'render_rsvp_page' )
		);

		add_submenu_page(
			'manmulro-invitation',
			'방명록 관리',
			'방명록',
			'manage_options',
			'mm-inv-guestbook',
			array( __CLASS__, 'render_guestbook_page' )
		);

		add_submenu_page(
			'manmulro-invitation',
			'만물로 초대장 설정',
			'설정',
			'manage_options',
			'mm-inv-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Handle admin form submissions (Template creation/tier change, Settings save).
	 */
	public static function handle_admin_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Save Settings.
		if ( isset( $_POST['mm_inv_save_settings'] ) ) {
			check_admin_referer( 'mm_inv_save_settings_action', 'mm_inv_settings_nonce' );

			$settings = array(
				'kakao_js_key'       => isset( $_POST['kakao_js_key'] ) ? sanitize_text_field( wp_unslash( $_POST['kakao_js_key'] ) ) : '',
				'default_visibility' => isset( $_POST['default_visibility'] ) ? sanitize_key( wp_unslash( $_POST['default_visibility'] ) ) : 'link_only',
				'default_noindex'    => ! empty( $_POST['default_noindex'] ) ? 1 : 0,
				'default_expiration' => isset( $_POST['default_expiration'] ) ? sanitize_key( wp_unslash( $_POST['default_expiration'] ) ) : 'always',
				'max_gallery_images' => 10,
				'enable_rsvp_spam'   => ! empty( $_POST['enable_rsvp_spam'] ) ? 1 : 0,
			);

			update_option( 'mm_inv_settings', $settings );
			add_settings_error( 'mm_inv_notices', 'saved', '설정이 저장되었습니다.', 'updated' );
		}

		// Add or update custom template (#39, #40).
		if ( isset( $_POST['mm_inv_add_template'] ) ) {
			check_admin_referer( 'mm_inv_template_action', 'mm_inv_template_nonce' );

			$slug   = isset( $_POST['tpl_slug'] ) ? sanitize_key( wp_unslash( $_POST['tpl_slug'] ) ) : '';
			$name   = isset( $_POST['tpl_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tpl_name'] ) ) : '';
			$desc   = isset( $_POST['tpl_desc'] ) ? sanitize_text_field( wp_unslash( $_POST['tpl_desc'] ) ) : '';
			$tier   = isset( $_POST['tpl_tier'] ) && 'PREMIUM' === $_POST['tpl_tier'] ? 'PREMIUM' : 'FREE';
			$accent = isset( $_POST['tpl_accent'] ) ? sanitize_hex_color( wp_unslash( $_POST['tpl_accent'] ) ) : '#2563eb';
			$bg     = isset( $_POST['tpl_bg'] ) ? sanitize_hex_color( wp_unslash( $_POST['tpl_bg'] ) ) : '#ffffff';

			if ( ! empty( $slug ) && ! empty( $name ) ) {
				$custom          = get_option( 'mm_inv_custom_templates', array() );
				$custom[ $slug ] = array(
					'slug'        => $slug,
					'name'        => $name,
					'description' => $desc,
					'tier'        => $tier,
					'accent'      => $accent ? $accent : '#2563eb',
					'bg'          => $bg ? $bg : '#ffffff',
				);
				update_option( 'mm_inv_custom_templates', $custom );
				add_settings_error( 'mm_inv_notices', 'tpl_added', '템플릿이 저장되었습니다.', 'updated' );
			}
		}
	}

	/**
	 * Render Admin Dashboard (#42):
	 * 전체 초대장, 이번 달 생성, 공개 중, 전체 RSVP, 활성 회원, 인기 카테고리, 인기 템플릿
	 */
	public static function render_dashboard_page() {
		global $wpdb;

		$counts       = wp_count_posts( 'mm_invitation' );
		$published    = isset( $counts->publish ) ? (int) $counts->publish : 0;
		$drafts       = isset( $counts->draft ) ? (int) $counts->draft : 0;
		$archived     = isset( $counts->mm_archived ) ? (int) $counts->mm_archived : 0;
		$privates     = isset( $counts->private ) ? (int) $counts->private : 0;
		$total_inv    = $published + $drafts + $archived + $privates;

		$month_start  = gmdate( 'Y-m-01 00:00:00' );
		$this_month   = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mm_invitation' AND post_date >= %s AND post_status NOT IN ('trash', 'auto-draft')",
				$month_start
			)
		);

		$rsvp_table   = MM_Inv_RSVP::get_table_name();
		$total_rsvp   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rsvp_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$active_users = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_author) FROM {$wpdb->posts} WHERE post_type = 'mm_invitation'" );

		// Popular Categories.
		$terms = get_terms(
			array(
				'taxonomy'   => 'invitation_category',
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 5,
			)
		);

		// Popular Templates.
		$popular_tpls = $wpdb->get_results(
			"SELECT meta_value as tpl, COUNT(*) as cnt FROM {$wpdb->postmeta} WHERE meta_key = '_mm_template' GROUP BY meta_value ORDER BY cnt DESC LIMIT 5",
			ARRAY_A
		);
		?>
		<div class="wrap">
			<h1>만물로 초대장 대시보드 (MANMULRO INVITATION V1)</h1>
			<p class="description">세상의 모든 만남을 위한 범용 초대장 플랫폼 운영 현황입니다 (#42).</p>

			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:20px 0;">
				<div class="card" style="margin:0;padding:18px;">
					<span style="color:#646970;font-size:13px;">전체 초대장</span>
					<h2 style="margin:6px 0 0;font-size:28px;"><?php echo (int) $total_inv; ?>건</h2>
				</div>
				<div class="card" style="margin:0;padding:18px;">
					<span style="color:#646970;font-size:13px;">이번 달 생성</span>
					<h2 style="margin:6px 0 0;font-size:28px;color:#2563eb;"><?php echo (int) $this_month; ?>건</h2>
				</div>
				<div class="card" style="margin:0;padding:18px;">
					<span style="color:#646970;font-size:13px;">공개 중 (PUBLISHED)</span>
					<h2 style="margin:6px 0 0;font-size:28px;color:#059669;"><?php echo (int) $published; ?>건</h2>
				</div>
				<div class="card" style="margin:0;padding:18px;">
					<span style="color:#646970;font-size:13px;">전체 RSVP 응답</span>
					<h2 style="margin:6px 0 0;font-size:28px;"><?php echo (int) $total_rsvp; ?>건</h2>
				</div>
				<div class="card" style="margin:0;padding:18px;">
					<span style="color:#646970;font-size:13px;">활성 제작 회원</span>
					<h2 style="margin:6px 0 0;font-size:28px;"><?php echo (int) $active_users; ?>명</h2>
				</div>
			</div>

			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:20px;">
				<div class="card" style="max-width:none;margin:0;padding:20px;">
					<h3>인기 카테고리 TOP 5</h3>
					<ul>
						<?php if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) : ?>
							<?php foreach ( $terms as $t ) : ?>
								<li><strong><?php echo esc_html( $t->name ); ?></strong> — <?php echo (int) $t->count; ?>건</li>
							<?php endforeach; ?>
						<?php else : ?>
							<li>데이터가 없습니다.</li>
						<?php endif; ?>
					</ul>
				</div>

				<div class="card" style="max-width:none;margin:0;padding:20px;">
					<h3>인기 템플릿 TOP 5</h3>
					<ul>
						<?php if ( ! empty( $popular_tpls ) ) : ?>
							<?php foreach ( $popular_tpls as $row ) : ?>
								<li><strong><?php echo esc_html( strtoupper( $row['tpl'] ) ); ?></strong> — <?php echo (int) $row['cnt']; ?>건</li>
							<?php endforeach; ?>
						<?php else : ?>
							<li>아직 집계된 템플릿 사용 내역이 없습니다.</li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Templates Management Page (#39, #40).
	 */
	public static function render_templates_page() {
		$templates = MM_Inv_Invitation::get_templates();
		?>
		<div class="wrap">
			<h1>초대장 템플릿 관리 (FREE / PREMIUM 구분 지원 #39, #40)</h1>
			<p class="description">이벤트 데이터와 템플릿 디자인은 완전히 분리되어 있으며, 관리자가 새로운 템플릿을 추가하거나 FREE / PREMIUM 등급을 관리할 수 있습니다.</p>
			<?php settings_errors( 'mm_inv_notices' ); ?>

			<table class="wp-list-table widefat fixed striped" style="margin-top:16px;">
				<thead>
					<tr>
						<th>Slug</th>
						<th>템플릿 이름</th>
						<th>설명</th>
						<th>등급 (FREE / PREMIUM)</th>
						<th>색상 테마</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $templates as $slug => $tpl ) : ?>
						<tr>
							<td><code><?php echo esc_html( $slug ); ?></code></td>
							<td><strong><?php echo esc_html( $tpl['name'] ); ?></strong></td>
							<td><?php echo esc_html( $tpl['description'] ); ?></td>
							<td>
								<span style="padding:3px 8px;border-radius:6px;font-weight:700;font-size:11px;background:<?php echo 'PREMIUM' === $tpl['tier'] ? '#fce7f3' : '#e0f2fe'; ?>;color:<?php echo 'PREMIUM' === $tpl['tier'] ? '#be185d' : '#0369a1'; ?>;">
									<?php echo esc_html( $tpl['tier'] ); ?>
								</span>
							</td>
							<td>
								<span style="display:inline-block;width:16px;height:16px;border-radius:4px;background:<?php echo esc_attr( $tpl['accent'] ); ?>;vertical-align:middle;"></span>
								<code><?php echo esc_html( $tpl['accent'] ); ?></code>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2 style="margin-top:28px;">+ 새 템플릿 등록 / 등급 변경</h2>
			<form method="post" action="">
				<?php wp_nonce_field( 'mm_inv_template_action', 'mm_inv_template_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="tpl_slug">템플릿 Slug</label></th>
						<td><input type="text" id="tpl_slug" name="tpl_slug" required class="regular-text" placeholder="예: Ocean, Luxury" /></td>
					</tr>
					<tr>
						<th><label for="tpl_name">템플릿 이름</label></th>
						<td><input type="text" id="tpl_name" name="tpl_name" required class="regular-text" placeholder="예: Ocean (오션)" /></td>
					</tr>
					<tr>
						<th><label for="tpl_desc">설명</label></th>
						<td><input type="text" id="tpl_desc" name="tpl_desc" class="large-text" placeholder="템플릿 디자인 설명" /></td>
					</tr>
					<tr>
						<th><label for="tpl_tier">구분 (#40)</label></th>
						<td>
							<select id="tpl_tier" name="tpl_tier">
								<option value="FREE">FREE (무료)</option>
								<option value="PREMIUM">PREMIUM (프리미엄)</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="tpl_accent">포인트 색상 / 배경 색상</label></th>
						<td>
							<input type="color" id="tpl_accent" name="tpl_accent" value="#0284c7" />
							<input type="color" id="tpl_bg" name="tpl_bg" value="#f0f9ff" />
						</td>
					</tr>
				</table>
				<p class="submit"><button type="submit" name="mm_inv_add_template" value="1" class="button button-primary">템플릿 저장</button></p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render RSVP Admin Page (#26, #27, #41).
	 */
	public static function render_rsvp_page() {
		$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$rows          = MM_Inv_RSVP::get_responses( 0, $status_filter, $search );
		$csv_url       = wp_nonce_url(
			add_query_arg(
				array(
					'action'        => 'mm_inv_export_rsvp_csv',
					'invitation_id' => 0,
					'status'        => $status_filter,
					's'             => $search,
				),
				admin_url( 'admin-ajax.php' )
			),
			'mm_inv_export_csv_0'
		);
		?>
		<div class="wrap">
			<h1>전체 RSVP 응답 관리 (#26, #27)</h1>
			<form method="get" action="" style="margin:16px 0;display:flex;gap:8px;align-items:center;">
				<input type="hidden" name="page" value="mm-inv-rsvp" />
				<select name="status">
					<option value="">전체 상태</option>
					<option value="attending" <?php selected( $status_filter, 'attending' ); ?>>참석</option>
					<option value="declined" <?php selected( $status_filter, 'declined' ); ?>>불참</option>
					<option value="maybe" <?php selected( $status_filter, 'maybe' ); ?>>미정</option>
				</select>
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="이름 또는 메시지 검색" />
				<button type="submit" class="button">필터 적용</button>
				<a href="<?php echo esc_url( $csv_url ); ?>" class="button button-primary">CSV 다운로드</a>
			</form>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>초대장</th>
						<th>이름</th>
						<th>상태</th>
						<th>인원</th>
						<th>메시지</th>
						<th>응답일</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="6">등록된 RSVP 응답이 없습니다.</td></tr>
					<?php else : ?>
						<?php foreach ( $rows as $r ) : ?>
							<tr>
								<td><?php echo esc_html( get_the_title( (int) $r['invitation_id'] ) ); ?> (#<?php echo (int) $r['invitation_id']; ?>)</td>
								<td><strong><?php echo esc_html( $r['name'] ); ?></strong></td>
								<td><?php echo esc_html( MM_Inv_RSVP::get_status_label( $r['status'] ) ); ?></td>
								<td><?php echo (int) $r['guest_count']; ?>명</td>
								<td><?php echo esc_html( $r['message'] ); ?></td>
								<td><?php echo esc_html( $r['created_at'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Guestbook Admin Page (#28, #29, #41).
	 */
	public static function render_guestbook_page() {
		$rows = MM_Inv_Guestbook::get_entries( 0, true );
		?>
		<div class="wrap">
			<h1>전체 방명록 관리 (#28, #29)</h1>
			<table class="wp-list-table widefat fixed striped" style="margin-top:16px;">
				<thead>
					<tr>
						<th>초대장</th>
						<th>작성자</th>
						<th>메시지</th>
						<th>상태</th>
						<th>작성일</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="5">등록된 방명록 메시지가 없습니다.</td></tr>
					<?php else : ?>
						<?php foreach ( $rows as $g ) : ?>
							<tr>
								<td><?php echo esc_html( get_the_title( (int) $g['invitation_id'] ) ); ?></td>
								<td><strong><?php echo esc_html( $g['name'] ); ?></strong></td>
								<td><?php echo esc_html( $g['message'] ); ?></td>
								<td><?php echo esc_html( $g['status'] ); ?></td>
								<td><?php echo esc_html( $g['created_at'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Settings Page (#41).
	 */
	public static function render_settings_page() {
		$settings = get_option( 'mm_inv_settings', array() );
		?>
		<div class="wrap">
			<h1>만물로 초대장 설정</h1>
			<?php settings_errors( 'mm_inv_notices' ); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'mm_inv_save_settings_action', 'mm_inv_settings_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="kakao_js_key">카카오톡 공유 JavaScript 키 (#18)</label></th>
						<td>
							<input type="text" id="kakao_js_key" name="kakao_js_key" class="regular-text" value="<?php echo esc_attr( isset( $settings['kakao_js_key'] ) ? $settings['kakao_js_key'] : '' ); ?>" />
							<p class="description">카카오 디벨로퍼스의 JavaScript 키를 입력하면 카카오톡 공유 버튼에서 Kakao Link 메시지가 발송됩니다.</p>
						</td>
					</tr>
					<tr>
						<th><label for="default_visibility">기본 공개 범위 (#32)</label></th>
						<td>
							<select id="default_visibility" name="default_visibility">
								<option value="link_only" <?php selected( isset( $settings['default_visibility'] ) ? $settings['default_visibility'] : 'link_only', 'link_only' ); ?>>링크를 아는 사람만 (권장)</option>
								<option value="password" <?php selected( isset( $settings['default_visibility'] ) ? $settings['default_visibility'] : '', 'password' ); ?>>비밀번호 보호</option>
								<option value="private" <?php selected( isset( $settings['default_visibility'] ) ? $settings['default_visibility'] : '', 'private' ); ?>>비공개</option>
							</select>
						</td>
					</tr>
					<tr>
						<th>검색엔진 노출 기본값 (#33)</th>
						<td>
							<label>
								<input type="checkbox" name="default_noindex" value="1" <?php checked( ! isset( $settings['default_noindex'] ) || ! empty( $settings['default_noindex'] ) ); ?> />
								기본값으로 <code>NOINDEX</code> 적용 (개인정보 보호 권장)
							</label>
						</td>
					</tr>
					<tr>
						<th>스팸 방지 (#44)</th>
						<td>
							<label>
								<input type="checkbox" name="enable_rsvp_spam" value="1" <?php checked( ! isset( $settings['enable_rsvp_spam'] ) || ! empty( $settings['enable_rsvp_spam'] ) ); ?> />
								RSVP 및 방명록 Honeypot + IP Rate-Limiting 활성화
							</label>
						</td>
					</tr>
				</table>
				<p class="submit"><button type="submit" name="mm_inv_save_settings" value="1" class="button button-primary">설정 저장</button></p>
			</form>
		</div>
		<?php
	}

	/**
	 * Customize columns in WP Admin -> 전체 초대장 (`edit.php?post_type=mm_invitation`).
	 *
	 * @param array $cols Existing columns.
	 * @return array
	 */
	public static function custom_columns( $cols ) {
		return array(
			'cb'                          => $cols['cb'],
			'title'                       => '초대장 제목',
			'taxonomy-invitation_category'=> '카테고리',
			'mm_template'                 => '템플릿',
			'mm_event_date'               => '행사일',
			'mm_short_url'                => '고유 URL (/i/code)',
			'mm_rsvp'                     => 'RSVP 현황',
			'author'                      => '작성자',
			'date'                        => '생성일',
		);
	}

	/**
	 * Render custom column values in WP Admin -> 전체 초대장.
	 *
	 * @param string $col     Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function render_custom_column( $col, $post_id ) {
		switch ( $col ) {
			case 'mm_template':
				echo esc_html( strtoupper( (string) get_post_meta( $post_id, '_mm_template', true ) ) );
				break;
			case 'mm_event_date':
				echo esc_html( (string) get_post_meta( $post_id, '_mm_event_date', true ) );
				break;
			case 'mm_short_url':
				$code = get_post_meta( $post_id, '_mm_invitation_code', true );
				if ( $code ) {
					$url = home_url( '/i/' . $code );
					echo '<a href="' . esc_url( $url ) . '" target="_blank"><code>/i/' . esc_html( $code ) . '</code></a>';
				}
				break;
			case 'mm_rsvp':
				$stats = MM_Inv_RSVP::get_stats( $post_id );
				echo sprintf( '응답 %d (참석 %d / %d명)', (int) $stats['total'], (int) $stats['attending'], (int) $stats['expected_guests'] );
				break;
		}
	}
}
