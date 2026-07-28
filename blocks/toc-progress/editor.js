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
