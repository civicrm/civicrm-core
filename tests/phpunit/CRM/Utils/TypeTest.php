<?php

/**
 * Class CRM_Utils_TypeTest
 * @package CiviCRM
 * @subpackage CRM_Utils_Type
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
      array('civicrm_column_name', 'MysqlColumnNameOrAlias', 'civicrm_column_name'),
      array('table.civicrm_column_name', 'MysqlColumnNameOrAlias', 'table.civicrm_column_name'),
      array('table.civicrm_column_name.toomanydots', 'MysqlColumnNameOrAlias', NULL),
      array('Home-street_address', 'MysqlColumnNameOrAlias', 'Home-street_address'),
      array('`Home-street_address`', 'MysqlColumnNameOrAlias', '`Home-street_address`'),
      array('`Home-street_address', 'MysqlColumnNameOrAlias', NULL),
      array('table.`Home-street_address`', 'MysqlColumnNameOrAlias', 'table.`Home-street_address`'),
      array('`table-alias`.`Home-street_address`', 'MysqlColumnNameOrAlias', '`table-alias`.`Home-street_address`'),
      array('`table-alias`.column', 'MysqlColumnNameOrAlias', '`table-alias`.column'),
      // Spaces also permitted, only when enclosed in backticks.
      array('`column alias`', 'MysqlColumnNameOrAlias', '`column alias`'),
      array('`table alias`.column', 'MysqlColumnNameOrAlias', '`table alias`.column'),
      array('`table alias`.`column alias`', 'MysqlColumnNameOrAlias', '`table alias`.`column alias`'),
      array('table alias.column alias', 'MysqlColumnNameOrAlias', NULL),
      array('table alias.column_alias', 'MysqlColumnNameOrAlias', NULL),
      array('table_alias.column alias', 'MysqlColumnNameOrAlias', NULL),
      // Functions are not permitted.
      array('column_name, sleep(5)', 'MysqlColumnNameOrAlias', NULL),
      // Length checking permits only 64 chars.
      array(str_repeat('a', 64), 'MysqlColumnNameOrAlias', str_repeat('a', 64)),
      array(str_repeat('a', 65), 'MysqlColumnNameOrAlias', NULL),
      array(str_repeat('a', 64) . '.' . str_repeat('a', 64), 'MysqlColumnNameOrAlias', str_repeat('a', 64) . '.' . str_repeat('a', 64)),
      array('`' . str_repeat('a', 64) . '`.`' . str_repeat('b', 64) . '`', 'MysqlColumnNameOrAlias', '`' . str_repeat('a', 64) . '`.`' . str_repeat('b', 64) . '`'),
      array(str_repeat('a', 64) . '.' . str_repeat('a', 65), 'MysqlColumnNameOrAlias', NULL),
      array(str_repeat('a', 65) . '.' . str_repeat('a', 64), 'MysqlColumnNameOrAlias', NULL),
      // ORDER BY can be ASC or DESC, case not significant.
      array('asc', 'MysqlOrderByDirection', 'asc'),
      array('DESC', 'MysqlOrderByDirection', 'desc'),
      array('DESCc', 'MysqlOrderByDirection', NULL),
      array('table.civicrm_column_name desc', 'MysqlOrderBy', 'table.civicrm_column_name desc'),
      array('field(civicrm_column_name,4,5,6)', 'MysqlOrderBy', 'field(civicrm_column_name,4,5,6)'),
      array('field(table.civicrm_column_name,4,5,6)', 'MysqlOrderBy', 'field(table.civicrm_column_name,4,5,6)'),
      array('table.civicrm_column_name desc,other_column, another_column desc', 'MysqlOrderBy', 'table.civicrm_column_name desc,other_column, another_column desc'),
      array('table.`Home-street_address` asc, `table-alias`.`Home-street_address` desc,`table-alias`.column', 'MysqlOrderBy', 'table.`Home-street_address` asc, `table-alias`.`Home-street_address` desc,`table-alias`.column'),
      // Lab issue dev/core#93 allow for 3 column orderby
      array('contact_id.gender_id.label', 'MysqlOrderBy', 'contact_id.gender_id.label'),
      array('a string', 'String', 'a string'),
      array('{"contact":{"contact_id":205}}', 'Json', '{"contact":{"contact_id":205}}'),
      array('{"contact":{"contact_id":!n†rude®}}', 'Json', NULL),
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
      array('civicrm_column_name', 'MysqlColumnNameOrAlias', '`civicrm_column_name`'),
      array('table.civicrm_column_name', 'MysqlColumnNameOrAlias', '`table`.`civicrm_column_name`'),
      array('table.civicrm_column_name.toomanydots', 'MysqlColumnNameOrAlias', NULL),
      array('Home-street_address', 'MysqlColumnNameOrAlias', '`Home-street_address`'),
      array('`Home-street_address`', 'MysqlColumnNameOrAlias', '`Home-street_address`'),
      array('`Home-street_address', 'MysqlColumnNameOrAlias', NULL),
      array('column_name, sleep(5)', 'MysqlColumnNameOrAlias', NULL),
      array('asc', 'MysqlOrderByDirection', 'asc'),
      array('DESC', 'MysqlOrderByDirection', 'desc'),
      array('DESCc', 'MysqlOrderByDirection', NULL),
      array('table.civicrm_column_name desc', 'MysqlOrderBy', '`table`.`civicrm_column_name` desc'),
      array('field(contribution_status_id,4,5,6) asc', 'MysqlOrderBy', 'field(`contribution_status_id`,4,5,6) asc'),
      array('field(contribution_status_id,4,5,6) asc, contact_id asc', 'MysqlOrderBy', 'field(`contribution_status_id`,4,5,6) asc, `contact_id` asc'),
      array('table.civicrm_column_name desc,other_column,another_column desc', 'MysqlOrderBy', '`table`.`civicrm_column_name` desc, `other_column`, `another_column` desc'),
      array('table.`Home-street_address` asc, `table-alias`.`Home-street_address` desc,`table-alias`.column', 'MysqlOrderBy', '`table`.`Home-street_address` asc, `table-alias`.`Home-street_address` desc, `table-alias`.`column`'),
    );
  }

}
