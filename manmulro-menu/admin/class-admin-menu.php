<?php
/**
 * WordPress Admin Menu for Manmulro Menu (`admin/class-admin-menu.php`)
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_Admin_Menu {

	/**
	 * Register WP Admin menu.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
	}

	/**
	 * Add top-level '메뉴판' admin menu.
	 */
	public static function register_menus() {
		add_menu_page(
			'만물로 메뉴판 관리',
			'메뉴판',
			'manage_options',
			'manmulro-menu',
			array( __CLASS__, 'render_projects_page' ),
			'dashicons-food',
			27
		);
	}

	/**
	 * Render Admin Projects Overview + `[원본 메뉴판 보기]` (#11).
	 */
	public static function render_projects_page() {
		global $wpdb;
		$t_projects = MM_Menu_DB::table( 'projects' );
		$projects   = $wpdb->get_results( "SELECT * FROM {$t_projects} ORDER BY updated_at DESC LIMIT 100", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		?>
		<div class="wrap">
			<h1>만물로 메뉴판 만들기 관리 (MANMULRO MENU V1)</h1>
			<p class="description">"메뉴는 한 번만 입력하세요." — OCR 원본 비교 입력과 직접 입력이 하나의 공통 메뉴 데이터로 관리됩니다.</p>

			<table class="wp-list-table widefat fixed striped" style="margin-top:16px;">
				<thead>
					<tr>
						<th>ID</th>
						<th>상호명</th>
						<th>업종</th>
						<th>디자인 템플릿</th>
						<th>상태</th>
						<th>QR 모바일 메뉴판 URL (#13)</th>
						<th>원본 메뉴판 보관 (#11)</th>
						<th>관리</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $projects ) ) : ?>
						<tr><td colspan="8">생성된 메뉴판 프로젝트가 없습니다.</td></tr>
					<?php else : ?>
						<?php foreach ( $projects as $p ) : ?>
							<?php
							$source_imgs = MM_Menu_Images_Module::get_source_images( (int) $p['project_id'] );
							$public_url  = home_url( '/menu/' . $p['store_slug'] );
							?>
							<tr>
								<td>#<?php echo (int) $p['project_id']; ?></td>
								<td><strong><?php echo esc_html( $p['business_name'] ); ?></strong></td>
								<td><?php echo esc_html( $p['business_type'] ); ?></td>
								<td><code><?php echo esc_html( $p['design_template'] ); ?></code></td>
								<td><?php echo esc_html( $p['status'] ); ?></td>
								<td><a href="<?php echo esc_url( $public_url ); ?>" target="_blank"><code>/menu/<?php echo esc_html( $p['store_slug'] ); ?></code></a></td>
								<td>
									<?php if ( ! empty( $source_imgs ) ) : ?>
										<a href="<?php echo esc_url( $source_imgs[0]['image_url'] ); ?>" target="_blank" class="button button-small">[원본 메뉴판 보기] (<?php echo count( $source_imgs ); ?>장)</a>
									<?php else : ?>
										<span style="color:#646970;">직접 입력</span>
									<?php endif; ?>
								</td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( 'project_id', (int) $p['project_id'], home_url( '/menu-builder/' ) ) ); ?>" class="button button-small">편집기 열기</a>
									<a href="<?php echo esc_url( MM_Menu_Print_Module::get_print_url( $public_url, 'a4', 'portrait' ) ); ?>" target="_blank" class="button button-small">인쇄 메뉴판</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
