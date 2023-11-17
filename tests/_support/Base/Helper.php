<?php
/**
 * Test utils.
 *
 * @package MemberDash\Tests
 */

namespace MemberDash\Tests\Base;

use Faker;
use ReflectionClass;
use ReflectionException;
use WP_UnitTest_Factory_For_Thing;

/**
 * LearnDash test utils.
 */
class Helper {
	/**
	 * Faker instance.
	 *
	 * @var Faker\Generator
	 */
	private $faker;

	/**
	 * Constructs.
	 */
	public function __construct() {
		$this->faker = Faker\Factory::create();
	}

	/**
	 * Call a protected or private method of a class.
	 *
	 * @param object       $object      The object to call the method on.
	 * @param string       $method_name The name of the method to call.
	 * @param array<mixed> $args        The arguments to pass to the method. Default empty array.
	 *
	 * @throws ReflectionException ReflectionException.
	 *
	 * @return mixed The return value of the method.
	 */
	public function call_protected_method( $object, string $method_name, array $args = array() ) {
		$class = new ReflectionClass( $object );

		$method = $class->getMethod( $method_name );

		$method->setAccessible( true );

		return $method->invokeArgs( $object, $args );
	}

	/**
	 * Gets a protected property from a given object via reflection.
	 *
	 * @param object $object        Object.
	 * @param string $property_name Property name.
	 *
	 * @throws ReflectionException ReflectionException.
	 *
	 * @return mixed Property value.
	 */
	public function get_protected_property( $object, string $property_name ) {
		$reflection = new ReflectionClass( $object );

		$property = $reflection->getProperty( $property_name );

		$property->setAccessible( true );

		return $property->getValue( $object );
	}

	/**
	 * Gets a protected static property from a given class via reflection.
	 *
	 * @param class-string $class_name    Class.
	 * @param string       $property_name Property name.
	 *
	 * @throws ReflectionException ReflectionException.
	 *
	 * @return mixed Property value.
	 */
	public function get_protected_static_property( string $class_name, string $property_name ) {
		$reflection = new ReflectionClass( $class_name );

		return $reflection->getStaticPropertyValue( $property_name );
	}

	/**
	 * Gets a protected constant from a given class via reflection.
	 *
	 * @param class-string $class_name Class.
	 * @param string       $const_name Constant name.
	 *
	 * @throws ReflectionException ReflectionException.
	 *
	 * @return mixed Property value.
	 */
	public function get_protected_constant( string $class_name, string $const_name ) {
		$reflection = new ReflectionClass( $class_name );

		return $reflection->getConstant( $const_name );
	}

	/**
	 * Sets a protected property to a given object via reflection.
	 *
	 * @param object|class-string $object_or_class Object or class name.
	 * @param string              $property        Property.
	 * @param mixed               $value           Value.
	 *
	 * @throws ReflectionException ReflectionException.
	 *
	 * @return void
	 */
	public function set_protected_property( $object_or_class, string $property, $value ): void {
		$reflection = new ReflectionClass( $object_or_class );

		$reflection_property = $reflection->getProperty( $property );
		$reflection_property->setAccessible( true );
		$reflection_property->setValue( is_object( $object_or_class ) ? $object_or_class : null, $value );
	}

	/**
	 * Get an array of metadata as key -> value pairs.
	 *
	 * @param int $meta_qty Quantity of metadata to return.
	 *
	 * @return array<string,array<string>> Array of metadata.
	 */
	public function generate_random_meta( int $meta_qty ): array {
		$metadata = array();

		for ( $i = 0; $i < $meta_qty; $i++ ) {
			$metadata[ $this->faker->unique()->word() ] = array( $this->faker->word() );
		}

		return $metadata;
	}

	/**
	 * Generates and returns multiple objects. A small helper for factories.
	 *
	 * @param int                           $quantity Quantity of objects to generate.
	 * @param WP_UnitTest_Factory_For_Thing $factory  Factory.
	 * @param array<string, mixed>          $args     Arguments to pass to the factory. Optional.
	 *
	 * @return array Array of generated objects.
	 *
	 * @phpstan-ignore-next-line -- this is a WP_UnitTest_Factory_For_Thing child class.
	 */
	public function create_many_and_get( int $quantity, WP_UnitTest_Factory_For_Thing $factory, array $args = [] ): array {
		$result = array();

		for ( $i = 0; $i < $quantity; $i++ ) {
			$result[] = $factory->create_and_get( $args ); // @phpstan-ignore-line -- this is a WP_UnitTest_Factory_For_Thing abstract method.
		}

		return $result;
	}
}
