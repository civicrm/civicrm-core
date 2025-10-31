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
 * Sql function returns the nth item in a GROUP_CONCAT set (per the ORDER_BY param)
 * Values start at 1 (not zero like PHP arrays)
 * If N is negative, it will count from the end: -1 is the last item, etc.
 * @since 6.6
 */
class SqlFunctionGROUP_NTH extends SqlFunction {

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
        'name' => 'N=',
        'max_expr' => 1,
        'must_be' => ['SqlNumber'],
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
    return ts('Nth');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Nth value in the grouping.');
  }

  /**
   * Render the final expression
   * @param string $output
   * @return string
   */
  public static function renderExpression(string $output): string {
    // Extract number after 'N= ' and store it
    preg_match('/N=\s*(-?\d+)/', $output, $matches);
    if (empty($matches)) {
      throw new \CRM_Core_Exception('Invalid GROUP_NTH expression: (' . $output . '). Must provide N= x after the field name.');
    }
    $n = (int) $matches[1];
    // Remove 'N= x' from the output
    $output = preg_replace('/N=\s*-?\d+/', '', $output);

    $sep = \CRM_Core_DAO::VALUE_SEPARATOR;
    if ($n === 1) {
      return "SUBSTRING_INDEX(GROUP_CONCAT($output SEPARATOR '$sep'), '$sep', 1)";
    }
    elseif ($n > 0) {
      return "CASE
            WHEN $n <= (LENGTH(GROUP_CONCAT($output SEPARATOR '$sep')) - LENGTH(REPLACE(GROUP_CONCAT($output SEPARATOR '$sep'), '$sep', '')) + 1)
            THEN SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT($output SEPARATOR '$sep'), '$sep', $n), '$sep', -1)
            ELSE NULL
        END";
    }
    else {
      // For negative indices, count from the end
      $abs = abs($n);
      return "CASE
            WHEN $abs <= (LENGTH(GROUP_CONCAT($output SEPARATOR '$sep')) - LENGTH(REPLACE(GROUP_CONCAT($output SEPARATOR '$sep'), '$sep', '')) + 1)
            THEN SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT($output SEPARATOR '$sep'), '$sep', -$abs), '$sep', 1)
            ELSE NULL
        END";
    }
  }

}
