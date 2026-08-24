<?php
/**
 * Requires a WordPress test environment (WP_TESTS_DIR / WP_PHPUNIT__DIR).
 *
 * @package ExternalLinkManager
 */

use ELM\Settings;
use ELM\LinkProcessor\LinkProcessor;

final class LinkProcessorTest extends WP_UnitTestCase {

	private function make_processor( array $settings_overrides = array() ): LinkProcessor {
		update_option( Settings::OPTION_KEY, array_merge( Settings::defaults(), $settings_overrides ) );

		return new LinkProcessor( new Settings() );
	}

	public function test_leaves_internal_links_untouched(): void {
		$processor = $this->make_processor();
		$html      = '<a href="' . home_url( '/about/' ) . '">About</a>';
		$result    = $processor->process_html( $html );

		$this->assertSame( $html, $result['html'] );
		$this->assertEmpty( $result['changes'] );
	}

	public function test_adds_configured_attributes_to_external_link(): void {
		$processor = $this->make_processor( array( 'new_tab' => true, 'noopener' => true, 'nofollow' => true ) );
		$html      = '<a href="https://external-example.test/">Example</a>';
		$result    = $processor->process_html( $html );

		$this->assertStringContainsString( 'target="_blank"', $result['html'] );
		$this->assertStringContainsString( 'noopener', $result['html'] );
		$this->assertStringContainsString( 'nofollow', $result['html'] );
	}

	public function test_preserves_existing_rel_values_configured_off(): void {
		$processor = $this->make_processor( array( 'nofollow' => false, 'sponsored' => false, 'noopener' => true ) );
		$html      = '<a href="https://external-example.test/" rel="nofollow sponsored">Example</a>';
		$result    = $processor->process_html( $html );

		$this->assertStringContainsString( 'nofollow', $result['html'] );
		$this->assertStringContainsString( 'sponsored', $result['html'] );
		$this->assertStringContainsString( 'noopener', $result['html'] );
	}

	public function test_excluded_domain_is_never_touched(): void {
		$processor = $this->make_processor( array( 'noopener' => true ) );
		$processor->exclusions()->save( array( array( 'type' => 'domain', 'value' => 'external-example.test' ) ) );

		$html   = '<a href="https://external-example.test/">Example</a>';
		$result = $processor->process_html( $html );

		$this->assertSame( $html, $result['html'] );
	}

	public function test_rule_can_force_nofollow_even_when_setting_is_off(): void {
		$processor = $this->make_processor( array( 'nofollow' => false ) );
		$processor->rule_engine()->save(
			array(
				array(
					'action'      => 'force_nofollow',
					'match_type'  => 'domain',
					'match_value' => 'external-example.test',
					'enabled'     => true,
				),
			)
		);

		$html   = '<a href="https://external-example.test/">Example</a>';
		$result = $processor->process_html( $html );

		$this->assertStringContainsString( 'nofollow', $result['html'] );
	}
}
