<?php

class MT_Field_DeclarationTest extends MT_Testing_TestCase {
    function test_exists() {
        $this->assertClassExists( 'MT_Field_Declaration' );
    }

    /**
     * @expectedException MT_Exception
     */
    function test_construct_throws_if_name_not_provided() {
        new MT_Field_Declaration( array() );
    }

    /**
     * @expectedException MT_Exception
     */
    function test_construct_throws_if_type_not_provided() {
        new MT_Field_Declaration( array('name' => 'foo') );
    }

    /**
     * @expectedException MT_Exception
     */
    function test_construct_throws_if_type_not_valid() {
        new MT_Field_Declaration( array('name' => 'foo', 'type' => 'zap') );
    }

    function test_field_declarations() {
        $registry = $this->environment->get_type_registry();

		$builder = new MT_Field_Declaration_Builder();
		$builder->with_kind( MT_Field_Declaration::FIELD )
            ->with_name( 'sum' )
            ->with_type( $registry->definition( 'integer' ) )
            ->build();
		$sum_declaration = $builder->build();
        $this->assertTrue( $sum_declaration->is_kind( MT_Field_Declaration::FIELD ) );
        $this->assertEquals( $sum_declaration->get_default_value(), 0 );
        $this->assertEquals( $sum_declaration->get_map_from(), $sum_declaration->get_name() );
        $this->assertSame( $sum_declaration->cast_value('0'), 0);
        $this->assertSame( $sum_declaration->cast_value( 0.1 ), 0);

		$builder = new MT_Field_Declaration_Builder();
		$builder->with_kind(MT_Field_Declaration::FIELD)
            ->with_name( 'first_name' )
            ->with_type( $registry->definition( 'string' ) )
            ->with_default( 'Foobar' )
            ->with_map_from( 'firstName' )
            ->with_required( true )
            ->build();
		$first_name_declaration =$builder->build();
        $this->assertTrue( $first_name_declaration->is_kind( MT_Field_Declaration::FIELD ) );
        $this->assertTrue( $first_name_declaration->is_required() );
        $this->assertEquals( 'Foobar', $first_name_declaration->get_default_value() );
        $this->assertNotEquals( $first_name_declaration->get_map_from(), $first_name_declaration->get_name() );
        $this->assertSame( '0', $first_name_declaration->cast_value(0) );

		$builder = new MT_Field_Declaration_Builder();
        $derived_declaration = $builder
			->with_kind( MT_Field_Declaration::DERIVED )
            ->with_name( 'derived' )
            ->build();
        $this->assertTrue( $derived_declaration->is_kind( MT_Field_Declaration::DERIVED ) );
        $this->assertEquals( $derived_declaration->get_default_value(), null );
    }

    function test_get_description_default_to_name() {
		$builder = new MT_Field_Declaration_Builder();
        $field = $builder->with_kind(MT_Field_Declaration::FIELD)
            ->with_name( 'first_name' )->build();
        $this->assertEquals( 'First name', $field->get_description() );
    }

    function test_as_item_schema_property_contain_choices_if_set() {
        $type_registry = $this->environment->start()->get_type_registry();
        $choices = array( 'a', 'b', 'c' );
        $builder = new MT_Field_Declaration_Builder();
        $builder->with_kind(MT_Field_Declaration::FIELD)
            ->with_choices( $choices )
            ->with_type( $type_registry->definition( 'string' ) )
            ->with_name('field_with_choices')->build();
        $field = $builder->build();
        $this->assertEquals( $choices, $field->get_choices() );
        $schema = $field->as_item_schema_property();
        $this->assertEquals( 'string', $schema['type'] );
        $this->assertTrue( isset( $schema['enum'] ) );
        $this->assertEquals( $choices, $schema['enum'] );
    }
}