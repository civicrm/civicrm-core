<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace api\v4\Action;

use Civi\Api4\MockBasicEntity;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class EvaluateConditionTest extends UnitTestCase {

  public function testEvaluateCondition() {
    $action = MockBasicEntity::get();
    $reflection = new \ReflectionClass($action);
    $method = $reflection->getMethod('evaluateCondition');
    $method->setAccessible(TRUE);

    $data = [
      'nada' => 0,
      'uno' => 1,
      'dos' => 2,
      'apple' => 'red',
      'banana' => 'yellow',
      'values' => ['one' => 1, 'two' => 2, 'three' => 3],
    ];

    $this->assertFalse($method->invoke($action, '$uno > $dos', $data));
    $this->assertTrue($method->invoke($action, '$uno < $dos', $data));
    $this->assertTrue($method->invoke($action, '$apple == "red" && $banana != "red"', $data));
    $this->assertFalse($method->invoke($action, '$apple == "red" && $banana != "yellow"', $data));
    $this->assertTrue($method->invoke($action, '$values.one == $uno', $data));
    $this->assertTrue($method->invoke($action, '$values.one + $dos == $values.three', $data));
    $this->assertTrue($method->invoke($action, 'empty($nada)', $data));
    $this->assertFalse($method->invoke($action, 'empty($values)', $data));
  }

}
