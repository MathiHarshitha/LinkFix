<?php
/**
 * Daily cron housekeeping: if a scan or bulk-apply job was left in
 * "running" state (browser closed mid-scan, PHP worker recycled, etc.)
 * for longer than a sane window, mark it interrupted so the admin UI
 * can offer to resume it instead of showing a job that looks stuck
 * forever.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Maintenance {

	const STALE_AFTER = 2 * HOUR_IN_SECONDS;

	public static function register(): void {
		add_action( 'elm_cleanup_stale_scans', array( __CLASS__, 'cleanup' ) );
	}

	public static function cleanup(): void {
		self::mark_stale( Scanner::STATE_OPTION );
		self::mark_stale( BulkApplier::STATE_OPTION );
	}

	private static function mark_stale( string $option_key ): void {
		$state = get_option( $option_key );

		if ( ! is_array( $state ) || 'running' !== ( $state['status'] ?? '' ) ) {
			return;
		}

		$reference = $state['updated_at'] ?? $state['started_at'] ?? null;

		if ( ! $reference ) {
			return;
		}

		$age = time() - strtotime( $reference . ' UTC' );

		if ( $age > self::STALE_AFTER ) {
			$state['status'] = 'interrupted';
			update_option( $option_key, $state, false );
		}
	}
}
