<?php
/**
 * Tests for which contact a WordPress user is in the Client Portal.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Portal\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * A user is a contact once they have confirmed their email address and a
 * contact has that address.
 */
class Portal_Identity_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * The confirmation emails sent during a test.
	 *
	 * @var array[]
	 */
	private $sent = array();

	/**
	 * Catch outgoing email instead of sending it.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->sent = array();
		add_filter( 'pre_wp_mail', array( $this, 'catch_mail' ), 10, 2 );
		wp_set_current_user( 0 );
	}

	/**
	 * Stop catching email.
	 */
	public function tear_down(): void {
		remove_filter( 'pre_wp_mail', array( $this, 'catch_mail' ), 10 );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Record an email and report it as sent.
	 *
	 * @param null|bool $short_circuit Whether to short-circuit wp_mail().
	 * @param array     $atts          The email.
	 * @return bool
	 */
	public function catch_mail( $short_circuit, $atts ) {
		unset( $short_circuit );
		// WordPress sends its own notices when an account's email changes; only ours are of interest.
		if ( false !== strpos( $atts['message'], JPCRM_PORTAL_CONFIRMATION_QUERY_ARG . '=' ) ) {
			$this->sent[] = $atts;
		}
		return true;
	}

	/**
	 * A WordPress user with the given email address.
	 *
	 * @param string $email Email address.
	 * @param string $role  Role.
	 * @return int User ID.
	 */
	private function user( string $email, string $role = 'subscriber' ): int {
		return self::factory()->user->create(
			array(
				'user_email' => $email,
				'role'       => $role,
			)
		);
	}

	/**
	 * A contact with the given email address, optionally linked to a user.
	 *
	 * @param string $email   Email address.
	 * @param int    $user_id User to link, or 0.
	 * @return int Contact ID.
	 */
	private function contact( string $email, int $user_id = 0 ): int {
		$contact_id = (int) $this->add_contact(
			array(
				'email' => $email,
				'fname' => 'Alex',
				'lname' => 'Murphy',
			)
		);
		if ( $user_id ) {
			zeroBS_setCustomerWPID( $contact_id, $user_id );
		}
		return $contact_id;
	}

	/**
	 * The email address a contact has now.
	 *
	 * @param int $contact_id Contact ID.
	 * @return string
	 */
	private function contact_email( int $contact_id ): string {
		global $zbs;
		return (string) $zbs->DAL->contacts->getContactEmail( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * The token in the last confirmation email sent.
	 *
	 * @return string
	 */
	private function last_token(): string {
		$this->assertNotEmpty( $this->sent, 'No email was sent.' );
		$last = end( $this->sent );
		$this->assertSame( 1, preg_match( '/' . JPCRM_PORTAL_CONFIRMATION_QUERY_ARG . '=([A-Za-z0-9]+)/', $last['message'], $matches ), 'The email has no confirmation link.' );
		return $matches[1];
	}

	/**
	 * @testdox A user who has not confirmed their email address is no contact, even when linked to one.
	 */
	#[TestDox( 'A user who has not confirmed their email address is no contact, even when linked to one.' )]
	public function test_unconfirmed_user_is_no_contact() {
		$user_id = $this->user( 'alex@example.test' );
		$this->contact( 'alex@example.test', $user_id );

		$this->assertSame( 0, jpcrm_portal_contact_id_for_user( $user_id ) );
	}

	/**
	 * @testdox A user who has confirmed their email address is the contact that has it.
	 */
	#[TestDox( 'A user who has confirmed their email address is the contact that has it.' )]
	public function test_confirmed_linked_user_is_the_contact() {
		$user_id    = $this->user( 'alex@example.test' );
		$contact_id = $this->contact( 'alex@example.test', $user_id );
		$this->confirm_user_email( $user_id );

		$this->assertSame( $contact_id, jpcrm_portal_contact_id_for_user( $user_id ) );
	}

	/**
	 * @testdox A confirmed user linked to a contact with a different email address is not that contact.
	 */
	#[TestDox( 'A confirmed user linked to a contact with a different email address is not that contact.' )]
	public function test_link_to_a_contact_with_another_address_gives_nothing() {
		$user_id = $this->user( 'sam@example.test' );
		$this->contact( 'alex@example.test', $user_id );
		$this->confirm_user_email( $user_id );

		$this->assertSame( 0, jpcrm_portal_contact_id_for_user( $user_id ) );
	}

	/**
	 * @testdox A confirmed user is the contact with their address even after something else re-pointed its link.
	 */
	#[TestDox( 'A confirmed user is the contact with their address even after something else re-pointed its link.' )]
	public function test_confirmed_user_keeps_their_contact_when_the_link_is_overwritten() {
		global $zbs;

		$user_id    = $this->user( 'alex@example.test' );
		$contact_id = $this->contact( 'alex@example.test', $user_id );
		$this->confirm_user_email( $user_id );

		// A sync that matched the contact by email links it to whoever placed the order.
		$other_id = $this->user( 'mallory@example.test' );
		$this->confirm_user_email( $other_id );
		zeroBS_setCustomerWPID( $contact_id, $other_id );

		$this->assertSame( 0, jpcrm_portal_contact_id_for_user( $other_id ), 'The link alone made another user this contact.' );
		$this->assertSame( $contact_id, jpcrm_portal_contact_id_for_user( $user_id ) );
		$this->assertSame( $user_id, (int) $zbs->DAL->contacts->getContactWPID( $contact_id ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * @testdox Looking up a contact's portal user does not link it to a user with the same email address.
	 */
	#[TestDox( 'Looking up a contact\'s portal user does not link it to a user with the same email address.' )]
	public function test_looking_up_the_portal_user_does_not_link() {
		global $zbs;

		$this->user( 'alex@example.test' );
		$contact_id = $this->contact( 'alex@example.test' );

		$this->assertFalse( zeroBSCRM_getClientPortalUserID( $contact_id ) );
		$this->assertLessThan( 1, (int) $zbs->DAL->contacts->getContactWPID( $contact_id ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * @testdox Opening the emailed link confirms the address and links the contact that has it.
	 */
	#[TestDox( 'Opening the emailed link confirms the address and links the contact that has it.' )]
	public function test_confirming_links_the_contact_with_that_address() {
		$user_id    = $this->user( 'alex@example.test' );
		$contact_id = $this->contact( 'alex@example.test' );

		$this->assertTrue( jpcrm_portal_send_email_confirmation( $user_id ) );
		$this->assertSame( 'alex@example.test', end( $this->sent )['to'] );

		$this->assertFalse( jpcrm_portal_confirm_email_with_token( $user_id, 'not-the-token' ) );
		$this->assertSame( 0, jpcrm_portal_contact_id_for_user( $user_id ) );

		$this->assertTrue( jpcrm_portal_confirm_email_with_token( $user_id, $this->last_token() ) );
		$this->assertSame( $contact_id, jpcrm_portal_contact_id_for_user( $user_id ) );
	}

	/**
	 * @testdox A link works once, and only for the user it was sent to.
	 */
	#[TestDox( 'A link works once, and only for the user it was sent to.' )]
	public function test_a_link_works_once_and_for_one_user() {
		$user_id  = $this->user( 'alex@example.test' );
		$other_id = $this->user( 'sam@example.test' );

		jpcrm_portal_send_email_confirmation( $user_id );
		$token = $this->last_token();

		$this->assertFalse( jpcrm_portal_confirm_email_with_token( $other_id, $token ), 'Another user confirmed with the link.' );
		$this->assertTrue( jpcrm_portal_confirm_email_with_token( $user_id, $token ) );
		$this->assertFalse( jpcrm_portal_confirm_email_with_token( $user_id, $token ), 'The link worked twice.' );
	}

	/**
	 * @testdox A link stops working when it expires, or when the user changes their address.
	 */
	#[TestDox( 'A link stops working when it expires, or when the user changes their address.' )]
	public function test_a_link_expires_and_is_tied_to_the_address() {
		$user_id = $this->user( 'alex@example.test' );

		jpcrm_portal_send_email_confirmation( $user_id );
		$token   = $this->last_token();
		$pending = get_user_meta( $user_id, JPCRM_PORTAL_CONFIRMATION_META, true );

		update_user_meta( $user_id, JPCRM_PORTAL_CONFIRMATION_META, array_merge( $pending, array( 'expires' => time() - 1 ) ) );
		$this->assertFalse( jpcrm_portal_confirm_email_with_token( $user_id, $token ), 'An expired link worked.' );

		update_user_meta( $user_id, JPCRM_PORTAL_CONFIRMATION_META, $pending );
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'someone-else@example.test',
			)
		);
		$this->assertFalse( jpcrm_portal_confirm_email_with_token( $user_id, $token ), 'A link sent to the old address confirmed the new one.' );
		$this->assertFalse( jpcrm_portal_email_is_confirmed( $user_id ) );
	}

	/**
	 * @testdox A second email cannot be asked for straight away.
	 */
	#[TestDox( 'A second email cannot be asked for straight away.' )]
	public function test_resending_has_a_wait() {
		$user_id = $this->user( 'alex@example.test' );

		$this->assertTrue( jpcrm_portal_send_email_confirmation( $user_id ) );
		$this->assertWPError( jpcrm_portal_send_email_confirmation( $user_id ) );
		$this->assertCount( 1, $this->sent );
	}

	/**
	 * @testdox A user who changes their own address to another contact's is neither contact, and neither contact changes.
	 */
	#[TestDox( 'A user who changes their own address to another contact\'s is neither contact, and neither contact changes.' )]
	public function test_changing_to_another_contacts_address_gives_nothing() {
		$user_id     = $this->user( 'mallory@example.test' );
		$own_contact = $this->contact( 'mallory@example.test', $user_id );
		$this->confirm_user_email( $user_id );
		$other_contact = $this->contact( 'alex@example.test' );

		wp_set_current_user( $user_id );
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'alex@example.test',
			)
		);

		$this->assertSame( 0, jpcrm_portal_contact_id_for_user( $user_id ) );
		$this->assertSame( 'mallory@example.test', $this->contact_email( $own_contact ), 'Their own contact moved to the new address.' );
		$this->assertSame( 'alex@example.test', $this->contact_email( $other_contact ) );
		$this->assertSame( 'alex@example.test', end( $this->sent )['to'], 'The confirmation did not go to the new address.' );
	}

	/**
	 * @testdox A contact follows its user to a new address once the user confirms it.
	 */
	#[TestDox( 'A contact follows its user to a new address once the user confirms it.' )]
	public function test_contact_follows_a_confirmed_change() {
		$user_id    = $this->user( 'alex@example.test' );
		$contact_id = $this->contact( 'alex@example.test', $user_id );
		$this->confirm_user_email( $user_id );

		wp_set_current_user( $user_id );
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'alex@new.example.test',
			)
		);

		$this->assertSame( 'alex@example.test', $this->contact_email( $contact_id ), 'The contact moved before the new address was confirmed.' );
		$this->assertSame( 0, jpcrm_portal_contact_id_for_user( $user_id ) );

		$this->assertTrue( jpcrm_portal_confirm_email_with_token( $user_id, $this->last_token() ) );

		$this->assertSame( 'alex@new.example.test', $this->contact_email( $contact_id ) );
		$this->assertSame( $contact_id, jpcrm_portal_contact_id_for_user( $user_id ) );
	}

	/**
	 * @testdox An administrator changing a user's address confirms it, and the contact follows.
	 */
	#[TestDox( 'An administrator changing a user\'s address confirms it, and the contact follows.' )]
	public function test_admin_change_confirms_and_moves_the_contact() {
		$user_id    = $this->user( 'alex@example.test' );
		$contact_id = $this->contact( 'alex@example.test', $user_id );

		wp_set_current_user( $this->user( 'admin@example.test', 'administrator' ) );
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'alex@new.example.test',
			)
		);

		$this->assertSame( 'alex@new.example.test', $this->contact_email( $contact_id ) );
		$this->assertSame( $contact_id, jpcrm_portal_contact_id_for_user( $user_id ) );
		$this->assertEmpty( $this->sent, 'A confirmation email was sent for a change an administrator made.' );
	}

	/**
	 * @testdox An administrator can bring back a user who changed their own address and could not confirm it.
	 */
	#[TestDox( 'An administrator can bring back a user who changed their own address and could not confirm it.' )]
	public function test_admin_change_recovers_a_user_with_an_unconfirmed_address() {
		$user_id    = $this->user( 'alex@example.test' );
		$contact_id = $this->contact( 'alex@example.test', $user_id );
		$this->confirm_user_email( $user_id );

		// The user mistypes their new address, so can never confirm it.
		wp_set_current_user( $user_id );
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'alex@typo.example.test',
			)
		);
		$this->assertSame( 0, jpcrm_portal_contact_id_for_user( $user_id ) );

		// An administrator sets the right one.
		wp_set_current_user( $this->user( 'admin@example.test', 'administrator' ) );
		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'alex@new.example.test',
			)
		);

		$this->assertSame( 'alex@new.example.test', $this->contact_email( $contact_id ) );
		$this->assertSame( $contact_id, jpcrm_portal_contact_id_for_user( $user_id ) );
	}

	/**
	 * @testdox WooCommerce confirming a customer's email address confirms it for the portal.
	 */
	#[TestDox( 'WooCommerce confirming a customer\'s email address confirms it for the portal.' )]
	public function test_woocommerce_verification_confirms() {
		$user_id    = $this->user( 'alex@example.test' );
		$contact_id = $this->contact( 'alex@example.test' );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's action.
		do_action( 'woocommerce_customer_email_verified', $user_id );

		$this->assertSame( $contact_id, jpcrm_portal_contact_id_for_user( $user_id ) );
	}

	/**
	 * @testdox A profile update only changes the name of a contact the user is.
	 */
	#[TestDox( 'A profile update only changes the name of a contact the user is.' )]
	public function test_profile_update_only_reaches_the_users_own_contact() {
		global $zbs;

		// Somebody who merely registered with a contact's address.
		$stranger_id = $this->user( 'alex@example.test' );
		$contact_id  = $this->contact( 'alex@example.test' );

		wp_set_current_user( $stranger_id );
		wp_update_user(
			array(
				'ID'         => $stranger_id,
				'first_name' => 'Mallory',
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->assertSame( 'Alex', $contact['fname'], 'An unconfirmed user changed a contact\'s name.' );

		// The same user, once linked and confirmed.
		zeroBS_setCustomerWPID( $contact_id, $stranger_id );
		$this->confirm_user_email( $stranger_id );
		wp_update_user(
			array(
				'ID'         => $stranger_id,
				'first_name' => 'Alexandra',
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->assertSame( 'Alexandra', $contact['fname'] );
	}

	/**
	 * @testdox An account the CRM creates for a contact has nothing to confirm.
	 */
	#[TestDox( 'An account the CRM creates for a contact has nothing to confirm.' )]
	public function test_crm_created_account_is_confirmed() {
		$contact_id = $this->contact( 'alex@example.test' );

		zeroBSCRM_createClientPortalUserFromRecord( $contact_id );
		$user = get_user_by( 'email', 'alex@example.test' );

		$this->assertInstanceOf( \WP_User::class, $user );
		$this->assertSame( $contact_id, jpcrm_portal_contact_id_for_user( $user->ID ) );
	}

	/**
	 * @testdox The portal asks customers to confirm, and does not ask staff.
	 */
	#[TestDox( 'The portal asks customers to confirm, and does not ask staff.' )]
	public function test_who_is_asked_to_confirm() {
		$customer_id = $this->user( 'alex@example.test' );

		wp_set_current_user( $customer_id );
		$this->assertTrue( jpcrm_portal_user_needs_email_confirmation() );

		$this->confirm_user_email( $customer_id );
		$this->assertFalse( jpcrm_portal_user_needs_email_confirmation() );

		wp_set_current_user( $this->user( 'admin@example.test', 'administrator' ) );
		$this->assertFalse( jpcrm_portal_user_needs_email_confirmation() );
	}

	/**
	 * Render the portal dashboard as the current user.
	 *
	 * @return string
	 */
	private function render_dashboard(): string {
		global $zbs;

		$endpoint = null;
		foreach ( $zbs->modules->portal->endpoints as $candidate ) {
			if ( 'dashboard' === $candidate->slug ) {
				$endpoint = $candidate;
			}
		}
		$this->assertNotNull( $endpoint, 'The portal has no dashboard endpoint.' );

		ob_start();
		$endpoint->perform_endpoint_action();
		return (string) ob_get_clean();
	}

	/**
	 * @testdox The portal shows an unconfirmed user the confirmation screen, sends the email, and shows nothing else.
	 */
	#[TestDox( 'The portal shows an unconfirmed user the confirmation screen, sends the email, and shows nothing else.' )]
	public function test_portal_shows_the_confirmation_screen() {
		$user_id = $this->user( 'alex@example.test' );
		$this->contact( 'alex@example.test', $user_id );
		wp_set_current_user( $user_id );

		$html = $this->render_dashboard();

		$this->assertStringContainsString( 'Confirm your email address', $html );
		$this->assertStringContainsString( 'alex@example.test', $html );
		$this->assertStringNotContainsString( 'zbs-portal-nav', $html, 'The portal navigation was shown to an unconfirmed user.' );
		$this->assertCount( 1, $this->sent, 'Viewing the screen did not send the confirmation email.' );

		// Viewing it again does not send another.
		$this->render_dashboard();
		$this->assertCount( 1, $this->sent );

		// Once confirmed, the dashboard is shown instead.
		jpcrm_portal_confirm_email_with_token( $user_id, $this->last_token() );
		$this->assertStringNotContainsString( 'Confirm your email address', $this->render_dashboard() );
	}
}
