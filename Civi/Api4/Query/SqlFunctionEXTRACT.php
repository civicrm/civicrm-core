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
class SqlFunctionEXTRACT extends SqlFunction {

  protected static $category = self::CATEGORY_DATE;

  protected static function params(): array {
    return [
      [
        'label' => ts('Unit'),
        'flag_before' => [
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
        'max_expr' => 0,
        'optional' => FALSE,
      ],
      [
        'name' => 'FROM',
        'must_be' => ['SqlField'],
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Partial Date');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Extract part(s) of a date (e.g. the day, year, etc.)');
  }

}
