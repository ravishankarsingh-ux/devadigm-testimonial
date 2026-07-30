<?php
/**
 * Block registration and server-side rendering.
 *
 * @package Devadigm\Testimonials
 */

declare( strict_types = 1 );

namespace Devadigm\Testimonials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the testimonials block and feeds it the settings it needs.
 */
final class Block {

	/**
	 * Hook registration into WordPress.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_filter( 'block_categories_all', array( self::class, 'category' ), 10, 1 );
	}

	/**
	 * Add a category so the block is easy to find in the inserter.
	 *
	 * @param array<int,array<string,mixed>> $categories Existing categories.
	 * @return array<int,array<string,mixed>>
	 */
	public static function category( array $categories ): array {
		array_unshift(
			$categories,
			array(
				'slug'  => 'devadigm',
				'title' => __( 'Devadigm', 'devadigm-testimonials' ),
			)
		);
		return $categories;
	}

	/**
	 * Register the block type and hand the editor its choices.
	 */
	public static function register(): void {
		register_block_type(
			PLUGIN_DIR . 'blocks/testimonials',
			array( 'render_callback' => array( self::class, 'render' ) )
		);

		$services = get_terms(
			array(
				'taxonomy'   => TAX_SERVICE,
				'hide_empty' => false,
			)
		);
		$sources  = get_terms(
			array(
				'taxonomy'   => TAX_SOURCE,
				'hide_empty' => false,
			)
		);

		$to_options = static function ( mixed $terms ): array {
			$out = array( array( 'label' => __( 'All', 'devadigm-testimonials' ), 'value' => '' ) );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( $term instanceof \WP_Term ) {
						$out[] = array(
							'label' => $term->name,
							'value' => $term->slug,
						);
					}
				}
			}
			return $out;
		};

		$to_choices = static function ( array $map ): array {
			$out = array();
			foreach ( $map as $value => $label ) {
				$out[] = array(
					'label' => $label,
					'value' => $value,
				);
			}
			return $out;
		};

		wp_localize_script(
			'devadigm-testimonials-editor-script',
			'dvdmTestimonials',
			array(
				'layouts'       => $to_choices( Settings::layouts() ),
				'markStyles'    => $to_choices( Settings::mark_styles() ),
				'services'      => $to_options( $services ),
				'sources'       => $to_options( $sources ),
				'palette'       => Settings::theme_palette(),
				'fonts'         => Settings::theme_fonts(),
				'defaultLayout' => Settings::get( 'default_layout', 'spotlight' ),
				'defaultMark'   => Settings::get( 'default_mark', 'ledger' ),
				'settingsUrl'   => admin_url( 'edit.php?post_type=' . POST_TYPE . '&page=dvdm-testimonials-design' ),
			)
		);
	}

	/**
	 * Render the block on the front end.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public static function render( array $attributes ): string {
		$overrides = self::instance_tokens( $attributes );

		$wrapper = get_block_wrapper_attributes(
			'' !== $overrides ? array( 'style' => $overrides ) : array()
		);

		return Renderer::render( $attributes, $wrapper );
	}

	/**
	 * Turn per-block colour and font overrides into inline custom properties.
	 *
	 * These sit above the settings screen in the cascade, so one block can look
	 * different without changing the site-wide defaults.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	private static function instance_tokens( array $attributes ): string {
		$map = array(
			'accentColor' => '--dvdm-accent',
			'quoteColor'  => '--dvdm-quote-color',
			'nameColor'   => '--dvdm-name-color',
			'metaColor'   => '--dvdm-meta-color',
			'surfaceColor' => '--dvdm-surface',
			'borderColor' => '--dvdm-border',
			'quoteFont'   => '--dvdm-quote-font',
			'bodyFont'    => '--dvdm-body-font',
		);

		$out = '';
		foreach ( $map as $attribute => $property ) {
			$value = isset( $attributes[ $attribute ] ) ? (string) $attributes[ $attribute ] : '';
			if ( '' === $value ) {
				continue;
			}
			$kind     = str_contains( $property, 'font' ) ? 'font-family' : 'color';
			$resolved = Tokens::resolve( $value, $kind, 'inherit' );
			$out     .= $property . ':' . $resolved . ';';
		}

		/*
		 * The scales have no default in block.json on purpose. An unset value
		 * means "use the site setting", so a block only writes the property
		 * when someone has actually moved the slider. Giving them a default of
		 * 1 would make every block silently override the settings screen.
		 */
		if ( isset( $attributes['quoteScale'] ) && is_numeric( $attributes['quoteScale'] ) && (float) $attributes['quoteScale'] > 0 ) {
			$out .= '--dvdm-quote-scale:' . (float) $attributes['quoteScale'] . ';';
		}
		if ( isset( $attributes['markScale'] ) && is_numeric( $attributes['markScale'] ) && (float) $attributes['markScale'] > 0 ) {
			$out .= '--dvdm-mark-scale:' . (float) $attributes['markScale'] . ';';
		}
		if ( isset( $attributes['columns'] ) && is_numeric( $attributes['columns'] ) ) {
			$out .= '--dvdm-columns:' . max( 1, min( 6, (int) $attributes['columns'] ) ) . ';';
		}

		return $out;
	}
}
