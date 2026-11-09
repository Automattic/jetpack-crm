<?php
/**
 * Tests for company custom fields and external sources on limited field updates.
 *
 * `addUpdateCompany()` has two update paths. A full update writes every column.
 * A limited field update writes only the columns it is given, and is what an
 * update becomes when "Overwrite Option" (fieldoverride) is on and the update
 * comes from an external source such as the CSV importer, a form or the API.
 *
 * Custom fields and external sources are saved after the columns. For companies
 * that only happened on the full update path, so a limited field update dropped
 * them without a word. Contacts have saved both on either path for some time.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Test that limited field updates save company custom fields and sources.
 */
class Company_Limited_Update_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Set up a company custom field.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $zbs;
		$zbs->DAL->updateActiveCustomFields( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			array(
				'objtypeid' => ZBS_TYPE_COMPANY,
				'fields'    => array(
					'website'  => array( 'text', 'Website', '', 'website' ),
					'industry' => array( 'text', 'Industry', '', 'industry' ),
				),
			)
		);
	}

	/**
	 * Restore settings and clear the CRM tables.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $zbs;
		$zbs->settings->update( 'fieldoverride', -1 );
		$zbs->DAL->updateActiveCustomFields( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			array(
				'objtypeid' => ZBS_TYPE_COMPANY,
				'fields'    => array(),
			)
		);

		zeroBSCRM_database_reset( false );

		parent::tear_down();
	}

	/**
	 * With fieldoverride on, an update from an external source is a limited
	 * field update. The custom field it sends must still be saved.
	 *
	 * @testdox An external-source update under fieldoverride saves the custom fields it sends.
	 */
	#[TestDox( 'An external-source update under fieldoverride saves the custom fields it sends.' )]
	public function test_fieldoverride_update_saves_custom_fields() {
		$company_id = $this->create_company_with_custom_fields();

		$this->update_from_external_source( $company_id, array( 'website' => 'https://new.example.test' ) );

		$company = $this->get_company( $company_id );
		$this->assertSame( 'https://new.example.test', $company['website'] );
		$this->assertSame( 'Widgets', $company['industry'] );
	}

	/**
	 * Fieldoverride exists to stop blanks replacing what is on record. That has
	 * to hold for custom fields as well as the columns.
	 *
	 * @testdox An external-source update under fieldoverride does not blank a custom field.
	 */
	#[TestDox( 'An external-source update under fieldoverride does not blank a custom field.' )]
	public function test_fieldoverride_update_keeps_blank_custom_fields() {
		$company_id = $this->create_company_with_custom_fields();

		$this->update_from_external_source(
			$company_id,
			array(
				'website'  => 'https://new.example.test',
				'industry' => '',
			)
		);

		$company = $this->get_company( $company_id );
		$this->assertSame( 'https://new.example.test', $company['website'] );
		$this->assertSame( 'Widgets', $company['industry'], 'A blank custom field replaced the value on record.' );
		$this->assertSame( 'Springfield', $company['city'], 'A blank column replaced the value on record.' );
	}

	/**
	 * Where a company came from is recorded outside the fields, and a limited
	 * field update used to skip it.
	 *
	 * @testdox An external-source update under fieldoverride records the source.
	 */
	#[TestDox( 'An external-source update under fieldoverride records the source.' )]
	public function test_fieldoverride_update_records_external_source() {
		global $zbs;

		$company_id = $this->create_company_with_custom_fields();

		$this->update_from_external_source( $company_id, array( 'website' => 'https://new.example.test' ) );

		$company = $zbs->DAL->companies->getCompany( $company_id, array( 'withExternalSources' => true ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->assertContains( 'api', wp_list_pluck( $company['external_sources'], 'source' ) );
	}

	/**
	 * A caller can also ask for a limited field update directly, naming a custom
	 * field among the fields. That key is moved out of the column update to be
	 * saved separately, so it has to actually be saved.
	 *
	 * @testdox An explicit limitedFields update saves a custom field it names.
	 */
	#[TestDox( 'An explicit limitedFields update saves a custom field it names.' )]
	public function test_explicit_limited_fields_saves_custom_field() {
		global $zbs;

		$company_id = $this->create_company_with_custom_fields();

		$zbs->DAL->companies->addUpdateCompany( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			array(
				'id'            => $company_id,
				'limitedFields' => array(
					array(
						'key'  => 'zbsco_website',
						'val'  => 'https://new.example.test',
						'type' => '%s',
					),
					array(
						'key'  => 'zbsco_city',
						'val'  => 'Shelbyville',
						'type' => '%s',
					),
				),
			)
		);

		$company = $this->get_company( $company_id );
		$this->assertSame( 'https://new.example.test', $company['website'] );
		$this->assertSame( 'Shelbyville', $company['city'] );
		$this->assertSame( 'Widgets', $company['industry'], 'A custom field the update did not name was changed.' );
	}

	/**
	 * Create a company with an address and both custom fields filled in.
	 *
	 * @return int The company ID.
	 */
	private function create_company_with_custom_fields(): int {
		return (int) $this->add_company(
			array(
				'name'     => 'Acme Ltd',
				'city'     => 'Springfield',
				'website'  => 'https://old.example.test',
				'industry' => 'Widgets',
			)
		);
	}

	/**
	 * Update a company the way an integration does with fieldoverride on: name
	 * plus whatever it has, every other column blank, and an external source.
	 *
	 * @param int   $company_id The company to update.
	 * @param array $fields     Fields to send on top of the blanks.
	 * @return void
	 */
	private function update_from_external_source( int $company_id, array $fields ): void {
		global $zbs;

		$zbs->settings->update( 'fieldoverride', 1 );

		$blank = array_fill_keys( array_keys( $this->generate_company_data() ), '' );

		$zbs->DAL->companies->addUpdateCompany( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			array(
				'id'   => $company_id,
				'data' => array_merge(
					$blank,
					array(
						'name'            => 'Acme Ltd',
						'externalSources' => array(
							array(
								'source' => 'api',
								'uid'    => 'acme-1',
							),
						),
					),
					$fields
				),
			)
		);
	}

	/**
	 * Get a company with its custom fields.
	 *
	 * @param int $company_id The company ID.
	 * @return array The company.
	 */
	private function get_company( int $company_id ): array {
		global $zbs;

		return $zbs->DAL->companies->getCompany( $company_id, array( 'withCustomFields' => true ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
}
