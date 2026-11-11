<?php
/**
 * Tests for the log type saved from the activity log box.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Ajax\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Log types are saved as plain labels.
 */
class Log_Type_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * Restore request state.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$_POST    = array();
		$_REQUEST = array();
		parent::tear_down();
	}

	/**
	 * @testdox A log type keeps its words and loses quotes, backslashes and angle brackets.
	 */
	#[TestDox( 'A log type keeps its words and loses quotes, backslashes and angle brackets.' )]
	public function test_sanitize_log_type() {
		$this->assertSame( 'Note', jpcrm_sanitize_log_type( 'Note' ) );
		$this->assertSame( 'Woo Order', jpcrm_sanitize_log_type( 'Woo Order' ) );
		$this->assertSame( 'Note x=y', jpcrm_sanitize_log_type( wp_slash( 'Note" x="y' ) ) );
		$this->assertSame( 'Clients call', jpcrm_sanitize_log_type( wp_slash( "Client's call" ) ) );
	}

	/**
	 * @testdox A log added from the activity log box is stored with a plain type.
	 */
	#[TestDox( 'A log added from the activity log box is stored with a plain type.' )]
	public function test_added_log_type_is_stored_plain() {
		global $wpdb, $ZBSCRM_t; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		$contact_id = $this->add_contact();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$_POST    = array(
			'sec'           => wp_create_nonce( 'zbscrmjs-ajax-nonce-logs' ),
			'zbsnagainstid' => $contact_id,
			'zbsntype'      => wp_slash( 'Note" data-x="1' ),
			'zbsnshortdesc' => 'Called about the quote',
			'zbsnobjtype'   => 'zerobs_customer',
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- simulating the request the handler verifies.

		$this->capture_json_response( 'zeroBSCRM_AJAX_addLog' );

		$type = $wpdb->get_var( 'SELECT zbsl_type FROM ' . $ZBSCRM_t['logs'] . ' ORDER BY ID DESC LIMIT 1' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		$this->assertIsString( $type, 'The log was not saved.' );
		$this->assertStringNotContainsString( '"', $type );
		$this->assertStringStartsWith( 'note', $type ); // Stored permified: lower case, spaces as underscores.
	}
}
