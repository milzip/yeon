<?php
/**
 * Core Invitation Engine (#2, #7, #11, #16, #17, #32, #33, #34, #35, #36, #37, #39, #40)
 *
 * Manages:
 * - Template registry (Simple, Classic, Modern, Flower, Nature) + FREE/PREMIUM tiers (#39, #40)
 * - Short URL routing `/i/{code}` (#17)
 * - NOINDEX search engine protection (#33)
 * - Visibility scope (`link_only`, `password`, `private`) (#32)
 * - Expiration period (`always`, `after_30`, `after_90`, `custom`) without deleting data (#34)
 * - Duplication (`{Title} - 복사본`) excluding RSVP & Guestbook (#37)
 * - Frontend Editor (`[manmulro_invitation_editor]`) & My Invitations (`[manmulro_my_invitations]`)
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Invitation {

	/**
	 * Initialize routes, shortcodes, and AJAX actions.
	 */
	public static function init() {
		self::register_rewrite_rules();

		add_shortcode( 'manmulro_invitation_editor', array( __CLASS__, 'render_editor_shortcode' ) );
		add_shortcode( 'manmulro_my_invitations', array( __CLASS__, 'render_my_invitations_shortcode' ) );

		add_action( 'template_redirect', array( __CLASS__, 'handle_short_url_request' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'maybe_output_noindex_and_og' ), 1 );

		// Authenticated user AJAX actions.
		add_action( 'wp_ajax_mm_inv_save', array( __CLASS__, 'ajax_save_invitation' ) );
		add_action( 'wp_ajax_mm_inv_duplicate', array( __CLASS__, 'ajax_duplicate_invitation' ) );
		add_action( 'wp_ajax_mm_inv_change_status', array( __CLASS__, 'ajax_change_status' ) );
		add_action( 'wp_ajax_mm_inv_delete', array( __CLASS__, 'ajax_delete_invitation' ) );
		add_action( 'wp_ajax_mm_inv_upload_image', array( __CLASS__, 'ajax_upload_image' ) );
	}

	/**
	 * Register `/i/{code}` rewrite rule (#17).
	 */
	public static function register_rewrite_rules() {
		add_rewrite_rule(
			'^i/([A-Za-z0-9_-]{4,16})/?$',
			'index.php?mm_invitation_code=$matches[1]',
			'top'
		);

		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = 'mm_invitation_code';
				return $vars;
			}
		);
	}

	/**
	 * Get available templates (#39, #40).
	 * Data and Template Design are completely separated (#11).
	 * Includes `tier` ('FREE' or 'PREMIUM') for future monetization readiness (#40).
	 *
	 * @return array
	 */
	public static function get_templates() {
		$defaults = array(
			'simple'  => array(
				'slug'        => 'simple',
				'name'        => 'Simple (심플)',
				'description' => '깔끔하고 정돈된 타이포그래피 중심의 미니멀 디자인',
				'tier'        => 'FREE',
				'accent'      => '#111827',
				'bg'          => '#ffffff',
			),
			'classic' => array(
				'slug'        => 'classic',
				'name'        => 'Classic (클래식)',
				'description' => '격식 있는 모임·결혼·기념일에 어울리는 우아한 세리프 스타일',
				'tier'        => 'FREE',
				'accent'      => '#7c5a3a',
				'bg'          => '#fdfaf6',
			),
			'modern'  => array(
				'slug'        => 'modern',
				'name'        => 'Modern (모던)',
				'description' => '기업 행사·전시·동창회에 어울리는 세련된 다크 포인트 스타일',
				'tier'        => 'FREE',
				'accent'      => '#2563eb',
				'bg'          => '#f8fafc',
			),
			'flower'  => array(
				'slug'        => 'flower',
				'name'        => 'Flower (플라워)',
				'description' => '따뜻한 파스텔 플로럴 감성의 화사한 초대장 디자인',
				'tier'        => 'PREMIUM',
				'accent'      => '#db2777',
				'bg'          => '#fff7f9',
			),
			'nature'  => array(
				'slug'        => 'nature',
				'name'        => 'Nature (네이처)',
				'description' => '등산·골프·사이클·러닝·야외 모임에 어울리는 싱그러운 그린 스타일',
				'tier'        => 'FREE',
				'accent'      => '#15803d',
				'bg'          => '#f4fbf7',
			),
		);

		$custom_templates = get_option( 'mm_inv_custom_templates', array() );
		if ( is_array( $custom_templates ) && ! empty( $custom_templates ) ) {
			$defaults = array_merge( $defaults, $custom_templates );
		}

		return apply_filters( 'manmulro_invitation_templates', $defaults );
	}

	/**
	 * Load full normalized invitation data array from post ID.
	 *
	 * @param int $post_id Invitation post ID.
	 * @return array|null
	 */
	public static function get_invitation( $post_id ) {
		$post = get_post( (int) $post_id );
		if ( ! $post || 'mm_invitation' !== $post->post_type ) {
			return null;
		}

		$code = get_post_meta( $post->ID, '_mm_invitation_code', true );
		if ( empty( $code ) ) {
			$code = MM_Inv_Security::generate_unique_code();
			update_post_meta( $post->ID, '_mm_invitation_code', $code );
		}

		$terms    = wp_get_post_terms( $post->ID, 'invitation_category' );
		$cat_slug = ! is_wp_error( $terms ) && ! empty( $terms ) ? $terms[0]->slug : 'other';
		$cat_name = ! is_wp_error( $terms ) && ! empty( $terms ) ? $terms[0]->name : '기타';

		$template = get_post_meta( $post->ID, '_mm_template', true );
		if ( empty( $template ) ) {
			$template = 'simple';
		}

		$status = get_post_meta( $post->ID, '_mm_status', true );
		if ( empty( $status ) ) {
			if ( 'publish' === $post->post_status ) {
				$status = 'PUBLISHED';
			} elseif ( 'mm_archived' === $post->post_status ) {
				$status = 'ARCHIVED';
			} elseif ( 'private' === $post->post_status ) {
				$status = 'PRIVATE';
			} else {
				$status = 'DRAFT';
			}
		}

		$fields_raw = get_post_meta( $post->ID, '_mm_fields', true );
		$fields     = $fields_raw ? MM_Inv_Fields::sanitize_fields( $fields_raw ) : MM_Inv_Fields::get_default_fields();

		$event_date     = get_post_meta( $post->ID, '_mm_event_date', true );
		$event_time     = get_post_meta( $post->ID, '_mm_event_time', true );
		$event_end_time = get_post_meta( $post->ID, '_mm_event_end_time', true );
		$location_name  = get_post_meta( $post->ID, '_mm_location_name', true );
		$address        = get_post_meta( $post->ID, '_mm_address', true );
		$summary        = get_post_meta( $post->ID, '_mm_summary', true );

		// Sync primary date/time/location/address from flexible fields if not explicitly set.
		foreach ( $fields as $f ) {
			if ( empty( $event_date ) && 'date' === $f['type'] && ! empty( $f['value'] ) ) {
				$event_date = $f['value'];
			}
			if ( empty( $event_time ) && 'time' === $f['type'] && ! empty( $f['value'] ) ) {
				$event_time = $f['value'];
			}
			if ( empty( $location_name ) && 'location' === $f['type'] && ! empty( $f['value'] ) ) {
				$location_name = $f['value'];
			}
			if ( empty( $address ) && 'address' === $f['type'] && ! empty( $f['value'] ) ) {
				$address = $f['value'];
			}
		}

		$short_url = home_url( '/i/' . $code );

		return array(
			'id'                => (int) $post->ID,
			'author_id'         => (int) $post->post_author,
			'code'              => $code,
			'short_url'         => $short_url,
			'title'             => $post->post_title ? $post->post_title : '제목 없는 초대장',
			'summary'           => $summary ? $summary : '',
			'category_slug'     => $cat_slug,
			'category_name'     => $cat_name,
			'template'          => $template,
			'status'            => $status,
			'event_date'        => $event_date,
			'event_time'        => $event_time,
			'event_end_time'    => $event_end_time,
			'location_name'     => $location_name,
			'address'           => $address,
			'cover_image_url'   => MM_Inv_Gallery::get_cover_image_url( $post->ID ),
			'gallery_items'     => MM_Inv_Gallery::get_gallery_items( $post->ID ),
			'fields'            => $fields,
			'rsvp_enabled'      => '0' !== (string) get_post_meta( $post->ID, '_mm_rsvp_enabled', true ),
			'rsvp_deadline'     => (string) get_post_meta( $post->ID, '_mm_rsvp_deadline', true ),
			'guestbook_enabled' => '0' !== (string) get_post_meta( $post->ID, '_mm_guestbook_enabled', true ),
			'dday_enabled'      => '0' !== (string) get_post_meta( $post->ID, '_mm_dday_enabled', true ),
			'visibility'        => get_post_meta( $post->ID, '_mm_visibility', true ) ? get_post_meta( $post->ID, '_mm_visibility', true ) : 'link_only',
			'expiration_mode'   => get_post_meta( $post->ID, '_mm_expiration_mode', true ) ? get_post_meta( $post->ID, '_mm_expiration_mode', true ) : 'always',
			'expiration_date'   => (string) get_post_meta( $post->ID, '_mm_expiration_date', true ),
			'noindex'           => '0' !== (string) get_post_meta( $post->ID, '_mm_noindex', true ),
			'created_at'        => $post->post_date,
			'updated_at'        => $post->post_modified,
		);
	}

	/**
	 * Find invitation by its short code (`/i/{code}`) (#17).
	 *
	 * @param string $code Short code.
	 * @return array|null
	 */
	public static function get_invitation_by_code( $code ) {
		$code = sanitize_text_field( $code );
		if ( empty( $code ) ) {
			return null;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'mm_invitation',
				'post_status'    => array( 'publish', 'draft', 'private', 'mm_archived' ),
				'meta_key'       => '_mm_invitation_code',
				'meta_value'     => $code,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( empty( $posts ) ) {
			return null;
		}

		return self::get_invitation( $posts[0] );
	}

	/**
	 * Check if an invitation's public viewing period has expired (#34).
	 * Note: Expired invitations are NEVER deleted; only public viewing is closed.
	 *
	 * @param array $inv Invitation data array.
	 * @return bool True if expired.
	 */
	public static function is_publication_expired( array $inv ) {
		$mode       = isset( $inv['expiration_mode'] ) ? $inv['expiration_mode'] : 'always';
		$event_date = isset( $inv['event_date'] ) ? $inv['event_date'] : '';
		$today_ts   = strtotime( current_time( 'Y-m-d' ) . ' 00:00:00' );

		if ( 'always' === $mode ) {
			return false;
		}

		if ( 'after_30' === $mode && ! empty( $event_date ) ) {
			$exp_ts = strtotime( $event_date . ' +30 days' );
			return ( $exp_ts && $today_ts > $exp_ts );
		}

		if ( 'after_90' === $mode && ! empty( $event_date ) ) {
			$exp_ts = strtotime( $event_date . ' +90 days' );
			return ( $exp_ts && $today_ts > $exp_ts );
		}

		if ( 'custom' === $mode && ! empty( $inv['expiration_date'] ) ) {
			$exp_ts = strtotime( $inv['expiration_date'] . ' 23:59:59' );
			return ( $exp_ts && time() > $exp_ts );
		}

		return false;
	}

	/**
	 * Output `<meta name="robots" content="noindex, nofollow">` and OpenGraph tags (#12, #33).
	 */
	public static function maybe_output_noindex_and_og() {
		$code = get_query_var( 'mm_invitation_code' );
		if ( empty( $code ) && isset( $_GET['mm_invitation_code'] ) ) {
			$code = sanitize_text_field( wp_unslash( $_GET['mm_invitation_code'] ) );
		}

		if ( empty( $code ) ) {
			return;
		}

		$inv = self::get_invitation_by_code( $code );
		if ( ! $inv ) {
			return;
		}

		if ( ! empty( $inv['noindex'] ) ) {
			echo '<meta name="robots" content="noindex, nofollow, noarchive" />' . "\n";
		}

		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $inv['title'] ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $inv['summary'] ? $inv['summary'] : '만물로 초대장이 도착했습니다.' ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $inv['short_url'] ) . '" />' . "\n";
		if ( ! empty( $inv['cover_image_url'] ) ) {
			echo '<meta property="og:image" content="' . esc_url( $inv['cover_image_url'] ) . '" />' . "\n";
		}
	}

	/**
	 * Handle `/i/{code}` public invitation & print requests (#17, #32, #33, #34, #38).
	 */
	public static function handle_short_url_request() {
		$code = get_query_var( 'mm_invitation_code' );
		if ( empty( $code ) && isset( $_GET['mm_invitation_code'] ) ) {
			$code = sanitize_text_field( wp_unslash( $_GET['mm_invitation_code'] ) );
		}

		if ( empty( $code ) ) {
			return;
		}

		$inv = self::get_invitation_by_code( $code );
		if ( ! $inv ) {
			status_header( 404 );
			wp_die( '요청하신 초대장을 찾을 수 없습니다.', '초대장 없음', array( 'response' => 404 ) );
		}

		// Default NOINDEX header (#33).
		if ( ! empty( $inv['noindex'] ) && ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}

		$is_owner = MM_Inv_Security::can_manage_invitation( $inv['id'] );

		// Check status & visibility (#16, #32, #34).
		if ( ! $is_owner ) {
			if ( in_array( $inv['status'], array( 'DRAFT', 'ARCHIVED', 'PRIVATE' ), true ) || 'private' === $inv['visibility'] ) {
				wp_die( '비공개 상태이거나 보관된 초대장입니다.', '비공개 초대장', array( 'response' => 403 ) );
			}

			if ( self::is_publication_expired( $inv ) ) {
				wp_die( '공개 기간이 종료된 초대장입니다.', '공개 기간 종료', array( 'response' => 410 ) );
			}

			// Password protection check (#32).
			if ( 'password' === $inv['visibility'] ) {
				$unlocked = self::check_password_access( $inv['id'] );
				if ( ! $unlocked ) {
					self::render_password_prompt( $inv );
					exit;
				}
			}
		}

		$is_print = ! empty( $_GET['print'] );
		if ( $is_print ) {
			include MM_INV_PLUGIN_DIR . 'templates/print.php';
			exit;
		}

		include MM_INV_PLUGIN_DIR . 'templates/invitation-single.php';
		exit;
	}

	/**
	 * Verify visitor password for password-protected invitations (#32).
	 *
	 * @param int $invitation_id Invitation ID.
	 * @return bool
	 */
	private static function check_password_access( $invitation_id ) {
		$cookie_key = 'mm_inv_pwd_' . (int) $invitation_id;
		$hash       = get_post_meta( (int) $invitation_id, '_mm_password_hash', true );

		if ( empty( $hash ) ) {
			return true;
		}

		if ( isset( $_POST['mm_inv_password_submit'], $_POST['mm_inv_password'] ) ) {
			$input = sanitize_text_field( wp_unslash( $_POST['mm_inv_password'] ) );
			if ( wp_check_password( $input, $hash ) ) {
				$token = hash_hmac( 'sha256', $invitation_id . '|' . $hash, wp_salt( 'auth' ) );
				if ( ! headers_sent() ) {
					setcookie( $cookie_key, $token, time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
				}
				return true;
			}
		}

		if ( ! empty( $_COOKIE[ $cookie_key ] ) ) {
			$expected = hash_hmac( 'sha256', $invitation_id . '|' . $hash, wp_salt( 'auth' ) );
			if ( hash_equals( $expected, sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render password prompt screen for password-protected invitation (#32).
	 *
	 * @param array $inv Invitation data.
	 */
	private static function render_password_prompt( array $inv ) {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />
			<meta name="robots" content="noindex, nofollow" />
			<title><?php echo esc_html( $inv['title'] ); ?> - 비밀번호 확인</title>
			<link rel="stylesheet" href="<?php echo esc_url( MM_INV_PLUGIN_URL . 'assets/css/frontend.css' ); ?>" />
		</head>
		<body class="mm-inv-password-body">
			<div class="mm-inv-password-card">
				<span class="mm-inv-badge">🔒 비밀번호 보호 초대장</span>
				<h1><?php echo esc_html( $inv['title'] ); ?></h1>
				<p>이 초대장은 비밀번호를 아는 분만 열람할 수 있습니다.</p>
				<form method="post" action="">
					<input type="password" name="mm_inv_password" required placeholder="비밀번호 입력" class="mm-inv-input" />
					<button type="submit" name="mm_inv_password_submit" value="1" class="mm-inv-primary-btn" style="width:100%;margin-top:12px;">초대장 열기</button>
				</form>
			</div>
		</body>
		</html>
		<?php
	}

	/**
	 * Render Invitation Editor Shortcode (`[manmulro_invitation_editor]`) (#7, #15).
	 * Requires login (#4).
	 *
	 * @return string
	 */
	public static function render_editor_shortcode() {
		if ( ! is_user_logged_in() ) {
			$login_url = MM_Inv_Security::get_login_url( home_url( '/invitation-editor/' ) );
			return '<div class="mm-inv-login-required"><h3>초대장을 만들려면 로그인이 필요합니다</h3><p>만물로 통합 로그인(카카오·네이버·구글·이메일) 후 무료로 초대장을 제작하세요.</p><a class="mm-inv-primary-btn" href="' . esc_url( $login_url ) . '">로그인하러 가기</a></div>';
		}

		wp_enqueue_media();
		wp_enqueue_style( 'mm-inv-frontend' );
		wp_enqueue_style( 'mm-inv-editor' );
		foreach ( array( 'simple', 'classic', 'modern', 'flower', 'nature' ) as $theme_slug ) {
			wp_enqueue_style( 'mm-inv-theme-' . $theme_slug );
		}
		wp_enqueue_script( 'mm-inv-qr' );
		wp_enqueue_script( 'mm-inv-frontend' );
		wp_enqueue_script( 'mm-inv-editor' );

		$invitation_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$inv           = null;

		if ( $invitation_id > 0 ) {
			if ( ! MM_Inv_Security::can_manage_invitation( $invitation_id ) ) {
				return '<div class="mm-inv-error">해당 초대장을 수정할 권한이 없습니다.</div>';
			}
			$inv = self::get_invitation( $invitation_id );
		}

		ob_start();
		include MM_INV_PLUGIN_DIR . 'templates/invitation-editor.php';
		return ob_get_clean();
	}

	/**
	 * Render My Invitations Dashboard Shortcode (`[manmulro_my_invitations]`) (#35, #36).
	 *
	 * @return string
	 */
	public static function render_my_invitations_shortcode() {
		if ( ! is_user_logged_in() ) {
			$login_url = MM_Inv_Security::get_login_url( home_url( '/my-invitations/' ) );
			return '<div class="mm-inv-login-required"><h3>내 초대장을 관리하려면 로그인이 필요합니다</h3><a class="mm-inv-primary-btn" href="' . esc_url( $login_url ) . '">로그인하러 가기</a></div>';
		}

		wp_enqueue_style( 'mm-inv-frontend' );
		wp_enqueue_style( 'mm-inv-editor' );
		wp_enqueue_script( 'mm-inv-qr' );
		wp_enqueue_script( 'mm-inv-frontend' );
		wp_enqueue_script( 'mm-inv-editor' );

		ob_start();
		include MM_INV_PLUGIN_DIR . 'templates/my-invitations.php';
		return ob_get_clean();
	}

	/**
	 * AJAX: Save or Publish Invitation (#7, #11, #16, #43, #44).
	 */
	public static function ajax_save_invitation() {
		check_ajax_referer( 'mm_inv_editor_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => '로그인이 필요합니다.' ) );
		}

		$invitation_id = isset( $_POST['invitation_id'] ) ? (int) $_POST['invitation_id'] : 0;
		if ( $invitation_id > 0 && ! MM_Inv_Security::can_manage_invitation( $invitation_id ) ) {
			wp_send_json_error( array( 'message' => '수정 권한이 없습니다.' ) );
		}

		$title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '새 초대장';
		$summary       = isset( $_POST['summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['summary'] ) ) : '';
		$category_slug = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'other';
		$template      = isset( $_POST['template'] ) ? sanitize_key( wp_unslash( $_POST['template'] ) ) : 'simple';
		$status        = isset( $_POST['status'] ) ? strtoupper( sanitize_key( wp_unslash( $_POST['status'] ) ) ) : 'DRAFT';

		$allowed_statuses = array( 'DRAFT', 'PUBLISHED', 'ARCHIVED', 'PRIVATE' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'DRAFT';
		}

		$wp_post_status = 'draft';
		if ( 'PUBLISHED' === $status ) {
			$wp_post_status = 'publish';
		} elseif ( 'ARCHIVED' === $status ) {
			$wp_post_status = 'mm_archived';
		} elseif ( 'PRIVATE' === $status ) {
			$wp_post_status = 'private';
		}

		$post_args = array(
			'post_title'  => $title ? $title : '새 초대장',
			'post_status' => $wp_post_status,
			'post_type'   => 'mm_invitation',
		);

		if ( $invitation_id > 0 ) {
			$post_args['ID'] = $invitation_id;
			$saved_id        = wp_update_post( $post_args, true );
		} else {
			$post_args['post_author'] = get_current_user_id();
			$saved_id                 = wp_insert_post( $post_args, true );
		}

		if ( is_wp_error( $saved_id ) ) {
			wp_send_json_error( array( 'message' => $saved_id->get_error_message() ) );
		}

		$invitation_id = (int) $saved_id;

		// Ensure unique short code exists (#17).
		$code = get_post_meta( $invitation_id, '_mm_invitation_code', true );
		if ( empty( $code ) ) {
			$code = MM_Inv_Security::generate_unique_code();
			update_post_meta( $invitation_id, '_mm_invitation_code', $code );
		}

		// Save Category (#6).
		if ( ! empty( $category_slug ) ) {
			wp_set_object_terms( $invitation_id, $category_slug, 'invitation_category', false );
		}

		// Save Template & Status (#11, #16, #39).
		update_post_meta( $invitation_id, '_mm_template', $template );
		update_post_meta( $invitation_id, '_mm_status', $status );
		update_post_meta( $invitation_id, '_mm_summary', $summary );

		// Save Event Data (#5, #20).
		$event_date     = isset( $_POST['event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) : '';
		$event_time     = isset( $_POST['event_time'] ) ? sanitize_text_field( wp_unslash( $_POST['event_time'] ) ) : '';
		$event_end_time = isset( $_POST['event_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['event_end_time'] ) ) : '';
		$location_name  = isset( $_POST['location_name'] ) ? sanitize_text_field( wp_unslash( $_POST['location_name'] ) ) : '';
		$address        = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';

		update_post_meta( $invitation_id, '_mm_event_date', $event_date );
		update_post_meta( $invitation_id, '_mm_event_time', $event_time );
		update_post_meta( $invitation_id, '_mm_event_end_time', $event_end_time );
		update_post_meta( $invitation_id, '_mm_location_name', $location_name );
		update_post_meta( $invitation_id, '_mm_address', $address );

		// Save Cover & Gallery (#12, #13).
		if ( isset( $_POST['cover_image_url'] ) ) {
			update_post_meta( $invitation_id, '_mm_cover_image_url', esc_url_raw( wp_unslash( $_POST['cover_image_url'] ) ) );
		}
		if ( isset( $_POST['cover_attachment_id'] ) && (int) $_POST['cover_attachment_id'] > 0 ) {
			set_post_thumbnail( $invitation_id, (int) $_POST['cover_attachment_id'] );
		}
		if ( isset( $_POST['gallery_items'] ) ) {
			MM_Inv_Gallery::save_gallery_items( $invitation_id, $_POST['gallery_items'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		// Save Flexible Fields (#8, #9, #10, #11).
		if ( isset( $_POST['fields'] ) ) {
			$sanitized_fields = MM_Inv_Fields::sanitize_fields( $_POST['fields'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			update_post_meta( $invitation_id, '_mm_fields', $sanitized_fields );
		}

		// Save Feature Toggles & Policies (#23, #24, #28, #30, #32, #33, #34).
		update_post_meta( $invitation_id, '_mm_rsvp_enabled', ! empty( $_POST['rsvp_enabled'] ) ? '1' : '0' );
		update_post_meta( $invitation_id, '_mm_rsvp_deadline', isset( $_POST['rsvp_deadline'] ) ? sanitize_text_field( wp_unslash( $_POST['rsvp_deadline'] ) ) : '' );
		update_post_meta( $invitation_id, '_mm_guestbook_enabled', ! empty( $_POST['guestbook_enabled'] ) ? '1' : '0' );
		update_post_meta( $invitation_id, '_mm_dday_enabled', ! empty( $_POST['dday_enabled'] ) ? '1' : '0' );

		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : 'link_only';
		if ( ! in_array( $visibility, array( 'link_only', 'password', 'private' ), true ) ) {
			$visibility = 'link_only';
		}
		update_post_meta( $invitation_id, '_mm_visibility', $visibility );

		if ( 'password' === $visibility && ! empty( $_POST['invitation_password'] ) ) {
			$raw_pwd = sanitize_text_field( wp_unslash( $_POST['invitation_password'] ) );
			update_post_meta( $invitation_id, '_mm_password_hash', wp_hash_password( $raw_pwd ) );
		}

		$exp_mode = isset( $_POST['expiration_mode'] ) ? sanitize_key( wp_unslash( $_POST['expiration_mode'] ) ) : 'always';
		update_post_meta( $invitation_id, '_mm_expiration_mode', $exp_mode );
		update_post_meta( $invitation_id, '_mm_expiration_date', isset( $_POST['expiration_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiration_date'] ) ) : '' );
		update_post_meta( $invitation_id, '_mm_noindex', ! empty( $_POST['noindex'] ) ? '1' : '0' );

		$updated_inv = self::get_invitation( $invitation_id );

		wp_send_json_success(
			array(
				'message'    => ( 'PUBLISHED' === $status ) ? '초대장이 공개되었습니다!' : '초대장이 저장되었습니다.',
				'invitation' => $updated_inv,
			)
		);
	}

	/**
	 * Duplicate an existing invitation (#37):
	 * - Copies Title as "{Title} - 복사본"
	 * - Copies Template Design, Flexible Fields, Photos, Basic Settings
	 * - Does NOT copy RSVP or Guestbook entries!
	 *
	 * @param int $source_id Source invitation post ID.
	 * @param int $user_id   Owner user ID.
	 * @return int|WP_Error New invitation ID or WP_Error.
	 */
	public static function duplicate_invitation( $source_id, $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! MM_Inv_Security::can_manage_invitation( $source_id, $user_id ) ) {
			return new WP_Error( 'forbidden', '복제 권한이 없습니다.' );
		}

		$src = self::get_invitation( $source_id );
		if ( ! $src ) {
			return new WP_Error( 'not_found', '원본 초대장을 찾을 수 없습니다.' );
		}

		$new_title = $src['title'] . ' - 복사본';
		$new_id    = wp_insert_post(
			array(
				'post_title'  => $new_title,
				'post_status' => 'draft',
				'post_type'   => 'mm_invitation',
				'post_author' => $user_id,
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			return $new_id;
		}

		// Generate a brand-new unique short code (#17, #37).
		$new_code = MM_Inv_Security::generate_unique_code();
		update_post_meta( $new_id, '_mm_invitation_code', $new_code );
		update_post_meta( $new_id, '_mm_status', 'DRAFT' );

		// Copy category.
		wp_set_object_terms( $new_id, $src['category_slug'], 'invitation_category', false );

		// Copy design, fields, photos, and settings (excluding RSVP and Guestbook DB rows #37).
		$meta_keys_to_copy = array(
			'_mm_template',
			'_mm_summary',
			'_mm_event_date',
			'_mm_event_time',
			'_mm_event_end_time',
			'_mm_location_name',
			'_mm_address',
			'_mm_cover_image_url',
			'_mm_gallery_items',
			'_mm_fields',
			'_mm_rsvp_enabled',
			'_mm_rsvp_deadline',
			'_mm_guestbook_enabled',
			'_mm_dday_enabled',
			'_mm_visibility',
			'_mm_expiration_mode',
			'_mm_expiration_date',
			'_mm_noindex',
		);

		foreach ( $meta_keys_to_copy as $meta_key ) {
			$val = get_post_meta( $source_id, $meta_key, true );
			if ( '' !== $val ) {
				update_post_meta( $new_id, $meta_key, $val );
			}
		}

		$thumb_id = get_post_thumbnail_id( $source_id );
		if ( $thumb_id ) {
			set_post_thumbnail( $new_id, $thumb_id );
		}

		return (int) $new_id;
	}

	/**
	 * AJAX handler for invitation duplication (#37).
	 */
	public static function ajax_duplicate_invitation() {
		check_ajax_referer( 'mm_inv_editor_nonce', 'nonce' );

		$source_id = isset( $_POST['invitation_id'] ) ? (int) $_POST['invitation_id'] : 0;
		$new_id    = self::duplicate_invitation( $source_id );

		if ( is_wp_error( $new_id ) ) {
			wp_send_json_error( array( 'message' => $new_id->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'    => '초대장이 복제되었습니다.',
				'new_id'     => $new_id,
				'editor_url' => add_query_arg( 'id', $new_id, home_url( '/invitation-editor/' ) ),
			)
		);
	}

	/**
	 * AJAX handler to archive or change invitation status (#16, #36).
	 */
	public static function ajax_change_status() {
		check_ajax_referer( 'mm_inv_editor_nonce', 'nonce' );

		$invitation_id = isset( $_POST['invitation_id'] ) ? (int) $_POST['invitation_id'] : 0;
		$new_status    = isset( $_POST['status'] ) ? strtoupper( sanitize_key( wp_unslash( $_POST['status'] ) ) ) : 'ARCHIVED';

		if ( ! MM_Inv_Security::can_manage_invitation( $invitation_id ) ) {
			wp_send_json_error( array( 'message' => '권한이 없습니다.' ) );
		}

		$wp_status = 'draft';
		if ( 'PUBLISHED' === $new_status ) {
			$wp_status = 'publish';
		} elseif ( 'ARCHIVED' === $new_status ) {
			$wp_status = 'mm_archived';
		} elseif ( 'PRIVATE' === $new_status ) {
			$wp_status = 'private';
		}

		wp_update_post(
			array(
				'ID'          => $invitation_id,
				'post_status' => $wp_status,
			)
		);
		update_post_meta( $invitation_id, '_mm_status', $new_status );

		wp_send_json_success( array( 'message' => '초대장 상태가 변경되었습니다.' ) );
	}

	/**
	 * AJAX handler to delete an invitation (#36, #43).
	 */
	public static function ajax_delete_invitation() {
		check_ajax_referer( 'mm_inv_editor_nonce', 'nonce' );

		$invitation_id = isset( $_POST['invitation_id'] ) ? (int) $_POST['invitation_id'] : 0;
		if ( ! MM_Inv_Security::can_manage_invitation( $invitation_id ) ) {
			wp_send_json_error( array( 'message' => '삭제 권한이 없습니다.' ) );
		}

		wp_trash_post( $invitation_id );
		wp_send_json_success( array( 'message' => '초대장이 삭제되었습니다.' ) );
	}

	/**
	 * AJAX handler for secure image upload (#12, #13, #14, #44).
	 */
	public static function ajax_upload_image() {
		check_ajax_referer( 'mm_inv_editor_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => '로그인이 필요합니다.' ) );
		}

		if ( empty( $_FILES['image'] ) ) {
			wp_send_json_error( array( 'message' => '업로드할 파일이 없습니다.' ) );
		}

		$valid = MM_Inv_Security::validate_image_upload( $_FILES['image'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_wp_error( $valid ) ) {
			wp_send_json_error( array( 'message' => $valid->get_error_message() ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'image', 0 );
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		$thumb_url = wp_get_attachment_image_url( $attachment_id, 'mm_inv_gallery_thumb' );
		$cover_url = wp_get_attachment_image_url( $attachment_id, 'mm_inv_cover' );
		$large_url = wp_get_attachment_image_url( $attachment_id, 'mm_inv_gallery_large' );

		wp_send_json_success(
			array(
				'id'        => (int) $attachment_id,
				'thumb_url' => $thumb_url ? $thumb_url : $cover_url,
				'cover_url' => $cover_url ? $cover_url : $large_url,
				'full_url'  => $large_url ? $large_url : $cover_url,
			)
		);
	}
}
