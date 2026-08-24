<?php
/**
 * Plugin Name:       External Link Manager
 * Plugin URI:        https://wordpress.org/plugins/external-link-manager/
 * Description:       Manage external links safely — new-tab handling, rel attributes (noopener, noreferrer, nofollow, sponsored, ugc), exclusions, a batch scanner, and content-scan statistics. Zero frontend bloat.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            External Link Manager
 * Author URI:        https://wordpress.org/plugins/external-link-manager/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       external-link-manager
 * Domain Path:       /languages
 *
 * @package ExternalLinkManager
 */

namespace ELM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------
// Core constants. Pro (and any other extension) detects Free via
// defined( 'ELM_VERSION' ) and/or class_exists( 'ELM\Plugin' ).
// ---------------------------------------------------------------------
if ( ! defined( 'ELM_VERSION' ) ) {
	define( 'ELM_VERSION', '1.0.0' );
}
if ( ! defined( 'ELM_DB_VERSION' ) ) {
	define( 'ELM_DB_VERSION', '1.0.0' );
}
if ( ! defined( 'ELM_PLUGIN_FILE' ) ) {
	define( 'ELM_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'ELM_PLUGIN_DIR' ) ) {
	define( 'ELM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ELM_PLUGIN_URL' ) ) {
	define( 'ELM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'ELM_PLUGIN_BASENAME' ) ) {
	define( 'ELM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

// ---------------------------------------------------------------------
// Minimal PSR-4-ish autoloader for the ELM\ namespace. Kept dependency
// free on purpose so Free never requires Composer at runtime.
// ---------------------------------------------------------------------
spl_autoload_register(
	function ( $class ) {
		if ( strpos( $class, 'ELM\\' ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( 'ELM\\' ) );
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
		$path     = ELM_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . $relative . '.php';

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, array( '\\ELM\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\ELM\\Core\\Deactivator', 'deactivate' ) );

/**
 * Boots the plugin on `init` rather than `plugins_loaded`. This is
 * deliberate: every plugin's `plugins_loaded` callbacks (at any
 * priority) are guaranteed to have finished before `init` fires, so
 * Pro (or any extension) registering `add_action( 'elm_loaded', ... )`
 * during its own `plugins_loaded` dependency check is guaranteed to
 * have that listener in place before this fires `elm_loaded` below.
 * Booting on `plugins_loaded` itself would race extensions that check
 * for Free at a lower priority than they register their listener.
 */
function elm_boot() {
	return Core\Plugin::instance();
}
add_action( 'init', __NAMESPACE__ . '\\elm_boot', 0 );
