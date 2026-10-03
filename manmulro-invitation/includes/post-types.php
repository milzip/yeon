<?php
/**
 * Custom Post Type `mm_invitation` & Custom Post Statuses (#5, #16)
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Post_Types {

	/**
	 * Register `mm_invitation` Custom Post Type and `mm_archived` status.
	 */
	public static function register() {
		$labels = array(
			'name'               => '초대장',
			'singular_name'      => '초대장',
			'menu_name'          => '초대장',
			'name_admin_bar'     => '초대장',
			'add_new'            => '새 초대장 만들기',
			'add_new_item'       => '새 초대장 만들기',
			'new_item'           => '새 초대장',
			'edit_item'          => '초대장 수정',
			'view_item'          => '초대장 보기',
			'all_items'          => '전체 초대장',
			'search_items'       => '초대장 검색',
			'not_found'          => '생성된 초대장이 없습니다.',
			'not_found_in_trash' => '휴지통에 초대장이 없습니다.',
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false, // Served via /i/{code} endpoint (#17) to avoid exposing post IDs or search indexing (#33).
			'show_ui'             => true,
			'show_in_menu'        => false, // Managed under top-level '초대장' menu (#41).
			'query_var'           => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'author', 'thumbnail' ),
			'show_in_rest'        => false,
		);

		register_post_type( 'mm_invitation', $args );

		// Register custom ARCHIVED status (#16).
		register_post_status(
			'mm_archived',
			array(
				'label'                     => '보관됨 (ARCHIVED)',
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: count */
				'label_count'               => _n_noop( '보관됨 <span class="count">(%s)</span>', '보관됨 <span class="count">(%s)</span>' ),
			)
		);
	}
}
