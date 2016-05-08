<?php

/**
 * Class CRM_Utils_TypeTest
 * @group headless
 */
class CRM_Utils_TypeTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * @dataProvider validateDataProvider
   * @param $inputData
   * @param $inputType
   * @param $expectedResult
   */
  public function testValidate($inputData, $inputType, $expectedResult) {
    $this->assertTrue($expectedResult === CRM_Utils_Type::validate($inputData, $inputType, FALSE));
  }

  /**
   * @return array
   */
  public function validateDataProvider() {
    return array(
      array(10, 'Int', 10),
      array('145E+3', 'Int', NULL),
      array('10', 'Integer', 10),
      array(-10, 'Int', -10),
      array('-10', 'Integer', -10),
      array('-10foo', 'Int', NULL),
      array(10, 'Positive', 10),
      array('145.0E+3', 'Positive', NULL),
      array('10', 'Positive', 10),
      array(-10, 'Positive', NULL),
      array('-10', 'Positive', NULL),
      array('-10foo', 'Positive', NULL),
      array('civicrm_column_name', 'MysqlColumnName', 'civicrm_column_name'),
      array('table.civicrm_column_name', 'MysqlColumnName', 'table.civicrm_column_name'),
      array('table.civicrm_column_name.toomanydots', 'MysqlColumnName', NULL),
      array('Home-street_address', 'MysqlColumnName', 'Home-street_address'),
      array('column_name, sleep(5)', 'MysqlColumnName', NULL),
      array(str_repeat('a', 64), 'MysqlColumnName', str_repeat('a', 64)),
      array(str_repeat('a', 65), 'MysqlColumnName', NULL),
      array(str_repeat('a', 64) . '.' . str_repeat('a', 64), 'MysqlColumnName', str_repeat('a', 64) . '.' . str_repeat('a', 64)),
      array(str_repeat('a', 64) . '.' . str_repeat('a', 65), 'MysqlColumnName', NULL),
      array(str_repeat('a', 65) . '.' . str_repeat('a', 64), 'MysqlColumnName', NULL),
      array('asc', 'MysqlOrderByDirection', 'asc'),
      array('DESC', 'MysqlOrderByDirection', 'desc'),
      array('DESCc', 'MysqlOrderByDirection', NULL),
      array('table.civicrm_column_name desc', 'MysqlOrderBy', 'table.civicrm_column_name desc'),
      array('table.civicrm_column_name desc,other_column, another_column desc', 'MysqlOrderBy', 'table.civicrm_column_name desc,other_column, another_column desc'),
    );
  }

  /**
   * @dataProvider escapeDataProvider
   * @param $inputData
   * @param $inputType
   * @param $expectedResult
   */
  public function testEscape($inputData, $inputType, $expectedResult) {
    $this->assertTrue($expectedResult === CRM_Utils_Type::escape($inputData, $inputType, FALSE));
  }

  /**
   * @return array
   */
  public function escapeDataProvider() {
    return array(
      array(10, 'Int', 10),
      array('145E+3', 'Int', NULL),
      array('10', 'Integer', 10),
      array(-10, 'Int', -10),
      array(array(), 'Integer', NULL),
      array('-10foo', 'Int', NULL),
      array(10, 'Positive', 10),
      array('145.0E+3', 'Positive', NULL),
      array('10', 'Positive', 10),
      array(-10, 'Positive', NULL),
      array('-10', 'Positive', NULL),
      array('-10foo', 'Positive', NULL),
      array(array('10', 20), 'Country', array('10', 20)),
      array(array('10', '-10foo'), 'Country', NULL),
      array('', 'Timestamp', ''),
      array('', 'ContactReference', ''),
      array('3', 'ContactReference', 3),
      array('-3', 'ContactReference', NULL),
      // Escape function is meant for sql, not xss
      array('<p onclick="alert(\'xss\');">Hello</p>', 'Memo', '<p onclick=\\"alert(\\\'xss\\\');\\">Hello</p>'),
      array('civicrm_column_name', 'MysqlColumnName', '`civicrm_column_name`'),
      array('table.civicrm_column_name', 'MysqlColumnName', '`table`.`civicrm_column_name`'),
      array('table.civicrm_column_name.toomanydots', 'MysqlColumnName', NULL),
      array('Home-street_address', 'MysqlColumnName', '`Home-street_address`'),
      array('column_name, sleep(5)', 'MysqlColumnName', NULL),
      array('asc', 'MysqlOrderByDirection', 'asc'),
      array('DESC', 'MysqlOrderByDirection', 'desc'),
      array('DESCc', 'MysqlOrderByDirection', NULL),
      array('table.civicrm_column_name desc', 'MysqlOrderBy', '`table`.`civicrm_column_name` desc'),
      array('table.civicrm_column_name desc,other_column,another_column desc', 'MysqlOrderBy', '`table`.`civicrm_column_name` desc, `other_column`, `another_column` desc'),
    );
  }

}
