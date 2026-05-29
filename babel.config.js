const path = require( 'path' );
const browserslist = require( 'browserslist' );
const localBrowserslistConfig = browserslist.findConfig( '.' ) || {};
targets = browserslist(
	localBrowserslistConfig.defaults || require( '@wordpress/browserslist-config' )
);

const config = {
	presets: [
		[
			require.resolve( '@babel/preset-env' ),
			{
				targets,
				// Exclude transforms that make all code slower, see https://github.com/facebook/create-react-app/pull/5278
				exclude: [ 'transform-typeof-symbol' ],
			},
		],
		[
			require.resolve( '@babel/preset-react' ),
			{ runtime: 'automatic' },
		],
		[ require.resolve( '@babel/preset-typescript' ) ],
	],
	plugins: [
		[
			require.resolve( '@automattic/babel-plugin-replace-textdomain' ),
			{ textdomain: 'zero-bs-crm' }
		],
		[
			require.resolve( '@babel/plugin-transform-runtime' ),
			{
				regenerator: false,
				absoluteRuntime: path.dirname( __dirname ),
				version: require( '@babel/runtime/package.json' )?.version,
			},
		],
		[
			require.resolve( '@automattic/babel-plugin-preserve-i18n' ),
		],
	],
};

module.exports = config;
