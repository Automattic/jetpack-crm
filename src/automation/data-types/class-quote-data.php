<?php
/**
 * Quote Data Type.
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation\Data_Types;

use Automattic\Jetpack\CRM\Entities\Quote;

/**
 * Quote Data Type.
 *
 * @since 6.2.0-alpha
 */
class Quote_Data extends Data_Type_Base {

	/**
	 * Validate the data.
	 *
	 * This method is meant to validate if the data has the expected inheritance
	 * or structure and will be used to throw a fatal error if not.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param mixed $data The data to validate.
	 * @return bool Whether the data is valid.
	 */
	public function validate_data( $data ): bool {
		return $data instanceof Quote;
	}
}
