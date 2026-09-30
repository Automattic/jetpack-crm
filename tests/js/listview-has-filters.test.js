/*
 * jpcrm_listview_has_filters — decides between the list view's two empty
 * states: "nothing here yet" with no filters, "no results" with them.
 */
const { jpcrm_listview_has_filters } = require( '../../js/ZeroBSCRM.admin.listview.js' );

describe( 'jpcrm_listview_has_filters', () => {
	afterEach( () => {
		delete window.zbsListViewParams;
	} );

	test.each( [
		[ 'no filters object', {} ],
		[ 'empty filters', { filters: {} } ],
		[ 'cleared filters', { filters: { s: '', tags: [], quickfilters: [] } } ],
	] )( 'is false with %s', ( label, params ) => {
		window.zbsListViewParams = params;
		expect( jpcrm_listview_has_filters() ).toBe( false );
	} );

	test.each( [
		[ 'a search', { s: 'maya' } ],
		[ 'a tag', { tags: [ { id: 3 } ] } ],
		[ 'a quick filter', { quickfilters: [ 'status_Lead' ] } ],
	] )( 'is true with %s', ( label, filters ) => {
		window.zbsListViewParams = { filters };
		expect( jpcrm_listview_has_filters() ).toBe( true );
	} );
} );
