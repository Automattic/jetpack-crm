<?php
/**
 * Tests for the status badge helpers.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\FormatHelpers\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Every status in CRM, whether a contact's, an invoice's or a task's, takes its badge color
 * from jpcrm_get_status_badge_intents(). The same map is passed to JS for the list views.
 */
class Status_Badge_Test extends JPCRM_Base_TestCase {

	/**
	 * Remove any filter a test added.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_filters( 'jpcrm_status_badge_intents' );

		parent::tear_down();
	}

	/**
	 * Statuses and the intent each should get.
	 *
	 * @return array
	 */
	public static function status_intents(): array {
		return array(
			'paid invoice'     => array( 'Paid', 'is-stable' ),
			'customer'         => array( 'Customer', 'is-stable' ),
			'unpaid invoice'   => array( 'Unpaid', 'is-medium' ),
			'overdue invoice'  => array( 'Overdue', 'is-high' ),
			'blacklisted'      => array( 'Blacklisted', 'is-high' ),
			'published quote'  => array( 'published', 'is-informational' ),
			'lead'             => array( 'Lead', 'is-draft' ),
			'custom status'    => array( 'Hot prospect', 'is-draft' ),
			'extra whitespace' => array( ' PAID ', 'is-stable' ),
			'empty status'     => array( '', 'is-draft' ),
		);
	}

	/**
	 * @testdox Test that a status gets the badge intent from the map.
	 *
	 * @param string $status       The status.
	 * @param string $intent_class The intent class it should get.
	 */
	#[DataProvider( 'status_intents' )]
	#[TestDox( 'Test that a status gets the badge intent from the map.' )]
	public function test_status_gets_intent_from_map( $status, $intent_class ) {
		$this->assertSame( 'jpcrm-badge ' . $intent_class, jpcrm_status_badge_classes( $status ) );
	}

	/**
	 * @testdox Test that the badge shows the label when given one, escaped.
	 */
	#[TestDox( 'Test that the badge shows the label when given one, escaped.' )]
	public function test_badge_shows_escaped_label() {
		$this->assertSame( '<span class="jpcrm-badge is-stable">Paid</span>', jpcrm_status_badge_html( 'Paid' ) );
		$this->assertSame( '<span class="jpcrm-badge is-stable">Payé &amp; done</span>', jpcrm_status_badge_html( 'Paid', 'Payé & done' ) );
		$this->assertSame( '<span class="jpcrm-badge is-draft">&lt;b&gt;New&lt;/b&gt;</span>', jpcrm_status_badge_html( '<b>New</b>' ) );
	}

	/**
	 * @testdox Test that the map can be filtered for custom statuses.
	 */
	#[TestDox( 'Test that the map can be filtered for custom statuses.' )]
	public function test_intents_can_be_filtered() {
		add_filter(
			'jpcrm_status_badge_intents',
			static function ( $intents ) {
				$intents['hot prospect'] = 'low';
				return $intents;
			}
		);

		$this->assertSame( 'jpcrm-badge is-low', jpcrm_status_badge_classes( 'Hot prospect' ) );
	}

	/**
	 * @testdox Test that the invoice, transaction, quote and task helpers use the map.
	 */
	#[TestDox( 'Test that the invoice, transaction, quote and task helpers use the map.' )]
	public function test_object_helpers_use_the_map() {
		$this->assertSame( 'jpcrm-badge is-high', zeroBSCRM_html_invoiceStatusLabel( array( 'status' => 'Overdue' ) ) );
		$this->assertSame( 'jpcrm-badge is-medium', zeroBSCRM_html_transactionStatusLabel( array( 'status' => 'Pending' ) ) );
		$this->assertSame( 'jpcrm-badge is-stable', zeroBSCRM_html_taskStatusLabel( array( 'complete' => 1 ) ) );
		$this->assertSame( 'jpcrm-badge is-draft', zeroBSCRM_html_taskStatusLabel( array( 'complete' => 0 ) ) );
	}
}
