<?php
/**
 * Uninstall handler.
 *
 * By default this plugin removes NOTHING when deleted — settings,
 * scan data, and any content it has modified are all left in place.
 * Only when the administrator has explicitly opted in via Tools →
 * "Remove plugin data on uninstall" do we clean up plugin-owned
 * options/tables/cron events. Post content is never touched: rel/target
 * attributes the plugin already wrote into post_content stay there,
 * because we have no reliable way to tell them apart from values a
 * human or another plugin may have added since.
 *
 * @package ExternalLinkManager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function elm_uninstall_single_site() {
	if ( ! get_option( 'elm_remove_data_on_uninstall', false ) ) {
		return;
	}

	global $wpdb;

	// Plugin-owned options.
	$options = array(
		'elm_settings',
		'elm_rules',
		'elm_exclusions',
		'elm_db_version',
		'elm_scan_state',
		'elm_bulk_apply_state',
		'elm_remove_data_on_uninstall',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Plugin-owned tables.
	$table = $wpdb->prefix . 'elm_links';
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	// Plugin-owned cron events.
	wp_clear_scheduled_hook( 'elm_cleanup_stale_scans' );

	/**
	 * Fires during uninstall cleanup, after Free removes its own data,
	 * so Pro can remove its own options/tables/cron events in turn.
	 */
	do_action( 'elm_uninstall_cleanup' );
}

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		elm_uninstall_single_site();
		restore_current_blog();
	}
} else {
	elm_uninstall_single_site();
}
