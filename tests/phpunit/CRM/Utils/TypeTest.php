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
    return [
      [10, 'Int', 10],
      ['145E+3', 'Int', NULL],
      ['10', 'Integer', 10],
      [-10, 'Int', -10],
      ['-10', 'Integer', -10],
      ['-10foo', 'Int', NULL],
      [10, 'Positive', 10],
      ['145.0E+3', 'Positive', NULL],
      ['10', 'Positive', 10],
      [-10, 'Positive', NULL],
      ['-10', 'Positive', NULL],
      ['-10foo', 'Positive', NULL],
      ['civicrm_column_name', 'MysqlColumnNameOrAlias', 'civicrm_column_name'],
      ['table.civicrm_column_name', 'MysqlColumnNameOrAlias', 'table.civicrm_column_name'],
      ['table.civicrm_column_name.toomanydots', 'MysqlColumnNameOrAlias', NULL],
      ['Home-street_address', 'MysqlColumnNameOrAlias', 'Home-street_address'],
      ['`Home-street_address`', 'MysqlColumnNameOrAlias', '`Home-street_address`'],
      ['`Home-street_address', 'MysqlColumnNameOrAlias', NULL],
      ['table.`Home-street_address`', 'MysqlColumnNameOrAlias', 'table.`Home-street_address`'],
      ['`table-alias`.`Home-street_address`', 'MysqlColumnNameOrAlias', '`table-alias`.`Home-street_address`'],
      ['`table-alias`.column', 'MysqlColumnNameOrAlias', '`table-alias`.column'],
      // Spaces also permitted, only when enclosed in backticks.
      ['`column alias`', 'MysqlColumnNameOrAlias', '`column alias`'],
      ['`table alias`.column', 'MysqlColumnNameOrAlias', '`table alias`.column'],
      ['`table alias`.`column alias`', 'MysqlColumnNameOrAlias', '`table alias`.`column alias`'],
      ['table alias.column alias', 'MysqlColumnNameOrAlias', NULL],
      ['table alias.column_alias', 'MysqlColumnNameOrAlias', NULL],
      ['table_alias.column alias', 'MysqlColumnNameOrAlias', NULL],
      // Functions are not permitted.
      ['column_name, sleep(5)', 'MysqlColumnNameOrAlias', NULL],
      // Length checking permits only 64 chars.
      [str_repeat('a', 64), 'MysqlColumnNameOrAlias', str_repeat('a', 64)],
      [str_repeat('a', 65), 'MysqlColumnNameOrAlias', NULL],
      [str_repeat('a', 64) . '.' . str_repeat('a', 64), 'MysqlColumnNameOrAlias', str_repeat('a', 64) . '.' . str_repeat('a', 64)],
      ['`' . str_repeat('a', 64) . '`.`' . str_repeat('b', 64) . '`', 'MysqlColumnNameOrAlias', '`' . str_repeat('a', 64) . '`.`' . str_repeat('b', 64) . '`'],
      [str_repeat('a', 64) . '.' . str_repeat('a', 65), 'MysqlColumnNameOrAlias', NULL],
      [str_repeat('a', 65) . '.' . str_repeat('a', 64), 'MysqlColumnNameOrAlias', NULL],
      // ORDER BY can be ASC or DESC, case not significant.
      ['asc', 'MysqlOrderByDirection', 'asc'],
      ['DESC', 'MysqlOrderByDirection', 'desc'],
      ['DESCc', 'MysqlOrderByDirection', NULL],
      ['table.civicrm_column_name desc', 'MysqlOrderBy', 'table.civicrm_column_name desc'],
      ['field(civicrm_column_name,4,5,6)', 'MysqlOrderBy', 'field(civicrm_column_name,4,5,6)'],
      ['field(table.civicrm_column_name,4,5,6)', 'MysqlOrderBy', 'field(table.civicrm_column_name,4,5,6)'],
      ['table.civicrm_column_name desc,other_column, another_column desc', 'MysqlOrderBy', 'table.civicrm_column_name desc,other_column, another_column desc'],
      ['table.`Home-street_address` asc, `table-alias`.`Home-street_address` desc,`table-alias`.column', 'MysqlOrderBy', 'table.`Home-street_address` asc, `table-alias`.`Home-street_address` desc,`table-alias`.column'],
      // Lab issue dev/core#93 allow for 3 column orderby
      ['contact_id.gender_id.label', 'MysqlOrderBy', 'contact_id.gender_id.label'],
      ['a string', 'String', 'a string'],
      ['{"contact":{"contact_id":205}}', 'Json', '{"contact":{"contact_id":205}}'],
      ['{"contact":{"contact_id":!n†rude®}}', 'Json', NULL],
    ];
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
    return [
      [10, 'Int', 10],
      ['145E+3', 'Int', NULL],
      ['10', 'Integer', 10],
      [-10, 'Int', -10],
      [[], 'Integer', NULL],
      ['-10foo', 'Int', NULL],
      [10, 'Positive', 10],
      ['145.0E+3', 'Positive', NULL],
      ['10', 'Positive', 10],
      [-10, 'Positive', NULL],
      ['-10', 'Positive', NULL],
      ['-10foo', 'Positive', NULL],
      [['10', 20], 'Country', ['10', 20]],
      [['10', '-10foo'], 'Country', NULL],
      ['', 'Timestamp', ''],
      ['', 'ContactReference', ''],
      ['3', 'ContactReference', 3],
      ['-3', 'ContactReference', NULL],
      // Escape function is meant for sql, not xss
      ['<p onclick="alert(\'xss\');">Hello</p>', 'Memo', '<p onclick=\\"alert(\\\'xss\\\');\\">Hello</p>'],
      ['civicrm_column_name', 'MysqlColumnNameOrAlias', '`civicrm_column_name`'],
      ['table.civicrm_column_name', 'MysqlColumnNameOrAlias', '`table`.`civicrm_column_name`'],
      ['table.civicrm_column_name.toomanydots', 'MysqlColumnNameOrAlias', NULL],
      ['Home-street_address', 'MysqlColumnNameOrAlias', '`Home-street_address`'],
      ['`Home-street_address`', 'MysqlColumnNameOrAlias', '`Home-street_address`'],
      ['`Home-street_address', 'MysqlColumnNameOrAlias', NULL],
      ['column_name, sleep(5)', 'MysqlColumnNameOrAlias', NULL],
      ['asc', 'MysqlOrderByDirection', 'asc'],
      ['DESC', 'MysqlOrderByDirection', 'desc'],
      ['DESCc', 'MysqlOrderByDirection', NULL],
      ['table.civicrm_column_name desc', 'MysqlOrderBy', '`table`.`civicrm_column_name` desc'],
      ['field(contribution_status_id,4,5,6) asc', 'MysqlOrderBy', 'field(`contribution_status_id`,4,5,6) asc'],
      ['field(contribution_status_id,4,5,6) asc, contact_id asc', 'MysqlOrderBy', 'field(`contribution_status_id`,4,5,6) asc, `contact_id` asc'],
      ['table.civicrm_column_name desc,other_column,another_column desc', 'MysqlOrderBy', '`table`.`civicrm_column_name` desc, `other_column`, `another_column` desc'],
      ['table.`Home-street_address` asc, `table-alias`.`Home-street_address` desc,`table-alias`.column', 'MysqlOrderBy', '`table`.`Home-street_address` asc, `table-alias`.`Home-street_address` desc, `table-alias`.`column`'],
    ];
  }

}
