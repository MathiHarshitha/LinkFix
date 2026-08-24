<?php
/**
 * @package ExternalLinkManager
 */

use PHPUnit\Framework\TestCase;
use ELM\LinkProcessor\RelAttributeBuilder;

final class RelAttributeBuilderTest extends TestCase {

	/** @var RelAttributeBuilder */
	private $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new RelAttributeBuilder();
	}

	public function test_adds_tokens_to_empty_rel(): void {
		$this->assertSame( 'noopener', $this->builder->build( '', array( 'noopener' ) ) );
	}

	public function test_preserves_existing_user_authored_tokens(): void {
		$result = $this->builder->build( 'nofollow sponsored', array( 'noopener' ) );

		$this->assertStringContainsString( 'nofollow', $result );
		$this->assertStringContainsString( 'sponsored', $result );
		$this->assertStringContainsString( 'noopener', $result );
	}

	public function test_does_not_duplicate_existing_token(): void {
		$result = $this->builder->build( 'noopener', array( 'noopener' ) );
		$count  = substr_count( $result, 'noopener' );

		$this->assertSame( 1, $count );
	}

	public function test_is_case_insensitive_on_input_but_normalizes_output(): void {
		$result = $this->builder->build( 'NoFollow', array( 'sponsored' ) );

		$this->assertStringContainsString( 'nofollow', $result );
		$this->assertStringNotContainsString( 'NoFollow', $result );
	}

	public function test_removes_only_explicitly_requested_tokens(): void {
		$result = $this->builder->build( 'nofollow sponsored ugc', array(), array( 'sponsored' ) );

		$this->assertStringContainsString( 'nofollow', $result );
		$this->assertStringContainsString( 'ugc', $result );
		$this->assertStringNotContainsString( 'sponsored', $result );
	}

	public function test_collapses_duplicate_whitespace(): void {
		$result = $this->builder->build( "nofollow\t\tsponsored   ugc", array() );

		$this->assertSame( 'nofollow sponsored ugc', $result );
	}
}
