<?php
/**
 * Registers the admin menu. Every page renders the same React root; the
 * app reads which page it's on from the localized route id and does its
 * own lightweight client-side routing between Dashboard, Settings,
 * Rules, Exclusions, Scanner, Statistics, Tools and Docs.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Menu {

	const SLUG = 'external-link-manager';

	/**
	 * @return array<string, array{slug: string, label: string}> route id => page slug/label.
	 */
	public static function routes(): array {
		return array(
			'dashboard'  => array(
				'slug'  => self::SLUG,
				'label' => __( 'Dashboard', 'external-link-manager' ),
			),
			'settings'   => array(
				'slug'  => self::SLUG . '-settings',
				'label' => __( 'Settings', 'external-link-manager' ),
			),
			'rules'      => array(
				'slug'  => self::SLUG . '-rules',
				'label' => __( 'Rules', 'external-link-manager' ),
			),
			'exclusions' => array(
				'slug'  => self::SLUG . '-exclusions',
				'label' => __( 'Exclusions', 'external-link-manager' ),
			),
			'scanner'    => array(
				'slug'  => self::SLUG . '-scanner',
				'label' => __( 'Scanner', 'external-link-manager' ),
			),
			'statistics' => array(
				'slug'  => self::SLUG . '-statistics',
				'label' => __( 'Statistics', 'external-link-manager' ),
			),
			'tools'      => array(
				'slug'  => self::SLUG . '-tools',
				'label' => __( 'Tools', 'external-link-manager' ),
			),
			'docs'       => array(
				'slug'  => self::SLUG . '-docs',
				'label' => __( 'Documentation', 'external-link-manager' ),
			),
		);
	}

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	public function register_menu(): void {
		$cap    = 'manage_options';
		$routes = self::routes();

		add_menu_page(
			__( 'External Link Manager', 'external-link-manager' ),
			__( 'Link Manager', 'external-link-manager' ),
			$cap,
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-admin-links',
			80
		);

		foreach ( $routes as $route_id => $route ) {
			add_submenu_page(
				self::SLUG,
				sprintf(
					/* translators: %s: tab label. */
					__( 'External Link Manager — %s', 'external-link-manager' ),
					$route['label']
				),
				$route['label'],
				$cap,
				$route['slug'],
				array( $this, 'render' )
			);
		}

		/**
		 * Fires after Free registers its admin menu, letting Pro add its
		 * own submenu entries (Link Health, Advanced Rules, Reports, ...)
		 * under the same top-level slug.
		 *
		 * @param string $slug
		 * @param string $cap
		 */
		do_action( 'elm_admin_menu_registered', self::SLUG, $cap );
	}

	public function render(): void {
		echo '<div id="elm-admin-root" class="elm-admin-root"></div>';
	}

	public static function current_route_id(): string {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : self::SLUG; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		foreach ( self::routes() as $route_id => $route ) {
			if ( $route['slug'] === $page ) {
				return $route_id;
			}
		}

		return 'dashboard';
	}
}
