<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Generates a new module skeleton, with a basic directory structure
 * and initial files.
 *
 * <comment>Additional options:</comment>
 *
 *   <info>--name=MODULE</info> <alert>(required)</alert>
 *
 *     The name of the module folder to be created.
 *
 * <comment>Examples</comment>
 * ========
 * <info>minion generate:module --name=mymodule</info>
 *
 *     file : MODPATH/mymodule/init.php
 *     file : MODPATH/mymodule/README.md
 *     file : MODPATH/mymodule/LICENSE
 *     file : MODPATH/mymodule/guide/mymodule/menu.md
 *     file : MODPATH/mymodule/guide/mymodule/index.md
 *     file : MODPATH/mymodule/guide/mymodule/start.md
 *     file : MODPATH/mymodule/config/userguide.php
 *     dir  : MODPATH/mymodule/classes
 *     dir  : MODPATH/mymodule/tests
 *
 * @package    Generator 
 * @category   Tasks 
 * @author     Zeebee 
 * @copyright  (c) 2012 Zeebee 
 * @license    BSD revised 
 */
class Task_Generate_Module extends Generator_Task_Generate_Module {} 
