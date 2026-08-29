<?php

namespace Automattic\Jetpack\CRM\API\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * End to end coverage for date custom fields sent to the legacy create/update API.
 *
 * The builder-level tests in tests/php/entities/Object_Array_Builder_Date_Test.php stop at
 * zeroBS_buildObjArr(). These go the whole way: they run the same calls api/create_customer.php
 * and api/create_company.php make, then read the record back out of the database and check the
 * value actually landed in wp_zbs_customfields.
 *
 * @covers ::zeroBS_buildObjArr
 */
class API_Date_Custom_Field_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Slug of the date custom field registered against contacts and companies.
	 */
	private const FIELD_SLUG = 'contract-date';

	/**
	 * The date as an API client would send it.
	 */
	private const DATE_STR = '2026-07-23';

	/**
	 * The same date as the DAL stores it.
	 */
	private const DATE_UTS = 1784764800;

	/**
	 * Register a real date custom field against contacts and companies, then rebuild the
	 * field globals so the builder and the DAL both see it.
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
				'companies' => ZBS_TYPE_COMPANY,
			)
		);
	}

	/**
	 * A date custom field sent to the contact create endpoint has to reach the database.
	 *
	 * Mirrors api/create_customer.php: build the payload with an empty input prefix and a
	 * 'zbsc_' output prefix, then hand the result to zeroBS_integrations_addOrUpdateCustomer(),
	 * which normalises it a second time inside zeroBS_addUpdateCustomer().
	 *
	 * @return void
	 */
	#[TestDox( 'A date custom field sent to the contact create API is stored.' )]
	public function test_contact_create_api_stores_date_custom_field() {
		global $zbs;

		$email = 'date-api-contact@example.com';

		$payload = array(
			'email'          => $email,
			'fname'          => 'Date',
			'lname'          => 'Field',
			self::FIELD_SLUG => self::DATE_STR,
		);

		// api/create_customer.php:51
		$update_args = zeroBS_buildContactMeta( $payload, array(), '', 'zbsc_', false, true );

		// api/create_customer.php:220
		$contact_id = zeroBS_integrations_addOrUpdateCustomer( 'api', $email, $update_args );

		$this->assertGreaterThan( 0, $contact_id, 'The contact was not created.' );

		$contact = $zbs->DAL->contacts->getContact( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$contact_id,
			array( 'withCustomFields' => true )
		);

		$this->assertArrayHasKey( self::FIELD_SLUG, $contact, 'The date custom field is missing from the contact.' );
		$this->assertEquals(
			self::DATE_UTS,
			$contact[ self::FIELD_SLUG ],
			'The date custom field did not reach the database.'
		);
	}

	/**
	 * The same, for the company create endpoint.
	 *
	 * Mirrors api/create_company.php, which calls zeroBS_buildObjArr() directly rather than
	 * zeroBS_buildCompanyMeta(), and passes a 'zbsc_' prefix rather than 'zbsco_'.
	 *
	 * @return void
	 */
	#[TestDox( 'A date custom field sent to the company create API is stored.' )]
	public function test_company_create_api_stores_date_custom_field() {
		global $zbs;

		$email = 'date-api-company@example.com';

		$payload = array(
			'email'          => $email,
			'name'           => 'Date Field Ltd',
			self::FIELD_SLUG => self::DATE_STR,
		);

		// api/create_company.php:30
		$update_args = zeroBS_buildObjArr( $payload, array(), '', 'zbsc_', false, ZBS_TYPE_COMPANY, true );

		// api/create_company.php:183
		$company_id = zeroBS_integrations_addOrUpdateCompany( 'api', $email, $update_args, '', 'none', false, false, 'update', 'zbsc_' );

		$this->assertGreaterThan( 0, $company_id, 'The company was not created.' );

		$company = $zbs->DAL->companies->getCompany( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$company_id,
			array( 'withCustomFields' => true )
		);

		$this->assertArrayHasKey( self::FIELD_SLUG, $company, 'The date custom field is missing from the company.' );
		$this->assertEquals(
			self::DATE_UTS,
			$company[ self::FIELD_SLUG ],
			'The date custom field did not reach the database.'
		);
	}
}
