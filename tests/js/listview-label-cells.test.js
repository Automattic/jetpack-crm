/*
 * jpcrm_listview_label_cells — labels each list view cell with its column
 * header, so the rows can stack into labeled cards on narrow screens.
 */
const { jpcrm_listview_label_cells } = require( '../../js/ZeroBSCRM.admin.listview.js' );

describe( 'jpcrm_listview_label_cells', () => {
	test( 'copies each header to the cells in its column', () => {
		document.body.innerHTML = `
			<table>
				<thead><tr><th><input type="checkbox" /></th><th data-colkey="id">ID</th><th data-colkey="status">Status <i class="icon"></i></th></tr></thead>
				<tbody>
					<tr><td><input type="checkbox" /></td><td>#1</td><td>Paid</td></tr>
					<tr><td><input type="checkbox" /></td><td>#2</td><td>Unpaid</td></tr>
				</tbody>
			</table>`;

		jpcrm_listview_label_cells( document.querySelector( 'table' ) );

		const cells = Array.from( document.querySelectorAll( 'tbody tr:first-child td' ) );
		expect( cells.map( td => td.dataset.colname ) ).toEqual( [ undefined, 'ID', 'Status' ] );
		expect( cells.map( td => td.dataset.colkey ) ).toEqual( [ undefined, 'id', 'status' ] );
		expect( document.querySelector( 'tbody tr:last-child td:last-child' ).dataset.colname ).toBe(
			'Status'
		);
	} );

	test( 'leaves a full-width message cell alone', () => {
		document.body.innerHTML = `
			<table>
				<thead><tr><th>ID</th><th>Name</th></tr></thead>
				<tbody><tr><td colspan="2">No contacts found</td></tr></tbody>
			</table>`;

		jpcrm_listview_label_cells( document.querySelector( 'table' ) );

		expect( document.querySelector( 'td' ).dataset.colname ).toBeUndefined();
	} );

	test( 'does nothing without a table', () => {
		expect( () => jpcrm_listview_label_cells( null ) ).not.toThrow();
	} );
} );
