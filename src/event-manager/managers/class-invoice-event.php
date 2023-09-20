<?php
/**
 * Invoice Event.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Event_Manager;

/**
 * Invoice Event class.
 *
 * @since 6.2.0-alpha
 */
class Invoice_Event implements Event {

	/**
	 * The Invoice_Event instance.
	 *
	 * @since 6.2.0-alpha
	 * @var Invoice_Event
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return Invoice_Event The Invoice_Event instance.
	 */
	public static function get_instance(): Invoice_Event {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * A new invoice was created.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $invoice_data The created invoice data.
	 * @return void
	 */
	public function created( array $invoice_data ): void {
		do_action( 'jpcrm_invoice_created', $invoice_data );
	}

	/**
	 * The invoice was updated.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $invoice_data The updated invoice data.
	 * @return void
	 */
	public function updated( array $invoice_data ): void {
		do_action( 'jpcrm_invoice_updated', $invoice_data );
	}
}
