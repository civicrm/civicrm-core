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


namespace api\v4\Query;

use api\v4\UnitTestCase;
use Civi\Api4\Query\SqlExpression;

/**
 * @group headless
 */
class SqlExpressionParserTest extends UnitTestCase {

  public function aggregateFunctions() {
    return [
      ['AVG'],
      ['COUNT'],
      ['MAX'],
      ['MIN'],
      ['SUM'],
    ];
  }

  /**
   * @param string|\Civi\Api4\Query\SqlFunction $fnName
   * @dataProvider aggregateFunctions
   */
  public function testAggregateFuncitons($fnName) {
    $className = 'Civi\Api4\Query\SqlFunction' . $fnName;
    $params = $className::getParams();
    $this->assertNotEmpty($params[0]['prefix']);
    $this->assertEmpty($params[0]['suffix']);

    $sqlFn = new $className($fnName . '(total)');
    $this->assertEquals($fnName, $sqlFn->getName());
    $this->assertEquals(['total'], $sqlFn->getFields());
    $this->assertCount(1, $this->getArgs($sqlFn));

    $sqlFn = SqlExpression::convert($fnName . '(DISTINCT stuff)');
    $this->assertEquals($fnName, $sqlFn->getName());
    $this->assertEquals("Civi\Api4\Query\SqlFunction$fnName", get_class($sqlFn));
    $this->assertEquals($params, $sqlFn->getParams());
    $this->assertEquals(['stuff'], $sqlFn->getFields());
    $this->assertCount(2, $this->getArgs($sqlFn));

    try {
      $sqlFn = SqlExpression::convert($fnName . '(*)');
      if ($fnName === 'COUNT') {
        $this->assertTrue(is_a($this->getArgs($sqlFn)[0], 'Civi\Api4\Query\SqlWild'));
      }
      else {
        $this->fail('SqlWild should only be allowed in COUNT.');
      }
    }
    catch (\API_Exception $e) {
      $this->assertContains('Illegal', $e->getMessage());
    }
  }

  /**
   * @param \Civi\Api4\Query\SqlFunction $fn
   * @return array
   * @throws \ReflectionException
   */
  private function getArgs($fn) {
    $ref = new \ReflectionClass($fn);
    $args = $ref->getProperty('args');
    $args->setAccessible(TRUE);
    return $args->getValue($fn);
  }

}
