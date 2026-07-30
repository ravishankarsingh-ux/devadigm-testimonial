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
		$posts = self::query( $attrs );
		if ( array() === $posts ) {
			return '';
		}

		$layout = (string) ( $attrs['layout'] ?? Settings::get( 'default_layout', 'spotlight' ) );
		$mark   = (string) ( $attrs['markStyle'] ?? Settings::get( 'default_mark', 'ledger' ) );

		if ( ! array_key_exists( $layout, Settings::layouts() ) ) {
			$layout = 'spotlight';
		}
		if ( ! array_key_exists( $mark, Settings::mark_styles() ) ) {
			$mark = 'ledger';
		}

		$items = array();
		foreach ( $posts as $post ) {
			$items[] = self::item( $post, $mark, $layout, $attrs );
		}

		return match ( $layout ) {
			'slideshow' => self::wrap_slideshow( $items, $wrapper_attributes, $layout, $mark ),
			'marquee'   => self::wrap_marquee( $items, $wrapper_attributes, $layout, $mark ),
			default     => self::wrap_plain( $items, $wrapper_attributes, $layout, $mark ),
		};
	}

	/**
	 * Compose the class list shared by every layout wrapper.
	 *
	 * @param string $layout Layout key.
	 * @param string $mark   Quotation-mark key.
	 */
	private static function wrapper_classes( string $layout, string $mark ): string {
		return sprintf( 'dvdm-t dvdm-t--%s dvdm-mark--%s', $layout, $mark );
	}

	/**
	 * Merge our classes into the attributes WordPress generated for the block.
	 *
	 * @param string $wrapper_attributes Attribute string from core.
	 * @param string $extra_classes      Classes to add.
	 */
	private static function merge_classes( string $wrapper_attributes, string $extra_classes ): string {
		$processor = new \WP_HTML_Tag_Processor( '<div ' . $wrapper_attributes . '></div>' );
		if ( ! $processor->next_tag() ) {
			return $wrapper_attributes;
		}
		foreach ( explode( ' ', $extra_classes ) as $class ) {
			if ( '' !== $class ) {
				$processor->add_class( $class );
			}
		}
		$html = $processor->get_updated_html();
		// Strip the synthetic tag back down to its attribute string.
		$html = preg_replace( '/^<div\s*/', '', $html );
		$html = is_string( $html ) ? preg_replace( '/><\/div>$/', '', $html ) : '';
		return is_string( $html ) ? $html : $wrapper_attributes;
	}

	/**
	 * Wrap items for the static layouts.
	 *
	 * @param array<int,string> $items              Rendered items.
	 * @param string            $wrapper_attributes Core wrapper attributes.
	 * @param string            $layout             Layout key.
	 * @param string            $mark               Quotation-mark key.
	 */
	private static function wrap_plain( array $items, string $wrapper_attributes, string $layout, string $mark ): string {
		return sprintf(
			'<div %1$s><div class="dvdm-t__items">%2$s</div></div>',
			self::merge_classes( $wrapper_attributes, self::wrapper_classes( $layout, $mark ) ),
			implode( '', $items )
		);
	}

	/**
	 * Wrap items as an accessible carousel driven by the Interactivity API.
	 *
	 * @param array<int,string> $items              Rendered items.
	 * @param string            $wrapper_attributes Core wrapper attributes.
	 * @param string            $layout             Layout key.
	 * @param string            $mark               Quotation-mark key.
	 */
	private static function wrap_slideshow( array $items, string $wrapper_attributes, string $layout, string $mark ): string {
		$prev = (string) Settings::get( 'arrow_prev', '←' );
		$next = (string) Settings::get( 'arrow_next', '→' );

		$slides = '';
		foreach ( $items as $index => $item ) {
			// A div, not a list item: giving an <li> role="group" strips its
			// listitem role and leaves the <ul> holding invalid children.
			$slides .= sprintf(
				'<div class="dvdm-t__slide" role="group" aria-roledescription="%1$s" aria-label="%2$s">%3$s</div>',
				esc_attr__( 'slide', 'devadigm-testimonials' ),
				esc_attr(
					sprintf(
						/* translators: 1: slide number, 2: total slides. */
						__( '%1$d of %2$d', 'devadigm-testimonials' ),
						$index + 1,
						count( $items )
					)
				),
				$item
			);
		}

		$dots = '';
		foreach ( array_keys( $items ) as $index ) {
			// The first dot is marked on the server so the state is right
			// before hydration, and for anyone with scripting turned off.
			$dots .= sprintf(
				'<button type="button" class="dvdm-t__dot" data-wp-context=\'{"index":%1$d}\' data-wp-on--click="actions.goTo" data-wp-bind--aria-current="state.isCurrent"%3$s aria-label="%2$s"></button>',
				$index,
				esc_attr(
					sprintf(
						/* translators: %d: slide number. */
						__( 'Go to testimonial %d', 'devadigm-testimonials' ),
						$index + 1
					)
				),
				0 === $index ? ' aria-current="true"' : ''
			);
		}

		$context = wp_json_encode(
			array(
				'current' => 0,
				'total'   => count( $items ),
			)
		);

		return sprintf(
			'<div %1$s data-wp-interactive="devadigm/testimonials" data-wp-context=\'%2$s\' data-wp-init="callbacks.init">
				<div class="dvdm-t__carousel" role="group" aria-roledescription="%3$s" aria-label="%4$s">
					<div class="dvdm-t__items" data-wp-on--scroll="actions.onScroll" tabindex="0" data-wp-on--keydown="actions.onKeydown">%5$s</div>
					<div class="dvdm-t__nav">
						<button type="button" class="dvdm-t__arrow" data-wp-on--click="actions.prev" aria-label="%6$s">%7$s</button>
						<div class="dvdm-t__dots">%8$s</div>
						<button type="button" class="dvdm-t__arrow" data-wp-on--click="actions.next" aria-label="%9$s">%10$s</button>
					</div>
					<p class="dvdm-t__live screen-reader-text" aria-live="polite" data-wp-text="state.liveText"></p>
				</div>
			</div>',
			self::merge_classes( $wrapper_attributes, self::wrapper_classes( $layout, $mark ) ),
			esc_attr( (string) $context ),
			esc_attr__( 'carousel', 'devadigm-testimonials' ),
			esc_attr__( 'Testimonials', 'devadigm-testimonials' ),
			$slides,
			esc_attr__( 'Previous testimonial', 'devadigm-testimonials' ),
			esc_html( $prev ),
			$dots,
			esc_attr__( 'Next testimonial', 'devadigm-testimonials' ),
			esc_html( $next )
		);
	}

	/**
	 * Wrap items as a pausable marquee. The track is duplicated so the loop is seamless.
	 *
	 * @param array<int,string> $items              Rendered items.
	 * @param string            $wrapper_attributes Core wrapper attributes.
	 * @param string            $layout             Layout key.
	 * @param string            $mark               Quotation-mark key.
	 */
	private static function wrap_marquee( array $items, string $wrapper_attributes, string $layout, string $mark ): string {
		$duplicate = '';
		foreach ( $items as $item ) {
			// The copy is decorative, so it is hidden from assistive technology.
			$duplicate .= str_replace( '<figure class="dvdm-t__item', '<figure aria-hidden="true" class="dvdm-t__item', $item );
		}

		$context = wp_json_encode(
			array(
				'paused'    => false,
				'pauseText' => __( 'Pause', 'devadigm-testimonials' ),
				'playText'  => __( 'Play', 'devadigm-testimonials' ),
			)
		);

		return sprintf(
			'<div %1$s data-wp-interactive="devadigm/testimonials" data-wp-context=\'%2$s\'>
				<div class="dvdm-t__marquee">
					<div class="dvdm-t__track" data-wp-bind--data-paused="state.paused">%3$s%4$s</div>
				</div>
				<button type="button" class="dvdm-t__pause" data-wp-on--click="actions.togglePause" data-wp-text="state.pauseLabel">%5$s</button>
			</div>',
			self::merge_classes( $wrapper_attributes, self::wrapper_classes( $layout, $mark ) ),
			esc_attr( (string) $context ),
			implode( '', $items ),
			$duplicate,
			esc_html__( 'Pause', 'devadigm-testimonials' )
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

		$parts = array();

		if ( $show_rating ) {
			$parts[] = self::rating( $rating );
		}

		$parts[] = self::quote_block( $quote, $mark, $source );

		if ( '' !== $metric ) {
			$parts[] = sprintf( '<p class="dvdm-t__metric">%s</p>', esc_html( $metric ) );
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
	private static function quote_block( string $quote, string $mark, string $source ): string {
		$cite = '' !== $source ? sprintf( ' cite="%s"', esc_url( $source ) ) : '';

		$blockquote = sprintf(
			'<blockquote class="dvdm-t__quote"%1$s><p>%2$s</p></blockquote>',
			$cite,
			esc_html( $quote )
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
