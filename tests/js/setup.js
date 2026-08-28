/*
 * Enough of a jQuery to let a legacy js/ file load under jsdom.
 *
 * Requiring one of these files runs its top-level code, which does two things
 * with jQuery before any test does: registers a document-ready callback
 * (`jQuery( fn )`) and hangs a helper off `jQuery.fn`. The stub is callable and
 * carries an `fn` object, which is all that top-level code needs. It is not a
 * real jQuery — a test that exercises DOM behaviour should stub the specific
 * calls it makes rather than lean on this.
 */
const jqueryStub = function () {
	return jqueryStub;
};
jqueryStub.fn = {};
jqueryStub.extend = Object.assign;

global.jQuery = jqueryStub;
global.$ = jqueryStub;
