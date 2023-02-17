<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * @group headless
 */
class AbstractActionTest extends Api4TestBase {

  protected function createExample(): AbstractAction {
    return new class('Example', 'gogo') extends AbstractAction {

      /**
       * @var bool
       */
      protected $simpleBool;

      /**
       * @var bool
       */
      protected $magicBool;

      /**
       * @var float
       */
      protected $simpleFloat;

      /**
       * @var float
       */
      protected $magicFloat;

      /**
       * @var int
       */
      protected $simpleInt;

      /**
       * @var int
       */
      protected $magicInt;

      /**
       * @param bool $simpleBool
       */
      public function setSimpleBool(bool $simpleBool) {
        $this->simpleBool = $simpleBool;
        return $this;
      }

      /**
       * @param float $simpleFloat
       */
      public function setSimpleFloat(float $simpleFloat) {
        $this->simpleFloat = $simpleFloat;
        return $this;
      }

      /**
       * @param int $simpleInt
       */
      public function setSimpleInt(int $simpleInt) {
        $this->simpleInt = $simpleInt;
        return $this;
      }

      /**
       * @param \Civi\Api4\Generic\Result $result
       */
      public function _run(Result $result) {
      }

    };
  }

  public function getCastingExamples(): array {
    $exs = [];

    $exs['bool'] = [
      ['simpleBool', 'magicBool'],
      [
        // Each item is an example: [$inputValue, $expectValue]
        [0, FALSE],
        ['0', FALSE],
        [1, TRUE],
        ['1', TRUE],
      ],
    ];

    $exs['float'] = [
      ['simpleFloat', 'magicFloat'],
      [
        // Each item is an example: [$inputValue, $expectValue]
        [0, 0.0],
        ['0', 0.0],
        [100, 100.0],
        ['200', 200.0],
        [300.5, 300.5],
        ['400.6', 400.6],
      ],
    ];

    $exs['int'] = [
      ['simpleInt', 'magicInt'],
      [
        // Each item is an example: [$inputValue, $expectValue]
        [0, 0],
        ['0', 0],
        [100, 100],
        ['200', 200],
      ],
    ];

    // Magic fields are nullable. Not saying that's good or bad. It just is.
    $exs['null'] = [
      ['magicBool', 'magicFloat', 'magicInt'],
      [[NULL, NULL]],
    ];

    return $exs;
  }

  /**
   * When you set a property on an APIv4 action, it should apply some type-casting rules -- even
   * if the property has magic methods.
   *
   * @param string[] $fields
   *   Name of a PHP properties to test. (See `createExample()` object.)
   * @param array $conversions
   *   List of inputs and their expected outputs.
   *   Ex: [[1, TRUE], ['1', TRUE]]
   * @dataProvider getCastingExamples
   * @see \Civi\Api4\Utils\ReflectionUtils::castTypeSoftly()
   */
  public function testCasting(array $fields, array $conversions): void {
    $this->assertTrue(!empty($fields) && !empty($conversions));
    foreach ($fields as $field) {
      foreach ($conversions as $conversion) {
        [$inputValue, $expectValue] = $conversion;
        $desc = sprintf("For field %s, casting should convert %s to %s", $field, json_encode($inputValue), json_encode($expectValue));
        $setter = 'set' . ucfirst($field);
        $getter = 'get' . ucfirst($field);

        $request = $this->createExample();
        call_user_func([$request, $setter], $inputValue);
        $actualValue = call_user_func([$request, $getter]);
        $this->assertEquals(gettype($actualValue), gettype($expectValue), "$desc");
        $this->assertTrue($actualValue === $expectValue, $desc);
      }
    }
  }

}
