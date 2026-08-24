<?php
/**
 * REST controller for the exclusion list (domains, subdomains, exact
 * URLs, URL patterns).
 *
 * @package ExternalLinkManager
 */

namespace ELM\REST;

use ELM\LinkProcessor\LinkProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ExclusionsController {

	/** @var LinkProcessor */
	private $processor;

	public function __construct( LinkProcessor $processor ) {
		$this->processor = $processor;
	}

	public function register_routes(): void {
		register_rest_route(
			RestApi::NAMESPACE_V1,
			'/exclusions',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_exclusions' ),
					'permission_callback' => array( RestApi::class, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save_exclusions' ),
					'permission_callback' => array( RestApi::class, 'permission_check' ),
				),
			)
		);
	}

	public function get_exclusions(): \WP_REST_Response {
		return new \WP_REST_Response( $this->processor->exclusions()->get_all() );
	}

	public function save_exclusions( \WP_REST_Request $request ): \WP_REST_Response {
		$params     = $request->get_json_params();
		$exclusions = isset( $params['exclusions'] ) && is_array( $params['exclusions'] ) ? $params['exclusions'] : array();

		return new \WP_REST_Response( $this->processor->exclusions()->save( $exclusions ) );
	}
}
