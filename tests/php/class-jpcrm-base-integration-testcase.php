<?php

namespace Automattic\Jetpack\CRM\Tests;

use Automattic\Jetpack\CRM\Entities\Contact;
use Automattic\Jetpack\CRM\Entities\Factories\Contact_Factory;

/**
 * Test case that ensures we have a clean and functioning Jetpack CRM instance.
 */
abstract class JPCRM_Base_Integration_TestCase extends JPCRM_Base_TestCase {

	/**
	 * Custom field settings as they were before register_custom_field() changed them.
	 *
	 * @var array|null Keys `legacy` (the `customfields` setting) and `types` (object type ID => fields).
	 */
	private $custom_fields_before = null;

	/**
	 * Clean up the database after each test.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$this->restore_custom_fields();

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
	 * Add a company.
	 *
	 * @param array $args (Optional) A list of arguments we should use for the company.
	 *
	 * @return int The company ID.
	 */
	public function add_company( array $args = array() ) {
		global $zbs;

		return $zbs->DAL->companies->addUpdateCompany( array( 'data' => $this->generate_company_data( $args ) ) );
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

		// Settings outlive the test: tear_down() puts these back.
		if ( null === $this->custom_fields_before ) {
			$this->custom_fields_before = array(
				'legacy' => $zbs->settings->get( 'customfields' ),
				'types'  => array(),
			);
		}
		foreach ( $object_keys as $obj_type_id ) {
			if ( ! array_key_exists( $obj_type_id, $this->custom_fields_before['types'] ) ) {
				$this->custom_fields_before['types'][ $obj_type_id ] = $zbs->DAL->getActiveCustomFields( array( 'objtypeid' => $obj_type_id ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}

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

	/**
	 * Undo register_custom_field(): put the settings and field globals back.
	 *
	 * @return void
	 */
	private function restore_custom_fields() {
		global $zbs;

		if ( null === $this->custom_fields_before ) {
			return;
		}

		foreach ( $this->custom_fields_before['types'] as $obj_type_id => $fields ) {
			$zbs->DAL->updateActiveCustomFields( // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				array(
					'objtypeid' => $obj_type_id,
					'fields'    => is_array( $fields ) ? $fields : array(),
				)
			);
		}
		$zbs->settings->update( 'customfields', $this->custom_fields_before['legacy'] );

		zeroBSCRM_fields_initialise();
		zeroBSCRM_unpackCustomFields();

		$this->custom_fields_before = null;
	}

	/**
	 * Create a user who is allowed to own a contact.
	 *
	 * `addUpdateContact()` drops an owner who cannot, so a test that used a
	 * default-role user (see `add_wp_user()`) would be asserting against -1
	 * either way.
	 *
	 * @return int The new user ID.
	 */
	public function create_contact_owner(): int {
		$owner_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->assertTrue( user_can( $owner_id, 'admin_zerobs_usr' ), 'The owner under test cannot own a contact.' );

		return $owner_id;
	}
}
