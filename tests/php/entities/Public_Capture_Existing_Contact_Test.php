<?php
/**
 * Tests for what a Jetpack form submission or a GiveWP donation changes on a
 * contact that already exists.
 *
 * Both name the contact by an email address typed in by whoever submits, so
 * they fill in what the contact is missing and leave what it already has.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use Automattic\JetpackCRM\JPCRM_GiveWP;
use Automattic\JetpackCRM\Mailpoet_Background_Sync_Job;
use PHPUnit\Framework\Attributes\TestDox;

require_once JPCRM_MODULES_PATH . 'givewp/class-jpcrm-givewp.php';

// The MailPoet module defines these when it is switched on.
if ( ! defined( 'JPCRM_MAILPOET_MODE_LOCAL' ) ) {
	define( 'JPCRM_MAILPOET_MODE_LOCAL', 0 );
}
require_once JPCRM_MODULES_PATH . 'mailpoet/includes/class-mailpoet-background-sync-job.php';

/**
 * Test that the two captures keep an existing contact's details.
 */
class Public_Capture_Existing_Contact_Test extends JPCRM_Base_Integration_TestCase {

	/**
	 * The email address the contact and each submission share.
	 *
	 * @var string
	 */
	private const CONTACT_EMAIL = 'alex.murphy@example.test';

	/**
	 * Create a contact with details a CRM user has filled in by hand.
	 *
	 * @param array $overrides Field overrides.
	 * @return int The new contact ID.
	 */
	private function create_contact_with_details( array $overrides = array() ): int {

		$id = $this->add_contact(
			array_merge(
				array(
					'email'    => self::CONTACT_EMAIL,
					'status'   => 'Customer',
					'fname'    => 'Alex',
					'lname'    => 'Murphy',
					'addr1'    => '19 Prospect Hill',
					'city'     => 'Cork',
					'postcode' => 'T12 XY45',
					'hometel'  => '+353 21 427 0000',
					'mobtel'   => '+353 87 000 0000',
				),
				$overrides
			)
		);

		$this->assertGreaterThan( 0, $id, 'Failed to create the contact under test.' );

		return $id;
	}

	/**
	 * A stand-in for a Jetpack Forms field: an id, a type and a value.
	 *
	 * @param string $id    The field id, e.g. 'jetpackcrm-city'.
	 * @param string $type  The field type, e.g. 'email'.
	 * @param mixed  $value The submitted value.
	 * @return object
	 */
	private function form_field( string $id, string $type, $value ) {
		return new class( $id, $type, $value ) {

			/**
			 * The submitted value.
			 *
			 * @var mixed
			 */
			public $value;

			/**
			 * The form the field belongs to.
			 *
			 * @var object
			 */
			public $form;

			/**
			 * The field's attributes.
			 *
			 * @var array
			 */
			private $attributes;

			/**
			 * Set the field up.
			 *
			 * @param string $id    The field id.
			 * @param string $type  The field type.
			 * @param mixed  $value The submitted value.
			 */
			public function __construct( $id, $type, $value ) {
				$this->value      = $value;
				$this->form       = (object) array( 'attributes' => array() );
				$this->attributes = array(
					'id'   => $id,
					'type' => $type,
				);
			}

			/**
			 * Read an attribute, as Contact_Form_Field does.
			 *
			 * @param string $key The attribute name.
			 * @return mixed
			 */
			public function get_attribute( $key ) {
				return isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : null;
			}
		};
	}

	/**
	 * Put a submission through the Jetpack Forms capture.
	 *
	 * @param object[] $fields The submitted fields.
	 * @return void
	 */
	private function submit_jetpack_form( array $fields ) {
		zero_bs_crm_capture_jetpack_form(
			1,
			$fields,
			false,
			array(
				'entry_title'     => 'Contact us',
				'entry_permalink' => 'https://example.test/contact/',
				'feedback_id'     => 'abc123',
			)
		);
	}

	/**
	 * A plain contact form: name, email and telephone, found by field type.
	 *
	 * @return object[]
	 */
	private function plain_form_fields(): array {
		return array(
			$this->form_field( 'g1-name', 'name', 'Sam Other' ),
			$this->form_field( 'g1-email', 'email', self::CONTACT_EMAIL ),
			$this->form_field( 'g1-phone', 'telephone', '000' ),
		);
	}

	/**
	 * Run a donation's donor info through the GiveWP integration.
	 *
	 * @param array $user_info GiveWP user_info.
	 * @return int Contact ID.
	 */
	private function add_donor( array $user_info ): int {
		$integration = new JPCRM_GiveWP();
		$method      = new \ReflectionMethod( $integration, 'add_update_donor' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true ); // A no-op from PHP 8.1, and deprecated in 8.5.
		}

		return (int) $method->invoke(
			$integration,
			123,
			array(
				'user_info'       => $user_info,
				'give_form_title' => 'Test form',
			)
		);
	}

	/**
	 * @testdox A Jetpack form submission leaves an existing contact's details as they are.
	 */
	#[TestDox( 'A Jetpack form submission leaves an existing contact\'s details as they are.' )]
	public function test_jetpack_form_leaves_existing_details() {
		global $zbs;

		$contact_id = $this->create_contact_with_details();

		$this->submit_jetpack_form( $this->plain_form_fields() );

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertSame( 'Alex', $contact['fname'], 'A submission replaced the first name.' );
		$this->assertSame( 'Murphy', $contact['lname'], 'A submission replaced the last name.' );
		$this->assertSame( '+353 21 427 0000', $contact['hometel'], 'A submission replaced the telephone number.' );
		$this->assertSame( '19 Prospect Hill', $contact['addr1'], 'A submission emptied the address.' );
		$this->assertSame( 'Cork', $contact['city'], 'A submission emptied the city.' );
		$this->assertSame( '+353 87 000 0000', $contact['mobtel'], 'A submission emptied the mobile number.' );
		$this->assertSame( 'Customer', $contact['status'], 'A submission changed the status.' );
	}

	/**
	 * @testdox A Jetpack form submission still fills in what an existing contact is missing.
	 */
	#[TestDox( 'A Jetpack form submission still fills in what an existing contact is missing.' )]
	public function test_jetpack_form_fills_in_missing_details() {
		global $zbs;

		$contact_id = $this->create_contact_with_details(
			array(
				'hometel' => '',
				'city'    => '',
			)
		);

		$this->submit_jetpack_form(
			array(
				$this->form_field( 'jetpackcrm-email', 'email', self::CONTACT_EMAIL ),
				$this->form_field( 'jetpackcrm-hometel', 'telephone', '+353 21 111 1111' ),
				$this->form_field( 'jetpackcrm-city', 'text', 'Galway' ),
				$this->form_field( 'jetpackcrm-addr1', 'text', '1 Other Street' ),
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertSame( '+353 21 111 1111', $contact['hometel'], 'A blank telephone number was not filled in.' );
		$this->assertSame( 'Galway', $contact['city'], 'A blank city was not filled in.' );
		$this->assertSame( '19 Prospect Hill', $contact['addr1'], 'A submission replaced the address.' );
	}

	/**
	 * @testdox A Jetpack form submission leaves an existing contact's custom field as it is.
	 */
	#[TestDox( 'A Jetpack form submission leaves an existing contact\'s custom field as it is.' )]
	public function test_jetpack_form_leaves_existing_custom_field() {
		global $zbs;

		$this->register_custom_field( 'account-ref', 'text', 'Account reference', array( 'customers' => ZBS_TYPE_CONTACT ) );

		$contact_id = $this->create_contact_with_details( array( 'account-ref' => 'A-100' ) );

		$this->submit_jetpack_form(
			array(
				$this->form_field( 'jetpackcrm-email', 'email', self::CONTACT_EMAIL ),
				$this->form_field( 'jetpackcrm-account-ref', 'text', 'B-200' ),
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertSame( 'A-100', $contact['account-ref'], 'A submission replaced the custom field.' );
	}

	/**
	 * @testdox A Jetpack form submission still records the form and adds its tags.
	 */
	#[TestDox( 'A Jetpack form submission still records the form and adds its tags.' )]
	public function test_jetpack_form_still_records_source_and_tags() {
		global $zbs;

		$contact_id = $this->create_contact_with_details();

		$zbs->DAL->contacts->addUpdateContactTags( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			array(
				'id'        => $contact_id,
				'tag_input' => array( 'VIP' ),
				'mode'      => 'append',
			)
		);

		$this->submit_jetpack_form(
			array(
				$this->form_field( 'jetpackcrm-email', 'email', self::CONTACT_EMAIL ),
				$this->form_field( 'jetpackcrm-tags', 'text', 'Newsletter' ),
			)
		);

		$contact = $zbs->DAL->contacts->getContact( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$contact_id,
			array(
				'withExternalSources' => true,
				'withTags'            => true,
			)
		);

		$this->assertContains( 'jetpack_form', wp_list_pluck( $contact['external_sources'], 'source' ), 'The form was not recorded as a source.' );
		$this->assertContains( 'Newsletter', wp_list_pluck( $contact['tags'], 'name' ), 'The tag was not added.' );
		$this->assertContains( 'VIP', wp_list_pluck( $contact['tags'], 'name' ), 'A submission removed a tag the contact already had.' );
	}

	/**
	 * @testdox A Jetpack form submission still creates a new contact with everything it sent.
	 */
	#[TestDox( 'A Jetpack form submission still creates a new contact with everything it sent.' )]
	public function test_jetpack_form_still_creates_new_contacts() {
		global $zbs;

		$this->submit_jetpack_form( $this->plain_form_fields() );

		$contact = $zbs->DAL->contacts->getContact( -1, array( 'email' => self::CONTACT_EMAIL ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertIsArray( $contact, 'No contact was created.' );
		$this->assertSame( 'Sam', $contact['fname'] );
		$this->assertSame( 'Other', $contact['lname'] );
		$this->assertSame( '000', $contact['hometel'] );
	}

	/**
	 * @testdox The filter can let submissions replace fields again.
	 */
	#[TestDox( 'The filter can let submissions replace fields again.' )]
	public function test_filter_can_let_submissions_replace_fields() {
		global $zbs;

		$contact_id = $this->create_contact_with_details();

		add_filter( 'jpcrm_public_submission_fields_to_protect', '__return_empty_array' );
		try {
			$this->submit_jetpack_form( $this->plain_form_fields() );
		} finally {
			remove_filter( 'jpcrm_public_submission_fields_to_protect', '__return_empty_array' );
		}

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertSame( 'Sam', $contact['fname'], 'The filter did not restore the previous behaviour.' );
		$this->assertSame( '19 Prospect Hill', $contact['addr1'], 'Fields the form did not send are still kept.' );
	}

	/**
	 * Import a MailPoet subscriber the way the MailPoet sync does.
	 *
	 * @param array $subscriber Subscriber fields.
	 * @return int The contact ID.
	 */
	private function import_mailpoet_subscriber( array $subscriber ): int {
		global $zbs;

		$had_module  = isset( $zbs->modules->mailpoet );
		$real_module = $had_module ? $zbs->modules->mailpoet : null;

		// The job only asks the module for its settings.
		$zbs->modules->mailpoet = (object) array(
			'settings' => new class() {
				/**
				 * The settings the import reads.
				 *
				 * @return array
				 */
				public function getAll() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
					return array(
						'tag_with_list' => 0,
						'tag_with_tags' => 0,
					);
				}
			},
		);

		$job        = new Mailpoet_Background_Sync_Job();
		$contact_id = (int) $job->import_subscriber(
			array_merge(
				array(
					'id'         => 7001,
					'created_at' => '2026-01-05 10:00:00',
				),
				$subscriber
			)
		);

		if ( $had_module ) {
			$zbs->modules->mailpoet = $real_module;
		} else {
			unset( $zbs->modules->mailpoet );
		}

		return $contact_id;
	}

	/**
	 * @testdox A new MailPoet subscriber leaves an existing contact's name as it is.
	 */
	#[TestDox( 'A new MailPoet subscriber leaves an existing contact\'s name as it is.' )]
	public function test_mailpoet_subscriber_leaves_existing_name() {
		global $zbs;

		$contact_id = $this->create_contact_with_details();

		$imported_id = $this->import_mailpoet_subscriber(
			array(
				'email'      => self::CONTACT_EMAIL,
				'first_name' => 'Sam',
				'last_name'  => 'Other',
				'wp_user_id' => 4242,
			)
		);

		$this->assertSame( $contact_id, $imported_id, 'The subscriber did not match the existing contact.' );

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertSame( 'Alex', $contact['fname'], 'A new subscriber replaced the first name.' );
		$this->assertSame( 'Murphy', $contact['lname'], 'A new subscriber replaced the last name.' );
		$this->assertNotEquals( 4242, $contact['wpid'], 'A new subscriber changed the linked user.' );

		$linked = $zbs->DAL->contacts->getContact( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			-1,
			array(
				'externalSource'    => 'mailpoet',
				'externalSourceUID' => 7001,
			)
		);
		$this->assertSame( $contact_id, (int) $linked['id'], 'The subscriber was not recorded as a source.' );
	}

	/**
	 * @testdox A new MailPoet subscriber fills in a name the contact is missing.
	 */
	#[TestDox( 'A new MailPoet subscriber fills in a name the contact is missing.' )]
	public function test_mailpoet_subscriber_fills_in_missing_name() {
		global $zbs;

		$contact_id = $this->create_contact_with_details(
			array(
				'fname' => '',
				'lname' => '',
			)
		);

		$this->import_mailpoet_subscriber(
			array(
				'email'      => self::CONTACT_EMAIL,
				'first_name' => 'Sam',
				'last_name'  => 'Other',
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertSame( 'Sam', $contact['fname'], 'A missing first name was not filled in.' );
		$this->assertSame( 'Other', $contact['lname'], 'A missing last name was not filled in.' );
	}

	/**
	 * @testdox A MailPoet subscriber already recorded on a contact still updates its name.
	 */
	#[TestDox( 'A MailPoet subscriber already recorded on a contact still updates its name.' )]
	public function test_mailpoet_known_subscriber_still_updates_name() {
		global $zbs;

		$contact_id = $this->import_mailpoet_subscriber(
			array(
				'email'      => 'sam.other@example.test',
				'first_name' => 'Sam',
				'last_name'  => 'Other',
			)
		);
		$this->assertGreaterThan( 0, $contact_id, 'The subscriber was not imported.' );

		$this->import_mailpoet_subscriber(
			array(
				'email'      => 'sam.other@example.test',
				'first_name' => 'Samantha',
				'last_name'  => 'Other',
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertSame( 'Samantha', $contact['fname'], 'A known subscriber could not update their own name.' );
	}

	/**
	 * @testdox A GiveWP donation leaves an existing contact's details and status as they are.
	 */
	#[TestDox( 'A GiveWP donation leaves an existing contact\'s details and status as they are.' )]
	public function test_givewp_donation_leaves_existing_details() {
		global $zbs;

		$contact_id = $this->create_contact_with_details( array( 'country' => '' ) );

		$donor_contact_id = $this->add_donor(
			array(
				'email'      => self::CONTACT_EMAIL,
				'first_name' => 'Sam',
				'last_name'  => 'Other',
				'address'    => array(
					'line1'   => '1 Other Street',
					'line2'   => '',
					'city'    => 'Galway',
					'state'   => '',
					'zip'     => 'H91 0000',
					'country' => 'IE',
				),
			)
		);

		$this->assertSame( $contact_id, $donor_contact_id, 'The donation did not match the existing contact.' );

		$contact = $zbs->DAL->contacts->getContact( $contact_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertSame( 'Alex', $contact['fname'], 'A donation replaced the first name.' );
		$this->assertSame( 'Murphy', $contact['lname'], 'A donation replaced the last name.' );
		$this->assertSame( '19 Prospect Hill', $contact['addr1'], 'A donation replaced the address.' );
		$this->assertSame( 'Cork', $contact['city'], 'A donation replaced the city.' );
		$this->assertSame( 'T12 XY45', $contact['postcode'], 'A donation replaced the postcode.' );
		$this->assertSame( '+353 87 000 0000', $contact['mobtel'], 'A donation emptied the mobile number.' );
		$this->assertSame( 'IE', $contact['country'], 'A blank country was not filled in.' );
		$this->assertSame( 'Customer', $contact['status'], 'A donation changed the status of an existing contact.' );

		$tagged = $zbs->DAL->contacts->getContact( $contact_id, array( 'withTags' => true ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->assertContains( 'GiveWP', wp_list_pluck( $tagged['tags'], 'name' ), 'The GiveWP tag was not added.' );
	}

	/**
	 * @testdox A GiveWP donation from somebody new creates a contact with the Donor status.
	 */
	#[TestDox( 'A GiveWP donation from somebody new creates a contact with the Donor status.' )]
	public function test_givewp_donation_creates_a_donor() {
		global $zbs;

		$contact_id = $this->add_donor(
			array(
				'email'      => 'new.donor@example.test',
				'first_name' => 'Dana',
			)
		);

		$contact = $zbs->DAL->contacts->getContact( $contact_id, array( 'withTags' => true ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->assertSame( 'Donor', $contact['status'] );
		$this->assertSame( 'Dana', $contact['fname'] );
		$this->assertContains( 'GiveWP', wp_list_pluck( $contact['tags'], 'name' ) );
	}
}
