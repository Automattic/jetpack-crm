<?php
/**
 * Status filter tests.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\TestDox;

/**
 * Tests that the list view status filters match a status exactly, case included.
 *
 * They used to force the match with `COLLATE utf8mb4_bin`, which the SQLite driver
 * behind WordPress Studio and Playground rejects, so every filter errored there.
 */
class Status_Filter_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Arguments for a count of one object type filtered to a status.
	 *
	 * @param string $status The status to filter to.
	 * @return array
	 */
	private function status_filter_count_args( string $status ): array {
		return array(
			'quickFilters' => array( 'status_' . $status ),
			'count'        => true,
			'ignoreowner'  => true,
		);
	}

	/**
	 * @testdox Test that the contact status filter matches case-sensitively.
	 */
	#[TestDox( 'Test that the contact status filter matches case-sensitively.' )]
	public function test_contact_status_filter_is_case_sensitive() {
		global $zbs;

		$this->add_contact(
			array(
				'status' => 'Lead',
				'email'  => 'lead@domain.null',
			)
		);
		$this->add_contact(
			array(
				'status' => 'lead',
				'email'  => 'lowercase-lead@domain.null',
			)
		);
		$this->add_contact(
			array(
				'status' => 'Customer',
				'email'  => 'customer@domain.null',
			)
		);

		$this->assertSame( 1, (int) $zbs->DAL->contacts->getContacts( $this->status_filter_count_args( 'Lead' ) ) );
		$this->assertSame( 1, (int) $zbs->DAL->contacts->getContacts( $this->status_filter_count_args( 'lead' ) ) );
		$this->assertSame( 0, (int) $zbs->DAL->contacts->getContacts( $this->status_filter_count_args( 'LEAD' ) ) );
		$this->assertSame( 1, (int) $zbs->DAL->contacts->getContactCount( array( 'withStatus' => 'Lead' ) ) );
	}

	/**
	 * @testdox Test that the company status filter matches case-sensitively.
	 */
	#[TestDox( 'Test that the company status filter matches case-sensitively.' )]
	public function test_company_status_filter_is_case_sensitive() {
		global $zbs;

		$this->add_company(
			array(
				'status' => 'Lead',
				'name'   => 'Lead Co',
				'email'  => 'lead@companyemail.com',
			)
		);
		$this->add_company(
			array(
				'status' => 'lead',
				'name'   => 'Lowercase Lead Co',
				'email'  => 'lowercase-lead@companyemail.com',
			)
		);

		$this->assertSame( 1, (int) $zbs->DAL->companies->getCompanies( $this->status_filter_count_args( 'Lead' ) ) );
		$this->assertSame( 0, (int) $zbs->DAL->companies->getCompanies( $this->status_filter_count_args( 'LEAD' ) ) );
	}

	/**
	 * @testdox Test that the invoice status filter matches case-sensitively.
	 */
	#[TestDox( 'Test that the invoice status filter matches case-sensitively.' )]
	public function test_invoice_status_filter_is_case_sensitive() {
		global $zbs;

		$this->add_invoice(
			array(
				'status'      => 'Paid',
				'id_override' => '1',
				'hash'        => 'StatusFilterPaid0001',
			)
		);
		$this->add_invoice(
			array(
				'status'      => 'Draft',
				'id_override' => '2',
				'hash'        => 'StatusFilterDraft001',
			)
		);

		$this->assertSame( 1, (int) $zbs->DAL->invoices->getInvoices( $this->status_filter_count_args( 'Paid' ) ) );
		$this->assertSame( 0, (int) $zbs->DAL->invoices->getInvoices( $this->status_filter_count_args( 'paid' ) ) );
	}

	/**
	 * @testdox Test that the transaction status filter matches case-sensitively.
	 */
	#[TestDox( 'Test that the transaction status filter matches case-sensitively.' )]
	public function test_transaction_status_filter_is_case_sensitive() {
		global $zbs;

		$this->add_transaction(
			array(
				'status' => 'Succeeded',
				'ref'    => 'StatusFilterSucceeded',
				'hash'   => 'StatusFilterSucceeded',
			)
		);
		$this->add_transaction(
			array(
				'status' => 'Refunded',
				'ref'    => 'StatusFilterRefunded',
				'hash'   => 'StatusFilterRefunded',
			)
		);

		$this->assertSame( 1, (int) $zbs->DAL->transactions->getTransactions( $this->status_filter_count_args( 'Succeeded' ) ) );
		$this->assertSame( 0, (int) $zbs->DAL->transactions->getTransactions( $this->status_filter_count_args( 'succeeded' ) ) );
	}
}
