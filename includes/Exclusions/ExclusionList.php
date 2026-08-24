<?php
/**
 * Stores and matches exclusion entries: exact domains, wildcard
 * subdomains (*.example.com), exact URLs, and URL-glob patterns
 * (example.com/partners/*).
 *
 * @package ExternalLinkManager
 */

namespace ELM\Exclusions;

use ELM\LinkProcessor\UrlAnalyzer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ExclusionList {

	const OPTION_KEY = 'elm_exclusions';

	/** @var UrlAnalyzer */
	private $analyzer;

	public function __construct( ?UrlAnalyzer $analyzer = null ) {
		$this->analyzer = $analyzer ?? new UrlAnalyzer();
	}

	/**
	 * @return array<int, array{type: string, value: string}>
	 */
	public function get_all(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * @param array<int, array{type: string, value: string}> $entries
	 */
	public function save( array $entries ): array {
		$clean = array();
		$seen  = array();

		foreach ( $entries as $entry ) {
			$type  = isset( $entry['type'] ) ? sanitize_key( $entry['type'] ) : '';
			$value = isset( $entry['value'] ) ? trim( (string) $entry['value'] ) : '';

			if ( '' === $value || ! in_array( $type, array( 'domain', 'subdomain', 'url', 'pattern' ), true ) ) {
				continue;
			}

			if ( 'domain' === $type || 'subdomain' === $type ) {
				$value = $this->analyzer->normalize_host( ltrim( $value, '*.' ) );
				$key   = $type . ':' . $value;
			} elseif ( 'url' === $type ) {
				$value = esc_url_raw( $value );
				$key   = 'url:' . $value;
			} else {
				$value = sanitize_text_field( $value );
				$key   = 'pattern:' . $value;
			}

			if ( '' === $value || isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$clean[]      = array(
				'type'  => $type,
				'value' => $value,
			);
		}

		update_option( self::OPTION_KEY, $clean );

		return $clean;
	}

	/**
	 * True if the given absolute (or resolved) URL matches any exclusion rule.
	 */
	public function is_excluded( string $url ): bool {
		$parsed = $this->analyzer->parse( $url );

		if ( null === $parsed ) {
			return false;
		}

		$host = $this->analyzer->normalize_host( $parsed['host'] );

		/**
		 * Filters the exclusion list just before matching, so Pro can layer
		 * regex/advanced-domain rules on top without Free knowing about them.
		 *
		 * @param array  $entries
		 * @param string $url
		 */
		$entries = apply_filters( 'elm_excluded_domains', $this->get_all(), $url );

		foreach ( $entries as $entry ) {
			switch ( $entry['type'] ) {
				case 'domain':
					if ( $host === $entry['value'] ) {
						return true;
					}
					break;

				case 'subdomain':
					$suffix = '.' . $entry['value'];
					if ( $host === $entry['value'] || substr( $host, -strlen( $suffix ) ) === $suffix ) {
						return true;
					}
					break;

				case 'url':
					if ( untrailingslashit( $url ) === untrailingslashit( $entry['value'] ) ) {
						return true;
					}
					break;

				case 'pattern':
					if ( $this->matches_pattern( $url, $entry['value'] ) ) {
						return true;
					}
					break;
			}
		}

		return false;
	}

	private function matches_pattern( string $url, string $pattern ): bool {
		// Support patterns with or without a scheme; compare against the
		// URL both with and without the scheme+www for a forgiving match.
		$regex = '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '$#i';

		$candidates = array(
			$url,
			preg_replace( '#^https?://#i', '', $url ),
			preg_replace( '#^https?://(www\.)?#i', '', $url ),
		);

		foreach ( $candidates as $candidate ) {
			if ( preg_match( $regex, $candidate ) ) {
				return true;
			}
		}

		return false;
	}
}
