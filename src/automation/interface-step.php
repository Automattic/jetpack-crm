<?php
/**
 * Interface to define Step in an automation workflow.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation;

use Automattic\Jetpack\CRM\Automation\Data_Types\Data_Type;

/**
 * Interface Step.
 *
 * @since 6.2.0-alpha
 */
interface Step {

	/**
	 * Execute the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param Data_Type $data Data passed from the trigger.
	 */
	public function execute( Data_Type $data );

	/**
	 * Get the next step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return int|string|null The next linked step.
	 */
	public function get_next_step_id();

	/**
	 * Set the next step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param int|string|null $step_id The next linked step.
	 */
	public function set_next_step( $step_id );

	/**
	 * Get the step attribute definitions.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return Attribute_Definition[] The attribute definitions of the step.
	 */
	public function get_attribute_definitions(): ?array;

	/**
	 * Set the step attribute definitions.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param Attribute_Definition[] $attribute_definitions Set the attribute definitions.
	 */
	public function set_attribute_definitions( array $attribute_definitions );

	/**
	 * Get the attributes of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return array The attributes of the step.
	 */
	public function get_attributes(): ?array;

	/**
	 * Get the attributes of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $attributes Set attributes to this step.
	 */
	public function set_attributes( array $attributes );

	/**
	 * Get the slug name of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The slug name of the step.
	 */
	public static function get_slug(): string;

	/**
	 * Get the title of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The title of the step.
	 */
	public static function get_title(): ?string;

	/**
	 * Get the description of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The description of the step.
	 */
	public static function get_description(): ?string;

	/**
	 * Get the data type expected by the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The data type expected by the step.
	 */
	public static function get_data_type(): string;

	/**
	 * Get the category of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The category of the step.
	 */
	public static function get_category(): ?string;
}
