<?php

namespace Automattic\Jetpack\CRM\Tests;

use WP_UnitTestCase;
use WPDieException;
use ZeroBSCRM;

/**
 * Test case that ensures we never have global changes to ZBS that bleeds into other tests.
 */
abstract class JPCRM_Base_TestCase extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * The original/initial ZBS instance.
	 *
	 * This can be used to always reset to the original state.
	 * Use-case: someone has to mock part of ZBS for a specific outcome. E.g.: returning a fatal error?
	 *
	 * @since 6.2.0
	 *
	 * @var ?ZeroBSCRM
	 */
	private $original_zbs;

	/**
	 * Store the initial state of ZBS.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// We have to clone the value of $GLOBALS['zbs'] because just assigning
		// it to a static property will still create a reference which means
		// we would never restore the previous state.
		global $zbs;
		$this->original_zbs = clone $zbs;
	}

	/**
	 * Restore the original state of ZBS.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function tear_down(): void {
		parent::tear_down();

		global $zbs;
		$zbs = $this->original_zbs;
	}

	/**
	 * Generate default contact data.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the contact.
	 *
	 * @return array An array of basic contact data.
	 */
	public function generate_contact_data( $args = array() ): array {
		return wp_parse_args(
			$args,
			array(
				'id'       => -1,
				'fname'    => 'John',
				'lname'    => 'Doe',
				'email'    => 'dev@domain.null',
				'status'   => 'Lead',
				'addr1'    => 'My Street 1',
				'addr2'    => 'First floor',
				'city'     => 'New York',
				'country'  => 'US',
				'postcode' => '10001',
				'hometel'  => '11111111',
				'worktel'  => '22222222',
				'mobtel'   => '33333333',
			)
		);
	}

	/**
	 * Generate default invoice data.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the invoice.
	 *
	 * @return array An array of basic contact data.
	 */
	public function generate_invoice_data( $args = array() ): array {
		return wp_parse_args(
			$args,
			array(
				'id_override' => '1',
				'parent'      => 0,
				'status'      => 'Draft',
				'due_date'    => 1690840800,
				'hash'        => 'ISSQndSUjlhJ8feWj2v',
				'lineitems'   => array(
					array(
						'net'      => 3.75,
						'desc'     => 'Dummy product',
						'quantity' => '3',
						'price'    => '1.25',
						'total'    => 3.75,
					),
				),
				'contacts'    => array( 1 ),
				'created'     => -1,
			)
		);
	}

	/**
	 * Generate default transaction data.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the transaction.
	 *
	 * @return array An array of basic transaction data.
	 */
	public function generate_transaction_data( $args = array() ): array {
		return wp_parse_args(
			$args,
			array(
				'title'          => 'Some transaction title',
				'desc'           => 'Some desc',
				'ref'            => 'TransactionReference_1',
				'hash'           => 'mASOpAnf334Pncl1px4',
				'status'         => 'Completed',
				'type'           => 'Sale',
				'currency'       => 'USD',
				'total'          => '150.00',
				'tax'            => '10.00',
				'lineitems'      => false,
				'date'           => 1676000000,
				'date_completed' => 1676923766,
				'created'        => 1675000000,
				'lastupdated'    => 1675000000,
			)
		);
	}

	/**
	 * Generate default company data.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the company.
	 *
	 * @return array An array of basic company data.
	 */
	public function generate_company_data( $args = array() ): array {
		return wp_parse_args(
			$args,
			array(
				'name'     => 'My Company',
				'email'    => 'my@companyemail.com',
				'status'   => 'Lead',
				'addr1'    => 'My Street 1',
				'addr2'    => 'First floor',
				'city'     => 'New York',
				'country'  => 'US',
				'postcode' => '10001',
				'maintel'  => '11111111',
				'sectel'   => '22222222',
			)
		);
	}

	/**
	 * Generate default quote data.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the quote.
	 *
	 * @return array An array of basic quote data.
	 */
	public function generate_quote_data( $args = array() ): array {
		return wp_parse_args(
			$args,
			array(
				'id_override'      => '1',
				'title'            => 'Some quote title',
				'value'            => '150.00',
				'hash'             => 'mASOpAnf334Pncl1px4',
				'template'         => 0,
				'currency'         => 'USD',
				'date'             => 1676000000,
				'notes'            => 'Some notes',
				'send_attachments' => false,
			)
		);
	}

	/**
	 * Generate default task data.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the task.
	 *
	 * @return array An array of basic task data.
	 */
	public function generate_task_data( $args = array() ): array {
		return wp_parse_args(
			$args,
			array(
				'title'            => 'Some task title',
				'desc'             => 'Some description',
				'start'            => 1675000000,
				'end'              => 1676000000,
				'complete'         => false,
				'show_on_portal'   => false,
				'show_on_calendar' => false,
			)
		);
	}

	/**
	 * Run an AJAX handler and return the JSON body it answered with.
	 *
	 * Handlers built on wp_send_json() write the response and exit, so the
	 * output has to be buffered and the exit intercepted; the response body is
	 * the only thing actually observable.
	 *
	 * @param callable $handler The AJAX handler to invoke.
	 * @return mixed The decoded response body, or null where it was not JSON.
	 */
	protected function capture_json_response( callable $handler ) {
		// wp_send_json() calls a bare die() unless wp_doing_ajax() is true, which
		// would take the whole PHP process with it rather than throwing.
		add_filter( 'wp_doing_ajax', '__return_true' );

		$die_handler = static function () {
			return static function () {
				throw new WPDieException( 'sent' );
			};
		};
		add_filter( 'wp_die_ajax_handler', $die_handler );
		add_filter( 'wp_die_json_handler', $die_handler );
		add_filter( 'wp_die_handler', $die_handler );

		ob_start();
		try {
			$handler();
		} catch ( WPDieException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Expected: wp_send_json always terminates.
		}
		$output = ob_get_clean();

		remove_filter( 'wp_die_handler', $die_handler );
		remove_filter( 'wp_die_json_handler', $die_handler );
		remove_filter( 'wp_die_ajax_handler', $die_handler );
		remove_filter( 'wp_doing_ajax', '__return_true' );

		return json_decode( $output, true );
	}
}
