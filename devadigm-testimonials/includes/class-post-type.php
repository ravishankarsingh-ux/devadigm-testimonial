<?php
/**
 * Testimonial post type and taxonomies.
 *
 * @package Devadigm\Testimonials
 */

declare( strict_types = 1 );

namespace Devadigm\Testimonials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the testimonial content type, its taxonomies and its admin columns.
 */
final class Post_Type {

	/**
	 * Hook registration into WordPress.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_filter( 'manage_' . POST_TYPE . '_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_' . POST_TYPE . '_posts_custom_column', array( self::class, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . POST_TYPE . '_sortable_columns', array( self::class, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( self::class, 'sort_by_rating' ) );
	}

	/**
	 * Register the post type and both taxonomies.
	 */
	public static function register(): void {
		register_post_type(
			POST_TYPE,
			array(
				'labels'          => array(
					'name'               => __( 'Testimonials', 'devadigm-testimonials' ),
					'singular_name'      => __( 'Testimonial', 'devadigm-testimonials' ),
					'add_new_item'       => __( 'Add Testimonial', 'devadigm-testimonials' ),
					'edit_item'          => __( 'Edit Testimonial', 'devadigm-testimonials' ),
					'search_items'       => __( 'Search Testimonials', 'devadigm-testimonials' ),
					'not_found'          => __( 'No testimonials yet.', 'devadigm-testimonials' ),
					'menu_name'          => __( 'Testimonials', 'devadigm-testimonials' ),
				),
				'public'          => true,
				'has_archive'     => false,
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-format-quote',
				'menu_position'   => 26,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields' ),
				'rewrite'         => array( 'slug' => 'testimonial' ),
				'capability_type' => 'post',
			)
		);

		register_taxonomy(
			TAX_SERVICE,
			POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Service lines', 'devadigm-testimonials' ),
					'singular_name' => __( 'Service line', 'devadigm-testimonials' ),
					'menu_name'     => __( 'Service lines', 'devadigm-testimonials' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'testimonial-service' ),
			)
		);

		register_taxonomy(
			TAX_SOURCE,
			POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Sources', 'devadigm-testimonials' ),
					'singular_name' => __( 'Source', 'devadigm-testimonials' ),
					'menu_name'     => __( 'Sources', 'devadigm-testimonials' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'hierarchical'      => false,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);
	}

	/**
	 * Give administrators and editors the moderation capability.
	 */
	public static function add_capabilities(): void {
		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role instanceof \WP_Role ) {
				$role->add_cap( 'moderate_testimonials' );
			}
		}
	}

	/**
	 * Replace the default columns with ones that matter for testimonials.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public static function columns( array $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['dvdm_author'] = __( 'Given by', 'devadigm-testimonials' );
				$out['dvdm_rating'] = __( 'Rating', 'devadigm-testimonials' );
			}
		}
		$out['dvdm_consent'] = __( 'Consent', 'devadigm-testimonials' );
		return $out;
	}

	/**
	 * Render one custom column cell.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post being rendered.
	 */
	public static function column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'dvdm_author':
				$name    = (string) get_post_meta( $post_id, 'dvdm_author_name', true );
				$company = (string) get_post_meta( $post_id, 'dvdm_author_company', true );
				echo esc_html( trim( $name . ( '' !== $company ? ', ' . $company : '' ) ) );
				break;

			case 'dvdm_rating':
				$rating = (float) get_post_meta( $post_id, 'dvdm_rating', true );
				echo $rating > 0
					? esc_html( sprintf( /* translators: %s: rating out of five. */ __( '%s / 5', 'devadigm-testimonials' ), (string) $rating ) )
					: '&mdash;';
				break;

			case 'dvdm_consent':
				$verified = (string) get_post_meta( $post_id, 'dvdm_verified_at', true );
				if ( '' !== $verified ) {
					printf(
						'<span style="color:#2c6e49">%s</span>',
						esc_html__( 'Verified', 'devadigm-testimonials' )
					);
				} else {
					printf(
						'<span style="color:#9a3232">%s</span>',
						esc_html__( 'Not verified', 'devadigm-testimonials' )
					);
				}
				break;
		}
	}

	/**
	 * Allow sorting the list table by rating.
	 *
	 * @param array<string,string> $columns Sortable columns.
	 * @return array<string,string>
	 */
	public static function sortable_columns( array $columns ): array {
		$columns['dvdm_rating'] = 'dvdm_rating';
		return $columns;
	}

	/**
	 * Translate the rating sort request into a meta query.
	 *
	 * @param \WP_Query $query Current admin query.
	 */
	public static function sort_by_rating( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'dvdm_rating' !== $query->get( 'orderby' ) ) {
			return;
		}
		$query->set( 'meta_key', 'dvdm_rating' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
