<?php
/**
 * REST controller exposing aggregated, locally-computed statistics.
 *
 * @package ExternalLinkManager
 */

namespace ELM\REST;

use ELM\Scanner\Scanner;
use ELM\Statistics\StatisticsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StatisticsController {

	/** @var StatisticsService */
	private $service;

	public function __construct( Scanner $scanner ) {
		$this->service = new StatisticsService( $scanner );
	}

	public function register_routes(): void {
		register_rest_route(
			RestApi::NAMESPACE_V1,
			'/statistics',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_statistics' ),
				'permission_callback' => array( RestApi::class, 'permission_check' ),
			)
		);
	}

	public function get_statistics(): \WP_REST_Response {
		return new \WP_REST_Response( $this->service->get_summary() );
	}
}
