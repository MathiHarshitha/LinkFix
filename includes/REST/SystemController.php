<?php
/**
 * REST controller backing the "Tools" admin page: system info, export/
 * import of settings+rules+exclusions, and clearing scan data.
 *
 * @package ExternalLinkManager
 */

namespace ELM\REST;

use ELM\Database\LinksRepository;
use ELM\Scanner\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SystemController {

	public function register_routes(): void {
		$perm = array( RestApi::class, 'permission_check' );

		register_rest_route( RestApi::NAMESPACE_V1, '/system/info', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_info' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/system/export', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'export' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/system/import', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'import' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/system/clear-index', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'clear_index' ),
			'permission_callback' => $perm,
		) );

		register_rest_route( RestApi::NAMESPACE_V1, '/system/uninstall-preference', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_uninstall_preference' ),
				'permission_callback' => $perm,
			),
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'set_uninstall_preference' ),
				'permission_callback' => $perm,
			),
		) );
	}

	public function get_uninstall_preference(): \WP_REST_Response {
		return new \WP_REST_Response(
			array( 'remove_data_on_uninstall' => (bool) get_option( 'elm_remove_data_on_uninstall', false ) )
		);
	}

	public function set_uninstall_preference( \WP_REST_Request $request ): \WP_REST_Response {
		$value = rest_sanitize_boolean( $request->get_param( 'remove_data_on_uninstall' ) );
		update_option( 'elm_remove_data_on_uninstall', $value );

		return new \WP_REST_Response( array( 'remove_data_on_uninstall' => $value ) );
	}

	public function get_info(): \WP_REST_Response {
		global $wp_version;

		$plugin = \ELM\Core\Plugin::instance();

		return new \WP_REST_Response(
			array(
				'elm_version'       => ELM_VERSION,
				'db_version'        => ELM_DB_VERSION,
				'wp_version'        => $wp_version,
				'php_version'       => PHP_VERSION,
				'html_api_available' => class_exists( '\WP_HTML_Tag_Processor' ),
				'extensions'        => array_keys( $plugin->get_extensions() ),
				'is_multisite'      => is_multisite(),
			)
		);
	}

	public function export(): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'settings'   => get_option( 'elm_settings', array() ),
				'rules'      => get_option( 'elm_rules', array() ),
				'exclusions' => get_option( 'elm_exclusions', array() ),
				'exported_at' => current_time( 'mysql', true ),
				'elm_version' => ELM_VERSION,
			)
		);
	}

	public function import( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params() ?: array();

		$settings_service = \ELM\Core\Plugin::instance()->settings();

		if ( isset( $params['settings'] ) && is_array( $params['settings'] ) ) {
			$settings_service->update( $params['settings'] );
		}

		if ( isset( $params['rules'] ) && is_array( $params['rules'] ) ) {
			\ELM\Core\Plugin::instance()->link_processor()->rule_engine()->save( $params['rules'] );
		}

		if ( isset( $params['exclusions'] ) && is_array( $params['exclusions'] ) ) {
			\ELM\Core\Plugin::instance()->link_processor()->exclusions()->save( $params['exclusions'] );
		}

		return new \WP_REST_Response( array( 'imported' => true ) );
	}

	public function clear_index(): \WP_REST_Response {
		( new LinksRepository() )->truncate();
		delete_option( Scanner::STATE_OPTION );

		return new \WP_REST_Response( array( 'cleared' => true ) );
	}
}
