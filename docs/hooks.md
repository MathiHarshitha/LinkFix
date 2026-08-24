# Developer API

External Link Manager Free exposes a stable set of hooks so themes, other plugins, and External Link Manager Pro can extend it without modifying core files.

## Extension registration

```php
add_action( 'elm_loaded', function ( \ELM\Core\Plugin $plugin ) {
	$plugin->register_extension( 'elm-pro', new \ELM_Pro\Bootstrap(), ELM_PRO_VERSION );
} );
```

Detect Free from another plugin:

```php
if ( defined( 'ELM_VERSION' ) && class_exists( '\ELM\Core\Plugin' ) ) {
	// Free is active.
}
```

## Filters

| Hook | Signature | Purpose |
|---|---|---|
| `elm_should_process_link` | `( bool $should, string $url, array $context )` | Veto processing of a specific link. |
| `elm_link_attributes` | `( array $attributes, string $url, array $context )` | Modify the `rel`/`target` attributes about to be applied. |
| `elm_excluded_domains` | `( array $entries, string $url )` | Add/modify exclusion entries at match time. |
| `elm_supported_post_types` | `( array $post_types )` | Change which post types are offered as scope. |
| `elm_default_settings` | `( array $defaults )` | Add default keys for settings an extension introduces. |
| `elm_sanitize_settings` | `( array $clean, array $input )` | Sanitize extra settings keys. |
| `elm_rule_engine_rules` | `( array $rules, string $url )` | Merge additional rule types (e.g. Pro's regex/conditional rules) into matching. |
| `elm_rule_engine_matched_actions` | `( array $actions, string $url )` | Adjust the final matched action set for a URL. |
| `elm_statistics_summary` | `( array $summary )` | Append additional statistics (e.g. link health counts). |
| `elm_admin_nav_items` | `( array $items )` | Add left-nav entries in the admin UI. |
| `elm_admin_extra_routes` | `( array $routes )` | Register additional client-side route ids. |

## Actions

| Hook | Signature | Purpose |
|---|---|---|
| `elm_loaded` | `( \ELM\Core\Plugin $plugin )` | Fires once Free's core services are ready. Register extensions here. |
| `elm_extension_registered` | `( string $slug, object $instance )` | Fires after an extension registers itself. |
| `elm_processed_link` | `( array $change, array $context )` | Fires after a single external link is processed. |
| `elm_scanner_batch_completed` | `( array $state )` | Fires after each Scanner batch. |
| `elm_admin_menu_registered` | `( string $slug, string $capability )` | Fires after Free's admin menu is registered. |
| `elm_after_install_tables` | `()` | Fires after Free's dbDelta() install/upgrade runs. |
| `elm_uninstall_cleanup` | `()` | Fires during uninstall, after Free removes its own data (only when the user opted in). |

## Admin UI extension (JavaScript)

Free's admin script is enqueued under the handle `elm-admin` (style: `elm-admin`). An
extension enqueues its own script with `elm-admin` as a dependency so it executes
immediately after, then registers pages/nav items before Free's app mounts
(Free defers its first render by one macrotask specifically to allow this):

```php
add_action( 'admin_enqueue_scripts', function () {
	$asset = require ELM_PRO_PLUGIN_DIR . 'build/index.asset.php';

	wp_enqueue_script(
		'elm-pro-admin',
		ELM_PRO_PLUGIN_URL . 'build/index.js',
		array_merge( $asset['dependencies'], array( 'elm-admin' ) ),
		$asset['version'],
		true
	);
} );
```

```js
// Pro's own src/index.js
import LinkHealth from './pages/LinkHealth';
import { chartBar } from '@wordpress/icons';

window.elmAdmin.registerPage( 'link-health', LinkHealth );
window.elmAdmin.registerNavItem( { id: 'link-health', label: 'Link Health', icon: chartBar } );
```

## Core services

```php
$plugin = \ELM\Core\Plugin::instance();

$plugin->settings();        // ELM\Settings
$plugin->link_processor();  // ELM\LinkProcessor\LinkProcessor
$plugin->scanner();         // ELM\Scanner\Scanner
```
