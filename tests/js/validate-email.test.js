/*
 * zbscrm_JS_validateEmail — the client-side email check shared by the quote
 * builder, the mail-delivery wizard, the public lead form and the contact
 * metaboxes.
 *
 * This seeds the JS suite against a small, pure function. It pins the current
 * contract: ordinary addresses pass, obvious non-addresses do not.
 */
const { zbscrm_JS_validateEmail } = require( '../../js/ZeroBSCRM.admin.global.js' );

describe( 'zbscrm_JS_validateEmail', () => {
	test.each( [
		'alex@example.com',
		'a.b-c@sub.example.co.uk',
		'first.last@really.deep.subdomain.example.org',
		'user@[192.168.0.1]',
	] )( 'accepts the ordinary address %p', address => {
		expect( zbscrm_JS_validateEmail( address ) ).toBe( true );
	} );

	test.each( [ '', 'plainname', 'no-at-sign.example.com', 'two@@example.com', 'a@b' ] )(
		'rejects the non-address %p',
		value => {
			expect( zbscrm_JS_validateEmail( value ) ).toBe( false );
		}
	);
} );
