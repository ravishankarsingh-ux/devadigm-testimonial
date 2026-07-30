<?php
/**
 * Settings screen: colours, typography, quotation marks and icons.
 *
 * Every visual value has three possible sources, in this order of precedence:
 * a block attribute, this settings screen, then the active theme. Leaving a
 * field on "Inherit from theme" keeps the plugin invisible to the theme, which
 * is what makes it portable between client sites.
 *
 * @package Devadigm\Testimonials
 */

declare( strict_types = 1 );

namespace Devadigm\Testimonials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the option array and the admin screen that edits it.
 */
final class Settings {

	/**
	 * Quotation-mark treatments shipped with the plugin.
	 *
	 * @return array<string,string>
	 */
	public static function mark_styles(): array {
		return array(
			'ledger'    => __( 'Ledger - big mark hanging at the left', 'devadigm-testimonials' ),
			'watermark' => __( 'Watermark - huge mark bled off the corner', 'devadigm-testimonials' ),
			'badge'     => __( 'Hairline badge - small mark in a ring', 'devadigm-testimonials' ),
			'dropcap'   => __( 'Drop-cap fusion - mark set with the first letter', 'devadigm-testimonials' ),
			'bracket'   => __( 'Bracket rules - two rules, no glyph', 'devadigm-testimonials' ),
			'slab'      => __( 'Filled slab - knocked-out mark on a solid block', 'devadigm-testimonials' ),
			'none'      => __( 'None', 'devadigm-testimonials' ),
		);
	}

	/**
	 * Display layouts shipped with the plugin.
	 *
	 * @return array<string,string>
	 */
	public static function layouts(): array {
		return array(
			'spotlight' => __( 'Spotlight - one large testimonial', 'devadigm-testimonials' ),
			'grid'      => __( 'Grid - columns side by side', 'devadigm-testimonials' ),
			'marquee'   => __( 'Marquee - continuous drift', 'devadigm-testimonials' ),
			'inline'    => __( 'Inline proof - single pull-quote', 'devadigm-testimonials' ),
		);
	}

	/**
	 * Layout names from version 1.0 and what they mean now.
	 *
	 * Row and Wall were never really two layouts: they differed only in whether
	 * cards share a baseline grid or pack by height, which is one switch. And
	 * Slideshow was a layout that hardcoded "one at a time", which is why a
	 * two-column slider was impossible to ask for. Sliding is a property of a
	 * layout, not a layout of its own.
	 *
	 * Content saved against the old names keeps rendering, so existing pages do
	 * not need editing.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function legacy_layouts(): array {
		return array(
			'row'       => array( 'layout' => 'grid' ),
			'wall'      => array(
				'layout'  => 'grid',
				'masonry' => true,
			),
			'slideshow' => array(
				'layout'        => 'spotlight',
				'slider'        => true,
				'slidesPerView' => 1,
			),
		);
	}

	/**
	 * Ready-made quotation glyph pairs. "custom" falls through to a free text field.
	 *
	 * @return array<string,string>
	 */
	public static function glyph_presets(): array {
		return array(
			'curly'    => '“',
			'straight' => '"',
			'guillemet' => '«',
			'german'   => '„',
			'heavy'    => '❝',
			'single'   => '‘',
		);
	}

	/**
	 * Star icon sets, as full and empty glyph pairs.
	 *
	 * @return array<string,array{0:string,1:string,label:string}>
	 */
	public static function star_sets(): array {
		return array(
			'star'    => array( '★', '☆', 'label' => __( 'Stars', 'devadigm-testimonials' ) ),
			'sparkle' => array( '✦', '✧', 'label' => __( 'Sparkles', 'devadigm-testimonials' ) ),
			'dot'     => array( '●', '○', 'label' => __( 'Dots', 'devadigm-testimonials' ) ),
			'square'  => array( '◆', '◇', 'label' => __( 'Diamonds', 'devadigm-testimonials' ) ),
		);
	}

	/**
	 * Default value for every setting.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			// Layout.
			'default_layout'   => 'spotlight',
			'default_mark'     => 'ledger',
			'default_columns'  => '3',

			// Slider.
			'slider_loop'      => true,
			'slider_autoplay'  => '0',

			// Long quotes.
			'read_more'        => true,
			'excerpt_words'    => '28',
			'clamp_lines'      => '6',

			// Colours. Empty string means "inherit from the theme".
			'quote_color'      => '',
			'name_color'       => '',
			'meta_color'       => '',
			'accent_color'     => '',
			'accent_contrast'  => '',
			'accent_text'      => '',
			'star_color'       => '',
			'surface_color'    => '',
			'border_color'     => '',

			// Typography.
			'quote_font'       => '',
			'body_font'        => '',
			'quote_scale'      => '1',
			'quote_weight'     => '',
			'quote_italic'     => false,

			// Quotation mark.
			'mark_glyph_set'   => 'curly',
			'mark_glyph_custom' => '',
			'mark_opacity'     => '1',
			'mark_scale'       => '1',
			'mark_color'       => '',
			'mark_auto_locale' => true,

			// Icons.
			'star_set'         => 'star',
			'star_custom_full' => '',
			'star_custom_empty' => '',
			'avatar_shape'     => 'circle',
			'arrow_prev'       => '←',
			'arrow_next'       => '→',

			// Advanced.
			'enable_schema'    => false,
			'custom_css'       => '',
		);
	}

	/**
	 * Read the stored settings merged over the defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function all(): array {
		$stored = get_option( OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( self::defaults(), $stored );
	}

	/**
	 * Read a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Value to use when the setting is unset.
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$all = self::all();
		return $all[ $key ] ?? $default;
	}

	/**
	 * Hook registration into WordPress.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_page' ) );
		add_action( 'admin_init', array( self::class, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Add the design screen beneath the Testimonials menu.
	 */
	public static function add_page(): void {
		add_submenu_page(
			'edit.php?post_type=' . POST_TYPE,
			__( 'Testimonial design', 'devadigm-testimonials' ),
			__( 'Design', 'devadigm-testimonials' ),
			'manage_options',
			'dvdm-testimonials-design',
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Register the option with the Settings API.
	 */
	public static function register(): void {
		register_setting(
			'dvdm_testimonials',
			OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Load the colour picker on our screen only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue( string $hook ): void {
		if ( ! str_contains( $hook, 'dvdm-testimonials-design' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'dvdm-testimonials-admin',
			PLUGIN_URL . 'assets/admin.js',
			array( 'wp-color-picker', 'jquery' ),
			VERSION,
			true
		);
		wp_enqueue_style(
			'dvdm-testimonials-admin',
			PLUGIN_URL . 'assets/admin.css',
			array(),
			VERSION
		);
	}

	/**
	 * Clean every submitted value before it reaches the database.
	 *
	 * @param mixed $input Raw submitted array.
	 * @return array<string,mixed>
	 */
	public static function sanitize( mixed $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$out      = array();

		$colour_keys = array( 'quote_color', 'name_color', 'meta_color', 'accent_color', 'accent_contrast', 'accent_text', 'star_color', 'surface_color', 'border_color', 'mark_color' );
		$font_keys   = array( 'quote_font', 'body_font' );

		foreach ( $defaults as $key => $default ) {
			$value = $input[ $key ] ?? null;

			if ( in_array( $key, $colour_keys, true ) ) {
				$out[ $key ] = self::sanitize_colour( (string) $value );
				continue;
			}

			if ( in_array( $key, $font_keys, true ) ) {
				$out[ $key ] = self::sanitize_font( (string) $value );
				continue;
			}

			if ( is_bool( $default ) ) {
				$out[ $key ] = ! empty( $value );
				continue;
			}

			$out[ $key ] = match ( $key ) {
				'default_layout'  => array_key_exists( (string) $value, self::layouts() ) ? (string) $value : $defaults[ $key ],
				'default_mark'    => array_key_exists( (string) $value, self::mark_styles() ) ? (string) $value : $defaults[ $key ],
				'mark_glyph_set'  => ( 'custom' === $value || array_key_exists( (string) $value, self::glyph_presets() ) ) ? (string) $value : $defaults[ $key ],
				'star_set'        => ( 'custom' === $value || array_key_exists( (string) $value, self::star_sets() ) ) ? (string) $value : $defaults[ $key ],
				'avatar_shape'    => in_array( $value, array( 'circle', 'rounded', 'square' ), true ) ? (string) $value : $defaults[ $key ],
				'quote_scale', 'mark_scale' => (string) self::clamp_float( $value, 0.5, 3.0, 1.0 ),
				'default_columns' => (string) (int) self::clamp_float( $value, 1, 6, 3 ),
				'slider_autoplay' => (string) (int) self::clamp_float( $value, 0, 30, 0 ),
				'excerpt_words'   => (string) (int) self::clamp_float( $value, 8, 120, 28 ),
				'clamp_lines'     => (string) (int) self::clamp_float( $value, 2, 20, 6 ),
				'mark_opacity'    => (string) self::clamp_float( $value, 0.0, 1.0, 1.0 ),
				'quote_weight'    => '' === (string) $value ? '' : (string) self::clamp_float( $value, 100, 900, 400 ),
				'custom_css'      => wp_strip_all_tags( (string) $value ),
				default           => sanitize_text_field( (string) $value ),
			};
		}

		return $out;
	}

	/**
	 * Accept an empty value, a theme preset reference or a hex colour.
	 *
	 * @param string $value Raw value.
	 */
	private static function sanitize_colour( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( str_starts_with( $value, 'preset:' ) ) {
			$slug = sanitize_key( substr( $value, 7 ) );
			return '' === $slug ? '' : 'preset:' . $slug;
		}
		$hex = sanitize_hex_color( $value );
		return is_string( $hex ) ? $hex : '';
	}

	/**
	 * Accept an empty value, a theme font preset or a custom font stack.
	 *
	 * @param string $value Raw value.
	 */
	private static function sanitize_font( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( str_starts_with( $value, 'preset:' ) ) {
			$slug = sanitize_key( substr( $value, 7 ) );
			return '' === $slug ? '' : 'preset:' . $slug;
		}
		/*
		 * A custom stack. Quotes are stripped rather than allowed through,
		 * because an unbalanced one would leave an open string and swallow the
		 * rest of the declaration. Families are re-quoted below where needed.
		 */
		$clean = preg_replace( '/[^a-zA-Z0-9 ,\-_]/', '', $value );
		if ( ! is_string( $clean ) ) {
			return '';
		}

		$families = array();
		foreach ( explode( ',', $clean ) as $family ) {
			$family = trim( preg_replace( '/\s+/', ' ', $family ) ?? '' );
			if ( '' === $family ) {
				continue;
			}
			// A family name containing spaces has to be quoted to be valid CSS.
			$families[] = str_contains( $family, ' ' ) ? '"' . $family . '"' : $family;
		}

		return implode( ', ', $families );
	}

	/**
	 * Clamp a numeric value into a range.
	 *
	 * @param mixed $value Raw value.
	 * @param float $min   Lower bound.
	 * @param float $max   Upper bound.
	 * @param float $fallback Value used when input is not numeric.
	 */
	private static function clamp_float( mixed $value, float $min, float $max, float $fallback ): float {
		if ( ! is_numeric( $value ) ) {
			return $fallback;
		}
		return max( $min, min( $max, (float) $value ) );
	}

	/**
	 * Colour presets published by the active theme.
	 *
	 * @return array<int,array{slug:string,name:string,color:string}>
	 */
	public static function theme_palette(): array {
		$palette = wp_get_global_settings( array( 'color', 'palette' ) );
		$out     = array();
		foreach ( array( 'theme', 'custom', 'default' ) as $origin ) {
			foreach ( (array) ( $palette[ $origin ] ?? array() ) as $entry ) {
				if ( isset( $entry['slug'], $entry['color'] ) ) {
					$out[ $entry['slug'] ] = array(
						'slug'  => (string) $entry['slug'],
						'name'  => (string) ( $entry['name'] ?? $entry['slug'] ),
						'color' => (string) $entry['color'],
					);
				}
			}
		}
		return array_values( $out );
	}

	/**
	 * Font families published by the active theme.
	 *
	 * @return array<int,array{slug:string,name:string}>
	 */
	public static function theme_fonts(): array {
		$families = wp_get_global_settings( array( 'typography', 'fontFamilies' ) );
		$out      = array();
		foreach ( array( 'theme', 'custom', 'default' ) as $origin ) {
			foreach ( (array) ( $families[ $origin ] ?? array() ) as $entry ) {
				if ( isset( $entry['slug'] ) ) {
					$out[ $entry['slug'] ] = array(
						'slug' => (string) $entry['slug'],
						'name' => (string) ( $entry['name'] ?? $entry['slug'] ),
					);
				}
			}
		}
		return array_values( $out );
	}

	/**
	 * Render the design screen.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::all();
		?>
		<div class="wrap dvdm-settings">
			<h1><?php esc_html_e( 'Testimonial design', 'devadigm-testimonials' ); ?></h1>
			<p class="dvdm-version">
				<?php
				printf(
					/* translators: %s: plugin version number. */
					esc_html__( 'Devadigm Testimonials version %s', 'devadigm-testimonials' ),
					esc_html( VERSION )
				);
				?>
			</p>
			<p class="description dvdm-intro">
				<?php esc_html_e( 'Every field below defaults to inheriting from your theme. Override only what you need. Individual blocks can override these values again in the editor.', 'devadigm-testimonials' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'dvdm_testimonials' ); ?>

				<h2 class="title"><?php esc_html_e( 'Defaults', 'devadigm-testimonials' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::select_row( __( 'Default layout', 'devadigm-testimonials' ), 'default_layout', self::layouts(), (string) $s['default_layout'] );
					self::select_row( __( 'Default quotation mark', 'devadigm-testimonials' ), 'default_mark', self::mark_styles(), (string) $s['default_mark'] );
					self::number_row( __( 'Default columns', 'devadigm-testimonials' ), 'default_columns', (string) $s['default_columns'], '1', '6', '1', __( 'Used by the Grid layout. Columns drop automatically when there is not room for them.', 'devadigm-testimonials' ) );
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Slider', 'devadigm-testimonials' ); ?></h2>
				<p class="description dvdm-note">
					<?php esc_html_e( 'Sliding is a property of a layout, not a layout of its own. Turn it on per block for Spotlight or Grid, and set how many testimonials are visible at once. These are the site-wide defaults for how a slider behaves.', 'devadigm-testimonials' ); ?>
				</p>
				<table class="form-table" role="presentation">
					<?php
					self::checkbox_row(
						__( 'Loop back to the start', 'devadigm-testimonials' ),
						'slider_loop',
						(bool) $s['slider_loop'],
						__( 'With this off, the arrows stop at the first and last slide.', 'devadigm-testimonials' )
					);
					self::number_row(
						__( 'Advance automatically after', 'devadigm-testimonials' ),
						'slider_autoplay',
						(string) $s['slider_autoplay'],
						'0',
						'30',
						'1',
						__( 'Seconds. Zero switches autoplay off, which is the default. When on, a pause button appears, autoplay stops as soon as anyone interacts, and it never starts for visitors who ask for reduced motion.', 'devadigm-testimonials' )
					);
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Long quotes', 'devadigm-testimonials' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::checkbox_row(
						__( 'Shorten long quotes in cards', 'devadigm-testimonials' ),
						'read_more',
						(bool) $s['read_more'],
						__( 'Applies to the Grid and Marquee layouts, where uneven quote lengths make cards ragged. Long quotes are trimmed to a set number of lines with a Read more link that opens the full quote. Short quotes are left alone.', 'devadigm-testimonials' )
					);
					self::number_row(
						__( 'Treat as long past', 'devadigm-testimonials' ),
						'excerpt_words',
						(string) $s['excerpt_words'],
						'8',
						'120',
						'1',
						__( 'Words. Quotes shorter than this never get a Read more link.', 'devadigm-testimonials' )
					);
					self::number_row(
						__( 'Lines shown before trimming', 'devadigm-testimonials' ),
						'clamp_lines',
						(string) $s['clamp_lines'],
						'2',
						'20',
						'1'
					);
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Colours', 'devadigm-testimonials' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::colour_row( __( 'Quote text', 'devadigm-testimonials' ), 'quote_color', (string) $s['quote_color'] );
					self::colour_row( __( 'Author name', 'devadigm-testimonials' ), 'name_color', (string) $s['name_color'] );
					self::colour_row( __( 'Role and company', 'devadigm-testimonials' ), 'meta_color', (string) $s['meta_color'] );
					self::colour_row( __( 'Accent (marks and rules)', 'devadigm-testimonials' ), 'accent_color', (string) $s['accent_color'] );
					self::colour_row( __( 'Accent contrast (knockout)', 'devadigm-testimonials' ), 'accent_contrast', (string) $s['accent_contrast'] );
					self::colour_row(
						__( 'Accent text (result metrics)', 'devadigm-testimonials' ),
						'accent_text',
						(string) $s['accent_text'],
						__( 'Kept separate from the accent on purpose. A colour that looks right as a mark or a fill is often too light to use as text - metallics and pastels usually are. Inherits the body colour until you set one.', 'devadigm-testimonials' )
					);
					self::colour_row( __( 'Rating icons', 'devadigm-testimonials' ), 'star_color', (string) $s['star_color'] );
					self::colour_row( __( 'Card background', 'devadigm-testimonials' ), 'surface_color', (string) $s['surface_color'] );
					self::colour_row( __( 'Borders', 'devadigm-testimonials' ), 'border_color', (string) $s['border_color'] );
					?>
				</table>
				<p class="description dvdm-note">
					<?php esc_html_e( 'Check any custom colour against its background before shipping. Body text needs 4.5:1 contrast, large display text needs 3:1. Metallic and pastel accents commonly fail on light backgrounds.', 'devadigm-testimonials' ); ?>
				</p>

				<h2 class="title"><?php esc_html_e( 'Typography', 'devadigm-testimonials' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::font_row( __( 'Quote typeface', 'devadigm-testimonials' ), 'quote_font', (string) $s['quote_font'] );
					self::font_row( __( 'Name and meta typeface', 'devadigm-testimonials' ), 'body_font', (string) $s['body_font'] );
					self::number_row( __( 'Quote size scale', 'devadigm-testimonials' ), 'quote_scale', (string) $s['quote_scale'], '0.5', '3', '0.05', __( 'Multiplies the quote size. 1 keeps the built-in scale.', 'devadigm-testimonials' ) );
					self::number_row( __( 'Quote weight', 'devadigm-testimonials' ), 'quote_weight', (string) $s['quote_weight'], '100', '900', '50', __( 'Leave empty to inherit the typeface default.', 'devadigm-testimonials' ) );
					self::checkbox_row( __( 'Italic quotes', 'devadigm-testimonials' ), 'quote_italic', (bool) $s['quote_italic'] );
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Quotation mark', 'devadigm-testimonials' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$glyphs = array();
					foreach ( self::glyph_presets() as $key => $glyph ) {
						$glyphs[ $key ] = $glyph . '  ' . $key;
					}
					$glyphs['custom'] = __( 'Custom character', 'devadigm-testimonials' );
					self::select_row( __( 'Glyph', 'devadigm-testimonials' ), 'mark_glyph_set', $glyphs, (string) $s['mark_glyph_set'] );
					self::text_row( __( 'Custom glyph', 'devadigm-testimonials' ), 'mark_glyph_custom', (string) $s['mark_glyph_custom'], __( 'Used when Glyph is set to Custom character.', 'devadigm-testimonials' ) );
					self::checkbox_row( __( 'Pick the glyph from the site language', 'devadigm-testimonials' ), 'mark_auto_locale', (bool) $s['mark_auto_locale'], __( 'German opens with a low quote, French uses guillemets. Overrides the glyph choice above.', 'devadigm-testimonials' ) );
					self::colour_row( __( 'Mark colour', 'devadigm-testimonials' ), 'mark_color', (string) $s['mark_color'], __( 'Falls back to the accent colour.', 'devadigm-testimonials' ) );
					self::number_row( __( 'Mark size scale', 'devadigm-testimonials' ), 'mark_scale', (string) $s['mark_scale'], '0.5', '3', '0.05' );
					self::number_row( __( 'Mark opacity', 'devadigm-testimonials' ), 'mark_opacity', (string) $s['mark_opacity'], '0', '1', '0.01' );
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Icons', 'devadigm-testimonials' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					$sets = array();
					foreach ( self::star_sets() as $key => $set ) {
						$sets[ $key ] = $set[0] . $set[1] . '  ' . $set['label'];
					}
					$sets['custom'] = __( 'Custom characters', 'devadigm-testimonials' );
					self::select_row( __( 'Rating icons', 'devadigm-testimonials' ), 'star_set', $sets, (string) $s['star_set'] );
					self::text_row( __( 'Custom filled icon', 'devadigm-testimonials' ), 'star_custom_full', (string) $s['star_custom_full'] );
					self::text_row( __( 'Custom empty icon', 'devadigm-testimonials' ), 'star_custom_empty', (string) $s['star_custom_empty'] );
					self::select_row(
						__( 'Avatar shape', 'devadigm-testimonials' ),
						'avatar_shape',
						array(
							'circle'  => __( 'Circle', 'devadigm-testimonials' ),
							'rounded' => __( 'Rounded', 'devadigm-testimonials' ),
							'square'  => __( 'Square', 'devadigm-testimonials' ),
						),
						(string) $s['avatar_shape']
					);
					self::text_row( __( 'Previous arrow', 'devadigm-testimonials' ), 'arrow_prev', (string) $s['arrow_prev'] );
					self::text_row( __( 'Next arrow', 'devadigm-testimonials' ), 'arrow_next', (string) $s['arrow_next'] );
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Advanced', 'devadigm-testimonials' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::checkbox_row(
						__( 'Output review schema', 'devadigm-testimonials' ),
						'enable_schema',
						(bool) $s['enable_schema'],
						__( 'Off by default on purpose. Google treats review markup on your own business pages as self-serving and will not show stars for it, and marking up imported third-party reviews as first-party breaks their policy. Turn this on only for pages reviewing a specific product or service.', 'devadigm-testimonials' )
					);
					?>
					<tr>
						<th scope="row"><label for="dvdm_custom_css"><?php esc_html_e( 'Custom CSS', 'devadigm-testimonials' ); ?></label></th>
						<td>
							<textarea id="dvdm_custom_css" name="<?php echo esc_attr( OPTION_KEY ); ?>[custom_css]" rows="6" class="large-text code"><?php echo esc_textarea( (string) $s['custom_css'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Applied after the plugin stylesheet. Scope rules to .dvdm-t so they cannot leak into the rest of the page.', 'devadigm-testimonials' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a select row.
	 *
	 * @param string               $label   Field label.
	 * @param string               $key     Setting key.
	 * @param array<string,string> $options Choices.
	 * @param string               $current Current value.
	 */
	private static function select_row( string $label, string $key, array $options, string $current ): void {
		?>
		<tr>
			<th scope="row"><label for="dvdm_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<select id="dvdm_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( OPTION_KEY . '[' . $key . ']' ); ?>">
					<?php foreach ( $options as $value => $text ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $text ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a colour row: inherit, a theme preset, or a custom hex value.
	 *
	 * @param string $label   Field label.
	 * @param string $key     Setting key.
	 * @param string $current Current value.
	 * @param string $help    Optional description.
	 */
	private static function colour_row( string $label, string $key, string $current, string $help = '' ): void {
		$is_preset = str_starts_with( $current, 'preset:' );
		$is_custom = ! $is_preset && '' !== $current;
		$name      = OPTION_KEY . '[' . $key . ']';
		?>
		<tr>
			<th scope="row"><label for="dvdm_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td class="dvdm-colour-cell">
				<select id="dvdm_<?php echo esc_attr( $key ); ?>" class="dvdm-colour-source" data-target="dvdm_<?php echo esc_attr( $key ); ?>_custom">
					<option value="" <?php selected( '' === $current ); ?>><?php esc_html_e( 'Inherit from theme', 'devadigm-testimonials' ); ?></option>
					<?php foreach ( self::theme_palette() as $entry ) : ?>
						<option value="preset:<?php echo esc_attr( $entry['slug'] ); ?>" <?php selected( $current, 'preset:' . $entry['slug'] ); ?>>
							<?php echo esc_html( $entry['name'] . ' (' . $entry['color'] . ')' ); ?>
						</option>
					<?php endforeach; ?>
					<option value="__custom" <?php selected( $is_custom ); ?>><?php esc_html_e( 'Custom colour', 'devadigm-testimonials' ); ?></option>
				</select>
				<input
					type="text"
					id="dvdm_<?php echo esc_attr( $key ); ?>_custom"
					class="dvdm-colour-custom"
					value="<?php echo esc_attr( $is_custom ? $current : '' ); ?>"
					<?php echo $is_custom ? '' : 'disabled'; ?>
				/>
				<input type="hidden" class="dvdm-colour-value" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" />
				<?php if ( '' !== $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a font row: inherit, a theme preset, or a custom stack.
	 *
	 * @param string $label   Field label.
	 * @param string $key     Setting key.
	 * @param string $current Current value.
	 */
	private static function font_row( string $label, string $key, string $current ): void {
		$is_preset = str_starts_with( $current, 'preset:' );
		$is_custom = ! $is_preset && '' !== $current;
		$name      = OPTION_KEY . '[' . $key . ']';
		?>
		<tr>
			<th scope="row"><label for="dvdm_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td class="dvdm-font-cell">
				<select id="dvdm_<?php echo esc_attr( $key ); ?>" class="dvdm-font-source" data-target="dvdm_<?php echo esc_attr( $key ); ?>_custom">
					<option value="" <?php selected( '' === $current ); ?>><?php esc_html_e( 'Inherit from theme', 'devadigm-testimonials' ); ?></option>
					<?php foreach ( self::theme_fonts() as $entry ) : ?>
						<option value="preset:<?php echo esc_attr( $entry['slug'] ); ?>" <?php selected( $current, 'preset:' . $entry['slug'] ); ?>>
							<?php echo esc_html( $entry['name'] ); ?>
						</option>
					<?php endforeach; ?>
					<option value="__custom" <?php selected( $is_custom ); ?>><?php esc_html_e( 'Custom font stack', 'devadigm-testimonials' ); ?></option>
				</select>
				<input
					type="text"
					id="dvdm_<?php echo esc_attr( $key ); ?>_custom"
					class="dvdm-font-custom regular-text"
					placeholder="Georgia, serif"
					value="<?php echo esc_attr( $is_custom ? $current : '' ); ?>"
					<?php echo $is_custom ? '' : 'disabled'; ?>
				/>
				<input type="hidden" class="dvdm-font-value" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current ); ?>" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a plain text row.
	 *
	 * @param string $label   Field label.
	 * @param string $key     Setting key.
	 * @param string $current Current value.
	 * @param string $help    Optional description.
	 */
	private static function text_row( string $label, string $key, string $current, string $help = '' ): void {
		?>
		<tr>
			<th scope="row"><label for="dvdm_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="text" id="dvdm_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( OPTION_KEY . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $current ); ?>" class="regular-text" />
				<?php if ( '' !== $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a numeric row.
	 *
	 * @param string $label   Field label.
	 * @param string $key     Setting key.
	 * @param string $current Current value.
	 * @param string $min     Minimum.
	 * @param string $max     Maximum.
	 * @param string $step    Step.
	 * @param string $help    Optional description.
	 */
	private static function number_row( string $label, string $key, string $current, string $min, string $max, string $step, string $help = '' ): void {
		?>
		<tr>
			<th scope="row"><label for="dvdm_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="number" id="dvdm_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( OPTION_KEY . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $current ); ?>" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>" class="small-text" />
				<?php if ( '' !== $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a checkbox row.
	 *
	 * @param string $label   Field label.
	 * @param string $key     Setting key.
	 * @param bool   $current Current value.
	 * @param string $help    Optional description.
	 */
	private static function checkbox_row( string $label, string $key, bool $current, string $help = '' ): void {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<label>
					<input type="checkbox" id="dvdm_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( OPTION_KEY . '[' . $key . ']' ); ?>" value="1" <?php checked( $current ); ?> />
					<?php esc_html_e( 'Enabled', 'devadigm-testimonials' ); ?>
				</label>
				<?php if ( '' !== $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}
