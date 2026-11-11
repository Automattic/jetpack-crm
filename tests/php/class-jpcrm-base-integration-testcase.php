<?php

namespace Automattic\Jetpack\CRM\Tests;

use Automattic\Jetpack\CRM\Entities\Contact;
use Automattic\Jetpack\CRM\Entities\Factories\Contact_Factory;

/**
 * Test case that ensures we have a clean and functioning Jetpack CRM instance.
 */
abstract class JPCRM_Base_Integration_TestCase extends JPCRM_Base_TestCase {

	/**
	 * Clean up the database after each test.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function tear_down(): void {
		parent::tear_down();

		zeroBSCRM_database_reset( false );
	}

	/**
	 * Add a contact.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the contact.
	 *
	 * @return int The contact ID.
	 */
	public function add_contact( array $args = array() ) {
		global $zbs;

		return $zbs->DAL->contacts->addUpdateContact( array( 'data' => $this->generate_contact_data( $args ) ) );
	}

	/**
	 * Add a company.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the company.
	 *
	 * @return int The company ID.
	 */
	public function add_company( array $args = array() ) {
		global $zbs;

		return $zbs->DAL->companies->addUpdateCompany( array( 'data' => $this->generate_company_data( $args ) ) );
	}

	/**
	 * Add an invoice.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the invoice.
	 *
	 * @return int The invoice ID.
	 */
	public function add_invoice( array $args = array() ) {
		global $zbs;

		return $zbs->DAL->invoices->addUpdateInvoice( array( 'data' => $this->generate_invoice_data( $args ) ) );
	}

	/**
	 * Add a transaction.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the transaction.
	 *
	 * @return int The transaction ID.
	 */
	public function add_transaction( array $args = array() ) {
		global $zbs;

		return $zbs->DAL->transactions->addUpdateTransaction( array( 'data' => $this->generate_transaction_data( $args ) ) );
	}

	/**
	 * Get a contact.
	 *
	 * @param int|string $id The ID of the contact we want to get.
	 * @param array      $args (Optional) A list of arguments we should use for the contact.
	 * @return Contact|null
	 */
	public function get_contact( $id, array $args = array() ) {
		global $zbs;

		$contact_data = $zbs->DAL->contacts->getContact( $id, $args );

		return Contact_Factory::create( $contact_data );
	}

	/**
	 * Add a WP User.
	 *
	 * @return int The user ID.
	 */
	public function add_wp_user() {

		return wp_create_user( 'testuser', 'password', 'user@demo.com' );
	}

	/**
	 * Create a user who is allowed to own a contact.
	 *
	 * `addUpdateContact()` drops an owner who cannot, so a test that used a
	 * default-role user (see `add_wp_user()`) would be asserting against -1
	 * either way.
	 *
	 * @return int The new user ID.
	 */
	public function create_contact_owner(): int {
		$owner_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->assertTrue( user_can( $owner_id, 'admin_zerobs_usr' ), 'The owner under test cannot own a contact.' );

		return $owner_id;
	}
}
