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

use Civi\Api4\MockBasicEntity;
use api\v4\Api4TestBase;
use Civi\Test\Invasive;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class EvaluateConditionTest extends Api4TestBase implements TransactionalInterface {

  public function testEvaluateCondition(): void {
    $method = [MockBasicEntity::get(), 'evaluateCondition'];

    $data = [
      'nada' => 0,
      'uno' => 1,
      'dos' => 2,
      'apple' => 'red',
      'banana' => 'yellow',
      'values' => ['one' => 1, 'two' => 2, 'three' => 3],
    ];

    $this->assertFalse(Invasive::call($method, ['$uno > $dos', $data]));
    $this->assertTrue(Invasive::call($method, ['$uno < $dos', $data]));
    $this->assertTrue(Invasive::call($method, ['$apple == "red" && $banana != "red"', $data]));
    $this->assertFalse(Invasive::call($method, ['$apple == "red" && $banana != "yellow"', $data]));
    $this->assertTrue(Invasive::call($method, ['$values.one == $uno', $data]));
    $this->assertTrue(Invasive::call($method, ['$values.one + $dos == $values.three', $data]));
    $this->assertTrue(Invasive::call($method, ['empty($nada)', $data]));
    $this->assertFalse(Invasive::call($method, ['empty($values)', $data]));
  }

}
