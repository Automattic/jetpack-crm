<?php

namespace Automattic\Jetpack\CRM\Tests;

use Automattic\Jetpack\CRM\Entities\Contact;
use Automattic\Jetpack\CRM\Entities\Factories\Contact_Factory;

/**
 * Test case that ensures we have a clean and functioning Jetpack CRM instance.
 */
abstract class JPCRM_Base_Integration_TestCase extends JPCRM_Base_TestCase {

	/**
	 * Clean up the database after each test.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function tear_down(): void {
		parent::tear_down();

		zeroBSCRM_database_reset( false );
	}

	/**
	 * Add a contact.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the contact.
	 *
	 * @return int The contact ID.
	 */
	public function add_contact( array $args = array() ) {
		global $zbs;

		return $zbs->DAL->contacts->addUpdateContact( array( 'data' => $this->generate_contact_data( $args ) ) );
	}

	/**
	 * Add an invoice.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the invoice.
	 *
	 * @return int The invoice ID.
	 */
	public function add_invoice( array $args = array() ) {
		global $zbs;

		return $zbs->DAL->invoices->addUpdateInvoice( array( 'data' => $this->generate_invoice_data( $args ) ) );
	}

	/**
	 * Add a transaction.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the transaction.
	 *
	 * @return int The transaction ID.
	 */
	public function add_transaction( array $args = array() ) {
		global $zbs;

		return $zbs->DAL->transactions->addUpdateTransaction( array( 'data' => $this->generate_transaction_data( $args ) ) );
	}

	/**
	 * Get a contact.
	 *
	 * @param int|string $id The ID of the contact we want to get.
	 * @param array      $args (Optional) A list of arguments we should use for the contact.
	 * @return Contact|null
	 */
	public function get_contact( $id, array $args = array() ) {
		global $zbs;

		$contact_data = $zbs->DAL->contacts->getContact( $id, $args );

		return Contact_Factory::create( $contact_data );
	}

	/**
	 * Add a WP User.
	 *
	 * @return int The user ID.
	 */
	public function add_wp_user() {

		return wp_create_user( 'testuser', 'password', 'user@demo.com' );
	}

	/**
	 * Register a custom field against one or more object types and rebuild the field globals.
	 *
	 * Custom fields are stored twice: once per object type for DAL3, and once in the legacy
	 * `customfields` setting. zeroBSCRM_unpackCustomFields() gates the DAL3 overload on the
	 * object key existing in the legacy setting, so both are needed before the field reaches
	 * $zbsCustomerFields / $zbsCompanyFields.
	 *
	 * @param string $slug        Field slug, e.g. 'contract-date'.
	 * @param string $type        Field type, e.g. 'date'. Must be in zeroBSCRM_customfields_acceptableCFTypes().
	 * @param string $label       Human readable field name.
	 * @param array  $object_keys Legacy object key => object type ID, e.g. array( 'customers' => ZBS_TYPE_CONTACT ).
	 *
	 * @return void
	 */
	public function register_custom_field( $slug, $type, $label, array $object_keys ) {
		global $zbs;

		$field_definition = array( $type, $label, '', $slug );

		$legacy_setting = array();

		foreach ( $object_keys as $object_key => $obj_type_id ) {
			$zbs->DAL->updateActiveCustomFields( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				array(
					'objtypeid' => $obj_type_id,
					'fields'    => array( $slug => $field_definition ),
				)
			);

			$legacy_setting[ $object_key ] = array( $field_definition );
		}

		$zbs->settings->update( 'customfields', $legacy_setting );

		// Same order as ZeroBSCRM.Core.php: build the globals from the models, then overlay
		// the custom fields.
		zeroBSCRM_fields_initialise();
		zeroBSCRM_unpackCustomFields();
	}
}
