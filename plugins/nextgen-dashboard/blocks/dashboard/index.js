(function (blocks, element, blockEditor, i18n) {
	'use strict';

	var registerBlockType = blocks.registerBlockType;
	var createElement = element.createElement;
	var useBlockProps = blockEditor.useBlockProps;
	var __ = i18n.__;

	registerBlockType('nextgen/dashboard', {
		edit: function () {
			return createElement(
				'div',
				useBlockProps({
					style: {
						padding: '24px',
						border: '1px dashed #8b98a8',
						borderRadius: '12px',
						fontSize: '13px'
					}
				}),
				__('NextGen Dashboard', 'nextgen-dashboard')
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.i18n);
