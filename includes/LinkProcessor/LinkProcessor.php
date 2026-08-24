<?php
/**
 * The reusable link-processing engine. Rewrites <a> tag attributes
 * (target, rel) on external links using WP_HTML_Tag_Processor — never
 * regex — and is designed to be called both from the live `the_content`
 * filter (stateless, nothing persisted) and from the Scanner/Bulk
 * Actions path (persisted back into post_content on demand).
 *
 * @package ExternalLinkManager
 */

namespace ELM\LinkProcessor;

use ELM\Settings;
use ELM\Exclusions\ExclusionList;
use ELM\Rules\RuleEngine;
use ELM\Support\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LinkProcessor {

	/** @var Settings */
	private $settings;

	/** @var UrlAnalyzer */
	private $analyzer;

	/** @var RelAttributeBuilder */
	private $rel_builder;

	/** @var ExclusionList */
	private $exclusions;

	/** @var RuleEngine */
	private $rules;

	public function __construct( Settings $settings ) {
		$this->settings    = $settings;
		$this->analyzer    = new UrlAnalyzer();
		$this->rel_builder = new RelAttributeBuilder();
		$this->exclusions  = new ExclusionList( $this->analyzer );
		$this->rules       = new RuleEngine( $this->analyzer );
	}

	public function analyzer(): UrlAnalyzer {
		return $this->analyzer;
	}

	public function exclusions(): ExclusionList {
		return $this->exclusions;
	}

	public function rule_engine(): RuleEngine {
		return $this->rules;
	}

	/**
	 * `the_content` filter callback. Stateless — computes attributes on
	 * the fly, writes nothing to the database.
	 */
	public function process_content( string $content ): string {
		if ( ! is_string( $content ) || '' === trim( $content ) || false === strpos( $content, '<a' ) ) {
			return $content;
		}

		if ( ! $this->is_current_post_in_scope() ) {
			return $content;
		}

		$result = $this->process_html( $content );

		return $result['html'];
	}

	private function is_current_post_in_scope(): bool {
		$post_type = get_post_type();

		if ( ! $post_type ) {
			return true; // Not inside a queried post (e.g. widget content) — process defensively.
		}

		$scope = $this->settings->get( 'content_types', array( 'post', 'page' ) );

		return in_array( $post_type, (array) $scope, true );
	}

	/**
	 * Core transform: walks every <a href> in $html and applies the
	 * configured target/rel handling to external links only.
	 *
	 * @param string $html
	 * @param array  $context Optional context, e.g. ['post_id' => 123].
	 * @return array{html: string, changes: array<int, array>} $changes is a list of
	 *         per-link change records (url, added rel tokens, target changed).
	 */
	public function process_html( string $html, array $context = array() ): array {
		$processor = new \WP_HTML_Tag_Processor( $html );
		$changes   = array();

		while ( $processor->next_tag( 'a' ) ) {
			$href = $processor->get_attribute( 'href' );

			if ( ! is_string( $href ) || $this->analyzer->should_ignore( $href ) ) {
				continue;
			}

			if ( ! $this->analyzer->is_external( $href ) ) {
				continue;
			}

			$absolute_url = $this->analyzer->resolve_absolute( $href );
			$existing_rel = (string) ( $processor->get_attribute( 'rel' ) ?? '' );

			// Extends (never overwrites) the caller-supplied context with
			// per-link facts, so extensions (e.g. Pro's advanced rule
			// conditions) can key off existing rel/target without a second pass.
			$link_context = array_merge(
				$context,
				array(
					'existing_rel'    => $existing_rel,
					'existing_target' => (string) ( $processor->get_attribute( 'target' ) ?? '' ),
				)
			);

			/**
			 * Filters whether a given external link should be processed at all.
			 * Returning false leaves the anchor completely untouched.
			 *
			 * @param bool   $should_process
			 * @param string $url
			 * @param array  $context
			 */
			if ( ! apply_filters( 'elm_should_process_link', true, $absolute_url, $link_context ) ) {
				continue;
			}

			if ( $this->exclusions->is_excluded( $absolute_url ) ) {
				continue;
			}

			$rule_actions = $this->rules->actions_for_url( $absolute_url );

			if ( in_array( 'exclude', $rule_actions, true ) ) {
				continue;
			}

			$attributes = $this->compute_attributes( $rule_actions, $absolute_url, $link_context );

			$new_rel = $this->rel_builder->build( $existing_rel, $attributes['rel_add'], $attributes['rel_remove'] ?? array() );

			if ( '' !== $new_rel && $new_rel !== strtolower( trim( $existing_rel ) ) ) {
				$processor->set_attribute( 'rel', $new_rel );
			}

			if ( $attributes['new_tab'] && '_blank' !== $processor->get_attribute( 'target' ) ) {
				$processor->set_attribute( 'target', '_blank' );
			}

			$change = array(
				'url'         => $absolute_url,
				'rel_added'   => $attributes['rel_add'],
				'rel_final'   => $new_rel,
				'new_tab'     => $attributes['new_tab'],
			);
			$changes[] = $change;

			/**
			 * Fires after a single external link has been processed.
			 *
			 * @param array $change
			 * @param array $context
			 */
			do_action( 'elm_processed_link', $change, $context );
		}

		return array(
			'html'    => $processor->get_updated_html(),
			'changes' => $changes,
		);
	}

	/**
	 * @param string[] $rule_actions Matched rule actions for this URL.
	 */
	private function compute_attributes( array $rule_actions, string $url, array $context ): array {
		$settings = $this->settings->get_all();

		$rel_add = array();

		if ( ! empty( $settings['noopener'] ) ) {
			$rel_add[] = 'noopener';
		}
		if ( ! empty( $settings['noreferrer'] ) ) {
			$rel_add[] = 'noreferrer';
		}
		if ( ! empty( $settings['nofollow'] ) || in_array( 'force_nofollow', $rule_actions, true ) ) {
			$rel_add[] = 'nofollow';
		}
		if ( ! empty( $settings['sponsored'] ) || in_array( 'force_sponsored', $rule_actions, true ) ) {
			$rel_add[] = 'sponsored';
		}
		if ( ! empty( $settings['ugc'] ) || in_array( 'force_ugc', $rule_actions, true ) ) {
			$rel_add[] = 'ugc';
		}

		$new_tab = ! empty( $settings['new_tab'] ) || in_array( 'force_new_tab', $rule_actions, true );

		$attributes = array(
			'rel_add'    => array_values( array_unique( $rel_add ) ),
			'rel_remove' => array(),
			'new_tab'    => $new_tab,
		);

		/**
		 * Filters the final attribute set about to be applied to an external link.
		 *
		 * @param array  $attributes {
		 *     @type string[] $rel_add    Rel tokens to ensure are present.
		 *     @type string[] $rel_remove Rel tokens to strip if present (Pro's "remove attribute" rule action).
		 *     @type bool     $new_tab    Whether to force target="_blank".
		 * }
		 * @param string $url
		 * @param array  $context
		 */
		return apply_filters( 'elm_link_attributes', $attributes, $url, $context );
	}

	/**
	 * Read-only discovery pass used by the Scanner: walks every external
	 * <a href> in $html and reports what it is / what it would become,
	 * without mutating anything. Excluded links are still reported (with
	 * is_excluded = true) so statistics can distinguish "external" from
	 * "external and actively managed".
	 *
	 * @return array<int, array{url:string, domain:string, rel:string, target_blank:bool, is_excluded:bool}>
	 */
	public function analyze_html( string $html, array $context = array() ): array {
		if ( '' === trim( $html ) || false === strpos( $html, '<a' ) ) {
			return array();
		}

		$processor = new \WP_HTML_Tag_Processor( $html );
		$results   = array();

		while ( $processor->next_tag( 'a' ) ) {
			$href = $processor->get_attribute( 'href' );

			if ( ! is_string( $href ) || $this->analyzer->should_ignore( $href ) || ! $this->analyzer->is_external( $href ) ) {
				continue;
			}

			$absolute_url = $this->analyzer->resolve_absolute( $href );
			$parsed       = $this->analyzer->parse( $absolute_url );
			$domain       = $parsed ? $this->analyzer->normalize_host( $parsed['host'] ) : '';
			$existing_rel = (string) ( $processor->get_attribute( 'rel' ) ?? '' );
			$existing_tgt = '_blank' === $processor->get_attribute( 'target' );

			$link_context = array_merge(
				$context,
				array(
					'existing_rel'    => $existing_rel,
					'existing_target' => (string) ( $processor->get_attribute( 'target' ) ?? '' ),
				)
			);

			$is_excluded = ! apply_filters( 'elm_should_process_link', true, $absolute_url, $link_context )
				|| $this->exclusions->is_excluded( $absolute_url );

			if ( $is_excluded ) {
				$results[] = array(
					'url'          => $absolute_url,
					'domain'       => $domain,
					'rel'          => $existing_rel,
					'target_blank' => $existing_tgt,
					'is_excluded'  => true,
				);
				continue;
			}

			$rule_actions = $this->rules->actions_for_url( $absolute_url );

			if ( in_array( 'exclude', $rule_actions, true ) ) {
				$results[] = array(
					'url'          => $absolute_url,
					'domain'       => $domain,
					'rel'          => $existing_rel,
					'target_blank' => $existing_tgt,
					'is_excluded'  => true,
				);
				continue;
			}

			$attributes = $this->compute_attributes( $rule_actions, $absolute_url, $link_context );
			$final_rel  = $this->rel_builder->build( $existing_rel, $attributes['rel_add'], $attributes['rel_remove'] ?? array() );

			$results[] = array(
				'url'          => $absolute_url,
				'domain'       => $domain,
				'rel'          => $final_rel,
				'target_blank' => $existing_tgt || $attributes['new_tab'],
				'is_excluded'  => false,
			);
		}

		return $results;
	}

	/**
	 * @return array<string, string> post_type => label, for admin UI scope pickers.
	 */
	public function get_available_post_types(): array {
		return PostTypes::get_public_post_types();
	}
}
