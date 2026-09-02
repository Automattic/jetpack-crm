<?php
/**
 * Tests for CSV formula-injection escaping in exports.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\TestDox;

/**
 * Tests that jpcrm_csv_escape_formula() neutralises CSV formula injection.
 *
 * A CRM export is user-controlled data (a contact's own fields, a public lead
 * form submission), so a cell beginning with =, +, -, @, tab or carriage return
 * would run as a formula when the export is opened in a spreadsheet. The helper
 * prefixes a single quote so the cell renders as text; everything else is left
 * exactly as it was.
 */
class CSV_Formula_Injection_Test extends JPCRM_Base_TestCase {

	/**
	 * @testdox A cell starting with a formula trigger is prefixed with a single quote.
	 */
	#[TestDox( 'A cell starting with a formula trigger is prefixed with a single quote.' )]
	public function test_formula_triggers_are_escaped() {
		$this->assertSame( "'=1+2", jpcrm_csv_escape_formula( '=1+2' ) );
		$this->assertSame( "'+SUM(A1)", jpcrm_csv_escape_formula( '+SUM(A1)' ) );
		$this->assertSame( "'-2+3", jpcrm_csv_escape_formula( '-2+3' ) );
		$this->assertSame( "'@cmd", jpcrm_csv_escape_formula( '@cmd' ) );
		$this->assertSame( "'\tx", jpcrm_csv_escape_formula( "\tx" ) );
		$this->assertSame( "'\rx", jpcrm_csv_escape_formula( "\rx" ) );
	}

	/**
	 * @testdox An ordinary cell is returned unchanged.
	 */
	#[TestDox( 'An ordinary cell is returned unchanged.' )]
	public function test_ordinary_values_are_untouched() {
		$this->assertSame( '19 Prospect Hill', jpcrm_csv_escape_formula( '19 Prospect Hill' ) );
		$this->assertSame( 'alex@example.com', jpcrm_csv_escape_formula( 'alex@example.com' ) );
		$this->assertSame( 'Doe, John', jpcrm_csv_escape_formula( 'Doe, John' ) );
		$this->assertSame( '', jpcrm_csv_escape_formula( '' ) );
	}

	/**
	 * @testdox A non-string cell is returned unchanged.
	 */
	#[TestDox( 'A non-string cell is returned unchanged.' )]
	public function test_non_string_values_pass_through() {
		$this->assertSame( 5, jpcrm_csv_escape_formula( 5 ) );
		$this->assertSame( 0, jpcrm_csv_escape_formula( 0 ) );
		$this->assertNull( jpcrm_csv_escape_formula( null ) );
	}
}
