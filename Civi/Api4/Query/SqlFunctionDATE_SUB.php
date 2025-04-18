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
class SqlFunctionDATE_SUB extends SqlFunction {

  protected static $category = self::CATEGORY_DATE;

  protected static $dataType = 'Date';

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
        'must_be' => ['SqlField'],
      ],
      [
        'label' => ts('Interval to subtract'),
        'must_be' => ['SqlNumber'],
        'flag_before' => ['INTERVAL' => ts('Minus')],
        'flag_after' => [
          'SECOND' => ts('Seconds'),
          'MINUTE' => ts('Minutes'),
          'HOUR' => ts('Hours'),
          'DAY' => ts('Days'),
          'WEEK' => ts('Weeks'),
          'MONTH' => ts('Months'),
          'QUARTER' => ts('Quarters'),
          'YEAR' => ts('Years'),
          'MINUTE_SECOND' => ts('Minutes:Seconds'),
          'HOUR_SECOND' => ts('Hours:Minutes:Seconds'),
          'HOUR_MINUTE' => ts('Hours:Minutes'),
          'DAY_SECOND' => ts('Days Hours:Minutes:Seconds'),
          'DAY_MINUTE' => ts('Days Hours:Minutes'),
          'DAY_HOUR' => ts('Days Hours'),
          'YEAR_MONTH' => ts('Years-Months'),
        ],
        'optional' => FALSE,
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Date Subtraction');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Subtracts a time/date interval from a date.');
  }

}
