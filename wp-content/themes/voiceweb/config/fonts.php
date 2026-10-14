<?php
/**
 * Front-end font configuration by language.
 *
 * This is the only place where text font stacks and language-specific font
 * stylesheets should be selected. Language keys may be a locale (for example
 * "fa_IR") or a language code (for example "fa").
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	// Used when the current language has no dedicated entry below.
	'default' => array(
		'family'      => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
		'stylesheets' => array(),
		'font_faces'  => array(),
		'preload'     => array(),
	),

	// Persian: three existing, self-hosted WOFF2 weights with Persian numerals.
	// Preload only regular; medium and bold are fetched when the page uses them.
	'fa' => array(
		'family'      => '"Dana", Tahoma, sans-serif',
		'stylesheets' => array(),
		'font_faces'  => array(
			array(
				'family'  => 'Dana',
				'path'    => 'assets/fonts/dana/DanaFaNum-Regular.woff2',
				'format'  => 'woff2',
				'weight'  => '400',
				'style'   => 'normal',
				'display' => 'swap',
				'preload' => true,
			),
			array(
				'family'  => 'Dana',
				'path'    => 'assets/fonts/dana/DanaFaNum-Medium.woff2',
				'format'  => 'woff2',
				'weight'  => '500',
				'style'   => 'normal',
				'display' => 'swap',
				'preload' => false,
			),
			array(
				'family'  => 'Dana',
				'path'    => 'assets/fonts/dana/DanaFaNum-Bold.woff2',
				'format'  => 'woff2',
				'weight'  => '700',
				'style'   => 'normal',
				'display' => 'swap',
				'preload' => false,
			),
		),
		'preload'     => array(),
	),

	// English: browser-safe Latin stack; no additional font download required.
	'en'      => array(
		'family'      => 'Arial, Helvetica, sans-serif',
		'stylesheets' => array(),
		'font_faces'  => array(),
		'preload'     => array(),
	),
);
