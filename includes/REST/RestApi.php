<?php
/**
 * Registers Free's REST API surface (namespace elm/v1). Every route
 * requires `manage_options` and goes through the same server-side
 * sanitizers used elsewhere — the REST layer never trusts client input.
 *
 * @package ExternalLinkManager
 */

namespace ELM\REST;

use ELM\Settings;
use ELM\Scanner\Scanner;
use ELM\Scanner\BulkApplier;
use ELM\LinkProcessor\LinkProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestApi {

	const NAMESPACE_V1 = 'elm/v1';

	/** @var Settings */
	private $settings;

	/** @var Scanner */
	private $scanner;

	/** @var LinkProcessor */
	private $processor;

	public function __construct( Settings $settings, Scanner $scanner, LinkProcessor $processor ) {
		$this->settings  = $settings;
		$this->scanner   = $scanner;
		$this->processor = $processor;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		$bulk_applier = new BulkApplier( $this->settings, $this->processor );

		( new SettingsController( $this->settings ) )->register_routes();
		( new RulesController( $this->processor ) )->register_routes();
		( new ExclusionsController( $this->processor ) )->register_routes();
		( new ScannerController( $this->scanner, $bulk_applier ) )->register_routes();
		( new StatisticsController( $this->scanner ) )->register_routes();
		( new SystemController() )->register_routes();
	}

	public static function permission_check(): bool {
		return current_user_can( 'manage_options' );
	}
}
