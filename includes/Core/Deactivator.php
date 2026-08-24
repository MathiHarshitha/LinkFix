<?php
/**
 * Runs on plugin deactivation. Deliberately non-destructive: settings,
 * tables and scan data all survive deactivation/reactivation.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivator {

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'elm_cleanup_stale_scans' );
		flush_rewrite_rules();
	}
}
