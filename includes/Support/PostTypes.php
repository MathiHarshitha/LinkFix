<?php
/**
 * Small helper for resolving the public post types eligible for link
 * processing/scanning (posts, pages, and public custom post types).
 *
 * @package ExternalLinkManager
 */

namespace ELM\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PostTypes {

	/**
	 * @return array<string, string> post_type => label
	 */
	public static function get_public_post_types(): array {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		$excluded = array( 'attachment' );

		$out = array();

		foreach ( $post_types as $slug => $object ) {
			if ( in_array( $slug, $excluded, true ) ) {
				continue;
			}

			$out[ $slug ] = $object->labels->name ?? $slug;
		}

		/**
		 * Filters the post types offered as scan/processing scope in the admin UI.
		 *
		 * @param array $out
		 */
		return apply_filters( 'elm_supported_post_types', $out );
	}
}
