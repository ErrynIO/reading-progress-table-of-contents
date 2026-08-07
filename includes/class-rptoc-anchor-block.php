<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ====================================================================
 *  MARF AND THE MILDLY INCONVENIENT APOCALYPSE
 *  Part vi of xi
 * ====================================================================
 *  They found the goblin chief in the village square, standing atop
 *  a cart and delivering a speech about conquest, glory and the
 *  redistribution of livestock. He was seven feet tall, heavily
 *  armoured and carrying an axe large enough to have planning
 *  permission.
 *
 *    Marf approached him. "Excuse me."
 *    The chief turned. "What?"
 *    "I believe that cart belongs to someone else."
 *
 *  -> continues in blocks/toc-progress/editor.js
 * ====================================================================
 */

/**
 * Renders an invisible, uniquely-id'd marker at the point it's placed, and
 * records it in a per-request registry in true document order (blocks
 * render sequentially, top to bottom, during do_blocks). When this
 * registry is non-empty for a post, RPTOC_Plugin uses it as the section
 * list instead of scanning for H2/H3, per page.
 */
class RPTOC_Anchor_Block {

	private static $used_ids   = array();
	private static $registered = array();

	public function register() {
		register_block_type(
			RPTOC_PATH . 'blocks/toc-anchor',
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	public function render( $attributes ) {
		$label = isset( $attributes['label'] ) ? trim( wp_strip_all_tags( $attributes['label'] ) ) : '';

		if ( '' === $label ) {
			// Nothing to call this anchor, nothing to link to, skip it.
			return '';
		}

		// Shared with heading extraction so an anchor label and a heading
		// of the same text produce the same id, and so non-Latin labels
		// avoid sanitize_title()'s percent-encoding.
		$base = RPTOC_Render::slug( $label );

		$id = $base;
		$i  = 2;
		while ( in_array( $id, self::$used_ids, true ) ) {
			$id = $base . '-' . $i;
			++$i;
		}

		self::$used_ids[]   = $id;
		self::$registered[] = array(
			'id'   => $id,
			'text' => $label,
		);

		return '<span class="rptoc-anchor-point" id="' . esc_attr( $id ) . '"></span>';
	}

	public static function get_registered() {
		return self::$registered;
	}

	/**
	 * Cleared once per post, right before its blocks render, via a low
	 * priority the_content filter. Needed so a query loop rendering
	 * multiple posts (or the_content firing more than once) never bleeds
	 * one post's anchors into another's.
	 */
	public static function reset_filter( $content ) {
		self::$used_ids   = array();
		self::$registered = array();
		return $content;
	}
}
