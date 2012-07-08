<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Task for generating classes, see Task_Generate_Class for usage.
 *
 * @package    Generator
 * @category   Generator/Tasks
 * @author     Zeebee
 * @copyright  (c) 2012 Zeebee
 * @license    BSD revised
 */
class Generator_Task_Generate_Class extends Task_Generate
{
	/**
	 * @var  array  The task options
	 */
	protected $_options = array(
		'name'      => '',
		'extend'    => '',
		'implement' => '',
		'stub'      => '',
		'abstract'  => FALSE,
		'no-test'   => FALSE,
		'clone'     => '',
		'reflect'   => FALSE,
		'inherit'   => FALSE,
	);

	/**
	 * Validates the task options.
	 *
	 * @param  Validation  $validation  The validation object to add rules to
	 * @return Validation
	 */
	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('name', 'not_empty');
	}

	/**
	 * Creates a generator builder with the given configuration options.
	 *
	 * @param  array  $options  The selected task options
	 * @return Generator_Builder
	 */
	public function get_builder(array $options)
	{
		if ( ! empty($options['clone']))
		{
			$builder = $this->get_clone($options);
		}
		else
		{
			$builder = Generator::build()
				->add_class($options['name'])
					->as_abstract(($options['abstract']))
					->extend($options['extend'])
					->implement($options['implement'])
					->template($options['template'])
				->builder();
		}

		if ($options['stub'])
		{
			$builder->add_class($options['stub'])
				->extend($options['name'])
				->template($options['template'])
				->blank();
		}

		if ( ! $options['no-test'])
		{
			$name = $options['stub'] ? $builder->name() : $options['name'];
			$builder->add_unittest($name)
				->group($options['module']);
		}

		return $builder
			->with_module($options['module'])
			->with_pretend($options['pretend'])
			->with_force($options['force'])
			->with_defaults($this->get_config('defaults.class', $options['config']))
			->prepare();
	}

	/**
	 * Creates a generator builder that clones an existing class, either from
	 * an existing file or from an internal class definition.
	 *
	 * @throws  Generator_Exception  On missing class to clone
	 * @param  array  $options  The selected task options
	 * @return Generator_Builder
	 */
	public function get_clone(array $options)
	{
		if ( ! class_exists($options['clone']))
		{
			throw new Generator_Exception("Class ':class' does not exist", array(
				':class' => $options['clone']));
		}

		// Convert the cloned class name to a filename
		$source = str_replace('_', DIRECTORY_SEPARATOR, $options['clone']);

		if ( ! $options['reflect'] AND ($file = Kohana::find_file('classes', $source)))
		{
			// Use the existing class file
			$content = file_get_contents($file);

			// Replace the class name references
			$content = preg_replace("/\b{$options['clone']}\b/", $options['name'], $content);

			// Convert the generated class name to a filename
			$destination = str_replace('_', DIRECTORY_SEPARATOR, $options['name']).EXT;

			// Create the Builder
			$builder = Generator::build()
				->add_file($destination)
					->folder('classes')
					->content($content)
				->builder();
		}
		else
		{
			// Use the internal class definition via reflection
			$builder = Generator::build()
				->add_clone($options['name'])
					->source($options['clone'])
					->type(Generator_Reflector::TYPE_CLASS)
					->inherit($options['inherit'])
				->builder();
		}

		return $builder;
	}

} // End Generator_Task_Generate_Class
