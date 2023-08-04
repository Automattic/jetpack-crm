<?php
/**
 * Defines the Jetpack CRM Automation workflow base.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation;

/**
 * Adds the Automation_Workflow class.
 *
 * @since 6.2.0-alpha
 */
class Automation_Workflow {

	/**
	 * The workflow id.
	 *
	 * @since 6.2.0-alpha
	 * @var int|string
	 */
	private $id;

	/**
	 * The workflow name.
	 *
	 * @since 6.2.0-alpha
	 * @var string
	 */
	public $name;

	/**
	 * The workflow description.
	 *
	 * @since 6.2.0-alpha
	 * @var string
	 */
	public $description;

	/**
	 * The workflow category.
	 *
	 * @since 6.2.0-alpha
	 * @var string
	 */
	public $category;

	/**
	 * The workflow triggers.
	 *
	 * @since 6.2.0-alpha
	 * @var string[]
	 */
	public $triggers;

	/**
	 * The workflow initial step.
	 *
	 * @since 6.2.0-alpha
	 * @var array
	 */
	public $initial_step;

	/**
	 * The workflow active status.
	 *
	 * @since 6.2.0-alpha
	 * @var bool
	 */
	public $active;

	/**
	 * The version of the workflow.
	 *
	 * @since 6.2.0-alpha
	 * @var int
	 */
	public $version = 1;

	/**
	 * The automation engine.
	 *
	 * @since 6.2.0-alpha
	 * @var Automation_Engine
	 */
	private $automation_engine;

	/**
	 * The automation logger.
	 *
	 * @since 6.2.0-alpha
	 * @var Automation_Logger
	 */
	private $logger;

	/**
	 * Automation_Workflow constructor.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array             $workflow_data The workflow data to be constructed.
	 * @param Automation_Engine $automation_engine An instance of the Automation_Engine class.
	 */
	public function __construct( array $workflow_data, Automation_Engine $automation_engine ) {
		$this->id           = $workflow_data['id'] ?? null;
		$this->triggers     = $workflow_data['triggers'] ?? array();
		$this->initial_step = $workflow_data['initial_step'] ?? array();
		$this->name         = $workflow_data['name'];
		$this->description  = $workflow_data['description'] ?? '';
		$this->category     = $workflow_data['category'] ?? '';
		$this->active       = $workflow_data['is_active'] ?? true;

		$this->automation_engine = $automation_engine;
		$this->logger            = $automation_engine->get_logger() ?? Automation_Logger::instance();
	}

	/**
	 * Get the id of this workflow.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return int|string The workflow id.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the triggers within the workflow given an array of triggers.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param string[] $triggers An array of triggers to be set.
	 */
	public function set_triggers( array $triggers ) {
		$this->triggers = $triggers;
	}

	/**
	 * Get the trigger names of this workflow.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string[] The workflow trigger names.
	 */
	public function get_triggers(): array {
		return $this->triggers;
	}

	/**
	 * Instance the triggers of this workflow.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @throws Workflow_Exception Throws an exception if there is an issue initializing the trigger.
	 */
	public function init_triggers() {

		if ( ! $this->is_active() ) {
			return;
		}

		foreach ( $this->get_triggers() as $trigger_slug ) {
			try {
				$trigger_class = $this->automation_engine->get_trigger_class( $trigger_slug );

				/** @var Base_Trigger $trigger */
				$trigger = new $trigger_class();
				$trigger->init( $this );

			} catch ( Automation_Exception $e ) {
				throw new Workflow_Exception(
					/* Translators: %s is the error message to be included in the exception string. */
					sprintf( __( 'An error happened initializing the trigger. %s', 'zero-bs-crm' ), $e->getMessage() ),
					Workflow_Exception::ERROR_INITIALIZING_TRIGGER
				);
			}
		}
	}

	/**
	 * Set initial step of this workflow.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $step_data The data for the step to be set as the initial step.
	 */
	public function set_initial_step( array $step_data ) {
		$this->initial_step = $step_data;
	}

	/**
	 * Get the workflow as an array to be stored or send as JSON.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return array The workflow as an array.
	 */
	public function get_workflow_array(): array {
		return array(
			'name'         => $this->name,
			'description'  => $this->description,
			'category'     => $this->category,
			'is_active'    => $this->active,
			'triggers'     => $this->triggers,
			'initial_step' => $this->initial_step,
		);
	}

	/**
	 * Get the initial step of this workflow.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return array
	 */
	public function get_initial_step(): array {
		return $this->initial_step;
	}

	/**
	 * Start the workflow execution once a trigger is activated.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param Trigger $trigger An instance of the Trigger class.
	 * @param array   $data All relevant object data to be passed through the workflow.
	 * @return bool Whether the workflow was executed successfully.
	 *
	 * @throws Automation_Exception Throws an exception if the step class does not exist, or there is an error executing the workflow.
	 */
	public function execute( Trigger $trigger, array $data ): bool {
		$this->logger->log( 'Trigger activated: ' . $trigger->get_slug() );
		$this->logger->log( 'Executing workflow: ' . $this->name );

		$step_data = $this->initial_step;

		while ( $step_data ) {
			try {
				$step_slug = $step_data['slug'];

				$step_class = $step_data['class_name'] ?? $this->automation_engine->get_step_class( $step_slug );

				if ( ! class_exists( $step_class ) ) {
					throw new Automation_Exception(
						/* Translators: %s is the name of the step class that does not exist. */
						sprintf( __( 'The step class %s does not exist.', 'zero-bs-crm' ), $step_class ),
						Automation_Exception::STEP_CLASS_NOT_FOUND
					);
				}

				/** @var Step $step */
				$step = new $step_class( $step_data );

				if ( isset( $step_data['attributes'] ) && is_array( $step_data['attributes'] ) ) {
					$step->set_attributes( $step_data['attributes'] );
				}

				$this->logger->log( '[' . $step->get_slug() . '] Executing step. Type: ' . $step->get_type() );

				$step->execute( $data );
				$step_data = $step->get_next_step();

				$this->logger->log( '[' . $step->get_slug() . '] Step executed!' );

				if ( ! $step_data ) {
					$this->logger->log( 'Workflow execution finished: No more steps found.' );
					return true;
				}
			} catch ( Automation_Exception $automation_exception ) {

				$this->logger->log( 'Error executing the workflow on step: ' . $step_slug . ' - ' . $automation_exception->getMessage() );

				throw $automation_exception;
			}
		}

		return false;
	}

	/**
	 * Get the step classname based on the step type.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $step_data The step data with which to check the step type.
	 * @return string The step classname.
	 *
	 * @throws Automation_Exception Throws an exception if the step class does not exist.
	 */
	private function get_step_class( array $step_data ): string {
		$step_type = $step_data['type'];

		return $this->automation_engine->get_step_class( $step_type );
	}

	/**
	 * Turn on the workflow.
	 *
	 * @since 6.2.0-alpha
	 */
	public function turn_on() {
		$this->active = true;
	}

	/**
	 * Turn off the workflow.
	 *
	 * @since 6.2.0-alpha
	 */
	public function turn_off() {
		$this->active = false;
	}

	/**
	 * Check if the workflow is active.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return bool Whether the workflow is active.
	 */
	public function is_active(): bool {
		return $this->active;
	}

	/**
	 * Add a trigger to this workflow.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param string $string The name of the trigger to add.
	 */
	public function add_trigger( string $string ) {
		$this->triggers[] = $string;
	}

	/**
	 * Set Automation Logger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param Automation_Logger $logger An instance of the Automation_Logger class.
	 */
	public function set_automation_logger( Automation_Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Set Automation Engine.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param Automation_Engine $automation_engine An instance of the Automation_Engine class.
	 */
	public function set_automation_engine( Automation_Engine $automation_engine ) {
		$this->automation_engine = $automation_engine;
	}

}
