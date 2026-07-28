/**
 * Stylelint configuration.
 *
 * Deliberately narrow: this lints WordPress Design System token usage only, and
 * intentionally does not `extend` a style guide. The Sass sources predate any
 * linting and a full rule set would bury the token rules under thousands of
 * pre-existing formatting errors.
 *
 * - no-unknown-ds-tokens catches `--wpds-*` names that don't exist in the
 *   installed @wordpress/theme. This is the rule that would have caught the
 *   0.13.0 -> 1.0.0 `bg`/`fg` -> `background`/`foreground` rename.
 * - no-token-fallback-values keeps hardcoded fallbacks out of `var()`. The token
 *   table is inlined into the same compiled stylesheet that references it, so a
 *   fallback can never be reached, and six of them had silently drifted out of
 *   sync with the tokens they shadowed.
 */
export default {
	// The default CSS parser chokes on `//` comments and `@use`.
	customSyntax: 'postcss-scss',

	plugins: [
		'@wordpress/theme/stylelint-plugins/no-unknown-ds-tokens',
		'@wordpress/theme/stylelint-plugins/no-token-fallback-values',
	],

	rules: {
		'plugin-wpds/no-unknown-ds-tokens': true,
		'plugin-wpds/no-token-fallback-values': true,
	},
};
