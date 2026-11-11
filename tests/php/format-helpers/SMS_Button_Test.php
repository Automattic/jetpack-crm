<?php
/**
 * Tests for gating the SMS button on the send capability.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\FormatHelpers\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use WP_User;

/**
 * The SMS button rendered next to a contact's mobile number is handled by the
 * Twilio extension, whose send handler requires `admin_zerobs_sendemails_contacts`.
 * The button itself must be gated on the same capability, otherwise CRM roles
 * that can open a contact but not send get a button that always fails.
 */
class SMS_Button_Test extends JPCRM_Base_TestCase {

	/**
	 * The mobile number the field is rendered with.
	 *
	 * @var string
	 */
	private const MOBILE = '5551234567';

	/**
	 * The capability that gates sending.
	 *
	 * @var string
	 */
	private const SEND_CAP = 'admin_zerobs_sendemails_contacts';

	/**
	 * Field definition for a `mobtel` field, as passed by the edit views.
	 *
	 * @var array
	 */
	private const FIELD = array( 'tel', 'Mobile', 'Mobile number' );

	/**
	 * Render `mobtel` through one of the two edit views and return the markup.
	 *
	 * @param string $renderer Name of the function under test.
	 * @param array  $data     The object data to render from.
	 */
	private function render( string $renderer, array $data = array( 'mobtel' => self::MOBILE ) ): string {
		ob_start();
		$renderer( $data, 'mobtel', self::FIELD );
		return (string) ob_get_clean();
	}

	/**
	 * Create a user with the given role.
	 *
	 * @param string $role A role name, or '' for a user with no role.
	 */
	private function create_user( string $role ): WP_User {
		return get_userdata( self::factory()->user->create( array( 'role' => $role ) ) );
	}

	/**
	 * Create a user with the given role and log in as them.
	 *
	 * @param string $role A role name, or '' for a user with no role.
	 */
	private function log_in_as( string $role ): int {
		return $this->log_in( $this->create_user( $role ) );
	}

	/**
	 * Log in as an existing user. Capabilities are read once when the current
	 * user is set, so any per-user capability change has to happen before this.
	 *
	 * @param WP_User $user The user to log in as.
	 */
	private function log_in( WP_User $user ): int {
		wp_set_current_user( $user->ID );

		return $user->ID;
	}

	/**
	 * Assert a role grants the capabilities this test assumes, so a role
	 * definition that drifts from the source fails loudly here rather than
	 * quietly turning a test into a tautology.
	 *
	 * @param int  $user_id  The user to check.
	 * @param bool $can_send Whether the user is expected to hold the send capability.
	 */
	private function assert_send_capability( int $user_id, bool $can_send ): void {
		$this->assertSame(
			$can_send,
			user_can( $user_id, self::SEND_CAP ),
			'Role does not hold the capabilities this test assumes; check zeroBSCRM_addUserRoles().'
		);
	}

	/**
	 * The two core functions that render the button.
	 *
	 * @return array<string, string>
	 */
	private static function render_sites(): array {
		return array(
			'contact edit view' => 'zeroBSCRM_html_editField',
			'invoice edit view' => 'zeroBSCRM_html_editField_for_invoices',
		);
	}

	/**
	 * Pair every render site with every role, so both sites are covered for
	 * every role without a test method per combination.
	 *
	 * @param array<string, string> $roles Role names keyed by a readable label.
	 *
	 * @return array<string, array{string, string}>
	 */
	private static function every_site_for( array $roles ): array {
		$cases = array();

		foreach ( self::render_sites() as $site_label => $renderer ) {
			foreach ( $roles as $role_label => $role ) {
				$cases[ "{$site_label}, {$role_label}" ] = array( $renderer, $role );
			}
		}

		return $cases;
	}

	/**
	 * Roles that can reach a contact record but cannot send SMS, plus users
	 * with no CRM role at all.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function sites_and_roles_without_send_capability(): array {
		return self::every_site_for(
			array(
				'quote manager'       => 'zerobs_quotemgr',
				'invoice manager'     => 'zerobs_invoicemgr',
				'transaction manager' => 'zerobs_transactionmgr',
				'CRM customer'        => 'zerobs_customer',
				'WP subscriber'       => 'subscriber',
				'no role'             => '',
			)
		);
	}

	/**
	 * Roles that hold the send capability and must keep the button.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function sites_and_roles_with_send_capability(): array {
		return self::every_site_for(
			array(
				'WP administrator' => 'administrator',
				'CRM admin'        => 'zerobs_admin',
				'contact manager'  => 'zerobs_customermgr',
				'mail manager'     => 'zerobs_mailmgr',
			)
		);
	}

	/**
	 * Roles without the capability get no button, at either render site.
	 *
	 * @dataProvider sites_and_roles_without_send_capability
	 *
	 * @param string $renderer The function under test.
	 * @param string $role     A role name.
	 */
	#[DataProvider( 'sites_and_roles_without_send_capability' )]
	public function test_hides_sms_button_without_send_capability( string $renderer, string $role ): void {
		$this->assert_send_capability( $this->log_in_as( $role ), false );

		$this->assertStringNotContainsString( 'data-smsnum', $this->render( $renderer ) );
	}

	/**
	 * Roles with the capability keep the button, at either render site.
	 *
	 * @dataProvider sites_and_roles_with_send_capability
	 *
	 * @param string $renderer The function under test.
	 * @param string $role     A role name.
	 */
	#[DataProvider( 'sites_and_roles_with_send_capability' )]
	public function test_shows_sms_button_with_send_capability( string $renderer, string $role ): void {
		$this->assert_send_capability( $this->log_in_as( $role ), true );

		$this->assertStringContainsString( 'data-smsnum="' . self::MOBILE . '"', $this->render( $renderer ) );
	}

	/**
	 * Capabilities live in the `wp_user_roles` option, so an old site or a role
	 * editor can leave a role holding something its source definition does not.
	 * The gate must follow the capability the user actually has, not the role name.
	 */
	public function test_follows_granted_capability_rather_than_role_name(): void {
		$user = $this->create_user( 'zerobs_quotemgr' );
		$user->add_cap( self::SEND_CAP );
		$this->log_in( $user );

		$this->assertStringContainsString( 'data-smsnum', $this->render( 'zeroBSCRM_html_editField' ) );
	}

	/**
	 * The mirror case: a role that should hold the capability but has had it
	 * taken away loses the button.
	 */
	public function test_follows_revoked_capability_rather_than_role_name(): void {
		$user = $this->create_user( 'zerobs_customermgr' );
		$user->add_cap( self::SEND_CAP, false );
		$this->log_in( $user );

		$this->assertStringNotContainsString( 'data-smsnum', $this->render( 'zeroBSCRM_html_editField' ) );
	}

	/**
	 * The mobile number itself stays visible either way; only the button goes.
	 */
	public function test_mobile_number_still_rendered_without_send_capability(): void {
		$this->log_in_as( 'zerobs_quotemgr' );

		$this->assertStringContainsString( 'value="' . self::MOBILE . '"', $this->render( 'zeroBSCRM_html_editField' ) );
	}

	/**
	 * A user who can send but has no number on the contact gets no button.
	 */
	public function test_no_button_when_contact_has_no_mobile_number(): void {
		$this->log_in_as( 'zerobs_admin' );

		$this->assertStringNotContainsString( 'data-smsnum', $this->render( 'zeroBSCRM_html_editField', array() ) );
	}
}
