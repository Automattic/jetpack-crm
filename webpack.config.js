const path = require( 'path' );
const RemoveAssetWebpackPlugin = require( '@automattic/remove-asset-webpack-plugin' );
const CopyPlugin = require( 'copy-webpack-plugin' );
const { glob } = require( 'glob' );
const webpack = require( 'webpack' );
const CssMinimizerWebpackPlugin = require( 'css-minimizer-webpack-plugin' );
const DuplicatePackageCheckerWebpackPlugin = require( '@cerner/duplicate-package-checker-webpack-plugin' );
const I18nCheckWebpackPlugin = require( '@automattic/i18n-check-webpack-plugin' );
const MiniCssExtractWebpackPlugin = require( 'mini-css-extract-plugin' );
const MiniCSSWithRTLWebpackPlugin = require( './tools/mini-css-with-rtl-webpack-plugin.js' );
const TerserPlugin = require( './tools/terser-webpack-plugin.js' );
const WebpackRTLWebpackPlugin = require( '@automattic/webpack-rtl-plugin' );
const doNotMinify = false;
const buildLibPath = path.resolve( __dirname, 'build/lib/' );

/**
 * Return an array with a list of our legacy '.js' files.
 *
 * Format: [ './full/path/file' => './full/path/file.js'].
 *
 * @return {Array} The list of js files that must be minified.
 */
function getLegacyJsEntries() {
	const patterns = [ 'js/**/*.js', 'modules/**/js/*.js' ];
	const ignorePatterns = [
		'**/js/**/*.min.js',
		// The js/lib directory contains directly "hosted" 3. party libraries.
		'**/lib/**',
	];

	const entries = {};
	glob.sync( `{${ patterns.join( ',' ) }}`, { ignore: ignorePatterns } ).forEach( file => {
		entries[ './' + file.substring( 0, file.length - '.js'.length ) ] = './' + file;
	} );

	return entries;
}

/**
 * Return an array with a list of our legacy '.scss' files.
 *
 * Format: [ './full/path/file' => './full/path/file.scss'].
 *
 * @param {boolean} minification - Whether or not the scss should be minified.
 *
 * @return {Array} The list of scss files that must be compiled and minified.
 */
function getLegacySassEntries( minification = true ) {
	const patterns = [ 'sass/**/*.scss', 'modules/**/sass/**/*.scss' ];
	const ignorePatterns = [
		'**/sass/**/_*.scss',
		/*
		 * All Welcome to ZBS styling is handled separately.
		 * @see getLegacyWelcomeZBSCSSEntries()
		 */
		'**/welcome-to-zbs/**',
	];

	const entries = {};
	glob.sync( `{${ patterns.join( ',' ) }}`, { ignore: ignorePatterns } ).forEach( file => {
		const newPath = file.replace( 'sass', 'css' );
		if ( minification ) {
			entries[ './' + newPath.substring( 0, newPath.length - '.scss'.length ) + '.min' ] =
				'./' + file;
		} else {
			entries[ './' + newPath.substring( 0, newPath.length - '.scss'.length ) ] = './' + file;
		}
	} );
	return entries;
}

/**
 * Return array with a list of our legacy 'welcome-to-zbs' 'css' file structure.
 *
 * Format: [ './full/path/file' => './full/path/file.css'].
 *
 * @return {Array} The list of css files that must be minified.
 */
function getLegacyWelcomeZBSCSSEntries() {
	const ignorePatterns = [ '**/welcome-to-zbs/*.min.css' ];

	const entries = {};
	glob.sync( 'css/welcome-to-zbs/*.css', { ignore: ignorePatterns } ).forEach( file => {
		entries[ './' + file.substring( 0, file.length - '.css'.length ) + '.min' ] = './' + file;
	} );
	return entries;
}

const isProduction = process.env.NODE_ENV === 'production';

const crmWebpackConfig = {
	mode: isProduction ? 'production' : 'development',
	devtool: false,
	output: {
		filename: '[name].js',
		chunkFilename: '[name].js?minify=false&ver=[contenthash]',
		path: path.resolve( __dirname, '.' ),
	},
	optimization: {
		minimize: isProduction,
		minimizer: [ TerserPlugin(), new CssMinimizerWebpackPlugin() ],
		mangleExports: false,
		concatenateModules: false,
		emitOnErrors: true,
	},
	resolve: {
		extensions: [ '.js', '.jsx', '.ts', '.tsx', '...' ],
	},
	node: false,
	plugins: [
		new webpack.DefinePlugin( {
			'process.env.FORCE_REDUCED_MOTION': 'false',
			global: 'window',
		} ),
		new DuplicatePackageCheckerWebpackPlugin(),
		new MiniCssExtractWebpackPlugin( {
			filename: '[name].css',
			chunkFilename: '[name].css?minify=false&ver=[contenthash]',
		} ),
		new MiniCSSWithRTLWebpackPlugin(),
		new webpack.IgnorePlugin( {
			resourceRegExp: /^\.\/locale$/,
			contextRegExp: /moment$/,
		} ),
		new WebpackRTLWebpackPlugin(),
		...( isProduction ? [
			new I18nCheckWebpackPlugin( {
				expectDomain: 'zero-bs-crm',
				extractorOptions: {
					babelOptions: {
						configFile: path.resolve( 'babel.config.js' ),
					},
				},
			} ),
		] : [] ),
	],
	module: {
		strictExportPresence: true,
		rules: [
			// Transpile JavaScript.
			{
				test: /\.(?:[jt]sx?|[cm]js)$/,
				exclude: [ /node_modules\//, /vendor\//, /tests\// ],
				use: [
					{
						loader: require.resolve( 'thread-loader' ),
					},
					{
						loader: require.resolve( 'babel-loader' ),
						options: {
							babelrc: false,
							cacheDirectory: path.resolve( '.cache/babel' ),
							cacheCompression: true,
							cacheIdentifier: 'babel-cache-development-false',
							configFile: path.resolve( 'babel.config.js' ),
						}
					}
				],
			},

			// Transpile @automattic/jetpack-* in node_modules too.
			{
				test: /\.(?:[jt]sx?|[cm]js)$/,
				include: file => {
					const modules = [ '@automattic/jetpack-' ];
					const i = file.lastIndexOf( '/node_modules/' ) + 14;
					return i >= 14 && modules.some( module => file.startsWith( module, i ) );
				},
				use: [
					{
						loader: require.resolve( 'thread-loader' ),
					},
					{
						loader: require.resolve( 'babel-loader' ),
						options: {
							babelrc: false,
							cacheDirectory: path.resolve( '.cache/babel' ),
							cacheCompression: true,
							cacheIdentifier: 'babel-cache-development-false',
							configFile: path.resolve( 'babel.config.js' ),
						}
					}
				]
			}
		],
	},
	externals: {
		jetpackConfig: JSON.stringify( {
			consumer_slug: 'zero-bs-crm',
		} ),
		'@wordpress/i18n': 'global wpI18n',
		'@wordpress/jp-i18n-loader': 'global jpI18nLoader',
	},
};

module.exports = [
	{
		...crmWebpackConfig,
		entry: getLegacyJsEntries(),
		output: {
			...crmWebpackConfig.output,
			filename: '[name].min.js',
			library: {
				name: 'window',
				type: 'assign-properties',
			},
		},
		optimization: {
			...crmWebpackConfig.optimization,
			minimize: true,
			minimizer: [
				TerserPlugin( {
					terserOptions: {
						mangle: {
							keep_fnames: true,
							keep_classnames: true,
						},
					},
					extractComments: isProduction,
				} ),
			],
		},
	},
	{
		...crmWebpackConfig,
		entry: getLegacySassEntries(),
		module: {
			...crmWebpackConfig.module,
			rules: [
				...crmWebpackConfig.module.rules,
				// Handle CSS.
				{
					test: /\.(?:css|sass|scss)$/i,
					sideEffects: true,
					use: [
						{
							loader: require( 'mini-css-extract-plugin' ).loader,
						},
						{
							loader: require.resolve( 'css-loader' ),
							options: {
								url: false,
								modules: { auto: true, localIdentName: '[local]--[hash:base64:5]' },
								importLoaders: 1
							}
						},
						{ loader: 'sass-loader', options: { api: 'modern-compiler' } },
					]
				},
			],
		},
		plugins: [
			...crmWebpackConfig.plugins,
			// Delete the dummy JS files Webpack would otherwise create.
			new RemoveAssetWebpackPlugin( {
				assets: /\.js(\.map)?$/,
			} ),
		],
	},
	{
		...crmWebpackConfig,
		mode: 'production',
		entry: getLegacySassEntries( doNotMinify ),
		optimization: {
			...crmWebpackConfig.optimization,
			minimize: false,
		},
		module: {
			...crmWebpackConfig.module,
			rules: [
				...crmWebpackConfig.module.rules,
				// // Handle CSS.
				{
					test: /\.(?:css|sass|scss)$/i,
					sideEffects: true,
					use: [
						{
							loader: require( 'mini-css-extract-plugin' ).loader,
						},
						{
							loader: require.resolve( 'css-loader' ),
							options: {
								url: false,
								modules: { auto: true, localIdentName: '[local]--[hash:base64:5]' },
								importLoaders: 1
							}
						},
						{ loader: 'sass-loader', options: { api: 'modern-compiler', sassOptions: { style: 'expanded' } } },
					]
				},
			],
		},
		plugins: [
			...crmWebpackConfig.plugins,
			// Delete the dummy JS files Webpack would otherwise create.
			new RemoveAssetWebpackPlugin( {
				assets: /\.js(\.map)?$/,
			} ),
		],
	},
	{
		...crmWebpackConfig,
		entry: getLegacyWelcomeZBSCSSEntries(),
		output: {
			...crmWebpackConfig.output,
		},
		module: {
			...crmWebpackConfig.module,
			rules: [
				...crmWebpackConfig.module.rules,
				// Handle CSS.
				{
					test: /\.(?:css|sass|scss)$/i,
					sideEffects: true,
					use: [
						{
							loader: require( 'mini-css-extract-plugin' ).loader,
						},
						{
							loader: require.resolve( 'css-loader' ),
							options: {
								url: false,
								modules: { auto: true, localIdentName: '[local]--[hash:base64:5]' },
								importLoaders: 0
							}
						},
					]
				},
			],
		},
		plugins: [
			...crmWebpackConfig.plugins,
			// Delete the dummy JS files Webpack would otherwise create.
			new RemoveAssetWebpackPlugin( {
				assets: /\.js(\.map)?$/,
			} ),
		],
	},
	// Copy third-party libraries into build dir.
	{
		...crmWebpackConfig,
		entry: {},
		output: {
			...crmWebpackConfig.output,
			path: path.resolve( __dirname, '.' ),
		},
		plugins: [
			new CopyPlugin( {
				patterns: [
					// Used by jpcrm-notifyme-front.js for notifications
					{
						from: path.resolve( __dirname, 'node_modules/js-cookie/dist/js.cookie.min.js' ),
						to: `${ buildLibPath }/js-cookie/`,
					},
					// Used by jpcrm-notifyme-front.js for notifications
					{
						from: path.resolve( __dirname, 'node_modules/push.js/bin/push.min.js' ),
						to: `${ buildLibPath }/push.js/`,
					},
					// Used by ZeroBSCRM.OnboardMe.php for the onboarding tour
					{
						from: path.resolve( __dirname, 'node_modules/hopscotch/dist/js/hopscotch.min.js' ),
						to: `${ buildLibPath }/hopscotch/js`,
					},
					// Used by ZeroBSCRM.OnboardMe.php for the onboarding tour
					{
						from: path.resolve( __dirname, 'node_modules/hopscotch/dist/css/hopscotch.min.css' ),
						to: `${ buildLibPath }/hopscotch/css`,
					},
					// Sprites used by hopscotch tour
					{
						from: path.resolve( __dirname, 'node_modules/hopscotch/dist/img' ),
						to: `${ buildLibPath }/hopscotch/img`,
					},
					// Used by extensively as a font icon
					{
						from: path.resolve( __dirname, 'node_modules/font-awesome/css/font-awesome.min.css' ),
						to: `${ buildLibPath }/font-awesome/css`,
					},
					// Used by extensively as a font icon
					{
						from: path.resolve( __dirname, 'node_modules/font-awesome/fonts' ),
						to: `${ buildLibPath }/font-awesome/fonts`,
					},
					// Used extensively for alerts
					{
						from: path.resolve( __dirname, 'node_modules/sweetalert2/dist/sweetalert2.min.js' ),
						to: `${ buildLibPath }/sweetalert2/`,
					},
					// Used extensively for alerts
					{
						from: path.resolve( __dirname, 'node_modules/sweetalert2/dist/sweetalert2.min.css' ),
						to: `${ buildLibPath }/sweetalert2/`,
					},
					// Used for dashboard charts
					{
						from: path.resolve( __dirname, 'node_modules/chart.js/dist/chart.umd.min.js' ),
						to: `${ buildLibPath }/chart.js/`,
					},
					// Used in a variety of areas
					{
						from: path.resolve( __dirname, 'node_modules/moment/min/moment-with-locales.min.js' ),
						to: `${ buildLibPath }/moment/`,
					},
					// Used extensively for date range selection
					{
						from: path.resolve( __dirname, 'node_modules/daterangepicker/daterangepicker.js' ),
						to: `${ buildLibPath }/daterangepicker/`,
					},
					// Used by events pages
					{
						from: path.resolve( __dirname, 'node_modules/fullcalendar/index.global.min.js' ),
						to: `${ buildLibPath }/fullcalendar/`,
					},
					// Used by events pages
					{
						from: path.resolve( __dirname, 'node_modules/@fullcalendar/core/locales' ),
						to: `${ buildLibPath }/fullcalendar/locales`,
						globOptions: { matchBase: true },
						filter: resourcePath => resourcePath.endsWith( '.min.js' ),
					},
					// Used for first-use dashboard modals
					{
						from: path.resolve( __dirname, 'node_modules/jquery-modal/jquery.modal.min.js' ),
						to: `${ buildLibPath }/jquery-modal/`,
					},
					// Used by first-use dashboard modals
					{
						from: path.resolve( __dirname, 'node_modules/jquery-modal/jquery.modal.min.css' ),
						to: `${ buildLibPath }/jquery-modal/`,
					},
					// Used extensively for autocompleting contacts/companies, etc.
					{
						from: path.resolve(
							__dirname,
							'node_modules/typeahead.js/dist/typeahead.bundle.min.js'
						),
						to: `${ buildLibPath }/typeahead.js/`,
					},
					// Used extensively as a general UI base
					{
						from: path.resolve( __dirname, 'node_modules/semantic-ui-css/semantic.min.css' ),
						to: `${ buildLibPath }/semantic-ui-css/`,
					},
					// Used extensively as a general UI base
					{
						from: path.resolve( __dirname, 'node_modules/semantic-ui-css/semantic.min.js' ),
						to: `${ buildLibPath }/semantic-ui-css/`,
					},
					// Used extensively as a general UI base
					{
						from: path.resolve( __dirname, 'node_modules/semantic-ui-css/themes' ),
						to: `${ buildLibPath }/semantic-ui-css/themes`,
					},
				],
			} ),
		],
	},
];
