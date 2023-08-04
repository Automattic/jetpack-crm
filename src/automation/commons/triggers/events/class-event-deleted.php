<?php
/**
 * Jetpack CRM Automation Event_Deleted trigger.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation\Triggers;

use Automattic\Jetpack\CRM\Automation\Base_Trigger;

/**
 * Adds the Event_Deleted class.
 *
 * @since 6.2.0-alpha
 */
class Event_Deleted extends Base_Trigger {

	/**
	 * Get the slug name of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The slug name of the trigger.
	 */
	public static function get_slug(): string {
		return 'jpcrm/event_deleted';
	}

	/**
	 * Get the title of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The title of the trigger.
	 */
	public static function get_title(): string {
		return __( 'Event Deleted', 'zero-bs-crm' );
	}

	/**
	 * Get the description of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The description of the trigger.
	 */
	public static function get_description(): string {
		return __( 'Triggered when an event is deleted', 'zero-bs-crm' );
	}

	/**
	 * Get the category of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The category of the trigger.
	 */
	public static function get_category(): string {
		return 'event';
	}

	/**
	 * Listen to this trigger's target event.
	 *
	 * @since 6.2.0-alpha
	 */
	protected function listen_to_event() {
		add_action(
			'jpcrm_event_delete',
			array( $this, 'execute_workflow' )
		);
	}

}
