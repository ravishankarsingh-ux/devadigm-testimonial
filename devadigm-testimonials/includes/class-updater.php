<?php
/**
 * Lets WordPress' own plugin updater install new releases straight from the
 * public GitHub repository, so a client site can click "Update now" in
 * Plugins instead of being sent a zip by hand each time.
 *
 * The repository holds more than this plugin (docs, sample data, the built
 * zip itself), so a tagged commit's auto-generated archive has the plugin
 * sitting one level down, inside a `devadigm-testimonials/` subfolder rather
 * than being the archive's own top-level folder. `fix_source_dir()` corrects
 * that after WordPress extracts the download and before it moves the result
 * into `wp-content/plugins/`; without it every update would install a stray
 * `devadigm-testimonial-x.y.z/` folder instead of overwriting the plugin.
 *
 * @package Devadigm\Testimonials
 */

declare( strict_types = 1 );

namespace Devadigm\Testimonials;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Updater {

	const REPO_OWNER = 'ravishankarsingh-ux';
	const REPO_NAME  = 'devadigm-testimonial';
	const PLUGIN_DIR = 'devadigm-testimonials';

	const VERSION_CACHE_KEY   = 'dvdm_gh_update_check';
	const CHANGELOG_CACHE_KEY = 'dvdm_gh_changelog';

	private static string $plugin_basename;

	public static function init( string $plugin_file ): void {
		self::$plugin_basename = plugin_basename( $plugin_file );

		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_for_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_source_dir' ), 10, 4 );

		/*
		 * "Check again" on Dashboard > Updates (and WordPress' own scheduled
		 * check) both work by deleting WordPress' own update_plugins
		 * transient, which is what makes every plugin's update check re-run -
		 * but our own GitHub lookup is cached in a second, separate transient
		 * with its own twelve-hour lifetime, so a "check again" click did not
		 * reach it: WordPress' cache cleared, ours did not, and
		 * check_for_update() kept returning whatever version was cached
		 * against WordPress' now-fresh check. Clearing ours on the same
		 * action WordPress fires when its own transient is deleted is what
		 * makes "check again" actually mean "check again" here too.
		 */
		add_action( 'delete_site_transient_update_plugins', array( __CLASS__, 'clear_cache' ) );
	}

	/**
	 * Drop the cached GitHub lookup so the next update check hits the API
	 * again instead of reusing a possibly-stale result.
	 */
	public static function clear_cache(): void {
		delete_site_transient( self::VERSION_CACHE_KEY );
	}

	/**
	 * Injects an available update into WordPress' own transient, the same one
	 * every wordpress.org-hosted plugin populates, so the normal update UI
	 * (Plugins list, Dashboard > Updates, "Update now") works unmodified.
	 */
	public static function check_for_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new \stdClass();
		}

		$remote = self::get_remote_version();

		if ( ! $remote || ! version_compare( $remote['version'], VERSION, '>' ) ) {
			return $transient;
		}

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}

		$transient->response[ self::$plugin_basename ] = (object) array(
			'slug'        => self::PLUGIN_DIR,
			'plugin'      => self::$plugin_basename,
			'new_version' => $remote['version'],
			'url'         => self::repo_url(),
			'package'     => self::archive_url( $remote['tag'] ),
			'tested'      => get_bloginfo( 'version' ),
		);

		return $transient;
	}

	/**
	 * Supplies the "View version x.y.z details" popup WordPress opens from the
	 * update notice, reading the changelog straight from the tagged commit.
	 */
	public static function plugin_info( $result, string $action = '', $args = null ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::PLUGIN_DIR !== $args->slug ) {
			return $result;
		}

		$remote = self::get_remote_version();

		if ( ! $remote ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Devadigm Testimonials',
			'slug'          => self::PLUGIN_DIR,
			'version'       => $remote['version'],
			'author'        => '<a href="https://github.com/' . self::REPO_OWNER . '">' . self::REPO_OWNER . '</a>',
			'homepage'      => self::repo_url(),
			'requires'      => '6.7',
			'tested'        => get_bloginfo( 'version' ),
			'requires_php'  => '8.1',
			'download_link' => self::archive_url( $remote['tag'] ),
			'sections'      => array(
				'changelog' => self::get_changelog_html( $remote['tag'] ),
			),
		);
	}

	/**
	 * The archive GitHub generates for a tag has the whole repository under
	 * one top-level folder, which WordPress' installer already flattens to
	 * automatically - this filter runs after that, once $source is that
	 * flattened repository root. From there the plugin itself is one level
	 * further down, so this moves `devadigm-testimonials/` up to be the
	 * source WordPress installs, and leaves everything else in the download
	 * (docs, sample data) behind.
	 */
	public static function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		if ( empty( $hook_extra['plugin'] ) || self::$plugin_basename !== $hook_extra['plugin'] ) {
			return $source;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem || ! $wp_filesystem->is_dir( $source ) ) {
			return $source;
		}

		$plugin_main_file = basename( self::$plugin_basename );

		// Already the plugin's own folder - e.g. a hand-uploaded zip. Nothing to fix.
		if ( $wp_filesystem->exists( trailingslashit( $source ) . $plugin_main_file ) ) {
			return $source;
		}

		$nested = trailingslashit( $source ) . self::PLUGIN_DIR . '/';

		if ( ! $wp_filesystem->is_dir( $nested ) || ! $wp_filesystem->exists( $nested . $plugin_main_file ) ) {
			// Not the shape this updater expects. Leave WordPress to fail on
			// its own rather than guess further and risk installing garbage.
			return $source;
		}

		$corrected = trailingslashit( $remote_source ) . self::PLUGIN_DIR . '/';

		if ( $wp_filesystem->exists( $corrected ) ) {
			$wp_filesystem->delete( $corrected, true );
		}

		if ( ! $wp_filesystem->move( $nested, $corrected ) ) {
			return $source;
		}

		$wp_filesystem->delete( $source, true );

		return $corrected;
	}

	/**
	 * The highest semver-looking tag in the repository, cached for twelve
	 * hours so an update check does not mean a GitHub API call on every page
	 * load - `false` is cached too, on failure, so a GitHub outage cannot
	 * turn into a request storm.
	 */
	private static function get_remote_version(): ?array {
		$cached = get_site_transient( self::VERSION_CACHE_KEY );

		if ( false !== $cached ) {
			return $cached ?: null;
		}

		$response = wp_remote_get(
			sprintf( 'https://api.github.com/repos/%s/%s/tags', self::REPO_OWNER, self::REPO_NAME ),
			array(
				'headers' => array( 'Accept' => 'application/vnd.github+json' ),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( self::VERSION_CACHE_KEY, false, HOUR_IN_SECONDS );
			return null;
		}

		$tags = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $tags ) ) {
			set_site_transient( self::VERSION_CACHE_KEY, false, HOUR_IN_SECONDS );
			return null;
		}

		$best = null;

		foreach ( $tags as $tag ) {
			$name    = (string) ( $tag['name'] ?? '' );
			$version = ltrim( $name, 'v' );

			if ( '' === $version || ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
				continue;
			}

			if ( null === $best || version_compare( $version, $best['version'], '>' ) ) {
				$best = array(
					'version' => $version,
					'tag'     => $name,
				);
			}
		}

		set_site_transient( self::VERSION_CACHE_KEY, $best, 12 * HOUR_IN_SECONDS );

		return $best;
	}

	private static function get_changelog_html( string $tag ): string {
		$cache_key = self::CHANGELOG_CACHE_KEY . '_' . $tag;
		$cached    = get_site_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get(
			sprintf(
				'https://raw.githubusercontent.com/%s/%s/%s/%s/CHANGELOG.md',
				self::REPO_OWNER,
				self::REPO_NAME,
				$tag,
				self::PLUGIN_DIR
			),
			array( 'timeout' => 10 )
		);

		$html = '<p>See <a href="' . esc_url( self::repo_url() ) . '">the plugin repository</a> for the full changelog.</p>';

		if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$body = (string) wp_remote_retrieve_body( $response );
			$html = '<pre style="white-space:pre-wrap;">' . esc_html( $body ) . '</pre>';
		}

		set_site_transient( $cache_key, $html, 12 * HOUR_IN_SECONDS );

		return $html;
	}

	private static function repo_url(): string {
		return sprintf( 'https://github.com/%s/%s', self::REPO_OWNER, self::REPO_NAME );
	}

	private static function archive_url( string $tag ): string {
		return sprintf( '%s/archive/refs/tags/%s.zip', self::repo_url(), rawurlencode( $tag ) );
	}
}
