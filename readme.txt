=== External Link Manager ===
Contributors: externallinkmanager
Tags: external links, nofollow, noopener, seo, link management
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage external links safely — new-tab handling, rel attributes, exclusions, a batch scanner, and content-scan statistics. Zero frontend bloat.

== Description ==

External Link Manager gives you full, server-side control over how external links behave on your WordPress site — without loading any JavaScript, CSS, or tracking on the frontend.

**Link management**

* Detects external links using proper URL parsing (subdomains, ports, query strings, protocol-relative URLs — all handled correctly)
* Opens external links in a new tab (optional)
* Adds `rel="noopener"`, `noreferrer`, `nofollow`, `sponsored`, `ugc` — independently configurable
* Never overwrites rel values it didn't add; existing `nofollow`/`sponsored`/etc. values from your theme or other plugins are always preserved

**Exclusions & rules**

* Exclude exact domains, whole subdomain trees, exact URLs, or wildcard URL patterns
* Simple per-domain/per-URL rules: force nofollow, sponsored, ugc, new tab, or exclude entirely

**Scanner & statistics**

* Batch-scans your existing content to index external links (never modifies content)
* Pausable, resumable, and safe to interrupt — no single request ever tries to scan your whole site at once
* Dashboard and Statistics pages show total external links, unique domains, top 20 domains, and a rel-attribute breakdown — all computed locally, nothing sent anywhere

**Bulk actions**

* Optionally apply your current settings to existing content, rewriting stored `<a>` attributes in safe batches

**Built to extend**

External Link Manager Free ships a stable extension API (`elm_loaded`, `register_extension()`, and a full set of filters/actions) so External Link Manager Pro — and other add-ons — can layer broken-link monitoring, scheduled scans, advanced rules, and reporting on top, without forking or duplicating the core engine.

= Privacy =

This plugin does not track visitors, does not record IP addresses, and does not send any data to third-party services. All statistics come from scanning your own content.

== Installation ==

1. Upload the `external-link-manager` folder to `/wp-content/plugins/`, or install via Plugins → Add New.
2. Activate the plugin.
3. Go to **Link Manager** in the admin menu to configure settings, rules, and exclusions, then run a scan from the Scanner tab.

== Frequently Asked Questions ==

= Does this slow down my site? =

No. Free loads zero JavaScript/CSS on the frontend. Link processing happens server-side using WordPress's own HTML Tag Processor.

= Will this remove rel values I already have? =

No. Existing rel tokens are always preserved and merged with the ones you configure.

= Does deleting the plugin delete my data? =

Not by default. Enable "Remove plugin data on uninstall" under Tools if you want settings and scan data removed on deletion. Your post content is never modified by uninstall.

== Changelog ==

= 1.0.0 =
* Initial release.
