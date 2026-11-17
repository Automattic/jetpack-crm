<?php
/**
 * Tests for date custom fields when two contacts are merged.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * A merge patches the main record's empty fields from the secondary record.
 * Date custom fields are stored as timestamps and have to survive that.
 *
 * @covers ::zeroBSCRM_mergeCustomers
 */
class Contact_Merge_Date_Custom_Field_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Slug of the date custom field registered against contacts.
	 */
	private const FIELD_SLUG = 'contract-date';

	/**
	 * The date as entered.
	 */
	private const DATE_STR = '2026-07-23';

	/**
	 * The same date as the DAL stores it.
	 */
	private const DATE_UTS = 1784764800;

	/**
	 * Register a real date custom field against contacts.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->register_custom_field(
			self::FIELD_SLUG,
			'date',
			'Contract Date',
			array(
				'customers' => ZBS_TYPE_CONTACT,
			)
		);
	}

	/**
	 * Merging copies the secondary record's date into a main record that has none.
	 *
	 * @return void
	 */
	#[TestDox( 'Merging contacts copies a date custom field from the secondary record.' )]
	public function test_merge_copies_date_custom_field_from_secondary() {
		global $zbs;

		$main_id = $this->add_contact( array( 'email' => 'merge-main@example.com' ) );

		$secondary_email = 'merge-secondary@example.com';
		$secondary_id    = zeroBS_integrations_addOrUpdateCustomer(
			'api',
			$secondary_email,
			zeroBS_buildContactMeta(
				array(
					'email'          => $secondary_email,
					'fname'          => 'Second',
					'lname'          => 'Record',
					self::FIELD_SLUG => self::DATE_STR,
				),
				array(),
				'',
				'zbsc_',
				false,
				true
			)
		);

		$this->assertGreaterThan( 0, $main_id, 'The main contact was not created.' );
		$this->assertGreaterThan( 0, $secondary_id, 'The secondary contact was not created.' );

		zeroBSCRM_mergeCustomers( $main_id, $secondary_id );

		$main = $zbs->DAL->contacts->getContact( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$main_id,
			array( 'withCustomFields' => true )
		);

		$this->assertArrayHasKey( self::FIELD_SLUG, $main, 'The date custom field is missing from the main contact.' );
		$this->assertEquals( self::DATE_UTS, $main[ self::FIELD_SLUG ], 'The date was not copied from the secondary contact.' );
	}
}
