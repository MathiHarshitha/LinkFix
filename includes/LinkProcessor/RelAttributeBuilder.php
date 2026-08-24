<?php
/**
 * Merges desired rel tokens into an existing rel attribute without ever
 * discarding values the plugin did not create (theme/user/other-plugin
 * authored tokens like "nofollow sponsored" survive untouched).
 *
 * @package ExternalLinkManager
 */

namespace ELM\LinkProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RelAttributeBuilder {

	/**
	 * @param string   $existing_rel Current rel attribute value (may be empty).
	 * @param string[] $add          Tokens the current configuration wants present.
	 * @param string[] $remove       Tokens to remove, but ONLY if the plugin itself
	 *                                previously added them (see ManagedAttributes).
	 * @return string Normalized, de-duplicated rel value.
	 */
	public function build( string $existing_rel, array $add, array $remove = array() ): string {
		$tokens = preg_split( '/\s+/', trim( $existing_rel ), -1, PREG_SPLIT_NO_EMPTY );
		$tokens = array_map( 'strtolower', $tokens );

		if ( ! empty( $remove ) ) {
			$remove = array_map( 'strtolower', $remove );
			$tokens = array_values( array_diff( $tokens, $remove ) );
		}

		foreach ( $add as $token ) {
			$token = strtolower( trim( $token ) );

			if ( '' === $token ) {
				continue;
			}

			if ( ! in_array( $token, $tokens, true ) ) {
				$tokens[] = $token;
			}
		}

		// De-duplicate while preserving first-seen order.
		$tokens = array_values( array_unique( $tokens ) );
		sort( $tokens ); // Stable, predictable output (also groups values for diffing/tests).

		return implode( ' ', $tokens );
	}
}
