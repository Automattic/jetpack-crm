<?php
/**
 * Jetpack CRM Automation Update_Contact action.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation\Actions;

use Automattic\Jetpack\CRM\Automation\Base_Action;

/**
 * Adds the Update_Contact class.
 *
 * @since 6.2.0-alpha
 */
class Update_Contact extends Base_Action {

	/**
	 * Get the slug name of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The slug name of the step.
	 */
	public static function get_slug(): string {
		return 'jpcrm/update_contact';
	}

	/**
	 * Get the title of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The title of the step.
	 */
	public static function get_title(): ?string {
		return 'Update Contact Action';
	}

	/**
	 * Get the description of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The description of the step.
	 */
	public static function get_description(): ?string {
		return 'Action to update the contact';
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
	 * @return string|null The category of the step.
	 */
	public static function get_category(): ?string {
		return 'actions';
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
	 * Update the DAL with the new contact data.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $contact_data The contact data to be updated.
	 */
	public function execute( array $contact_data ) {
		global $zbs;

		$contact_data['data'] = array_replace( $contact_data['data'], $this->attributes['data'] );
		$zbs->DAL->contacts->addUpdateContact( $contact_data ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

}
