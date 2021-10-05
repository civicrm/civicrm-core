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
        'flag_before' => ['DISTINCT' => ts('Distinct')],
        'max_expr' => 1,
        'must_be' => ['SqlField', 'SqlFunction'],
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
   * @see \Civi\Api4\Utils\FormattingUtil::formatOutputValues
   * @param string $value
   * @param string $dataType
   * @return string|array
   */
  public function formatOutputValue($value, &$dataType) {
    $exprArgs = $this->getArgs();
    // By default, values are split into an array and formatted according to the field's dataType
    if (isset($exprArgs[2]['expr'][0]->expr) && $exprArgs[2]['expr'][0]->expr === \CRM_Core_DAO::VALUE_SEPARATOR) {
      $value = explode(\CRM_Core_DAO::VALUE_SEPARATOR, $value);
      // If the first expression is another sqlFunction, allow it to control the dataType
      if ($exprArgs[0]['expr'][0] instanceof SqlFunction) {
        $exprArgs[0]['expr'][0]->formatOutputValue(NULL, $dataType);
      }
    }
    // If using custom separator, preserve raw string
    else {
      $dataType = 'String';
    }
    return $value;
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
