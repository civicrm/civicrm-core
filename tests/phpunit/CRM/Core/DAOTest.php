<?php

require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Core_DAOTest extends CiviUnitTestCase {
  function composeQueryExamples() {
    $cases = array();
    // $cases[] = array('Input-SQL', 'Input-Params', 'Expected-SQL');

    // CASE: No params
    $cases[] = array(
      'SELECT * FROM whatever',
      array(),
      'SELECT * FROM whatever',
    );

    // CASE: Integer param
    $cases[] = array(
      'SELECT * FROM whatever WHERE id = %1',
      array(
        1 => array(10, 'Integer'),
      ),
      'SELECT * FROM whatever WHERE id = 10',
    );

    // CASE: String param
    $cases[] = array(
      'SELECT * FROM whatever WHERE name = %1',
      array(
        1 => array('Alice', 'String'),
      ),
      'SELECT * FROM whatever WHERE name = \'Alice\'',
    );

    // CASE: Two params
    $cases[] = array(
      'SELECT * FROM whatever WHERE name = %1 AND title = %2',
      array(
        1 => array('Alice', 'String'),
        2 => array('Bob', 'String'),
      ),
      'SELECT * FROM whatever WHERE name = \'Alice\' AND title = \'Bob\'',
    );

    // CASE: Two params with special character (%1)
    $cases[] = array(
      'SELECT * FROM whatever WHERE name = %1 AND title = %2',
      array(
        1 => array('Alice %2', 'String'),
        2 => array('Bob', 'String'),
      ),
      'SELECT * FROM whatever WHERE name = \'Alice %2\' AND title = \'Bob\'',
    );

    // CASE: Two params with special character ($1)
    $cases[] = array(
      'SELECT * FROM whatever WHERE name = %1 AND title = %2',
      array(
        1 => array('Alice $1', 'String'),
        2 => array('Bob', 'String'),
      ),
      'SELECT * FROM whatever WHERE name = \'Alice $1\' AND title = \'Bob\'',
    );

    return $cases;
  }

  /**
   * @dataProvider composeQueryExamples
   */
  function testComposeQuery($inputSql, $inputParams, $expectSql) {
    $actualSql = CRM_Core_DAO::composeQuery($inputSql, $inputParams);
    $this->assertEquals($expectSql, $actualSql);
  }
}
