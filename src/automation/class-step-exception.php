<?php
/**
 * Defines the Jetpack CRM Automation step exception.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation;

/**
 * Adds the Step_Exception class.
 *
 * @since 6.2.0-alpha
 */
class Step_Exception extends \Exception {

	/**
	 * Step type not allowed code.
	 *
	 * @since 6.2.0-alpha
	 * @var int
	 */
	const STEP_TYPE_NOT_ALLOWED = 10;

	/**
	 * Step class does not exist code.
	 *
	 * @since 6.2.0-alpha
	 * @var int
	 */
	const STEP_CLASS_DOES_NOT_EXIST = 11;

}
