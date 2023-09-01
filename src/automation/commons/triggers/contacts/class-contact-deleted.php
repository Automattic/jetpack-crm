<?php
/**
 * Jetpack CRM Automation Contact_Deleted trigger.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation\Triggers;

use Automattic\Jetpack\CRM\Automation\Automation_Workflow;
use Automattic\Jetpack\CRM\Automation\Base_Trigger;
use Automattic\Jetpack\CRM\Automation\Data_Types\Data_Type_Contact;

/**
 * Adds the Contact_Deleted class.
 *
 * @since 6.2.0-alpha
 */
class Contact_Deleted extends Base_Trigger {

	/**
	 * The Automation workflow object.
	 *
	 * @since 6.2.0-alpha
	 * @var Automation_Workflow
	 */
	protected $workflow;

	/**
	 * Get the slug name of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The slug name of the trigger.
	 */
	public static function get_slug(): string {
		return 'jpcrm/contact_delete';
	}

	/**
	 * Get the title of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The title of the trigger.
	 */
	public static function get_title(): ?string {
		return __( 'Contact Deleted', 'zero-bs-crm' );
	}

	/**
	 * Get the description of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The description of the trigger.
	 */
	public static function get_description(): ?string {
		return __( 'Triggered when a CRM contact is deleted', 'zero-bs-crm' );
	}

	/**
	 * Get the category of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null
	 */
	public static function get_category(): ?string {
		return __( 'Contact', 'zero-bs-crm' );
	}

	/**
	 * Get the date type.
	 *
	 * @return string The type of the step
	 */
	public static function get_data_type(): string {
		return Data_Type_Contact::get_slug();
	}

	/**
	 * Listen to the desired event.
	 *
	 * @since 6.2.0-alpha
	 */
	protected function listen_to_event() {
		add_action(
			'jpcrm_contact_deleted',
			array( $this, 'execute_workflow' )
		);
	}

}
