<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * This class provides some shortcuts for handling basic Reflection details
 * from sources such as classes, interfaces, etc.
 *
 * Thanks to simshaun for the pointers and samples.
 *
 * @package    Generator
 * @category   Reflectors
 * @author     Zeebee
 * @copyright  (c) 2012 Zeebee
 * @license    BSD revised
 */
class Generator_Generator_Reflector
{
	// The supported source types
	const TYPE_CLASS     = 'class';
	const TYPE_INTERFACE = 'interface';

	/**
	 * The source class, interface etc. to inspect
	 * @var  string
	 */
	protected $_source;

	/**
	 * The current source type
	 * @var  string
	 */
	protected $_type;

	/**
	 * The parsed reflection info for the source
	 * @var  string
	 */
	protected $_info = array();

	/**
	 * Instantiates the reflector and stores the name of the source that is
	 * being inspected.
	 *
	 * @param  string  $source  The source name
	 * @param  string  $type    The source type
	 * @return void
	 */
	public function __construct($source = NULL, $type = Generator_Reflector::TYPE_CLASS)
	{
		$this->source($source);
		$this->type($type);
	}

	/**
	 * Setter/getter for the source class, interface etc. being inspected.
	 *
	 * @param   string  $source  The source name
	 * @return  string|Generator_Reflector  The current source name or this instance
	 */
	public function source($source = NULL)
	{
		if ($source === NULL)
			return $this->_source;

		if ($this->_source !== $source)
		{
			// Reset if we're swapping sources
			$this->_source = $source;
			$this->_info = array();
		}

		return $this;
	}

	/**
	 * Setter/getter for the current source type.
	 *
	 * @param   string  $type    The source type
	 * @return  string|Generator_Reflector  The current source type or this instance
	 */
	public function type($type = NULL)
	{
		if ($type === NULL)
			return $this->_type;

		if ($this->_type !== $type)
		{
			// Reset if we're swapping types
			$this->_type = $type;
			$this->_info = array();
		}

		return $this;
	}

	/**
	 * Determines whether the current source exists, based on its given type.
	 *
	 * @return  bool
	 */
	public function exists()
	{
		return call_user_func($this->_type.'_exists', $this->_source);
	}

	/**
	 * Gathers basic reflection info on the given source and stores it locally.
	 *
	 * @throws  Generator_Exception  On missing source
	 * @return  Generator_Reflector  This instance
	 */
	public function analyze()
	{
		if ( ! $this->_source)
		{
			// We need a source to work with
			throw new Generator_Exception('No source is available to analyze');
		}

		// Start the new reflection
		$class = new ReflectionClass($this->_source);

		// Store the reflection info locally
		$this->_info = $this->parse_reflection_class($class);

		return $this;
	}

	/**
	 * Parses reflection classes for key information to store locally.
	 *
	 * @param   ReflectionClass  $class  The class to parse
	 * @return  array  The parsed info
	 */
	public function parse_reflection_class(ReflectionClass $class)
	{
		// Get any class doccomment
		$doccomment = $class->getDocComment();

		// Get the full class name
		$name = $class->getName();

		// Get any class modifiers
		$modifiers = Reflection::getModifierNames($class->getModifiers());

		// Get the abstract flag, always set false for interfaces
		$abstract = $this->is_interface() ? FALSE : $class->isAbstract();

		// Get any implemented interfaces
		$parent = ($parent = $class->getParentClass()) ? $parent->getName() : NULL;

		// Get any implemented interfaces
		$interfaces = $class->getInterfaceNames();

		// Get any class constants
		$constants = $class->getConstants();

		// Get the default properties list
		$defaults = $class->getDefaultProperties();

		// Get any class properties
		$properties = array();
		foreach ($class->getProperties() as $property)
		{
			$properties[$property->getName()] = $this->parse_reflection_property($property, $defaults);
		}

		// Start the methods list
		$methods = array();

		// Get any declared methods
		foreach ($class->getMethods() as $method)
		{
			$m = $this->parse_reflection_method($method);

			if ( ! $abstract AND $m['abstract'])
			{
				// We shouldn't have any abstract methods in a concrete class
				$m = $this->make_method_concrete($m);
			}

			$methods[$method->getName()] = $m;
		}

		// Return the parsed info
		return array(
			'doccomment' => $doccomment,
			'name'       => $name,
			'modifiers'  => $modifiers,
			'abstract'   => $abstract,
			'parent'     => $parent,
			'interfaces' => $interfaces,
			'constants'  => $constants,
			'properties' => $properties,
			'methods'    => $methods,
		);
	}

	/**
	 * Parses reflection properties for information such as modifiers, type,
	 * any default values, etc.
	 *
	 * @param   ReflectionProperty  $property  The property to parse
	 * @param   array  $defaults  A list of defined property defaults
	 * @return  array  The parsed info
	 */
	public function parse_reflection_property(ReflectionProperty $property, array $defaults)
	{
		// Get the property doccomment
		$doccomment = $property->getDocComment();

		// Get the declaring class name
		$class = $property->getDeclaringClass()->getName();

		// Get the modifiers string
		$modifiers = implode(' ', Reflection::getModifierNames($property->getModifiers()));

		// Get any default value
		$default = ($property->isDefault() AND $defaults[$property->getName()] !== NULL)
			? $this->export_variable($defaults[$property->getName()])
			: NULL;

		// Get the property type based on the default value
		$type = $this->get_variable_type($defaults[$property->getName()]);

		// Return the parsed info
		return array(
			'class'      => $class,
			'doccomment' => $doccomment,
			'modifiers'  => $modifiers,
			'value'      => $default,
			'type'       => $type,
		);
	}

	/**
	 * Parses reflection methods for key information, including any modifiers,
	 * declaring class, the parsed parameters list, etc.
	 *
	 * @param   ReflectionMethod  $method  The method to parse
	 * @return  array  The parsed info
	 */
	public function parse_reflection_method(ReflectionMethod $method)
	{
		// Get any method doccomment
		$doccomment = $method->getDocComment();

		// Get the declaring class name
		$class = $method->getDeclaringClass()->getName();

		// Get the modifiers string
		$modifiers = implode(' ', Reflection::getModifierNames($method->getModifiers()));

		// Get the returns by reference flag
		$by_ref = $method->returnsReference();

		// Get the method flags
		$abstract = $method->isAbstract();
		$final    = $method->isFinal();
		$private  = $method->isPrivate();

		// Get the parsed parameters list
		$params = array();
		foreach ($method->getParameters() as $param)
		{
			$params[$param->getName()] = $this->parse_reflection_param($param);
		}

		// Return the parsed info
		return array(
			'class'      => $class,
			'doccomment' => $doccomment,
			'modifiers'  => $modifiers,
			'by_ref'     => $by_ref,
			'abstract'   => $abstract,
			'final'      => $final,
			'private'    => $private,
			'params'     => $params,
		);
	}

	/**
	 * Parses reflection parameters for information such as type hints and any
	 * default values, etc.
	 *
	 * @param   ReflectionParameter  $param  The parameter to parse
	 * @return  array  The parsed info
	 */
	public function parse_reflection_param(ReflectionParameter $param)
	{
		// Get any type hint without needing to load any classes
		preg_match('/\[\s\<\w+?>\s([\w]+)/s', $param->__toString(), $matches);
		$type = isset($matches[1]) ? $matches[1] : '';

		// Do we have a type hint to use?
		$hint = (bool) $type;

		// Get the param properties
		$by_ref = $param->isPassedByReference();
		$default = NULL;

		if ($param->isDefaultValueAvailable())
		{
			// Add any default values
			$default = $this->export_variable($param->getDefaultValue());

			if ($type == '')
			{
				// Set the type info based on the default value
				$type = $this->get_variable_type($param->getDefaultValue());
			}
		}

		// Use 'mixed' as the default type
		$type = ($type == '') ? 'mixed' : $type;

		// Return the parsed info
		return array(
			'type'    => $type,
			'hint'    => $hint,
			'default' => $default,
			'by_ref'  => $by_ref
		);
	}

	/**
	 * Converts a parsed abstract method definition to a concrete one for
	 * storing locally.
	 *
	 * @param   array   $method  The method definition to convert
	 * @return  array   The converted definition
	 */
	public function make_method_concrete(array $method)
	{
		$method['modifiers'] = trim(str_replace('abstract', '', $method['modifiers']));
		$method['abstract'] = FALSE;

		return $method;
	}

	/**
	 * Exports a variable value to a parsable string representation. Array
	 * variables can be processed recursively, and indentation may optionally
	 * be included with these.
	 *
	 * @param   mixed   $variable  The variable to export
	 * @param   bool    $indent    Should indentation be included?
	 * @param   bool    $level     The indentation level
	 * @return  string  The exported string
	 */
	public function export_variable($variable, $indent = FALSE, $level = 1)
	{
		if ( ! is_array($variable))
		{
			// Objects shouldn't be exported
			if (is_object($variable))
				return NULL;

			// Return the exported value
			$val = var_export($variable, TRUE);
			return in_array($val, array('true', 'false', 'null')) ? strtoupper($val) : $val;
		}

		// Convert arrays to comma-separated lists
		$list = array();

		foreach ($variable as $key => $value)
		{
			// Array values may be exported recursively
			$entry = $this->export_variable($value, $indent, is_array($value) ? ($level + 1) : $level);

			if ( ! is_integer($key))
			{
				// Expand string keys to 'key' => val
				$entry = "'{$key}' => ".$entry;
			}

			// Add the new entry
			$list[] = $entry;
		}

		if ($indent)
		{
			// Return an indented array definition
			return 'array('.PHP_EOL
				.str_repeat("\t", $level)
				.implode(",\n".str_repeat("\t", $level), $list).','.PHP_EOL
				.str_repeat("\t", $level - 1).')';
		}

		// Return a flat array definition
		return 'array('.implode(', ', $list).')';
	}

	/**
	 * Returns a normalized type definition for a given variable.
	 *
	 * @param   mixed   $variable  The variable to inspect
	 * @return  string  The normalized type
	 */
	public function get_variable_type($variable)
	{
		$type = gettype($variable);
		$type = str_replace(array('NULL', 'boolean'), array('mixed', 'bool'), $type);

		return $type;
	}

	/**
	 * Determines whether the current source has been analyzed yet.
	 *
	 * @return  bool
	 */
	public function is_analyzed()
	{
		return ! empty($this->_info);
	}

	/**
	 * Determines whether the current source is an interface type.
	 *
	 * @return  bool
	 */
	public function is_interface()
	{
		return $this->_type === Generator_Reflector::TYPE_INTERFACE;
	}

	/**
	 * Determines whether the current source is a class type.
	 *
	 * @return  bool
	 */
	public function is_class()
	{
		return $this->_type === Generator_Reflector::TYPE_CLASS;
	}

	/**
	 * Determines whether the current source is an abstract type.
	 *
	 * @return  bool
	 */
	public function is_abstract()
	{
		$this->is_analyzed() OR $this->analyze();

		return $this->_info['abstract'];
	}

	/**
	 * Returns the doccomment for the current source.
	 *
	 * @return  string  The source doccomment
	 */
	public function get_doccomment()
	{
		$this->is_analyzed() OR $this->analyze();

		return $this->_info['doccomment'];
	}

	/**
	 * Returns the modifiers string for the current source.
	 *
	 * @return  string  The source modifiers
	 */
	public function get_modifiers()
	{
		$this->is_analyzed() OR $this->analyze();

		return implode(' ', $this->_info['modifiers']);
	}

	/**
	 * Returns the parent class of the current source.
	 *
	 * @return  string  The parent class name
	 */
	public function get_parent()
	{
		$this->is_analyzed() OR $this->analyze();

		return $this->_info['parent'];
	}

	/**
	 * Returns the list of interfaces implemented by the current source.
	 *
	 * @return  array  The interfaces list
	 */
	public function get_interfaces()
	{
		$this->is_analyzed() OR $this->analyze();

		return $this->_info['interfaces'];
	}

	/**
	 * Returns the list of constants defined by the current source.
	 *
	 * @return  array  The constants list
	 */
	public function get_constants()
	{
		$this->is_analyzed() OR $this->analyze();

		return $this->_info['constants'];
	}

	/**
	 * Returns the list of properties with their parsed info from the current
	 * source.
	 *
	 * @return  array  The parameters list
	 */
	public function get_properties()
	{
		$this->is_analyzed() OR $this->analyze();

		return $this->_info['properties'];
	}

	/**
	 * Returns the list of methods with their parsed info from the current
	 * source.
	 *
	 * @return  array  The methods list
	 */
	public function get_methods()
	{
		$this->is_analyzed() OR $this->analyze();

		return $this->_info['methods'];
	}

	/**
	 * Returns a parsable string declaration for the given constant name.
	 *
	 * @throws  Generator_Exception  On invalid constant name
	 * @param   string  $constant  The constant name
	 * @return  string  The constant declaration
	 */
	public function get_constant_declaration($constant)
	{
		$this->is_analyzed() OR $this->analyze();

		if ( ! isset($this->_info['constants'][$constant]))
		{
			throw new Generator_Exception('Constant :constant does not exist', array(
				':constant' => $constant));
		}

		// Create the declaration
		return 'const '.$constant.' = '
			.$this->export_variable($this->_info['constants'][$constant]);
	}

	/**
	 * Returns a parsable string declaration for the given property name.
	 *
	 * @throws  Generator_Exception  On invalid property name
	 * @param   string  $property  The property name
	 * @return  string  The property declaration
	 */
	public function get_property_declaration($property)
	{
		$this->is_analyzed() OR $this->analyze();

		if (empty($this->_info['properties'][$property]))
		{
			throw new Generator_Exception('Property :property does not exist', array(
				':property' => $property));
		}

		$p = $this->_info['properties'][$property];

		// Create the declaration
		$modifiers = $p['modifiers'] ? ($p['modifiers'].' ') : '';
		$value = $p['value'] ? (' = '.$p['value']) : '';

		return $modifiers.'$'.$property.$value;
	}

	/**
	 * Returns the signature for a given method parameter as a parsable string
	 * representation from the current source.
	 *
	 * @throws  Generator_Exception  On invalid parameter name
	 * @param   string  $method  The method name
	 * @param   string  $param   The parameter name
	 * @return  string  The parameter signature
	 */
	public function get_param_signature($method, $param)
	{
		if (empty($this->_info['methods'][$method]['params'][$param]))
		{
			throw new Generator_Exception('Param :param does not exist for method :method',
				array(':param' => $param, ':method' => $method));
		}

		$p = $this->_info['methods'][$method]['params'][$param];

		// Build the signature from the stored info
		$type    = ($p['hint'] AND $p['type']) ? ($p['type'].' ') : '';
		$ref     = $p['by_ref'] ? '& ' : '';
		$default = $p['default'] ? (' = '.$p['default']) : '';

		// Return the parsed signature
		return $type.$ref.'$'.$param.$default;
	}

	/**
	 * Returns the full signature for the given method parameters as a parsable
	 * string representation from the current source.
	 *
	 * @throws  Generator_Exception  On invalid method name
	 * @param   string  $method  The method name
	 * @return  string  The full signature for the parameters
	 */
	public function get_method_param_signatures($method)
	{
		if (empty($this->_info['methods'][$method]))
		{
			throw new Generator_Exception('Method :method does not exist', array(
			':method' => $method));
		}

		// Start the list of signatures
		$sigs = array();

		if ( ! empty($this->_info['methods'][$method]['params']))
		{
			foreach (array_keys($this->_info['methods'][$method]['params']) as $param)
			{
				// Add each parameter signature to the list
				$sigs[] = $this->get_param_signature($method, $param);
			}
		}

		// Return the imploded list
		return implode(', ', $sigs);
	}

	/**
	 * Returns a full method signature as a parsable string representation from
	 * the current source.
	 *
	 * @throws  Generator_Exception  On invalid method name
	 * @param   string  $method  The method name
	 * @return  string  The method signature
	 */
	public function get_method_signature($method)
	{
		$this->is_analyzed() OR $this->analyze();

		if (empty($this->_info['methods'][$method]))
		{
			throw new Generator_Exception('Method :method does not exist', array(
				':method' => $method));
		}

		$m = $this->_info['methods'][$method];

		// Get the method parameter signatures
		$params = $this->get_method_param_signatures($method);

		// Create the full signature
		$ref = $m['by_ref'] ? '& ' : '';
		$modifiers = $m['modifiers'] ? ($m['modifiers'].' ') : '';

		return $modifiers.'function '.$ref.$method.'('.$params.')';
	}

	/**
	 * Returns a parsable string representation of a method invocation from
	 * the current source.
	 *
	 * @throws  Generator_Exception  On invalid method name
	 * @param   string  $method  The method name
	 * @return  string  The method invocation string
	 */
	public function get_method_invocation($method)
	{
		$this->is_analyzed() OR $this->analyze();

		if (empty($this->_info['methods'][$method]))
		{
			throw new Generator_Exception('Method :method does not exist', array(
				':method' => $method));
		}

		// Get the parameters list
		$params = array_keys($this->_info['methods'][$method]['params']);
		$params = array_map(function($p) {return '$'.$p;}, $params);

		return $method.'('.implode(', ', $params).')';
	}

} // End Generator_Generator_Reflector
