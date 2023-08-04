<?php
/**
 * Jetpack CRM Automation Delete_Contact action.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation\Actions;

use Automattic\Jetpack\CRM\Automation\Base_Action;

/**
 * Adds the Delete_Contact class.
 *
 * @since 6.2.0-alpha
 */
class Delete_Contact extends Base_Action {

	/**
	 * Get the slug name of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The slug name of the step.
	 */
	public static function get_slug(): string {
		return 'jpcrm/delete_contact';
	}

	/**
	 * Get the title of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The title of the step.
	 */
	public static function get_title(): ?string {
		return __( 'Delete Contact Action', 'zero-bs-crm' );
	}

	/**
	 * Get the description of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The description of the step.
	 */
	public static function get_description(): ?string {
		return __( 'Action to delete the contact', 'zero-bs-crm' );
	}

	/**
	 * Get the type of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The type of the step.
	 */
	public static function get_type(): string {
		return 'contacts';
	}

	/**
	 * Get the category of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The category of the step.
	 */
	public static function get_category(): ?string {
		return __( 'Contacts', 'zero-bs-crm' );
	}

	/**
	 * Get the allowed triggers.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string[]|null The allowed triggers.
	 */
	public static function get_allowed_triggers(): ?array {
		return array();
	}

	/**
	 * Update the DAL - deleting the given contact.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $contact_data The contact data to be passed into the DAL's delete function.
	 */
	public function execute( array $contact_data ) {
		global $zbs;

		$contact_data_for_deletion = array(
			'id'          => (int) $contact_data['id'],
			'saveOrphans' => (bool) $this->attributes['keep_orphans'],

		);
		$zbs->DAL->contacts->deleteContact( $contact_data_for_deletion ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

}
