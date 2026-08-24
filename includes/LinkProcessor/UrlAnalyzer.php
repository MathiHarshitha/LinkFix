<?php
/**
 * Classifies an <a href> as internal, external, or "ignore" (mailto:,
 * tel:, javascript:, data:, anchors, etc.) using proper URL parsing
 * rather than string matching.
 *
 * @package ExternalLinkManager
 */

namespace ELM\LinkProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UrlAnalyzer {

	/**
	 * Schemes that should never be touched (rel/target added) at all.
	 *
	 * @var string[]
	 */
	private static $ignored_schemes = array( 'mailto', 'tel', 'javascript', 'data', 'sms', 'fax', 'callto', 'skype' );

	/**
	 * @var string Cached, normalized host of the current site.
	 */
	private $site_host;

	public function __construct() {
		$this->site_host = $this->normalize_host( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	/**
	 * True if the href should be skipped entirely (not a navigable http(s) URL).
	 */
	public function should_ignore( string $href ): bool {
		$href = trim( $href );

		if ( '' === $href ) {
			return true;
		}

		// Pure fragment, e.g. "#section".
		if ( '#' === $href[0] ) {
			return true;
		}

		$scheme = wp_parse_url( $href, PHP_URL_SCHEME );

		if ( $scheme && in_array( strtolower( $scheme ), self::$ignored_schemes, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * True if the href points off-site relative to the current WordPress
	 * install (different registrable host). Relative and protocol-relative
	 * URLs are resolved against home_url() first.
	 */
	public function is_external( string $href ): bool {
		if ( $this->should_ignore( $href ) ) {
			return false;
		}

		$host = $this->extract_host( $href );

		if ( null === $host ) {
			// No host at all => path-relative URL => internal.
			return false;
		}

		return $this->normalize_host( $host ) !== $this->site_host;
	}

	/**
	 * Resolves the effective host for a (possibly relative/protocol-relative) href.
	 */
	public function extract_host( string $href ): ?string {
		$href = trim( $href );

		// Protocol-relative: //example.com/path
		if ( 0 === strpos( $href, '//' ) ) {
			$host = wp_parse_url( 'http:' . $href, PHP_URL_HOST );
			return $host ?: null;
		}

		$host = wp_parse_url( $href, PHP_URL_HOST );

		if ( $host ) {
			return $host;
		}

		// Relative URL (no scheme, no host): "/path", "path", "?q=1", "../x".
		return null;
	}

	/**
	 * Resolves a protocol-relative href ("//example.com/x") to an absolute
	 * URL using the current request scheme. Absolute hrefs pass through
	 * unchanged; relative hrefs (already filtered out as internal before
	 * this is called) are returned as-is.
	 */
	public function resolve_absolute( string $href ): string {
		if ( 0 === strpos( $href, '//' ) ) {
			return ( is_ssl() ? 'https:' : 'http:' ) . $href;
		}

		return $href;
	}

	/**
	 * Lowercases and strips a leading "www." so example.com and
	 * www.example.com are treated as the same site host, while still
	 * treating unrelated subdomains (blog.example.com) as external.
	 */
	public function normalize_host( string $host ): string {
		$host = strtolower( trim( $host ) );
		$host = preg_replace( '/^www\./', '', $host );

		return (string) $host;
	}

	/**
	 * Returns [scheme, host, port, path, query, fragment] safely, or null
	 * for hrefs with no parseable host.
	 */
	public function parse( string $href ): ?array {
		$host = $this->extract_host( $href );

		if ( null === $host ) {
			return null;
		}

		$normalized = 0 === strpos( $href, '//' ) ? 'http:' . $href : $href;
		$parts      = wp_parse_url( $normalized );

		if ( false === $parts ) {
			return null;
		}

		return array(
			'scheme'   => $parts['scheme'] ?? '',
			'host'     => $parts['host'] ?? '',
			'port'     => $parts['port'] ?? null,
			'path'     => $parts['path'] ?? '',
			'query'    => $parts['query'] ?? '',
			'fragment' => $parts['fragment'] ?? '',
		);
	}
}
