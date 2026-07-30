<?php
/**
 * Plugin Name:       Devadigm Testimonials
 * Plugin URI:        https://devadigm.com/
 * Description:       Testimonials with six display layouts, six quotation-mark treatments, and a settings screen for colours, fonts and icons.
 * Version:           1.0.0
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

const VERSION     = '1.0.0';
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

/**
 * Boot every subsystem once WordPress is ready.
 */
function bootstrap(): void {
	Post_Type::init();
	Meta::init();
	Settings::init();
	Tokens::init();
	Block::init();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\bootstrap' );

/**
 * Register the post type on activation so rewrite rules can be flushed once.
 */
function activate(): void {
	Post_Type::register();
	Post_Type::add_capabilities();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\activate' );

/**
 * Drop rewrite rules on deactivation. Content and settings are left intact.
 */
function deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate' );
