<?php
/**
 * Jetpack CRM Automation Company_Created trigger.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation\Triggers;

use Automattic\Jetpack\CRM\Automation\Base_Trigger;

/**
 * Adds the Company_Created class.
 *
 * @since 6.2.0-alpha
 */
class Company_Created extends Base_Trigger {

	/**
	 * Get the slug name of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string The slug name of the trigger.
	 */
	public static function get_slug(): string {
		return 'jpcrm/company_created';
	}

	/**
	 * Get the title of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The title of the trigger.
	 */
	public static function get_title(): ?string {
		return __( 'New Company', 'zero-bs-crm' );
	}

	/**
	 * Get the description of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The description of the trigger.
	 */
	public static function get_description(): ?string {
		return __( 'Triggered when a CRM company is added', 'zero-bs-crm' );
	}

	/**
	 * Get the category of the trigger.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string|null The category of the trigger.
	 */
	public static function get_category(): ?string {
		return __( 'company', 'zero-bs-crm' );
	}

	/**
	 * Listen to the desired event.
	 *
	 * @since 6.2.0-alpha
	 */
	protected function listen_to_event() {
		add_action(
			'jpcrm_automation_company_created',
			array( $this, 'execute_workflow' )
		);
	}

}
