<?php
/**
 * Guestbook System & Database Table (`wp_mm_invitation_guestbook`) (#28, #29)
 *
 * Features:
 * - ON/OFF per invitation (#28)
 * - Visitor submission without login: Name + Message (#4, #28)
 * - Spam protection (honeypot + rate limiting) (#28, #44)
 * - Invitation owner can manage (hide / delete) messages on their invitation (#28, #43)
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Guestbook {

	/**
	 * Initialize AJAX handlers.
	 */
	public static function init() {
		add_action( 'wp_ajax_mm_inv_submit_guestbook', array( __CLASS__, 'ajax_submit_entry' ) );
		add_action( 'wp_ajax_nopriv_mm_inv_submit_guestbook', array( __CLASS__, 'ajax_submit_entry' ) );
		add_action( 'wp_ajax_mm_inv_manage_guestbook', array( __CLASS__, 'ajax_manage_entry' ) );
	}

	/**
	 * Return full table name (`wp_mm_invitation_guestbook`) (#29).
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mm_invitation_guestbook';
	}

	/**
	 * Create `wp_mm_invitation_guestbook` table via dbDelta (#29).
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			invitation_id bigint(20) unsigned NOT NULL,
			name varchar(120) NOT NULL,
			message text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'approved',
			ip_hash varchar(64) DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY invitation_id (invitation_id),
			KEY status (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Add a guestbook entry (#28, #29).
	 *
	 * @param int    $invitation_id Invitation post ID.
	 * @param string $name          Writer name.
	 * @param string $message       Message text.
	 * @return array|WP_Error Inserted entry or WP_Error.
	 */
	public static function add_entry( $invitation_id, $name, $message ) {
		global $wpdb;

		$invitation_id = (int) $invitation_id;
		$enabled       = get_post_meta( $invitation_id, '_mm_guestbook_enabled', true );
		if ( '0' === (string) $enabled ) {
			return new WP_Error( 'guestbook_disabled', '방명록 기능이 비활성화되어 있습니다.' );
		}

		$name    = trim( sanitize_text_field( $name ) );
		$message = trim( sanitize_textarea_field( $message ) );

		if ( '' === $name || '' === $message ) {
			return new WP_Error( 'empty_fields', '이름과 메시지를 모두 입력해주세요.' );
		}

		$now      = current_time( 'mysql' );
		$inserted = $wpdb->insert(
			self::get_table_name(),
			array(
				'invitation_id' => $invitation_id,
				'name'          => $name,
				'message'       => $message,
				'status'        => 'approved',
				'ip_hash'       => MM_Inv_Security::get_visitor_ip_hash(),
				'created_at'    => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'db_error', '방명록 저장 중 오류가 발생했습니다.' );
		}

		return array(
			'id'         => (int) $wpdb->insert_id,
			'name'       => $name,
			'message'    => $message,
			'created_at' => gmdate( 'm/d H:i', strtotime( $now ) ),
		);
	}

	/**
	 * Get guestbook entries for an invitation.
	 *
	 * @param int  $invitation_id  Invitation post ID (0 for all in admin).
	 * @param bool $include_hidden Whether to include hidden entries for owner/admin.
	 * @return array
	 */
	public static function get_entries( $invitation_id = 0, $include_hidden = false ) {
		global $wpdb;

		$table  = self::get_table_name();
		$where  = array( '1=1' );
		$params = array();

		if ( $invitation_id > 0 ) {
			$where[]  = 'invitation_id = %d';
			$params[] = (int) $invitation_id;
		}

		if ( ! $include_hidden ) {
			$where[] = "status = 'approved'";
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC LIMIT 100';
		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * AJAX handler for visitor guestbook submission (#28).
	 */
	public static function ajax_submit_entry() {
		check_ajax_referer( 'mm_inv_public_nonce', 'nonce' );

		$invitation_id = isset( $_POST['invitation_id'] ) ? (int) $_POST['invitation_id'] : 0;
		$honeypot      = isset( $_POST['mm_hp_website'] ) ? sanitize_text_field( wp_unslash( $_POST['mm_hp_website'] ) ) : '';

		$spam_check = MM_Inv_Security::verify_public_submission( 'guestbook', $invitation_id, $honeypot );
		if ( is_wp_error( $spam_check ) ) {
			wp_send_json_error( array( 'message' => $spam_check->get_error_message() ) );
		}

		$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		$entry = self::add_entry( $invitation_id, $name, $message );
		if ( is_wp_error( $entry ) ) {
			wp_send_json_error( array( 'message' => $entry->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => '방명록 메시지가 등록되었습니다.',
				'entry'   => $entry,
			)
		);
	}

	/**
	 * AJAX handler for invitation author or admin to hide/approve/delete a guestbook entry (#28, #43).
	 */
	public static function ajax_manage_entry() {
		check_ajax_referer( 'mm_inv_editor_nonce', 'nonce' );

		global $wpdb;
		$entry_id = isset( $_POST['entry_id'] ) ? (int) $_POST['entry_id'] : 0;
		$op       = isset( $_POST['operation'] ) ? sanitize_key( wp_unslash( $_POST['operation'] ) ) : 'delete';
		$table    = self::get_table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $entry_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $row || ! MM_Inv_Security::can_manage_invitation( (int) $row['invitation_id'] ) ) {
			wp_send_json_error( array( 'message' => '권한이 없습니다.' ) );
		}

		if ( 'delete' === $op ) {
			$wpdb->delete( $table, array( 'id' => $entry_id ), array( '%d' ) );
		} elseif ( 'hide' === $op ) {
			$wpdb->update( $table, array( 'status' => 'hidden' ), array( 'id' => $entry_id ), array( '%s' ), array( '%d' ) );
		} elseif ( 'approve' === $op ) {
			$wpdb->update( $table, array( 'status' => 'approved' ), array( 'id' => $entry_id ), array( '%s' ), array( '%d' ) );
		}

		wp_send_json_success( array( 'message' => '처리되었습니다.' ) );
	}
}
