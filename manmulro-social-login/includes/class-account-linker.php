<?php
/**
 * Social Account Linker & DB Table Manager (`wp_manmulro_social_accounts`)
 *
 * Implements sections #7, #12, #13, #14 of the MANMULRO SOCIAL LOGIN specification.
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Account_Linker {

	/**
	 * Return full table name (`wp_manmulro_social_accounts`).
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'manmulro_social_accounts';
	}

	/**
	 * Create or upgrade the `wp_manmulro_social_accounts` table via dbDelta (#7).
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			provider varchar(32) NOT NULL,
			provider_user_id varchar(191) NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY provider_user (provider, provider_user_id),
			KEY user_id (user_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Find the WordPress User ID linked to a given (provider, provider_user_id).
	 *
	 * @param string $provider         Provider slug (kakao, naver, google).
	 * @param string $provider_user_id Unique ID from provider.
	 * @return int WordPress user ID or 0 if not found.
	 */
	public function get_user_id_by_provider( $provider, $provider_user_id ) {
		global $wpdb;

		$table = self::get_table_name();
		$uid   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$table} WHERE provider = %s AND provider_user_id = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				sanitize_key( $provider ),
				(string) $provider_user_id
			)
		);

		return $uid ? (int) $uid : 0;
	}

	/**
	 * Get all linked social accounts for a WordPress user.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return array List of associative rows Keyed by provider slug.
	 */
	public function get_linked_accounts( $user_id ) {
		global $wpdb;

		$table = self::get_table_name();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, user_id, provider, provider_user_id, created_at, updated_at FROM {$table} WHERE user_id = %d ORDER BY created_at ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $user_id
			),
			ARRAY_A
		);

		$by_provider = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$by_provider[ $row['provider'] ] = $row;
			}
		}

		return $by_provider;
	}

	/**
	 * Link a social account to a WordPress User (#12, #13).
	 * Enforces UNIQUE(provider, provider_user_id) so one social account cannot link to multiple WP users.
	 *
	 * @param int    $user_id          WordPress User ID.
	 * @param string $provider         Provider slug.
	 * @param string $provider_user_id Provider's unique user ID.
	 * @return true|WP_Error
	 */
	public function link_account( $user_id, $provider, $provider_user_id ) {
		global $wpdb;

		$user_id          = (int) $user_id;
		$provider         = sanitize_key( $provider );
		$provider_user_id = trim( (string) $provider_user_id );

		if ( $user_id <= 0 || empty( $provider ) || '' === $provider_user_id ) {
			return new WP_Error( 'invalid_link_params', '계정 연결에 필요한 정보가 올바르지 않습니다.' );
		}

		$existing_owner = $this->get_user_id_by_provider( $provider, $provider_user_id );
		if ( $existing_owner > 0 && $existing_owner !== $user_id ) {
			return new WP_Error(
				'already_linked_other_user',
				'이미 다른 만물로 계정에 연결된 소셜 계정입니다.'
			);
		}

		if ( $existing_owner === $user_id ) {
			// Update timestamp.
			$wpdb->update(
				self::get_table_name(),
				array( 'updated_at' => current_time( 'mysql' ) ),
				array(
					'user_id'  => $user_id,
					'provider' => $provider,
				),
				array( '%s' ),
				array( '%d', '%s' )
			);
			return true;
		}

		// Check if user already linked a different account on the same provider.
		$user_links = $this->get_linked_accounts( $user_id );
		if ( isset( $user_links[ $provider ] ) ) {
			return new WP_Error(
				'provider_already_connected',
				'해당 소셜 로그인 수단은 이미 연결되어 있습니다.'
			);
		}

		$now      = current_time( 'mysql' );
		$inserted = $wpdb->insert(
			self::get_table_name(),
			array(
				'user_id'          => $user_id,
				'provider'         => $provider,
				'provider_user_id' => $provider_user_id,
				'created_at'       => $now,
				'updated_at'       => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'db_insert_error', '소셜 계정 연결 중 데이터베이스 오류가 발생했습니다.' );
		}

		return true;
	}

	/**
	 * Unlink a social account from a WordPress User with Last-Method Protection (#14).
	 *
	 * If the user only has 1 social provider linked and has not set up an Email/Password login,
	 * unlinking is blocked with: "다른 로그인 방법을 먼저 연결해주세요."
	 *
	 * @param int    $user_id  WordPress User ID.
	 * @param string $provider Provider slug to unlink.
	 * @return true|WP_Error
	 */
	public function unlink_account( $user_id, $provider ) {
		global $wpdb;

		$user_id  = (int) $user_id;
		$provider = sanitize_key( $provider );

		$linked = $this->get_linked_accounts( $user_id );
		if ( ! isset( $linked[ $provider ] ) ) {
			return new WP_Error( 'not_linked', '연결되어 있지 않은 소셜 계정입니다.' );
		}

		$remaining_social_count = count( $linked ) - 1;
		$has_password_login     = MM_SL_User::user_has_password_login( $user_id );

		if ( $remaining_social_count <= 0 && ! $has_password_login ) {
			return new WP_Error(
				'last_login_method',
				'다른 로그인 방법을 먼저 연결해주세요.'
			);
		}

		$deleted = $wpdb->delete(
			self::get_table_name(),
			array(
				'user_id'  => $user_id,
				'provider' => $provider,
			),
			array( '%d', '%s' )
		);

		if ( false === $deleted ) {
			return new WP_Error( 'unlink_failed', '연결 해제 중 오류가 발생했습니다.' );
		}

		return true;
	}

	/**
	 * Remove all social account links for a user (used only when a user is permanently deleted in WP).
	 *
	 * @param int $user_id WordPress User ID.
	 */
	public function delete_all_for_user( $user_id ) {
		global $wpdb;
		$wpdb->delete(
			self::get_table_name(),
			array( 'user_id' => (int) $user_id ),
			array( '%d' )
		);
	}
}
