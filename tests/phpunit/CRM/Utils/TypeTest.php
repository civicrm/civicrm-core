<?php

/**
 * Class CRM_Utils_TypeTest
 * @package CiviCRM
 * @subpackage CRM_Utils_Type
 * @group headless
 */
class CRM_Utils_TypeTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
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
      [TRUE, 'Boolean', TRUE],
      [FALSE, 'Boolean', FALSE],
      ['TRUE', 'Boolean', 'TRUE'],
      ['false', 'Boolean', 'false'],
      ['banana', 'Boolean', NULL],
    ];
  }

  public function getPhpTypeExamples() {
    $es = [];
    $es['int_ok'] = [['int'], 1, 'strictly'];
    $es['int_lax'] = [['int'], '1', 'lackadaisically'];
    $es['int_badstr'] = [['int'], 'one', 'never'];

    $es['float_ok'] = ['float', 1.2, 'strictly'];
    $es['float_lax_int'] = ['float', 123, 'lackadaisically'];
    $es['float_lax_str'] = ['float', '1.2', 'lackadaisically'];
    $es['float_badstr'] = ['float', 'one point two', 'never'];

    $es['double_ok'] = ['double', 1.2, 'strictly'];
    $es['double_lax'] = ['double', '1.2', 'lackadaisically'];
    $es['double_badstr'] = [['double'], 'one point two', 'never'];

    $es['bool_ok'] = ['bool', TRUE, 'strictly'];
    $es['bool_lax_int'] = ['bool', 0, 'lackadaisically'];
    $es['bool_lax_strint'] = ['bool', '1', 'lackadaisically'];
    $es['bool_bad_null'] = ['bool', NULL, 'never'];
    $es['bool_bad_empty'] = ['bool', '', 'never'];
    $es['bool_bad_str'] = ['bool', '1.2', 'never'];

    $es['string_ok'] = [['string'], 'one', 'strictly'];
    $es['string_ok'] = [['string'], 123, 'lackadaisically'];
    $es['string_badarr'] = [['string'], ['a', 'b', 'c'], 'never'];
    $es['string_badobj'] = [['string'], new \stdClass(), 'never'];

    $es['array_ok'] = ['array', [1, 2, 3], 'strictly'];
    $es['array_null_req'] = ['array', NULL, 'never'];

    $es['int[]_ok'] = [['int[]'], [1, 2, 3], 'strictly'];
    $es['int[]_lax'] = [['int[]'], [1, '22', 3], 'lackadaisically'];
    $es['int[]_obj'] = [['int[]'], [1, 2, new \stdClass()], 'never'];
    $es['int[]_single'] = [['int[]'], 1, 'never'];
    $es['int[]_null_req'] = [['int[]'], NULL, 'never'];
    $es['string[]_ok'] = [['string[]'], ['a', 'b', 'c'], 'strictly'];
    $es['string[]_obj'] = [['string[]'], ['a', 'b', new \stdClass()], 'never'];
    $es['string[]_single'] = [['string[]'], 'a', 'never'];
    $es['string[]_null_opt'] = [['string[]'], NULL, 'never'];

    $es['int|null_1'] = ['int|NULL', 1, 'strictly'];
    $es['int|null_null'] = ['int|NULL', NULL, 'strictly'];

    $es['int[]|null_ok'] = ['int[]|NULL', [1, 2, 3], 'strictly'];
    $es['int[]|null_single'] = ['int[]|NULL', 1, 'never'];
    $es['int[]|null_badstr'] = ['int[]|NULL', 'abc', 'never'];

    $es['array|null_ok'] = ['array|NULL', [1, 2, 3], 'strictly'];
    $es['array|null_null'] = ['array|NULL', NULL, 'strictly'];

    $es['DateTimeZone|DateTime_ok_date'] = ['DateTimeZone|DateTime', new \DateTimeZone('UTC'), 'strictly'];
    $es['DateTimeZone|DateTime_ok_datetime'] = ['Date|DateTime', new \DateTime(), 'strictly'];
    $es['DateTimeZone|DateTime_bad_arr'] = ['DateTimeZone|DateTime', [], 'never'];
    $es['DateTimeZone|DateTime_bad_obj'] = ['DateTimeZone|DateTime', new \stdClass(), 'never'];

    $es['Throwable_ok'] = ['Throwable', new \Exception('Somethingsomething'), 'strictly'];
    $es['Throwable|NotReallyAClass_ok'] = ['Throwable|NotReallyAClass', new \Exception('Somethingsomething'), 'strictly'];
    $es['Throwable|NotReallyAClass_bad'] = ['Throwable|NotReallyAClass', 2, 'never'];

    $es['string|false_ok_str'] = ['string|false', 'one', 'strictly'];
    $es['string|false_ok_false'] = ['string|false', FALSE, 'strictly'];
    $es['string|false_bad_true'] = ['string|false', TRUE, 'never'];
    $es['string|false_lax_0'] = ['string|false', 0, 'lackadaisically' /* via string */];
    $es['string|TRUE_ok_true'] = ['string|TRUE', TRUE, 'strictly'];

    return $es;
  }

  public function testValidatePhpType() {

    // This test runs much faster as one test-func rather than data-provider func.
    foreach ($this->getPhpTypeExamples() as $exampleId => $example) {
      [$types, $value, $expectMatches] = $example;

      $strictMatch = CRM_Utils_Type::validatePhpType($value, $types, TRUE);
      $relaxedMatch = CRM_Utils_Type::validatePhpType($value, $types, FALSE);

      switch ($expectMatches) {
        case 'strictly':
          $this->assertEquals(TRUE, $strictMatch, sprintf('(%s) Expect value %s to strictly match type %s', $exampleId, json_encode($value), json_encode($types)));
          $this->assertEquals(TRUE, $relaxedMatch, sprintf('(%s) Expect value %s to laxly match type %s', $exampleId, json_encode($value), json_encode($types)));
          break;

        case 'lackadaisically':
          $this->assertEquals(FALSE, $strictMatch, sprintf('(%s) Expect value %s to strictly NOT match type %s', $exampleId, json_encode($value), json_encode($types)));
          $this->assertEquals(TRUE, $relaxedMatch, sprintf('(%s) Expect value %s to laxly match type %s', $exampleId, json_encode($value), json_encode($types)));
          break;

        case 'never':
          $this->assertEquals(FALSE, $strictMatch, sprintf('(%s) Expect value %s to strictly NOT match type %s', $exampleId, json_encode($value), json_encode($types)));
          $this->assertEquals(FALSE, $relaxedMatch, sprintf('(%s) Expect value %s to laxly NOT match type %s', $exampleId, json_encode($value), json_encode($types)));
          break;

        default:
          throw new \RuntimeException("Unrecognized option: $expectMatches");
      }
    }
  }

}
