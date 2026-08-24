<?php
/**
 * Core plugin container: boots services, wires hooks, and hosts the
 * extension registry that Pro (or any third-party add-on) plugs into.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Core;

use ELM\Admin\Menu;
use ELM\Admin\Assets;
use ELM\Database\Installer;
use ELM\LinkProcessor\LinkProcessor;
use ELM\Scanner\Scanner;
use ELM\REST\RestApi;
use ELM\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	/**
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Registered extensions (e.g. Pro), keyed by slug.
	 *
	 * @var array<string, array{instance: object, version: string}>
	 */
	private $extensions = array();

	/** @var LinkProcessor */
	private $link_processor;

	/** @var Scanner */
	private $scanner;

	/** @var Settings */
	private $settings;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	private function __construct() {}

	private function init(): void {
		$this->maybe_upgrade();

		$this->settings       = new Settings();
		$this->link_processor = new LinkProcessor( $this->settings );
		$this->scanner        = new Scanner( $this->settings, $this->link_processor );

		\ELM\Scanner\Maintenance::register();

		load_plugin_textdomain( 'external-link-manager', false, dirname( ELM_PLUGIN_BASENAME ) . '/languages' );

		( new RestApi( $this->settings, $this->scanner, $this->link_processor ) )->register();

		if ( is_admin() ) {
			new Menu();
			new Assets();
		}

		// Content-scope filtering happens inside LinkProcessor::process_content(),
		// hooked here so third parties can unhook/replace it if needed.
		add_filter( 'the_content', array( $this->link_processor, 'process_content' ), 99 );

		new \ELM\Compatibility\WooCommerce( $this->link_processor );

		/**
		 * Fires once Free's core services are ready. Pro hooks its own
		 * bootstrap here rather than being loaded any earlier, guaranteeing
		 * $this->settings / link_processor / scanner already exist.
		 *
		 * @param Plugin $plugin
		 */
		do_action( 'elm_loaded', $this );
	}

	private function maybe_upgrade(): void {
		$installed = get_option( 'elm_db_version' );

		if ( $installed !== ELM_DB_VERSION ) {
			Installer::install();
			update_option( 'elm_db_version', ELM_DB_VERSION );
		}
	}

	/**
	 * Registration hook for Pro (or any extension). Kept intentionally
	 * generic so Free never has to know Pro's internals.
	 *
	 * Usage from Pro:
	 *   add_action( 'elm_loaded', function( $plugin ) {
	 *       $plugin->register_extension( 'elm-pro', $pro_instance, ELM_PRO_VERSION );
	 *   } );
	 *
	 * @param string $slug     Unique extension slug, e.g. 'elm-pro'.
	 * @param object $instance The extension's bootstrap object.
	 * @param string $version  Extension version string.
	 */
	public function register_extension( string $slug, object $instance, string $version = '' ): void {
		$this->extensions[ $slug ] = array(
			'instance' => $instance,
			'version'  => $version,
		);

		/**
		 * Fires after an extension registers itself with Free.
		 *
		 * @param string $slug
		 * @param object $instance
		 */
		do_action( 'elm_extension_registered', $slug, $instance );
	}

	public function get_extension( string $slug ) {
		return $this->extensions[ $slug ]['instance'] ?? null;
	}

	public function has_extension( string $slug ): bool {
		return isset( $this->extensions[ $slug ] );
	}

	public function get_extensions(): array {
		return $this->extensions;
	}

	public function settings(): Settings {
		return $this->settings;
	}

	public function link_processor(): LinkProcessor {
		return $this->link_processor;
	}

	public function scanner(): Scanner {
		return $this->scanner;
	}

	private function __clone() {}

	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}
}
