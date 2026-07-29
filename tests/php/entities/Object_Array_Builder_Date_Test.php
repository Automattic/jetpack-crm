<?php

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Tests date and datetime normalisation in the shared object array builder.
 *
 * The contact and company create/update API endpoints normalise their payload with
 * zeroBS_buildObjArr(), then hand the result to zeroBS_addUpdateCustomer()/
 * zeroBS_addUpdateCompany(), which normalise it a second time with $removeEmpties on.
 * Normalising an already-normalised value therefore has to be a no-op, or the second
 * pass strips the field and the value never reaches the DAL.
 *
 * @covers ::zeroBS_buildObjArr
 */
class Object_Array_Builder_Date_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Field globals as they were before a test replaced them.
	 *
	 * @var array
	 */
	private $original_field_globals = array();

	/**
	 * Register a date and a datetime custom field against contacts and companies.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		foreach ( array( 'zbsCustomerFields', 'zbsCompanyFields' ) as $global_name ) {
			$this->original_field_globals[ $global_name ] = $GLOBALS[ $global_name ] ?? array();

			$GLOBALS[ $global_name ]['contract-date'] = array( 'date', 'Contract Date', '' );
			$GLOBALS[ $global_name ]['signed-at']     = array( 'datetime', 'Signed At', '' );
		}
	}

	/**
	 * Restore the field globals.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		foreach ( $this->original_field_globals as $global_name => $value ) {
			$GLOBALS[ $global_name ] = $value;
		}

		parent::tear_down();
	}

	/**
	 * Run the two normalisation passes an API create/update performs.
	 *
	 * Mirrors api/create_customer.php and api/create_company.php, which build with an
	 * empty input prefix and a 'zbsc_' output prefix, followed by zeroBS_addUpdateCustomer(),
	 * which rebuilds with a 'zbsc_' input prefix, an empty output prefix and $removeEmpties on.
	 *
	 * @param array $payload  Incoming API payload.
	 * @param int   $obj_type Object type constant.
	 *
	 * @return array The twice-normalised array.
	 */
	private function build_twice( array $payload, $obj_type ) {
		$first_pass = zeroBS_buildObjArr( $payload, array(), '', 'zbsc_', false, $obj_type, true );

		return zeroBS_buildObjArr( $first_pass, array(), 'zbsc_', '', true, $obj_type, false );
	}

	/**
	 * Object types the builder is shared across, for the cases which apply to both.
	 *
	 * @return array
	 */
	public static function object_type_provider() {
		return array(
			'contact' => array( ZBS_TYPE_CONTACT ),
			'company' => array( ZBS_TYPE_COMPANY ),
		);
	}

	/**
	 * @testdox A date custom field survives the second normalisation pass.
	 *
	 * @dataProvider object_type_provider
	 *
	 * @param int $obj_type Object type constant.
	 */
	#[DataProvider( 'object_type_provider' )]
	#[TestDox( 'A date custom field survives the second normalisation pass.' )]
	public function test_date_custom_field_survives_second_pass( $obj_type ) {
		$expected = jpcrm_date_str_to_uts( '2026-07-23', '!Y-m-d', true );

		$result = $this->build_twice( array( 'contract-date' => '2026-07-23' ), $obj_type );

		$this->assertArrayHasKey( 'contract-date', $result, 'The date custom field was stripped before reaching the DAL.' );
		$this->assertSame( $expected, $result['contract-date'] );
	}

	/**
	 * @testdox A datetime custom field survives the second normalisation pass.
	 *
	 * @dataProvider object_type_provider
	 *
	 * @param int $obj_type Object type constant.
	 */
	#[DataProvider( 'object_type_provider' )]
	#[TestDox( 'A datetime custom field survives the second normalisation pass.' )]
	public function test_datetime_custom_field_survives_second_pass( $obj_type ) {
		$first_pass = zeroBS_buildObjArr( array( 'signed-at' => '2026-07-23 14:30' ), array(), '', 'zbsc_', false, $obj_type, true );

		$this->assertIsInt( $first_pass['zbsc_signed-at'], 'The first pass did not produce a timestamp; the fixture format may not match the site date format.' );

		$result = zeroBS_buildObjArr( $first_pass, array(), 'zbsc_', '', true, $obj_type, false );

		$this->assertArrayHasKey( 'signed-at', $result, 'The datetime custom field was stripped before reaching the DAL.' );
		$this->assertSame( $first_pass['zbsc_signed-at'], $result['signed-at'] );
	}

	/**
	 * A pre-1970 date is the case a post-conversion guard would miss: it normalises to a
	 * negative timestamp, and zeroBSCRM_locale_dateToUTS() falls back to strtotime(), which
	 * reads a leading minus as a relative offset and returns roughly the current time.
	 *
	 * @testdox A pre-1970 date custom field survives the second pass without becoming today.
	 */
	#[TestDox( 'A pre-1970 date custom field survives the second pass without becoming today.' )]
	public function test_pre_1970_date_custom_field_survives_second_pass() {
		$expected = jpcrm_date_str_to_uts( '1950-01-01', '!Y-m-d', true );

		$this->assertLessThan( 0, $expected, 'Fixture assumption: a 1950 date is a negative timestamp.' );

		$result = $this->build_twice( array( 'contract-date' => '1950-01-01' ), ZBS_TYPE_CONTACT );

		$this->assertArrayHasKey( 'contract-date', $result, 'The pre-1970 date custom field was stripped before reaching the DAL.' );
		$this->assertSame( $expected, $result['contract-date'] );
	}

	/**
	 * @testdox Normalising a date once and twice produce the same result.
	 */
	#[TestDox( 'Normalising a date once and twice produce the same result.' )]
	public function test_normalising_twice_matches_normalising_once() {
		$payload = array( 'contract-date' => '2026-07-23' );

		$once  = zeroBS_buildObjArr( $payload, array(), '', '', true, ZBS_TYPE_CONTACT, false );
		$twice = zeroBS_buildObjArr( $once, array(), '', '', true, ZBS_TYPE_CONTACT, false );

		$this->assertArrayHasKey( 'contract-date', $twice, 'The second pass stripped the date custom field.' );
		$this->assertSame( $once['contract-date'], $twice['contract-date'] );
	}

	/**
	 * The pass-through accepts nine or more digits, so that a short numeric string is still
	 * treated as an unparseable date rather than as a timestamp in 1970.
	 *
	 * @testdox A short numeric date value is not read as a timestamp.
	 */
	#[TestDox( 'A short numeric date value is not read as a timestamp.' )]
	public function test_short_numeric_date_value_is_not_read_as_a_timestamp() {
		$result = zeroBS_buildObjArr( array( 'contract-date' => '2026' ), array(), '', '', false, ZBS_TYPE_CONTACT, false );

		$this->assertFalse( $result['contract-date'], 'A four digit year should still fail to parse, as it did before.' );
	}

	/**
	 * @testdox Empty and unparseable date values behave as they did before.
	 */
	#[TestDox( 'Empty and unparseable date values behave as they did before.' )]
	public function test_empty_and_unparseable_date_values_are_unchanged() {
		foreach ( array( '', 'not-a-date' ) as $value ) {
			$result = zeroBS_buildObjArr( array( 'contract-date' => $value ), array(), '', '', false, ZBS_TYPE_CONTACT, false );

			$this->assertFalse( $result['contract-date'], sprintf( 'Expected %s to remain unparseable.', var_export( $value, true ) ) );
		}
	}
}
