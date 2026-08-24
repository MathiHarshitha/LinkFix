<?php
/**
 * Runs on plugin activation.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Core;

use ELM\Database\Installer;
use ELM\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	public static function activate(): void {
		Installer::install();
		update_option( 'elm_db_version', ELM_DB_VERSION );

		// Seed defaults only if settings do not already exist (upgrade-safe).
		if ( false === get_option( 'elm_settings', false ) ) {
			add_option( 'elm_settings', Settings::defaults() );
		}

		if ( false === get_option( 'elm_rules', false ) ) {
			add_option( 'elm_rules', array() );
		}

		if ( false === get_option( 'elm_exclusions', false ) ) {
			add_option( 'elm_exclusions', array() );
		}

		if ( ! wp_next_scheduled( 'elm_cleanup_stale_scans' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'elm_cleanup_stale_scans' );
		}

		flush_rewrite_rules();
	}
}
