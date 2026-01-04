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
 * Sql function returns the first item in a GROUP_CONCAT set (per the ORDER_BY param)
 * @since 5.69
 */
class SqlFunctionGROUP_FIRST extends SqlFunction {

  public $supportsExpansion = TRUE;

  protected static $category = self::CATEGORY_AGGREGATE;

  protected static function params(): array {
    return [
      [
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
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('First');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('First value in the grouping.');
  }

  /**
   * Render the final expression
   * @param string $output
   * @return string
   */
  public static function renderExpression(string $output): string {
    $sep = \CRM_Core_DAO::VALUE_SEPARATOR;
    return "SUBSTRING_INDEX(GROUP_CONCAT($output SEPARATOR '$sep'), '$sep', 1)";
  }

}
