<?php
/**
 * RSVP System & Database Table (`wp_mm_invitation_rsvp`) (#23, #24, #25, #26, #27)
 *
 * Features:
 * - ON/OFF per invitation (#23)
 * -Statuses: attending (참석), declined (불참), maybe (미정) (#23)
 * - Guest count & message (#23)
 * - RSVP Deadline (`_mm_rsvp_deadline`): shows "참석 여부 응답이 마감되었습니다." after deadline (#24)
 * - Summary statistics (전체 응답, 참석, 불참, 미정, 예상 참석 인원) (#26)
 * - Search, status filter, and UTF-8 BOM CSV export (#27)
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_RSVP {

	/**
	 * Initialize AJAX & CSV export hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_mm_inv_submit_rsvp', array( __CLASS__, 'ajax_submit_rsvp' ) );
		add_action( 'wp_ajax_nopriv_mm_inv_submit_rsvp', array( __CLASS__, 'ajax_submit_rsvp' ) );
		add_action( 'wp_ajax_mm_inv_export_rsvp_csv', array( __CLASS__, 'ajax_export_csv' ) );
		add_action( 'wp_ajax_mm_inv_delete_rsvp', array( __CLASS__, 'ajax_delete_rsvp' ) );
	}

	/**
	 * Get table name (`wp_mm_invitation_rsvp`) (#25).
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mm_invitation_rsvp';
	}

	/**
	 * Create `wp_mm_invitation_rsvp` table (#25).
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			invitation_id bigint(20) unsigned NOT NULL,
			name varchar(120) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'attending',
			guest_count int(11) unsigned NOT NULL DEFAULT 1,
			message text,
			ip_hash varchar(64) DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY invitation_id (invitation_id),
			KEY status (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Check if RSVP deadline has passed (#24).
	 *
	 * @param int $invitation_id Invitation ID.
	 * @return bool True if closed/expired.
	 */
	public static function is_deadline_passed( $invitation_id ) {
		$deadline = get_post_meta( (int) $invitation_id, '_mm_rsvp_deadline', true );
		if ( empty( $deadline ) ) {
			return false;
		}

		$today = current_time( 'Y-m-d' );
		return ( $today > $deadline );
	}

	/**
	 * Insert a new RSVP response (#23, #24, #25, #44).
	 *
	 * @param int    $invitation_id Invitation post ID.
	 * @param string $name          Guest name.
	 * @param string $status        'attending', 'declined', or 'maybe'.
	 * @param int    $guest_count   Number of attendees.
	 * @param string $message       Optional message.
	 * @return int|WP_Error Inserted row ID or WP_Error.
	 */
	public static function add_response( $invitation_id, $name, $status, $guest_count = 1, $message = '' ) {
		global $wpdb;

		$invitation_id = (int) $invitation_id;
		$enabled       = get_post_meta( $invitation_id, '_mm_rsvp_enabled', true );
		if ( '0' === (string) $enabled ) {
			return new WP_Error( 'rsvp_disabled', '이 초대장은 참석 여부(RSVP) 응답을 받지 않습니다.' );
		}

		if ( self::is_deadline_passed( $invitation_id ) ) {
			return new WP_Error( 'rsvp_closed', '참석 여부 응답이 마감되었습니다.' );
		}

		$name = trim( sanitize_text_field( $name ) );
		if ( '' === $name ) {
			return new WP_Error( 'empty_name', '성함을 입력해주세요.' );
		}

		$allowed_statuses = array( 'attending', 'declined', 'maybe' );
		$status           = sanitize_key( $status );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'attending';
		}

		$guest_count = max( 0, min( 50, (int) $guest_count ) );
		if ( 'declined' === $status ) {
			$guest_count = 0;
		} elseif ( $guest_count < 1 ) {
			$guest_count = 1;
		}

		$message = sanitize_textarea_field( $message );
		$now     = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			self::get_table_name(),
			array(
				'invitation_id' => $invitation_id,
				'name'          => $name,
				'status'        => $status,
				'guest_count'   => $guest_count,
				'message'       => $message,
				'ip_hash'       => MM_Inv_Security::get_visitor_ip_hash(),
				'created_at'    => $now,
				'updated_at'    => $now,
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'db_error', '응답 저장 중 오류가 발생했습니다.' );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get attendance summary statistics for an invitation (#26).
	 * Returns: total, attending, declined, maybe, expected_guests.
	 *
	 * @param int $invitation_id Invitation post ID.
	 * @return array
	 */
	public static function get_stats( $invitation_id ) {
		global $wpdb;

		$table = self::get_table_name();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT status, COUNT(*) as cnt, SUM(guest_count) as heads FROM {$table} WHERE invitation_id = %d GROUP BY status", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $invitation_id
			),
			ARRAY_A
		);

		$stats = array(
			'total'           => 0,
			'attending'       => 0,
			'declined'        => 0,
			'maybe'           => 0,
			'expected_guests' => 0,
		);

		if ( is_array( $rows ) ) {
			foreach ( $rows as $r ) {
				$st  = $r['status'];
				$cnt = (int) $r['cnt'];
				$stats['total'] += $cnt;
				if ( isset( $stats[ $st ] ) ) {
					$stats[ $st ] = $cnt;
				}
				if ( 'attending' === $st ) {
					$stats['expected_guests'] += (int) $r['heads'];
				}
			}
		}

		return $stats;
	}

	/**
	 * Query RSVP list with search and status filter (#27).
	 *
	 * @param int    $invitation_id Invitation ID (0 for all invitations in admin).
	 * @param string $status_filter Optional 'attending', 'declined', 'maybe'.
	 * @param string $search        Optional search keyword.
	 * @return array
	 */
	public static function get_responses( $invitation_id = 0, $status_filter = '', $search = '' ) {
		global $wpdb;

		$table   = self::get_table_name();
		$where   = array( '1=1' );
		$params  = array();

		if ( $invitation_id > 0 ) {
			$where[]  = 'invitation_id = %d';
			$params[] = (int) $invitation_id;
		}

		if ( ! empty( $status_filter ) && in_array( $status_filter, array( 'attending', 'declined', 'maybe' ), true ) ) {
			$where[]  = 'status = %s';
			$params[] = $status_filter;
		}

		if ( ! empty( $search ) ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(name LIKE %s OR message LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC';

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $results ) ? $results : array();
	}

	/**
	 * Convert status slug to Korean label (#23, #27).
	 *
	 * @param string $status Status code.
	 * @return string
	 */
	public static function get_status_label( $status ) {
		switch ( $status ) {
			case 'attending':
				return '참석';
			case 'declined':
				return '불참';
			case 'maybe':
				return '미정';
			default:
				return '참석';
		}
	}

	/**
	 * AJAX handler for visitor RSVP submission (no login required #4, #23).
	 */
	public static function ajax_submit_rsvp() {
		check_ajax_referer( 'mm_inv_public_nonce', 'nonce' );

		$invitation_id = isset( $_POST['invitation_id'] ) ? (int) $_POST['invitation_id'] : 0;
		$honeypot      = isset( $_POST['mm_hp_website'] ) ? sanitize_text_field( wp_unslash( $_POST['mm_hp_website'] ) ) : '';

		$spam_check = MM_Inv_Security::verify_public_submission( 'rsvp', $invitation_id, $honeypot );
		if ( is_wp_error( $spam_check ) ) {
			wp_send_json_error( array( 'message' => $spam_check->get_error_message() ) );
		}

		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$status      = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'attending';
		$guest_count = isset( $_POST['guest_count'] ) ? (int) $_POST['guest_count'] : 1;
		$message     = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		$res = self::add_response( $invitation_id, $name, $status, $guest_count, $message );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => '참석 여부 응답이 전달되었습니다. 감사합니다!',
				'stats'   => self::get_stats( $invitation_id ),
			)
		);
	}

	/**
	 * AJAX handler to export RSVP list as CSV with UTF-8 BOM (#27, #43).
	 */
	public static function ajax_export_csv() {
		$invitation_id = isset( $_GET['invitation_id'] ) ? (int) $_GET['invitation_id'] : 0;
		$nonce         = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'mm_inv_export_csv_' . $invitation_id ) ) {
			wp_die( '보안 검증에 실패했습니다.' );
		}

		if ( $invitation_id > 0 ) {
			if ( ! MM_Inv_Security::can_manage_invitation( $invitation_id ) ) {
				wp_die( '권한이 없습니다.' );
			}
		} elseif ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '권한이 없습니다.' );
		}

		$status_filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$rows          = self::get_responses( $invitation_id, $status_filter, $search );

		$filename = 'manmulro-rsvp-' . ( $invitation_id > 0 ? $invitation_id : 'all' ) . '-' . gmdate( 'Ymd' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		// Output UTF-8 BOM so Korean characters display properly in Microsoft Excel.
		echo "\xEF\xBB\xBF";

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( '이름', '상태', '인원', '메시지', '응답일' ) );

		foreach ( $rows as $row ) {
			fputcsv(
				$out,
				array(
					$row['name'],
					self::get_status_label( $row['status'] ),
					(int) $row['guest_count'],
					$row['message'],
					$row['created_at'],
				)
			);
		}

		fclose( $out );
		exit;
	}

	/**
	 * AJAX handler for invitation owner to delete an RSVP entry.
	 */
	public static function ajax_delete_rsvp() {
		check_ajax_referer( 'mm_inv_editor_nonce', 'nonce' );

		global $wpdb;
		$rsvp_id = isset( $_POST['rsvp_id'] ) ? (int) $_POST['rsvp_id'] : 0;
		$table   = self::get_table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $rsvp_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $row || ! MM_Inv_Security::can_manage_invitation( (int) $row['invitation_id'] ) ) {
			wp_send_json_error( array( 'message' => '삭제 권한이 없습니다.' ) );
		}

		$wpdb->delete( $table, array( 'id' => $rsvp_id ), array( '%d' ) );
		wp_send_json_success( array( 'message' => '삭제되었습니다.' ) );
	}
}
