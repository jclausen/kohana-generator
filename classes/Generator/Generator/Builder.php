<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Generator Builder class.
 *
 * This class provides convenience methods for building sets of generator
 * types, applying global settings and creating items in one action. See the
 * bundled tasks and guide pages for different ways of combining types.
 *
 * Any methods prefixed by with_* refer to global settings that are applied
 * to each generator type in the list when the builder is prepared.
 *
 * @package    Generator
 * @category   Builders
 * @author     Zeebee
 * @copyright  (c) 2012 Zeebee
 * @license    BSD revised
 */
class Generator_Generator_Builder
{
	// Release version
	const VERSION = '1.0';

	// Generator commands
	const CREATE = 'create';
	const REMOVE = 'remove';

	/**
	 * The list of generator types added by the builder
	 * @var array
	 */
	protected $_generators = array();

	/**
	 * The pretend mode to be applied to each generator
	 * @var boolean
	 */
	protected $_pretend;

	/**
	 * The force mode be applied to each generator
	 * @var boolean
	 */
	protected $_force;

	/**
	 * The verify mode be applied to each generator
	 * @var boolean
	 */
	protected $_verify;

	/**
	 * Is the builder ready to be executed?
	 * @var boolean
	 */
	protected $_is_prepared = FALSE;

	/**
	 * Parameter defaults to be applied to each generator
	 * @var array
	 */
	protected $_defaults = array();

	/**
	 * The absolute base path under which each item should be created
	 * @var string
	 */
	protected $_path;

	/**
	 * The module folder under which each item should be created
	 * @var string
	 */
	protected $_module;

	/**
	 * Overrides the view template file used by each generator
	 * @var string
	 */
	protected $_template;

	/**
	 * The main factory method for returning new builder instances.
	 *
	 * @return  Generator_Builder
	 */
	public static function build()
	{
		return new Generator;
	}

	/**
	 * Returns full paths for loaded module names (or folder names under MODPATH or
	 * defined custom base path), optionally with a check for the path's existence.
	 *
	 * @param   string   $module  The module name or folder
	 * @param   boolean  $verify  Should the existence of the path be checked?
	 * @param   boolean  $base    The custom base path to check
	 * @return  string   The full path to the module
	 * @throws  Generator_Exception  On missing module path
	 */
	public static function get_module_path($module, $verify = TRUE, $base = NULL)
	{
		$modules = Kohana::modules();

		// Return the loaded module path
		if (isset($modules[$module]))
			return $modules[$module];

		// Search for the folder instead
		$path =  $base ?: MODPATH;
		$path .= $module.DIRECTORY_SEPARATOR;

		if ($verify AND ! file_exists($path))
		{
			throw new Generator_Exception("Module ':module' is not loaded or does not exist",
				array(':module' => $module));
		}

		return $path;
	}

	/**
	 * Adds a new type to the builder list, and returns the type instance
	 * so that it can be configured via the fluent interface. Note that
	 * the __call() method allows simple aliasing of this function, so these
	 * are equivalent:
	 *
	 *     Generator::build()->add_type('class', 'Foo');
	 *     Generator::build()->add_class('Foo');
	 *
	 * The $type may be the name of a valid type, or an existing instance
	 * of a Generator_Type class.
	 *
	 * @param   string|Generator_Type  $type   The generator type to be added
	 * @param   string                 $name   The name of the new type
	 * @return  Generator_Type         The new generator instance
	 * @throws  Generator_Exception    On invalid type class name
	 */
	public function add_type($type, $name = NULL)
	{
		if ($type instanceof Generator_Type)
		{
			// Add any generator instances directly
			$this->_generators[] = $type;
			$type->set_builder($this);
			return $type;
		}

		// Convert the requested type to the class name
		$class = rtrim('Generator_Type_'.ucfirst($type), '_');

		if ( ! class_exists($class))
		{
			throw new Generator_Exception("Class ':class' does not exist", array(
				':class' => $class));
		}

		// Create the generator with a reference to this builder
		$type = new $class($name, $this);

		// Store the new generator locally
		$this->_generators[] = $type;

		// We'll need to prepare the generator later
		$this->_is_prepared = FALSE;

		// Return the new generator instance
		return $type;
	}

	/**
	 * Sets the global parameter defaults that are to be applied to each
	 * generator type added by the builder.
	 *
	 * These default values will be merged during prepare() with those already
	 * set on each type, with the type values taking precedence - so these are
	 * to be used as fallback values.
	 *
	 * @param   array  $defaults   The list of default values
	 * @return  Generator_Builder  This instance
	 */
	public function with_defaults(array $defaults = NULL)
	{
		if ($defaults)
		{
			$this->_defaults = $defaults;
			$this->_is_prepared = FALSE;
		}

		return $this;
	}

	/**
	 * Sets the pretend mode to be applied to each generator type added
	 * by the builder.
	 *
	 * @param   boolean  $pretend  The pretend mode
	 * @return  Generator_Builder  This instance
	 */
	public function with_pretend($pretend = TRUE)
	{
		$this->_pretend = (bool) $pretend;

		$this->_is_prepared = FALSE;
		return $this;
	}

	/**
	 * Returns the current global pretend mode.
	 *
	 * @return boolean
	 */
	public function is_pretend()
	{
		return $this->_pretend;
	}

	/**
	 * Sets the force mode to be applied to each generator type added
	 * by the builder.
	 *
	 * @param   boolean  $force  The force mode
	 * @return  Generator_Builder  This instance
	 */
	public function with_force($force = TRUE)
	{
		$this->_force = (bool) $force;

		$this->_is_prepared = FALSE;
		return $this;
	}

	/**
	 * Sets the verify mode to be applied to each generator type added
	 * by the builder.
	 *
	 * @param   boolean  $pretend  The verify mode
	 * @return  Generator_Builder  This instance
	 */
	public function with_verify($verify = TRUE)
	{
		$this->_verify = (bool) $verify;

		$this->_is_prepared = FALSE;
		return $this;
	}

	/**
	 * Sets the absolute base path in which each generator item is to be created,
	 * otherwise defaults to either APPPATH or MODPATH.
	 *
	 * @param   string  $path  The absolute base path
	 * @return  Generator_Builder  This instance
	 */
	public function with_path($path)
	{
		$this->_path = (string) $path;

		$this->_is_prepared = FALSE;
		return $this;
	}

	/**
	 * Sets the name of the module in which each generator item is to be created.
	 * This must be either the name of a loaded module as defined in the bootstrap,
	 * or a valid folder under the current MODPATH.
	 *
	 * @param   string  $module  The module name
	 * @return  Generator_Builder  This instance
	 */
	public function with_module($module)
	{
		$this->_module = (string) $module;

		$this->_is_prepared = FALSE;
		return $this;
	}

	/**
	 * Sets the view template file to be used by each generator. This is only
	 * useful if the generators are of the same type.
	 *
	 * @param   string  $template  The view template
	 * @return  Generator_Builder  This instance
	 */
	public function with_template($template)
	{
		$this->_template = (string) $template;

		$this->_is_prepared = FALSE;
		return $this;
	}

	/**
	 * Returns the list of generators added by the builder, each representing
	 * an item to be created in the filesystem.
	 *
	 * @return  array  The generators list
	 */
	public function generators()
	{
		$this->_is_prepared OR $this->prepare();

		return $this->_generators;
	}

	/**
	 * Allows inspection of the current generators list for debugging purposes.
	 *
	 * @param   boolean  $rendered  Should rendered output be displayed?
	 * @return  array
	 */
	public function inspect($rendered = TRUE)
	{
		$generators = array();

		foreach ($this->_generators as $generator)
		{
			$generators[] = array(
				'file'     => $generator->file(),
				'rendered' => ($rendered ? $generator->render() : ''),
			);
		}

		return $generators;
	}

	/**
	 * Merges the generators from a given builder object into the current
	 * instance, preserving any prepared settings for each.
	 *
	 * @param   Generator_Builder  $builder  The builder to merge
	 * @return  Generator_Builder  This instance
	 */
	public function merge(Generator_Builder $builder)
	{
		// Prepare the generators
		$this->_is_prepared OR $this->prepare();
		$builder->prepare();

		// Merge the generators lists
		$this->_generators = array_merge($this->_generators, $builder->generators());

		foreach ($this->_generators as $generator)
		{
			// Set all references to this instance
			$generator->set_builder($this);
		}

		return $this;
	}

	/**
	 * Runs the given command on all added generators in one action.
	 *
	 * In practice it's more convenient to iterate over each generator
	 * via the generators() method and run the commands individually.
	 *
	 * @param   string  $commmand  The command to run
	 * @return  Generator_Builder  This instance
	 */
	public function execute($command = Generator::CREATE)
	{
		$this->_is_prepared OR $this->prepare();

		foreach ($this->_generators as $generator)
		{
			$generator->$command();
		}

		return $this;
	}

	/**
	 * Returns a combined log of all the actions recorded by each generator.
	 *
	 * @return  array  The combined generators log
	 */
	public function get_log()
	{
		$log = array();

		foreach ($this->_generators as $generator)
		{
			$log = array_merge($log, $generator->log());
		}

		return $log;
	}

	/**
	 * Returns a list of files and folders that have been marked as removed by
	 * the current generators.
	 *
	 * @return  array  A list of removed items
	 */
	public function get_removed_items()
	{
		$removed = array();

		foreach ($this->_generators as $generator)
		{
			foreach ($generator->log() as $msg)
			{
				if ($msg['status'] == Generator::REMOVE)
				{
					$removed[] = $msg['item'];
				}
			}
		}

		return $removed;
	}

	/**
	 * Prepares each generator type added by the builder before execution.
	 *
	 * The main task here is to ensure that the instances are properly configured,
	 * and any global settings are applied.
	 *
	 * @return  Generator_Builder  This instance
	 */
	public function prepare()
	{
		if ($this->_is_prepared)
			return $this;

		foreach ($this->_generators as $generator)
		{
			// Set the module for the generator, if any
			$generator->module($this->_module);

			// Set the verify mode for the generator
			$generator->verify($this->_verify);

			// Set the custom base path for the generator, if any
			$generator->path($this->_path);

			if ( ! $generator->file())
			{
				// We need a filename before continuing
				$generator->guess_filename();
			}

			// Builder defaults should be merged with the generator defaults
			$generator->defaults(array_merge($generator->defaults(), $this->_defaults));

			// Set the other global options
			$generator->template($this->_template);
			$generator->pretend($this->_pretend);
			$generator->force($this->_force);
		}

		// We're finished preparing
		$this->_is_prepared = TRUE;

		return $this;
	}

	/**
	 * This magic method allows simple aliasing of the add_type() method,
	 * and supports the fluent interface by passing undefined method calls
	 * to the last added generator, or else throws an exception.
	 *
	 * @param   string  $method      The undefined method name
	 * @param   string  $arguments   The undefined method arguments
	 * @return  Generator_Type       The requested generator type
	 * @throws  Generator_Exception  For any other undefined methods
	 */
	public function __call($method, $arguments)
	{
		if (strpos($method, 'add_') === 0)
		{
			// Include the name argument if present
			$name = isset($arguments[0]) ? $arguments[0] : NULL;

			// Convert e.g. add_foo($name) to add_type('foo', $name)
			$type = strtolower(substr($method, 4));
			return $this->add_type($type, $name);
		}

		if ( ! empty($this->_generators))
		{
			// We need the last added generator
			$generator = end($this->_generators);

			// Call only existing methods to avoid deadlock
			if (method_exists($generator, $method))
				return call_user_func_array(array($generator, $method), $arguments);
		}

		// Any other undefined methods should throw an exception
		throw new Generator_Exception("Method :method() is not defined for :class",
			array(':method' => $method, ':class' => get_class($this)));
	}

} // End Generator_Generator_Builder
