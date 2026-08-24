<?php
/**
 * Free rule engine: a small, non-conditional set of per-domain/per-URL
 * overrides (exclude, force nofollow/sponsored/ugc/new-tab). Advanced
 * conditional logic (regex, post type/category/author targeting,
 * priorities) is a Pro concern layered on via the `elm_rule_engine_*`
 * filters below — Free intentionally keeps this linear and simple.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Rules;

use ELM\LinkProcessor\UrlAnalyzer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RuleEngine {

	const OPTION_KEY = 'elm_rules';

	const ACTIONS = array( 'exclude', 'force_nofollow', 'force_sponsored', 'force_ugc', 'force_new_tab' );

	/** @var UrlAnalyzer */
	private $analyzer;

	public function __construct( ?UrlAnalyzer $analyzer = null ) {
		$this->analyzer = $analyzer ?? new UrlAnalyzer();
	}

	public function get_all(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		return is_array( $stored ) ? $stored : array();
	}

	public function save( array $rules ): array {
		$clean = array();

		foreach ( $rules as $rule ) {
			$action = isset( $rule['action'] ) ? sanitize_key( $rule['action'] ) : '';
			$match  = isset( $rule['match_type'] ) ? sanitize_key( $rule['match_type'] ) : 'domain';
			$value  = isset( $rule['match_value'] ) ? trim( (string) $rule['match_value'] ) : '';

			if ( ! in_array( $action, self::ACTIONS, true ) || '' === $value || ! in_array( $match, array( 'domain', 'url' ), true ) ) {
				continue;
			}

			$clean[] = array(
				'id'          => isset( $rule['id'] ) ? sanitize_key( $rule['id'] ) : wp_generate_uuid4(),
				'action'      => $action,
				'match_type'  => $match,
				'match_value' => 'domain' === $match ? $this->analyzer->normalize_host( $value ) : esc_url_raw( $value ),
				'enabled'     => ! empty( $rule['enabled'] ),
			);
		}

		update_option( self::OPTION_KEY, $clean );

		return $clean;
	}

	/**
	 * Returns the set of active actions ('exclude', 'force_nofollow', ...)
	 * that apply to the given absolute URL.
	 *
	 * @return string[]
	 */
	public function actions_for_url( string $url ): array {
		$parsed = $this->analyzer->parse( $url );
		$host   = $parsed ? $this->analyzer->normalize_host( $parsed['host'] ) : '';

		$matched = array();

		/**
		 * Filters the raw rule list before matching, so Pro's rule types
		 * (regex, post type, priority, etc.) can be merged in.
		 *
		 * @param array  $rules
		 * @param string $url
		 */
		$rules = apply_filters( 'elm_rule_engine_rules', $this->get_all(), $url );

		foreach ( $rules as $rule ) {
			if ( empty( $rule['enabled'] ) ) {
				continue;
			}

			$is_match = ( 'domain' === $rule['match_type'] && $host === $rule['match_value'] )
				|| ( 'url' === $rule['match_type'] && untrailingslashit( $url ) === untrailingslashit( $rule['match_value'] ) );

			if ( $is_match ) {
				$matched[] = $rule['action'];
			}
		}

		/**
		 * Filters the final matched action set for a URL.
		 *
		 * @param string[] $matched
		 * @param string   $url
		 */
		return apply_filters( 'elm_rule_engine_matched_actions', array_values( array_unique( $matched ) ), $url );
	}
}
