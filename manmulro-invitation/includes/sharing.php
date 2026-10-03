<?php
/**
 * Sharing, Location (Naver Map / Directions), D-Day & Calendar Export (#18, #20, #21, #30, #31)
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Sharing {

	/**
	 * Initialize `.ics` calendar download endpoint (#31).
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_download_ics' ), 2 );
	}

	/**
	 * Calculate D-Day badge text per Section #30:
	 * - Before event: D-15
	 * - Event day:    D-DAY
	 * - After event:  행사가 종료되었습니다.
	 *
	 * @param string $event_date YYYY-MM-DD date string.
	 * @return array{status:string, label:string, days:int}
	 */
	public static function get_dday_info( $event_date ) {
		if ( empty( $event_date ) ) {
			return array(
				'status' => 'none',
				'label'  => '',
				'days'   => 0,
			);
		}

		$today_ts = strtotime( current_time( 'Y-m-d' ) . ' 00:00:00' );
		$event_ts = strtotime( substr( $event_date, 0, 10 ) . ' 00:00:00' );

		if ( ! $event_ts ) {
			return array(
				'status' => 'none',
				'label'  => '',
				'days'   => 0,
			);
		}

		$diff_days = (int) round( ( $event_ts - $today_ts ) / DAY_IN_SECONDS );

		if ( $diff_days > 0 ) {
			return array(
				'status' => 'upcoming',
				'label'  => 'D-' . $diff_days,
				'days'   => $diff_days,
			);
		}

		if ( 0 === $diff_days ) {
			return array(
				'status' => 'today',
				'label'  => 'D-DAY',
				'days'   => 0,
			);
		}

		return array(
			'status' => 'ended',
			'label'  => '행사가 종료되었습니다.',
			'days'   => $diff_days,
		);
	}

	/**
	 * Build Naver Map & Directions links for Section #20, #21.
	 *
	 * @param string $place_name Place name (e.g. OO 컨벤션센터).
	 * @param string $address    Address (e.g. 부산광역시 OO구 OO로 123).
	 * @return array{map_url:string, directions_url:string, query:string}
	 */
	public static function get_naver_map_links( $place_name, $address ) {
		$query   = trim( $address ? $address : $place_name );
		$encoded = rawurlencode( $query );

		return array(
			'query'          => $query,
			'map_url'        => 'https://map.naver.com/p/search/' . $encoded,
			'directions_url' => 'https://map.naver.com/index.nhn?slng=&slat=&stext=&elng=&elat=&etext=' . rawurlencode( $place_name ? $place_name : $address ) . '&menu=route&pathType=1',
		);
	}

	/**
	 * Get `.ics` download URL and Google Calendar URL for `[내 일정에 저장]` (#31).
	 *
	 * @param array $inv Invitation data array.
	 * @return array{ics_url:string, google_url:string}
	 */
	public static function get_calendar_links( array $inv ) {
		$code    = ! empty( $inv['code'] ) ? $inv['code'] : '';
		$ics_url = add_query_arg(
			array(
				'mm_inv_ics' => $code,
			),
			home_url( '/' )
		);

		$date_raw   = ! empty( $inv['event_date'] ) ? preg_replace( '/[^0-9]/', '', $inv['event_date'] ) : gmdate( 'Ymd' );
		$start_time = ! empty( $inv['event_time'] ) ? preg_replace( '/[^0-9]/', '', $inv['event_time'] ) : '1200';
		if ( strlen( $start_time ) === 4 ) {
			$start_time .= '00';
		} else {
			$start_time = '120000';
		}

		$end_raw = ! empty( $inv['event_end_time'] ) ? preg_replace( '/[^0-9]/', '', $inv['event_end_time'] ) : '';
		if ( strlen( $end_raw ) === 4 ) {
			$end_time = $end_raw . '00';
		} else {
			$end_time = gmdate( 'His', strtotime( $date_raw . ' ' . $start_time ) + 7200 );
		}

		$dates_param = "{$date_raw}T{$start_time}/{$date_raw}T{$end_time}";
		$location    = trim( ( isset( $inv['location_name'] ) ? $inv['location_name'] : '' ) . ' ' . ( isset( $inv['address'] ) ? $inv['address'] : '' ) );

		$google_url = add_query_arg(
			array(
				'action'   => 'TEMPLATE',
				'text'     => isset( $inv['title'] ) ? $inv['title'] : '만물로 초대장 일정',
				'dates'    => $dates_param,
				'location' => $location,
				'details'  => isset( $inv['summary'] ) ? $inv['summary'] : '',
			),
			'https://calendar.google.com/calendar/render'
		);

		return array(
			'ics_url'    => $ics_url,
			'google_url' => $google_url,
		);
	}

	/**
	 * Output `.ics` iCalendar file when `?mm_inv_ics={code}` is requested (#31).
	 */
	public static function maybe_download_ics() {
		if ( empty( $_GET['mm_inv_ics'] ) ) {
			return;
		}

		$code = sanitize_text_field( wp_unslash( $_GET['mm_inv_ics'] ) );
		$inv  = MM_Inv_Invitation::get_invitation_by_code( $code );
		if ( ! $inv ) {
			wp_die( '초대장을 찾을 수 없습니다.' );
		}

		$date_raw   = ! empty( $inv['event_date'] ) ? preg_replace( '/[^0-9]/', '', $inv['event_date'] ) : gmdate( 'Ymd' );
		$start_time = ! empty( $inv['event_time'] ) ? preg_replace( '/[^0-9]/', '', $inv['event_time'] ) : '1200';
		$start_time = ( strlen( $start_time ) === 4 ) ? $start_time . '00' : '120000';

		$end_raw  = ! empty( $inv['event_end_time'] ) ? preg_replace( '/[^0-9]/', '', $inv['event_end_time'] ) : '';
		$end_time = ( strlen( $end_raw ) === 4 ) ? $end_raw . '00' : gmdate( 'His', strtotime( $date_raw . ' ' . $start_time ) + 7200 );

		$title    = self::escape_ics_text( $inv['title'] );
		$location = self::escape_ics_text( trim( $inv['location_name'] . ' ' . $inv['address'] ) );
		$desc     = self::escape_ics_text( $inv['summary'] . '\n' . $inv['short_url'] );
		$uid      = 'mm-inv-' . $inv['code'] . '@manmulro.local';
		$now_utc  = gmdate( 'Ymd\THis\Z' );

		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="manmulro-' . $inv['code'] . '.ics"' );

		echo "BEGIN:VCALENDAR\r\n";
		echo "VERSION:2.0\r\n";
		echo "PRODID:-//MANMULRO//Manmulro Invitation V1//KO\r\n";
		echo "CALSCALE:GREGORIAN\r\n";
		echo "METHOD:PUBLISH\r\n";
		echo "BEGIN:VEVENT\r\n";
		echo "UID:{$uid}\r\n";
		echo "DTSTAMP:{$now_utc}\r\n";
		echo "DTSTART;TZID=Asia/Seoul:{$date_raw}T{$start_time}\r\n";
		echo "DTEND;TZID=Asia/Seoul:{$date_raw}T{$end_time}\r\n";
		echo "SUMMARY:{$title}\r\n";
		echo "LOCATION:{$location}\r\n";
		echo "DESCRIPTION:{$desc}\r\n";
		echo "URL:" . esc_url_raw( $inv['short_url'] ) . "\r\n";
		echo "END:VEVENT\r\n";
		echo "END:VCALENDAR\r\n";
		exit;
	}

	/**
	 * Escape text for iCalendar specification.
	 *
	 * @param string $text Input text.
	 * @return string
	 */
	private static function escape_ics_text( $text ) {
		$text = str_replace( array( "\r\n", "\n", "\r" ), '\n', (string) $text );
		return addcslashes( $text, ',;\\' );
	}
}
