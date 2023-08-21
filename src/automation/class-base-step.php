<?php
/**
 * Base Step
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation;

/**
 * Base Step.
 *
 * @since 6.2.0-alpha
 * {@inheritDoc}
 */
abstract class Base_Step implements Step {

	/**
	 * Step attributes.
	 *
	 * @since 6.2.0-alpha
	 * @var array
	 */
	protected $attributes;

	/**
	 * Next linked step.
	 *
	 * @since 6.2.0-alpha
	 * @var array|null
	 */
	protected $next_step;

	/**
	 * Base_Step constructor.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $step_data An array of data for the current step.
	 */
	public function __construct( array $step_data ) {
		$this->attributes = $step_data['attributes'] ?? array();
	}

	/**
	 * Get the data of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return array The step data.
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Set attributes of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $attributes The attributes to set.
	 */
	public function set_attributes( array $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Set the next step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $step_data The next linked step.
	 */
	public function set_next_step( array $step_data ) {
		$this->next_step = $step_data;
	}

	/**
	 * Get the next step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return array|null The next linked step.
	 */
	public function get_next_step(): ?array {
		return $this->next_step;
	}

	/**
	 * Execute the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param mixed  $data Data passed from the trigger.
	 * @param ?mixed $previous_data (Optional) The data before being changed.
	 */
	abstract public function execute( $data, $previous_data = null );

	/**
	 * Get the slug name of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The slug name of the step.
	 */
	abstract public static function get_slug(): string;

	/**
	 * Get the title of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The title of the step.
	 */
	abstract public static function get_title(): ?string;

	/**
	 * Get the description of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The description of the step.
	 */
	abstract public static function get_description(): ?string;

	/**
	 * Get the type of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The type of the step.
	 */
	abstract public static function get_data_type(): string;

	/**
	 * Get the category of the step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The category of the step.
	 */
	abstract public static function get_category(): ?string;

	/**
	 * Get the allowed triggers.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return array|null The allowed triggers.
	 */
	abstract public static function get_allowed_triggers(): ?array;
}
