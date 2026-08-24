<?php
/**
 * WooCommerce compatibility: Free does not require WooCommerce and
 * never loads any of this unless it is active.
 *
 * @package ExternalLinkManager
 */

namespace ELM\Compatibility;

use ELM\LinkProcessor\LinkProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WooCommerce {

	/** @var LinkProcessor */
	private $processor;

	public function __construct( LinkProcessor $processor ) {
		$this->processor = $processor;

		if ( ! class_exists( '\WooCommerce' ) ) {
			return;
		}

		add_filter( 'elm_supported_post_types', array( $this, 'ensure_product_selectable' ) );

		// "External/Affiliate product" buy buttons carry their own external
		// URL outside post_content — run them through the same engine so
		// rel/target settings stay consistent site-wide.
		$this->register_button_filter();
	}

	public function ensure_product_selectable( array $post_types ): array {
		if ( ! isset( $post_types['product'] ) ) {
			$product_type = get_post_type_object( 'product' );

			if ( $product_type ) {
				$post_types['product'] = $product_type->labels->name;
			}
		}

		return $post_types;
	}

	/**
	 * External/affiliate products render their buy button via a template
	 * that echoes an <a> tag directly (not through the_content). We hook
	 * the anchor HTML filter WooCommerce exposes for that button and run
	 * it through the same LinkProcessor used everywhere else.
	 */
	public function register_button_filter(): void {
		add_filter(
			'woocommerce_loop_add_to_cart_link',
			function ( $html ) {
				global $product;

				if ( ! $product || ! $product->is_type( 'external' ) ) {
					return $html;
				}

				$result = $this->processor->process_html( $html, array( 'post_id' => $product->get_id() ) );

				return $result['html'];
			}
		);
	}
}
