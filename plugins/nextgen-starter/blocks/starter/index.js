(function (blocks, element, blockEditor, i18n) {
	'use strict';

	var registerBlockType = blocks.registerBlockType;
	var createElement = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var RichText = blockEditor.RichText;
	var __ = i18n.__;

	registerBlockType('nextgen/starter', {
		edit: function (props) {
			return createElement(
				'div',
				useBlockProps(),
				createElement(RichText, {
					tagName: 'p',
					value: props.attributes.message,
					allowedFormats: [],
					placeholder: __('Text eingeben …', 'nextgen-starter'),
					onChange: function (value) {
						props.setAttributes({ message: value });
					}
				})
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.i18n);
