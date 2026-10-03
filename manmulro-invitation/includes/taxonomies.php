<?php
/**
 * Taxonomy `invitation_category` & Initial 18 Categories + Category Default Fields (#6, #50)
 *
 * @package Manmulro_Invitation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Inv_Taxonomies {

	/**
	 * Register `invitation_category` taxonomy.
	 */
	public static function register() {
		$labels = array(
			'name'              => '초대장 카테고리',
			'singular_name'     => '초대장 카테고리',
			'search_items'      => '카테고리 검색',
			'all_items'         => '전체 카테고리',
			'edit_item'         => '카테고리 수정',
			'update_item'       => '카테고리 업데이트',
			'add_new_item'      => '새 초대장 카테고리 추가',
			'new_item_name'     => '새 카테고리 이름',
			'menu_name'         => '카테고리',
		);

		register_taxonomy(
			'invitation_category',
			array( 'mm_invitation' ),
			array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => false,
				'public'            => false,
				'show_in_rest'      => false,
			)
		);

		add_action( 'invitation_category_add_form_fields', array( __CLASS__, 'render_add_preset_field' ) );
		add_action( 'invitation_category_edit_form_fields', array( __CLASS__, 'render_edit_preset_field' ), 10, 2 );
		add_action( 'created_invitation_category', array( __CLASS__, 'save_preset_field' ) );
		add_action( 'edited_invitation_category', array( __CLASS__, 'save_preset_field' ) );
	}

	/**
	 * Return the 18 initial categories defined in Section #6 + preset fields per Section #50.
	 *
	 * @return array
	 */
	public static function get_default_categories() {
		return array(
			'wedding'      => array(
				'name'    => '결혼',
				'icon'    => '💍',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '신랑 · 신부', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '혼주 안내', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '식사 및 주차 안내', 'value' => '' ),
				),
			),
			'birthday'     => array(
				'name'    => '생일',
				'icon'    => '🎂',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '주인공', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '드레스코드', 'value' => '' ),
				),
			),
			'baby'         => array(
				'name'    => '돌·백일',
				'icon'    => '👶',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '아가 이름', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '아빠 · 엄마', 'value' => '' ),
				),
			),
			'longevity'    => array(
				'name'    => '회갑·칠순·팔순',
				'icon'    => '🌺',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '주인공 어르신', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '가족 대표 연락처', 'value' => '' ),
				),
			),
			'reunion'      => array(
				'name'    => '동창회',
				'icon'    => '🎓',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '기수 / 졸업연도', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '회비', 'value' => '' ),
				),
			),
			'gathering'    => array(
				'name'    => '친목모임',
				'icon'    => '🥂',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '회비', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '모임 안내', 'value' => '' ),
				),
			),
			'hiking'       => array(
				'name'    => '등산',
				'icon'    => '⛰️',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '산행코스', 'value' => '범어사 → 북문 → 고당봉' ),
					array( 'type' => 'custom', 'label' => '집결장소 및 시간', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '준비물', 'value' => '등산화, 스틱, 식수, 행동식' ),
					array( 'type' => 'custom', 'label' => '회비', 'value' => '' ),
				),
			),
			'golf'         => array(
				'name'    => '골프',
				'icon'    => '⛳',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '티오프 시간 / 코스', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '그린피', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '카트비', 'value' => '25,000원' ),
					array( 'type' => 'custom', 'label' => '캐디피', 'value' => '' ),
				),
			),
			'cycling'      => array(
				'name'    => '사이클',
				'icon'    => '🚴',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '라이딩 코스 / 거리', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '집결지', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '필수 장비', 'value' => '헬멧, 전조등, 후미등, 보급식' ),
				),
			),
			'running'      => array(
				'name'    => '러닝',
				'icon'    => '🏃',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '러닝 코스 / 거리', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '페이스 그룹', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '짐 보관 안내', 'value' => '' ),
				),
			),
			'corporate'    => array(
				'name'    => '회사행사',
				'icon'    => '🏢',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '주최 부서 / 담당자', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '주요 식순', 'value' => '' ),
				),
			),
			'school'       => array(
				'name'    => '학교행사',
				'icon'    => '🏫',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '대상 학년 / 학과', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '집결 장소', 'value' => '' ),
				),
			),
			'opening'      => array(
				'name'    => '개업',
				'icon'    => '🎊',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '상호명 / 대표', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '오픈 이벤트 안내', 'value' => '' ),
				),
			),
			'housewarming' => array(
				'name'    => '집들이',
				'icon'    => '🏠',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '출입문 / 주차 안내', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '준비된 음식', 'value' => '' ),
				),
			),
			'exhibition'   => array(
				'name'    => '전시·공연',
				'icon'    => '🎨',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '참여 작가 / 출연진', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '관람 시간 / 입장료', 'value' => '' ),
				),
			),
			'religious'    => array(
				'name'    => '종교행사',
				'icon'    => '🕊️',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '집례 / 인도자', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '예배 · 법회 안내', 'value' => '' ),
				),
			),
			'travel'       => array(
				'name'    => '여행',
				'icon'    => '✈️',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '여행 일정표', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '숙소 안내', 'value' => '' ),
					array( 'type' => 'custom', 'label' => '준비물 / 회비', 'value' => '' ),
				),
			),
			'other'        => array(
				'name'    => '기타',
				'icon'    => '✨',
				'presets' => array(
					array( 'type' => 'custom', 'label' => '안내 사항', 'value' => '' ),
				),
			),
		);
	}

	/**
	 * Seed the 18 initial categories into `invitation_category` (#6).
	 */
	public static function seed_initial_categories() {
		$defaults = self::get_default_categories();

		foreach ( $defaults as $slug => $data ) {
			$term = term_exists( $data['name'], 'invitation_category' );
			if ( ! $term ) {
				$inserted = wp_insert_term(
					$data['name'],
					'invitation_category',
					array(
						'slug' => $slug,
					)
				);
				if ( ! is_wp_error( $inserted ) && ! empty( $inserted['term_id'] ) ) {
					update_term_meta( $inserted['term_id'], '_mm_category_icon', $data['icon'] );
					update_term_meta( $inserted['term_id'], '_mm_category_preset_fields', wp_json_encode( $data['presets'], JSON_UNESCAPED_UNICODE ) );
				}
			}
		}
	}

	/**
	 * Render custom preset fields textarea when adding a new category (#50).
	 * Example in spec #50: 낚시모임 -> 출조일, 집결시간, 집결장소, 선박, 출조비, 준비물
	 */
	public static function render_add_preset_field() {
		?>
		<div class="form-field">
			<label for="mm_category_icon">카테고리 아이콘 (이모지)</label>
			<input type="text" name="mm_category_icon" id="mm_category_icon" value="✨" />
		</div>
		<div class="form-field">
			<label for="mm_category_preset_lines">기본 추천 항목 (한 줄에 하나씩 입력 - 코드 수정 없이 새 초대장 종류 구성)</label>
			<textarea name="mm_category_preset_lines" id="mm_category_preset_lines" rows="5" placeholder="출조일&#10;집결시간&#10;집결장소&#10;선박&#10;출조비&#10;준비물"></textarea>
			<p class="description">이 카테고리를 선택했을 때 초대장 편집기에 기본으로 제안할 항목 이름을 한 줄에 하나씩 입력하세요.</p>
		</div>
		<?php
	}

	/**
	 * Render custom preset fields textarea when editing a category (#50).
	 *
	 * @param WP_Term $term Term object.
	 */
	public static function render_edit_preset_field( $term ) {
		$icon        = get_term_meta( $term->term_id, '_mm_category_icon', true );
		$presets_raw = get_term_meta( $term->term_id, '_mm_category_preset_fields', true );
		$presets     = $presets_raw ? json_decode( $presets_raw, true ) : array();
		$lines       = array();
		if ( is_array( $presets ) ) {
			foreach ( $presets as $item ) {
				if ( ! empty( $item['label'] ) ) {
					$lines[] = $item['label'];
				}
			}
		}
		?>
		<tr class="form-field">
			<th scope="row"><label for="mm_category_icon">카테고리 아이콘</label></th>
			<td>
				<input type="text" name="mm_category_icon" id="mm_category_icon" value="<?php echo esc_attr( $icon ? $icon : '✨' ); ?>" />
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="mm_category_preset_lines">기본 추천 항목 (#50)</label></th>
			<td>
				<textarea name="mm_category_preset_lines" id="mm_category_preset_lines" rows="6"><?php echo esc_textarea( implode( "\n", $lines ) ); ?></textarea>
				<p class="description">예: 낚시모임의 경우 출조일, 집결시간, 집결장소, 선박, 출조비, 준비물 등을 줄바꿈으로 입력하면 코드 수정 없이 해당 초대장 기본 항목으로 제공됩니다.</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save category preset fields meta.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function save_preset_field( $term_id ) {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		if ( isset( $_POST['mm_category_icon'] ) ) {
			update_term_meta( $term_id, '_mm_category_icon', sanitize_text_field( wp_unslash( $_POST['mm_category_icon'] ) ) );
		}

		if ( isset( $_POST['mm_category_preset_lines'] ) ) {
			$raw_lines = explode( "\n", sanitize_textarea_field( wp_unslash( $_POST['mm_category_preset_lines'] ) ) );
			$presets   = array();
			foreach ( $raw_lines as $line ) {
				$line = trim( $line );
				if ( '' !== $line ) {
					$presets[] = array(
						'type'  => 'custom',
						'label' => $line,
						'value' => '',
					);
				}
			}
			update_term_meta( $term_id, '_mm_category_preset_fields', wp_json_encode( $presets, JSON_UNESCAPED_UNICODE ) );
		}
	}
}
