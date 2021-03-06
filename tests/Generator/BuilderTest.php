<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Test case for Generator_Builder.
 *
 * @group      generator
 * @group      generator.builder
 *
 * @package    Generator
 * @category   Tests
 * @author     Zeebee
 * @copyright  (c) 2012 Zeebee
 * @license    BSD revised
 */
class Generator_BuilderTest extends Unittest_TestCase
{
	/**
	 * The build() method is a factory method for the Builder.
	 */
	public function test_build_returns_new_builder_instance()
	{
		$builder_a = Generator::build();
		$builder_b = Generator::build();

		$this->assertInstanceOf('Generator_Builder', $builder_a);
		$this->assertInstanceOf('Generator_Builder', $builder_b);
		$this->assertNotSame($builder_a, $builder_b);
	}

	/**
	 * Undefined methods should throw Generator_Exception.
	 *
	 * @expectedException Generator_Exception
	 */
	public function test_undefined_method_throws_exception()
	{
		Generator::build()->some_undefined_method();
	}

	/**
	 * Adding invalid types should throw Generator_Exception.
	 *
	 * @expectedException Generator_Exception
	 */
	public function test_adding_invalid_type_throws_exception()
	{
		Generator::build()->add_type('some_invalid_type');
	}

	/**
	 * Successive calls to different methods for adding types
	 * should always return Generator_Type instances.
	 */
	public function test_adding_type_returns_new_type_instance()
	{
		$type = Generator::build()->add_type('class');
		$this->assertInstanceOf('Generator_Type', $type);

		$type = Generator::build()->add_class();
		$this->assertInstanceOf('Generator_Type', $type);

		$type = Generator::build()->add_class()->add_class();
		$this->assertInstanceOf('Generator_Type', $type);

		$type = Generator::build()->add_type(new Generator_Type);
		$this->assertInstanceOf('Generator_Type', $type);
	}

	/**
	 * Created types are stored in the Builder instance.
	 */
	public function test_builder_stores_type_instances()
	{
		$builder = Generator::build()->add_type('class', 'Foo');
		$generators = $builder->generators();

		$this->assertCount(1, $generators);
		$this->assertInstanceOf('Generator_Type', $generators[0]);
	}

	/**
	 * To allow the fluent interface, if undefined methods are called
	 * on the builder and are not of the add_* type, they will be passed
	 * to the last generator added to the builder, if any.
	 */
	public function test_undefined_method_is_called_on_last_added_generator()
	{
		$type = $this->getMock('Generator_Type', array('pretend'));
		$type->expects($this->once())->method('pretend');

		$builder = Generator::build()->add_type($type)->builder();
		$this->assertInstanceOf('Generator_Builder', $builder);
		$builder->pretend();
	}

	/**
	 * Calling a method undefined on a type instance should always
	 * return the builder instance.
	 */
	public function test_undefined_type_method_returns_builder_instance()
	{
		$builder = Generator::build()->add_type('class')->with_pretend();
		$this->assertInstanceOf('Generator_Builder', $builder);
	}

	/**
	 * Methods not defined on either the builder or any added types
	 * should throw Generator_Exception.
	 *
	 * @expectedException Generator_Exception
	 */
	public function test_undefined_method_on_builder_or_types_throws_exception()
	{
		$builder = Generator::build()->add_type(new Generator_Type)->builder();
		$this->assertInstanceOf('Generator_Builder', $builder);
		$builder->some_undefined_method();
	}

	/**
	 * Module names should be converted to valid module paths. Names must be
	 * defined in the bootstrap, or should be folders under MODPATH or any
	 * custom base path.
	 */
	public function test_converts_module_names_to_paths()
	{
		$ds = DIRECTORY_SEPARATOR;

		$modules = Kohana::modules();
		$module = array_search(dirname(dirname(__DIR__)).$ds, $modules);

		$this->assertSame($modules[$module], Generator::get_module_path($module));

		// Verification can be disabled
		$path = MODPATH.'nonexistantmod'.$ds;
		$this->assertSame($path, Generator::get_module_path('nonexistantmod', FALSE));

		// Custom base paths can be specified
		$base = 'some'.$ds.'path'.$ds;
		$path = $base.'nonexistantmod'.$ds;
		$this->assertSame($path, Generator::get_module_path('nonexistantmod', FALSE, $base));
	}

	/**
	 * Only valid module paths are allowed if verification is enabled.
	 *
	 * @expectedException Generator_Exception
	 */
	public function test_missing_module_throws_exception_if_verifying()
	{
		$ds = DIRECTORY_SEPARATOR;

		$path = MODPATH.'nonexistantmod'.$ds;
		$this->assertSame($path, Generator::get_module_path('nonexistantmod', TRUE));
	}

	/**
	 * The prepare() method sets final configuration on each Type, and
	 * completes essential functions like determining filenames.
	 */
	public function test_prepare_configures_stored_types()
	{
		$builder = Generator::build()->add_type('class', 'Foo');
		$this->assertAttributeEmpty('_file', $builder);
		$builder->prepare();
		$this->assertAttributeNotEmpty('_file', $builder);
	}

	/**
	 * The inspect() method can be used to view rendered output either
	 * before or after each Type item has been prepared.
	 */
	public function test_inspect_returns_rendered_output()
	{
		$builder = Generator::build()->add_type('class', 'Foo');
		$inspect = $builder->inspect(FALSE);

		$this->assertCount(1, $inspect);
		$this->assertArrayHasKey('file', $inspect[0]);
		$this->assertArrayHasKey('rendered', $inspect[0]);

		$this->assertEmpty($inspect[0]['file']);
		$this->assertEmpty($inspect[0]['rendered']);

		$builder->prepare();
		$inspect = $builder->inspect();

		$this->assertNotEmpty($inspect[0]['file']);
		$this->assertNotEmpty($inspect[0]['rendered']);
	}

	/**
	 * Global settings can be set on each Type via the Builder's with_* methods.
	 */
	public function test_with_methods_apply_global_settings_to_types()
	{
		$builder = Generator::build()->add_type('class', 'Foo')
			->with_defaults(array('package' => 'Tester'))
			->with_module('amodule')
			->with_template('foo.bar')
			->with_pretend(TRUE)
			->with_force(TRUE)
			->with_verify(FALSE);

		$generators = $builder->generators();
		$globals = $builder->globals();

		$this->assertTrue($globals['pretend']);
		$this->assertAttributeSame(TRUE, '_pretend', $generators[0]);

		$this->assertTrue($globals['force']);
		$this->assertAttributeSame(TRUE, '_force', $generators[0]);

		$this->assertFalse($globals['verify']);
		$this->assertAttributeSame(FALSE, '_verify', $generators[0]);

		$this->assertSame('amodule', $globals['module']);
		$this->assertSame('amodule', $generators[0]->module());

		$this->assertSame('foo.bar', $globals['template']);
		$this->assertSame('foo.bar', $generators[0]->template());

		$this->assertContains('Tester', $globals['defaults']);
		$this->assertContains('Tester', $generators[0]->defaults());

		// With custom base path
		$path = 'somebasepath'.DIRECTORY_SEPARATOR;
		$builder->with_path($path)->prepare();
		$globals = $builder->globals();
		$this->assertSame($path, $globals['path']);
		$this->assertSame($path, $generators[0]->path());
	}

	/**
	 * The execute() method should call create() on each stored item, which
	 * in turn should keep a log of any actions.
	 */
	public function test_execute_calls_create_on_stored_types()
	{
		$builder = Generator::build()->add_type('class', 'Foo')
			->pretend()->execute();

		$generators = $builder->generators();
		$this->assertAttributeNotEmpty('_log', $generators[0]);
	}

	/**
	 * Generating via execute() should produce a status log for each action.
	 */
	public function test_stored_type_execution_logs_are_accessible()
	{
		$log = Generator::build()->add_type('class', 'Foo')
			->pretend()->execute()->get_log();

		$this->assertCount(2, $log);
		$this->assertArrayHasKey('status', $log[0]);
		$this->assertArrayHasKey('item', $log[0]);
	}

	/**
	 * Executing an empty builder should do nothing.
	 */
	public function test_executing_empty_builder_does_nothing()
	{
		$builder = Generator::build()->prepare()->execute();

		$this->assertEmpty($builder->generators());
		$this->assertEmpty($builder->get_log());
	}

	/**
	 * Generators from different builder instances may be merged into each other,
	 * possibly with the different prepared settings for each. Merged generators
	 * should reference the new builder object.
	 */
	public function test_builders_can_merge_types_from_other_builders()
	{
		$builder_a = Generator::build()
			->add_type('class', 'Foo')
				->module('baz')
				->verify(FALSE)
			->builder();

		$builder_b = Generator::build()
			->add_type('class', 'Bar')
				->module('qux')
				->verify(FALSE)
			->builder();

		$builder_a->merge($builder_b);

		$generators = $builder_a->generators();
		$this->assertCount(2, $generators);

		$this->assertSame('Foo', $generators[0]->name());
		$this->assertNotEmpty($generators[0]->file());
		$this->assertSame('baz', $generators[0]->module());
		$this->assertSame($builder_a, $generators[0]->builder());

		$this->assertSame('Bar', $generators[1]->name());
		$this->assertNotEmpty($generators[1]->file());
		$this->assertSame('qux', $generators[1]->module());
		$this->assertSame($builder_a, $generators[1]->builder());
	}

	/**
	 * Paths produced by Debug::path() and equivalent should be reversible.
	 */
	public function test_debug_paths_can_be_expanded()
	{
		$path = 'some/file/path.php';

		$this->assertSame(APPPATH.$path, Generator::expand_path('APPPATH/'.$path));
		$this->assertSame(MODPATH.$path, Generator::expand_path('MODPATH/'.$path));
		$this->assertSame(SYSPATH.$path, Generator::expand_path('SYSPATH/'.$path));
		$this->assertSame(DOCROOT.$path, Generator::expand_path('DOCROOT/'.$path));
	}

	/**
	 * We should be able to load configuration values from any source, including
	 * from absolute file paths, with or without array paths specified.
	 */
	public function test_config_can_be_loaded_from_different_sources()
	{
		$expected = array('author' => 'Author', 'copyright' => '(c) 2012 Author', 'license' => 'License info');

		// From an absolute file path
		$file = dirname(dirname(dirname(__FILE__))).'/config/testconfig/generator.php';
		$config = Generator::get_config($file, 'defaults.class');
		$this->assertEquals($expected, $config);

		// We can also get the whole config as an array
		$config = Generator::get_config($file);
		$this->assertArrayHasKey('defaults', $config);
		$this->assertArrayHasKey('class', $config['defaults']);
		$this->assertArrayHasKey('guide', $config['defaults']);

		// From searching the CFS
		$config = Generator::get_config('testconfig/generator', 'defaults.class');
		$this->assertEquals($expected, $config);

		$config = Generator::get_config('testconfig/generator');
		$this->assertArrayHasKey('defaults', $config);
		$this->assertArrayHasKey('class', $config['defaults']);
		$this->assertArrayHasKey('guide', $config['defaults']);
	}

	/**
	 * We should be able to load message values from any source, including from
	 * absolute file paths, with or without array paths specified.
	 */
	public function test_messages_can_be_loaded_from_different_sources()
	{
		$expected = array('three' => 'second message', 'four' => array('five' => 'third message'));

		// From an absolute file path
		$file = dirname(dirname(dirname(__FILE__))).'/messages/testmsgs/generator.php';
		$msg = Generator::get_message($file, 'two');
		$this->assertEquals($expected, $msg);

		// We can also get the whole config as an array
		$msg = Generator::get_message($file);
		$this->assertArrayHasKey('one', $msg);
		$this->assertArrayHasKey('two', $msg);
		$this->assertArrayHasKey('three', $msg['two']);

		// From searching the CFS
		$msg = Generator::get_message('testmsgs/generator', 'two');
		$this->assertEquals($expected, $msg);

		$msg = Generator::get_message('testmsgs/generator');
		$this->assertArrayHasKey('one', $msg);
		$this->assertArrayHasKey('two', $msg);
		$this->assertArrayHasKey('three', $msg['two']);
	}

} // End Generator_BuilderTest
