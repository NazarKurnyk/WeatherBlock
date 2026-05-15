const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

/** @type {import('jest').Config} */
module.exports = {
	...defaultConfig,

	testMatch: [
		'<rootDir>/src/**/__tests__/**/*.{js,jsx}',
		'<rootDir>/src/**/*.test.{js,jsx}',
	],

	// Load jest-dom matchers (toBeInTheDocument, toBeChecked, etc.)
	setupFilesAfterEnv: [
		...( defaultConfig.setupFilesAfterEnv ?? [] ),
		'@testing-library/jest-dom',
	],

	collectCoverageFrom: [
		'src/**/*.{js,jsx}',
		'!src/**/index.js',
		'!src/**/__tests__/**',
	],

	// coverageThreshold (singular) — correct Jest option name.
	coverageThreshold: {
		global: {
			branches: 60,
			functions: 70,
			lines: 70,
			statements: 70,
		},
	},

	coverageReporters: [ 'text', 'lcov', 'clover' ],
};
