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
				'layouts'    => $to_choices( Settings::layouts() ),
				'markStyles' => $to_choices( Settings::mark_styles() ),
				'services'   => $to_options( $services ),
				'sources'    => $to_options( $sources ),
				'palette'    => Settings::theme_palette(),
				'fonts'      => Settings::theme_fonts(),
				/*
				 * Nested rather than flattened into top-level keys, because
				 * wp_localize_script() casts every top-level scalar value to a
				 * string before it reaches the browser - a long-standing
				 * WordPress behaviour, not a bug in this call. A value nested
				 * inside an array survives with its real type intact, which is
				 * the only reason legacyLayouts' booleans below come through
				 * correctly. Flattened as they were before, `defaultLoop` and
				 * `defaultReadMore` arrived as the strings "1" or "", and
				 * `"" !== false` is true in JavaScript - so a site with either
				 * setting switched off still had the editor believe it was on,
				 * silently ignoring the Design screen. Every number and boolean
				 * the editor reads is nested here for that reason.
				 */
				'defaults'   => array(
					'layout'    => Settings::get( 'default_layout', 'spotlight' ),
					'mark'      => Settings::get( 'default_mark', 'ledger' ),
					'columns'   => (int) Settings::get( 'default_columns', 3 ),
					'loop'      => (bool) Settings::get( 'slider_loop', true ),
					'autoplay'  => (int) Settings::get( 'slider_autoplay', 0 ),
					'readMore'  => (bool) Settings::get( 'read_more', true ),
				),
				/*
				 * The full translation table, not just the legacy names. The
				 * editor needs to compute the same effective layout, columns
				 * and slider state that the PHP renderer does, so a block still
				 * carrying an old name shows its real current controls instead
				 * of hiding them until someone happens to re-pick a layout from
				 * the dropdown.
				 */
				'legacyLayouts' => Settings::legacy_layouts(),
				'settingsUrl'   => admin_url( 'edit.php?post_type=' . POST_TYPE . '&page=dvdm-testimonials-design' ),
			)
		);
	}

	/**
	 * Render the block on the front end.
	 *
	 * Attributes are normalised once, here, and the same settled array is
	 * handed to both the style builder and the markup renderer. Normalising
	 * twice with two different call paths is how a fresh block - one where
	 * nobody has touched the Columns slider, so the raw attribute is simply
	 * absent - ended up with its arrangement decided one way in PHP (which
	 * defaults an unset column count from the Design screen) and a different
	 * way in CSS (which had no property to read and fell back to a number
	 * hardcoded in the stylesheet). Settling the attributes before either
	 * consumer sees them keeps both reading the same value.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public static function render( array $attributes ): string {
		$settled   = Renderer::normalise( $attributes );
		$overrides = self::instance_tokens( $settled );

		$wrapper = get_block_wrapper_attributes(
			'' !== $overrides ? array( 'style' => $overrides ) : array()
		);

		return Renderer::render( $settled, $wrapper );
	}

	/**
	 * Turn per-block colour, font and arrangement overrides into inline custom
	 * properties.
	 *
	 * These sit above the settings screen in the cascade, so one block can look
	 * different without changing the site-wide defaults.
	 *
	 * @param array<string,mixed> $attributes Settled block attributes.
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
		/*
		 * Unlike the scales above, columns is always written. $attributes has
		 * already been through Renderer::normalise() by the time it reaches
		 * here, which defaults an unset column count from the Design screen -
		 * so there is no "unset" state left to distinguish, and the value that
		 * decided how many testimonials PHP fits per slider page needs to be
		 * the same value CSS uses to size the grid track. Leaving this
		 * conditional, as it was, meant a block that had never had its Columns
		 * control touched wrote nothing here at all, and the grid silently
		 * fell back to a number hardcoded in the stylesheet instead of
		 * whatever the Design screen said - the direct cause of a site-wide
		 * column default appearing to do nothing.
		 */
		$out .= '--dvdm-columns:' . max( 1, min( 6, (int) $attributes['columns'] ) ) . ';';
		if ( isset( $attributes['clampLines'] ) && is_numeric( $attributes['clampLines'] ) ) {
			$out .= '--dvdm-clamp-lines:' . max( 2, min( 20, (int) $attributes['clampLines'] ) ) . ';';
		}

		return $out;
	}
}
