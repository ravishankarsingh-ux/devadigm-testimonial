<?php
/**
 * Testimonial meta fields, their REST registration and the editor panel.
 *
 * @package Devadigm\Testimonials
 */

declare( strict_types = 1 );

namespace Devadigm\Testimonials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns every piece of per-testimonial data that is not the quote itself.
 */
final class Meta {

	/**
	 * Field definitions: key => [type, sanitize callback, label].
	 *
	 * @return array<string,array{type:string,sanitize:callable,label:string,input:string}>
	 */
	public static function fields(): array {
		return array(
			'dvdm_author_name'    => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Name', 'devadigm-testimonials' ),
				'input'    => 'text',
			),
			'dvdm_author_role'    => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Role', 'devadigm-testimonials' ),
				'input'    => 'text',
			),
			'dvdm_author_company' => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Company', 'devadigm-testimonials' ),
				'input'    => 'text',
			),
			'dvdm_company_url'    => array(
				'type'     => 'string',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Company URL', 'devadigm-testimonials' ),
				'input'    => 'url',
			),
			'dvdm_rating'         => array(
				'type'     => 'number',
				'sanitize' => array( self::class, 'sanitize_rating' ),
				'label'    => __( 'Rating (0 to 5)', 'devadigm-testimonials' ),
				'input'    => 'rating',
			),
			'dvdm_result_metric'  => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Result metric', 'devadigm-testimonials' ),
				'input'    => 'text',
			),
			'dvdm_given_on'       => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_date' ),
				'label'    => __( 'Date given', 'devadigm-testimonials' ),
				'input'    => 'date',
			),
			'dvdm_source_url'     => array(
				'type'     => 'string',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Source URL', 'devadigm-testimonials' ),
				'input'    => 'url',
			),
			'dvdm_featured'       => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
				'label'    => __( 'Featured', 'devadigm-testimonials' ),
				'input'    => 'checkbox',
			),
			'dvdm_consent_method' => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Consent method', 'devadigm-testimonials' ),
				'input'    => 'text',
			),
			'dvdm_verified_at'    => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_date' ),
				'label'    => __( 'Verified on', 'devadigm-testimonials' ),
				'input'    => 'date',
			),
			'dvdm_original_text'  => array(
				'type'     => 'string',
				'sanitize' => 'wp_kses_post',
				'label'    => __( 'Original unedited submission', 'devadigm-testimonials' ),
				'input'    => 'textarea',
			),
		);
	}

	/**
	 * Hook registration into WordPress.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'add_meta_boxes', array( self::class, 'add_meta_box' ) );
		add_action( 'save_post_' . POST_TYPE, array( self::class, 'save' ), 10, 2 );
	}

	/**
	 * Register every field with the REST API so blocks and headless clients see them.
	 */
	public static function register(): void {
		foreach ( self::fields() as $key => $field ) {
			register_post_meta(
				POST_TYPE,
				$key,
				array(
					'type'              => $field['type'],
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $field['sanitize'],
					'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
				)
			);
		}
	}

	/**
	 * Clamp a rating to the 0 to 5 range in half steps.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_rating( mixed $value ): float {
		$rating = (float) $value;
		$rating = max( 0.0, min( 5.0, $rating ) );
		return round( $rating * 2 ) / 2;
	}

	/**
	 * Accept only Y-m-d dates.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_date( mixed $value ): string {
		$raw = sanitize_text_field( (string) $value );
		if ( '' === $raw ) {
			return '';
		}
		$date = \DateTimeImmutable::createFromFormat( 'Y-m-d', $raw );
		return ( $date instanceof \DateTimeImmutable && $date->format( 'Y-m-d' ) === $raw ) ? $raw : '';
	}

	/**
	 * Normalise a checkbox value.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_bool( mixed $value ): bool {
		return (bool) $value;
	}

	/**
	 * Add the details panel to the testimonial editor.
	 */
	public static function add_meta_box(): void {
		add_meta_box(
			'dvdm-testimonial-details',
			__( 'Testimonial details', 'devadigm-testimonials' ),
			array( self::class, 'render_meta_box' ),
			POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the details panel.
	 *
	 * @param \WP_Post $post Post being edited.
	 */
	public static function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'dvdm_save_meta', 'dvdm_meta_nonce' );

		echo '<div class="dvdm-meta-grid">';
		foreach ( self::fields() as $key => $field ) {
			$value = get_post_meta( $post->ID, $key, true );
			printf( '<p class="dvdm-field dvdm-field--%s">', esc_attr( $field['input'] ) );
			printf(
				'<label for="%1$s"><strong>%2$s</strong></label><br />',
				esc_attr( $key ),
				esc_html( $field['label'] )
			);

			switch ( $field['input'] ) {
				case 'textarea':
					printf(
						'<textarea id="%1$s" name="%1$s" rows="4" class="widefat">%2$s</textarea>',
						esc_attr( $key ),
						esc_textarea( (string) $value )
					);
					break;

				case 'checkbox':
					printf(
						'<input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s />',
						esc_attr( $key ),
						checked( (bool) $value, true, false )
					);
					break;

				case 'rating':
					printf(
						'<input type="number" id="%1$s" name="%1$s" value="%2$s" min="0" max="5" step="0.5" class="small-text" />',
						esc_attr( $key ),
						esc_attr( (string) $value )
					);
					break;

				default:
					printf(
						'<input type="%3$s" id="%1$s" name="%1$s" value="%2$s" class="widefat" />',
						esc_attr( $key ),
						esc_attr( (string) $value ),
						esc_attr( 'url' === $field['input'] ? 'url' : ( 'date' === $field['input'] ? 'date' : 'text' ) )
					);
			}
			echo '</p>';
		}
		echo '</div>';

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Keep the original submission on record. Editing a customer\'s words without keeping the original removes your ability to prove what they actually said.', 'devadigm-testimonials' )
		);

		echo '<style>
			.dvdm-meta-grid{display:grid;gap:0 1.5rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
			.dvdm-field--textarea{grid-column:1/-1}
		</style>';
	}

	/**
	 * Persist submitted values.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public static function save( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST['dvdm_meta_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['dvdm_meta_nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'dvdm_save_meta' ) ) {
			return;
		}

		foreach ( self::fields() as $key => $field ) {
			if ( 'checkbox' === $field['input'] ) {
				update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) );
				continue;
			}

			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$raw   = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized on the next line.
			$clean = call_user_func( $field['sanitize'], $raw );

			if ( '' === $clean || null === $clean ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $clean );
			}
		}
	}
}
