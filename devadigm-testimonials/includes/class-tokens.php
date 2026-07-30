<?php
/**
 * Turns settings into CSS custom properties.
 *
 * Nothing here writes a colour or a typeface directly into markup. Every value
 * becomes a custom property on the block wrapper, so a theme, this settings
 * screen and an individual block can all take part in the same cascade.
 *
 * @package Devadigm\Testimonials
 */

declare( strict_types = 1 );

namespace Devadigm\Testimonials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the custom property set used by the front end.
 */
final class Tokens {

	/**
	 * Hook registration into WordPress.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_inline' ), 20 );
	}

	/**
	 * Map a stored setting to a CSS value.
	 *
	 * An empty setting returns the supplied fallback, which is normally either
	 * `inherit`, `currentColor`, or a reference to a theme preset.
	 *
	 * @param string $value    Stored value: '', 'preset:slug', or a literal.
	 * @param string $kind     Either 'color' or 'font-family'.
	 * @param string $fallback CSS value used when the setting is empty.
	 */
	public static function resolve( string $value, string $kind, string $fallback ): string {
		if ( '' === $value ) {
			return $fallback;
		}
		if ( str_starts_with( $value, 'preset:' ) ) {
			$slug = substr( $value, 7 );
			$var  = 'color' === $kind
				? '--wp--preset--color--' . $slug
				: '--wp--preset--font-family--' . $slug;
			return sprintf( 'var(%s, %s)', $var, $fallback );
		}
		return $value;
	}

	/**
	 * The opening quotation glyph, optionally chosen from the site language.
	 */
	public static function glyph(): string {
		$settings = Settings::all();

		if ( ! empty( $settings['mark_auto_locale'] ) ) {
			$locale = strtolower( (string) get_locale() );
			$lang   = substr( $locale, 0, 2 );
			$by_lang = array(
				'de' => '„',
				'fr' => '«',
				'es' => '«',
				'it' => '«',
				'ru' => '«',
				'pl' => '„',
				'ja' => '「',
				'zh' => '「',
			);
			if ( isset( $by_lang[ $lang ] ) ) {
				return $by_lang[ $lang ];
			}
		}

		$set = (string) $settings['mark_glyph_set'];
		if ( 'custom' === $set ) {
			$custom = trim( (string) $settings['mark_glyph_custom'] );
			return '' !== $custom ? mb_substr( $custom, 0, 2 ) : '“';
		}

		$presets = Settings::glyph_presets();
		return $presets[ $set ] ?? '“';
	}

	/**
	 * The filled and empty rating icons.
	 *
	 * @return array{0:string,1:string}
	 */
	public static function star_glyphs(): array {
		$settings = Settings::all();
		$set      = (string) $settings['star_set'];

		if ( 'custom' === $set ) {
			$full  = trim( (string) $settings['star_custom_full'] );
			$empty = trim( (string) $settings['star_custom_empty'] );
			return array(
				'' !== $full ? mb_substr( $full, 0, 2 ) : '★',
				'' !== $empty ? mb_substr( $empty, 0, 2 ) : '☆',
			);
		}

		$sets = Settings::star_sets();
		if ( isset( $sets[ $set ] ) ) {
			return array( $sets[ $set ][0], $sets[ $set ][1] );
		}
		return array( '★', '☆' );
	}

	/**
	 * Build the full custom property map.
	 *
	 * @return array<string,string>
	 */
	public static function map(): array {
		$s = Settings::all();

		$accent = self::resolve( (string) $s['accent_color'], 'color', 'currentColor' );

		$radius = match ( (string) $s['avatar_shape'] ) {
			'square'  => '0',
			'rounded' => '.35em',
			default   => '50%',
		};

		$tokens = array(
			'--dvdm-quote-color'    => self::resolve( (string) $s['quote_color'], 'color', 'inherit' ),
			'--dvdm-name-color'     => self::resolve( (string) $s['name_color'], 'color', 'inherit' ),
			'--dvdm-meta-color'     => self::resolve( (string) $s['meta_color'], 'color', 'inherit' ),
			'--dvdm-accent'         => $accent,
			'--dvdm-accent-contrast' => self::resolve( (string) $s['accent_contrast'], 'color', 'canvas' ),
			'--dvdm-accent-text'    => self::resolve( (string) $s['accent_text'], 'color', 'inherit' ),
			'--dvdm-star-color'     => self::resolve( (string) $s['star_color'], 'color', 'var(--dvdm-accent)' ),
			'--dvdm-surface'        => self::resolve( (string) $s['surface_color'], 'color', 'transparent' ),
			'--dvdm-border'         => self::resolve( (string) $s['border_color'], 'color', 'currentColor' ),
			'--dvdm-mark-color'     => self::resolve( (string) $s['mark_color'], 'color', 'var(--dvdm-accent)' ),
			'--dvdm-quote-font'     => self::resolve( (string) $s['quote_font'], 'font-family', 'inherit' ),
			'--dvdm-body-font'      => self::resolve( (string) $s['body_font'], 'font-family', 'inherit' ),
			'--dvdm-quote-scale'    => (string) $s['quote_scale'],
			'--dvdm-quote-style'    => ! empty( $s['quote_italic'] ) ? 'italic' : 'normal',
			'--dvdm-mark-scale'     => (string) $s['mark_scale'],
			'--dvdm-mark-opacity'   => (string) $s['mark_opacity'],
			'--dvdm-avatar-radius'  => $radius,
			'--dvdm-clamp-lines'    => (string) (int) $s['clamp_lines'],
			'--dvdm-glyph'          => '"' . self::escape_css_string( self::glyph() ) . '"',
		);

		if ( '' !== (string) $s['quote_weight'] ) {
			$tokens['--dvdm-quote-weight'] = (string) $s['quote_weight'];
		}

		/**
		 * Filter the custom property map before it is printed.
		 *
		 * Useful for shipping per-client preset packs without touching settings.
		 *
		 * @param array<string,string> $tokens Property name to CSS value.
		 */
		return apply_filters( 'devadigm_testimonials_tokens', $tokens );
	}

	/**
	 * Render the map as a declaration list for use in a style attribute.
	 */
	public static function inline_style(): string {
		$out = '';
		foreach ( self::map() as $property => $value ) {
			$out .= $property . ':' . $value . ';';
		}
		return $out;
	}

	/**
	 * Escape a string for use inside a CSS content value.
	 *
	 * @param string $value Raw string.
	 */
	public static function escape_css_string( string $value ): string {
		return str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $value );
	}

	/**
	 * Attach the token block and any custom CSS to the plugin stylesheet.
	 *
	 * The stylesheet is registered by the block, so this only runs when a
	 * testimonial block is actually on the page.
	 */
	public static function enqueue_inline(): void {
		$custom = trim( (string) Settings::get( 'custom_css', '' ) );

		$css = ':root{' . self::inline_style() . '}';
		if ( '' !== $custom ) {
			$css .= "\n" . $custom;
		}

		wp_register_style( 'dvdm-testimonials-tokens', false, array(), VERSION );
		wp_enqueue_style( 'dvdm-testimonials-tokens' );
		wp_add_inline_style( 'dvdm-testimonials-tokens', $css );
	}
}
