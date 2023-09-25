<?php
/**
 * Transaction Event.
 *
 * @package automattic/jetpack-crm
 */

namespace Automattic\Jetpack\CRM\Event_Manager;

/**
 * Transaction Event class.
 *
 * @since 6.2.0-alpha
 */
class Transaction_Event implements Event {

	/**
	 * The Transaction_Event instance.
	 *
	 * @since 6.2.0-alpha
	 * @var Transaction_Event
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return Transaction_Event The Transaction_Event instance.
	 */
	public static function get_instance(): Transaction_Event {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * A new transaction was created.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $transaction_data The created transaction data.
	 * @return void
	 */
	public function created( array $transaction_data ): void {
		do_action( 'jpcrm_transaction_created', $transaction_data );
	}

	/**
	 * The transaction was updated.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array $transaction_data The updated transaction data.
	 * @return void
	 */
	public function updated( array $transaction_data ): void {
		do_action( 'jpcrm_transaction_updated', $transaction_data );
	}

	/**
	 * The transaction was deleted.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param int $transaction_id The deleted transaction id.
	 * @return void
	 */
	public function deleted( int $transaction_id ): void {
		do_action( 'jpcrm_transaction_deleted', $transaction_id );
	}
}
