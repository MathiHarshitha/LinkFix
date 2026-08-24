<?php
/**
 * Enqueues the admin React bundle — ONLY on this plugin's own admin
 * pages, and NEVER on the frontend. Free ships zero frontend JS/CSS.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue( string $hook ): void {
		if ( ! $this->is_plugin_screen() ) {
			return;
		}

		$asset_file = ELM_PLUGIN_DIR . 'build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			add_action( 'admin_notices', array( $this, 'missing_build_notice' ) );
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'elm-admin',
			ELM_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( 'elm-admin', 'external-link-manager', ELM_PLUGIN_DIR . 'languages' );

		wp_enqueue_style(
			'elm-admin',
			ELM_PLUGIN_URL . 'build/style-index.css',
			array( 'wp-components' ),
			$asset['version']
		);

		$plugin = \ELM\Core\Plugin::instance();

		wp_localize_script(
			'elm-admin',
			'elmAdminData',
			array(
				'restUrl'       => esc_url_raw( rest_url( \ELM\REST\RestApi::NAMESPACE_V1 ) ),
				'restNonce'     => wp_create_nonce( 'wp_rest' ),
				'adminUrl'      => esc_url_raw( admin_url( 'admin.php' ) ),
				'menuSlug'      => Menu::SLUG,
				'initialRoute'  => Menu::current_route_id(),
				'routes'        => Menu::routes(),
				'version'       => ELM_VERSION,
				'isPro'         => $plugin->has_extension( 'elm-pro' ),
				'upgradeUrl'    => 'https://example.com/external-link-manager-pro/',
			)
		);
	}

	private function is_plugin_screen(): bool {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		return false !== strpos( $screen->id, Menu::SLUG );
	}

	public function missing_build_notice(): void {
		echo '<div class="notice notice-error"><p>' .
			esc_html__( 'External Link Manager: admin assets have not been built yet. Run "npm install && npm run build" inside the plugin directory.', 'external-link-manager' ) .
			'</p></div>';
	}
}
