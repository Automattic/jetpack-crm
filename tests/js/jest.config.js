/*
 * Jest config for the legacy browser JS in js/.
 *
 * These files are not modules — they define global functions and, at the
 * bottom, guard a `module.exports` so a test can require() them. Loading one
 * runs its top-level code, which touches jQuery and window, so we use the jsdom
 * environment (for window/document) and a small jQuery stub (tests/js/setup.js).
 *
 * Transforms go through babel-jest, which picks up the repo's babel.config.js —
 * the same config webpack builds with — so tests see the code the way it ships.
 */
const path = require( 'path' );

module.exports = {
	rootDir: path.resolve( __dirname, '../..' ),
	testEnvironment: 'jsdom',
	testMatch: [ '<rootDir>/tests/js/**/*.test.js' ],
	setupFiles: [ '<rootDir>/tests/js/setup.js' ],
};
