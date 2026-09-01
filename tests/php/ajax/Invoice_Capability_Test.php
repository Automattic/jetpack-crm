<?php
/**
 * Tests the capability gates on the invoice AJAX read handlers.
 *
 * These deliberately do not extend WP_Ajax_UnitTestCase. That case is in the
 * `ajax` group, which the WordPress bootstrap excludes unless you pass
 * --group ajax, so tests written that way are silently skipped and look green
 * while never running.
 *
 * @package Automattic\JetpackCRM
 */

namespace Automattic\Jetpack\CRM\Tests;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Capability gates on zbs_get_invoice_data and getinvs.
 */
class Invoice_Capability_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Roles that hold admin_zerobs_view_invoices and should reach invoice data.
	 *
	 * zerobs_mailmgr is included on purpose. It reads as a role that should be
	 * refused, but it is granted the view capability
	 * deliberately and the KB documents mail managers as having read-only
	 * access to invoices and transactions.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function allowed_roles(): array {
		return array(
			'administrator' => array( 'administrator' ),
			'CRM admin'     => array( 'zerobs_admin' ),
			'invoice mgr'   => array( 'zerobs_invoicemgr' ),
			'customer mgr'  => array( 'zerobs_customermgr' ),
			'mail mgr'      => array( 'zerobs_mailmgr' ),
		);
	}

	/**
	 * Roles that must not reach invoice data.
	 *
	 * The portal contact is the role that matters here. WordPress stores role names
	 * as keys in WP_User::$allcaps, so has_cap( 'zerobs_customer' ) is true for any
	 * portal contact, which is what made zeroBSCRM_permsIsZBSUser() the wrong gate.
	 * A plain subscriber is included as a floor: no CRM role at all.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function refused_roles(): array {
		return array(
			'quote mgr'       => array( 'zerobs_quotemgr' ),
			'transaction mgr' => array( 'zerobs_transactionmgr' ),
			'portal contact'  => array( 'zerobs_customer' ),
			'subscriber'      => array( 'subscriber' ),
		);
	}

	/**
	 * Set the current user to a fresh user with the given role, and supply a
	 * valid nonce so the handler gets past check_ajax_referer.
	 *
	 * @param string $role  The role to create the user with.
	 * @param string $nonce The nonce action the handler checks.
	 * @return void
	 */
	private function acting_as( string $role, string $nonce ): void {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );

		$_REQUEST['sec'] = wp_create_nonce( $nonce );
		$_POST['sec']    = $_REQUEST['sec'];
	}

	/**
	 * Build an invoice attached to a contact or company, plus a second owner of
	 * the same type with an invoice of its own.
	 *
	 * The second pair is what makes the assertions mean anything. The CRM tables
	 * are truncated between tests, so with one invoice in the database "the
	 * response carries the seeded ID" cannot be told apart from "the response
	 * carries whatever invoice exists", and getinvs takes cid/coid straight off
	 * the request without scoping it to anything.
	 *
	 * @param string $owner_type 'contact' or 'company'.
	 * @return array{owner: int, invoice: int, other_invoice: int}
	 */
	private function seed_invoice( string $owner_type = 'contact' ): array {
		if ( 'company' === $owner_type ) {
			$owner_id       = $this->add_company( array( 'name' => 'Acme' ) );
			$other_owner_id = $this->add_company(
				array(
					'name'  => 'Globex',
					'email' => 'other@companyemail.com',
				)
			);
		} else {
			$owner_id       = $this->add_contact();
			$other_owner_id = $this->add_contact(
				array(
					'fname' => 'Jane',
					'lname' => 'Roe',
					'email' => 'other@domain.null',
				)
			);
		}

		$this->assertNotSame(
			$owner_id,
			$other_owner_id,
			'the two seeded owners collapsed into one, so nothing below is scoped'
		);

		// generate_invoice_data() defaults contacts to array( 1 ), so the company
		// case has to override it away explicitly.
		$assignment   = 'company' === $owner_type ? 'companies' : 'contacts';
		$invoice_args = array(
			'contacts' => array(),
			'status'   => 'Unpaid',
		);

		$invoice_args[ $assignment ] = array( $owner_id );
		$invoice_id                  = $this->add_invoice( $invoice_args );

		$invoice_args['id_override'] = '2';
		$invoice_args[ $assignment ] = array( $other_owner_id );
		$other_invoice_id            = $this->add_invoice( $invoice_args );

		return array(
			'owner'         => $owner_id,
			'invoice'       => $invoice_id,
			'other_invoice' => $other_invoice_id,
		);
	}

	/**
	 * Shared body for the cid/coid refusal tests. The handler has a single
	 * capability gate ahead of the id branching today, but each request key
	 * keeps its own test so a later per-path gate cannot regress silently.
	 *
	 * @param string $role        The role under test.
	 * @param string $request_key 'cid' or 'coid'.
	 * @param string $owner_type  'contact' or 'company'.
	 */
	private function assert_getinvs_refuses( string $role, string $request_key, string $owner_type ): void {
		$seed = $this->seed_invoice( $owner_type );
		$this->acting_as( $role, 'zbscrmjs-glob-ajax-nonce' );
		$_POST[ $request_key ] = $seed['owner'];

		$response = $this->capture_json_response( 'zeroBSCRM_AJAX_getCustInvs' );

		$this->assertSame( array(), $response, "$role should get no invoices back" );

		// The handler returns an empty array both when it refuses and when the
		// owner genuinely has no invoices, so the assertion above would also pass
		// with the gate deleted and a broken seed. Prove the data was there to leak.
		$this->acting_as( 'zerobs_admin', 'zbscrmjs-glob-ajax-nonce' );
		$control = $this->capture_json_response( 'zeroBSCRM_AJAX_getCustInvs' );

		$this->assertSame(
			array( $seed['invoice'] ),
			array_map( 'intval', wp_list_pluck( $control, 'id' ) ),
			'the seeded invoice is not reachable at all, so the refusal above proves nothing'
		);
		$this->assertNotContains(
			$seed['other_invoice'],
			array_map( 'intval', wp_list_pluck( $control, 'id' ) ),
			'getinvs returned another owner\'s invoice, so the control fetch is not scoped'
		);
	}

	/**
	 * Shared body for the cid/coid scoping tests: exactly the seeded owner's
	 * invoice comes back, not the other owner's.
	 *
	 * @param string $role        The role under test.
	 * @param string $request_key 'cid' or 'coid'.
	 * @param string $owner_type  'contact' or 'company'.
	 */
	private function assert_getinvs_scoped_to_owner( string $role, string $request_key, string $owner_type ): void {
		$seed = $this->seed_invoice( $owner_type );
		$this->acting_as( $role, 'zbscrmjs-glob-ajax-nonce' );
		$_POST[ $request_key ] = $seed['owner'];

		$response = $this->capture_json_response( 'zeroBSCRM_AJAX_getCustInvs' );

		// Exactly one ID, not just a non-empty response: a second owner's invoice
		// exists, so this fails if the handler ignores the id and returns everything.
		$this->assertSame(
			array( $seed['invoice'] ),
			array_map( 'intval', wp_list_pluck( (array) $response, 'id' ) ),
			"$role should get this {$owner_type}'s invoices and no others"
		);
	}

	/**
	 * zbs_get_invoice_data used to gate on zeroBSCRM_permsIsZBSUser(), which is
	 * true for any CRM user at all.
	 *
	 * @param string $role The role under test.
	 */
	#[DataProvider( 'refused_roles' )]
	public function test_get_invoice_data_refuses_roles_without_the_view_capability( string $role ) {
		$seed = $this->seed_invoice();
		$this->acting_as( $role, 'zbscrmjs-ajax-nonce' );
		$_POST['invid'] = $seed['invoice'];

		$response = $this->capture_json_response( 'zeroBSCRM_AJAX_getInvoice' );

		$this->assertIsArray( $response, "$role should get a JSON error back" );
		$this->assertArrayHasKey( 'success', $response, "$role should be refused invoice data" );
		$this->assertFalse( $response['success'], "$role should be refused invoice data" );
		$this->assertArrayNotHasKey( 'invoiceObj', $response, "$role must not receive invoice data" );
	}

	/**
	 * The gate must not be so tight that documented read-only roles lose access.
	 *
	 * @param string $role The role under test.
	 */
	#[DataProvider( 'allowed_roles' )]
	public function test_get_invoice_data_allows_roles_with_the_view_capability( string $role ) {
		$seed = $this->seed_invoice();
		$this->acting_as( $role, 'zbscrmjs-ajax-nonce' );
		$_POST['invid'] = $seed['invoice'];

		$response = $this->capture_json_response( 'zeroBSCRM_AJAX_getInvoice' );

		$this->assertIsArray( $response, "$role should reach invoice data" );
		$this->assertArrayNotHasKey( 'success', $response, "$role should not get an error response" );
		$this->assertArrayHasKey( 'invoiceObj', $response, "$role should reach invoice data" );

		// invoiceObj alone proves nothing: zeroBSCRM_invoicing_getInvoiceData() falls
		// back to zeroBSCRM_get_invoice_defaults() for any ID it cannot load, so the
		// key is present even for a bogus ID or an empty database. The defaults carry
		// new_invoice = true; only a real load sets it false.
		$this->assertSame(
			$seed['invoice'],
			(int) $response['invoiceObj']['id'],
			"$role should get the seeded invoice back"
		);
		$this->assertFalse(
			$response['invoiceObj']['new_invoice'],
			"$role got the blank-invoice defaults, not the seeded invoice"
		);
	}

	/**
	 * getinvs used to gate on zeroBSCRM_permsCustomers(), so contacts access was
	 * enough to pull every invoice for a contact.
	 *
	 * @param string $role The role under test.
	 */
	#[DataProvider( 'refused_roles' )]
	public function test_getinvs_refuses_roles_without_the_view_capability( string $role ) {
		$this->assert_getinvs_refuses( $role, 'cid', 'contact' );
	}

	/**
	 * @param string $role The role under test.
	 */
	#[DataProvider( 'allowed_roles' )]
	public function test_getinvs_allows_roles_with_the_view_capability( string $role ) {
		$this->assert_getinvs_scoped_to_owner( $role, 'cid', 'contact' );
	}

	/**
	 * The getinvs endpoint also serves company-assigned invoices via coid —
	 * the path the transaction editor uses for company-assigned transactions.
	 */
	public function test_getinvs_returns_invoices_scoped_to_the_requested_company() {
		$this->assert_getinvs_scoped_to_owner( 'zerobs_admin', 'coid', 'company' );
	}

	/**
	 * The company path sits behind the same view-invoices gate as the contact path.
	 *
	 * @param string $role The role under test.
	 */
	#[DataProvider( 'refused_roles' )]
	public function test_getinvs_company_path_refuses_roles_without_the_view_capability( string $role ) {
		$this->assert_getinvs_refuses( $role, 'coid', 'company' );
	}

	/**
	 * Guards the fix itself: contacts access alone must never be sufficient.
	 * If someone reverts either handler to a contacts-based check, this fails.
	 */
	public function test_contacts_access_alone_is_not_enough_for_invoices() {
		$user_id = self::factory()->user->create( array( 'role' => 'zerobs_quotemgr' ) );
		wp_set_current_user( $user_id );

		$this->assertTrue(
			zeroBSCRM_permsCustomers(),
			'quote manager is expected to have contacts access, otherwise this test proves nothing'
		);
		$this->assertFalse(
			zeroBSCRM_permsViewInvoices(),
			'quote manager must not have invoice view access'
		);
	}

	/**
	 * Clean up request superglobals between tests.
	 */
	public function tear_down(): void {
		unset( $_POST['sec'], $_POST['invid'], $_POST['cid'], $_POST['coid'], $_REQUEST['sec'] );
		parent::tear_down();
	}
}
