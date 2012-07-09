<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Test case for Generator_Type_Message.
 *
 * @group      generator
 * @group      generator.types
 *
 * @package    Generator
 * @category   Tests
 * @author     Zeebee
 * @copyright  (c) 2012 Zeebee
 * @license    BSD revised
 */
class Generator_Type_MessageTest extends Unittest_TestCase
{
	/**
	 * Tests that all type options are applied correctly. The Message type
	 * just extends the Config type, so those tests should pass too.
	 */
	public function test_type_options()
	{
		$ds = DIRECTORY_SEPARATOR;

		$type = new Generator_Type_Message();
		$this->assertSame('messages', $type->folder());

		$type->value('a.key|a_value');
		$type->value('b.key |b_value, c.key | c_value, c.d.key|d_value');

		$type->render();
		$params = $type->params();

		$this->assertSame(array(
				'a' => array('key' => 'a_value'),
				'b' => array('key' => 'b_value'),
				'c' => array(
					'key' => 'c_value',
					'd' => array('key' => 'd_value'),
				)
			),
			$params['values']
		);
	}

} // End Generator_Type_MessageTest
