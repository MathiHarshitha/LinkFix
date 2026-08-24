<?php
/**
 * Data access for the elm_links index table. All queries are prepared;
 * this is the only class that should touch $wpdb for link data.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinksRepository {

	private function table(): string {
		return Installer::links_table();
	}

	/**
	 * Replaces all indexed links for a single post with a fresh set,
	 * scoped by post_id so re-scanning a post never leaves stale rows.
	 *
	 * @param int   $post_id
	 * @param array $links [ ['url' => ..., 'domain' => ..., 'rel' => ..., 'target_blank' => bool, 'is_excluded' => bool], ... ]
	 */
	public function replace_for_post( int $post_id, array $links ): void {
		global $wpdb;

		$table = $this->table();
		$now   = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'post_id' => $post_id ), array( '%d' ) );

		foreach ( $links as $link ) {
			$wpdb->insert(
				$table,
				array(
					'post_id'       => $post_id,
					'url'           => $link['url'],
					'url_hash'      => md5( $link['url'] ),
					'domain'        => $link['domain'],
					'rel_attribute' => $link['rel'] ?? '',
					'target_blank'  => ! empty( $link['target_blank'] ) ? 1 : 0,
					'is_excluded'   => ! empty( $link['is_excluded'] ) ? 1 : 0,
					'last_seen'     => $now,
					'created_at'    => $now,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
			);
		}
	}

	public function delete_for_post( int $post_id ): void {
		global $wpdb;

		$wpdb->delete( $this->table(), array( 'post_id' => $post_id ), array( '%d' ) );
	}

	public function delete_for_posts( array $post_ids ): void {
		global $wpdb;

		if ( empty( $post_ids ) ) {
			return;
		}

		$post_ids     = array_map( 'absint', $post_ids );
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$table        = $this->table();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE post_id IN ({$placeholders})", $post_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	}

	public function count_total_links( bool $exclude_excluded = true ): int {
		global $wpdb;

		$table = $this->table();
		$where = $exclude_excluded ? 'WHERE is_excluded = 0' : '';

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public function count_unique_domains(): int {
		global $wpdb;

		$table = $this->table();

		return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT domain) FROM {$table} WHERE is_excluded = 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public function count_content_with_links(): int {
		global $wpdb;

		$table = $this->table();

		return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$table} WHERE is_excluded = 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * @return array<int, array{domain: string, count: int}>
	 */
	public function get_top_domains( int $limit = 20 ): array {
		global $wpdb;

		$table = $this->table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT domain, COUNT(*) as link_count FROM {$table} WHERE is_excluded = 0 AND domain != '' GROUP BY domain ORDER BY link_count DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$limit
			),
			ARRAY_A
		);

		return array_map(
			static function ( $row ) {
				return array(
					'domain' => $row['domain'],
					'count'  => (int) $row['link_count'],
				);
			},
			$rows ?: array()
		);
	}

	/**
	 * @return array{noopener:int, noreferrer:int, nofollow:int, sponsored:int, ugc:int, new_tab:int}
	 */
	public function get_rel_distribution(): array {
		global $wpdb;

		$table = $this->table();

		$rows = $wpdb->get_results( "SELECT rel_attribute, target_blank FROM {$table} WHERE is_excluded = 0", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$counts = array(
			'noopener'   => 0,
			'noreferrer' => 0,
			'nofollow'   => 0,
			'sponsored'  => 0,
			'ugc'        => 0,
			'new_tab'    => 0,
		);

		foreach ( $rows ?: array() as $row ) {
			$tokens = preg_split( '/\s+/', trim( (string) $row['rel_attribute'] ), -1, PREG_SPLIT_NO_EMPTY );

			foreach ( $tokens as $token ) {
				if ( isset( $counts[ $token ] ) ) {
					++$counts[ $token ];
				}
			}

			if ( ! empty( $row['target_blank'] ) ) {
				++$counts['new_tab'];
			}
		}

		return $counts;
	}

	public function get_last_seen(): ?string {
		global $wpdb;

		$table = $this->table();

		$value = $wpdb->get_var( "SELECT MAX(last_seen) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $value ?: null;
	}

	public function truncate(): void {
		global $wpdb;

		$table = $this->table();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
