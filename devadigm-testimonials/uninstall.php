<?php
/**
 * Uninstall routine.
 *
 * Removes the settings row only. Testimonials are content, and content is not
 * deleted by uninstalling a plugin.
 *
 * @package Devadigm\Testimonials
 */

declare( strict_types = 1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'devadigm_testimonials_settings' );
delete_option( 'devadigm_testimonials_version' );

foreach ( array( 'administrator', 'editor' ) as $role_name ) {
	$role = get_role( $role_name );
	if ( $role instanceof WP_Role ) {
		$role->remove_cap( 'moderate_testimonials' );
	}
}
