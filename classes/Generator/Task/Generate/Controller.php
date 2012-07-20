<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Task for generating controllers, see Task_Generate_Controller for usage.
 *
 * @package    Generator
 * @category   Generator/Tasks
 * @author     Zeebee
 * @copyright  (c) 2012 Zeebee
 * @license    BSD revised
 */
class Generator_Task_Generate_Controller extends Task_Generate
{
	/**
	 * @var  array  The task options
	 */
	protected $_options = array(
		'name'    => '',
		'actions' => '',
		'extend'  => '',
		'blank'   => FALSE,
	);

	/**
	 * @var  array  Arguments mapped to options
	 */
	protected $_arguments = array(
		1 => 'name',
		2 => 'actions',
	);

	/**
	 * Validates the task options.
	 *
	 * @param   Validation  $validation  The validation object to add rules to
	 * @return  Validation
	 */
	public function build_validation(Validation $validation)
	{
		return parent::build_validation($validation)
			->rule('name', 'not_empty');
	}

	/**
	 * Creates a generator builder with the given configuration options.
	 *
	 * @param   array  $options  The selected task options
	 * @return  Generator_Builder
	 */
	public function get_builder(array $options)
	{
		return Generator::build()
			->add_controller($options['name'])
				->extend($options['extend'])
				->action($options['actions'])
				->blank($options['blank'])
				->module($options['module'])
				->template($options['template'])
				->pretend($options['pretend'])
				->force($options['force'])
			->with_defaults($this->get_config('defaults.class', $options['config']));
	}

} // End Generator_Task_Generate_Controller
