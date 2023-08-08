<?php
/**
 * Jetpack CRM Automation Event_Updated trigger.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Automation\Triggers;

use Automattic\Jetpack\CRM\Automation\Base_Trigger;

/**
 * Adds the Event_Updated class.
 *
 * @since 6.2.0-alpha
 */
class Event_Updated extends Base_Trigger {

	/**
	 * Get the slug name of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The trigger slug.
	 */
	public static function get_slug(): string {
		return 'jpcrm/event_updated';
	}

	/**
	 * Get the title of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The title.
	 */
	public static function get_title(): string {
		return __( 'Event Updated', 'zero-bs-crm' );
	}

	/**
	 * Get the description of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The description.
	 */
	public static function get_description(): string {
		return __( 'Triggered when an event is updated', 'zero-bs-crm' );
	}

	/**
	 * Get the category of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The category.
	 */
	public static function get_category(): string {
		return __( 'event', 'zero-bs-crm' );
	}

	/**
	 * Listen to this trigger's target event.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return void
	 */
	protected function listen_to_event() {
		add_action(
			'jpcrm_event_updated',
			array( $this, 'execute_workflow' )
		);
	}
}