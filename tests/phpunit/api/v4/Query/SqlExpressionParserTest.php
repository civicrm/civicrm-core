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
    $this->assertNotEmpty($params[0]['flag_before']);
    $this->assertEmpty($params[0]['flag_after']);

    $sqlFn = new $className($fnName . '(total)');
    $this->assertEquals($fnName, $sqlFn->getName());
    $this->assertEquals(['total'], $sqlFn->getFields());
    $args = $sqlFn->getArgs();
    $this->assertCount(1, $args);
    $this->assertEmpty($args[0]['prefix']);
    $this->assertEmpty($args[0]['suffix']);
    $this->assertTrue(is_a($args[0]['expr'][0], 'Civi\Api4\Query\SqlField'));

    $sqlFn = SqlExpression::convert($fnName . '(DISTINCT stuff)');
    $this->assertEquals($fnName, $sqlFn->getName());
    $this->assertEquals("Civi\Api4\Query\SqlFunction$fnName", get_class($sqlFn));
    $this->assertEquals($params, $sqlFn->getParams());
    $this->assertEquals(['stuff'], $sqlFn->getFields());
    $args = $sqlFn->getArgs();
    $this->assertCount(1, $args);
    $this->assertEquals('DISTINCT', $args[0]['prefix'][0]);
    $this->assertEmpty($args[0]['suffix']);
    $this->assertTrue(is_a($args[0]['expr'][0], 'Civi\Api4\Query\SqlField'));

    try {
      $sqlFn = SqlExpression::convert($fnName . '(*)');
      if ($fnName === 'COUNT') {
        $args = $sqlFn->getArgs();
        $this->assertCount(1, $args);
        $this->assertEmpty($args[0]['prefix']);
        $this->assertEmpty($args[0]['suffix']);
        $this->assertTrue(is_a($args[0]['expr'][0], 'Civi\Api4\Query\SqlWild'));
      }
      else {
        $this->fail('SqlWild should only be allowed in COUNT.');
      }
    }
    catch (\API_Exception $e) {
      $this->assertStringContainsString('Illegal', $e->getMessage());
    }
  }

}
