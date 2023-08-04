<?php
/**
 * Jetpack CRM Automation Add_Contact_Log action.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation\Actions;

use Automattic\Jetpack\CRM\Automation\Base_Action;

/**
 * Adds the Add_Contact_Log class.
 *
 * @since 6.2.0-alpha
 */
class Add_Contact_Log extends Base_Action {

	/**
	 * Get the slug name of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The slug name of the step.
	 */
	public static function get_slug(): string {
		return 'jpcrm/add_contact_log';
	}

	/**
	 * Get the title of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The title of the step.
	 */
	public static function get_title(): ?string {
		return 'Add Contact Log Action';
	}

	/**
	 * Get the description of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The description of the step.
	 */
	public static function get_description(): ?string {
		return 'Action to add a log to a contact';
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
		return 'actions';
	}

	/**
	 * Get the allowed triggers.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string[] The allowed triggers.
	 */
	public static function get_allowed_triggers(): ?array {
		return array();
	}

	/**
	 * Add the log to the contact via the DAL.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $contact_data The contact data on which the log is to be added.
	 */
	public function execute( array $contact_data = array() ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		global $zbs;

		$zbs->DAL->contacts->zeroBS_addUpdateObjLog( $this->attributes ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

}
