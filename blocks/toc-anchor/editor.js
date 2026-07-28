( function () {
	var el            = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var TextControl    = wp.components.TextControl;

	wp.blocks.registerBlockType( 'rptoc/toc-anchor', {
		edit: function ( props ) {
			var attributes   = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps   = useBlockProps( { className: 'rptoc-anchor-editor' } );

			return el(
				'div',
				blockProps,
				el( 'span', { className: 'rptoc-anchor-editor__icon' }, '\uD83D\uDD16' ),
				el( TextControl, {
					label: 'TOC entry label',
					value: attributes.label,
					placeholder: 'e.g. Pricing',
					onChange: function ( value ) {
						setAttributes( { label: value } );
					},
					help: 'If any TOC Anchor blocks exist on this page, the TOC & Progress bar is built from these instead of H2/H3 headings.'
				} )
			);
		},
		save: function () {
			return null;
		}
	} );
} )();
