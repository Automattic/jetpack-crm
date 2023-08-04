<?php
/**
 * Base Condition implementation
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation;

/**
 * Base Condition Step.
 *
 * @since 6.2.0-alpha
 * {@inheritDoc}
 */
abstract class Base_Condition extends Base_Step implements Condition {

	/**
	 * The next step if the condition is met.
	 *
	 * @since 6.2.0-alpha
	 * @var array|null
	 */
	protected $next_step_true = null;

	/**
	 * The next step if the condition is not met.
	 *
	 * @since 6.2.0-alpha
	 * @var array|null
	 */
	protected $next_step_false = null;

	/**
	 * If the condition is met or not.
	 *
	 * @since 6.2.0-alpha
	 * @var bool If the condition is met or not.
	 */
	protected $condition_met = false;

	/**
	 * Base_Condition constructor.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $step_data The step data.
	 */
	public function __construct( array $step_data ) {
		parent::__construct( $step_data );

		$this->next_step_true  = $step_data['next_step_true'] ?? null;
		$this->next_step_false = $step_data['next_step_false'] ?? null;
	}

	/**
	 * Get the next step.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return array|null The next step data.
	 */
	public function get_next_step(): ?array {
		return ( $this->condition_met ? $this->next_step_true : $this->next_step_false );
	}

	/**
	 *  Met the condition?
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return bool If the condition is met or not.
	 */
	public function condition_met(): bool {
		return $this->condition_met;
	}

}
