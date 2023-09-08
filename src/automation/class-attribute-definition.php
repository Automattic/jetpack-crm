<?php
/**
 * Attribute Definition
 *
 * @package automattic/jetpack-crm
 * @since 6.2.0-alpha
 */

namespace Automattic\Jetpack\CRM\Automation;

/**
 * Step Attribute.
 *
 * The Step Attribute represents
 *
 * @since 6.2.0-alpha
 */
class Attribute_Definition {

	/**
	* Represents a dropdown selection input.
	*
	* @since 6.2.0-alpha
	* @var string
	*/
	const SELECT = 'select';

	/**
	* Represents a checkbox input.
	*
	* @since 6.2.0-alpha
	* @var string
	*/
	const CHECKBOX = 'checkbox';

	/**
	* Represents a textarea input.
	*
	* @since 6.2.0-alpha
	* @var string
	*/
	const TEXTAREA = 'textarea';

	/**
	* Represents a text input.
	*
	* @since 6.2.0-alpha
	* @var string
	*/
	const TEXT = 'text';

	/**
	* Represents a date input.
	*
	* @since 6.2.0-alpha
	* @var string
	*/
	const DATE = 'date';

	/**
	* Represents a date and time input.
	*
	* @since 6.2.0-alpha
	* @var string
	*/
	const DATETIME = 'datetime';

	/**
	* Represents a numerical input.
	*
	* @since 6.2.0-alpha
	* @var string
	*/
	const NUMBER = 'number';

	/**
	* Represents a password input.
	*
	* @since 6.2.0-alpha
	* @var string
	*/
	const PASSWORD = 'password';

	/**
	 * The slug (key) that identifies this attribute.
	 *
	 * @since 6.2.0-alpha
	 * @var string
	 */
	protected $slug;

	/**
	 * The title (label) for this attribute.
	 *
	 * @since 6.2.0-alpha
	 * @var string
	 */
	protected $title;

	/**
	 * The description for this attribute.
	 *
	 * @since 6.2.0-alpha
	 * @var string
	 */
	protected $description;

	/**
	 * Attribute type (is it a select? an input?). The const values of this class
	 * should be used here (e.g. Step_Attribute::NUMBER).
	 *
	 * @since 6.2.0-alpha
	 * @var string
	 */
	protected $type;

	/**
	 * Data needed by this attribute (e.g. a map of "key -> description" in the case of a select).
	 *
	 * @since 6.2.0-alpha
	 * @var array|null
	 */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param string     $slug        The slug (key) that identifies this attribute.
	 * @param string     $title       The title (label) for this attribute.
	 * @param string     $description The description for this attribute.
	 * @param string     $type        Attribute type.
	 * @param array|null $data        Data needed by this attribute.
	 */
	public function __construct( $slug, $title, $description, $type, $data = null ) {
		$this->slug        = $slug;
		$this->title       = $title;
		$this->description = $description;
		$this->type        = $type;
		$this->data        = $data;
	}

	/**
	 * Get the slug.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string
	 */
	public function getSlug(): string {
		return $this->slug;
	}

	/**
	 * Set the slug.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param string $slug The slug (key) that identifies this attribute.
	 */
	public function setSlug( string $slug ): void {
		$this->slug = $slug;
	}

	/**
	 * Get the title.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * Set the title.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param string $title The title (label) for this attribute.
	 */
	public function setTitle( string $title ): void {
		$this->title = $title;
	}

	/**
	 * Get the description.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * Set the description.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param string $description The description for this attribute.
	 */
	public function setDescription( string $description ): void {
		$this->description = $description;
	}

	/**
	 * Get the type.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Set the type.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param string $type The attribute type.
	 */
	public function setType( string $type ): void {
		$this->type = $type;
	}

	/**
	 * Get the data.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @return array|null
	 */
	public function getData(): ?array {
		return $this->data;
	}

	/**
	 * Set the data.
	 *
	 * @since 6.2.0-alpha
	 *
	 * @param array|null $data The data needed by this attribute.
	 */
	public function setData( ?array $data ): void {
		$this->data = $data;
	}
}