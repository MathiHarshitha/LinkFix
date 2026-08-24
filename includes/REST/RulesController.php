<?php
/**
 * REST controller for the basic Free rule engine.
 *
 * @package ExternalLinkManager
 */

namespace ELM\REST;

use ELM\LinkProcessor\LinkProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RulesController {

	/** @var LinkProcessor */
	private $processor;

	public function __construct( LinkProcessor $processor ) {
		$this->processor = $processor;
	}

	public function register_routes(): void {
		register_rest_route(
			RestApi::NAMESPACE_V1,
			'/rules',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_rules' ),
					'permission_callback' => array( RestApi::class, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save_rules' ),
					'permission_callback' => array( RestApi::class, 'permission_check' ),
				),
			)
		);
	}

	public function get_rules(): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'rules'   => $this->processor->rule_engine()->get_all(),
				'actions' => \ELM\Rules\RuleEngine::ACTIONS,
			)
		);
	}

	public function save_rules( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params();
		$rules  = isset( $params['rules'] ) && is_array( $params['rules'] ) ? $params['rules'] : array();

		return new \WP_REST_Response(
			array( 'rules' => $this->processor->rule_engine()->save( $rules ) )
		);
	}
}
