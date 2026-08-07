<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ====================================================================
 *  MARF AND THE MILDLY INCONVENIENT APOCALYPSE
 *  Part iv of xi
 * ====================================================================
 *  Outside, the village was being attacked by goblins. This was
 *  immediately apparent from the screaming, the fires, and a
 *  handwritten sign reading:
 *
 *    VILLAGE CURRENTLY BEING ATTACKED BY GOBLINS
 *    PLEASE USE ALTERNATIVE ROUTE
 *
 *  A translucent message appeared before Marf.
 *
 *    [ New Quest: Save the Village ]
 *    Recommended Level:  8
 *    Marf's Level:       1
 *    Suggested Strategy: Do not.
 *
 *  Marf dismissed the message.
 *
 *  -> continues in includes/class-rptoc-block.php
 * ====================================================================
 */

class RPTOC_Render {

	/**
	 * How deep the pill strip will render. Levels below this still get an
	 * id and still appear in the schema; they just stop indenting, since
	 * a horizontal strip runs out of visual room for depth long before
	 * six levels of it.
	 */
	const MAX_VISUAL_DEPTH = 4;

	/**
	 * Walk the rendered content once, find headings at the allowed levels,
	 * assign an id to any that lacks one (uniquified), and collect a flat,
	 * ordered list. IDs and extraction happen in the same pass so the
	 * returned heading list always matches what actually landed in the
	 * markup. Heading levels outside $allowed_levels are left untouched
	 * and never counted.
	 *
	 * @param string $content        Fully rendered post content.
	 * @param array  $allowed_levels e.g. array( 2, 3 ) or array( 2, 3, 4, 5, 6 ).
	 * @param string $exclude_class  Optional class name; headings carrying it are skipped.
	 * @return array { 'content' => string, 'headings' => array }
	 */
	public static function process_content( $content, $allowed_levels, $exclude_class = '' ) {
		$used_ids = array();
		$headings = array();

		$pattern = '/<h([2-6])((?:\s+[^>]*)?)>(.*?)<\/h\1>/is';

		$modified = preg_replace_callback(
			$pattern,
			function ( $m ) use ( &$used_ids, &$headings, $allowed_levels, $exclude_class ) {
				$level = (int) $m[1];

				if ( ! in_array( $level, $allowed_levels, true ) ) {
					return $m[0];
				}

				$attrs = $m[2];
				$inner = $m[3];

				// Opt-out class. Matched against the class attribute's
				// whitespace-separated tokens, so "no-toc" never matches
				// "no-toc-wrapper" the way a substring check would.
				if ( '' !== $exclude_class && self::has_class( $attrs, $exclude_class ) ) {
					return $m[0];
				}

				$text = trim( wp_strip_all_tags( $inner ) );
				if ( '' === $text ) {
					return $m[0];
				}

				$id = '';
				if ( preg_match( '/\sid=["\']([^"\']+)["\']/i', $attrs, $idm ) ) {
					$id = $idm[1];
				}

				if ( '' === $id ) {
					$base = self::slug( $text );
					$id   = $base;
					$i    = 2;
					while ( in_array( $id, $used_ids, true ) ) {
						$id = $base . '-' . $i;
						++$i;
					}
					$attrs .= ' id="' . esc_attr( $id ) . '"';
				}

				$used_ids[] = $id;

				$headings[] = array(
					'level' => $level,
					'id'    => $id,
					'text'  => $text,
				);

				return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
			},
			$content
		);

		return array(
			'content'  => null === $modified ? $content : $modified,
			'headings' => $headings,
		);
	}

	/**
	 * Does this attribute string carry the given class as a whole token?
	 */
	private static function has_class( $attrs, $class ) {
		if ( ! preg_match( '/\sclass=["\']([^"\']*)["\']/i', $attrs, $m ) ) {
			return false;
		}
		$classes = preg_split( '/\s+/', trim( $m[1] ) );
		return in_array( $class, (array) $classes, true );
	}

	/**
	 * Anchor id from heading text.
	 *
	 * sanitize_title() percent-encodes non-Latin scripts, which produces
	 * ids like %d0%bf%d1%80... and, worse, publishes them as schema URLs.
	 * So for text that has no usable Latin slug, the raw text is used
	 * instead, lowercased with whitespace collapsed to dashes. HTML5 ids
	 * accept any character except whitespace, and browsers resolve UTF-8
	 * fragments fine, so a Cyrillic or CJK heading gets a readable
	 * anchor instead of an encoded one.
	 */
	public static function slug( $text ) {
		$slug = sanitize_title( $text );

		// Percent-encoded output means sanitize_title found nothing it
		// could transliterate: fall back rather than ship the encoding.
		if ( '' === $slug || false !== strpos( $slug, '%' ) ) {
			$raw = preg_replace( '/\s+/u', '-', trim( wp_strip_all_tags( $text ) ) );
			$raw = preg_replace( '/["\'<>&\/\\\\#]/u', '', (string) $raw );

			if ( function_exists( 'mb_strtolower' ) ) {
				$raw = mb_strtolower( $raw, 'UTF-8' );
			} else {
				$raw = strtolower( $raw );
			}

			$slug = trim( (string) $raw, '-' );
		}

		return '' !== $slug ? $slug : 'section';
	}

	/**
	 * Nests each heading under the nearest preceding heading of a shallower
	 * level, whatever the selected levels happen to be. A stack rather than
	 * the old fixed last_h2/last_h3 pair, because with H2 to H6 selectable
	 * there is no fixed number of tiers, and because the selected set can
	 * skip levels entirely (H2 and H5 with nothing between them).
	 *
	 * Orphans, meaning anything with no shallower heading above it, become
	 * top-level entries so nothing is ever dropped.
	 */
	public static function nest( $headings ) {
		$cursor = 0;
		// Level 1 as the notional parent: every selectable level (2 to 6)
		// is deeper than it, so a document opening on an H5 still gets a
		// top-level entry rather than being dropped for having no parent.
		return self::build( $headings, $cursor, 1 );
	}

	/**
	 * Recursive descent over the flat list. Consumes headings deeper than
	 * $parent_level as children, and returns the moment it meets one that
	 * is not, leaving it for the caller up the stack.
	 *
	 * Only the cursor is passed by reference, and it's a plain integer.
	 * Building the tree by holding references into nested arrays is the
	 * obvious alternative and a reliable way to get bitten, since PHP's
	 * copy-on-write and reference semantics disagree about what a nested
	 * array element is.
	 *
	 * @param array $headings     Flat, ordered list.
	 * @param int   $cursor       Read position, by reference.
	 * @param int   $parent_level Level of the node these will hang under.
	 */
	private static function build( $headings, &$cursor, $parent_level ) {
		$out   = array();
		$total = count( $headings );

		while ( $cursor < $total ) {
			$h = $headings[ $cursor ];

			if ( $h['level'] <= $parent_level ) {
				break;
			}

			++$cursor;
			$h['children'] = self::build( $headings, $cursor, $h['level'] );
			$out[]         = $h;
		}

		return $out;
	}

	/**
	 * Flat anchor list (from RPTOC_Anchor_Block) doesn't nest, every entry
	 * is a top-level pill. Shaped to match nest()'s output so widget_html()
	 * doesn't need to know which source it came from.
	 */
	public static function anchors_to_nested( $anchors ) {
		return array_map(
			function ( $a ) {
				$a['level']    = 2;
				$a['children'] = array();
				return $a;
			},
			$anchors
		);
	}

	/**
	 * Build the widget markup plus its JSON-LD ItemList, as a single string
	 * ready to append to post content. Position is fixed via CSS and
	 * corrected at runtime by JS (see toc-progress.js), so where this
	 * string physically lands inside the content is irrelevant to the
	 * reader.
	 *
	 * Two layouts share this one structure. The bar is the full-width
	 * strip. The rail is the Medium-style edge minimap: a disclosure
	 * button of ticks that opens a popover holding the same list. The
	 * markup is deliberately common to both, because below a breakpoint
	 * the rail falls back to the bar, and a shared structure means that
	 * fallback is pure CSS rather than a second render.
	 *
	 * @param array  $nested            Output of nest() or anchors_to_nested().
	 * @param array  $flat_headings     Flat, ordered list, for the ticks and schema.
	 * @param string $permalink
	 * @param string $style             'pills' or 'text'.
	 * @param string $align             'left', 'center' or 'right'.
	 * @param string $progress_position 'above' or 'below' the links.
	 * @param string $dock              'bottom' or 'header'.
	 * @param string $layout            'bar' or 'rail'.
	 * @param string $rail_side         'left' or 'right' (rail layout only).
	 * @param bool   $schema_enabled    Whether to emit the ItemList structured data.
	 */
	public static function widget_html( $nested, $flat_headings, $permalink, $style, $align, $progress_position, $dock, $layout = 'bar', $rail_side = 'right', $schema_enabled = true, $shadow = false ) {
		$is_rail = ( 'rail' === $layout );

		$classes = array( 'rptoc' );
		if ( 'text' === $style ) {
			$classes[] = 'rptoc--text';
		}
		$classes[] = 'rptoc--align-' . $align;
		$classes[] = 'rptoc--dock-' . $dock;
		if ( $shadow ) {
			$classes[] = 'rptoc--shadow';
		}
		if ( $is_rail ) {
			$classes[] = 'rptoc--rail';
			$classes[] = 'rptoc--rail-' . ( 'left' === $rail_side ? 'left' : 'right' );
		}
		$nav_class = implode( ' ', $classes );

		$progress_html = '<div class="rptoc__progress" aria-hidden="true"><span class="rptoc__progress-fill" data-rptoc-fill></span></div>';

		// The list is identical in both layouts. In the bar it is the
		// horizontal track; in the rail it becomes the popover contents.
		$list_html = self::render_items( $nested, 1 );

		if ( $is_rail ) {
			// wp_unique_id() gives a per-request unique suffix so several
			// widgets on one page (unlikely, but a query loop could) don't
			// share an aria-controls target.
			$popover_id = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'rptoc-popover-' ) : 'rptoc-popover-' . wp_rand( 1000, 99999 );

			$toggle_label = esc_attr__( 'Table of contents', 'erryn-reading-progress-table-of-contents' );

			$rail_html  = '<button type="button" class="rptoc__rail-toggle" aria-label="' . $toggle_label . '" aria-expanded="false" aria-controls="' . esc_attr( $popover_id ) . '" data-rptoc-rail-toggle>';
			$rail_html .= self::render_ticks( $flat_headings );
			$rail_html .= '</button>';

			$panel_html = '<div class="rptoc__panel" id="' . esc_attr( $popover_id ) . '" data-rptoc-panel>';
			$panel_html .= '<div class="rptoc__track" data-rptoc-track>' . $list_html . '</div>';
			$panel_html .= '</div>';

			// Progress element is still emitted so the mobile bar fallback
			// keeps its fill; CSS hides it in the desktop rail. Ordering
			// follows progress_position for that fallback's sake.
			$inner = $rail_html;
			$inner = ( 'below' === $progress_position )
				? $inner . $panel_html . $progress_html
				: $progress_html . $inner . $panel_html;
		} else {
			$track_html = '<div class="rptoc__track" data-rptoc-track>' . $list_html . '</div>';
			$inner      = ( 'below' === $progress_position )
				? $track_html . $progress_html
				: $progress_html . $track_html;
		}

		ob_start();
		?>
		<nav class="<?php echo esc_attr( $nav_class ); ?>" data-rptoc aria-label="<?php esc_attr_e( 'Table of contents', 'erryn-reading-progress-table-of-contents' ); ?>">
			<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput -- assembled from esc_*() calls above. ?>
		</nav>
		<?php
		$html = ob_get_clean();

		$items    = array();
		$position = 1;
		foreach ( $flat_headings as $h ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => $h['text'],
				'url'      => $permalink . '#' . rawurlencode( $h['id'] ),
			);
			++$position;
		}

		if ( $schema_enabled && ! empty( $items ) ) {
			$schema = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'ItemList',
				'itemListElement' => $items,
			);

			$html .= "\n" . '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}

		return $html;
	}

	/**
	 * The rail's ticks, one per heading in document order. Decorative by
	 * design: they sit inside the disclosure button, so they carry no
	 * interactivity of their own (a button may not contain links), and the
	 * actual jumping happens from the popover rows. Each tick still names
	 * its target so the active-section observer can light the right one.
	 *
	 * Tick length is driven by heading level, so a glance at the rail
	 * reads as document shape rather than an undifferentiated barcode.
	 */
	private static function render_ticks( $flat ) {
		$out = '<span class="rptoc__ticks" aria-hidden="true">';

		foreach ( $flat as $h ) {
			$level = isset( $h['level'] ) ? (int) $h['level'] : 2;
			$level = max( 2, min( 6, $level ) );

			$out .= '<span class="rptoc__tick rptoc__tick--l' . $level . '" data-target="' . esc_attr( $h['id'] ) . '"></span>';
		}

		$out .= '</span>';

		return $out;
	}

	/**
	 * Renders the list recursively. Previously three hand-written tiers,
	 * which was fine while H2 to H4 was the whole story and stopped being
	 * fine the moment H5 and H6 became selectable.
	 *
	 * Depth drives a modifier class rather than a distinct element name,
	 * so the stylesheet needs one rule per depth instead of a new class
	 * per level, and depth beyond MAX_VISUAL_DEPTH simply stops indenting
	 * rather than marching off the side of the strip.
	 */
	private static function render_items( $items, $depth ) {
		if ( empty( $items ) ) {
			return '';
		}

		$visual = min( $depth, self::MAX_VISUAL_DEPTH );

		$out = '<ul class="rptoc__list rptoc__list--depth-' . (int) $visual . '">';

		foreach ( $items as $item ) {
			$out .= '<li class="rptoc__item rptoc__item--depth-' . (int) $visual . '">';
			$out .= '<a href="#' . esc_attr( $item['id'] ) . '"';
			$out .= ' class="rptoc__pill rptoc__pill--depth-' . (int) $visual . '"';
			$out .= ' data-target="' . esc_attr( $item['id'] ) . '">';
			$out .= esc_html( $item['text'] );
			$out .= '</a>';

			if ( ! empty( $item['children'] ) ) {
				$out .= self::render_items( $item['children'], $depth + 1 );
			}

			$out .= '</li>';
		}

		$out .= '</ul>';

		return $out;
	}
}
