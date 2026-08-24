<?php
/**
 * "Apply current settings to existing content" bulk action. Unlike the
 * Scanner (read-only, indexes for statistics), this persists rewritten
 * <a> attributes back into post_content — batched, resumable, and
 * capped per request to avoid long-running requests.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Scanner;

use ELM\Settings;
use ELM\LinkProcessor\LinkProcessor;
use ELM\Support\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BulkApplier {

	const STATE_OPTION = 'elm_bulk_apply_state';

	/** @var Settings */
	private $settings;

	/** @var LinkProcessor */
	private $processor;

	public function __construct( Settings $settings, LinkProcessor $processor ) {
		$this->settings  = $settings;
		$this->processor = $processor;
	}

	public function get_state(): array {
		$state = get_option( self::STATE_OPTION, array() );

		return wp_parse_args(
			is_array( $state ) ? $state : array(),
			array(
				'status'          => 'idle',
				'post_types'      => array(),
				'total_items'     => 0,
				'processed_items' => 0,
				'items_changed'   => 0,
				'links_affected'  => 0,
				'last_id'         => 0,
				'started_at'      => null,
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

		return $this->save_state(
			array(
				'status'          => 'running',
				'post_types'      => $post_types,
				'total_items'     => $total,
				'processed_items' => 0,
				'items_changed'   => 0,
				'links_affected'  => 0,
				'last_id'         => 0,
				'started_at'      => current_time( 'mysql', true ),
				'finished_at'     => null,
				'errors'          => array(),
			)
		);
	}

	public function cancel(): array {
		$state                = $this->get_state();
		$state['status']      = 'cancelled';
		$state['finished_at'] = current_time( 'mysql', true );

		return $this->save_state( $state );
	}

	public function resume(): array {
		$state = $this->get_state();

		if ( in_array( $state['status'], array( 'paused', 'interrupted' ), true ) ) {
			$state['status'] = 'running';
		}

		return $this->save_state( $state );
	}

	public function run_batch(): array {
		global $wpdb;

		$state = $this->get_state();

		if ( 'running' !== $state['status'] ) {
			return $state;
		}

		$batch_size = min( 20, (int) $this->settings->get( 'scanner_batch_size', 50 ) ); // Smaller: this batch writes to the DB.

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

				$result = $this->processor->process_html( $post->post_content, array( 'post_id' => $id ) );

				if ( ! empty( $result['changes'] ) && $result['html'] !== $post->post_content ) {
					wp_update_post(
						array(
							'ID'           => $id,
							'post_content' => $result['html'],
						)
					);

					++$state['items_changed'];
					$state['links_affected'] += count( $result['changes'] );
				}
			} catch ( \Throwable $e ) {
				$state['errors'][] = array(
					'post_id' => $id,
					'message' => $e->getMessage(),
				);
			}

			$state['last_id'] = $id;
			++$state['processed_items'];
		}

		return $this->save_state( $state );
	}
}
