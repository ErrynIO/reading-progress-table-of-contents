/*
 * ====================================================================
 *  MARF AND THE MILDLY INCONVENIENT APOCALYPSE
 *  Part vii of xi
 * ====================================================================
 *  The goblin stared at him. Then he laughed. The other goblins
 *  laughed.
 *
 *    Erryn floated beside Marf. "You have successfully lowered their
 *    guard."
 *    "That was the plan."
 *    "It clearly was not."
 *
 *  The goblin chief swung his axe. Marf ducked. The blade struck the
 *  cart, split the axle and sent the chief tumbling backwards into
 *  the village fountain, where his armour wedged firmly between two
 *  decorative stone fish. There was a long silence.
 *
 *    [ Goblin Chief Immobilised ]
 *
 *  -> continues in assets/js/admin.js
 * ====================================================================
 */

( function () {
	var el            = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;

	wp.blocks.registerBlockType( 'rptoc/toc-progress', {
		edit: function () {
			var blockProps = useBlockProps( { className: 'rptoc-editor-placeholder' } );
			return el(
				'div',
				blockProps,
				el( 'strong', {}, 'TOC & Progress Bar' ),
				el(
					'p',
					{},
					'Renders automatically, fixed to the bottom of the viewport, built from this page\u2019s H2/H3 headings. Nothing to configure here; thresholds and post types live on the plugin\u2019s settings page.'
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )();
