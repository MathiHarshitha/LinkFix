<?php
/**
 * Batch content scanner. Discovers external links in existing content
 * and indexes them into elm_links for statistics — it never modifies
 * post content (see BulkApplier for the opt-in "apply to existing
 * content" action). Designed to run over many short HTTP requests
 * (REST calls driven by the admin UI) so a single request never has to
 * walk the whole site, and to resume cleanly if interrupted.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Scanner;

use ELM\Settings;
use ELM\LinkProcessor\LinkProcessor;
use ELM\Database\LinksRepository;
use ELM\Support\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Scanner {

	const STATE_OPTION = 'elm_scan_state';

	/** @var Settings */
	private $settings;

	/** @var LinkProcessor */
	private $processor;

	/** @var LinksRepository */
	private $repository;

	public function __construct( Settings $settings, LinkProcessor $processor ) {
		$this->settings   = $settings;
		$this->processor  = $processor;
		$this->repository = new LinksRepository();
	}

	public function get_state(): array {
		$state = get_option( self::STATE_OPTION, array() );

		return wp_parse_args(
			is_array( $state ) ? $state : array(),
			array(
				'status'          => 'idle', // idle|running|paused|completed|cancelled
				'post_types'      => array(),
				'total_items'     => 0,
				'processed_items' => 0,
				'links_found'     => 0,
				'last_id'         => 0,
				'started_at'      => null,
				'updated_at'      => null,
				'finished_at'     => null,
				'errors'          => array(),
			)
		);
	}

	private function save_state( array $state ): array {
		$state['updated_at'] = current_time( 'mysql', true );
		update_option( self::STATE_OPTION, $state, false );

		return $state;
	}

	/**
	 * @param string[]|null $post_types Defaults to the configured content scope.
	 */
	public function start( ?array $post_types = null ): array {
		global $wpdb;

		$post_types = $post_types ?: (array) $this->settings->get( 'content_types', array( 'post', 'page' ) );
		$post_types = array_values( array_intersect( $post_types, array_keys( PostTypes::get_public_post_types() ) ) );

		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$post_types
			)
		);

		$state = array(
			'status'          => 'running',
			'post_types'      => $post_types,
			'total_items'     => $total,
			'processed_items' => 0,
			'links_found'     => 0,
			'last_id'         => 0,
			'started_at'      => current_time( 'mysql', true ),
			'finished_at'     => null,
			'errors'          => array(),
		);

		return $this->save_state( $state );
	}

	public function pause(): array {
		$state = $this->get_state();

		if ( 'running' === $state['status'] ) {
			$state['status'] = 'paused';
		}

		return $this->save_state( $state );
	}

	public function resume(): array {
		$state = $this->get_state();

		if ( in_array( $state['status'], array( 'paused', 'interrupted' ), true ) ) {
			$state['status'] = 'running';
		}

		return $this->save_state( $state );
	}

	public function cancel(): array {
		$state           = $this->get_state();
		$state['status']  = 'cancelled';
		$state['finished_at'] = current_time( 'mysql', true );

		return $this->save_state( $state );
	}

	/**
	 * Processes one batch and returns the updated state. Safe to call
	 * repeatedly (e.g. from the browser polling every ~1s) until status
	 * becomes 'completed'.
	 */
	public function run_batch(): array {
		$state = $this->get_state();

		if ( ! in_array( $state['status'], array( 'running' ), true ) ) {
			return $state;
		}

		$batch_size = (int) $this->settings->get( 'scanner_batch_size', 50 );

		// A direct query (rather than WP_Query) because resumption needs a
		// simple "ID > last_id" cursor, which WP_Query cannot express.
		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $state['post_types'] ), '%s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders}) AND ID > %d ORDER BY ID ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				array_merge( $state['post_types'], array( $state['last_id'], $batch_size ) )
			)
		);

		if ( empty( $ids ) ) {
			$state['status']      = 'completed';
			$state['finished_at'] = current_time( 'mysql', true );

			return $this->save_state( $state );
		}

		foreach ( $ids as $id ) {
			$id = (int) $id;

			try {
				$post = get_post( $id );

				if ( ! $post ) {
					continue;
				}

				$links = $this->processor->analyze_html( $post->post_content, array( 'post_id' => $id ) );
				$this->repository->replace_for_post( $id, $links );

				$state['links_found'] += count( $links );
			} catch ( \Throwable $e ) {
				$state['errors'][] = array(
					'post_id' => $id,
					'message' => $e->getMessage(),
				);
			}

			$state['last_id'] = $id;
			++$state['processed_items'];
		}

		/**
		 * Fires after each scanner batch completes.
		 *
		 * @param array $state
		 */
		do_action( 'elm_scanner_batch_completed', $state );

		return $this->save_state( $state );
	}

	public function get_last_scan_summary(): array {
		$state = $this->get_state();

		return array(
			'status'          => $state['status'],
			'started_at'      => $state['started_at'],
			'finished_at'     => $state['finished_at'],
			'processed_items' => $state['processed_items'],
			'total_items'     => $state['total_items'],
			'links_found'     => $state['links_found'],
			'last_indexed'    => $this->repository->get_last_seen(),
		);
	}
}
