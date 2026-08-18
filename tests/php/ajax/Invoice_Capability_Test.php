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
use WPDieException;

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
			'administrator'  => array( 'administrator' ),
			'CRM admin'      => array( 'zerobs_admin' ),
			'invoice mgr'    => array( 'zerobs_invoicemgr' ),
			'customer mgr'   => array( 'zerobs_customermgr' ),
			'mail mgr'       => array( 'zerobs_mailmgr' ),
		);
	}

	/**
	 * Roles with CRM access but no invoice capability at all.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function refused_roles(): array {
		return array(
			'quote mgr'       => array( 'zerobs_quotemgr' ),
			'transaction mgr' => array( 'zerobs_transactionmgr' ),
		);
	}

	/**
	 * Capture what a handler passes to wp_send_json* without exiting.
	 *
	 * Status codes are not checked here. wp_send_json() only sets one when
	 * headers_sent() is false, which it never is under PHPUnit, so the response
	 * body is the only thing actually observable.
	 *
	 * @param callable $handler The AJAX handler to invoke.
	 * @return mixed The decoded response body.
	 */
	private function capture_json_response( callable $handler ) {
		// wp_send_json() calls a bare die() unless wp_doing_ajax() is true, which
		// would take the whole PHP process with it rather than throwing.
		add_filter( 'wp_doing_ajax', '__return_true' );

		$die_handler = static function () {
			return static function () {
				throw new WPDieException( 'sent' );
			};
		};
		add_filter( 'wp_die_ajax_handler', $die_handler );
		add_filter( 'wp_die_handler', $die_handler );

		ob_start();
		try {
			$handler();
		} catch ( WPDieException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Expected: wp_send_json always terminates.
		}
		$output = ob_get_clean();

		remove_filter( 'wp_die_ajax_handler', $die_handler );
		remove_filter( 'wp_die_handler', $die_handler );
		remove_filter( 'wp_doing_ajax', '__return_true' );

		return json_decode( $output, true );
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
	 * Build an invoice attached to a contact.
	 *
	 * @return array{contact: int, invoice: int}
	 */
	private function seed_invoice(): array {
		$contact_id = $this->add_contact();
		$invoice_id = $this->add_invoice(
			array(
				'contact' => array( $contact_id ),
				'status'  => 'Unpaid',
			)
		);

		return array(
			'contact' => $contact_id,
			'invoice' => $invoice_id,
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
		$this->assertArrayHasKey( 'invoiceObj', $response, "$role should reach invoice data" );
		$this->assertArrayNotHasKey( 'success', $response, "$role should not get an error response" );
	}

	/**
	 * getinvs used to gate on zeroBSCRM_permsCustomers(), so contacts access was
	 * enough to pull every invoice for a contact.
	 *
	 * @param string $role The role under test.
	 */
	#[DataProvider( 'refused_roles' )]
	public function test_getinvs_refuses_roles_without_the_view_capability( string $role ) {
		$seed = $this->seed_invoice();
		$this->acting_as( $role, 'zbscrmjs-glob-ajax-nonce' );
		$_POST['cid'] = $seed['contact'];

		$response = $this->capture_json_response( 'zeroBSCRM_AJAX_getCustInvs' );

		$this->assertSame( array(), $response, "$role should get no invoices back" );
	}

	/**
	 * @param string $role The role under test.
	 */
	#[DataProvider( 'allowed_roles' )]
	public function test_getinvs_allows_roles_with_the_view_capability( string $role ) {
		$seed = $this->seed_invoice();
		$this->acting_as( $role, 'zbscrmjs-glob-ajax-nonce' );
		$_POST['cid'] = $seed['contact'];

		$response = $this->capture_json_response( 'zeroBSCRM_AJAX_getCustInvs' );

		$this->assertNotEmpty( $response, "$role should get the contact's invoices" );
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
		unset( $_POST['sec'], $_POST['invid'], $_POST['cid'], $_REQUEST['sec'] );
		parent::tear_down();
	}
}
