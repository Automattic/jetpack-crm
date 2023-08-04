<?php
/**
 * Interface Trigger
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation;

/**
 * Interface Trigger.
 *
 * @since 6.2.0-alpha
 */
interface Trigger {

	/**
	 * Get the slug name of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The slug name of the trigger.
	 */
	public static function get_slug(): string;

	/**
	 * Get the title of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The title of the trigger.
	 */
	public static function get_title(): ?string;

	/**
	 * Get the description of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The description of the trigger.
	 */
	public static function get_description(): ?string;

	/**
	 * Get the category of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The category of the trigger.
	 */
	public static function get_category(): ?string;

	/**
	 * Execute the workflow.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array|null $data The data to pass to the workflow.
	 */
	public function execute_workflow( array $data = null );

	/**
	 * Set the workflow to execute by this trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param Automation_Workflow $workflow The workflow to execute by this trigger.
	 */
	public function set_workflow( Automation_Workflow $workflow );

	/**
	 * Init the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param Automation_Workflow $workflow The workflow to which the trigger belongs.
	 */
	public function init( Automation_Workflow $workflow );

}
