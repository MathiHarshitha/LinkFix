<?php
/**
 * PHPUnit bootstrap.
 *
 * Unit tests (tests/phpunit/unit) load plugin classes directly and
 * never touch WordPress — they run with plain `phpunit`.
 *
 * Integration tests (tests/phpunit/integration) need a WordPress test
 * environment. Install one with `wp-phpunit/wp-phpunit` (composer) or
 * the classic `bin/install-wp-tests.sh` script, then set WP_TESTS_DIR
 * (or WP_PHPUNIT__DIR) before running the suite.
 *
 * @package ExternalLinkManager
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require_once dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// Minimal ABSPATH shim so plugin files loaded outside WP (unit suite)
// don't hard-exit on `defined( 'ABSPATH' )` guards.
if ( ! defined( 'ABSPATH' ) && ! getenv( 'WP_PHPUNIT__DIR' ) && ! getenv( 'WP_TESTS_DIR' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/' );
}

$_wp_tests_dir = getenv( 'WP_PHPUNIT__DIR' ) ?: getenv( 'WP_TESTS_DIR' );

if ( $_wp_tests_dir ) {
	require_once $_wp_tests_dir . '/includes/functions.php';

	tests_add_filter(
		'muplugins_loaded',
		function () {
			require dirname( __DIR__, 2 ) . '/external-link-manager.php';
		}
	);

	require $_wp_tests_dir . '/includes/bootstrap.php';
}

spl_autoload_register(
	function ( $class ) {
		if ( strpos( $class, 'ELM\\' ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( 'ELM\\' ) );
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
		$path     = dirname( __DIR__, 2 ) . '/includes/' . $relative . '.php';

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);
