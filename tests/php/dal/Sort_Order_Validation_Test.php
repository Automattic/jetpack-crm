<?php

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\TestDox;

/**
 * Tests that buildSort() only ever emits a recognised sort direction.
 *
 * The array (multi-sort) branch did not check the direction against the two values it
 * accepts, so an unrecognised one reached the ORDER BY clause unchanged.
 */
class Sort_Order_Validation_Test extends JPCRM_Base_TestCase {

	/**
	 * @testdox Test that an unrecognised sort direction is normalised in the array branch.
	 */
	#[TestDox( 'Test that an unrecognised sort direction is normalised in the array branch.' )]
	public function test_array_branch_normalises_unrecognised_sort_direction() {
		global $zbs;

		$sort = $zbs->DAL->buildSort( array( 'external_source_uids' => 'ASC,(SELECT SLEEP(3))' ) );

		$this->assertSame( ' ORDER BY external_source_uids DESC', $sort );
	}

	/**
	 * @testdox Test that a valid sort direction is kept in the array branch.
	 */
	#[TestDox( 'Test that a valid sort direction is kept in the array branch.' )]
	public function test_array_branch_keeps_valid_sort_direction() {
		global $zbs;

		$sort = $zbs->DAL->buildSort( array( 'zbsc_fname' => 'ASC' ) );

		$this->assertSame( ' ORDER BY zbsc_fname ASC', $sort );
	}

	/**
	 * @testdox Test that multi-sort keeps each direction and capitalises it.
	 */
	#[TestDox( 'Test that multi-sort keeps each direction and capitalises it.' )]
	public function test_array_branch_keeps_each_direction_in_multi_sort() {
		global $zbs;

		$sort = $zbs->DAL->buildSort(
			array(
				'zbsc_fname' => 'ASC',
				'zbsc_lname' => 'desc',
			)
		);

		$this->assertSame( ' ORDER BY zbsc_fname ASC, zbsc_lname DESC', $sort );
	}

	/**
	 * @testdox Test that the list view rejects an unrecognised sort direction.
	 */
	#[TestDox( 'Test that the list view rejects an unrecognised sort direction.' )]
	public function test_list_view_rejects_unrecognised_sort_order() {
		$this->assertFalse( jpcrm_sanitize_list_view_sort_order( 'ASC,(SELECT SLEEP(3))' ) );
	}

	/**
	 * @testdox Test that the list view accepts a valid sort direction in any case, unaltered.
	 */
	#[TestDox( 'Test that the list view accepts a valid sort direction in any case, unaltered.' )]
	public function test_list_view_accepts_valid_sort_order() {
		$this->assertSame( 'asc', jpcrm_sanitize_list_view_sort_order( 'asc' ) );
		$this->assertSame( 'DESC', jpcrm_sanitize_list_view_sort_order( 'DESC' ) );
	}

	/**
	 * @testdox Test that the list view treats an absent sort direction as unset.
	 */
	#[TestDox( 'Test that the list view treats an absent sort direction as unset.' )]
	public function test_list_view_rejects_empty_sort_order() {
		$this->assertFalse( jpcrm_sanitize_list_view_sort_order( '' ) );
		$this->assertFalse( jpcrm_sanitize_list_view_sort_order( false ) );
	}
}
