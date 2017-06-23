<?php
/**
 * Fields
 *
 * @package MT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // End if().

/**
 * Class Mixtape_Model_Field_Declaration
 */
class MT_Model_Field_Declaration {
	/**
	 * Field A field
	 */
	const FIELD = 'field';
	/**
	 * Meta a meta field
	 */
	const META = 'meta';
	/**
	 * Derived fields kinds get their values from callables. It is also
	 * possible to update their values from callables
	 */
	const DERIVED = 'derived';
	private $map_from;
	/**
	 * The field kind
	 *
	 * @var string
	 */
	private $kind;
	private $name;
	private $primary;
	private $required;
	private $supported_outputs;
	private $description;
	private $data_transfer_name;
	private $validations;
	private $default_value;
	private $choices;
	/**
	 * @var null|MT_Interfaces_Type
	 */
	private $type;

	/**
	 * Acceptable field kinds
	 *
	 * @var array
	 */
	private $field_kinds = array(
		self::FIELD,
		self::META,
		self::DERIVED,
	);
	/**
	 * A custom function to call before serialization
	 *
	 * @var null|callable
	 */
	private $serializer;
	/**
	 * A custom function to call before deserialization
	 *
	 * @var null|callable
	 */
	private $deserializer;
	/**
	 * A custom function to use for sanitizing the field value before setting it.
	 * Used when receiving values from untrusted sources (e.g. a web form of a REST API request)
	 *
	 * @var null|callable
	 */
	private $sanitizer;
	/**
	 * A custom filtering callable triggered before setting the field with the value
	 *
	 * @var null|callable
	 */
	private $before_set;
	/**
	 * A custom filtering callable triggered before returning the field value
	 *
	 * @var null|callable
	 */
	private $before_get;
	/**
	 * Used by derived fields: The function to use to get the field value
	 *
	 * @var null|callable
	 */
	private $reader;
	/**
	 * Used by derived fields: The function to use to update the field value
	 *
	 * @var null|callable
	 */
	private $updater;

	/**
	 * MT_Model_Field_Declaration constructor.
	 *
	 * @param array $args The arguments.
	 * @throws MT_Exception When invalid name or kind provided.
	 */
	public function __construct( $args ) {
		if ( ! isset( $args['name'] ) || empty( $args['name'] ) || ! is_string( $args['name'] ) ) {
			throw new MT_Exception( 'every field declaration should have a (non-empty) name string' );
		}
		if ( ! isset( $args['type'] ) || ! in_array( $args['type'], $this->field_kinds, true ) ) {
			throw new MT_Exception( 'every field should have a type (one of ' . implode( ',', $this->field_kinds ) . ')' );
		}

		$this->name                = $args['name'];
		$this->description         = $this->value_or_default( $args, 'description', '' );

		$this->kind                = $args['type'];
		$this->type     = $this->value_or_default( $args, 'type_definition', MT_Type::any() );
		$this->choices             = $this->value_or_default( $args, 'choices', null );
		$this->default_value       = $this->value_or_default( $args, 'default_value' );

		$this->map_from            = $this->value_or_default( $args, 'map_from' );
		$this->data_transfer_name  = $this->value_or_default( $args, 'data_transfer_name', $this->get_name() );

		$this->primary             = $this->value_or_default( $args, 'primary', false );
		$this->required            = $this->value_or_default( $args, 'required', false );
		$this->supported_outputs   = $this->value_or_default( $args, 'supported_outputs', array( 'json' ) );

		$this->sanitizer            = $this->value_or_default( $args, 'sanitize' );
		$this->validations         = $this->value_or_default( $args, 'validations', array() );

		$this->serializer          = $this->value_or_default( $args, 'on_serialize' );
		$this->deserializer        = $this->value_or_default( $args, 'on_deserialize' );

		$this->before_get          = $this->value_or_default( $args, 'before_return' );
		$this->before_set          = $this->value_or_default( $args, 'before_model_set' );

		$this->reader              = $this->value_or_default( $args, 'reader' );
		$this->updater             = $this->value_or_default( $args, 'updater' );
	}

	/**
	 * Get possible choices if set
	 *
	 * @return null|array
	 */
	public function get_choices() {
		return $this->choices;
	}

	public function get_sanitizer() {
		return $this->sanitizer;
	}

	private function value_or_default( $args, $name, $default = null ) {
		return isset( $args[ $name ] ) ? $args[ $name ] : $default;
	}

	public function is_meta_field() {
		return $this->kind === self::META;
	}

	public function is_derived_field() {
		return $this->kind === self::DERIVED;
	}

	public function is_field() {
		return $this->kind === self::FIELD;
	}

	public function get_default_value() {
		if ( isset( $this->default_value ) && ! empty( $this->default_value ) ) {
			return ( is_array( $this->default_value ) && is_callable( $this->default_value ) ) ? call_user_func( $this->default_value ) : $this->default_value;
		}

		return $this->type->default_value();
	}

	public function cast_value( $value ) {
		return $this->type->cast( $value );
	}

	public function supports_output_type( $type ) {
		return in_array( $type, $this->supported_outputs, true );
	}

	public function as_item_schema_property() {
		$schema = $this->type->schema();
		$schema['context'] = array( 'view', 'edit' );
		$schema['description'] = $this->get_description();

		if ( $this->get_choices() ) {
			$schema['enum'] = (array) $this->get_choices();
		}
		return $schema;
	}

	/**
	 * @return null
	 */
	public function get_map_from() {
		if ( isset( $this->map_from ) && ! empty( $this->map_from ) ) {
			return $this->map_from;
		}

		return $this->get_name();
	}

	/**
	 * @return mixed
	 */
	public function get_kind() {
		return $this->kind;
	}

	/**
	 * @return mixed
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return bool
	 */
	public function is_primary() {
		return (bool) $this->primary;
	}

	/**
	 * @return bool
	 */
	public function is_required() {
		return (bool) $this->required;
	}

	/**
	 * @return string
	 */
	public function get_description() {
		if ( isset( $this->description ) && ! empty( $this->description ) ) {
			return $this->description;
		}
		$name = ucfirst( str_replace( '_', ' ', $this->get_name() ) );
		return $name;
	}

	/**
	 * @return string
	 */
	public function get_data_transfer_name() {
		return isset( $this->data_transfer_name ) ? $this->data_transfer_name : $this->get_name();
	}

	/**
	 * @return array
	 */
	public function get_validations() {
		return $this->validations;
	}

	/**
	 * Get Before get
	 *
	 * @return callable|null
	 */
	public function before_get() {
		return $this->before_get;
	}

	/**
	 * Get Serializer
	 *
	 * @return callable|null
	 */
	public function get_serializer() {
		return $this->serializer;
	}

	/**
	 * Get Deserializer
	 *
	 * @return callable|null
	 */
	public function get_deserializer() {
		return $this->deserializer;
	}

	/**
	 * Get Type
	 *
	 * @return MT_Interfaces_Type
	 */
	function get_type() {
		return $this->type;
	}

	/**
	 * Before Set
	 *
	 * @return callable|null
	 */
	public function before_set() {
		return $this->before_set;
	}

	/**
	 * Get Reader
	 *
	 * @return callable|null
	 */
	public function get_reader() {
		return $this->reader;
	}

	/**
	 * Get Updater
	 *
	 * @return callable|null
	 */
	public function get_updater() {
		return $this->updater;
	}
}
