<?php
/**
 * Dependency manifest for the block editor script.
 *
 * Hand written rather than generated, because the plugin ships without a build
 * step: the editor script uses wp.element.createElement directly instead of JSX.
 *
 * @package Devadigm\Testimonials
 */

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-block-editor',
		'wp-components',
		'wp-element',
		'wp-i18n',
		'wp-server-side-render',
	),
	'version'      => '1.0.0',
);
