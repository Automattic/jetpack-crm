<?php
/**
 * Tests for accepting a quote from its easy-access link.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Ajax\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * A quote can be accepted while it is published, and not once it is back in Draft.
 */
class Quote_Accept_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * The quote's easy-access hash.
	 *
	 * @var string
	 */
	private const HASH = 'mASOpAnf334Pncl1px4';

	/**
	 * Turn easy-access links on.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $zbs;
		$zbs->settings->update( 'easyaccesslinks', 1 );
	}

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
	 * @testdox A published quote can be accepted from its easy-access link.
	 */
	#[TestDox( 'A published quote can be accepted from its easy-access link.' )]
	public function test_published_quote_can_be_accepted_by_link() {
		$quote_id = $this->add_quote_with_template( 1 );

		$this->accept_by_link_as_visitor( $quote_id );

		$this->assertNotEmpty( $this->accepted( $quote_id ) );
	}

	/**
	 * @testdox A quote returned to Draft can't be accepted from its old easy-access link.
	 */
	#[TestDox( 'A quote returned to Draft can\'t be accepted from its old easy-access link.' )]
	public function test_draft_quote_cannot_be_accepted_by_link() {
		$quote_id = $this->add_quote_with_template( -1 );

		$response = $this->accept_by_link_as_visitor( $quote_id );

		$this->assertFalse( $response['success'] ?? null );
		$this->assertEmpty( $this->accepted( $quote_id ) );
	}

	/**
	 * @testdox Staff with quote access can still accept a Draft quote.
	 */
	#[TestDox( 'Staff with quote access can still accept a Draft quote.' )]
	public function test_staff_can_accept_draft_quote() {
		$quote_id = $this->add_quote_with_template( -1 );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->post_accept( $quote_id, '' );

		$this->assertNotEmpty( $this->accepted( $quote_id ) );
	}

	/**
	 * @testdox An accepted quote can't be accepted again.
	 */
	#[TestDox( 'An accepted quote can\'t be accepted again.' )]
	public function test_accepted_quote_cannot_be_accepted_again() {
		$quote_id = $this->add_quote_with_template( 1 );

		$this->accept_by_link_as_visitor( $quote_id );
		$this->assertNotEmpty( $this->accepted( $quote_id ) );

		$response = $this->accept_by_link_as_visitor( $quote_id );

		$this->assertFalse( $response['success'] ?? null );
	}

	/**
	 * @testdox A contact can't view their quote while it's a Draft with a blank template.
	 */
	#[TestDox( 'A contact can\'t view their quote while it\'s a Draft with a blank template.' )]
	public function test_contact_cannot_view_blank_template_draft() {
		$user = $this->contact_user();

		$draft_id     = $this->add_quote_with_template( 0, $user['contact_id'] );
		$published_id = $this->add_quote_with_template( 1, $user['contact_id'] );

		$this->assertFalse( jpcrm_can_wp_user_view_object( $user['wp_user'], $draft_id, ZBS_TYPE_QUOTE ) );
		$this->assertTrue( jpcrm_can_wp_user_view_object( $user['wp_user'], $published_id, ZBS_TYPE_QUOTE ) );
	}

	/**
	 * Add a quote. A template ID of 0 or less is how CRM marks a quote as Draft.
	 *
	 * @param int $template   Template ID.
	 * @param int $contact_id Contact to assign it to, or 0 for none.
	 * @return int Quote ID.
	 */
	private function add_quote_with_template( int $template, int $contact_id = 0 ): int {
		global $zbs;

		$data = $this->generate_quote_data(
			array(
				'template'    => $template,
				'hash'        => self::HASH,
				'id_override' => (string) wp_rand( 2, 99999 ),
			)
		);
		if ( $contact_id > 0 ) {
			$data['contacts'] = array( $contact_id );
		}

		return (int) $zbs->DAL->quotes->addUpdateQuote( array( 'data' => $data ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * A WordPress user and the contact they're linked to.
	 *
	 * @return array{wp_user: \WP_User, contact_id: int}
	 */
	private function contact_user(): array {
		$email      = 'quote-contact@example.test';
		$user_id    = self::factory()->user->create( array( 'user_email' => $email ) );
		$contact_id = (int) $this->add_contact( array( 'email' => $email ) );
		zeroBS_setCustomerWPID( $contact_id, $user_id );

		return array(
			'wp_user'    => new \WP_User( $user_id ),
			'contact_id' => $contact_id,
		);
	}

	/**
	 * Accept a quote the way its easy-access page does, as a logged-out visitor.
	 *
	 * @param int $quote_id Quote ID.
	 * @return mixed The decoded JSON response.
	 */
	private function accept_by_link_as_visitor( int $quote_id ) {
		wp_set_current_user( 0 );

		return $this->post_accept( $quote_id, self::HASH );
	}

	/**
	 * Post an accept request to the AJAX handler.
	 *
	 * @param int    $quote_id Quote ID.
	 * @param string $hash     Easy-access hash, or '' for none.
	 * @return mixed The decoded JSON response.
	 */
	private function post_accept( int $quote_id, string $hash ) {
		$_POST = array(
			'sec'          => wp_create_nonce( 'zbscrmquo-nonce' ),
			'zbs-quote-id' => $quote_id,
		);
		if ( '' !== $hash ) {
			$_POST['zbs-quote-hash'] = $hash;
		}
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- simulating the request the handler verifies.

		return $this->capture_json_response( 'ZeroBSCRM_accept_quote' );
	}

	/**
	 * When the quote was accepted, or empty if it wasn't.
	 *
	 * @param int $quote_id Quote ID.
	 * @return mixed
	 */
	private function accepted( int $quote_id ) {
		global $zbs;

		$quote = $zbs->DAL->quotes->getQuote( $quote_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return $quote['accepted'] ?? '';
	}
}
