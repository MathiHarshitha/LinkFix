<?php
/**
 * REST controller driving the batch Scanner and the "apply to existing
 * content" Bulk Applier. The admin UI polls/advances these via repeated
 * short requests — each call processes exactly one batch.
 *
 * @package ExternalLinkManager
 */

namespace ELM\REST;

use ELM\Scanner\Scanner;
use ELM\Scanner\BulkApplier;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ScannerController {

	/** @var Scanner */
	private $scanner;

	/** @var BulkApplier */
	private $bulk_applier;

	public function __construct( Scanner $scanner, BulkApplier $bulk_applier ) {
		$this->scanner      = $scanner;
		$this->bulk_applier = $bulk_applier;
	}

	public function register_routes(): void {
		$perm = array( RestApi::class, 'permission_check' );

		register_rest_route( RestApi::NAMESPACE_V1, '/scanner/state', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_state' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/scanner/start', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'start' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/scanner/batch', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'run_batch' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/scanner/pause', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'pause' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/scanner/resume', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'resume' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/scanner/cancel', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'cancel' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/bulk-apply/state', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_bulk_state' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/bulk-apply/start', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'start_bulk_apply' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/bulk-apply/batch', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'run_bulk_apply_batch' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/bulk-apply/cancel', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'cancel_bulk_apply' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/bulk-apply/resume', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'resume_bulk_apply' ),
			'permission_callback' => $perm,
		) );
	}

	public function get_state(): \WP_REST_Response {
		return new \WP_REST_Response( $this->scanner->get_state() );
	}

	public function start( \WP_REST_Request $request ): \WP_REST_Response {
		$post_types = $request->get_param( 'post_types' );

		return new \WP_REST_Response( $this->scanner->start( is_array( $post_types ) ? $post_types : null ) );
	}

	public function run_batch(): \WP_REST_Response {
		return new \WP_REST_Response( $this->scanner->run_batch() );
	}

	public function pause(): \WP_REST_Response {
		return new \WP_REST_Response( $this->scanner->pause() );
	}

	public function resume(): \WP_REST_Response {
		return new \WP_REST_Response( $this->scanner->resume() );
	}

	public function cancel(): \WP_REST_Response {
		return new \WP_REST_Response( $this->scanner->cancel() );
	}

	public function get_bulk_state(): \WP_REST_Response {
		return new \WP_REST_Response( $this->bulk_applier->get_state() );
	}

	public function start_bulk_apply( \WP_REST_Request $request ): \WP_REST_Response {
		$post_types = $request->get_param( 'post_types' );

		return new \WP_REST_Response( $this->bulk_applier->start( is_array( $post_types ) ? $post_types : null ) );
	}

	public function run_bulk_apply_batch(): \WP_REST_Response {
		return new \WP_REST_Response( $this->bulk_applier->run_batch() );
	}

	public function cancel_bulk_apply(): \WP_REST_Response {
		return new \WP_REST_Response( $this->bulk_applier->cancel() );
	}

	public function resume_bulk_apply(): \WP_REST_Response {
		return new \WP_REST_Response( $this->bulk_applier->resume() );
	}
}
