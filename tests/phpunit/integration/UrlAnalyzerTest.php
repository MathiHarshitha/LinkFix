<?php
/**
 * Requires a WordPress test environment (WP_TESTS_DIR / WP_PHPUNIT__DIR).
 *
 * @package ExternalLinkManager
 */

use ELM\LinkProcessor\UrlAnalyzer;

final class UrlAnalyzerTest extends WP_UnitTestCase {

	/** @var UrlAnalyzer */
	private $analyzer;

	public function setUp(): void {
		parent::setUp();
		$this->analyzer = new UrlAnalyzer();
	}

	public function test_detects_external_url(): void {
		$this->assertTrue( $this->analyzer->is_external( 'https://external-example.test/page' ) );
	}

	public function test_detects_internal_url(): void {
		$this->assertFalse( $this->analyzer->is_external( home_url( '/some-page/' ) ) );
	}

	public function test_relative_url_is_internal(): void {
		$this->assertFalse( $this->analyzer->is_external( '/some-page/' ) );
		$this->assertFalse( $this->analyzer->is_external( 'some-page' ) );
		$this->assertFalse( $this->analyzer->is_external( '?query=1' ) );
	}

	public function test_protocol_relative_external_url(): void {
		$this->assertTrue( $this->analyzer->is_external( '//external-example.test/page' ) );
	}

	public function test_www_and_bare_domain_are_treated_as_same_site(): void {
		$this->assertSame(
			$this->analyzer->normalize_host( 'www.example.com' ),
			$this->analyzer->normalize_host( 'example.com' )
		);
	}

	public function test_unrelated_subdomain_of_site_host_is_still_external(): void {
		$site_host  = wp_parse_url( home_url(), PHP_URL_HOST );
		$sub_domain = 'unrelated-blog.' . $site_host;

		$this->assertTrue( $this->analyzer->is_external( 'https://' . $sub_domain . '/x' ) );
	}

	public function test_ignores_mailto_tel_javascript_and_data_links(): void {
		$this->assertTrue( $this->analyzer->should_ignore( 'mailto:test@example.com' ) );
		$this->assertTrue( $this->analyzer->should_ignore( 'tel:+15551234567' ) );
		$this->assertTrue( $this->analyzer->should_ignore( 'javascript:void(0)' ) );
		$this->assertTrue( $this->analyzer->should_ignore( 'data:text/plain;base64,SGVsbG8=' ) );
		$this->assertTrue( $this->analyzer->should_ignore( '#anchor' ) );
	}

	public function test_does_not_ignore_plain_http_https(): void {
		$this->assertFalse( $this->analyzer->should_ignore( 'https://external-example.test/' ) );
	}

	public function test_url_with_port_and_query_and_fragment_parses_correctly(): void {
		$parsed = $this->analyzer->parse( 'https://external-example.test:8443/path?x=1&y=2#section' );

		$this->assertSame( 'external-example.test', $parsed['host'] );
		$this->assertSame( '8443', (string) $parsed['port'] );
		$this->assertSame( 'x=1&y=2', $parsed['query'] );
		$this->assertSame( 'section', $parsed['fragment'] );
	}
}
