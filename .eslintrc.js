module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	env: {
		browser: true,
	},
	globals: {
		elmAdminData: 'readonly',
		wp: 'readonly',
	},
	rules: {
		'jsdoc/require-param': 'off',
	},
};
