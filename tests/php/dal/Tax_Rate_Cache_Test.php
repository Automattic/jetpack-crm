<?php
/**
 * Tests for adding a tax rate again after deleting it.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\DAL\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Deleting a tax rate clears the cache the duplicate check uses.
 */
class Tax_Rate_Cache_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Someone adds a rate that already exists (refused), deletes the existing one,
	 * then adds it again. The refused attempt is what leaves the cache filled.
	 *
	 * @testdox A deleted tax rate can be added again straight away.
	 */
	#[TestDox( 'A deleted tax rate can be added again straight away.' )]
	public function test_deleted_rate_can_be_added_again() {
		$rate = array(
			'data' => array(
				'name' => 'VAT',
				'rate' => 20,
			),
		);

		$first_id = zeroBSCRM_taxRates_addUpdateTaxRate( $rate );
		$this->assertIsInt( $first_id );

		$this->assertWPError( zeroBSCRM_taxRates_addUpdateTaxRate( $rate ), 'A duplicate should be refused.' );

		zeroBSCRM_taxRates_deleteTaxRate( array( 'id' => $first_id ) );

		$second_id = zeroBSCRM_taxRates_addUpdateTaxRate( $rate );

		$this->assertNotWPError( $second_id );
		$this->assertGreaterThan( 0, (int) $second_id );
	}
}
