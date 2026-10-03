<?php
/**
 * My Invitations Dashboard Template (`[manmulro_my_invitations]`)
 *
 * Implements sections #26, #27, #28, #35, #36, #37, #38:
 * - [+ 새 초대장 만들기]
 * - Tabs: 진행 예정 | 지난 행사 | 임시저장 | 보관 (#35)
 * - Invitation Cards: 대표사진, 제목, 행사일, 상태, RSVP 현황 +
 *   Actions: 보기, 관리(RSVP/방명록), 공유, 수정, 복제, 인쇄, QR, 보관, 삭제 (#36)
 * - Inline RSVP & Guestbook Manager with search, status filter (참석/불참/미정), and CSV download (#26, #27, #28)
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id    = get_current_user_id();
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'upcoming';
$manage_id  = isset( $_GET['manage_id'] ) ? (int) $_GET['manage_id'] : 0;

$posts = get_posts(
	array(
		'post_type'      => 'mm_invitation',
		'author'         => $user_id,
		'post_status'    => array( 'publish', 'draft', 'private', 'mm_archived' ),
		'posts_per_page' => 100,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

$today = current_time( 'Y-m-d' );
$tabs  = array(
	'upcoming' => array(
		'label' => '진행 예정',
		'items' => array(),
	),
	'past'     => array(
		'label' => '지난 행사',
		'items' => array(),
	),
	'draft'    => array(
		'label' => '임시저장',
		'items' => array(),
	),
	'archived' => array(
		'label' => '보관',
		'items' => array(),
	),
);

foreach ( $posts as $p ) {
	$inv = MM_Inv_Invitation::get_invitation( $p->ID );
	if ( ! $inv ) {
		continue;
	}
	$inv['rsvp_stats'] = MM_Inv_RSVP::get_stats( $inv['id'] );

	if ( 'ARCHIVED' === $inv['status'] ) {
		$tabs['archived']['items'][] = $inv;
	} elseif ( 'DRAFT' === $inv['status'] ) {
		$tabs['draft']['items'][] = $inv;
	} elseif ( ! empty( $inv['event_date'] ) && $inv['event_date'] < $today ) {
		$tabs['past']['items'][] = $inv;
	} else {
		$tabs['upcoming']['items'][] = $inv;
	}
}

if ( ! isset( $tabs[ $active_tab ] ) ) {
	$active_tab = 'upcoming';
}
$current_items = $tabs[ $active_tab ]['items'];
?>
<div class="mm-inv-my-wrap">

	<div class="mm-inv-my-header">
		<div>
			<span class="mm-inv-badge">MANMULRO INVITATION</span>
			<h2>내 초대장 관리</h2>
			<p>제작한 초대장의 공유·QR·인쇄·참석여부(RSVP)·방명록을 한곳에서 관리하세요.</p>
		</div>
		<a href="<?php echo esc_url( home_url( '/invitation-editor/' ) ); ?>" class="mm-inv-btn mm-inv-btn--primary">
			[+ 새 초대장 만들기]
		</a>
	</div>

	<!-- Status Tabs (#35) -->
	<nav class="mm-inv-my-tabs" aria-label="내 초대장 상태별 분류">
		<?php foreach ( $tabs as $tab_slug => $tab_data ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_slug, home_url( '/my-invitations/' ) ) ); ?>"
			   class="mm-inv-my-tab <?php echo ( $tab_slug === $active_tab ) ? 'is-active' : ''; ?>">
				<span><?php echo esc_html( $tab_data['label'] ); ?></span>
				<strong class="mm-inv-my-tab__count"><?php echo count( $tab_data['items'] ); ?></strong>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( $manage_id > 0 && MM_Inv_Security::can_manage_invitation( $manage_id ) ) : ?>
		<?php
		$managed_inv   = MM_Inv_Invitation::get_invitation( $manage_id );
		$rsvp_stats    = MM_Inv_RSVP::get_stats( $manage_id );
		$status_filter = isset( $_GET['rsvp_status'] ) ? sanitize_key( wp_unslash( $_GET['rsvp_status'] ) ) : '';
		$search_q      = isset( $_GET['rsvp_s'] ) ? sanitize_text_field( wp_unslash( $_GET['rsvp_s'] ) ) : '';
		$rsvp_rows     = MM_Inv_RSVP::get_responses( $manage_id, $status_filter, $search_q );
		$gb_rows       = MM_Inv_Guestbook::get_entries( $manage_id, true );
		$csv_url       = wp_nonce_url(
			add_query_arg(
				array(
					'action'        => 'mm_inv_export_rsvp_csv',
					'invitation_id' => $manage_id,
					'status'        => $status_filter,
					's'             => $search_q,
				),
				admin_url( 'admin-ajax.php' )
			),
			'mm_inv_export_csv_' . $manage_id
		);
		?>
		<!-- RSVP & Guestbook Management Panel (#26, #27, #28) -->
		<section class="mm-inv-manage-panel">
			<div class="mm-inv-manage-panel__head">
				<div>
					<span class="mm-inv-badge">RSVP · 방명록 통합 관리</span>
					<h3><?php echo esc_html( $managed_inv['title'] ); ?></h3>
				</div>
				<a href="<?php echo esc_url( home_url( '/my-invitations/?tab=' . $active_tab ) ); ?>" class="mm-inv-btn mm-inv-btn--outline">닫기 ✕</a>
			</div>

			<!-- Attendance Stats Summary (#26) -->
			<div class="mm-inv-stats-grid">
				<div class="mm-inv-stat-box">
					<span>전체 응답</span>
					<strong><?php echo (int) $rsvp_stats['total']; ?>건</strong>
				</div>
				<div class="mm-inv-stat-box mm-inv-stat-box--attending">
					<span>참석</span>
					<strong><?php echo (int) $rsvp_stats['attending']; ?>건</strong>
				</div>
				<div class="mm-inv-stat-box mm-inv-stat-box--declined">
					<span>불참</span>
					<strong><?php echo (int) $rsvp_stats['declined']; ?>건</strong>
				</div>
				<div class="mm-inv-stat-box mm-inv-stat-box--maybe">
					<span>미정</span>
					<strong><?php echo (int) $rsvp_stats['maybe']; ?>건</strong>
				</div>
				<div class="mm-inv-stat-box mm-inv-stat-box--highlight">
					<span>예상 참석 인원</span>
					<strong><?php echo (int) $rsvp_stats['expected_guests']; ?>명</strong>
				</div>
			</div>

			<!-- Search, Filter & CSV Export (#27) -->
			<form method="get" action="<?php echo esc_url( home_url( '/my-invitations/' ) ); ?>" class="mm-inv-rsvp-toolbar">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>" />
				<input type="hidden" name="manage_id" value="<?php echo (int) $manage_id; ?>" />

				<select name="rsvp_status" class="mm-inv-select">
					<option value="">전체 상태 필터</option>
					<option value="attending" <?php selected( $status_filter, 'attending' ); ?>>참석 필터</option>
					<option value="declined" <?php selected( $status_filter, 'declined' ); ?>>불참 필터</option>
					<option value="maybe" <?php selected( $status_filter, 'maybe' ); ?>>미정 필터</option>
				</select>

				<input type="search" name="rsvp_s" value="<?php echo esc_attr( $search_q ); ?>" placeholder="이름 또는 메시지 검색..." class="mm-inv-input" />
				<button type="submit" class="mm-inv-btn mm-inv-btn--outline">검색 / 필터</button>
				<a href="<?php echo esc_url( $csv_url ); ?>" class="mm-inv-btn mm-inv-btn--primary">📥 CSV 다운로드</a>
			</form>

			<div class="mm-inv-table-wrap">
				<table class="mm-inv-table">
					<thead>
						<tr>
							<th>이름</th>
							<th>상태</th>
							<th>인원</th>
							<th>메시지</th>
							<th>응답일</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rsvp_rows ) ) : ?>
							<tr><td colspan="5" style="text-align:center;color:#6b7280;">조건에 해당하는 참석 여부(RSVP) 응답이 없습니다.</td></tr>
						<?php else : ?>
							<?php foreach ( $rsvp_rows as $r ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $r['name'] ); ?></strong></td>
									<td>
										<span class="mm-inv-status-pill mm-inv-status-pill--<?php echo esc_attr( $r['status'] ); ?>">
											<?php echo esc_html( MM_Inv_RSVP::get_status_label( $r['status'] ) ); ?>
										</span>
									</td>
									<td><?php echo (int) $r['guest_count']; ?>명</td>
									<td><?php echo esc_html( $r['message'] ); ?></td>
									<td><?php echo esc_html( gmdate( 'm/d H:i', strtotime( $r['created_at'] ) ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Guestbook Moderation (#28) -->
			<h4 style="margin:24px 0 12px;">방명록 메시지 관리 (<?php echo count( $gb_rows ); ?>건)</h4>
			<div class="mm-inv-table-wrap">
				<table class="mm-inv-table">
					<thead>
						<tr>
							<th>이름</th>
							<th>메시지</th>
							<th>상태</th>
							<th>작성일</th>
							<th>관리</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $gb_rows ) ) : ?>
							<tr><td colspan="5" style="text-align:center;color:#6b7280;">등록된 방명록이 없습니다.</td></tr>
						<?php else : ?>
							<?php foreach ( $gb_rows as $g ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $g['name'] ); ?></strong></td>
									<td><?php echo esc_html( $g['message'] ); ?></td>
									<td><?php echo esc_html( $g['status'] ); ?></td>
									<td><?php echo esc_html( gmdate( 'm/d H:i', strtotime( $g['created_at'] ) ) ); ?></td>
									<td>
										<button type="button"
										        class="mm-inv-btn mm-inv-btn--sm mm-inv-btn--outline mm-js-gb-manage"
										        data-entry-id="<?php echo (int) $g['id']; ?>"
										        data-op="delete">삭제</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>
	<?php endif; ?>

	<!-- Invitation Cards Grid (#36) -->
	<div class="mm-inv-cards-grid">
		<?php if ( empty( $current_items ) ) : ?>
			<div class="mm-inv-empty-state">
				<p>해당 탭에 표시할 초대장이 없습니다.</p>
				<a href="<?php echo esc_url( home_url( '/invitation-editor/' ) ); ?>" class="mm-inv-btn mm-inv-btn--primary">+ 새 초대장 만들기</a>
			</div>
		<?php else : ?>
			<?php foreach ( $current_items as $item ) : ?>
				<article class="mm-inv-card-item">
					<div class="mm-inv-card-item__media">
						<?php if ( ! empty( $item['cover_image_url'] ) ) : ?>
							<img src="<?php echo esc_url( $item['cover_image_url'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" loading="lazy" />
						<?php else : ?>
							<div class="mm-inv-card-item__placeholder"><?php echo esc_html( $item['category_name'] ); ?></div>
						<?php endif; ?>
						<span class="mm-inv-card-item__status mm-inv-card-item__status--<?php echo esc_attr( strtolower( $item['status'] ) ); ?>">
							<?php echo esc_html( $item['status'] ); ?>
						</span>
					</div>

					<div class="mm-inv-card-item__body">
						<div class="mm-inv-card-item__meta">
							<span><?php echo esc_html( $item['category_name'] ); ?></span>
							<span>·</span>
							<span>행사일: <?php echo esc_html( $item['event_date'] ? $item['event_date'] : '미정' ); ?></span>
						</div>

						<h3 class="mm-inv-card-item__title"><?php echo esc_html( $item['title'] ); ?></h3>

						<div class="mm-inv-card-item__rsvp">
							<span>RSVP 응답 <strong><?php echo (int) $item['rsvp_stats']['total']; ?></strong>건</span>
							<span>·</span>
							<span>참석 <strong><?php echo (int) $item['rsvp_stats']['attending']; ?></strong>건 (예상 <?php echo (int) $item['rsvp_stats']['expected_guests']; ?>명)</span>
						</div>

						<!-- 9 Card Actions per Section #36: 보기, 관리, 공유, 수정, 복제, 인쇄, QR, 보관, 삭제 -->
						<div class="mm-inv-card-item__actions">
							<a href="<?php echo esc_url( $item['short_url'] ); ?>" target="_blank" class="mm-inv-action-btn">보기</a>
							<a href="<?php echo esc_url( add_query_arg( array( 'tab' => $active_tab, 'manage_id' => $item['id'] ), home_url( '/my-invitations/' ) ) ); ?>" class="mm-inv-action-btn">관리</a>
							<button type="button" class="mm-inv-action-btn" data-copy-text="<?php echo esc_attr( $item['short_url'] ); ?>">공유(복사)</button>
							<a href="<?php echo esc_url( add_query_arg( 'id', $item['id'], home_url( '/invitation-editor/' ) ) ); ?>" class="mm-inv-action-btn">수정</a>
							<button type="button" class="mm-inv-action-btn mm-js-duplicate-inv" data-invitation-id="<?php echo (int) $item['id']; ?>">복제</button>
							<a href="<?php echo esc_url( MM_Inv_Print::get_print_url( $item['short_url'], 'a4' ) ); ?>" target="_blank" class="mm-inv-action-btn">인쇄</a>
							<button type="button" class="mm-inv-action-btn mm-js-show-card-qr" data-short-url="<?php echo esc_attr( $item['short_url'] ); ?>">QR</button>
							<?php if ( 'ARCHIVED' !== $item['status'] ) : ?>
								<button type="button" class="mm-inv-action-btn mm-js-archive-inv" data-invitation-id="<?php echo (int) $item['id']; ?>" data-status="ARCHIVED">보관</button>
							<?php endif; ?>
							<button type="button" class="mm-inv-action-btn mm-inv-action-btn--danger mm-js-delete-inv" data-invitation-id="<?php echo (int) $item['id']; ?>">삭제</button>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<!-- QR Popup Modal -->
	<div id="mm-my-qr-modal" class="mm-inv-lightbox" hidden role="dialog" aria-modal="true">
		<div class="mm-inv-qr-modal-card">
			<button type="button" class="mm-inv-lightbox__close" id="mm-close-qr-modal">&times;</button>
			<h3>초대장 QR 코드 (#19)</h3>
			<div id="mm-my-qr-target" class="mm-inv-qr-box" data-qr-url="" data-qr-size="180">
				<div class="mm-inv-qr-canvas"></div>
			</div>
			<p class="mm-inv-qr-caption" id="mm-my-qr-url-label"></p>
		</div>
	</div>
</div>

<script>
	window.mmInvMyConfig = {
		ajaxUrl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		nonce: <?php echo wp_json_encode( wp_create_nonce( 'mm_inv_editor_nonce' ) ); ?>
	};
</script>
