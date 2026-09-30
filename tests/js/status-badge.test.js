/*
 * jpcrm.status_badge_html — status badges in the list views and the invoice
 * editor. The status map comes from PHP (jpcrm_get_status_badge_intents()) as
 * zbs_root.status_badge_intents.
 */
const { jpcrm } = require( '../../js/ZeroBSCRM.admin.global.js' );

describe( 'jpcrm.status_badge_html', () => {
	beforeEach( () => {
		window.zbs_root = { status_badge_intents: { paid: 'stable', overdue: 'high' } };
	} );

	afterEach( () => {
		delete window.zbs_root;
	} );

	test( 'colors a status from the map, whatever its case', () => {
		expect( jpcrm.status_badge_html( 'Paid' ) ).toBe(
			'<span class="jpcrm-badge is-stable">Paid</span>'
		);
		expect( jpcrm.status_badge_html( ' OVERDUE ' ) ).toContain( 'is-high' );
	} );

	test( 'shows a label in place of the status when given one', () => {
		expect( jpcrm.status_badge_html( 'Paid', 'Payé' ) ).toBe(
			'<span class="jpcrm-badge is-stable">Payé</span>'
		);
	} );

	test( 'falls back to the neutral draft intent for other statuses', () => {
		expect( jpcrm.status_badge_html( 'Hot lead' ) ).toContain( 'is-draft' );

		delete window.zbs_root;
		expect( jpcrm.status_badge_html( 'Paid' ) ).toContain( 'is-draft' );
	} );

	test( 'escapes the label', () => {
		expect( jpcrm.status_badge_html( '<b>New</b>' ) ).toBe(
			'<span class="jpcrm-badge is-draft">&lt;b&gt;New&lt;/b&gt;</span>'
		);
	} );
} );
