<?php
/**
 * WordPress Admin Users Integration (`admin/users.php`)
 *
 * Displays connected social accounts in WP Admin -> Users list and User Profile.
 *
 * @package Manmulro_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SL_Admin_Users {

	/**
	 * Account linker.
	 *
	 * @var MM_SL_Account_Linker
	 */
	private static $linker;

	/**
	 * Initialize hooks.
	 *
	 * @param MM_SL_Account_Linker $linker Account linker.
	 */
	public static function init( MM_SL_Account_Linker $linker ) {
		self::$linker = $linker;

		add_filter( 'manage_users_columns', array( __CLASS__, 'add_social_column' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'render_social_column' ), 10, 3 );
		add_action( 'show_user_profile', array( __CLASS__, 'render_user_profile_section' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'render_user_profile_section' ) );
	}

	/**
	 * Add column to WP Users table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function add_social_column( $columns ) {
		$columns['mm_social_accounts'] = '만물로 소셜 연결';
		return $columns;
	}

	/**
	 * Render connected providers badges for each user in WP Admin.
	 *
	 * @param string $output      Custom column output.
	 * @param string $column_name Column name.
	 * @param int    $user_id     User ID.
	 * @return string
	 */
	public static function render_social_column( $output, $column_name, $user_id ) {
		if ( 'mm_social_accounts' !== $column_name ) {
			return $output;
		}

		$linked = self::$linker->get_linked_accounts( $user_id );
		if ( empty( $linked ) ) {
			return '<span style="color:#8c8f94;">미연결 (Email)</span>';
		}

		$badges = array();
		foreach ( $linked as $provider => $row ) {
			$badges[] = sprintf(
				'<span style="display:inline-block;padding:2px 8px;border-radius:10px;background:#f0f0f1;font-size:11px;font-weight:600;margin-right:4px;">%s</span>',
				esc_html( strtoupper( $provider ) )
			);
		}

		return implode( '', $badges );
	}

	/**
	 * Show connected social accounts on WP Admin User Profile page.
	 *
	 * @param WP_User $user User object.
	 */
	public static function render_user_profile_section( $user ) {
		$linked = self::$linker->get_linked_accounts( $user->ID );
		?>
		<h2>만물로 소셜 계정 연결 현황</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th>연결된 Provider</th>
				<td>
					<?php if ( empty( $linked ) ) : ?>
						<p>연결된 소셜 계정이 없습니다.</p>
					<?php else : ?>
						<ul>
							<?php foreach ( $linked as $provider => $row ) : ?>
								<li>
									<strong><?php echo esc_html( strtoupper( $provider ) ); ?></strong>
									— 연결일: <?php echo esc_html( $row['created_at'] ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}
}
