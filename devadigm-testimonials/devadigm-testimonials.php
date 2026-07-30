<?php
/**
 * Plugin Name:       Devadigm Testimonials
 * Plugin URI:        https://devadigm.com/
 * Description:       Testimonials with six display layouts, six quotation-mark treatments, and a settings screen for colours, fonts and icons.
 * Version:           1.4.2
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Devadigm
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       devadigm-testimonials
 * Domain Path:       /languages
 *
 * @package Devadigm\Testimonials
 */

declare( strict_types = 1 );

namespace Devadigm\Testimonials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION     = '1.4.2';
const VERSION_KEY = 'devadigm_testimonials_version';
const OPTION_KEY  = 'devadigm_testimonials_settings';
const POST_TYPE   = 'dvdm_testimonial';
const TAX_SERVICE = 'dvdm_service';
const TAX_SOURCE  = 'dvdm_source';

define( __NAMESPACE__ . '\PLUGIN_FILE', __FILE__ );
define( __NAMESPACE__ . '\PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( __NAMESPACE__ . '\PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once PLUGIN_DIR . 'includes/class-settings.php';
require_once PLUGIN_DIR . 'includes/class-tokens.php';
require_once PLUGIN_DIR . 'includes/class-post-type.php';
require_once PLUGIN_DIR . 'includes/class-meta.php';
require_once PLUGIN_DIR . 'includes/class-renderer.php';
require_once PLUGIN_DIR . 'includes/class-block.php';
require_once PLUGIN_DIR . 'includes/class-updater.php';

/**
 * Boot every subsystem once WordPress is ready.
 */
function bootstrap(): void {
	Post_Type::init();
	Meta::init();
	Settings::init();
	Tokens::init();
	Block::init();
	Updater::init( PLUGIN_FILE );

	add_action( 'admin_init', __NAMESPACE__ . '\\maybe_upgrade' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );

/**
 * Run any work a version bump needs, once per upgrade.
 *
 * The stored version is what makes an upgrade detectable at all: without it
 * there is no way to tell a fresh install from one that has been running since
 * an earlier release, and no way to add a migration later without guessing.
 *
 * Layout renames are deliberately not migrated. Rewriting saved block markup
 * risks damaging content for a change that is only cosmetic, so old layout
 * names are translated at render time instead. See Settings::legacy_layouts().
 */
function maybe_upgrade(): void {
	$stored = (string) get_option( VERSION_KEY, '' );

	if ( VERSION === $stored ) {
		return;
	}

	if ( '' === $stored ) {
		// A fresh install, or an upgrade from 1.0.0, which stored no version.
		Post_Type::add_capabilities();
	}

	if ( '' !== $stored && version_compare( $stored, VERSION, '>' ) ) {
		// Downgraded. Leave settings alone and only record where we are.
		update_option( VERSION_KEY, VERSION, false );
		return;
	}

	// Settings added in later versions need their defaults written in, or a
	// site upgrading from an older release would read them as empty.
	$settings = get_option( OPTION_KEY, array() );
	if ( is_array( $settings ) ) {
		$merged = array_merge( Settings::defaults(), $settings );
		if ( $merged !== $settings ) {
			update_option( OPTION_KEY, $merged );
		}
	}

	flush_rewrite_rules();
	update_option( VERSION_KEY, VERSION, false );

	/**
	 * Fires once after the plugin has upgraded to a new version.
	 *
	 * @param string $to   Version now running.
	 * @param string $from Version previously recorded, empty on first run.
	 */
	do_action( 'devadigm_testimonials_upgraded', VERSION, $stored );
}

/**
 * Register the post type on activation so rewrite rules can be flushed once.
 */
function activate(): void {
	Post_Type::register();
	Post_Type::add_capabilities();
	flush_rewrite_rules();
	update_option( VERSION_KEY, VERSION, false );
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\activate' );

/**
 * Drop rewrite rules on deactivation. Content and settings are left intact.
 */
function deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate' );
