<?php
/**
 * Central settings store. Options live in a single `elm_settings` row,
 * registered through the Settings API so core WP tooling (and the REST
 * API) share one sanitize/validate path.
 *
 * @package ExternalLinkManager
 */

namespace ELM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	const OPTION_KEY = 'elm_settings';

	public function __construct() {
		add_action( 'init', array( $this, 'register_settings' ) );
	}

	public static function defaults(): array {
		/**
		 * Filters the default settings before they are used as a fallback.
		 * Pro uses this to seed additional default keys without touching Free.
		 *
		 * @param array $defaults
		 */
		return apply_filters(
			'elm_default_settings',
			array(
				'new_tab'             => true,
				'noopener'            => true,
				'noreferrer'          => false,
				'nofollow'            => false,
				'sponsored'           => false,
				'ugc'                 => false,
				'statistics_enabled'  => true,
				'auto_apply_existing' => false,
				'content_types'       => array( 'post', 'page' ),
				'scanner_batch_size'  => 50,
			)
		);
	}

	public function register_settings(): void {
		register_setting(
			'elm_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false, // Free ships its own REST controller for validation parity.
			)
		);
	}

	public function get_all(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, self::defaults() );
	}

	public function get( string $key, $default = null ) {
		$all = $this->get_all();

		return $all[ $key ] ?? $default;
	}

	public function update( array $partial ): array {
		$sanitized = $this->sanitize( array_merge( $this->get_all(), $partial ) );
		update_option( self::OPTION_KEY, $sanitized );

		return $sanitized;
	}

	/**
	 * Authoritative, server-side sanitizer. Never trust the client (React)
	 * for validation — this is the single source of truth.
	 *
	 * @param mixed $input
	 * @return array
	 */
	public function sanitize( $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$clean    = array();

		foreach ( array( 'new_tab', 'noopener', 'noreferrer', 'nofollow', 'sponsored', 'ugc', 'statistics_enabled', 'auto_apply_existing' ) as $bool_key ) {
			$clean[ $bool_key ] = ! empty( $input[ $bool_key ] );
		}

		$allowed_types           = array_keys( \ELM\Support\PostTypes::get_public_post_types() );
		$requested_types         = isset( $input['content_types'] ) && is_array( $input['content_types'] ) ? $input['content_types'] : $defaults['content_types'];
		$clean['content_types']  = array_values( array_intersect( array_map( 'sanitize_key', $requested_types ), $allowed_types ) );

		if ( empty( $clean['content_types'] ) ) {
			$clean['content_types'] = $defaults['content_types'];
		}

		$batch_size                  = isset( $input['scanner_batch_size'] ) ? absint( $input['scanner_batch_size'] ) : $defaults['scanner_batch_size'];
		$clean['scanner_batch_size'] = min( 200, max( 5, $batch_size ) );

		/**
		 * Lets Pro (or other extensions) sanitize additional settings keys
		 * they introduced, without Free needing to know about them.
		 *
		 * @param array $clean
		 * @param array $input
		 */
		return apply_filters( 'elm_sanitize_settings', $clean, $input );
	}
}
