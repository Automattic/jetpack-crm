<?php
/**
 * Defines the Jetpack CRM Automation workflow exception.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation;

/**
 * Adds the Workflow_Exception class.
 *
 * @since 6.2.0-alpha
 */
class Workflow_Exception extends \Exception {

	/**
	 * Invalid Workflow error code.
	 *
	 * @since 6.2.0-alpha
	 * @var int
	 */
	const INVALID_WORKFLOW = 10;

	/**
	 * Workflow require a trigger error code.
	 *
	 * @since 6.2.0-alpha
	 * @var int
	 */
	const WORKFLOW_REQUIRE_A_TRIGGER = 11;

	/**
	 * Workflow require an initial step error code.
	 *
	 * @since 6.2.0-alpha
	 * @var int
	 */
	const WORKFLOW_REQUIRE_A_INITIAL_STEP = 12;

	/**
	 * Error initializing trigger error code.
	 *
	 * @since 6.2.0-alpha
	 * @var int
	 */
	const ERROR_INITIALIZING_TRIGGER = 13;

	/**
	 * Missing engine instance.
	 *
	 * This exception should be thrown if a workflow is attempted to be executed without an engine instance.
	 *
	 * @since 6.2.0-alpha
	 * @var int
	 */
	const MISSING_ENGINE_INSTANCE = 14;
}
