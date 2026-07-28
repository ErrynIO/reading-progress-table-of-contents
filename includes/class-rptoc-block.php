<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ====================================================================
 *  MARF AND THE MILDLY INCONVENIENT APOCALYPSE
 *  Part v of xi
 * ====================================================================
 *    "How many goblins?"
 *    Erryn's eyes flashed. "Thirty-seven."
 *    "Can I beat thirty-seven goblins?"
 *    "No."
 *    "Can we beat thirty-seven goblins?"
 *    Erryn considered this. "I could provide emotional support."
 *    "That sounds useful."
 *    "It wasn't intended to."
 *
 *  -> continues in includes/class-rptoc-anchor-block.php
 * ====================================================================
 */

/**
 * The block itself is a marker only. Its saved content is empty, and its
 * job is purely to let has_block( 'rptoc/toc-progress', $post ) detect intent
 * from the raw post_content. The actual widget HTML is built once, by
 * RPTOC_Plugin::filter_content(), from the same headings pass that adds
 * the anchor ids, so we never parse the content twice.
 */
class RPTOC_Block {

	public function register() {
		register_block_type(
			RPTOC_PATH . 'blocks/toc-progress',
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	public function render( $attributes, $content ) {
		return '';
	}
}
