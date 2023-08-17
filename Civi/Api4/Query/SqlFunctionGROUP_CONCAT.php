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

namespace Civi\Api4\Query;

/**
 * Sql function
 */
class SqlFunctionGROUP_CONCAT extends SqlFunction {

  public $supportsExpansion = TRUE;

  protected static $category = self::CATEGORY_AGGREGATE;

  protected static function params(): array {
    return [
      [
        'flag_before' => ['' => NULL, 'DISTINCT' => ts('Distinct')],
        'max_expr' => 1,
        'must_be' => ['SqlField', 'SqlFunction', 'SqlEquation'],
        'optional' => FALSE,
      ],
      [
        'name' => 'ORDER BY',
        'label' => ts('Order by'),
        'max_expr' => 1,
        'flag_after' => ['ASC' => ts('Ascending'), 'DESC' => ts('Descending')],
        'must_be' => ['SqlField'],
        'optional' => TRUE,
      ],
      [
        'name' => 'SEPARATOR',
        'max_expr' => 1,
        'must_be' => ['SqlString'],
        'optional' => TRUE,
        // @see self::formatOutput()
        'api_default' => [
          'expr' => ['"' . \CRM_Core_DAO::VALUE_SEPARATOR . '"'],
        ],
      ],
    ];
  }

  /**
   * Reformat result as array if using default separator
   *
   * @param string|null $dataType
   * @param array $values
   * @param string $key
   * @see \Civi\Api4\Utils\FormattingUtil::formatOutputValues
   */
  public function formatOutputValue(?string &$dataType, array &$values, string $key): void {
    $exprArgs = $this->getArgs();
    // By default, values are split into an array and formatted according to the field's dataType
    if (isset($exprArgs[2]['expr'][0]->expr) && $exprArgs[2]['expr'][0]->expr === \CRM_Core_DAO::VALUE_SEPARATOR) {
      $values[$key] = explode(\CRM_Core_DAO::VALUE_SEPARATOR, $values[$key]);
      // If the first expression is a SqlFunction/SqlEquation, allow it to control the dataType
      if (method_exists($exprArgs[0]['expr'][0], 'formatOutputValue')) {
        foreach (array_keys($values[$key]) as $index) {
          $exprArgs[0]['expr'][0]->formatOutputValue($dataType, $values[$key], $index);
        }
      }
    }
    // If using custom separator, preserve raw string
    else {
      $dataType = 'String';
    }
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('List');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('All values in the grouping.');
  }

}
