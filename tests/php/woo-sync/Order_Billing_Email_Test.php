<?php
/**
 * Tests for how Woo Sync records an order's billing email on the contact.
 *
 * @package Automattic\Jetpack\CRM
 */

// The stand-ins live beside the one test class that uses them, as in Import_Created_Contact_Test.php.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

namespace Automattic\Jetpack\CRM\Woo_Sync\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use Automattic\JetpackCRM\Woo_Sync_Background_Sync_Job;
use PHPUnit\Framework\Attributes\TestDox;

require_once JPCRM_WOO_SYNC_ROOT_PATH . 'includes/class-woo-sync-background-sync-job.php';

/**
 * A sync job that maps orders without WooCommerce or the Woo Sync module loaded.
 */
class Order_Mapping_Sync_Job extends Woo_Sync_Background_Sync_Job {

	/**
	 * Settings order mapping reads, at their defaults.
	 *
	 * @return array
	 */
	public function settings() {
		return array(
			'wccopyship' => 0,
			'wcinv'      => 0,
		);
	}

	/**
	 * Stand-in for the module, answering only what order mapping asks of it.
	 *
	 * @return object
	 */
	public function woosync() {
		return new Module_Stub();
	}
}

/**
 * Stands in for the Woo Sync module.
 */
class Module_Stub {

	/**
	 * Stands in for the module's background sync.
	 *
	 * @var Module_Stub
	 */
	public $background_sync;

	/**
	 * Point background_sync back at this stub.
	 */
	public function __construct() {
		$this->background_sync = $this;
	}

	/**
	 * No tax rates configured.
	 *
	 * @return array
	 */
	public function get_tax_rates_table() {
		return array();
	}

	/**
	 * Map a Woo order status to a CRM status.
	 *
	 * @param string $order_status Woo order status.
	 * @return string
	 */
	public function translate_order_status_to_obj_status( $order_status ) {
		return ucfirst( (string) $order_status );
	}
}

/**
 * Stands in for a WC_Order: only its data matters to these tests.
 */
class Order_Stub {

	/**
	 * Order data, as WC_Order::get_data() returns it.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Set the order data.
	 *
	 * @param array $data Order data.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Order data.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Order status.
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->data['status'];
	}

	/**
	 * Any other getter: empty, or an empty list for plural ones.
	 *
	 * @param string $name Method name.
	 * @param array  $args Arguments.
	 * @return mixed
	 */
	public function __call( $name, $args ) {
		return str_ends_with( $name, 's' ) ? array() : '';
	}
}

/**
 * Test the contact data Woo Sync builds from an order's billing email.
 */
class Order_Billing_Email_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Map an order placed by a logged-in customer whose billing email differs
	 * from their account email.
	 *
	 * @return array The CRM objects Woo Sync would import.
	 */
	private function map_account_order_with_other_billing_email(): array {
		$user_id = self::factory()->user->create( array( 'user_email' => 'account@example.test' ) );

		$job = new Order_Mapping_Sync_Job(
			'store.example.test',
			array(
				'domain' => 'store.example.test',
				'mode'   => 0,
			)
		);

		$order_data = array(
			'id'             => 101,
			'customer_id'    => $user_id,
			'status'         => 'completed',
			'currency'       => 'USD',
			'total'          => '10.00',
			'total_tax'      => '0',
			'discount_total' => '0',
			'shipping_total' => '0',
			'billing'        => array(
				'first_name' => 'Alex',
				'last_name'  => 'Murphy',
				'email'      => 'billing@example.test',
			),
		);

		return $job->woocommerce_order_to_crm_objects( $order_data, new Order_Stub( $order_data ), 101, '101' );
	}

	/**
	 * @testdox An account order's billing email is not added to the contact as an alias.
	 */
	#[TestDox( 'An account order\'s billing email is not added to the contact as an alias.' )]
	public function test_billing_email_is_not_an_alias() {
		$data = $this->map_account_order_with_other_billing_email();

		$this->assertSame( 'account@example.test', $data['contact']['email'] );
		$this->assertArrayNotHasKey( 'aliases', $data['contact'] );
	}

	/**
	 * @testdox An account order's billing email is still kept on the contact as extra meta.
	 */
	#[TestDox( 'An account order\'s billing email is still kept on the contact as extra meta.' )]
	public function test_billing_email_is_kept_as_extra_meta() {
		$data = $this->map_account_order_with_other_billing_email();

		$this->assertSame( 'billing@example.test', $data['contact_extra_meta']['billingemail'] );
	}
}
