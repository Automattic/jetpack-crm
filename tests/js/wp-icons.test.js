/*
 * tools/build-wp-icons.mjs builds includes/jpcrm-wp-icons.php and
 * sass/_wp-icons.scss from tools/wp-icons.mjs and the @wordpress/icons
 * package. This fails when either is out of date, e.g. after the list changed
 * without `npm run build:icons`, or when an upgrade drops an icon CRM uses.
 */
const { execFileSync } = require( 'child_process' );
const path = require( 'path' );

test( 'the generated icon files match tools/wp-icons.mjs and @wordpress/icons', () => {
	const root = path.resolve( __dirname, '../..' );

	expect( () =>
		execFileSync( process.execPath, [ 'tools/build-wp-icons.mjs', '--check' ], {
			cwd: root,
			stdio: 'pipe',
		} )
	).not.toThrow();
} );
