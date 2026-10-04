<?php
/**
 * Database Schema Manager for Manmulro Menu (#17, #20)
 *
 * Creates and manages the 10 tables defined in Section #20:
 * 1. MENU_PROJECT      (`wp_mm_menu_projects`)
 * 2. CATEGORY          (`wp_mm_menu_categories`)
 * 3. MENU_ITEM         (`wp_mm_menu_items`)
 * 4. MENU_INGREDIENT   (`wp_mm_menu_ingredients`)
 * 5. MENU_ALLERGEN     (`wp_mm_menu_allergens`)
 * 6. MENU_ORIGIN       (`wp_mm_menu_origins`)
 * 7. MENU_FLAVOR       (`wp_mm_menu_flavors`)
 * 8. SOURCE_IMAGE      (`wp_mm_menu_source_images`)
 * 9. OCR_RESULT        (`wp_mm_menu_ocr_results`)
 * 10. MENU_TRANSLATION (`wp_mm_menu_translations`)
 *
 * @package Manmulro_Menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_Menu_DB {

	/**
	 * Get full table name by logical key.
	 *
	 * @param string $key Table slug.
	 * @return string
	 */
	public static function table( $key ) {
		global $wpdb;
		return $wpdb->prefix . 'mm_menu_' . sanitize_key( $key );
	}

	/**
	 * Create or upgrade all 10 tables via dbDelta (#20).
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$t_projects     = self::table( 'projects' );
		$t_categories   = self::table( 'categories' );
		$t_items        = self::table( 'items' );
		$t_ingredients  = self::table( 'ingredients' );
		$t_allergens    = self::table( 'allergens' );
		$t_origins      = self::table( 'origins' );
		$t_flavors      = self::table( 'flavors' );
		$t_source_imgs  = self::table( 'source_images' );
		$t_ocr_results  = self::table( 'ocr_results' );
		$t_translations = self::table( 'translations' );

		$sql = "
		CREATE TABLE {$t_projects} (
			project_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			business_name varchar(191) NOT NULL,
			business_type varchar(64) NOT NULL DEFAULT '음식점',
			store_slug varchar(120) NOT NULL,
			status varchar(24) NOT NULL DEFAULT 'DRAFT',
			public_url varchar(255) NOT NULL DEFAULT '',
			design_template varchar(64) NOT NULL DEFAULT 'korean',
			design_config longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (project_id),
			UNIQUE KEY store_slug (store_slug),
			KEY user_id (user_id)
		) {$charset_collate};

		CREATE TABLE {$t_categories} (
			category_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			project_id bigint(20) unsigned NOT NULL,
			name varchar(120) NOT NULL,
			sort_order int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (category_id),
			KEY project_id (project_id)
		) {$charset_collate};

		CREATE TABLE {$t_items} (
			menu_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			project_id bigint(20) unsigned NOT NULL,
			category_id bigint(20) unsigned NOT NULL DEFAULT 0,
			item_slug varchar(120) NOT NULL,
			name varchar(191) NOT NULL,
			price int(11) NOT NULL DEFAULT 0,
			short_description text,
			detailed_description longtext,
			image_id bigint(20) unsigned NOT NULL DEFAULT 0,
			image_url text,
			status varchar(24) NOT NULL DEFAULT 'ACTIVE',
			tags text,
			recommended_for text,
			extra_meta longtext,
			source_ocr_id bigint(20) unsigned NOT NULL DEFAULT 0,
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (menu_id),
			KEY project_id (project_id),
			KEY category_id (category_id),
			KEY item_slug (item_slug)
		) {$charset_collate};

		CREATE TABLE {$t_ingredients} (
			ingredient_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			menu_id bigint(20) unsigned NOT NULL,
			name varchar(120) NOT NULL,
			description text,
			PRIMARY KEY  (ingredient_id),
			KEY menu_id (menu_id)
		) {$charset_collate};

		CREATE TABLE {$t_allergens} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			menu_id bigint(20) unsigned NOT NULL,
			allergen_type varchar(64) NOT NULL,
			PRIMARY KEY  (id),
			KEY menu_id (menu_id)
		) {$charset_collate};

		CREATE TABLE {$t_origins} (
			origin_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			menu_id bigint(20) unsigned NOT NULL,
			ingredient_name varchar(120) NOT NULL,
			origin varchar(120) NOT NULL,
			PRIMARY KEY  (origin_id),
			KEY menu_id (menu_id)
		) {$charset_collate};

		CREATE TABLE {$t_flavors} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			menu_id bigint(20) unsigned NOT NULL,
			flavor_type varchar(64) NOT NULL,
			intensity tinyint(3) unsigned NOT NULL DEFAULT 3,
			PRIMARY KEY  (id),
			KEY menu_id (menu_id)
		) {$charset_collate};

		CREATE TABLE {$t_source_imgs} (
			source_image_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			project_id bigint(20) unsigned NOT NULL,
			image_url text NOT NULL,
			page_number int(11) unsigned NOT NULL DEFAULT 1,
			preprocess_meta text,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (source_image_id),
			KEY project_id (project_id)
		) {$charset_collate};

		CREATE TABLE {$t_ocr_results} (
			ocr_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_image_id bigint(20) unsigned NOT NULL,
			project_id bigint(20) unsigned NOT NULL,
			text text NOT NULL,
			parsed_name varchar(191) NOT NULL DEFAULT '',
			parsed_price int(11) NOT NULL DEFAULT 0,
			parsed_category varchar(120) NOT NULL DEFAULT '식사',
			confidence float NOT NULL DEFAULT 1,
			bounding_box text NOT NULL,
			needs_review tinyint(1) unsigned NOT NULL DEFAULT 0,
			linked_menu_id bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (ocr_id),
			KEY source_image_id (source_image_id),
			KEY project_id (project_id)
		) {$charset_collate};

		CREATE TABLE {$t_translations} (
			translation_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			menu_id bigint(20) unsigned NOT NULL,
			language varchar(16) NOT NULL,
			translated_name varchar(191) NOT NULL DEFAULT '',
			translated_short_description text,
			translated_detailed_description longtext,
			PRIMARY KEY  (translation_id),
			KEY menu_id_lang (menu_id, language)
		) {$charset_collate};
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
