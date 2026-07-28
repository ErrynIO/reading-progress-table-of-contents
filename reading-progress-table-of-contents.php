<?php
/**
 * Plugin Name: Reading Progress & Table of Contents
 * Plugin URI: https://erryn.io/products/reading-progress-table-of-contents/
 * Description: A lightweight progress indicator and navigable table of contents for WordPress.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Erryn Deane
 * Author URI: https://erryn.io
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: reading-progress-table-of-contents
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ====================================================================
 *  MARF AND THE MILDLY INCONVENIENT APOCALYPSE
 *  Part i of xi
 * ====================================================================
 *  Marf woke beneath a tavern table with a sword in his hand, a
 *  headache behind his eyes and no memory of acquiring either.
 *
 *  A small metal figure floated above him. It was shaped like a
 *  pixie, if pixies had been designed by an engineer going through a
 *  difficult divorce. Its wings emitted a tired blue light. Its face
 *  displayed the resigned expression of someone who had already read
 *  the terms and conditions.
 *
 *    "You're alive," it said.
 *    Marf sat up. "Was there doubt?"
 *    "There remains doubt."
 *
 *    [ Companion Identified: ERRYN ]
 *    Type:              Pixie Android
 *    Disposition:       Clinically unimpressed
 *    Primary Function:  Guidance
 *    Secondary:         Making guidance feel like criticism
 *
 *  -> continues in includes/class-rptoc-plugin.php
 * ====================================================================
 */

define( 'RPTOC_VERSION', '1.0.0' );
define( 'RPTOC_PATH', plugin_dir_path( __FILE__ ) );
define( 'RPTOC_URL', plugin_dir_url( __FILE__ ) );

require_once RPTOC_PATH . 'includes/class-rptoc-settings.php';
require_once RPTOC_PATH . 'includes/class-rptoc-render.php';
require_once RPTOC_PATH . 'includes/class-rptoc-block.php';
require_once RPTOC_PATH . 'includes/class-rptoc-anchor-block.php';
require_once RPTOC_PATH . 'includes/class-rptoc-plugin.php';

/**
 * Boot.
 */
function rptoc_run() {
	new RPTOC_Plugin();
}
add_action( 'plugins_loaded', 'rptoc_run' );

/**
 * Settings are rendered inline into every page, so a saved change only
 * shows once the full-page cache is cleared. This fires the purge for the
 * common caches when the option is written, so a change is live without a
 * trip to the cache plugin. Each call is a no-op when that plugin is absent:
 * the LiteSpeed action has no listener, and the functions are guarded.
 *
 * Note this cannot reach a CDN edge, LiteSpeed's separate CSS/JS
 * optimisation store, or a browser cache. A version bump handles the
 * asset side; the rest is on the host's own purge.
 */
function rptoc_purge_caches() {
	// LiteSpeed Cache's own documented purge action. Prefixing does not
	// apply: this fires a third-party hook rather than declaring one.
	do_action( 'litespeed_purge_all' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third-party (LiteSpeed) action.

	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
	}
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
}
add_action( 'update_option_' . RPTOC_Settings::OPTION, 'rptoc_purge_caches' );


/**
 * RankMath has no way to detect a TOC from page HTML (its own docs say so),
 * so it checks a hardcoded whitelist of known TOC plugin files instead.
 * This registers this plugin against that whitelist so the "Content
 * Readability > Table of Contents" check passes, the same mechanism
 * RankMath documents for any third-party TOC plugin it doesn't already
 * know about: https://rankmath.com/kb/table-of-contents-not-detected/
 */
add_filter(
	'rank_math/researches/toc_plugins',
	function ( $toc_plugins ) {
		$toc_plugins[ plugin_basename( __FILE__ ) ] = 'Reading Progress & Table of Contents';
		return $toc_plugins;
	}
);
