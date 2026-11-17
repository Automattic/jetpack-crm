<?php
/**
 * Tests for the values a Jetpack form submission stores on a contact, and for
 * how the source of a contact is shown.
 *
 * @package Automattic\Jetpack\CRM
 */

namespace Automattic\Jetpack\CRM\Entities\Tests;

use Automattic\Jetpack\CRM\Tests\JPCRM_Base_Integration_TestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Submitted values are stored as plain text, the contact's email has to be an
 * email address, and the ID of a source is shown as text.
 */
class Jetpack_Form_Capture_Values_Test extends JPCRM_Base_Integration_TestCase {

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
	private function submit( array $fields ) {
		zero_bs_crm_capture_jetpack_form( 1, $fields, false, array() );
	}

	/**
	 * The contact with the given email, or false.
	 *
	 * @param string $email The email address.
	 * @return array|false
	 */
	private function contact_with_email( string $email ) {
		global $zbs;

		return $zbs->DAL->contacts->getContact( -1, array( 'email' => $email ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * @testdox An ordinary submission is stored as it was sent.
	 */
	#[TestDox( 'An ordinary submission is stored as it was sent.' )]
	public function test_an_ordinary_submission_is_stored_as_sent() {

		$this->submit(
			array(
				$this->form_field( 'g1-name', 'name', "Siobhán O'Neill-Byrne" ),
				$this->form_field( 'g1-email', 'email', 'siobhan@example.test' ),
				$this->form_field( 'g1-phone', 'telephone', '+353 (21) 427-0000' ),
			)
		);

		$contact = $this->contact_with_email( 'siobhan@example.test' );

		$this->assertIsArray( $contact, 'No contact was created.' );
		$this->assertSame( 'Siobhán', $contact['fname'] );
		$this->assertSame( "O'Neill-Byrne", $contact['lname'] );
		$this->assertSame( '+353 (21) 427-0000', $contact['hometel'] );
	}

	/**
	 * @testdox Submitted values are stored as plain text.
	 */
	#[TestDox( 'Submitted values are stored as plain text.' )]
	public function test_submitted_values_are_stored_as_plain_text() {

		$this->submit(
			array(
				$this->form_field( 'jetpackcrm-email', 'email', 'sam@example.test' ),
				$this->form_field( 'jetpackcrm-fname', 'text', 'Sam <b>Other</b>' ),
				$this->form_field( 'jetpackcrm-city', 'select', array( 'Cork', 'Galway' ) ),
			)
		);

		$contact = $this->contact_with_email( 'sam@example.test' );

		$this->assertIsArray( $contact, 'No contact was created.' );
		$this->assertSame( 'Sam Other', $contact['fname'], 'Markup was stored in the first name.' );
		$this->assertSame( 'Cork, Galway', $contact['city'], 'A field with several values was not stored as text.' );
	}

	/**
	 * @testdox Form fields cannot set a contact's ID, linked user or owner.
	 */
	#[TestDox( 'Form fields cannot set a contact\'s ID, linked user or owner.' )]
	public function test_fields_cannot_set_id_linked_user_or_owner() {

		$other_id = $this->add_contact(
			array(
				'email' => 'other@example.test',
				'fname' => 'Other',
			)
		);

		$this->submit(
			array(
				$this->form_field( 'jetpackcrm-email', 'email', 'pat@example.test' ),
				$this->form_field( 'jetpackcrm-fname', 'text', 'Pat' ),
				$this->form_field( 'jetpackcrm-id', 'text', (string) $other_id ),
				$this->form_field( 'jetpackcrm-wpid', 'text', '4242' ),
				$this->form_field( 'jetpackcrm-owner', 'text', '4243' ),
			)
		);

		$contact = $this->contact_with_email( 'pat@example.test' );

		$this->assertIsArray( $contact, 'No contact was created.' );
		$this->assertNotEquals( $other_id, $contact['id'], 'The submission was written to another contact.' );
		$this->assertNotEquals( 4242, $contact['wpid'], 'A form field set the linked user.' );
		$this->assertNotEquals( 4243, $contact['owner'], 'A form field set the owner.' );

		$other = $this->contact_with_email( 'other@example.test' );
		$this->assertSame( 'Other', $other['fname'], 'Another contact was changed.' );
	}

	/**
	 * @testdox A submission whose email is not an email address creates no contact.
	 */
	#[TestDox( 'A submission whose email is not an email address creates no contact.' )]
	public function test_a_submission_without_an_email_address_creates_no_contact() {
		global $zbs;

		$before = (int) $zbs->DAL->contacts->getContactCount(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$this->submit(
			array(
				$this->form_field( 'jetpackcrm-email', 'text', 'sam at example <i>test</i>' ),
				$this->form_field( 'jetpackcrm-fname', 'text', 'Sam' ),
			)
		);

		$this->assertSame( $before, (int) $zbs->DAL->contacts->getContactCount(), 'A contact was created without an email address.' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * @testdox The ID of a contact's source is shown as text.
	 */
	#[TestDox( 'The ID of a contact\'s source is shown as text.' )]
	public function test_the_id_of_a_source_is_shown_as_text() {

		$this->assertSame(
			'<i class="fa fa-wpforms"></i> Form Capture:<br /><span>a&lt;i&gt;b&lt;/i&gt;@example.test</span>',
			zeroBS_getExternalSourceTitle( 'form', 'a<i>b</i>@example.test' )
		);

		$this->assertSame(
			'<i class="fa fa-wpforms"></i> Form Capture:<br /><span>sam@example.test</span>',
			zeroBS_getExternalSourceTitle( 'form', 'sam@example.test' ),
			'An ordinary ID is no longer shown as it was.'
		);
	}
}
