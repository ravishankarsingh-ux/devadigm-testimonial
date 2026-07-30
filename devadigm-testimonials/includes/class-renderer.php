<?php
/**
 * Markup for every layout and quotation-mark treatment.
 *
 * @package Devadigm\Testimonials
 */

declare( strict_types = 1 );

namespace Devadigm\Testimonials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the front-end markup for a set of testimonials.
 */
final class Renderer {

	/**
	 * Run the query described by a block's attributes.
	 *
	 * @param array<string,mixed> $attrs Block attributes.
	 * @return array<int,\WP_Post>
	 */
	public static function query( array $attrs ): array {
		$args = array(
			'post_type'           => POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, (int) ( $attrs['count'] ?? 3 ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		$tax_query = array();
		if ( ! empty( $attrs['service'] ) ) {
			$tax_query[] = array(
				'taxonomy' => TAX_SERVICE,
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', (array) $attrs['service'] ),
			);
		}
		if ( ! empty( $attrs['source'] ) ) {
			$tax_query[] = array(
				'taxonomy' => TAX_SOURCE,
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', (array) $attrs['source'] ),
			);
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		if ( array() !== $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$meta_query = array();
		if ( ! empty( $attrs['featuredOnly'] ) ) {
			$meta_query[] = array(
				'key'     => 'dvdm_featured',
				'value'   => '1',
				'compare' => '=',
			);
		}
		if ( ! empty( $attrs['minRating'] ) ) {
			$meta_query[] = array(
				'key'     => 'dvdm_rating',
				'value'   => (float) $attrs['minRating'],
				'type'    => 'DECIMAL(3,1)',
				'compare' => '>=',
			);
		}
		if ( array() !== $meta_query ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		switch ( (string) ( $attrs['order'] ?? 'newest' ) ) {
			case 'rating':
				$args['meta_key'] = 'dvdm_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;
			case 'random':
				$args['orderby'] = 'rand';
				break;
			case 'manual':
				$args['orderby'] = 'menu_order';
				$args['order']   = 'ASC';
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}

		$query = new \WP_Query( $args );
		return $query->posts;
	}

	/**
	 * Render a complete block.
	 *
	 * @param array<string,mixed> $attrs Block attributes.
	 * @param string              $wrapper_attributes Attributes from get_block_wrapper_attributes().
	 */
	public static function render( array $attrs, string $wrapper_attributes ): string {
		$attrs = self::normalise( $attrs );
		$posts = self::query( $attrs );
		if ( array() === $posts ) {
			return '';
		}

		$layout = (string) $attrs['layout'];
		$mark   = (string) $attrs['markStyle'];

		/*
		 * Columns and sliding are arrangement properties, independent of which
		 * card style was picked, so they apply to any layout except Marquee.
		 * Marquee is already a continuous strip with no discrete page to slide
		 * between, so it keeps its own dedicated behaviour.
		 */
		$slider = ! empty( $attrs['slider'] ) && 'marquee' !== $layout;

		$items = array();
		foreach ( $posts as $post ) {
			$items[] = self::item( $post, $mark, $layout, $attrs );
		}

		if ( 'marquee' === $layout ) {
			return self::wrap_marquee( $items, $wrapper_attributes, $attrs );
		}
		if ( $slider ) {
			return self::wrap_slider( $items, $wrapper_attributes, $attrs );
		}
		return self::wrap_plain( $items, $wrapper_attributes, $attrs );
	}

	/**
	 * Resolve a block's attributes into a settled set.
	 *
	 * Translates the version 1.0 layout names, falls back to the settings screen
	 * where the block is silent, and clamps anything out of range. Legacy values
	 * only fill attributes the block has not set itself, so an old block keeps
	 * its old appearance while a newly edited one wins.
	 *
	 * @param array<string,mixed> $attrs Raw block attributes.
	 * @return array<string,mixed>
	 */
	public static function normalise( array $attrs ): array {
		$layout = (string) ( $attrs['layout'] ?? Settings::get( 'default_layout', 'spotlight' ) );

		$legacy = Settings::legacy_layouts();
		if ( isset( $legacy[ $layout ] ) ) {
			foreach ( $legacy[ $layout ] as $key => $value ) {
				if ( 'layout' === $key || ! array_key_exists( $key, $attrs ) ) {
					$attrs[ $key ] = $value;
				}
			}
			$layout = (string) $legacy[ $layout ]['layout'];
		}

		if ( ! array_key_exists( $layout, Settings::layouts() ) ) {
			$layout = 'spotlight';
		}
		$attrs['layout'] = $layout;

		$mark = (string) ( $attrs['markStyle'] ?? Settings::get( 'default_mark', 'ledger' ) );
		if ( ! array_key_exists( $mark, Settings::mark_styles() ) ) {
			$mark = 'ledger';
		}
		$attrs['markStyle'] = $mark;

		/*
		 * One column count drives both roles: how many cards sit in a row when
		 * static, and how many are visible per page when sliding. A separate
		 * "slides per view" setting alongside it was one control too many for
		 * what is really one idea - how wide the arrangement is.
		 */
		$attrs['columns'] = max( 1, min( 6, (int) ( $attrs['columns'] ?? Settings::get( 'default_columns', 3 ) ) ) );

		$attrs['loop']     = array_key_exists( 'loop', $attrs ) ? (bool) $attrs['loop'] : (bool) Settings::get( 'slider_loop', true );
		$attrs['autoplay'] = max( 0, min( 30, (int) ( $attrs['autoplay'] ?? Settings::get( 'slider_autoplay', 0 ) ) ) );

		// Settled to plain booleans, same as every other key here, so nothing
		// downstream needs its own isset()/empty() guard to read them safely.
		$attrs['slider']  = ! empty( $attrs['slider'] );
		$attrs['masonry'] = ! empty( $attrs['masonry'] );

		return $attrs;
	}

	/**
	 * Whether this testimonial has others sitting beside it.
	 *
	 * True whenever more than one column is showing, on any layout, and always
	 * true for Marquee, whose cards are inherently side by side. Used only to
	 * scale an oversized mark - sized for a wide, solitary quote - back down
	 * once it shares a narrower column with others. Whether long quotes get
	 * trimmed is a separate question, decided purely by whether Read More is
	 * turned on: a single large Spotlight quote can run just as long as one
	 * sitting in a row of cards, and the choice of whether to shorten it
	 * belongs to whoever placed the block, not to how many columns it has.
	 *
	 * @param array<string,mixed> $attrs Settled attributes.
	 */
	private static function is_side_by_side( array $attrs ): bool {
		return 'marquee' === $attrs['layout'] || (int) $attrs['columns'] > 1;
	}

	/**
	 * Whether long quotes should be trimmed.
	 *
	 * Decided entirely by the Read More setting - a per-block override where
	 * one is set, the site-wide default otherwise - and nothing else. Applies
	 * on every layout, including a single-column Spotlight or Inline quote and
	 * Marquee alike.
	 *
	 * @param array<string,mixed> $attrs Settled attributes.
	 */
	private static function trims_quotes( array $attrs ): bool {
		if ( array_key_exists( 'readMore', $attrs ) ) {
			return (bool) $attrs['readMore'];
		}
		return (bool) Settings::get( 'read_more', true );
	}

	/**
	 * Compose the class list shared by every layout wrapper.
	 *
	 * @param string $layout Layout key.
	 * @param string $mark   Quotation-mark key.
	 */
	private static function wrapper_classes( array $attrs ): string {
		$classes = array(
			'dvdm-t',
			'dvdm-t--' . $attrs['layout'],
			'dvdm-mark--' . $attrs['markStyle'],
		);

		// Named to match core's own has-text-align-* convention, so a theme
		// that already styles that class for other blocks styles this one too.
		$align = (string) ( $attrs['textAlign'] ?? '' );
		if ( in_array( $align, array( 'left', 'center', 'right' ), true ) ) {
			$classes[] = 'has-text-align-' . $align;
		}

		$applies = 'marquee' !== $attrs['layout'];
		$slider  = $applies && ! empty( $attrs['slider'] );

		// Masonry packs by height across a flow; a slider moves by whole pages.
		// The two ideas do not combine, so masonry only applies while static.
		if ( $applies && ! $slider && ! empty( $attrs['masonry'] ) ) {
			$classes[] = 'dvdm-t--masonry';
		}
		if ( $slider ) {
			$classes[] = 'dvdm-t--slider';
		}
		if ( self::is_side_by_side( $attrs ) ) {
			// Lets a mark sized for one wide, solitary quote (Ledger, Slab) scale
			// back down once that quote has neighbours narrowing its column,
			// regardless of which layout put it there.
			$classes[] = 'dvdm-t--multi';
		}
		if ( self::trims_quotes( $attrs ) ) {
			$classes[] = 'dvdm-t--trimmed';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Merge classes and extra CSS declarations into the attributes WordPress
	 * generated for the block, producing exactly one class attribute and
	 * exactly one style attribute.
	 *
	 * Every wrapper here needs to add its own classes, and the slider wrapper
	 * also needs to add a CSS custom property alongside whatever style
	 * get_block_wrapper_attributes() already produced (colours, fonts, the
	 * column count). Concatenating a second `style="..."` string onto the
	 * markup by hand, which is what an earlier version of this method's
	 * caller did, produces two style attributes on one element - invalid
	 * HTML that browsers resolve by silently keeping only the first and
	 * discarding the second, which is why the per-view slide width once
	 * disappeared without any error. Routing every addition through
	 * WP_HTML_Tag_Processor is what makes that class of bug impossible.
	 *
	 * @param string               $wrapper_attributes Attribute string from core.
	 * @param string               $extra_classes      Classes to add.
	 * @param array<string,string> $extra_style        CSS custom properties to add, name to value.
	 */
	private static function merge_attributes( string $wrapper_attributes, string $extra_classes, array $extra_style = array() ): string {
		$processor = new \WP_HTML_Tag_Processor( '<div ' . $wrapper_attributes . '></div>' );
		if ( ! $processor->next_tag() ) {
			return $wrapper_attributes;
		}

		foreach ( explode( ' ', $extra_classes ) as $class ) {
			if ( '' !== $class ) {
				$processor->add_class( $class );
			}
		}

		if ( array() !== $extra_style ) {
			$style = (string) $processor->get_attribute( 'style' );
			if ( '' !== $style && ! str_ends_with( rtrim( $style ), ';' ) ) {
				$style .= ';';
			}
			foreach ( $extra_style as $property => $value ) {
				$style .= $property . ':' . $value . ';';
			}
			$processor->set_attribute( 'style', $style );
		}

		$html = $processor->get_updated_html();
		// Strip the synthetic tag back down to its attribute string.
		$html = preg_replace( '/^<div\s*/', '', $html );
		$html = is_string( $html ) ? preg_replace( '/><\/div>$/', '', $html ) : '';
		return is_string( $html ) ? $html : $wrapper_attributes;
	}

	/**
	 * Wrap items for the layouts that do not move.
	 *
	 * @param array<int,string>   $items              Rendered items.
	 * @param string              $wrapper_attributes Core wrapper attributes.
	 * @param array<string,mixed> $attrs              Settled attributes.
	 */
	private static function wrap_plain( array $items, string $wrapper_attributes, array $attrs ): string {
		return sprintf(
			'<div %1$s%2$s><div class="dvdm-t__items">%3$s</div>%4$s</div>',
			self::merge_attributes( $wrapper_attributes, self::wrapper_classes( $attrs ) ),
			self::trims_quotes( $attrs ) ? ' data-wp-interactive="devadigm/testimonials"' : '',
			implode( '', $items ),
			self::dialog( $attrs )
		);
	}

	/**
	 * Wrap items as an accessible carousel.
	 *
	 * Works for any number of visible slides, so the same code serves a single
	 * large testimonial and a three-across slider. Scroll snapping does the
	 * moving; the script only keeps the controls in step.
	 *
	 * @param array<int,string>   $items              Rendered items.
	 * @param string              $wrapper_attributes Core wrapper attributes.
	 * @param array<string,mixed> $attrs              Settled attributes.
	 */
	private static function wrap_slider( array $items, string $wrapper_attributes, array $attrs ): string {
		$prev     = (string) Settings::get( 'arrow_prev', '←' );
		$next     = (string) Settings::get( 'arrow_next', '→' );
		$total    = count( $items );
		$per_view = min( (int) $attrs['columns'], max( 1, $total ) );
		$pages    = (int) ceil( $total / $per_view );

		$slides = '';
		foreach ( $items as $index => $item ) {
			$slides .= sprintf(
				'<div class="dvdm-t__slide" role="group" aria-roledescription="%1$s" aria-label="%2$s">%3$s</div>',
				esc_attr__( 'slide', 'devadigm-testimonials' ),
				esc_attr(
					sprintf(
						/* translators: 1: slide number, 2: total slides. */
						__( '%1$d of %2$d', 'devadigm-testimonials' ),
						$index + 1,
						$total
					)
				),
				$item
			);
		}

		$dots = '';
		for ( $page = 0; $page < $pages; $page++ ) {
			// The first dot is marked on the server, so the state is correct
			// before hydration and for anyone without scripting.
			$dots .= sprintf(
				'<button type="button" class="dvdm-t__dot" data-wp-context=\'{"index":%1$d}\' data-wp-on--click="actions.goTo" data-wp-bind--aria-current="state.isCurrent"%3$s aria-label="%2$s"></button>',
				$page,
				esc_attr(
					sprintf(
						/* translators: %d: slide group number. */
						__( 'Go to testimonial group %d', 'devadigm-testimonials' ),
						$page + 1
					)
				),
				0 === $page ? ' aria-current="true"' : ''
			);
		}

		$autoplay = (int) $attrs['autoplay'];
		$pause    = '';
		if ( $autoplay > 0 ) {
			$pause = sprintf(
				'<button type="button" class="dvdm-t__pause" data-wp-on--click="actions.togglePause" data-wp-text="state.pauseLabel">%s</button>',
				esc_html__( 'Pause', 'devadigm-testimonials' )
			);
		}

		$context = wp_json_encode(
			array(
				'current'   => 0,
				'total'     => $total,
				'perView'   => $per_view,
				'pages'     => $pages,
				'loop'      => (bool) $attrs['loop'],
				'autoplay'  => $autoplay,
				'paused'    => false,
				'pauseText' => __( 'Pause', 'devadigm-testimonials' ),
				'playText'  => __( 'Play', 'devadigm-testimonials' ),
			)
		);

		return sprintf(
			'<div %1$s data-wp-interactive="devadigm/testimonials" data-wp-context=\'%2$s\' data-wp-init="callbacks.initSlider">
				<div class="dvdm-t__carousel" role="group" aria-roledescription="%3$s" aria-label="%4$s" data-wp-on--focusin="actions.stopAutoplay" data-wp-on--mouseenter="actions.stopAutoplay">
					<div class="dvdm-t__items" tabindex="0" data-wp-on--scroll="actions.onScroll" data-wp-on--keydown="actions.onKeydown">%5$s</div>
					<div class="dvdm-t__nav">
						<button type="button" class="dvdm-t__arrow" data-wp-on--click="actions.prev" data-wp-bind--disabled="state.atStart" aria-label="%6$s">%7$s</button>
						<div class="dvdm-t__dots">%8$s</div>
						<button type="button" class="dvdm-t__arrow" data-wp-on--click="actions.next" data-wp-bind--disabled="state.atEnd" aria-label="%9$s">%10$s</button>
						%11$s
					</div>
					<p class="dvdm-t__live screen-reader-text" aria-live="polite" data-wp-text="state.liveText"></p>
				</div>
				%12$s
			</div>',
			self::merge_attributes(
				$wrapper_attributes,
				self::wrapper_classes( $attrs ),
				array( '--dvdm-per-view' => (string) $per_view )
			),
			esc_attr( (string) $context ),
			esc_attr__( 'carousel', 'devadigm-testimonials' ),
			esc_attr__( 'Testimonials', 'devadigm-testimonials' ),
			$slides,
			esc_attr__( 'Previous testimonials', 'devadigm-testimonials' ),
			esc_html( $prev ),
			$dots,
			esc_attr__( 'Next testimonials', 'devadigm-testimonials' ),
			esc_html( $next ),
			$pause,
			self::dialog( $attrs )
		);
	}

	/**
	 * Wrap items as a pausable marquee. The track is duplicated so the loop is seamless.
	 *
	 * @param array<int,string>   $items              Rendered items.
	 * @param string              $wrapper_attributes Core wrapper attributes.
	 * @param array<string,mixed> $attrs              Settled attributes.
	 */
	private static function wrap_marquee( array $items, string $wrapper_attributes, array $attrs ): string {
		$duplicate = '';
		foreach ( $items as $item ) {
			/*
			 * The second copy of the track exists only so the loop loops. It is
			 * hidden from assistive technology and marked inert, because a
			 * testimonial can carry a company link or a Read more link, and
			 * hiding a focusable element without removing it from the tab order
			 * strands keyboard users on a control screen readers cannot describe.
			 */
			$processor = new \WP_HTML_Tag_Processor( $item );
			if ( $processor->next_tag( array( 'tag_name' => 'FIGURE' ) ) ) {
				$processor->set_attribute( 'aria-hidden', 'true' );
				$processor->set_attribute( 'inert', '' );
			}
			$duplicate .= $processor->get_updated_html();
		}

		$context = wp_json_encode(
			array(
				'paused'    => false,
				'hovering'  => false,
				'pauseText' => __( 'Pause', 'devadigm-testimonials' ),
				'playText'  => __( 'Play', 'devadigm-testimonials' ),
			)
		);

		/*
		 * Pausing while hovered or focused is driven from JavaScript
		 * (state.trackPaused), not left to the CSS :hover / :focus-within rule
		 * alone. CSS hover is what actually stops the animation frame by frame,
		 * so it still does the work, but a theme carrying its own animation or
		 * transition reset can override a plain :hover rule with a more
		 * specific or later one - which is exactly the class of bug that broke
		 * this plugin's dialog positioning on a real site. Binding the pause
		 * through data-wp-bind--data-paused as well means the state the Read
		 * More link's own click and focus handling can depend on is never
		 * purely a CSS outcome that a theme happens to be able to override.
		 */
		return sprintf(
			'<div %1$s data-wp-interactive="devadigm/testimonials" data-wp-context=\'%2$s\'>
				<div class="dvdm-t__marquee" data-wp-on--mouseenter="actions.marqueeHoverStart" data-wp-on--mouseleave="actions.marqueeHoverEnd" data-wp-on--focusin="actions.marqueeHoverStart" data-wp-on--focusout="actions.marqueeHoverEnd">
					<div class="dvdm-t__track" data-wp-bind--data-paused="state.trackPaused">%3$s%4$s</div>
				</div>
				<button type="button" class="dvdm-t__pause" data-wp-on--click="actions.togglePause" data-wp-text="state.pauseLabel">%5$s</button>
				%6$s
			</div>',
			self::merge_attributes( $wrapper_attributes, self::wrapper_classes( $attrs ) ),
			esc_attr( (string) $context ),
			implode( '', $items ),
			$duplicate,
			esc_html__( 'Pause', 'devadigm-testimonials' ),
			self::dialog( $attrs )
		);
	}

	/**
	 * The shared dialog a Read more link opens.
	 *
	 * One per block rather than one per testimonial. The full quote already
	 * exists in the card - it is only visually trimmed - so the script moves
	 * that markup in here rather than the server printing every quote twice.
	 *
	 * @param array<string,mixed> $attrs Settled attributes.
	 */
	private static function dialog( array $attrs ): string {
		if ( ! self::trims_quotes( $attrs ) ) {
			return '';
		}

		return sprintf(
			'<dialog class="dvdm-t__dialog" aria-label="%1$s" data-wp-on--click="actions.dialogClickOut" data-wp-on--close="actions.dialogClosed">
				<div class="dvdm-t__dialog-body"></div>
				<button type="button" class="dvdm-t__dialog-close" data-wp-on--click="actions.closeDialog">%2$s</button>
			</dialog>',
			esc_attr__( 'Full testimonial', 'devadigm-testimonials' ),
			esc_html__( 'Close', 'devadigm-testimonials' )
		);
	}

	/**
	 * Render one testimonial.
	 *
	 * @param \WP_Post            $post   Testimonial.
	 * @param string              $mark   Quotation-mark key.
	 * @param string              $layout Layout key.
	 * @param array<string,mixed> $attrs  Block attributes.
	 */
	public static function item( \WP_Post $post, string $mark, string $layout, array $attrs = array() ): string {
		$quote   = trim( wp_strip_all_tags( (string) $post->post_content ) );
		$name    = (string) get_post_meta( $post->ID, 'dvdm_author_name', true );
		$role    = (string) get_post_meta( $post->ID, 'dvdm_author_role', true );
		$company = (string) get_post_meta( $post->ID, 'dvdm_author_company', true );
		$url     = (string) get_post_meta( $post->ID, 'dvdm_company_url', true );
		$rating  = (float) get_post_meta( $post->ID, 'dvdm_rating', true );
		$source  = (string) get_post_meta( $post->ID, 'dvdm_source_url', true );
		$metric  = (string) get_post_meta( $post->ID, 'dvdm_result_metric', true );

		if ( '' === $quote ) {
			return '';
		}

		$show_rating = ! empty( $attrs['showRating'] ) && $rating > 0;
		$show_avatar = ! isset( $attrs['showAvatar'] ) || ! empty( $attrs['showAvatar'] );

		/*
		 * Length is judged on the server so the decision is identical for every
		 * visitor and survives page caching. Measuring rendered overflow in the
		 * browser instead would mean the link flickers in after paint, and would
		 * leave the quote trimmed with no way out for anyone without scripting.
		 */
		$long = false;
		if ( self::trims_quotes( $attrs ) ) {
			$words     = preg_split( '/\s+/', $quote, -1, PREG_SPLIT_NO_EMPTY );
			$threshold = (int) Settings::get( 'excerpt_words', 28 );
			$long      = is_array( $words ) && count( $words ) > $threshold;
		}

		$parts = array();

		if ( $show_rating ) {
			$parts[] = self::rating( $rating );
		}

		$parts[] = self::quote_block( $quote, $mark, $source, $long );

		if ( '' !== $metric ) {
			$parts[] = sprintf( '<p class="dvdm-t__metric">%s</p>', esc_html( $metric ) );
		}

		if ( $long ) {
			$parts[] = self::read_more_link( $post, $name );
		}

		$parts[] = self::attribution( $post, $name, $role, $company, $url, $show_avatar );

		return sprintf(
			'<figure class="dvdm-t__item">%s</figure>',
			implode( '', $parts )
		);
	}

	/**
	 * Render the quote itself, including the decorative mark.
	 *
	 * The glyph is never placed in the text. It is drawn by CSS from the
	 * --dvdm-glyph custom property, so copying the quote copies only the words.
	 *
	 * @param string $quote  Quote text.
	 * @param string $mark   Quotation-mark key.
	 * @param string $source Optional source URL for the cite attribute.
	 */
	private static function quote_block( string $quote, string $mark, string $source, bool $clamped = false ): string {
		$cite = '' !== $source ? sprintf( ' cite="%s"', esc_url( $source ) ) : '';

		/*
		 * The whole quote is printed either way. Trimming is done in CSS, so the
		 * complete text stays in the document for search engines, for copying,
		 * and for the script to move into the dialog - printed once, not twice.
		 */
		$blockquote = sprintf(
			'<blockquote class="dvdm-t__quote%3$s"%1$s><p>%2$s</p></blockquote>',
			$cite,
			esc_html( $quote ),
			$clamped ? ' dvdm-t__quote--clamped' : ''
		);

		// Treatments that need a real element rather than a pseudo-element.
		return match ( $mark ) {
			'badge'   => '<span class="dvdm-t__badge" aria-hidden="true"></span>' . $blockquote,
			'slab'    => '<span class="dvdm-t__slab" aria-hidden="true"></span>' . $blockquote,
			'bracket' => '<span class="dvdm-t__rule dvdm-t__rule--top" aria-hidden="true"></span>'
				. $blockquote
				. '<span class="dvdm-t__rule dvdm-t__rule--bottom" aria-hidden="true"></span>',
			default   => $blockquote,
		};
	}

	/**
	 * The Read more control.
	 *
	 * A real link to the testimonial, which the script upgrades into a dialog.
	 * That way it still does something useful with scripting unavailable, and
	 * middle-clicking it opens the testimonial in a tab as a link should.
	 *
	 * @param \WP_Post $post Testimonial.
	 * @param string   $name Author name, for the accessible label.
	 */
	private static function read_more_link( \WP_Post $post, string $name ): string {
		$label = '' !== $name
			/* translators: %s: person who gave the testimonial. */
			? sprintf( __( 'Read the full testimonial from %s', 'devadigm-testimonials' ), $name )
			: __( 'Read the full testimonial', 'devadigm-testimonials' );

		return sprintf(
			'<a class="dvdm-t__more" href="%1$s" aria-label="%2$s" data-wp-on--click="actions.openDialog">%3$s</a>',
			esc_url( (string) get_permalink( $post ) ),
			esc_attr( $label ),
			esc_html__( 'Read more', 'devadigm-testimonials' )
		);
	}

	/**
	 * Render the rating as text with decorative icons.
	 *
	 * @param float $rating Rating out of five.
	 */
	private static function rating( float $rating ): string {
		[ $full, $empty ] = Tokens::star_glyphs();

		$icons = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			$icons .= ( $i <= round( $rating ) ) ? $full : $empty;
		}

		return sprintf(
			'<p class="dvdm-t__rating"><span aria-hidden="true">%1$s</span><span class="screen-reader-text">%2$s</span></p>',
			esc_html( $icons ),
			esc_html(
				sprintf(
					/* translators: %s: rating out of five. */
					__( 'Rated %s out of 5', 'devadigm-testimonials' ),
					(string) $rating
				)
			)
		);
	}

	/**
	 * Render the attribution line.
	 *
	 * @param \WP_Post $post        Testimonial.
	 * @param string   $name        Author name.
	 * @param string   $role        Author role.
	 * @param string   $company     Company name.
	 * @param string   $url         Company URL.
	 * @param bool     $show_avatar Whether to render the avatar.
	 */
	private static function attribution( \WP_Post $post, string $name, string $role, string $company, string $url, bool $show_avatar ): string {
		if ( '' === $name && '' === $company ) {
			return '';
		}

		$avatar = '';
		if ( $show_avatar ) {
			if ( has_post_thumbnail( $post ) ) {
				$avatar = get_the_post_thumbnail(
					$post,
					array( 96, 96 ),
					array(
						'class'    => 'dvdm-t__avatar',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'alt'      => '',
					)
				);
			} elseif ( '' !== $name ) {
				$avatar = sprintf(
					'<span class="dvdm-t__avatar dvdm-t__avatar--initials" aria-hidden="true">%s</span>',
					esc_html( self::initials( $name ) )
				);
			}
		}

		$secondary = trim( implode( ', ', array_filter( array( $role, $company ) ) ) );
		if ( '' !== $secondary && '' !== $url ) {
			$secondary = sprintf(
				'<a href="%1$s" rel="nofollow noopener">%2$s</a>',
				esc_url( $url ),
				esc_html( $secondary )
			);
		} else {
			$secondary = esc_html( $secondary );
		}

		return sprintf(
			'<figcaption class="dvdm-t__attribution">%1$s<span class="dvdm-t__who">%2$s%3$s</span></figcaption>',
			$avatar,
			'' !== $name ? sprintf( '<span class="dvdm-t__name">%s</span>', esc_html( $name ) ) : '',
			'' !== $secondary ? sprintf( '<span class="dvdm-t__role">%s</span>', $secondary ) : ''
		);
	}

	/**
	 * Build up to two initials from a name.
	 *
	 * @param string $name Full name.
	 */
	private static function initials( string $name ): string {
		$words    = preg_split( '/\s+/', trim( $name ) ) ?: array();
		$initials = '';
		foreach ( array_slice( $words, 0, 2 ) as $word ) {
			$initials .= mb_strtoupper( mb_substr( $word, 0, 1 ) );
		}
		return $initials;
	}
}
