/*
 * zbscrm_JS_validateEmail — the client-side email check shared by the quote
 * builder, the mail-delivery wizard, the public lead form and the contact
 * metaboxes.
 *
 * This seeds the JS suite against a small, pure, security-relevant function.
 * It pins the current contract: ordinary addresses pass, obvious non-addresses
 * do not. The tighter assertions — that a "payload"@x.tld style quoted local
 * part is rejected — land with the validator hardening from the invoice/quote
 * email-modal XSS fix (SIRT #29); see the marked block below.
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

	// --- attaches with the SIRT #29 validator hardening ---
	// Once the quoted-local-part branch is removed from the regex, un-skip these.
	// They are the regression guard for the XSS laundering path (HackerOne
	// 3925730), so a later edit cannot widen the validator back.
	test.skip.each( [
		'"x onanimationstart=alert(1) style=animation:fade-in "@ns.tr',
		'"payload"@x.tld',
		'" "@x.tld',
	] )( 'rejects the quoted-local-part payload %p', payload => {
		expect( zbscrm_JS_validateEmail( payload ) ).toBe( false );
	} );
} );
