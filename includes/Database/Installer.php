<?php
/**
 * Creates/upgrades Free's custom tables via dbDelta. Options (settings,
 * rules, exclusions) are NOT stored here — only scanner/index data, per
 * the plugin's "custom tables for index data only" design.
 *
 * Table names always go through $wpdb->prefix; a bare "wp_" is never
 * assumed, so the plugin works on any prefix and in multisite.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Installer {

	public static function links_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'elm_links';
	}

	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$links_table      = self::links_table();

		$sql = "CREATE TABLE {$links_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED NOT NULL,
			url TEXT NOT NULL,
			url_hash CHAR(32) NOT NULL,
			domain VARCHAR(255) NOT NULL DEFAULT '',
			rel_attribute VARCHAR(255) NOT NULL DEFAULT '',
			target_blank TINYINT(1) NOT NULL DEFAULT 0,
			is_excluded TINYINT(1) NOT NULL DEFAULT 0,
			last_seen DATETIME NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY domain (domain(191)),
			KEY url_hash (url_hash),
			KEY last_seen (last_seen)
		) {$charset_collate};";

		dbDelta( $sql );

		/**
		 * Fires after Free's core tables are created/updated. Pro hooks
		 * here to run its own dbDelta() calls for elm_pro_link_health, etc.
		 */
		do_action( 'elm_after_install_tables' );
	}
}
