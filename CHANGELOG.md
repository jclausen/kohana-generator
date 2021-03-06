Generator for Kohana - Changelog
================================

Version 1.1
-----------

- Added `--import` option for `generate:config` and `generate:message`, allows
  importing of existing values into config or message output.
- Added get_config(), get_message() and expand_path() convenience methods to
  Generator_Builder.
- Fixed quoting of values with reserved characters when creating fixtures.
- Added Generator_Builder::globals() method for getting and setting global values
  to be set on all stored generators in one call.
- When using the `generate:fixture --refresh` option, matching files will now
  be replaced by default without needing the `--force` option.
- Inherited methods now return the values of invoked parent methods by default
  unless the return value is known to be void.
- Added support for specifying absolute paths to view template files with new
  Generator_View class.

Version 1.0
-----------

- Added composer.json for Packagist support.
- Various tweaks and code cleanup for Kohana 3.3.0 release.
- Added option for generators to define custom base paths for destination files
  outside of the default APPPATH or MODPATH.

Version 0.8
-----------

- Added get_info() method to Generator_Reflector for debugging purposes only.
- Interface methods are now better tracked, and the original declaration will
  now be reported properly in the doccomments.
- Fixed invocation of inherited methods when cloning.
- Precedence will now be given to any overridden methods when cloning sources
  or implementing abstract methods.
- Added `--use` option to `generate:class` for including traits (PHP >= 5.4.0).
- Added **Trait** generator (PHP >= 5.4.0 only).
- Only concrete classes will now implement inherited or interface methods by
  default, abstract classes will not. When cloning abstract classes, the
  `--inherit` option should be used to include any inherited abstract methods.
- Added support for traits to Generator_Reflector (PHP >= 5.4.0 only).
- Added new `generator/type/stub` template for transparent extension.

Version 0.7
-----------

- Moved all type templates to `views/generator/types`.
- Updated the 'Running the Tasks' guide page with info about task arguments.
- Replaced the Minion validation error template, the new one now has the right
  help info and includes style tags.
- Task help pages now support style tags for colorization of output with any
  consoles that support it (use `--no-ansi` to disable).
- References to modules are now treated either as module names defined in the
  bootstrap or as folders located under MODPATH.
- Positional arguments are now supported by generator tasks - see the 'Usage'
  info in the task `--help` to see which options may be passed as arguments.
- When refreshing fixtures, wildcards may now be used in the `--name` option to
  match a list of fixture files (e.g. `--name=gen_model*`).
- Generators from different builders may now be merged via the new merge()
  method of Generator_Builder.

Version 0.6
-----------

- Added a new guide page: 'Using the Reflector', includes tips on cloning.
- Fixed naming of Models and their tests when stubbed.
- Replaced Kohana_Minion_CLI with Generator_Minion_CLI to be more friendly to
  other modules - still only included temporarily, though.
- Fixed the mixed line ending types for exported arrays with indentation.
- Added a new render_template() method to Generator_Type for easier overriding
  of the templating system.
- Added a new **Fixture** generator for creating simple test fixtures, with some new
  functional tests for the Minion tasks that use them.
- Fixed the tasks produced by `generate:generator` always to extend Task_Generate
  rather than Minion_Task.
- The get_config() method for tasks can now accept an absolute path as the $group
  parameter, and configuration will be loaded directly from that file.
- Directory separators for any guessed filenames should now be consistent with
  the value of DIRECTORY_SEPARATOR.
- If any generated concrete class extends a parent that includes abstract methods,
  skeleton methods for implementing these will be included by default along with
  any interface methods. Abstract classes aren't affected, though.
- Added the option to retrieve only abstract methods via reflection.	
- The `generate:class` task now includes the `--blank` option to prevent any skeleton
  methods from being included.
- There should no longer be any duplicate 'remove' messages when removing,
  although some extra info can still be viewed when using the `--verbose` option,
  which is now disabled by default.
- When removing directories, parents will now be ignored if the child isn't empty.

Version 0.5
-----------

- Added `--clone` option to `generate:interface`, works the same as for classes
  except for the handling of multiple inheritance and other peculiar quirks
  of interfaces in PHP.
- Improved the cloning of classes, especially the handling of inherited methods
  and properties from parents or interfaces with multiple inheritance.
- Added better support for interfaces with multiple inheritance to prevent
  conflicts and re-implementation errors.
- Interface and constant inheritance can now be tracked by Generator_Reflector.
- Minor fixes for task help and examples output, along with some general
  code clean-up across the board.
- Values for the Message type can now use array paths as keys, so works the
  same as for the Config type.

Version 0.4
-----------

- Added a `--clone` option to the `generate:class` task along with a new **Clone**
  type, allowing properties and methods to be copied directly from a class file
  or via reflection (with optional inheritance).
- Extended Generator_Reflector to include class constants, properties and other
  basic reflection info.
- The implemented methods for generated classes will now use the original interface
  doccomments if these are available, otherwise new ones will be created.
- Added new doccomment template.
- Renamed Generator_Reflector::inspect() to analyze(), avoiding confusion with
  the inspect methods of other Generator classes.

Version 0.3
-----------

- Skeleton methods will now be added for classes that implement interfaces - if
  the interfaces exist - to satisfy any interface requirements.
- Generated config values should now have proper indentation.
- Numeric config keys are now set directly and not treated as array paths.
- Added **Generator_Reflector** class to support basic reflection functions.
- Limited access to generator methods that should not be part of their fluent interface.
- Refactored the tasks to reduce the number of required methods, making
  the creation of new tasks simpler.
- Added new get_config() method for all tasks to more easily load default
  configuration values.

Version 0.2
-----------

- Added `generate:task:generator` task as a shortcut for creating generator tasks
  with skeleton methods.
- Config file to use with generator tasks can now be specified with the
  new `--config` option.
- Added colorization of log and inspect output, with a new `--no-ansi` option
	to disable it. Windows users will need to install the [ANSICON console](http://adoxa.110mb.com/ansicon)
	to view the colors.
- Added a custom version of Minion_CLI temporarily to support some
  extra features.

Version 0.1
-----------

- Initial release
