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
class SqlFunctionTIMESTAMPDIFF extends SqlFunction {

  protected static $category = self::CATEGORY_DATE;

  protected static $dataType = 'Integer';

  protected static function params(): array {
    /* This produces:
     * SELECT `a`.`id` AS `id`, `a`.`description` AS `description`, `a`.`is_active` AS `is_active`, `a`.`last_run_end` AS `last_run_end`,
     * TIMESTAMPDIFF(SECOND `a`.`last_run` , `a`.`last_run`, `a`.`last_run_end`) AS `TIMESTAMPDIFF_last_run_last_run_last_run_end`
     *
     * But we need eg. TIMESTAMPDIFF(SECOND, `a`.`last_run`, `a`.`last_run_end`)
     */
    return [
      [
        'label' => ts('Unit'),
        'flag_before' => [
          'MICROSECOND' => ts('Microseconds'),
          'SECOND' => ts('Seconds'),
          'MINUTE' => ts('Minutes'),
          'HOUR' => ts('Hours'),
          'DAY' => ts('Days'),
          'WEEK' => ts('Weeks'),
          'MONTH' => ts('Months'),
          'QUARTER' => ts('Quarters'),
          'YEAR' => ts('Years'),
        ],
        'max_expr' => 1,
        'optional' => FALSE,
      ],
      [
        'max_expr' => 1,
        'min_expr' => 1,
        'optional' => FALSE,
        'label' => ts('diff'),
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Time between two dates');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Time between two dates.');
  }

  /**
   * @inheritDoc
   */
  public static function renderExpression(string $output): string {
    $expression = explode(' ', $output);
    $first = TRUE;
    $sqlExpression = '';
    foreach ($expression as $expr) {
      $sqlExpression .= $expr;
      if ($first) {
        $sqlExpression .= ',';
      }
      $first = FALSE;
    }
    return "TIMESTAMPDIFF($sqlExpression)";
  }

}
