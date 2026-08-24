<?php
/**
 * REST controller for reading/updating elm_settings.
 *
 * @package ExternalLinkManager
 */

namespace ELM\REST;

use ELM\Settings;
use ELM\Support\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsController {

	/** @var Settings */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register_routes(): void {
		register_rest_route(
			RestApi::NAMESPACE_V1,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( RestApi::class, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( RestApi::class, 'permission_check' ),
					'args'                => $this->get_settings_args(),
				),
			)
		);

		register_rest_route(
			RestApi::NAMESPACE_V1,
			'/settings/post-types',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_types' ),
				'permission_callback' => array( RestApi::class, 'permission_check' ),
			)
		);
	}

	public function get_settings(): \WP_REST_Response {
		return new \WP_REST_Response( $this->settings->get_all() );
	}

	public function update_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$params  = $request->get_json_params() ?: array();
		$updated = $this->settings->update( $params );

		return new \WP_REST_Response( $updated );
	}

	public function get_post_types(): \WP_REST_Response {
		return new \WP_REST_Response( PostTypes::get_public_post_types() );
	}

	private function get_settings_args(): array {
		$bool_arg = array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
		);

		return array(
			'new_tab'             => $bool_arg,
			'noopener'            => $bool_arg,
			'noreferrer'          => $bool_arg,
			'nofollow'            => $bool_arg,
			'sponsored'           => $bool_arg,
			'ugc'                 => $bool_arg,
			'statistics_enabled'  => $bool_arg,
			'auto_apply_existing' => $bool_arg,
			'content_types'       => array(
				'type'  => 'array',
				'items' => array( 'type' => 'string' ),
			),
			'scanner_batch_size'  => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		);
	}
}
